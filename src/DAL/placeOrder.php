<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/stockDAL.php";
require_once dirname(__DIR__,2) ."/configs/utils.php";
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();


$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'placeOrder.php bad input. CONTENT_TYPE=' . ($_SERVER['CONTENT_TYPE'] ?? 'none')]);
}

$side    = $input['side']    ?? null;
$idStock = $input['idStock'] ?? null;
$shares  = $input['amount']  ?? null;
$unit    = $input['unit']    ?? 'shares';

if (!in_array($side, ['buy', 'sell'], true) || !$idStock || !$shares || $shares <= 0) {
    respond(422, ['success' => false, 'message' => 'Remplissez au complet le formulaire']);
}

if (empty($_SESSION['user']['username'])) {
    respond(401, ['success' => false, 'message' => 'Vous devez être connecté pour passer un ordre.']);
}

$alias = (string) $_SESSION['user']['username'];

try {
    $dal = new StockDAL();

    if ($side === 'sell') {
        $result = $dal->sellStock($alias, (int) $idStock, (float) $shares);
        respond($result['success'] ? 200 : 400, $result);
    }

    $result = $dal->buyStock($alias, (int) $idStock, (float) $shares);
    respond($result['success'] ? 200 : 400, $result);

} catch (\RuntimeException $e) {
    respond(404, ['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('placeOrder.php failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Something went wrong placing the order']);
}