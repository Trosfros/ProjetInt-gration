<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/AccountDAL.php";
require_once dirname(__DIR__,2) ."/configs/utils.php";
 
$rawBody = file_get_contents("php://input");
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Requête invalide']);
}

if (empty($_SESSION['user']['username'])) {
    respond(401, ['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette opération.']);
}

$accName = trim((string) ($input['accountName'] ?? ''));
$amount = $input['amount'] ?? null;
$mode = $input['mode'] ?? '';

if (
    !in_array($mode, ['add', 'remove'], true)
    || $accName === ''
    || !is_numeric($amount)
    || (float) $amount <= 0
) {
    respond(422, ['success' => false, 'message' => 'Remplissez au complet le formulaire']);
}

$amount = (float) $amount;

try {
    $dal = new AccountDAL();
    $username = (string) $_SESSION['user']['username'];
    
    if ($mode === 'add') {
        $result = $dal->AddMoneyToAccount( $accName, $username,$amount);
    } else {
        $result = $dal->RemoveMoneyFromAccount( $accName, $username,  $amount);
    }

    respond($result['success'] ? 200 : 400, $result);
} catch (\RuntimeException $e) {
    respond(404, ['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('updateAccountMoney.php failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Une erreur est survenue.']);
}