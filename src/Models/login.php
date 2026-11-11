<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);

// Gestion des messages de session (erreurs et succès de vérification/création)
$error = $_SESSION['login_error'] ?? ($_SESSION['verify_error'] ?? null);
$success = $_SESSION['register_success'] ?? ($_SESSION['verify_success'] ?? ($_SESSION['login_success'] ?? null));

unset($_SESSION['login_error'], $_SESSION['verify_error'], $_SESSION['register_success'], $_SESSION['verify_success'], $_SESSION['login_success']);

$pageTitle = 'Connexion | NovaBank';
$pageStylesheets = ['auth.css'];
require $projectRoot . '/views/layout/header.php';
?>

<div id="loadingOverlay" class="loading-overlay" aria-hidden="true" role="status">
  <div class="loading-box">
    <div class="loading-spinner"></div>
    <h3>Connexion en cours</h3>
    <p>Vérification de vos identifiants et chargement de votre espace bancaire...</p>
  </div>
</div>

<main class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>Connexion</h2>
      <p>Accédez à votre compte NovaBank.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form id="loginForm" action="index.php?action=login" method="POST" class="auth-form">
      <div class="form-group">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" placeholder="nom@exemple.com" required autocomplete="email">
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>

      <div class="label-wrapper">
        <span></span>
        <a class="forgot-link" href="index.php?action=forgotPassword">Mot de passe oublié ?</a>
      </div>

      <button type="submit" id="submitBtn" class="btn-primary btn-block">Se connecter</button>
    </form>

    <div class="auth-footer">
      <p>Pas encore de compte ? <a href="index.php?action=registerPage">Ouvrir un compte</a></p>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
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