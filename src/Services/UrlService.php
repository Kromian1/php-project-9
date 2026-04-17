<?php

namespace Analyzer\Services;

use Analyzer\Repositories\UrlChecksRepository;
use Analyzer\Repositories\UrlRepository;

class UrlService
{
    private UrlRepository $urlRepository;
    private UrlChecksRepository $urlChecksRepository;

    public function __construct(
        UrlRepository $urlRepository,
        UrlChecksRepository $urlChecksRepository
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlChecksRepository = $urlChecksRepository;
    }

    public function getUrlsWithCode(): array
    {
        $urls = $this->urlRepository->getUrls();
        $lastChecks = $this->urlChecksRepository->getLastChecks();

        $checksIndex = [];

        foreach ($lastChecks as $check) {
            $checksIndex[$check['url_id']] = $check['status_code'];
        }

        $urlsWithCode = [];

        foreach ($urls as $url) {
            $urlsWithCode[] = [
                'id' => $url['id'],
                'name' => $url['name'],
                'status_code' => $checksIndex[$url['id']] ?? '',
                'created_at' => $url['created_at']
            ];
        }

        return $urlsWithCode;
    }
}