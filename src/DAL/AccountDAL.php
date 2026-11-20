<?php
require_once dirname(__DIR__, 2) . "/configs/database.php";
require_once dirname(__DIR__, 2) . "/src/Models/TransactionModel.php";
require_once dirname(__DIR__, 2) . '/src/Models/AuditModel.php';

class AccountDAL
{
    private const MAX_ACCOUNT_BALANCE_CENTS = 999999999999999;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function createAccount(string $Alias, string $accountName, ?string $accountType = 'checking', ?string $currency = 'CAD'): array
    {
        $accountName = trim($accountName);
        $accountType = $accountType ?? 'checking';
        $currency = $currency ?? 'CAD';

        if ($accountName === '') {
            return ['success' => false, 'message' => 'Le nom du compte est requis.'];
        }
        if (!in_array($accountType, ['checking', 'savings', 'investment'], true)) {
            return ['success' => false, 'message' => 'Le type de compte est invalide.'];
        }
        if (!in_array($currency, ['CAD', 'USD'], true)) {
            return ['success' => false, 'message' => 'La devise sélectionnée est invalide.'];
        }

        $userId = $this->GetActiveUserID($Alias);
        $this->pdo->beginTransaction();
        try {
            $userStmt = $this->pdo->prepare(
                'SELECT `idUser` FROM `User` WHERE `idUser` = :userId FOR UPDATE'
            );
            $userStmt->execute(['userId' => $userId]);
            if (!$userStmt->fetchColumn()) {
                throw new RuntimeException('Utilisateur introuvable.');
            }

            $duplicateStmt = $this->pdo->prepare(
                "SELECT `idAccount`
                 FROM `Account`
                 WHERE `idUser` = :userId
                   AND `AccountName` = :accountName
                   AND `Status` NOT IN ('closed', 'Fermée')
                 LIMIT 1
                 FOR UPDATE"
            );
            $duplicateStmt->execute(['userId' => $userId, 'accountName' => $accountName]);
            if ($duplicateStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Un compte actif porte déjà ce nom.'];
            }

            $insertStmt = $this->pdo->prepare(
                'INSERT INTO `Account` (`idUser`, `Money`, `AccountName`, `AccountNumber`, `AccountType`, `Currency`)
                 VALUES (:userId, 0, :accountName, :accountNumber, :accountType, :currency)'
            );
            $insertStmt->execute([
                'userId' => $userId,
                'accountName' => $accountName,
                'accountNumber' => bin2hex(random_bytes(16)),
                'accountType' => $accountType,
                'currency' => $currency,
            ]);
            $accountId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            AuditModel::record('account.created', 'low', 'Compte bancaire créé.', $userId, $userId, [
                'accountId' => $accountId,
                'accountType' => $accountType,
                'currency' => $currency,
            ], $this->pdo);

            return ['success' => true, 'message' => 'Compte créé avec succès.'];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    public function AddMoneyToAccount(string $accountName, string $username, string $money): array
    {
        return $this->moveAccountMoney($accountName, $username, $money, true);
    }

    public function RemoveMoneyFromAccount(string $accountName, string $username, string $money): array
    {
        return $this->moveAccountMoney($accountName, $username, $money, false);
    }

    private function moveAccountMoney(string $accountName, string $username, string $money, bool $deposit): array
    {
        if (!preg_match('/^(?:0|[1-9]\d{0,12})\.(\d{2})$/', $money, $amountParts)) {
            return ['success' => false, 'message' => 'Saisissez un montant valide.'];
        }

        $amountCents = ((int) substr($money, 0, strpos($money, '.')) * 100) + (int) $amountParts[1];
        if ($amountCents <= 0) {
            return ['success' => false, 'message' => 'Le montant doit être supérieur à 0 $.'];
        }

        $idAccount = $this->GetAccountID($accountName, $username);
        $idUser = $this->GetActiveUserID($username);
        $transactionModel = new TransactionModel($this->pdo);

        $this->pdo->beginTransaction();
        try {
            $accountStmt = $this->pdo->prepare(
                'SELECT `idUser`, `Money`, `OverdraftLimit`, `Status`, `Currency`
                 FROM `Account`
                 WHERE `idAccount` = :accountId
                 FOR UPDATE'
            );
            $accountStmt->execute(['accountId' => $idAccount]);
            $account = $accountStmt->fetch(PDO::FETCH_ASSOC);

            if (!$account || (int) $account['idUser'] !== $idUser) {
                throw new RuntimeException('Le compte demandé est introuvable.');
            }
            if (!in_array($account['Status'], ['active', 'actif'], true)) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Ce compte n’est pas actif.'];
            }

            $accountCents = $this->toAccountCents((string) $account['Money']);
            if ($deposit) {
                if ($accountCents > self::MAX_ACCOUNT_BALANCE_CENTS - $amountCents) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Le solde du compte dépasserait la limite autorisée.'];
                }

                $userStmt = $this->pdo->prepare(
                    'SELECT COALESCE(`Balance`, 0) >= CAST(:amount AS DECIMAL(15,2)) AS `hasBalance`
                     FROM `User`
                     WHERE `idUser` = :userId
                     FOR UPDATE'
                );
                $userStmt->execute(['amount' => $money, 'userId' => $idUser]);
                if (!(bool) $userStmt->fetchColumn()) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Votre solde disponible est insuffisant pour ajouter ce montant.'];
                }

                $updateAccount = $this->pdo->prepare(
                    'UPDATE `Account` SET `Money` = `Money` + CAST(:amount AS DECIMAL(15,2)) WHERE `idAccount` = :accountId'
                );
                $updateAccount->execute(['amount' => $money, 'accountId' => $idAccount]);
                $updateUser = $this->pdo->prepare(
                    'UPDATE `User` SET `Balance` = COALESCE(`Balance`, 0) - CAST(:amount AS DECIMAL(15,2)) WHERE `idUser` = :userId'
                );
                $updateUser->execute(['amount' => $money, 'userId' => $idUser]);
                $type = 'deposit';
                $message = 'Ajout de l’argent au compte effectué avec succès.';
            } else {
                $overdraftCents = $this->toAccountCents((string) $account['OverdraftLimit']);
                if ($accountCents - $amountCents < -$overdraftCents) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Le solde du compte est insuffisant pour effectuer ce retrait.'];
                }

                $userStmt = $this->pdo->prepare('SELECT `idUser` FROM `User` WHERE `idUser` = :userId FOR UPDATE');
                $userStmt->execute(['userId' => $idUser]);
                if (!$userStmt->fetchColumn()) {
                    throw new RuntimeException('Utilisateur introuvable.');
                }

                $updateAccount = $this->pdo->prepare(
                    'UPDATE `Account` SET `Money` = `Money` - CAST(:amount AS DECIMAL(15,2)) WHERE `idAccount` = :accountId'
                );
                $updateAccount->execute(['amount' => $money, 'accountId' => $idAccount]);
                $updateUser = $this->pdo->prepare(
                    'UPDATE `User` SET `Balance` = COALESCE(`Balance`, 0) + CAST(:amount AS DECIMAL(15,2)) WHERE `idUser` = :userId'
                );
                $updateUser->execute(['amount' => $money, 'userId' => $idUser]);
                $type = 'withdrawal';
                $message = 'Retrait effectué avec succès.';
            }

