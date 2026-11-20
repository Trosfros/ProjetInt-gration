<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

function getPDO(): PDO {
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
    $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

    if ($host === '' || $dbname === '' || $username === '') {
        error_log('Database configuration is incomplete.');
        http_response_code(500);
        exit('Service temporairement indisponible.');
    }

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Service temporairement indisponible.');
    }
}

function getQuebecDateTime(string $databaseTimestamp): DateTimeImmutable
{
    $utc = new DateTimeZone('UTC');
    $quebec = new DateTimeZone('America/Toronto');

    return (new DateTimeImmutable($databaseTimestamp, $utc))->setTimezone($quebec);
}
