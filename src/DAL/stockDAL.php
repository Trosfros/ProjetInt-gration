<?php

require_once dirname(__DIR__,2) . "/configs/database.php";
require_once dirname(__DIR__,2) . "/vendor/autoload.php";

class StockDAL
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    //returns if function worked
    public function buyStock(string $alias, int $idStock, float $shares): array
    {
        if ($shares <= 0) {
            return ['success' => false, 'message' => 'Share amount must be greater than 0.'];
        }

        $userId = $this->getActiveUserID($alias);
       
        try {
            $stmt = $this->pdo->prepare("CALL BuyStockInvestment(?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->bindParam(3, $shares);
            $stmt->execute();
            $stmt->closeCursor();

            return ['success' => true, 'message' => "Achat passer avec succès!"];
        } catch (PDOException $e) {
           
            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
        return ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
    }
    public function sellStock(string $alias, int $idStock,$shares): array
    {
        
        if ($shares <= 0) {
            return ['success' => false, 'message' => 'Share amount must be greater than 0.'];
        }

        $userId = $this->getActiveUserID($alias);
    
        try {
            $stmt = $this->pdo->prepare("CALL SellStockInvestment(?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->bindParam(3, $shares);
            $stmt->execute();
            $stmt->closeCursor();

            return ['success' => true, 'message' => "Vendu stock avec succès!"];
        } catch (PDOException $e) {
           
            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
        return ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
    }
    private function getStockPrice(int $idStock): float
    {
        $stmt = $this->pdo->prepare("SELECT LastPrice FROM `Stock` WHERE idStock = ?");
        $stmt->bindParam(1, $idStock, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$result) {
            throw new \RuntimeException("No stock found for idStock: $idStock");
        }

        return (float) $result['LastPrice'];
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
    public function calculateShares(int $idStock, float $amount, string $unit = 'shares'): float
    {
        if ($unit !== 'dollars') {
            return round($amount, 4);
        }

        $price = $this->getStockPrice($idStock);


        return round($amount / $price, 4);
    }
    public function AddStock(string $name, string $price, string $change):void
    {   
        $stmt = $this->pdo->prepare('call addStock(?,?,?)');
        $stmt->bindParam(1, $name, PDO::PARAM_STR);
        $stmt->bindParam(2, $price);
        $stmt->bindParam(3, $change);
        $stmt->execute();
        $stmt->closeCursor();
    }

}