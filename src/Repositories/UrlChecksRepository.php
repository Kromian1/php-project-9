<?php

namespace Analyzer\Repositories;

class UrlChecksRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getChecks(string $id)
    {
        $sql = "
    SELECT
        *
    FROM
        url_checks
    WHERE
        url_id = :id
    ORDER BY
        created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateChecks(string $id, string $statusCode, array $data)
    {
        $sql = "
    INSERT INTO url_checks (url_id, status_code, h1, title, description)
    VALUES (:url_id, :status_code, :h1, :title, :description)
";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':url_id', $id);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':h1', $data['h1']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->execute();
    }
}