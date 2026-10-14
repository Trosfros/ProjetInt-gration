<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle =$pageTitle ?? 'NovaBank | Banque virtuelle & Simulateur de Bourse';
$isLoggedIn = !empty($_SESSION['user']);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/DAL/stockDAL.php';
require_once dirname(__DIR__, 2) . '/configs/database.php';

use Dotenv\Dotenv;
use Composer\CaBundle\CaBundle;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));$dotenv->load();

$apiKey = $_ENV['FinnhubKey'] ?? $_ENV['API_KEY'] ?? '';

// --- CONNEXION (bdnovabank) ---

$marketData = [];

if (!isset($symbols) || !is_array($symbols) || empty($symbols)) {$symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA'];
}

// --- SYSTÈME DE CACHE FINNHUB ---
$cacheFile = dirname(__DIR__, 2) . '/storage/market_cache.json';
$cacheLockFile = dirname(__DIR__, 2) . '/storage/market_cache.lock';
$cacheLifetime = 60;
$loadCachedMarketData = static function () use ($cacheFile): array {
    if (!is_file($cacheFile)) {
        return [];
    }

    $cacheHandle = fopen($cacheFile, 'r');
    if ($cacheHandle === false) {
        throw new RuntimeException('Impossible d’ouvrir le cache des cours.');
    }
    if (!flock($cacheHandle, LOCK_SH)) {
        fclose($cacheHandle);
        throw new RuntimeException('Impossible de verrouiller la lecture du cache des cours.');
    }

    try {
        $cachedContent = stream_get_contents($cacheHandle);
        if ($cachedContent === false) {
            throw new RuntimeException('Impossible de lire le cache des cours.');
        }

        $cachedData = json_decode($cachedContent, true);
        return is_array($cachedData) ? $cachedData : [];
    } finally {
        flock($cacheHandle, LOCK_UN);
        fclose($cacheHandle);
    }
};

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) <$cacheLifetime)) {
    $marketData = $loadCachedMarketData();
} else {
    $cacheDirectory = dirname($cacheFile);
    if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
        throw new RuntimeException('Impossible de créer le dossier du cache des cours.');
    }

    $lockHandle = fopen($cacheLockFile, 'c');
    if ($lockHandle === false) {
        throw new RuntimeException('Impossible d’ouvrir le verrou du cache des cours.');
    }
    if (!flock($lockHandle, LOCK_EX)) {
        fclose($lockHandle);
        throw new RuntimeException('Impossible de verrouiller le cache des cours.');
    }

    try {
        if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
            $marketData = $loadCachedMarketData();
        } else {
            $cachedMarketData = $loadCachedMarketData();
            if (!touch($cacheFile)) {
                throw new RuntimeException('Impossible d’actualiser la limite du cache des cours.');
            }

            $marketData = [];
            $caBundle = CaBundle::getSystemCaRootBundlePath();
            $stockId = 0;

            foreach ($symbols as $sym) {
                $url = 'https://finnhub.io/api/v1/quote?' . http_build_query([
                    'symbol' => $sym,
                    'token'  => $apiKey,
                ]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);

                $res = curl_exec($ch);
                $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $stockId += 1;
                $quote = json_decode($res, true);

                if ($httpStatus === 200 && $quote && isset($quote['c'], $quote['dp'])) {
                    $priceFormatted = number_format($quote['c'], 2, '.', '');
                    $changeFormatted = round($quote['dp'], 2);

                    if (isset($stockDal)) {
                        $stockDal->AddStock($sym, $priceFormatted, $changeFormatted);
                    }

                    $marketData[] = [
                        'id' => $stockId,
                        'symbol' => $sym,
                        'price' => $priceFormatted,
                        'change' => $changeFormatted,
                    ];
                }
            }

            if (!empty($marketData)) {
                $encodedMarketData = json_encode($marketData);
                if ($encodedMarketData === false || file_put_contents($cacheFile, $encodedMarketData, LOCK_EX) === false) {
                    throw new RuntimeException('Impossible d’enregistrer le cache des cours.');
                }
            } else {
                $marketData = $cachedMarketData;
            }
        }
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="public/css/main.css">
  <?php
    $availablePageStylesheets = ['home.css', 'dashboard.css', 'auth.css', 'errors.css', 'market.css', 'education.css'];
    foreach (($pageStylesheets ?? []) as $pageStylesheet):
      if (in_array($pageStylesheet,$availablePageStylesheets, true)):
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
      margin-left: auto;
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

    /* Barre de saisie principale */
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

    /* Section Recherches populaires & Résultats */
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
      <?php else: ?>
        <a href="index.php#simulator">Simulateur Bourse</a>
        <a href="index.php#leaderboard">Classement</a>
        <a href="index.php#features">Fonctionnalités</a>
        <a href="index.php#about">À propos</a>
      <?php endif; ?>
    </nav>

    <!-- BOUTON LOUPE POUR OUVRIR LE PANNEAU -->
    <button type="button" class="nav-search-btn" id="openSearchBtn" aria-label="Rechercher">
      <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </button>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <span class="user-greeting" style="color: var(--text-muted); font-size: 0.9rem; margin-right: 1rem;">
          Bonjour, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['Alias'] ?? 'Membre') ?>
        </span>
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
            <?php foreach ($marketData as$stock):
              $isPositive =$stock['change'] >= 0;
              $class =$isPositive ? 'up' : 'down';
              $sign =$isPositive ? '+' : '';
            ?>
              <div class="ticker-item" data-stock-id="<?= htmlspecialchars($stock['id'] ?? '') ?>">
                <span class="sym"><?= htmlspecialchars($stock['symbol']) ?></span>
                <span class="val">$<?= htmlspecialchars($stock['price']) ?></span>
                <span class="trend <?= $class ?>"><?= $sign . htmlspecialchars($stock['change']) ?>%</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>

  <script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
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

        const results = searchIndex.filter(item =>
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