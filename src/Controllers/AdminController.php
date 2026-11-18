<?php

require_once dirname(__DIR__) . '/Models/TransactionModel.php';

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

    public function adjustUserBalance(
        int $userId,
        string $direction,
        string $amount,
        string $reason,
        string $adminAlias
    ): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Veuillez sélectionner un utilisateur.');
        }
        if (!in_array($direction, ['credit', 'debit'], true)) {
            throw new InvalidArgumentException('Veuillez choisir un type d’ajustement valide.');
        }
        if (!preg_match('/^(0|[1-9]\d{0,12})(?:\.(\d{1,2}))?$/', trim($amount), $matches)) {
            throw new InvalidArgumentException('Entrez un montant valide avec deux décimales au maximum.');
        }

        $amountCents = ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Le montant doit être supérieur à 0 $.');
        }

        $reason = trim($reason);
        if (preg_match('/^.{1,120}$/us', $reason) !== 1) {
            throw new InvalidArgumentException('Le motif est obligatoire et ne doit pas dépasser 120 caractères.');
        }

        $formattedAmount = intdiv($amountCents, 100)
            . '.'
            . str_pad((string) ($amountCents % 100), 2, '0', STR_PAD_LEFT);
        $delta = $direction === 'credit' ? $formattedAmount : '-' . $formattedAmount;
        $transactionType = $direction === 'credit' ? 'deposit' : 'withdrawal';
        $description = 'Ajustement admin par ' . $adminAlias . ' : ' . $reason;
        $transactionModel = new TransactionModel($this->pdo);

        $this->pdo->beginTransaction();
        try {
            $userStmt = $this->pdo->prepare(
                'SELECT `idUser` FROM `User` WHERE `idUser` = :userId FOR UPDATE'
            );
            $userStmt->execute(['userId' => $userId]);
            if (!$userStmt->fetchColumn()) {
                throw new InvalidArgumentException('Cet utilisateur est introuvable.');
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE `User`
                 SET `Balance` = COALESCE(`Balance`, 0) + :deltaValue
                 WHERE `idUser` = :userId
                   AND COALESCE(`Balance`, 0) + :deltaCheck >= 0'
            );
            $updateStmt->execute([
                'deltaValue' => $delta,
                'userId' => $userId,
                'deltaCheck' => $delta,
            ]);
            if ($updateStmt->rowCount() !== 1) {
                throw new InvalidArgumentException('Le solde ne peut pas devenir négatif.');
            }

            $transactionModel->record(
                $userId,
                $transactionType,
                $description,
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
}