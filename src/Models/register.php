<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);

$pageTitle = 'Inscription | NovaBank';
require $projectRoot . '/views/layout/header.php';
?>

<main class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>Créer un compte</h2>
      <p>Rejoins NovaBank et commence ta simulation.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="index.php?action=register" method="POST" class="auth-form">
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

      <button type="submit" class="btn-primary btn-block">Créer mon compte</button>
    </form>

    <div class="auth-footer">
      <p>Déjà membre ? <a href="index.php?action=loginPage">Se connecter</a></p>
    </div>
  </div>
</main>

<?php require $projectRoot . '/views/layout/footer.php';
