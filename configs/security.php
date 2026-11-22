<?php

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, private');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validCsrfToken(?string $token): bool
{
    $valid = is_string($token) && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
    if (!$valid) {
        try {
            require_once dirname(__DIR__) . '/src/Models/AuditModel.php';
            AuditModel::record(
                'security.csrf.failed',
                'high',
                'Jeton CSRF invalide ou expiré.',
                isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
                isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
                [
                    'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
                    'endpoint' => (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''),
                ]
            );
            $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
            if ($ipAddress !== null
                && AuditModel::countRecentEvent('security.csrf.failed', 10, $ipAddress) >= 10
                && AuditModel::countRecentEvent('security.csrf.brute_force', 10, $ipAddress) === 0
            ) {
                AuditModel::record(
                    'security.csrf.brute_force',
                    'critical',
                    'Tentatives répétées avec jetons CSRF invalides détectées.',
                    null,
                    null,
                    ['failedAttemptsWindow' => 10, 'windowMinutes' => 10]
                );
            }
        } catch (Throwable $e) {
            error_log('Unable to record failed CSRF validation: ' . $e->getMessage());
        }
    }
    return $valid;
}

function requestCsrfToken(): ?string
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (is_string($token)) {
        return $token;
    }

    $token = $_POST['csrf_token'] ?? null;
    return is_string($token) ? $token : null;
}
