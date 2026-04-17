<?php

namespace Analyzer\Repositories;

use PDO;

class UrlRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getId(string $url): int
    {
        $sql = "
    SELECT
        id
    FROM
        urls
    WHERE
        name = :name
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name', $url);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function addUrl(string $url): string
    {
        $sql = "INSERT INTO urls (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name', $url);
        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    public function getUrl(string $id): array
    {
        $sql = "
    SELECT
        *
    FROM
        urls
    WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUrlName(string $id): mixed
    {
        $sql = "
    SELECT
        name
    FROM
        urls
    WHERE
        id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getUrls(): array
    {
        $sql = "
    SELECT 
        id,
        name,
        created_at,
    FROM urls
    ORDER BY created_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
