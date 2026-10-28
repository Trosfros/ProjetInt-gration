<?php
require_once dirname(__DIR__, 2) . "/configs/database.php";
require_once dirname(__DIR__, 2) . "/src/Models/TransactionModel.php";

class AccountDAL
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function createAccount(string $Alias, string $accountName, ?string $accountType = 'checking', ?string $currency = 'CAD'): array
    {
        try {
            $userId = $this->GetActiveUserID($Alias);

            $stmt = $this->pdo->prepare("CALL createAccount(?, ?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $accountName, PDO::PARAM_STR);
            $stmt->bindParam(3, $accountType, PDO::PARAM_STR);
            $stmt->bindParam(4, $currency, PDO::PARAM_STR);
            $stmt->execute();

            return ['success' => true, 'message' => "Compte créé avec succès"];

        } catch (PDOException $e) {
            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
    }
    public function AddMoneyToAccount(string $accountName, string $username, float $money): array
    {
        
        if ($money < 0) {
            return ["success" => false, "message" => "Ne peut pas ajouter 0 dollar au compte ou même moins"];
        }
        $idAccount = $this->GetAccountID($accountName, $username);
        $idUser = $this->GetActiveUserID($username);
        $transactionModel = new TransactionModel();
        try {
            $stmt = $this->pdo->prepare("CALL AddMoneyToAccount(?,?,?)");
              $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
            $stmt->bindParam(2, $idAccount, PDO::PARAM_INT);
            $stmt->bindParam(3, $money);
            $stmt->execute();

            $this->recordAccountTransaction($transactionModel, $idUser, $idAccount, 'deposit', $money);
            return ["success" => true, "message" => "Ajout de l'argent au compte avec succès!"];
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '45000') {
                return ['success' => false, 'message' => $e->getMessage()];
            }
            throw $e;
        }
    }
    public function RemoveMoneyFromAccount(string $accountName, string $username, float $money): array
    {
       
        if ($money < 0) {
            return ["success" => false, "message" => "Ne peut pas retirer un montant négatif de dollar au compte"];
        }
        $idAccount = $this->GetAccountID($accountName, $username);
        $idUser = $this->GetActiveUserID($username);
        $transactionModel = new TransactionModel();
        try {
            $stmt = $this->pdo->prepare("CALL RemoveMoneyFromAccount(?,?,?)");
            $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
            $stmt->bindParam(2, $idAccount, PDO::PARAM_INT);
            $stmt->bindParam(3, $money);
            $stmt->execute();

            $this->recordAccountTransaction($transactionModel, $idUser, $idAccount, 'withdrawal', $money);
            return ["success" => true, "message" => "Ajout de l'argent au compte avec succès!"];
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '45000') {
                return ['success' => false, 'message' => $e->getMessage()];
            }
            throw $e;
        }
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
    public function DeleteAccount(int $accountId): array
    {
        try {
            $stmt = $this->pdo->prepare("CALL deleteAccount(?)");
            $stmt->bindParam(1, $accountId, PDO::PARAM_INT);
            $stmt->execute();
            return ['success' => true, 'message' => "Compte suprimmé avec succès... "];
        } catch (PDOException $e) {
            if ($e->errorInfo[0] == '450000') {
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
        $stmt = $this->pdo->prepare("SELECT * FROM Account WHERE idUser = ?");
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

    private function recordAccountTransaction(TransactionModel $transactionModel, int $userId, int $accountId, string $type, float $amount): void
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