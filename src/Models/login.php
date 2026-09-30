<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);

// Gestion des messages de session (erreurs et succès de vérification/création)
$error = $_SESSION['login_error'] ?? ($_SESSION['verify_error'] ?? null);
$success = $_SESSION['register_success'] ?? ($_SESSION['verify_success'] ?? null);

unset($_SESSION['login_error'], $_SESSION['verify_error'], $_SESSION['register_success'], $_SESSION['verify_success']);

$pageTitle = 'Connexion | NovaBank';
require $projectRoot . '/views/layout/header.php';
?>

// Les styles sont généré par l'intelligence artificielle.
<style>
  .loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(10, 15, 29, 0.85);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
  }

  .loading-box {
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    padding: 36px 32px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6);
    color: #ffffff;
    animation: fadeIn 0.25s ease-out;
  }

  .loading-spinner {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    border: 4px solid rgba(56, 189, 248, 0.2);
    border-top-color: #38bdf8;
    border-radius: 50%;
    animation: spin 0.85s linear infinite;
  }

  .loading-box h3 {
    margin: 0 0 8px;
    font-size: 1.25rem;
    color: #38bdf8;
  }

  .loading-box p {
    margin: 0;
    font-size: 0.9rem;
    color: #94a3b8;
    line-height: 1.5;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
  }
</style>

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