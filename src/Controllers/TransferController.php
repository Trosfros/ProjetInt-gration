<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';
require_once dirname(__DIR__, 2) . '/src/Models/TransactionModel.php';

class TransferController
{
    private const DAILY_LIMIT_CENTS = 1000000;

    private PDO $pdo;
    private TransactionModel $transactionModel;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->transactionModel = new TransactionModel($this->pdo);
    }

    public function getPageData(int $userId): array
    {
        $userStmt = $this->pdo->prepare(
            'SELECT `Balance` FROM `User` WHERE `idUser` = :userId'
        );
        $userStmt->execute(['userId' => $userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $dailySentCents = $this->getDailySentCents($userId);

        return [
            'balance' => (float) ($user['Balance'] ?? 0),
            'dailySent' => $dailySentCents / 100,
            'dailyRemaining' => max(0, self::DAILY_LIMIT_CENTS - $dailySentCents) / 100,
        ];
    }

    public function sendTransfer(int $senderId, string $recipientEmail, string $amount): void
    {
        $recipientEmail = trim($recipientEmail);
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Veuillez saisir une adresse email valide.');
        }

        if (!preg_match('/^(?:0|[1-9]\d{0,4})(?:\.\d{1,2})?$/', trim($amount))) {
            throw new InvalidArgumentException('Veuillez saisir un montant valide avec deux décimales au maximum.');
        }

        $amountCents = $this->toCents($amount);
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Le montant du virement doit être supérieur à 0 $.');
        }

        $recipientStmt = $this->pdo->prepare(
            'SELECT `idUser`, `Description`
             FROM `User`
             WHERE `Description` = :email AND `idUser` <> :senderId
             LIMIT 1'
        );
        $recipientStmt->execute([
            'email' => $recipientEmail,
            'senderId' => $senderId,
        ]);
        $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);
        if (!$recipient) {
            throw new InvalidArgumentException('Aucun autre utilisateur ne possède cette adresse email.');
        }

        $recipientId = (int) $recipient['idUser'];
        $this->pdo->beginTransaction();
        try {
            $lockStmt = $this->pdo->prepare(
                'SELECT `idUser`, `Alias`, `Balance`, `Description`
                 FROM `User`
                 WHERE `idUser` IN (:senderId, :recipientId)
                 ORDER BY `idUser`
                 FOR UPDATE'
            );
            $lockStmt->execute([
                'senderId' => $senderId,
                'recipientId' => $recipientId,
            ]);
            $lockedUsers = [];
            foreach ($lockStmt->fetchAll(PDO::FETCH_ASSOC) as $lockedUser) {
                $lockedUsers[(int) $lockedUser['idUser']] = $lockedUser;
            }
            if (!isset($lockedUsers[$senderId], $lockedUsers[$recipientId])) {
                throw new RuntimeException('Impossible de verrouiller les comptes du virement.');
            }
            if (strcasecmp((string) $lockedUsers[$recipientId]['Description'], (string) $recipient['Description']) !== 0) {
                throw new InvalidArgumentException('Aucun autre utilisateur ne possède cette adresse email.');
            }

            $dailySentCents = $this->getDailySentCents($senderId);
            if ($dailySentCents + $amountCents > self::DAILY_LIMIT_CENTS) {
                $remaining = max(0, self::DAILY_LIMIT_CENTS - $dailySentCents) / 100;
                throw new InvalidArgumentException(
                    'La limite quotidienne de 10 000,00 $ serait dépassée. Montant encore disponible : '
                    . number_format($remaining, 2, ',', ' ') . ' $.'
                );
            }

            $senderBalanceCents = $this->toCents((string) ($lockedUsers[$senderId]['Balance'] ?? '0'));
            if ($amountCents > $senderBalanceCents) {
                throw new InvalidArgumentException('Votre solde est insuffisant pour effectuer ce virement.');
            }

            $formattedAmount = number_format($amountCents / 100, 2, '.', '');
            $updateSender = $this->pdo->prepare(
                'UPDATE `User` SET `Balance` = COALESCE(`Balance`, 0) - :amount WHERE `idUser` = :userId'
            );
            $updateSender->execute(['amount' => $formattedAmount, 'userId' => $senderId]);

            $updateRecipient = $this->pdo->prepare(
                'UPDATE `User` SET `Balance` = COALESCE(`Balance`, 0) + :amount WHERE `idUser` = :userId'
            );
            $updateRecipient->execute(['amount' => $formattedAmount, 'userId' => $recipientId]);

            $this->transactionModel->record(
                $senderId,
                'transfer_sent',
                'Virement envoyé à ' . $recipient['Description'],
                $amountCents / 100,
                'CAD'
            );
            $this->transactionModel->record(
                $recipientId,
                'transfer_received',
                'Virement reçu de ' . $lockedUsers[$senderId]['Alias'],
                $amountCents / 100,
                'CAD'
            );

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function getDailySentCents(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(`Amount`), 0)
             FROM `BankTransaction`
             WHERE `idUser` = :userId
               AND `TransactionType` = \'transfer_sent\'
               AND `CreatedAt` >= CURRENT_DATE()
               AND `CreatedAt` < CURRENT_DATE() + INTERVAL 1 DAY'
        );
        $stmt->execute(['userId' => $userId]);

        return $this->toCents((string) $stmt->fetchColumn());
    }

    private function toCents(string $amount): int
    {
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $matches)) {
            throw new RuntimeException('Le montant enregistré est invalide.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}