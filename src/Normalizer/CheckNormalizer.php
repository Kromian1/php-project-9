<?php

namespace Analyzer\Normalizer;

class CheckNormalizer
{
    private const MAX_LENGTH = 200;

    public function normalizeCheckBody(array $check): array
    {
        return [
            'h1' => $this->truncate($check['h1']),
            'title' => $this->truncate($check['title']),
            'description' => $this->truncate($check['description'])
        ];
    }

    private function truncate(?string $string): string
    {
        if ($string === '' || $string === null) {
            return '';
        }

        if (strlen($string) > self::MAX_LENGTH) {
            return mb_substr($string, 0, self::MAX_LENGTH) . '...';
        }

        return $string;
    }
}
