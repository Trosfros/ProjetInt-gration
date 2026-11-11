<?php
if (session_status() === PHP_SESSION_NONE) {
   
}

$pageTitle = $pageTitle ?? 'NovaBank | Banque virtuelle & Simulateur de Bourse';
$isLoggedIn = !empty($_SESSION['user']);

// Récupération du thème depuis le cookie (par défaut 'dark')
$currentTheme = $_COOKIE['novabank_theme'] ?? 'dark';
$isLightMode = ($currentTheme === 'light');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/src/DAL/stockDAL.php';
require_once $rootPath . '/configs/database.php';

use Dotenv\Dotenv;
use Composer\CaBundle\CaBundle;

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$apiKey = $_ENV['FinnhubKey'] ?? $_ENV['API_KEY'] ?? '';

// --- SYSTÈME DE CACHE FINNHUB ---
$marketData = [];
if (!isset($symbols) || !is_array($symbols) || empty($symbols)) {
    $symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA'];
}

$cacheFile = $rootPath . '/storage/market_cache.json';
$cacheLockFile = $rootPath . '/storage/market_cache.lock';
$cacheLifetime = 60;

try {
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
        $cachedContent = file_get_contents($cacheFile);
        $marketData = json_decode($cachedContent, true) ?? [];
    } else {
        $cacheDirectory = dirname($cacheFile);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }
        if (is_file($cacheFile)) {
            $marketData = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
    }
} catch (Exception $e) {
    $marketData = [];
}
?>
<!DOCTYPE html>
<html lang="fr" class="<?= $isLightMode ? 'light-mode' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet" href="public/css/main.css">
  <?php
    $availablePageStylesheets = ['home.css', 'dashboard.css', 'auth.css', 'errors.css', 'market.css', 'education.css', 'history.css', 'transfer.css', 'admin.css', 'profile.css', 'about.css'];
    foreach (($pageStylesheets ?? []) as $pageStylesheet):
      if (in_array($pageStylesheet, $availablePageStylesheets, true)):
  ?>
    <link rel="stylesheet" href="public/css/<?= htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8') ?>">
  <?php
      endif;
    endforeach;
  ?>
  <link rel="stylesheet" href="public/css/investment.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

  <style>
    /* --- SWITCH MODE SOMBRE / CLAIR --- */
    .theme-switch-container {
      display: flex;
      align-items: center;
      margin-left: auto;
      margin-right: 12px;
    }

    .theme-switch {
      position: relative;
      display: inline-block;
      width: 52px;
      height: 28px;
      cursor: pointer;
    }

    .theme-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .theme-slider {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(30, 41, 59, 0.8);
      border: 1px solid rgba(148, 163, 184, 0.3);
      transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 34px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 6px;
    }

    .theme-slider .icon-sun {
      color: #fbbf24;
      width: 14px;
      height: 14px;
      opacity: 0.5;
      transition: opacity 0.2s;
    }

    .theme-slider .icon-moon {
      color: #38bdf8;
      width: 14px;
      height: 14px;
      opacity: 1;
      transition: opacity 0.2s;
    }

    .theme-slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 3px;
      bottom: 3px;
      background-color: #f8fafc;
      transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 50%;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    input:checked + .theme-slider {
      background-color: #e2e8f0;
      border-color: #cbd5e1;
    }

    input:checked + .theme-slider:before {
      transform: translateX(24px);
      background-color: #0f172a;
    }

    input:checked + .theme-slider .icon-sun {
      opacity: 1;
    }

    input:checked + .theme-slider .icon-moon {
      opacity: 0.5;
    }

    /* --- STYLES LIGHT MODE (VARIABLES & OVERRIDES) --- */
    html.light-mode {
      background-color: #f1f5f9;
      color: #0f172a;
    }

    html.light-mode body {
      background-color: #f1f5f9;
      color: #1e293b;
    }

    html.light-mode .glow-bg {
      opacity: 0.15;
    }

    html.light-mode .navbar {
      background: rgba(255, 255, 255, 0.9) !important;
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    html.light-mode .logo {
      color: #0f172a !important;
    }

    html.light-mode .nav-links a {
      color: #475569;
    }

    html.light-mode .nav-links a:hover {
      color: #0284c7;
    }

    html.light-mode .nav-search-btn {
      color: #64748b;
    }

    html.light-mode .nav-search-btn:hover {
      color: #0284c7;
      background: rgba(2, 132, 199, 0.1);
    }

    html.light-mode .user-greeting {
      color: #64748b !important;
    }

    html.light-mode .btn-ghost {
      color: #475569;
      border-color: #cbd5e1;
    }

    html.light-mode .btn-ghost:hover {
      background: #e2e8f0;
      color: #0f172a;
    }

    html.light-mode .market-ticker {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
    }

    html.light-mode .ticker-item {
      color: #334155;
    }

    html.light-mode .desjardins-search-panel {
      background: rgba(248, 250, 252, 0.98);
      border-bottom: 1px solid rgba(203, 213, 225, 0.8);
    }

    html.light-mode .search-field-box {
      background: #ffffff;
      border-color: #cbd5e1;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    html.light-mode .search-field-box input {
      color: #0f172a;
    }

    html.light-mode .popular-item, html.light-mode .result-item {
      color: #334155;
    }

    html.light-mode .popular-item:hover, html.light-mode .result-item:hover {
      background: rgba(2, 132, 199, 0.08);
      color: #0284c7;
    }

    /* Bouton Loupe dans la Navbar */
    .nav-search-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      padding: 8px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      margin-right: 12px;
    }

    .nav-search-btn:hover {
      color: #7dd3fc;
      background: rgba(14, 165, 233, 0.1);
    }

    /* Panneau Dropdown Déroulant Style Desjardins (Dark Glow) */
    .desjardins-search-panel {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background: rgba(10, 16, 28, 0.98);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(125, 211, 252, 0.2);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      z-index: 99999;
      transform: translateY(-100%);
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      padding: 30px 0 40px;
    }

    .desjardins-search-panel.active {
      transform: translateY(0);
    }

    .search-panel-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .search-input-wrapper {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 30px;
    }

    .search-field-box {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
      background: rgba(15, 23, 42, 0.8);
      border: 2px solid rgba(148, 163, 184, 0.25);
      border-radius: 40px;
      padding: 8px 24px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .search-field-box:focus-within {
      border-color: #38bdf8;
      box-shadow: 0 0 15px rgba(56, 189, 248, 0.25);
    }

    .search-field-box input {
      width: 100%;
      background: transparent;
      border: none;
      outline: none;
      color: #f8fafc;
      font-size: 1.25rem;
      font-family: inherit;
      padding-right: 40px;
    }

    .btn-clear-input {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 4px;
      display: none;
    }

    .btn-clear-input.visible {
      display: block;
    }

    .search-icon-inside {
      color: #38bdf8;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-close-panel {
      background: transparent;
      border: none;
      color: #cbd5e1;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .btn-close-panel:hover {
      color: #38bdf8;
    }

    .search-section-title {
      font-size: 0.75rem;
      font-weight: 700;
      color: #7dd3fc;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 16px;
    }

    .popular-searches-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .popular-item, .result-item {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #e2e8f0;
      text-decoration: none;
      font-size: 1.05rem;
      font-weight: 500;
      padding: 8px 12px;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .popular-item:hover, .result-item:hover {
      background: rgba(56, 189, 248, 0.1);
      color: #38bdf8;
      transform: translateX(4px);
    }

    .popular-item svg, .result-item svg {
      color: #64748b;
    }

    .popular-item:hover svg, .result-item:hover svg {
      color: #38bdf8;
    }

    .result-item-badge {
      margin-left: auto;
      font-size: 0.75rem;
      background: rgba(51, 65, 85, 0.6);
      color: #94a3b8;
      padding: 2px 8px;
      border-radius: 6px;
    }
</style>

</head>

<body>
  <div class="glow-bg top-left"></div>
  <div class="glow-bg top-right"></div>
  <div class="glow-bg bottom-center"></div>

  <header class="navbar">
    <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
      <span class="logo-symbol">
        <img src="public/images/logo.png" alt="NovaBank Logo" onerror="this.style.display='none'">
      </span>
      NovaBank
    </a>

    <nav class="nav-links">
      <?php if ($isLoggedIn): ?>
        <a href="index.php?action=dashboard">Tableau de bord</a>
        <a href="index.php?action=investment">Investissements</a>
        <a href="index.php?action=marketPage">Marché</a>
        <a href="index.php?action=accounts">Comptes</a>
        <a href="index.php?action=education">Éducation</a>
        <a href="index.php?action=about">À propos</a>
      <?php else: ?>
        <a href="index.php#simulator">Simulateur Bourse</a>
        <a href="index.php#leaderboard">Classement</a>
        <a href="index.php#features">Fonctionnalités</a>
        <a href="index.php?action=about">À propos</a>
      <?php endif; ?>
    </nav>

    <!-- SWITCH DE MODE (ENTRE ÉDUCATION ET LOUPE) -->
    <div class="theme-switch-container">
      <label class="theme-switch" for="themeToggle" aria-label="Basculer entre le mode clair et sombre">
        <input type="checkbox" id="themeToggle" <?= $isLightMode ? 'checked' : '' ?>>
        <span class="theme-slider">
          <svg class="icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg class="icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </span>
      </label>
    </div>

    <!-- BOUTON LOUPE POUR OUVRIR LE PANNEAU -->
    <button type="button" class="nav-search-btn" id="openSearchBtn" aria-label="Rechercher">
      <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </button>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <?php
          $headerUserName = trim((string) ($_SESSION['user']['username'] ?? $_SESSION['user']['Alias'] ?? 'Membre'));
          $headerUserInitial = mb_strtoupper(mb_substr($headerUserName, 0, 1, 'UTF-8'), 'UTF-8');
        ?>
        <a href="index.php?action=profile" class="user-profile-link" aria-label="Voir le profil de <?= htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8') ?>">
          <span class="user-avatar" aria-hidden="true">
            <span class="user-avatar-initial"><?= htmlspecialchars($headerUserInitial, ENT_QUOTES, 'UTF-8') ?></span>
            <img src="index.php?action=profilePhoto" alt="" onerror="this.style.display='none'">
          </span>
          <span class="user-greeting">Bonjour, <?= htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <a href="index.php?action=logout" class="btn-ghost">Déconnexion</a>
      <?php else: ?>
        <a href="index.php?action=loginPage" class="btn-ghost">Se connecter</a>
        <a href="index.php?action=registerPage" class="btn-primary">Créer un compte</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- PANNEAU DÉROULANT STYLE DESJARDINS -->
  <div class="desjardins-search-panel" id="desjardinsSearchPanel">
    <div class="search-panel-container">
      
      <div class="search-input-wrapper">
        <div class="search-field-box">
          <input type="text" id="desjardinsSearchInput" placeholder="Rechercher sur NovaBank..." autocomplete="off">
          <button type="button" class="btn-clear-input" id="clearSearchInput">&times;</button>
          <div class="search-icon-inside">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          </div>
        </div>
        <button type="button" class="btn-close-panel" id="closeSearchBtn">Fermer</button>
      </div>

      <!-- Zone dynamique (Populaires / Résultats) -->
      <div id="searchDynamicContent">
        <div class="search-section-title">Recherches populaires</div>
        <div class="popular-searches-list">
          <a href="index.php?action=marketPage" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Acheter des actions Apple (AAPL)
          </a>
          <a href="index.php?action=investment" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Consulter mon portefeuille
          </a>
          <a href="index.php?action=accounts" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Solde des comptes
          </a>
          <a href="index.php?action=education" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Guides & Éducation financière
          </a>
        </div>
      </div>

    </div>
  </div>

  <div class="market-ticker">
    <div class="ticker-track">
      <?php if (empty($marketData)): ?>
        <?php for ($copy = 0; $copy < 4; $copy++): ?>
          <div class="ticker-group">
            <div class="ticker-item"><span class="sym">API</span> <span class="val">Données indisponibles</span></div>
          </div>
        <?php endfor; ?>
      <?php else: ?>
        <?php for ($copy = 0; $copy < 4; $copy++): ?>
          <div class="ticker-group">
            <?php foreach ($marketData as $stock):
              $isPositive = ($stock['change'] ?? 0) >= 0;
              $class = $isPositive ? 'up' : 'down';
              $sign = $isPositive ? '+' : '';
            ?>
              <div class="ticker-item" data-stock-id="<?= htmlspecialchars($stock['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <span class="sym"><?= htmlspecialchars($stock['symbol'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="val">$<?= htmlspecialchars($stock['price'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="trend <?= $class ?>"><?= $sign . htmlspecialchars($stock['change'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>

  <script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    // Gestion du Switch Théma / Cookie
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      themeToggle.addEventListener('change', (e) => {
        const theme = e.target.checked ? 'light' : 'dark';
        
        if (e.target.checked) {
          document.documentElement.classList.add('light-mode');
        } else {
          document.documentElement.classList.remove('light-mode');
        }

        // Sauvegarde dans un cookie valable 1 an
        document.cookie = `novabank_theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
      });
    }

    // Gestion de la recherche
    const openBtn = document.getElementById('openSearchBtn');
    const closeBtn = document.getElementById('closeSearchBtn');
    const panel = document.getElementById('desjardinsSearchPanel');
    const input = document.getElementById('desjardinsSearchInput');
    const clearBtn = document.getElementById('clearSearchInput');
    const dynamicContent = document.getElementById('searchDynamicContent');

    const searchIndex = [
      { title: "Tableau de bord", category: "Navigation", url: "index.php?action=dashboard", badge: "Page" },
      { title: "Marché & Bourse (TradingView)", category: "Bourse", url: "index.php?action=marketPage", badge: "Achat / Vente" },
      { title: "Portefeuille d'investissements", category: "Bourse", url: "index.php?action=investment", badge: "Actifs" },
      { title: "Comptes bancaires & Solde", category: "Banque", url: "index.php?action=accounts", badge: "Comptes" },
      { title: "Espace Éducation & Guides", category: "Éducation", url: "index.php?action=education", badge: "Apprentissage" },
      { title: "À propos de NovaBank", category: "Informations", url: "index.php?action=about", badge: "Projet" },
      { title: "Comprendre les ordres d'achat à terme", category: "Éducation", url: "index.php?action=education#orders", badge: "Article" },
      { title: "Analyse technique & Graphiques", category: "Éducation", url: "index.php?action=education#analysis", badge: "Tutoriel" },
      { title: "Gestion des risques et diversification", category: "Éducation", url: "index.php?action=education#risk", badge: "Guide" }
    ];

    const defaultPopularHTML = dynamicContent.innerHTML;

    function openPanel() {
      panel.classList.add('active');
      setTimeout(() => input.focus(), 150);
    }

    function closePanel() {
      panel.classList.remove('active');
      input.value = '';
      clearBtn.classList.remove('visible');
      dynamicContent.innerHTML = defaultPopularHTML;
    }

    if (openBtn) openBtn.addEventListener('click', openPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        input.value = '';
        clearBtn.classList.remove('visible');
        dynamicContent.innerHTML = defaultPopularHTML;
        input.focus();
      });
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && panel.classList.contains('active')) {
        closePanel();
      }
    });

    if (input) {
      input.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();

        if (query.length > 0) {
          clearBtn.classList.add('visible');
        } else {
          clearBtn.classList.remove('visible');
          dynamicContent.innerHTML = defaultPopularHTML;
          return;
        }

        const results = searchIndex.searchIndex ? [] : searchIndex.filter(item =>
          item.title.toLowerCase().includes(query) ||
          item.category.toLowerCase().includes(query)
        );

        renderResults(results, query);
      });
    }

    function renderResults(items, query) {
      if (items.length === 0) {
        dynamicContent.innerHTML = `
          <div class="search-section-title">Résultats pour "${query}"</div>
          <div style="color: #64748b; padding: 12px 0;">Aucun résultat correspondant trouvé.</div>
        `;
        return;
      }

      let html = `<div class="search-section-title">Résultats (${items.length})</div><div class="popular-searches-list">`;
      items.forEach(item => {
        html += `
          <a href="${item.url}" class="result-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <span>${item.title}</span>
            <span class="result-item-badge">${item.badge}</span>
          </a>
        `;
      });
      html += `</div>`;

      dynamicContent.innerHTML = html;
    }
  });
  </script>