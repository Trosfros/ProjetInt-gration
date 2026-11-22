<?php
if (session_status() === PHP_SESSION_NONE) {

}

$pageTitle = $pageTitle ?? 'NovaBank | Banque virtuelle & Simulateur de Bourse';
$isLoggedIn = !empty($_SESSION['user']);
$pageCsrfToken = function_exists('getCsrfToken') ? getCsrfToken() : '';
require_once dirname(__DIR__, 2) . '/configs/language.php';
$currentLanguage = getCurrentLanguage();

// Récupération du thème depuis le cookie (par défaut 'dark')
$currentTheme = $_COOKIE['novabank_theme'] ?? 'dark';
$isLightMode = ($currentTheme === 'light');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/configs/currency_display.php';
$serverDisplayCurrency = in_array($_COOKIE['novabank_display_currency'] ?? 'CAD', ['CAD', 'USD', 'EUR'], true)
  ? ($_COOKIE['novabank_display_currency'] ?? 'CAD')
  : 'CAD';
$cachedCadUsdReferenceRate = getCachedCadUsdReferenceRate();
require_once $rootPath . '/vendor/autoload.php';
require_once $rootPath . '/src/DAL/stockDAL.php';
require_once $rootPath . '/configs/database.php';
require_once $rootPath . '/src/Models/snowSupport.php';
use Dotenv\Dotenv;
require_once $rootPath . '/src/Middlewares/FinnhubMiddleware.php';

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$apiKey = $_ENV['FinnhubKey'] ?? $_ENV['API_KEY'] ?? '';

// --- SYSTÈME DE CACHE FINNHUB ---

if (!isset($symbols) || !is_array($symbols) || empty($symbols)) {
  $symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA'];
}

$marketData = getMarketdata($rootPath,$apiKey,$symbols,60);
$headerNotificationCount = null;


