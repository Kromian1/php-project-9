<?php

namespace Analyzer\Db;

use PDO;

class Connection
{
    public static function get(): PDO
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        $databaseUrl = 'postgresql://mok1408:1@localhost:15432/page_analyzer_dev'; //дев тест
        $parsedUrl = parse_url($databaseUrl);

        $username = $parsedUrl['user'];
        $password = $parsedUrl['pass'];
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 5432;
        $dbName = ltrim($parsedUrl['path'], '/');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;";

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
