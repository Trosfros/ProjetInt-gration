<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);

$pageTitle = 'Inscription | NovaBank';
$pageStylesheets = ['auth.css'];
require $projectRoot . '/views/layout/header.php';
?>

<!-- Écran de chargement -->
<div id="loadingOverlay" class="loading-overlay" aria-hidden="true" role="status">
  <div class="loading-box">
    <div class="loading-spinner"></div>
    <h3>Création de votre compte</h3>
    <p>Création des accès et envoi de votre courriel de validation en cours...</p>
  </div>
</div>

<main class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>Créer un compte</h2>
      <p>Rejoins NovaBank et commence ta simulation.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="registerForm" action="index.php?action=register" method="POST" class="auth-form">
      <div class="form-group">
        <label for="username">Nom d’utilisateur</label>
        <input type="text" id="username" name="username" placeholder="Votre pseudo" required>
      </div>

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" placeholder="nom@exemple.com" required>
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" id="submitBtn" class="btn-primary btn-block">Créer mon compte</button>
    </form>

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