if ($isLoggedIn) {
  try {
    require_once $rootPath . '/src/Models/NotificationModel.php';
    $headerNotificationCount = (new NotificationModel())->getUnreadCount((int) $_SESSION['user']['id']);
  } catch (Throwable $e) {
    error_log('header.php failed to load unread notifications: ' . $e->getMessage());
  }
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8') ?>" data-language="<?= htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8') ?>" class="<?= $isLightMode ? 'light-mode' : '' ?>"
  data-server-display-currency="<?= $serverDisplayCurrency ?>"
  data-currency-rate-ready="<?= $cachedCadUsdReferenceRate !== null ? 'true' : 'false' ?>"
  data-cad-usd-rate="<?= $cachedCadUsdReferenceRate !== null ? number_format($cachedCadUsdReferenceRate['rate'], 8, '.', '') : '' ?>"
  data-cad-eur-rate="<?= $cachedCadUsdReferenceRate !== null && isset($cachedCadUsdReferenceRate['eurRate']) && $cachedCadUsdReferenceRate['eurRate'] !== null ? number_format($cachedCadUsdReferenceRate['eurRate'], 8, '.', '') : '' ?>"
  data-cad-usd-rate-date="<?= $cachedCadUsdReferenceRate !== null ? htmlspecialchars($cachedCadUsdReferenceRate['date'], ENT_QUOTES, 'UTF-8') : '' ?>"
  data-cad-usd-rate-stale="<?= $cachedCadUsdReferenceRate !== null && $cachedCadUsdReferenceRate['isStale'] ? 'true' : 'false' ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($pageCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <?php if ($isLoggedIn): ?>
    <script>
      (() => {
        const serverCurrency = ['CAD', 'USD', 'EUR'].includes(document.documentElement.dataset.serverDisplayCurrency)
          ? document.documentElement.dataset.serverDisplayCurrency
          : 'CAD';
        let displayCurrency = serverCurrency;
        try {
          const savedCurrency = localStorage.getItem('novabank_display_currency');
          if (savedCurrency === 'CAD' || savedCurrency === 'USD' || savedCurrency === 'EUR') {
            displayCurrency = savedCurrency;
          }
        } catch (error) {
          console.error('Unable to read the saved display currency.', error);
        }
        document.documentElement.dataset.displayCurrency = displayCurrency;
        if (
          document.documentElement.dataset.currencyRateReady !== 'true'
          || displayCurrency !== serverCurrency
        ) {
          document.documentElement.classList.add('currency-pending');
        }
        try {
          const secureAttribute = location.protocol === 'https:' ? '; Secure' : '';
          document.cookie = `novabank_display_currency=${displayCurrency}; Max-Age=31536000; Path=/; SameSite=Lax${secureAttribute}`;
        } catch (error) {
          console.error('Unable to synchronize the display currency cookie.', error);
        }
      })();
    </script>
  <?php endif; ?>

  <link rel="stylesheet" href="public/css/main.css">
   <link rel="stylesheet" href="/public/css/snow.css">
  <?php
  $availablePageStylesheets = ['home.css', 'dashboard.css', 'auth.css', 'errors.css', 'market.css', 'education.css', 'history.css', 'leaderboard.css', 'transfer.css', 'admin.css', 'audit.css', 'profile.css', 'about.css', 'privacy.css', 'notifications.css', 'support.css', 'settings.css'];
  foreach (($pageStylesheets ?? []) as $pageStylesheet):
    if (in_array($pageStylesheet, $availablePageStylesheets, true)):
      ?>
      <link rel="stylesheet" href="public/css/<?= htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8') ?>">
      <?php
    endif;
  endforeach;
  ?>
  <link rel="stylesheet" href="public/css/investment.css">
  <link rel="stylesheet" href="public/css/notifications.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap"
    rel="stylesheet">

  <style>
    .navbar-authenticated .logo {
      flex: 0 0 auto;
    }

    .navbar-authenticated .nav-links {
      flex: 1;
      gap: clamp(0.25rem, 1vw, 1rem);
    }

    .navbar-authenticated .nav-links a {
      margin: 0;
      padding: 0.6rem 0.75rem;
      border-radius: 9px;
      transition: color 0.2s ease, background 0.2s ease;
    }

    .navbar-authenticated .nav-links a:hover,
    .navbar-authenticated .nav-links a[aria-current="page"] {
      color: #7dd3fc;
      background: rgba(56, 189, 248, 0.1);
    }

    .navbar-authenticated .nav-search-btn {
      flex: 0 0 auto;
      margin: 0 0.5rem;
    }

    .navbar-authenticated .user-profile-link {
      gap: 0.7rem;
      margin-right: 0;
      color: #e2e8f0;
      font-weight: 600;
    }

    .navbar-authenticated .nav-actions {
      gap: clamp(0.5rem, 1vw, 0.9rem);
    }

    html.light-mode .navbar-authenticated .nav-links a:hover,
    html.light-mode .navbar-authenticated .nav-links a[aria-current="page"] {
      color: #0284c7;
      background: rgba(2, 132, 199, 0.08);
    }

    html.light-mode .navbar-authenticated .user-profile-link {
      color: #334155;
    }

    @media (max-width: 1100px) {
      .navbar-authenticated .nav-links {
        display: none;
      }
    }

    .currency-rate-status {
      position: fixed;
      top: 82px;
      right: 20px;
      z-index: 100;
      max-width: min(360px, calc(100vw - 40px));
      padding: 10px 14px;
      color: #fff;
      background: #7f1d1d;
      border: 1px solid rgba(252, 165, 165, 0.5);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
      font-size: 0.82rem;
    }

    .currency-rate-status.is-loading {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    .currency-rate-status.is-warning {
      color: #fef3c7;
      background: #78350f;
      border-color: rgba(252, 211, 77, 0.5);
    }

    .currency-rate-status.is-error {
      color: #fff;
      background: #7f1d1d;
      border-color: rgba(252, 165, 165, 0.5);
    }

    .currency-rate-status:empty {
      display: none;
    }

    .currency-display-control select {
      min-height: 36px;
      padding: 0 30px 0 11px;
      color: #e2e8f0;
      background-color: rgba(15, 23, 42, 0.78);
      border: 1px solid rgba(148, 163, 184, 0.28);
      border-radius: 9px;
      font: inherit;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
    }

    .currency-display-control select:focus-visible {
      outline: 2px solid #38bdf8;
      outline-offset: 2px;
    }

    .currency-display-control option {
      color: #0f172a;
      background: #fff;
    }

    .header-settings-menu {
      position: relative;
    }

    .header-settings-toggle {
      display: grid;
      width: 38px;
      height: 38px;
      place-items: center;
      color: #cbd5e1;
      background: transparent;
      border: 1px solid transparent;
      border-radius: 10px;
      cursor: pointer;
      transition: color 0.2s ease, background 0.2s ease, border-color 0.2s ease;
    }

    .header-settings-toggle svg {
      width: 20px;
      height: 20px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 1.8;
    }

    .header-settings-toggle:hover,
    .header-settings-toggle[aria-expanded="true"] {
      color: #7dd3fc;
      background: rgba(56, 189, 248, 0.1);
      border-color: rgba(56, 189, 248, 0.18);
    }

    .header-settings-toggle:focus-visible,
    .header-settings-dropdown a:focus-visible,
    .header-settings-dropdown button:focus-visible {
      outline: 2px solid #38bdf8;
      outline-offset: 2px;
    }

    .header-settings-dropdown {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      z-index: 60;
      width: 220px;
      padding: 7px;
      background: rgba(14, 18, 29, 0.97);
      border: 1px solid var(--card-border);
      border-radius: 14px;
      box-shadow: 0 18px 48px rgba(0, 0, 0, 0.38);
      backdrop-filter: blur(18px);
    }

    .header-settings-dropdown[hidden] {
      display: none;
    }

    .header-settings-dropdown a {
      display: flex;
      align-items: center;
      gap: 11px;
      min-height: 42px;
      padding: 0 11px;
      color: #e2e8f0;
      border-radius: 9px;
      font-size: 0.9rem;
      font-weight: 500;
      text-decoration: none;
      transition: color 0.18s ease, background 0.18s ease;
    }

    .header-settings-dropdown form {
      margin: 0;
    }

    .header-settings-dropdown button.header-logout-link {
      display: flex;
      align-items: center;
      gap: 11px;
      width: 100%;
      min-height: 42px;
      padding: 0 11px;
      color: #e2e8f0;
      background: transparent;
      border: 0;
      border-radius: 9px;
      font: inherit;
      font-size: 0.9rem;
      font-weight: 500;
      text-align: left;
      cursor: pointer;
    }

    .header-settings-dropdown a:hover {
      color: #7dd3fc;
      background: rgba(56, 189, 248, 0.1);
    }

    .header-settings-dropdown button.header-logout-link:hover {
      color: #7dd3fc;
      background: rgba(56, 189, 248, 0.1);
    }

    .header-settings-dropdown a svg {
      width: 18px;
      height: 18px;
      flex: 0 0 18px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 1.8;
    }

    .header-settings-dropdown button.header-logout-link svg {
      width: 18px;
      height: 18px;
      flex: 0 0 18px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 1.8;
    }

    .header-settings-dropdown .header-logout-link {
      margin-top: 5px;
      padding-top: 5px;
      border-top: 1px solid rgba(148, 163, 184, 0.14);
      border-radius: 0 0 9px 9px;
    }

    html.light-mode .header-settings-toggle {
      color: #475569;
    }

    html.light-mode .header-settings-toggle:hover,
    html.light-mode .header-settings-toggle[aria-expanded="true"] {
      color: #0284c7;
      background: rgba(2, 132, 199, 0.08);
      border-color: rgba(2, 132, 199, 0.16);
    }

    html.light-mode .header-settings-dropdown {
      background: rgba(255, 255, 255, 0.98);
      border-color: rgba(100, 116, 139, 0.2);
      box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
    }

    html.light-mode .header-settings-dropdown a {
      color: #334155;
    }

    html.light-mode .header-settings-dropdown a:hover {
      color: #0284c7;
      background: rgba(2, 132, 199, 0.08);
    }

    html.light-mode .currency-display-control select {
      color: #334155;
      background-color: rgba(248, 250, 252, 0.95);
      border-color: rgba(100, 116, 139, 0.28);
    }

    html.currency-pending .currency-display {
      color: transparent !important;
      background: linear-gradient(90deg, rgba(148, 163, 184, 0.12), rgba(148, 163, 184, 0.34), rgba(148, 163, 184, 0.12));
      background-size: 200% 100%;
      border-radius: 4px;
      animation: currency-shimmer 1.2s ease-in-out infinite;
    }

    @keyframes currency-shimmer {
      to {
        background-position: -200% 0;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      html.currency-pending .currency-display {
        animation: none;
      }
    }

    @media (max-width: 520px) {
      .navbar.navbar-authenticated {
        gap: 0.4rem;
        padding: 0.65rem 0.7rem !important;
      }

      .navbar-authenticated .logo {
        gap: 5px;
        font-size: 1.1rem;
      }

      .navbar-authenticated .nav-actions {
        gap: 0.35rem;
      }

      .navbar-authenticated .user-profile-name {
        display: none;
      }

      .navbar-authenticated .nav-search-btn {
        display: none;
      }

      .navbar-authenticated .currency-display-control select {
        min-height: 34px;
        padding-right: 22px;
        padding-left: 8px;
      }
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

    html.light-mode .user-profile-name {
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
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    html.light-mode .search-field-box input {
      color: #0f172a;
    }

    html.light-mode .popular-item,
    html.light-mode .result-item {
      color: #334155;
    }

    html.light-mode .popular-item:hover,
    html.light-mode .result-item:hover {
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

    .popular-item,
    .result-item {
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

    .popular-item:hover,
    .result-item:hover {
      background: rgba(56, 189, 248, 0.1);
      color: #38bdf8;
      transform: translateX(4px);
    }

    .popular-item svg,
    .result-item svg {
      color: #64748b;
    }

    .popular-item:hover svg,
    .result-item:hover svg {
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

  <link rel="stylesheet" href="public/css/light-mode.css">

</head>

<body>
  <div class="glow-bg top-left"></div>
  <div class="glow-bg top-right"></div>

  <div class="glow-bg top-left"></div>
  <header class="navbar<?= $isLoggedIn ? ' navbar-authenticated' : '' ?>">
    <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
      <span class="logo-symbol">
        <img src="public/images/logo.png" alt="NovaBank Logo" onerror="this.style.display='none'">
      </span>
      NovaBank
    </a>

    <?php if ($isLoggedIn): ?>
      <nav class="nav-links nav-links-authenticated" aria-label="Navigation principale">
        <a href="index.php"<?= ($activePage ?? '') === 'dashboard' ? ' aria-current="page"' : '' ?>>Tableau de bord</a>
        <a href="index.php?action=marketPage"<?= ($activePage ?? '') === 'market' ? ' aria-current="page"' : '' ?>>Marchés</a>
        <a href="index.php?action=investment"<?= ($activePage ?? '') === 'investment' ? ' aria-current="page"' : '' ?>>Portefeuille</a>
        <a href="index.php?action=accounts"<?= ($activePage ?? '') === 'Accounts' ? ' aria-current="page"' : '' ?>>Comptes</a>
        <a href="index.php?action=leaderboard"<?= ($activePage ?? '') === 'leaderboard' ? ' aria-current="page"' : '' ?>>Classement</a>
      </nav>
    <?php else: ?>
      <nav class="nav-links">
        <a href="index.php#simulator">Simulateur Bourse</a>
        <a href="index.php?action=leaderboard">Classement</a>
        <a href="index.php#features">Fonctionnalités</a>
        <a href="index.php?action=about">À propos</a>
      </nav>
    <?php endif; ?>

    <!-- BOUTON LOUPE POUR OUVRIR LE PANNEAU -->
    <button type="button" class="nav-search-btn" id="openSearchBtn" aria-label="Rechercher">
      <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
    </button>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <?php
        $headerUserName = trim((string) ($_SESSION['user']['username'] ?? $_SESSION['user']['Alias'] ?? 'Membre'));
        $headerUserInitial = mb_strtoupper(mb_substr($headerUserName, 0, 1, 'UTF-8'), 'UTF-8');
        ?>
        <div class="currency-display-control">
          <select id="headerCurrencySelect" aria-label="Devise d’affichage">
            <option value="CAD">CAD</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
          </select>
        </div>
        <script>
          document.getElementById('headerCurrencySelect').value =
            ['CAD', 'USD', 'EUR'].includes(document.documentElement.dataset.displayCurrency)
              ? document.documentElement.dataset.displayCurrency
              : 'CAD';
        </script>
        <a href="index.php?action=notifications" class="notification-nav-link" data-notification-link
          aria-label="<?= $headerNotificationCount === null ? 'Notifications indisponibles' : 'Notifications' . ($headerNotificationCount > 0 ? ', ' . $headerNotificationCount . ' non lues' : '') ?>"
          title="Notifications">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
          </svg>
          <?php if ($headerNotificationCount !== null && $headerNotificationCount > 0): ?>
            <span class="notification-nav-badge" aria-hidden="true">!</span>
          <?php endif; ?>
        </a>
        <a href="index.php?action=profile" class="user-profile-link"
          aria-label="Voir le profil de <?= htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8') ?>">
          <span class="user-avatar" aria-hidden="true">
            <span class="user-avatar-initial"><?= htmlspecialchars($headerUserInitial, ENT_QUOTES, 'UTF-8') ?></span>
            <img src="index.php?action=profilePhoto" alt="" onerror="this.style.display='none'">
          </span>
          <span class="user-profile-name"><?= htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <div class="header-settings-menu" id="headerSettingsMenuContainer">
          <button type="button" class="header-settings-toggle" id="headerSettingsToggle"
            aria-label="Ouvrir les options du compte" aria-expanded="false" aria-controls="headerSettingsDropdown">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
          <div class="header-settings-dropdown" id="headerSettingsDropdown" hidden>
            <a href="index.php?action=settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="3" />
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
            </svg>
              <span>Paramètres</span>
            </a>
            <form method="post" action="index.php?action=logout">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($pageCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <button class="header-logout-link" type="submit">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
              </svg>
              <span>Déconnexion</span>
              </button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <a href="index.php?action=loginPage" class="btn-ghost">Se connecter</a>
        <a href="index.php?action=registerPage" class="btn-primary">Créer un compte</a>
      <?php endif; ?>
    </div>
  </header>

  <?php if ($isLoggedIn): ?>
    <script>
      (() => {
        const container = document.getElementById('headerSettingsMenuContainer');
        const toggle = document.getElementById('headerSettingsToggle');
        const menu = document.getElementById('headerSettingsDropdown');
        if (!container || !toggle || !menu) return;

        const setMenuOpen = (isOpen) => {
          menu.hidden = !isOpen;
          toggle.setAttribute('aria-expanded', String(isOpen));
        };

        toggle.addEventListener('click', () => setMenuOpen(menu.hidden));
        document.addEventListener('click', (event) => {
          if (!container.contains(event.target)) setMenuOpen(false);
        });
        container.addEventListener('focusout', (event) => {
          if (!container.contains(event.relatedTarget)) setMenuOpen(false);
        });
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && !menu.hidden) {
            setMenuOpen(false);
            toggle.focus();
          }
        });
      })();
    </script>
  <?php endif; ?>

  <?php if ($isLoggedIn): ?>
    <div id="currencyRateStatus" class="currency-rate-status is-loading" role="status" aria-live="polite">
      Chargement des taux CAD/USD/EUR…
    </div>
  <?php endif; ?>

  <?php if ($isLoggedIn): ?>
    <script>
      (() => {
        const link = document.querySelector('[data-notification-link]');
        if (!link) return;

        const refreshNotificationCount = async () => {
          try {
            const response = await fetch('index.php?action=notificationsCount', {
              headers: { Accept: 'application/json' },
              cache: 'no-store'
            });
            if (!response.ok) throw new Error('Impossible de mettre à jour le compteur de notifications.');

            const data = await response.json();
            if (!Number.isInteger(data.unreadCount) || data.unreadCount < 0) {
              throw new Error('Le compteur de notifications reçu est invalide.');
            }

            link.setAttribute('aria-label', data.unreadCount > 0
              ? `Notifications, ${data.unreadCount} non lues`
              : 'Notifications');
            let badge = link.querySelector('.notification-nav-badge');
            if (data.unreadCount === 0) {
              if (badge) badge.remove();
              return;
            }
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'notification-nav-badge';
              badge.setAttribute('aria-hidden', 'true');
              link.appendChild(badge);
            }
            badge.textContent = '!';
          } catch (error) {
            console.error(error);
          }
        };

        refreshNotificationCount();
        window.setInterval(refreshNotificationCount, 30000);
      })();
    </script>
  <?php endif; ?>

  <!-- PANNEAU DÉROULANT STYLE DESJARDINS -->
  <div class="desjardins-search-panel" id="desjardinsSearchPanel">
    <div class="search-panel-container">

      <div class="search-input-wrapper">
        <div class="search-field-box">
          <input type="text" id="desjardinsSearchInput" placeholder="Rechercher sur NovaBank..." autocomplete="off">
          <button type="button" class="btn-clear-input" id="clearSearchInput">&times;</button>
          <div class="search-icon-inside">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
        </div>
        <button type="button" class="btn-close-panel" id="closeSearchBtn">Fermer</button>
      </div>

      <!-- Zone dynamique (Populaires / Résultats) -->
      <div id="searchDynamicContent">
        <div class="search-section-title">Recherches populaires</div>
        <div class="popular-searches-list">
          <a href="index.php?action=marketPage" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Acheter des actions Apple (AAPL)
          </a>
          <a href="index.php?action=investment" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Consulter mon portefeuille
          </a>
          <a href="index.php?action=accounts" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Solde des comptes
          </a>
          <a href="index.php?action=education" class="popular-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
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
                <span class="val"><?= renderCurrencyAmount((float) ($stock['price'] ?? 0), 'USD') ?></span>
                <span
                  class="trend <?= $class ?>"><?= $sign . htmlspecialchars($stock['change'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</span>
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

  <?php if ($isLoggedIn): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const rateStatus = document.getElementById('currencyRateStatus');
        const currencySelect = document.getElementById('headerCurrencySelect');
        const preferenceStorageKey = 'novabank_display_currency';
        let displayCurrency = ['CAD', 'USD', 'EUR'].includes(document.documentElement.dataset.displayCurrency)
          ? document.documentElement.dataset.displayCurrency
          : 'CAD';
        let cadToUsdRate = null;
        let cadToEurRate = null;
        let rateDate = '';
        if (currencySelect) currencySelect.value = displayCurrency;

        const applyDisplayCurrency = () => {
          if (cadToUsdRate === null) return;
          if (displayCurrency === 'EUR' && cadToEurRate === null) return;

          document.querySelectorAll('[data-currency-value][data-currency-base]').forEach((amountElement) => {
            const amount = Number(amountElement.dataset.currencyValue);
            const baseCurrency = amountElement.dataset.currencyBase.toUpperCase();
            if (!Number.isFinite(amount) || !['CAD', 'USD', 'EUR'].includes(baseCurrency)) return;

            const ratesFromCad = { CAD: 1, USD: cadToUsdRate, EUR: cadToEurRate };
            const amountInCad = baseCurrency === 'CAD'
              ? amount
              : baseCurrency === 'USD'
                ? amount / cadToUsdRate
                : amount / cadToEurRate;
            const convertedAmount = amountInCad * ratesFromCad[displayCurrency];

            const numberLocale = document.documentElement.dataset.language === 'en' ? 'en-CA' : 'fr-CA';
            amountElement.textContent = new Intl.NumberFormat(numberLocale, {
              style: 'currency',
              currency: displayCurrency,
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            }).format(convertedAmount);
          });
          document.documentElement.classList.remove('currency-pending');
        };

        try {
          const savedCurrency = localStorage.getItem(preferenceStorageKey);
          if (savedCurrency === 'CAD' || savedCurrency === 'USD' || savedCurrency === 'EUR') {
            displayCurrency = savedCurrency;
          }
        } catch (error) {
          console.error('Unable to read the saved display currency.', error);
        }
        document.documentElement.dataset.displayCurrency = displayCurrency;

        const setDisplayCurrency = (currency, persist = true) => {
          if (currency !== 'CAD' && currency !== 'USD' && currency !== 'EUR') {
            return false;
          }

          displayCurrency = currency;
          document.documentElement.dataset.displayCurrency = currency;
          if (currencySelect) currencySelect.value = currency;
          try {
            const secureAttribute = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `novabank_display_currency=${displayCurrency}; Max-Age=31536000; Path=/; SameSite=Lax${secureAttribute}`;
          } catch (error) {
            console.error('Unable to synchronize the display currency cookie.', error);
          }
          try {
            if (persist) {
              localStorage.setItem(preferenceStorageKey, displayCurrency);
            }
          } catch (error) {
            console.error('Unable to save the display currency preference.', error);
            applyDisplayCurrency();
            return false;
          }

          applyDisplayCurrency();
          return true;
        };

        window.NovaBankCurrency = {
          getDisplayCurrency: () => displayCurrency,
          setDisplayCurrency
        };

        if (currencySelect) {
          currencySelect.addEventListener('change', () => {
            setDisplayCurrency(currencySelect.value);
          });
        }

        window.addEventListener('storage', (event) => {
          if (event.key === preferenceStorageKey && (event.newValue === 'CAD' || event.newValue === 'USD' || event.newValue === 'EUR')) {
            setDisplayCurrency(event.newValue, false);
          }
        });

        const cachedRate = Number(document.documentElement.dataset.cadUsdRate);
        if (Number.isFinite(cachedRate) && cachedRate > 0) {
          cadToUsdRate = cachedRate;
          const cachedEurRate = Number(document.documentElement.dataset.cadEurRate);
          cadToEurRate = Number.isFinite(cachedEurRate) && cachedEurRate > 0 ? cachedEurRate : null;
          rateDate = document.documentElement.dataset.cadUsdRateDate || '';
          if (document.documentElement.dataset.cadUsdRateStale === 'true') {
            rateStatus.className = 'currency-rate-status is-warning';
            rateStatus.textContent = `Taux du ${rateDate} utilisé; actualisation momentanément indisponible.`;
          } else {
            rateStatus.className = 'currency-rate-status';
            rateStatus.textContent = '';
          }
          applyDisplayCurrency();
        }

        fetch('index.php?action=currencyRate', {
          headers: { Accept: 'application/json' },
          cache: 'no-store'
        })
          .then(async (response) => {
            const result = await response.json();
            if (!response.ok) {
              throw new Error(result.error || 'Impossible de charger les taux CAD/USD/EUR.');
            }
            if (
              !Number.isFinite(Number(result.rate)) ||
              Number(result.rate) <= 0 ||
              !Number.isFinite(Number(result.eurRate)) ||
              Number(result.eurRate) <= 0 ||
              typeof result.date !== 'string' ||
              typeof result.isStale !== 'boolean'
            ) {
              throw new Error('Le taux CAD/USD/EUR reçu est invalide.');
            }

            cadToUsdRate = Number(result.rate);
            cadToEurRate = Number(result.eurRate);
            rateDate = result.date;
            if (result.isStale) {
              rateStatus.className = 'currency-rate-status is-warning';
              rateStatus.textContent = `Taux du ${rateDate} utilisé; actualisation momentanément indisponible.`;
            } else {
              rateStatus.className = 'currency-rate-status';
              rateStatus.textContent = '';
            }
            applyDisplayCurrency();
          })
          .catch((error) => {
            console.error('Unable to load the CAD/USD/EUR reference rates.', error);
            document.documentElement.classList.remove('currency-pending');
            rateStatus.className = 'currency-rate-status is-error';
            rateStatus.textContent = 'Taux automatique indisponible : les montants restent dans leur devise d’origine.';
          });
      });
    </script>
  <?php endif; ?>
