<?php

require_once dirname(__DIR__, 2) . "/configs/database.php";
require_once dirname(__DIR__, 2) . "/src/Models/TransactionModel.php";
require_once dirname(__DIR__, 2) . '/src/Models/AuditModel.php';
require_once dirname(__DIR__, 2) . '/src/Models/NotificationModel.php';

class StockDAL
{
    private PDO $pdo;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->pdo = getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->notificationModel = new NotificationModel($this->pdo);
    }
    //returns if function worked
    public function buyStock(string $alias, int $idStock, float $shares): array
    {
        if ($shares <= 0) {
            return ['success' => false, 'message' => 'Share amount must be greater than 0.'];
        }

        $userId = $this->getActiveUserID($alias);
        $stock = $this->getStockDetails($idStock);
        $transactionModel = new TransactionModel();

        try {
            $balanceStmt = $this->pdo->prepare('SELECT `Balance` FROM `User` WHERE `idUser` = :userId');
            $balanceStmt->execute(['userId' => $userId]);
            $previousBalance = $balanceStmt->fetchColumn();
            if ($previousBalance === false) {
                throw new RuntimeException('User not found while fetching balance.');
            }

            $stmt = $this->pdo->prepare("CALL BuyStockInvestment(?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->bindParam(3, $shares);
            $stmt->execute();
            $stmt->closeCursor();

            $transactionModel->record(
                $userId,
                'purchase',
                'Achat de ' . rtrim(rtrim(number_format($shares, 4, '.', ''), '0'), '.') . ' action(s) — ' . $stock['Name'],
                (float) number_format($stock['Price'] * $shares, 2, '.', ''),
                'CAD'
            );
            $balanceStmt->execute(['userId' => $userId]);
            $currentBalance = $balanceStmt->fetchColumn();
            if ($currentBalance === false) {
                throw new RuntimeException('User not found while fetching balance.');
            }
            try {
                $this->notificationModel->notifyLowBalanceCrossing(
                    $userId,
                    number_format((float) ($previousBalance ?? 0), 2, '.', ''),
                    number_format((float) $currentBalance, 2, '.', '')
                );
            } catch (PDOException $e) {
                error_log('stockDAL failed to create low-balance notification: ' . $e->getMessage());
            }
            AuditModel::record('investment.purchase', 'high', 'Ordre d’achat exécuté.', $userId, $userId, [
                'stockId' => $idStock,
                'stockName' => $stock['Name'],
                'action' => 'buy',
                'shares' => number_format($shares, 8, '.', ''),
                'unitPrice' => number_format($stock['Price'], 2, '.', ''),
                'estimatedValue' => number_format($stock['Price'] * $shares, 2, '.', ''),
            ], $this->pdo);
            return ['success' => true, 'message' => "Achat passer avec succès!"];
        } catch (PDOException $e) {

            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
        return ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
    }
    public function sellStock(string $alias, int $idStock, $shares): array
    {

        if ($shares <= 0) {
            return ['success' => false, 'message' => 'Share amount must be greater than 0.'];
        }

        $userId = $this->getActiveUserID($alias);
        $stock = $this->getStockDetails($idStock);
        $transactionModel = new TransactionModel();
        try {
            $stmt = $this->pdo->prepare("CALL SellStockInvestment(?, ?, ?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->bindParam(3, $shares);
            $stmt->execute();
            $stmt->closeCursor();

            $transactionModel->record(
                $userId,
                'sale',
                'Vente de ' . rtrim(rtrim(number_format((float) $shares, 4, '.', ''), '0'), '.') . ' action(s) — ' . $stock['Name'],
                (float) number_format($stock['Price'] * (float) $shares, 2, '.', ''),
                'CAD'
            );
            AuditModel::record('investment.sale', 'high', 'Ordre de vente exécuté.', $userId, $userId, [
                'stockId' => $idStock,
                'stockName' => $stock['Name'],
                'action' => 'sell',
                'shares' => number_format((float) $shares, 8, '.', ''),
                'unitPrice' => number_format($stock['Price'], 2, '.', ''),
                'estimatedValue' => number_format($stock['Price'] * (float) $shares, 2, '.', ''),
            ], $this->pdo);
            return ['success' => true, 'message' => "Vendu stock avec succès!"];
        } catch (PDOException $e) {

            if ($e->errorInfo[0] === '45000') {
                return ['success' => false, 'message' => $e->errorInfo[2]];
            }
            throw $e;
        }
        return ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
    }
    public function getUsersFavorite(string $alias): array
    {
        if ($alias == null) {
            return ['success' => 'false', 'message' => 'username needs to be given to find favorites'];
        }

        $userId = $this->GetActiveUserID($alias);

        try {
            $stmt = $this->pdo->prepare("SELECT s.* FROM StockFavorite sf INNER JOIN Stock s ON s.idStock = sf.idStock WHERE sf.IdUser = ?");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->execute();

            $res = $stmt->fetchAll();
            $stmt->closeCursor();

            return $res;

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'impossible de traiter la demande'];
        }

    }
    public function ToggleFavorite(string $alias, int $idStock): array
    {
        if ($alias == null || $idStock == null) {
            return ['success' => 'false', 'message' => 'cannot give null information to this func pls'];
        }
        $userId = $this->GetActiveUserID($alias);

        try {
            $stmt = $this->pdo->prepare("CALL TOGGLE_STOCK_FAVORITE(?,?)");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $idStock, PDO::PARAM_INT);
            $stmt->execute();

            $stmt->closeCursor();

            $isFavorite = $this->isFavorite($userId, $idStock);
            AuditModel::record('investment.favorite.changed', 'low', 'Action favorite mise à jour.', $userId, $userId, [
                'stockId' => $idStock,
                'isFavorite' => $isFavorite,
            ], $this->pdo);

            return [
                'success' => true,
                'isFavorite' => $isFavorite,
                'message' => $isFavorite ? 'Ajouté à vos favoris!' : 'Retiré de vos favoris.',
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Impossible de traiter la demande'];
        }

    }
    public function isFavorite(int $userId, int $idStock): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM StockFavorite WHERE IdUser = ? AND idStock = ? LIMIT 1");
        $stmt->execute([$userId, $idStock]);
        $found = $stmt->fetchColumn() !== false;
        $stmt->closeCursor();
        return $found;
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


    private function getStockDetails(int $idStock): array
    {
        $stmt = $this->pdo->prepare("SELECT `Name`, `LastPrice` FROM `Stock` WHERE `idStock` = ?");
        $stmt->execute([$idStock]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$result || $result['LastPrice'] === null) {
            throw new RuntimeException("No valid stock found for idStock: $idStock");
        }

        return ['Name' => (string) $result['Name'], 'Price' => (float) $result['LastPrice']];
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
    public function AddStock(string $name, string $price, string $change): void
    {
        $stmt = $this->pdo->prepare('call addStock(?,?,?)');
        $stmt->bindParam(1, $name, PDO::PARAM_STR);
        $stmt->bindParam(2, $price);
        $stmt->bindParam(3, $change);
        $stmt->execute();
        $stmt->closeCursor();
    }
    public function getUsersFavoriteIds(string $alias): array
    {
        $userId = $this->GetActiveUserID($alias);
        $stmt = $this->pdo->prepare("SELECT idStock FROM StockFavorite WHERE IdUser = ?");
        $stmt->execute([$userId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $stmt->closeCursor();
        return $ids;
    }
    // Returns [symbol => idStock] for the given symbols (Stock.Name holds the symbol)
    public function getStockIdsBySymbols(array $symbols): array
    {
        $symbols = array_values(array_unique(array_filter($symbols)));
        if (empty($symbols)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($symbols), '?'));
        $stmt = $this->pdo->prepare("SELECT idStock, `Name` FROM `Stock` WHERE `Name` IN ($placeholders)");
        $stmt->execute($symbols);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['Name']] = (int) $row['idStock'];
        }
        $stmt->closeCursor();
        return $map;
    }
    public function AddArrayStock(array $stock): void
    {
        foreach ($stock as $s) {
            $stmt = $this->pdo->prepare('call addStock(?,?,?)');
            $stmt->bindParam(1, $s["symbol"], PDO::PARAM_STR);
            $stmt->bindParam(2, $s["price"]);
            $stmt->bindParam(3, $s["change"]);
            $stmt->execute();
            $stmt->closeCursor();
        }
    }

}
