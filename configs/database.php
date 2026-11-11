<?php

function ensureEmailVerificationColumns(PDO $pdo): void {
    $columns = $pdo->query("SHOW COLUMNS FROM `User`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('email_verified', $columns, true)) {
        $pdo->exec("ALTER TABLE `User` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Description`");
    }

    if (!in_array('email_verification_token', $columns, true)) {
        $pdo->exec("ALTER TABLE `User` ADD COLUMN `email_verification_token` VARCHAR(255) NULL AFTER `email_verified`");
    }

    if (!in_array('IsAdmin', $columns, true)) {
        $pdo->exec("ALTER TABLE `User` ADD COLUMN `IsAdmin` TINYINT(1) NOT NULL DEFAULT 0");
    }
}

function getPDO(): PDO {
    $host     = '127.0.0.1';
    $port     = '3307';
    $dbname   = 'bdnovabank';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        ensureEmailVerificationColumns($pdo);

        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}