
<?php 

require_once __DIR__ ."/stockDAL.php";

function respond(int $httpCode, array $payload):void{
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if(!is_array($input)){
    respond(400, ['success' => false, 'message' => 'Requête invalide']);
}
$side = $input['side'];
$idStock = $input['idStock'];
$amount = $input['amount'];
$unit = $input['unit'] ?? 'shares';

if (!in_array($side, ['buy', 'sell'], true) || !$idStock || !$amount || $amount <= 0) {
    respond(422, ['success' => false, 'message' => 'Remplissez au complet le formulaire']);
}

$alias = $_SESSION['alias'] ?? respond(422, ['success' => false, 'message' => 'Remplissez au complet le formulaire']);
try 
{
    $dal = new StockDAL();
   
    if ($side ==='sell'){
        //no dal procedure for this yet do nothing
        respond(501, [
            'success' => false,
            'message' => 'Selling isn\'t wired up yet — only BuyStockInvestment exists in the database.',
        ]);
    }
    $shares = $dal->calculateShares($alias, $amount, $unit);
    if ($share <= 0) {
        respond(421, 
        ['sucess' => false,
        'message' => 'ammount is to small to buy any shares at this price']);
    }
    $result = $dal->buyStock($alias, $idStock, $shares);
}catch (\RuntimeException $e) {
    
    respond(404, ['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('place-order.php failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Something went wrong placing the order']);
} 

