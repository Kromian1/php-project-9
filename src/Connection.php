<?php

namespace Db;
use PDO;

class Connection
{
    public static function get(): PDO
    {
        //$databaseUrl = 'postgresql://mok1408:1@localhost:5432/page_analyzer_dev'; для мака тестовый запуск
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL'); //прод
        $parsedUrl = parse_url($databaseUrl);

        $username = $parsedUrl['user'];
        $password = $parsedUrl['pass'];
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 5432;
        $dbName = ltrim($parsedUrl['path'], '/');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;sslmode=require";

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
