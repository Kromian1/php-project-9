<?php

namespace Analyzer;

class UrlValidator
{
    public function validateUrl(string $url): array
    {
        $errors = [];
        if (empty($url)) {
            $errors[] = 'URL не должен быть пустым';
        }
        if (strlen($url) > 255) {
            $errors[] = 'URL превышает 255 символов';
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL';
        }

        return $errors;
    }

    public function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        return strtolower($parsedUrl['scheme'] . '://' . $parsedUrl['host']);
    }
}