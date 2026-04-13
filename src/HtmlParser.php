<?php

namespace Analyzer;

use Symfony\Component\DomCrawler\Crawler;

class HtmlParser
{
    public function parse(string $body): array
    {
        $crawler = new Crawler($body);

        return [
            'h1' => $crawler->filter('h1')->count() ? $crawler->filter('h1')->text() : '',
            'title' => $crawler->filter('title')->count() ? $crawler->filter('title')->text() : '',
            'description' => $crawler->filter('meta[name="description"]')->count()
                ? $crawler->filter('meta[name="description"]')->attr('content')
                : ''
        ];
    }
}