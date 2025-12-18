<?php

namespace Coderjerk\Scrapeheap;

use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Coderjerk\Scrapeheap\Cleaner;
use Coderjerk\Scrapeheap\Document;

class Spider extends BasicSpider
{

    /**
     * @var string[]
     */
    public array $startUrls = [
        'https://roach-php.dev/docs/spiders'
    ];

    /**
     * Keeps track of links that have already been crawled.
     *
     * @var array
     */
    public array $crawled = [];

    /**
     * @var string
     */
    public string $base_domain;

    /**
     * Get all links on a page and then send to be parsed.
     *
     * @param Response $response
     * @return \Generator
     */
    public function parse(Response $response): \Generator
    {

        $links = $response->filter('a')->links();

        if ($links) {
            foreach ($links as $link) {
                if (!in_array($link->getUri(), $this->crawled)) {
                    if ($this->checkUrl($link)) {
                        yield $this->request('GET', $link->getUri(), 'parsePage');
                    }
                } else {
                    array_push($this->crawled, $link->getUri());
                }
            }
        }
    }

    /**
     * Determines if the url is internal and a probably valid http/s url.
     *
     * @param object $link
     * @return boolean
     */
    private function checkUrl(object $link): bool
    {
        // does it look like a url
        if (!parse_url($link->getUri(), PHP_URL_SCHEME) && parse_url($link->getUri(), PHP_URL_HOST)) {
            return false;
        }

        // does it start with http/s
        $pattern = '/(http[s]?\:\/\/)?(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}/';

        if (!preg_match($pattern, $link->getUri())) {
            return false;
        };

        //does the host match the base url host
        $domain = parse_url($link->getUri(), PHP_URL_HOST);

        if ($domain !== $this->context['base_domain']) {
            return false;
        }

        return true;
    }

    /**
     * Parses the page and writes the data to individual MS Word docs.
     *
     * @param Response $response
     * @return \Generator
     */
    public function parsePage(Response $response): \Generator
    {
        $current_uri = $response->getUri();

        $title = $response->filter('title')->count() ? $response->filter('title')->text() : 'Untitled';

        // Prefer main/article content; fall back to body
        $node = null;
        if ($response->filter('main')->count()) {
            $node = $response->filter('main')->first();
        } elseif ($response->filter('article')->count()) {
            $node = $response->filter('article')->first();
        } elseif ($response->filter('body')->count()) {
            $node = $response->filter('body')->first();
        }

        $html = $node ? $node->html() : '<p>(No content extracted)</p>';

        $html = preg_replace('#<(script|style|nav|footer)[^>]*>.*?</\1>#si', '', $html);

        $clean_text = Cleaner::htmlToCleanText($html);

        Document::make($current_uri, $title, $clean_text);

        yield $this->item([
            'title' => $title,
            'content' => $html,
            'uri' => $current_uri
        ]);

        $links = $response->filter('a')->links();


        if ($links) {
            foreach ($links as $link) {
                if (!in_array($link->getUri(), $this->crawled)) {
                    if ($this->checkUrl($link)) {
                        yield $this->request('GET', $link->getUri(), 'parsePage');
                    }
                } else {
                    array_push($this->crawled, $link->getUri());
                }
            }
        }
    }
}
