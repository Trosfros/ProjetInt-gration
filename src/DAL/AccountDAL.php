<?php
require_once dirname(__DIR__,2) . "/configs/database.php";
require_once dirname(__DIR__,2) . "/vendor/autoload.php";

class AccountDAL
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function createAccount(string $Alias): array
    {
        try {
            $userId = $this->GetActiveUserID($Alias);
            $stmt = $this->pdo->prepare("CALL createAccount(?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'message' => "Compte créer avec succès"];

        } catch (PDOException $e) {
            return ["success"=> false,"message"=> $e->getMessage()];

        }
    }
    public function AddMoneyToAccount(int $idAccount, float $money): array
    {
        if ($money < 0 )
        {
            return ["success" => false,"message"=> "Ne peut pas ajouter 0 dollar au compte ou même moins"];
        }

        try {
            $stmt = $this->pdo->prepare("CALL AddMoneyToAccount(?,?)");
            $stmt->bindParam(1, $idAccount, PDO::PARAM_INT);
            $stmt->bindParam(2, $money);
            $stmt->execute();

            return ["success"=> true,"message"=> "Ajout de l'argent au compte avec succès!"];
        }catch (PDOException $e ) {
            if ($e->errorInfo[0] == '45000')
            {
                return ['success'=> false,'message'=> $e->getMessage()];
            }            
            throw $e;
        }
    }
  public function RemoveMoneyToAccount(int $idAccount, float $money): array
    {
        if ($money < 0 )
        {
            return ["success" => false,"message"=> "Ne peut pas retirer un montant négatif de dollar au compte"];
        }

        try {
            $stmt = $this->pdo->prepare("CALL RemoveMoneyFromAccount(?,?)");
            $stmt->bindParam(1, $idAccount, PDO::PARAM_INT);
            $stmt->bindParam(2, $money);
            $stmt->execute();

            return ["success"=> true,"message"=> "Ajout de l'argent au compte avec succès!"];
        }catch (PDOException $e ) {
            if ($e->errorInfo[0] == '45000')
            {
                return ['success'=> false,'message'=> $e->getMessage()];
            }            
            throw $e;
        }
    }
    public function GetUserAccounts(int $Alias): array
    {
        $idUser = $this->GetActiveUserID($Alias);

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Account WHERE ? = idUser");
            $stmt->bindParam(1, $idUser, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $e) {
            return ['success'=> false,'message'=> $e->getMessage()];
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
        $stmt = $this->pdo->prepare("SELECT idUser from user where ? = Alias");
        $stmt->bindParam(1, $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$result) {
            throw new RuntimeException("no user found under the username " . $username);
        }
        return (int) $result["idUser"];
    }

    public function AddAccount(string $name, string $price, string $change): void
    {
        $stmt = $this->pdo->prepare('call addStock(?,?,?)');
        $stmt->bindParam(1, $name, PDO::PARAM_STR);
        $stmt->bindParam(2, $price);
        $stmt->bindParam(3, $change);
        $stmt->execute();
        $stmt->closeCursor();
    }
}