
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
    $shares = $dal->shares($alias, $amount, $unit);
}

