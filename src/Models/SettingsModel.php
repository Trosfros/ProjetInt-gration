<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';
require_once __DIR__ . '/NotificationModel.php';

class SettingsModel
{
    private PDO $pdo;
    private NotificationModel $notificationModel;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->notificationModel = new NotificationModel($this->pdo);
    }

    public function getNotificationPreferences(int $userId): array
    {
        return $this->notificationModel->getPreferences($userId);
    }

    public function saveNotificationPreferences(
        int $userId,
        bool $transfers,
        bool $logins,
        bool $lowBalance,
        string $lowBalanceThreshold
    ): void {
        $this->notificationModel->savePreferences(
            $userId,
            $transfers,
            $logins,
            $lowBalance,
            $lowBalanceThreshold
        );
    }

    public function deleteUserAccount(int $userId, string $password, string $confirmation): void
    {
        if ($userId <= 0 || $password === '' || !hash_equals('SUPPRIMER', trim($confirmation))) {
            throw new InvalidArgumentException('Confirmez la suppression et saisissez votre mot de passe.');
        }

        $this->pdo->beginTransaction();
        try {
            $userStmt = $this->pdo->prepare(
                'SELECT `Alias`, `Description`, `Password`, `IsAdmin`
                 FROM `User`
                 WHERE `idUser` = :userId
                 FOR UPDATE'
            );
            $userStmt->execute(['userId' => $userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new RuntimeException('Ce compte utilisateur est introuvable.');
            }
            if (!password_verify($password, (string) $user['Password'])) {
                throw new InvalidArgumentException('Le mot de passe saisi est incorrect.');
            }

            if ((int) $user['IsAdmin'] === 1) {
                $adminsStmt = $this->pdo->prepare(
                    'SELECT `idUser`
                     FROM `User`
                     WHERE `IsAdmin` = 1 AND `idUser` <> :userId
                     FOR UPDATE'
                );
                $adminsStmt->execute(['userId' => $userId]);
                if ($adminsStmt->fetchColumn() === false) {
                    throw new InvalidArgumentException('Le dernier compte administrateur ne peut pas être supprimé.');
                }
            }

            $this->anonymizeCounterpartyTransactions($userId, (string) $user['Alias'], (string) $user['Description']);

            foreach (['StockInvest', 'StockFavorite', 'CryptoInvest', 'Account'] as $table) {
                $deleteStmt = $this->pdo->prepare(
                    'DELETE FROM `' . $table . '` WHERE `'
                    . ($table === 'Account' ? 'idUser' : 'IdUser')
                    . '` = :userId'
                );
                $deleteStmt->execute(['userId' => $userId]);
            }

            if ($this->tableExists('SecurityAuditLog')) {
                $auditStmt = $this->pdo->prepare(
                    'DELETE FROM `SecurityAuditLog`
                     WHERE `actorUserId` = :actorUserId OR `targetUserId` = :targetUserId'
                );
                $auditStmt->execute([
                    'actorUserId' => $userId,
                    'targetUserId' => $userId,
                ]);
            }

            $deleteUser = $this->pdo->prepare('DELETE FROM `User` WHERE `idUser` = :userId');
            $deleteUser->execute(['userId' => $userId]);
            if ($deleteUser->rowCount() !== 1) {
                throw new RuntimeException('La suppression du compte utilisateur a échoué.');
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function anonymizeCounterpartyTransactions(int $userId, string $alias, string $email): void
    {
        $receivedStmt = $this->pdo->prepare(
            "UPDATE `BankTransaction`
             SET `Description` = 'Virement reçu'
             WHERE `idUser` <> :userId
               AND `TransactionType` = 'transfer_received'
               AND `Description` = :description"
        );
        $receivedStmt->execute([
            'userId' => $userId,
            'description' => 'Virement reçu de ' . $alias,
        ]);

        $sentStmt = $this->pdo->prepare(
            "UPDATE `BankTransaction`
             SET `Description` = 'Virement envoyé'
             WHERE `idUser` <> :userId
               AND `TransactionType` = 'transfer_sent'
               AND `Description` = :description"
        );
        $sentStmt->execute([
            'userId' => $userId,
            'description' => 'Virement envoyé à ' . $email,
        ]);
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :tableName
             LIMIT 1'
        );
        $stmt->execute(['tableName' => $tableName]);
        return $stmt->fetchColumn() !== false;
    }
}
