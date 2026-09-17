<main class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <h2>Espace Membre</h2>
      <p>Connectez-vous pour accéder à votre portefeuille et vos comptes.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($verifySuccess)): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($verifySuccess) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($verifyError)): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($verifyError) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($verificationLink)): ?>
      <div class="alert alert-warning">
        <p>Pour valider votre compte, cliquez sur ce lien :</p>
        <a href="<?= htmlspecialchars($verificationLink) ?>" target="_blank" rel="noopener noreferrer">
          <?= htmlspecialchars($verificationLink) ?>
        </a>
      </div>
    <?php endif; ?>

    <form action="index.php?action=login" method="POST" class="auth-form">
      <div class="form-group">
        <label for="email">Adresse courriel</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="nom@exemple.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required
          autocomplete="email"
        >
      </div>

      <div class="form-group">
        <div class="label-wrapper">
          <label for="password">Mot de passe</label>
          <a href="forgot-password.php" class="forgot-link">Oublié ?</a>
        </div>
        <div class="password-input-wrapper">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          >
          <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility()">
            👁️
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary btn-block">Se connecter</button>
    </form>

    <div class="auth-footer">
      <p>En vous connectant, vous acceptez les conditions de la simulation NovaBank.</p>
    </div>
  </div>
</main>

<script>
  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
    } else {
      passwordInput.type = 'password';
    }
  }
</script>