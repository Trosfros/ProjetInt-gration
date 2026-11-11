<?php

class AdminMiddleware
{
    public static function isAdmin(PDO $pdo, int $userId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT `IsAdmin` FROM `User` WHERE `idUser` = :userId LIMIT 1'
        );
        $stmt->execute(['userId' => $userId]);

        return (int) $stmt->fetchColumn() === 1;
    }

}