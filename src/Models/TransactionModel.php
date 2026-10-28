<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class TransactionModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->ensureTransactionTable();
    }

    public function record(int $userId, string $type, string $description, float $amount, string $currency): void
    {
        if (
            $userId <= 0
            || !in_array($type, ['deposit', 'withdrawal', 'purchase', 'sale'], true)
            || !is_finite($amount)
            || $amount <= 0
        ) {
            throw new InvalidArgumentException('Les informations de transaction sont invalides.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `BankTransaction` (`idUser`, `TransactionType`, `Description`, `Amount`, `Currency`)
             VALUES (:userId, :type, :description, :amount, :currency)'
        );
        $stmt->execute([
            'userId' => $userId,
            'type' => $type,
            'description' => $description,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
        ]);
    }

    public function getRecentForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `idTransaction`, `TransactionType`, `Description`, `Amount`, `Currency`, `CreatedAt`
             FROM `BankTransaction`
             WHERE `idUser` = :userId
             ORDER BY `CreatedAt` DESC, `idTransaction` DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ensureTransactionTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS `BankTransaction` (
                `idTransaction` INT NOT NULL AUTO_INCREMENT,
                `idUser` INT NOT NULL,
                `TransactionType` ENUM(\'deposit\', \'withdrawal\', \'purchase\', \'sale\') NOT NULL,
                `Description` VARCHAR(255) NOT NULL,
                `Amount` DECIMAL(15,2) NOT NULL,
                `Currency` CHAR(3) NOT NULL DEFAULT \'CAD\',
                `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`idTransaction`),
                INDEX `idx_bank_transaction_user_date` (`idUser`, `CreatedAt`),
                CONSTRAINT `FK_BankTransaction_User`
                    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}