<?php
require_once dirname(__DIR__, 2) . '/configs/security.php';
if (session_status() === PHP_SESSION_NONE) {
    startSecureSession();
}

$projectRoot = dirname(__DIR__, 2);

$pendingRegistration = $_SESSION['registration_verification'] ?? null;
if (!is_array($pendingRegistration)) {
    $pendingRegistration = null;
}
if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['register_csrf_token'];
$error = $_SESSION['register_error'] ?? null;
$success = $_SESSION['register_success'] ?? null;
unset($_SESSION['register_error'], $_SESSION['register_success']);

$pageTitle = 'Inscription | NovaBank';
$pageStylesheets = ['auth.css'];
require $projectRoot . '/views/layout/header.php';
?>

<!-- Écran de chargement -->
<div id="loadingOverlay" class="loading-overlay" aria-hidden="true" role="status">
  <div class="loading-box">
    <div class="loading-spinner"></div>
    <h3>Création de votre compte</h3>
    <p>Création des accès et envoi de votre code de validation en cours...</p>
  </div>
</div>

<main class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <?php if ($pendingRegistration !== null): ?>
        <h2>Vérifier votre adresse courriel</h2>
        <?php if ($pendingRegistration['code_sent'] ?? true): ?>
          <p>Entrez le code à six chiffres envoyé à <?= htmlspecialchars((string) $pendingRegistration['email'], ENT_QUOTES, 'UTF-8') ?> pour terminer la création de votre compte.</p>
        <?php else: ?>
          <p>Un compte non vérifié existe déjà pour cette adresse. Renvoyez un code pour terminer la création du compte.</p>
        <?php endif; ?>
      <?php else: ?>
        <h2>Créer un compte</h2>
        <p>Rejoins NovaBank et commence ta simulation.</p>
      <?php endif; ?>
    </div>

    <?php if ($error !== null): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($success !== null): ?>
      <div class="alert alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($pendingRegistration !== null): ?>
      <?php if ($pendingRegistration['code_sent'] ?? true): ?>
        <form action="index.php?action=register" method="POST" class="auth-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="verify_code">
          <div class="form-group">
            <label for="verification-code">Code à six chiffres</label>
            <input id="verification-code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
          </div>
          <button type="submit" class="btn-primary btn-block">Confirmer le code</button>
        </form>
      <?php endif; ?>

      <form action="index.php?action=register" method="POST" class="auth-form" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="form_action" value="resend_code">
        <button type="submit" class="btn-secondary btn-block">Renvoyer le code</button>
      </form>
    <?php else: ?>
      <form id="registerForm" action="index.php?action=register" method="POST" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="form_action" value="create_account">
        <div class="form-group">
          <label for="username">Nom d’utilisateur</label>
          <input type="text" id="username" name="username" placeholder="Votre pseudo" required>
        </div>

        <div class="form-group">
          <label for="email">Adresse courriel</label>
          <input type="email" id="email" name="email" placeholder="nom@exemple.com" required>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" id="submitBtn" class="btn-primary btn-block">Créer mon compte</button>
      </form>
    <?php endif; ?>

    <div class="auth-footer">
      <p>Déjà membre ? <a href="index.php?action=loginPage">Se connecter</a></p>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const overlay = document.getElementById('loadingOverlay');
    const submitBtn = document.getElementById('submitBtn');

    if (form && overlay) {
      form.addEventListener('submit', function (e) {
        if (!this.checkValidity()) {
          return;
        }

        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.style.opacity = '0.6';
          submitBtn.style.cursor = 'not-allowed';
        }
      });
    }
  });
</script>

<?php require $projectRoot . '/views/layout/footer.php'; ?>