<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class NotificationModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
        $this->ensureTable();
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
             WHERE `idUser` = :userId AND `NotificationType` = \'admin_message\'
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
            "SELECT COUNT(*) FROM `UserNotification`
             WHERE `idUser` = :userId AND `NotificationType` = 'admin_message' AND `IsRead` = 0"
        );
        $stmt->execute(['userId' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE `UserNotification` SET `IsRead` = 1
             WHERE `idUser` = :userId AND `NotificationType` = 'admin_message' AND `IsRead` = 0"
        );
        $stmt->execute(['userId' => $userId]);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `UserNotification` (
                `idNotification` INT NOT NULL AUTO_INCREMENT,
                `idUser` INT NOT NULL,
                `NotificationType` ENUM('admin_message') NOT NULL,
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