            $this->recordAccountTransaction($transactionModel, $idUser, $idAccount, $type, $money);
            $this->pdo->commit();

            $risk = $deposit ? 'medium' : 'high';
            AuditModel::record(
                $deposit ? 'account.deposit' : 'account.withdrawal',
                $risk,
                $deposit ? 'Transfert vers un compte interne effectué.' : 'Retrait depuis un compte interne effectué.',
                $idUser,
                $idUser,
                ['accountId' => $idAccount, 'amount' => $money, 'currency' => (string) ($account['Currency'] ?? 'CAD')],
                $this->pdo
            );

            return ['success' => true, 'message' => $message];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function toAccountCents(string $amount): int
    {
        if (!preg_match('/^(-?)(?:0|[1-9]\d{0,12})\.(\d{2})$/', $amount, $parts)) {
            throw new RuntimeException('Le solde enregistré du compte est invalide.');
        }

        $wholeAmount = substr($amount, 0, strpos($amount, '.'));
        $negative = str_starts_with($wholeAmount, '-');
        if ($negative) {
            $wholeAmount = substr($wholeAmount, 1);
        }
        $cents = ((int) $wholeAmount * 100) + (int) $parts[2];

        return $negative ? -$cents : $cents;
    }
    public function GetUserAccounts(string $Alias): array
    {
        $idUser = $this->GetActiveUserID($Alias);

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Account WHERE ? = idUser");
            $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

    }
    public function GetAccount(int $accountId, string $username): array
    {
         $idUser = $this->GetActiveUserID($username);
        try {
            $stmt = $this->pdo->prepare("Select * from Account where idUser =? and idAccount = ? ");
            $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
            $stmt->bindParam(2, $accountId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '450000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];

            }
            throw $e;
        }
    }
   public function DeleteAccount(int $accountId, string $username): array
{
    try {
        $acc = $this->GetAccount($accountId, $username);

        if ((float) $acc["Money"] > 0) {
            return ['success' => false, 'message' => "Vous ne pouvez pas fermer un compte s'il reste de l'argent dedans."];
        }

        $stmt = $this->pdo->prepare("CALL deleteAccount(?)");
        $stmt->bindParam(1, $accountId, PDO::PARAM_INT);
        $stmt->execute();
        $userId = $this->GetActiveUserID($username);
        AuditModel::record('account.closed', 'medium', 'Compte fermé.', $userId, $userId, ['accountId' => $accountId], $this->pdo);
        return ['success' => true, 'message' => "Compte fermé avec succès."];
    } catch (PDOException $e) {
        if ($e->errorInfo[0] === '45000') {
            return ['success' => false, 'message' => $e->errorInfo[2]];
        }
        throw $e;
    }
}



