<?php

namespace Analyzer;

class CheckNormalizer
{
    private const MAX_LENGTH = 200;
    public function normalizeChecks(array $checks): array
    {
        $normalizedChecks = [];
        foreach ($checks as $check) {
            $normalizedChecks[] = $this->normalizeCheck($check);
        }

        return $normalizedChecks;
    }

    private function normalizeCheck(array $check): array
    {
        return [
            'id' => $check['id'],
            'url_id' => $check['url_id'],
            'status_code' => $check['status_code'],
            'h1' => $this->truncate($check['h1']),
            'title' => $this->truncate($check['title']),
            'description' => $this->truncate($check['description']),
            'created_at' => $check['created_at']
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
