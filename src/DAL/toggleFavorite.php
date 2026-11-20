<?php
// src/DAL/toggleFavorite.php — ajoute / retire une action des favoris de l'utilisateur connecté
if (session_status() === PHP_SESSION_NONE) {
    require_once dirname(__DIR__, 2) . '/configs/security.php';
    startSecureSession();
}
require_once dirname(__DIR__, 2) . '/configs/security.php';
sendSecurityHeaders();

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__,2) ."/configs/utils.php";
require_once __DIR__ . '/StockDAL.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond( 405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}


$alias = (string) $_SESSION['user']['username'];

if (empty($alias)) {
    respond(401,['success' => false, 'message' => 'Vous devez être connecté.'], );
}
if (!validCsrfToken(requestCsrfToken())) {
    respond(403, ['success' => false, 'message' => 'Session expirée. Rechargez la page.']);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$idStock = isset($input['idStock']) ? (int) $input['idStock'] : 0;

if ($idStock <= 0) {
    respond( 400,['success' => false, 'message' => 'Identifiant d\'action invalide.'],);
}

try {
    $dal = new StockDAL();
    respond(200,$dal->ToggleFavorite((string) $alias, $idStock));
} catch (Throwable $e) {
    respond(500,['success' => false, 'message' => 'Impossible de traiter la demande.'],);
}
