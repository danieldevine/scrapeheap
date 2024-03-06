<?php

namespace Coderjerk\Scrapeheap;

use Coderjerk\Scrapeheap\Spider;
use RoachPHP\Roach;
use RoachPHP\Spider\Configuration\Overrides;


class Scrapeheap
{

    /**
     * Let's scrape.
     *
     * @param string $target_url
     * @return void
     */
    public function scrape(string $target_url): void
    {
        $base_domain = parse_url($target_url, PHP_URL_HOST);

        try {
            Roach::startSpider(
                Spider::class,
                new Overrides(startUrls: [$target_url]),
                context: ['base_domain' => $base_domain],
            );
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        echo '<h3>All Done!</h3>';
        echo 'You scraped: ' . $target_url;
        echo '
        <form action="action.php" method="post">
            <label for="target_url">Scrape again - Target URL</label>
            <input type="url" name="target_url" id="target_url">
            <input type="submit" value="Scrape">
        </form>';
    }
}
