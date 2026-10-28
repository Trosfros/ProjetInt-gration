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

$accountName = trim((string) ($input['accountName'] ?? ''));
$accountType = $input['accountType'] ?? 'checking';
$currency    = $input['currency'] ?? 'CAD';

if ($accountName === '') {
    respond(422, ['success' => false, 'message' => 'Le nom du compte est requis.']);
}



$username = (string) $_SESSION['user']['username'];

try {
    $dal = new AccountDAL();
    $result = $dal->createAccount($username, $accountName, $accountType, $currency);
    respond($result['success'] ? 200 : 400, $result);

} catch (\RuntimeException $e) {
    respond(404, ['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('newAccount.php failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Une erreur est survenue lors de la création du compte.']);
}