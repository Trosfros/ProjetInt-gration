<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NovaBank | Banque virtuelle & Simulateur de Bourse</title>
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
    <div class="logo">
      <span class="logo-symbol">
        <img src="public/images/logo.png" alt="NovaBank Logo" onerror="this.style.display='none'">
      </span>
      NovaBank
    </div>

    <nav class="nav-links">
      <a href="#simulator">Simulateur Bourse</a>
      <a href="#banking">Gestion Bancaire</a>
      <a href="#leaderboard">Classement</a>
      <a href="#features">Fonctionnalités</a>
    </nav>

    <div class="nav-actions">
      <a href="#" class="btn-ghost">Se connecter</a>
      <a href="#" class="btn-primary">Rejoindre la simulation</a>
    </div>
  </header>

  <!-- Bandeau cours de bourse défilant -->
  <div class="market-ticker">
    <div class="ticker-track">
      <div class="ticker-item"><span class="sym">NVDA</span> <span class="val">$128.45</span> <span class="up">+5.42%</span></div>
      <div class="ticker-item"><span class="sym">AAPL</span> <span class="val">$224.10</span> <span class="up">+1.15%</span></div>
      <div class="ticker-item"><span class="sym">TSLA</span> <span class="val">$248.80</span> <span class="down">-2.30%</span></div>
      <div class="ticker-item"><span class="sym">BTC/USD</span> <span class="val">$64,320</span> <span class="up">+3.80%</span></div>
      <div class="ticker-item"><span class="sym">SPY</span> <span class="val">$562.14</span> <span class="up">+0.65%</span></div>
      <div class="ticker-item"><span class="sym">MSFT</span> <span class="val">$432.20</span> <span class="down">-0.45%</span></div>
      <!-- Répétition pour la fluidité de l'animation -->
      <div class="ticker-item"><span class="sym">NVDA</span> <span class="val">$128.45</span> <span class="up">+5.42%</span></div>
      <div class="ticker-item"><span class="sym">AAPL</span> <span class="val">$224.10</span> <span class="up">+1.15%</span></div>
      <div class="ticker-item"><span class="sym">TSLA</span> <span class="val">$248.80</span> <span class="down">-2.30%</span></div>
      <div class="ticker-item"><span class="sym">BTC/USD</span> <span class="val">$64,320</span> <span class="up">+3.80%</span></div>
    </div>
  </div>

<footer>
    <div class="footer-inner">
      <p>© 2026 NovaBank. Simulateur éducatif d'investissement boursier et bancaire. Ne constitue pas un conseil financier.</p>
    </div>
  </footer>

</body>
</html>