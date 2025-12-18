<?php

namespace Coderjerk\Scrapeheap;

/**
 * Throwing the sh*t at a variety of walls
 */
final class Cleaner
{
    public static function htmlToCleanText(string $html): string
    {
        $html = trim($html);
        if ($html === '') return '';

        $wrapped = '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        foreach (['script', 'style', 'noscript', 'svg', 'canvas', 'form', 'button', 'input', 'select', 'textarea', 'iframe'] as $tag) {
            foreach ($xpath->query('//' . $tag) as $n) {
                $n->parentNode?->removeChild($n);
            }
        }

        foreach (['header', 'footer', 'nav', 'aside'] as $tag) {
            foreach ($xpath->query('//' . $tag) as $n) {
                $n->parentNode?->removeChild($n);
            }
        }

        $cruftPatterns = [
            'cookie',
            'consent',
            'banner',
            'modal',
            'popup',
            'sidebar',
            'sidenav',
            'nav',
            'navbar',
            'breadcrumb',
            'toc',
            'table-of-contents',
            'share',
            'social',
            'ad',
            'ads',
            'advert',
            'sponsor',
            'promo',
            'related',
            'recommend',
            'subscribe',
            'newsletter',
            'comments',
            'comment',
            'pagination',
        ];

        $patternExpr = implode('|', array_map('preg_quote', $cruftPatterns));
        $query = "//*[re:match(@class, '(^|\\s)($patternExpr)(\\s|$)', 'i') or re:match(@id, '($patternExpr)', 'i')]";

        $xpath->registerNamespace('re', 'http://exslt.org/regular-expressions');
        try {
            foreach ($xpath->query($query) as $n) {
                $n->parentNode?->removeChild($n);
            }
        } catch (\Throwable $e) {
        }

        $body = $xpath->query('//body')->item(0);
        $text = $body ? self::nodeToText($body) : '';

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n[ \t]+\n/", "\n\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    private static function nodeToText(\DOMNode $node): string
    {
        $out = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $out .= $child->nodeValue;
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $name = strtolower($child->nodeName);

            $blockBefore = in_array($name, ['p', 'div', 'section', 'article', 'br', 'hr', 'pre', 'blockquote', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true);
            if ($blockBefore) $out .= "\n";

            if ($name === 'pre') {
                $out .= "\n" . trim($child->textContent) . "\n";
            } elseif ($name === 'li') {
                $out .= "• " . trim(self::nodeToText($child)) . "\n";
            } elseif (preg_match('/^h[1-6]$/', $name)) {
                $out .= strtoupper(trim($child->textContent)) . "\n";
            } else {
                $out .= self::nodeToText($child);
            }

            if ($blockBefore) $out .= "\n";
        }

        return $out;
    }
}
