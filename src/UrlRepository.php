<?php

namespace Db;

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
        $sql = "SELECT id FROM urls WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name', $url);
        $stmt->execute();
        return $stmt->fetch();
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
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUrlName(string $id)
    {
        $sql = "SELECT name FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getAllUrls()
    {
        $sql = "
        SELECT u.id, u.name, u.created_at, 
       (SELECT status_code FROM url_checks WHERE url_id = u.id ORDER BY created_at DESC LIMIT 1) as status_code 
        FROM urls u ORDER BY created_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getChecks(string $id)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateChecks(string $id, string $statusCode, string $h1, string $title, string $description)
    {
        $sql = "
    INSERT INTO url_checks (url_id, status_code, h1, title, description) 
    VALUES (:url_id, :status_code, :h1, :title, :description)
";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':url_id', $id);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
    }

}