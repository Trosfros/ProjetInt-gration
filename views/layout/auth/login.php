<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion | NovaBank</title>
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Halos d'ambiance -->
  <div class="glow-bg top-left"></div>
  <div class="glow-bg top-right"></div>
  <div class="glow-bg bottom-center"></div>

  <!-- Barre de navigation -->
  <header class="navbar">
    <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
      <span class="logo-symbol">
        <img src="public/images/logo.png" alt="NovaBank Logo" onerror="this.style.display='none'">
      </span>
      NovaBank
    </a>

    <div class="nav-actions">
      <span class="auth-switch-text">Pas encore de compte ?</span>
      <a href="register.php" class="btn-primary">Rejoindre la simulation</a>
    </div>
  </header>

  <!-- Bandeau cours de bourse -->
  <div class="market-ticker">
    <div class="ticker-track">
      <div class="ticker-item"><span class="sym">NVDA</span> <span class="val">$128.45</span> <span class="up">+5.42%</span></div>
      <div class="ticker-item"><span class="sym">AAPL</span> <span class="val">$224.10</span> <span class="up">+1.15%</span></div>
      <div class="ticker-item"><span class="sym">TSLA</span> <span class="val">$248.80</span> <span class="down">-2.30%</span></div>
      <div class="ticker-item"><span class="sym">BTC/USD</span> <span class="val">$64,320</span> <span class="up">+3.80%</span></div>
      <div class="ticker-item"><span class="sym">SPY</span> <span class="val">$562.14</span> <span class="up">+0.65%</span></div>
      <div class="ticker-item"><span class="sym">MSFT</span> <span class="val">$432.20</span> <span class="down">-0.45%</span></div>
    </div>
  </div>

  <!-- Conteneur Formulaire de Connexion -->
  <main class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <h2>Espace Membre</h2>
        <p>Connectez-vous pour accéder à votre portefeuille et vos comptes.</p>
      </div>

      <!-- Affichage des erreurs éventuelles -->
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($error) ?>
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

  <footer>
    <div class="footer-inner">
      <p>© 2026 NovaBank. Simulateur éducatif d'investissement boursier et bancaire. Ne constitue pas un conseil financier.</p>
    </div>
  </footer>

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
</body>
</html>