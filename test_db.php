<?php


function getPDO(): PDO {
    $host     = '127.0.0.1';
    $port     = '3307';
    $dbname   = 'bdnovabank';
    $username = 'root';
    $password = '';

    try {
        return new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}