    public function GetActiveUserID(string $username): int
    {
        $stmt = $this->pdo->prepare("SELECT idUser from User where ? = Alias");
        $stmt->bindParam(1, $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$result) {
            throw new RuntimeException("no user found under the username " . $username);
        }
        return (int) $result["idUser"];
    }
    public function GetAccountID(string $accName, $username): int
    {
        $userid = $this->GetActiveUserID($username);
        $stmt = $this->pdo->prepare("SELECT idAccount from Account where AccountName = ? and idUser = ? ");
        $stmt->bindParam(1, $accName, PDO::PARAM_STR);
        $stmt->bindParam(2, $userid, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$result) {
            throw new RuntimeException("no account found under the name " . $accName);
        }
        return (int) $result["idAccount"];
    }
    public function GetAccounts(string $username): array
    {
        $idUser = $this->GetActiveUserID($username);
        $stmt = $this->pdo->prepare("SELECT * FROM Account WHERE idUser = ? AND Status != 'closed'");
        $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $result;
    }
    public function GetUserBalance(string $username): float
    {
        $idUser = $this->GetActiveUserID($username);
        $stmt = $this->pdo->prepare("SELECT Balance FROM User WHERE idUser = ?");
        $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$result) {
            throw new RuntimeException("User not found while fetching balance.");
        }

        return (float) $result['Balance'];
    }

    private function recordAccountTransaction(TransactionModel $transactionModel, int $userId, int $accountId, string $type, string $amount): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT `AccountName`, `Currency` FROM `Account` WHERE `idAccount` = :accountId AND `idUser` = :userId'
        );
        $stmt->execute(['accountId' => $accountId, 'userId' => $userId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) {
            throw new RuntimeException('Le compte utilisé pour cette opération est introuvable.');
        }

        $labels = ['deposit' => 'Dépôt', 'withdrawal' => 'Retrait'];
        $transactionModel->record(
            $userId,
            $type,
            $labels[$type] . ' — ' . $account['AccountName'],
            $amount,
            (string) $account['Currency']
        );
    }
}
