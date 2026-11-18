<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class SupportModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
        $this->ensureTable();
    }

    public function createRequest(int $userId, string $subject, string $message): void
    {
        $subject = trim($subject);
        $message = trim($message);
        if ($userId <= 0) {
            throw new InvalidArgumentException('Votre session utilisateur est invalide.');
        }
        if (preg_match('/^[^\r\n]{1,150}$/u', $subject) !== 1) {
            throw new InvalidArgumentException('Le sujet est obligatoire et ne doit pas dépasser 150 caractères.');
        }
        if (preg_match('/^.{1,4000}$/us', $message) !== 1) {
            throw new InvalidArgumentException('Votre message est obligatoire et ne doit pas dépasser 4 000 caractères.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `SupportRequest` (`idUser`, `Subject`, `Message`)
             VALUES (:userId, :subject, :message)'
        );
        $stmt->execute([
            'userId' => $userId,
            'subject' => $subject,
            'message' => $message,
        ]);
    }

    public function getRequestsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `idSupportRequest`, `Subject`, `Message`, `Status`, `AdminResponse`,
                    `CreatedAt`, `AnsweredAt`
             FROM `SupportRequest`
             WHERE `idUser` = :userId
             ORDER BY `CreatedAt` DESC, `idSupportRequest` DESC'
        );
        $stmt->execute(['userId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRequests(): array
    {
        $stmt = $this->pdo->query(
            'SELECT sr.`idSupportRequest`, sr.`idUser`, sr.`Subject`, sr.`Message`,
                    sr.`Status`, sr.`AdminResponse`, sr.`CreatedAt`, sr.`AnsweredAt`,
                    u.`Alias`, u.`Description`
             FROM `SupportRequest` sr
             INNER JOIN `User` u ON u.`idUser` = sr.`idUser`
             ORDER BY (sr.`Status` = \'open\') DESC, sr.`CreatedAt` ASC, sr.`idSupportRequest` ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function replyToRequest(int $requestId, string $response): void
    {
        $response = trim($response);
        if ($requestId <= 0) {
            throw new InvalidArgumentException('La demande sélectionnée est invalide.');
        }
        if (preg_match('/^.{1,4000}$/us', $response) !== 1) {
            throw new InvalidArgumentException('La réponse est obligatoire et ne doit pas dépasser 4 000 caractères.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE `SupportRequest`
             SET `AdminResponse` = :response, `Status` = 'answered', `AnsweredAt` = CURRENT_TIMESTAMP
             WHERE `idSupportRequest` = :requestId AND `Status` = 'open'"
        );
        $stmt->execute([
            'response' => $response,
            'requestId' => $requestId,
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('Cette demande est introuvable ou a déjà reçu une réponse.');
        }
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `SupportRequest` (
                `idSupportRequest` INT NOT NULL AUTO_INCREMENT,
                `idUser` INT NOT NULL,
                `Subject` VARCHAR(150) NOT NULL,
                `Message` TEXT NOT NULL,
                `Status` ENUM('open', 'answered') NOT NULL DEFAULT 'open',
                `AdminResponse` TEXT NULL,
                `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `AnsweredAt` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`idSupportRequest`),
                INDEX `idx_support_request_status_date` (`Status`, `CreatedAt`),
                INDEX `idx_support_request_user_date` (`idUser`, `CreatedAt`),
                CONSTRAINT `FK_SupportRequest_User`
                    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}