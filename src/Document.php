<?php

namespace Coderjerk\Scrapeheap;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

class Document
{
    /**
     * Creates anf Formats an MS Word document.
     *
     * @param string $target_url
     * @param string $title
     * @param string $content
     * @return void
     */
    public static function make(string $target_url, string $title, string $content): void
    {
        $phpWord = new PhpWord;

        Settings::setOutputEscapingEnabled(true);

        $phpWord->addTitleStyle(
            1,
            ['bold' => true, 'size' => 32],
            ['spaceAfter' => 640]
        );

        // New portrait section
        $section = $phpWord->addSection();

        // Simple text
        $section->addTitle($title, 1);
        $section->addLink($target_url, $target_url);

        // Two text break
        $section->addTextBreak(2);

        $section->addText($content);

        $section->addTextBreak(4);

        // Link
        $section->addLink($target_url, 'View Page');
        $section->addTextBreak(2);

        $domain = parse_url($target_url, PHP_URL_HOST);
        $path = "output/{$domain}/";

        // Save file
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'HTML');

        $objWriter->save("{$path}{$title}.docx");
    }
}
