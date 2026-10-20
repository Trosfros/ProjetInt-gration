<?php 
function respond(int $httpCode, array $payload): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
