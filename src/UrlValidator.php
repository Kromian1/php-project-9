<?php

namespace Analyzer;

use Valitron\Validator;

class UrlValidator
{
    public function validateUrl(string $url): ?string
    {
        $v = new Validator(['url' => $url]);

        $v->rule('required', 'url')->message('URL не должен быть пустым');
        $v->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
        $v->rule('url', 'url')->message('Некорректный URL');

        $v->validate();
        $error = $v->errors();

        return $error['url'][0] ?? null;
    }

    public function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        return strtolower($parsedUrl['scheme'] . '://' . $parsedUrl['host']);
    }
}