<?php 

require_once __DIR__ . "/configs/database.php";
require_once __DIR__ ."/vendor/autoload.php";

class StockDAL
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
         $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    //returns if function worked
    public function buyStock(int $Alias,int $idstock,float $amount) : array 
    {       
             $userId = $this->GetActiveUserID($Alias);
              try {
            $stmt = $this->pdo->prepare("CALL BuyStockInvestment(?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->bindParam(3, $amount);
            $stmt->execute();
            $stmt->closeCursor();

            return ['success' => true, 'message' => 'Purchase successful'];
        } catch (PDOException $e) {
            
            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }

    }
    public function GetActiveUserID(string $username) : int
    {
        $stmt = $this->pdo->prepare("SELECT idUser from user where ? = Alias");
        $stmt->bindParam( 1,$username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$result)
            {
                throw new RuntimeException("no user found under the username ". $username );
            }
        return (int) $result["idUser"];
    }

}