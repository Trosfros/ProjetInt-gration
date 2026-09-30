<?php

require_once __DIR__ . "/configs/database.php";
require_once __DIR__ . "/vendor/autoload.php";

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

            return ['success' => true, 'message' => "Bought {$shares} share(s) of stock #{$idStock}."];
        } catch (PDOException $e) {
           
            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
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
    public function calculateShares(int $idStock, float $amount, string $unit = 'shares'): float
    {
        if ($unit !== 'dollars') {
            return round($amount, 4);
        }

        $price = $this->getStockPrice($idStock);


        return round($amount / $price, 4);
    }

}