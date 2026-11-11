<?php

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function getUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `idUser`, `Alias`, `Description`, `Balance`, `IsAdmin`
             FROM `User`
             ORDER BY `Alias`'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}