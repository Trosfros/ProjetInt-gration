<?php

require_once dirname(__DIR__) . '/Models/TransactionModel.php';
require_once dirname(__DIR__) . '/Models/AuditModel.php';

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
        string $adminAlias,
        int $adminUserId
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
            AuditModel::record('admin.balance.adjusted', 'critical', 'Ajustement de solde effectué par un administrateur.', $adminUserId, $userId, [
                'direction' => $direction,
                'amount' => $formattedAmount,
            ], $this->pdo);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getEducationModules(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `idModule`, `Title`, `Badge`, `DisplayOrder`
             FROM `EducationModule`
             ORDER BY `DisplayOrder`, `idModule`'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEducationQuestions(): array
    {
        $stmt = $this->pdo->query(
            'SELECT q.`idQuestion`, q.`Question`, q.`Explanation`, q.`OptionsJson`, q.`CreatedAt`,
                    m.`Title` AS `ModuleTitle`
             FROM `EducationQuestion` q
             INNER JOIN `EducationModule` m ON m.`idModule` = q.`idModule`
             ORDER BY q.`CreatedAt` DESC, q.`idQuestion` DESC
             LIMIT 20'
        );

        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($questions as &$question) {
            $question['Options'] = json_decode((string) $question['OptionsJson'], true) ?: [];
        }
        unset($question);

        return $questions;
    }

    public function addEducationQuestion(
        int $moduleId,
        string $question,
        string $explanation,
        array $optionTexts,
        array $correctOptionIndexes
    ): void {
        $question = trim($question);
        $explanation = trim($explanation);

        if ($moduleId <= 0) {
            throw new InvalidArgumentException('Veuillez sélectionner un niveau éducatif.');
        }
        if (mb_strlen($question, 'UTF-8') < 5 || mb_strlen($question, 'UTF-8') > 500) {
            throw new InvalidArgumentException('La question doit contenir entre 5 et 500 caractères.');
        }
        if (mb_strlen($explanation, 'UTF-8') > 5000) {
            throw new InvalidArgumentException('L’explication ne doit pas dépasser 5 000 caractères.');
        }

        $moduleStmt = $this->pdo->prepare('SELECT 1 FROM `EducationModule` WHERE `idModule` = :moduleId');
        $moduleStmt->execute(['moduleId' => $moduleId]);
        if ($moduleStmt->fetchColumn() === false) {
            throw new InvalidArgumentException('Le niveau éducatif sélectionné est invalide.');
        }

        if (count($optionTexts) < 2 || count($optionTexts) > 6) {
            throw new InvalidArgumentException('Ajoutez entre 2 et 6 choix de réponse.');
        }
        if (count($correctOptionIndexes) !== 1) {
            throw new InvalidArgumentException('Une seule réponse doit être indiquée comme correcte.');
        }

        $correctIndex = (int) $correctOptionIndexes[0];
        $options = [];
        foreach ($optionTexts as $index => $text) {
            $text = trim((string) $text);
            if ($text === '' || mb_strlen($text, 'UTF-8') > 500) {
                throw new InvalidArgumentException('Chaque choix doit contenir entre 1 et 500 caractères.');
            }
            $options[] = [
                'text' => $text,
                'isCorrect' => $index === $correctIndex,
            ];
        }

        if (!isset($options[$correctIndex])) {
            throw new InvalidArgumentException('La réponse correcte sélectionnée est invalide.');
        }

        $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt = $this->pdo->prepare(
            'INSERT INTO `EducationQuestion` (`idModule`, `Question`, `Explanation`, `OptionsJson`)
             VALUES (:moduleId, :question, :explanation, :optionsJson)'
        );
        $stmt->execute([
            'moduleId' => $moduleId,
            'question' => $question,
            'explanation' => $explanation !== '' ? $explanation : null,
            'optionsJson' => $optionsJson,
        ]);
    }

    public function deleteEducationQuestion(int $questionId): void
    {
        if ($questionId <= 0) {
            throw new InvalidArgumentException('Question éducative invalide.');
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM `EducationQuestion`
             WHERE `idQuestion` = :questionId'
        );
        $stmt->execute(['questionId' => $questionId]);

        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('Cette question éducative est introuvable.');
        }
    }
}
