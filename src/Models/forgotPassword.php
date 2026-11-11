<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);

if (empty($_SESSION['password_reset_csrf_token'])) {
    $_SESSION['password_reset_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['password_reset_csrf_token'];
$error = $_SESSION['password_reset_error'] ?? null;
$success = $_SESSION['password_reset_success'] ?? null;
unset($_SESSION['password_reset_error'], $_SESSION['password_reset_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $formAction = (string) ($_POST['form_action'] ?? '');
    $resetState = $_SESSION['password_reset'] ?? null;

    if (!hash_equals($csrfToken, $submittedToken)) {
        $_SESSION['password_reset_error'] = 'Votre session a expiré. Rechargez la page et réessayez.';
    } elseif ($formAction === 'request_code') {
        $lastSentAt = (int) ($_SESSION['password_reset_last_sent_at'] ?? 0);
        $email = trim((string) ($_POST['email'] ?? ''));
        $_SESSION['password_reset_email'] = $email;

        if ($lastSentAt > 0 && $lastSentAt + 60 > time()) {
            $_SESSION['password_reset_error'] = 'Veuillez patienter une minute avant de demander un autre code.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['password_reset_error'] = 'Entrez une adresse courriel valide.';
        } else {
            $_SESSION['password_reset_last_sent_at'] = time();
            unset($_SESSION['password_reset']);

            try {
                $pdo = getPDO();
                $stmt = $pdo->prepare('SELECT `idUser`, `Alias` FROM `User` WHERE `Description` = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $code = (string) random_int(100000, 999999);
                    sendPasswordChangeCode($email, (string) $user['Alias'], $code);
                    $_SESSION['password_reset'] = [
                        'user_id' => (int) $user['idUser'],
                        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                        'expires_at' => time() + 600,
                        'attempts' => 0,
                    ];
                }

                $_SESSION['password_reset_success'] = 'Si cette adresse est associée à un compte, un code de confirmation a été envoyé. Il est valable 10 minutes.';
            } catch (Throwable $e) {
                error_log('index.php failed to process a password reset request: ' . $e->getMessage());
                $_SESSION['password_reset_error'] = 'Impossible de traiter la demande pour le moment. Réessayez plus tard.';
            }
        }
    } elseif ($formAction === 'verify_code') {
        $submittedCode = trim((string) ($_POST['verification_code'] ?? ''));

        if (!is_array($resetState) || ($resetState['expires_at'] ?? 0) <= time()) {
            unset($_SESSION['password_reset']);
            $_SESSION['password_reset_error'] = 'Code invalide ou expiré. Demandez un nouveau code.';
        } elseif (!preg_match('/^\d{6}$/', $submittedCode)
            || !password_verify($submittedCode, (string) ($resetState['code_hash'] ?? ''))
        ) {
            $resetState['attempts'] = (int) ($resetState['attempts'] ?? 0) + 1;
            if ($resetState['attempts'] >= 5) {
                unset($_SESSION['password_reset']);
                $_SESSION['password_reset_error'] = 'Trop de tentatives. Demandez un nouveau code.';
            } else {
                $_SESSION['password_reset'] = $resetState;
                $_SESSION['password_reset_error'] = 'Code invalide ou expiré. Vérifiez le code reçu et réessayez.';
            }
        } else {
            $resetState['verified_at'] = time();
            $_SESSION['password_reset'] = $resetState;
            $_SESSION['password_reset_success'] = 'Identité confirmée. Vous pouvez choisir un nouveau mot de passe.';
        }
    } elseif ($formAction === 'reset_password') {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!is_array($resetState)
            || !isset($resetState['verified_at'])
            || ($resetState['verified_at'] + 300) <= time()
            || ($resetState['expires_at'] ?? 0) <= time()
        ) {
            unset($_SESSION['password_reset']);
            $_SESSION['password_reset_error'] = 'La confirmation a expiré. Demandez un nouveau code.';
        } elseif (strlen($newPassword) < 8) {
            $_SESSION['password_reset_error'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif (strlen($newPassword) > 72) {
            $_SESSION['password_reset_error'] = 'Le mot de passe ne doit pas dépasser 72 octets.';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['password_reset_error'] = 'Les deux mots de passe ne correspondent pas.';
        } else {
            try {
                $update = getPDO()->prepare('UPDATE `User` SET `Password` = :password WHERE `idUser` = :userId');
                $update->execute([
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'userId' => (int) $resetState['user_id'],
                ]);

                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('No account was updated by the password reset.');
                }

                unset(
                    $_SESSION['password_reset'],
                    $_SESSION['password_reset_email'],
                    $_SESSION['password_reset_last_sent_at']
                );
                session_regenerate_id(true);
                $_SESSION['login_success'] = 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.';
                header('Location: index.php?action=loginPage');
                exit;
            } catch (Throwable $e) {
                error_log('index.php failed to reset a password: ' . $e->getMessage());
                $_SESSION['password_reset_error'] = 'Impossible d’enregistrer le nouveau mot de passe. Réessayez.';
            }
        }
    } else {
        $_SESSION['password_reset_error'] = 'Action invalide. Rechargez la page et réessayez.';
    }

    header('Location: index.php?action=forgotPassword');
    exit;
}

$resetState = $_SESSION['password_reset'] ?? null;
if (is_array($resetState) && ($resetState['expires_at'] ?? 0) <= time()) {
    unset($_SESSION['password_reset']);
    $resetState = null;
}
$resetVerified = is_array($resetState)
    && isset($resetState['verified_at'])
    && $resetState['verified_at'] + 300 > time()
    && ($resetState['expires_at'] ?? 0) > time();
$emailValue = (string) ($_SESSION['password_reset_email'] ?? '');

$pageTitle = 'Mot de passe oublié | NovaBank';
$pageStylesheets = ['auth.css'];
require $projectRoot . '/views/layout/header.php';
?>

<main class="auth-container">
  <section class="auth-card" aria-labelledby="forgot-password-title">
    <div class="auth-header">
      <h2 id="forgot-password-title">Mot de passe oublié ?</h2>
      <p>Recevez un code par courriel pour confirmer votre identité et définir un nouveau mot de passe.</p>
    </div>

    <?php if ($error !== null): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($success !== null): ?>
      <div class="alert alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="index.php?action=forgotPassword" method="POST" class="auth-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="form_action" value="request_code">
      <div class="form-group">
        <label for="reset-email">Adresse courriel</label>
        <input id="reset-email" name="email" type="email" value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="nom@exemple.com" required autocomplete="email">
      </div>
      <button class="btn-primary btn-block" type="submit">Envoyer un code</button>
    </form>

    <?php if (!$resetVerified): ?>
      <form action="index.php?action=forgotPassword" method="POST" class="auth-form" style="margin-top: 1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="form_action" value="verify_code">
        <div class="form-group">
          <label for="verification-code">Code à six chiffres</label>
          <input id="verification-code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
        </div>
        <button class="btn-secondary btn-block" type="submit">Confirmer le code</button>
      </form>
    <?php else: ?>
      <form action="index.php?action=forgotPassword" method="POST" class="auth-form" style="margin-top: 1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="form_action" value="reset_password">
        <div class="form-group">
          <label for="new-password">Nouveau mot de passe</label>
          <input id="new-password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <div class="form-group">
          <label for="confirm-password">Confirmer le nouveau mot de passe</label>
          <input id="confirm-password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <button class="btn-primary btn-block" type="submit">Enregistrer le nouveau mot de passe</button>
      </form>
    <?php endif; ?>

    <div class="auth-footer">
      <p><a href="index.php?action=loginPage">Retour à la connexion</a></p>
    </div>
  </section>
</main>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
