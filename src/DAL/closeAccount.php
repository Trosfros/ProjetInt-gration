<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/AccountDAL.php";
require_once dirname(__DIR__, 2) . "/configs/utils.php";

$rawBody = file_get_contents("php://input");
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Requête invalide']);
}

$idAccount = $input['idAccount'] ?? null;

if (!$idAccount) {
    respond(422, ['success' => false, 'message' => 'Compte invalide.']);
}

if (empty($_SESSION['user']['username'])) {
    respond(401, ['success' => false, 'message' => 'Vous devez être connecté.']);
}

$username = (string) $_SESSION['user']['username'];

try {
    $dal = new AccountDAL();


    $ownedAccounts = $dal->GetUserAccounts($username);
    $ownsAccount = false;
    foreach ($ownedAccounts as $acc) {
        if ((int) $acc['idAccount'] === (int) $idAccount) {
            $ownsAccount = true;
            break;
        }
    }
    if (!$ownsAccount) {
        respond(403, ['success' => false, 'message' => 'Ce compte ne vous appartient pas.']);
    }

    $result = $dal->DeleteAccount((int) $idAccount, $username);
    respond($result['success'] ? 200 : 400, $result);
} catch (\RuntimeException $e) {
    respond(404, ['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('closeAccount.php failed: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Une erreur est survenue lors de la fermeture du compte.']);
}