<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class NotificationModel
{
    private const NOTIFICATION_TYPES = [
        'admin_message',
        'transfer_sent',
        'transfer_received',
        'login_success',
        'low_balance',
    ];

    private static bool $schemaEnsured = false;
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ensureSchema();
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
            'SELECT COUNT(*) FROM `UserNotification`
             WHERE `idUser` = :userId AND `IsRead` = 0'
        );
        $stmt->execute(['userId' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `UserNotification` SET `IsRead` = 1
             WHERE `idUser` = :userId AND `IsRead` = 0'
        );
        $stmt->execute(['userId' => $userId]);
    }

    public function getPreferences(int $userId): array
    {
        $this->assertUserId($userId);
        $insert = $this->pdo->prepare(
            'INSERT IGNORE INTO `UserNotificationPreference` (`idUser`) VALUES (:userId)'
        );
        $insert->execute(['userId' => $userId]);

        $stmt = $this->pdo->prepare(
            'SELECT `NotifyTransfers`, `NotifyLogin`, `NotifyLowBalance`, `LowBalanceThreshold`
             FROM `UserNotificationPreference`
             WHERE `idUser` = :userId'
        );
        $stmt->execute(['userId' => $userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$preferences) {
            throw new RuntimeException('Les préférences de notification sont introuvables.');
        }

        return [
            'transfers' => (bool) $preferences['NotifyTransfers'],
            'logins' => (bool) $preferences['NotifyLogin'],
            'lowBalance' => (bool) $preferences['NotifyLowBalance'],
            'lowBalanceThreshold' => (string) $preferences['LowBalanceThreshold'],
        ];
    }

    public function savePreferences(
        int $userId,
        bool $transfers,
        bool $logins,
        bool $lowBalance,
        string $lowBalanceThreshold
    ): void {
        $this->assertUserId($userId);
        $lowBalanceThreshold = trim($lowBalanceThreshold);
        if (
            !preg_match('/^(?:0|[1-9]\d{0,6})(?:\.\d{1,2})?$/', $lowBalanceThreshold)
            || (float) $lowBalanceThreshold <= 0
        ) {
            throw new InvalidArgumentException('Le seuil de solde bas doit être supérieur à 0 $.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `UserNotificationPreference`
                (`idUser`, `NotifyTransfers`, `NotifyLogin`, `NotifyLowBalance`, `LowBalanceThreshold`)
             VALUES (:userId, :transfers, :logins, :lowBalance, :threshold)
             ON DUPLICATE KEY UPDATE
                `NotifyTransfers` = VALUES(`NotifyTransfers`),
                `NotifyLogin` = VALUES(`NotifyLogin`),
                `NotifyLowBalance` = VALUES(`NotifyLowBalance`),
                `LowBalanceThreshold` = VALUES(`LowBalanceThreshold`)'
        );
        $stmt->execute([
            'userId' => $userId,
            'transfers' => (int) $transfers,
            'logins' => (int) $logins,
            'lowBalance' => (int) $lowBalance,
            'threshold' => $lowBalanceThreshold,
        ]);
    }

    public function notifyLoginSuccess(int $userId): void
    {
        if ($this->getPreferences($userId)['logins']) {
            $this->insertNotification(
                $userId,
                'login_success',
                'Nouvelle connexion',
                'Une connexion à votre compte NovaBank vient d’être effectuée.'
            );
        }
    }

    public function notifyTransfer(
        int $senderId,
        int $recipientId,
        string $senderAlias,
        string $recipientAlias,
        int $amountCents
    ): void {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Le montant du virement est invalide.');
        }

        $amount = number_format($amountCents / 100, 2, ',', ' ') . ' $';
        if ($this->getPreferences($senderId)['transfers']) {
            $this->insertNotification(
                $senderId,
                'transfer_sent',
                'Virement envoyé',
                'Votre virement de ' . $amount . ' à ' . $recipientAlias . ' a été effectué.'
            );
        }
        if ($this->getPreferences($recipientId)['transfers']) {
            $this->insertNotification(
                $recipientId,
                'transfer_received',
                'Virement reçu',
                'Vous avez reçu un virement de ' . $amount . ' de ' . $senderAlias . '.'
            );
        }
    }

    public function notifyLowBalanceCrossing(int $userId, string $previousBalance, string $currentBalance): void
    {
        $preferences = $this->getPreferences($userId);
        if (!$preferences['lowBalance']) {
            return;
        }

        $thresholdCents = $this->toCents($preferences['lowBalanceThreshold']);
        $previousCents = $this->toCents($previousBalance);
        $currentCents = $this->toCents($currentBalance);
        $crossedThreshold = (
            $previousCents > $thresholdCents && $currentCents <= $thresholdCents
        ) || (
            $previousCents === $thresholdCents && $currentCents < $thresholdCents
        );

        if ($crossedThreshold) {
            $this->insertNotification(
                $userId,
                'low_balance',
                'Solde bas',
                'Votre solde disponible est passé sous votre seuil de '
                    . number_format($thresholdCents / 100, 2, ',', ' ') . ' $.'
            );
        }
    }

    private function insertNotification(int $userId, string $type, string $title, string $message): void
    {
        if (!in_array($type, self::NOTIFICATION_TYPES, true) || $type === 'admin_message') {
            throw new InvalidArgumentException('Le type de notification est invalide.');
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

    private function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `UserNotification` (
                `idNotification` INT NOT NULL AUTO_INCREMENT,
                `idUser` INT NOT NULL,
                `NotificationType` ENUM('admin_message', 'transfer_sent', 'transfer_received', 'login_success', 'low_balance') NOT NULL,
                `Title` VARCHAR(150) NOT NULL,
                `Message` TEXT NOT NULL,
                `IsRead` TINYINT(1) NOT NULL DEFAULT 0,
                `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`idNotification`),
                INDEX `idx_user_notification_unread` (`idUser`, `IsRead`, `CreatedAt`),
                CONSTRAINT `FK_UserNotification_User`
                    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `UserNotificationPreference` (
                `idUser` INT NOT NULL,
                `NotifyTransfers` TINYINT(1) NOT NULL DEFAULT 1,
                `NotifyLogin` TINYINT(1) NOT NULL DEFAULT 1,
                `NotifyLowBalance` TINYINT(1) NOT NULL DEFAULT 1,
                `LowBalanceThreshold` DECIMAL(15,2) NOT NULL DEFAULT 100.00,
                PRIMARY KEY (`idUser`),
                CONSTRAINT `FK_UserNotificationPreference_User`
                    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $column = $this->pdo->query(
            "SHOW COLUMNS FROM `UserNotification` LIKE 'NotificationType'"
        )->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            throw new RuntimeException('La colonne de type de notification est introuvable.');
        }

        $existingType = (string) $column['Type'];
        $requiresTypeMigration = false;
        foreach (self::NOTIFICATION_TYPES as $type) {
            if (strpos($existingType, "'" . $type . "'") === false) {
                $requiresTypeMigration = true;
                break;
            }
        }
        if ($requiresTypeMigration) {
            $this->pdo->exec(
                "UPDATE `UserNotification`
                 SET `NotificationType` = 'admin_message'
                 WHERE `NotificationType` = ''"
            );

            if (
                strncasecmp($existingType, 'enum(', 5) !== 0
                || substr($existingType, -1) !== ')'
            ) {
                throw new RuntimeException('Le type de notification existant ne peut pas être migré automatiquement.');
            }

            $enumValues = substr($existingType, 5, -1);
            foreach (self::NOTIFICATION_TYPES as $type) {
                $quotedType = $this->pdo->quote($type);
                if ($quotedType === false) {
                    throw new RuntimeException('Impossible de préparer les nouveaux types de notification.');
                }
                if (strpos($enumValues, $quotedType) === false) {
                    $enumValues .= ($enumValues === '' ? '' : ', ') . $quotedType;
                }
            }

            $this->pdo->exec(
                "ALTER TABLE `UserNotification`
                 MODIFY `NotificationType` ENUM(" . $enumValues . ') NOT NULL'
            );
        }

        self::$schemaEnsured = true;
    }

    private function assertUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('L’utilisateur de notification est invalide.');
        }
    }

    private function toCents(string $amount): int
    {
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $matches)) {
            throw new InvalidArgumentException('Le solde de notification est invalide.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}
