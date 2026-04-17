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

    public function getId(string $url)
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

    public function addUrl(string $url)
    {
        $sql = "INSERT INTO urls (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name', $url);
        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    public function getUrl(string $id)
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

    public function getUrlName(string $id)
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

    public function getAllUrls()
    {
        $sql = "
    WITH ranked_checks AS (
        SELECT 
            url_id,
            status_code,
            ROW_NUMBER() OVER (PARTITION BY url_id ORDER BY created_at DESC) AS rn
        FROM url_checks
    )
    SELECT 
        u.id,
        u.name,
        u.created_at,
        rc.status_code
    FROM urls u
    LEFT JOIN ranked_checks rc ON u.id = rc.url_id AND rc.rn = 1
    ORDER BY u.created_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
