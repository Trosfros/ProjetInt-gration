<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class NotificationModel
{
    private const NOTIFICATION_TYPES = ['transaction', 'admin_message'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
        $this->ensureTable();
    }

    public function createTransactionNotification(
        int $userId,
        string $description,
        float $amount,
        string $currency
    ): void {
        $message = $description . ' — ' . number_format($amount, 2, ',', ' ') . ' ' . $currency;
        $this->create($userId, 'transaction', 'Transaction complétée', $message);
    }

    public function broadcastAdminMessage(string $title, string $message): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `UserNotification` (`idUser`, `NotificationType`, `Title`, `Message`)
             SELECT `idUser`, 'admin_message', :title, :message
             FROM `User`
             WHERE `email_verified` = 1"
        );
        $stmt->execute([
            'title' => $title,
            'message' => $message,
        ]);

        return $stmt->rowCount();
    }

    public function getRecentForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `idNotification`, `NotificationType`, `Title`, `Message`, `IsRead`, `CreatedAt`
             FROM `UserNotification`
             WHERE `idUser` = :userId
             ORDER BY `CreatedAt` DESC, `idNotification` DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `UserNotification` WHERE `idUser` = :userId AND `IsRead` = 0'
        );
        $stmt->execute(['userId' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `UserNotification` SET `IsRead` = 1 WHERE `idUser` = :userId AND `IsRead` = 0'
        );
        $stmt->execute(['userId' => $userId]);
    }

    private function create(int $userId, string $type, string $title, string $message): void
    {
        if ($userId <= 0 || !in_array($type, self::NOTIFICATION_TYPES, true)) {
            throw new InvalidArgumentException('Les informations de notification sont invalides.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `UserNotification` (`idUser`, `NotificationType`, `Title`, `Message`)
             VALUES (:userId, :type, :title, :message)'
        );
        $stmt->execute([
            'userId' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `UserNotification` (
                `idNotification` INT NOT NULL AUTO_INCREMENT,
                `idUser` INT NOT NULL,
                `NotificationType` ENUM('transaction', 'admin_message') NOT NULL,
                `Title` VARCHAR(150) NOT NULL,
                `Message` TEXT NOT NULL,
                `IsRead` TINYINT(1) NOT NULL DEFAULT 0,
                `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`idNotification`),
                INDEX `idx_user_notification_unread` (`idUser`, `IsRead`, `CreatedAt`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
