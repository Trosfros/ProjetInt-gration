<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle =$pageTitle ?? 'NovaBank | Banque virtuelle & Simulateur de Bourse';
$isLoggedIn = !empty($_SESSION['user']);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__,2) .'/src/DAL/stockDAL.php';
require_once dirname(__DIR__,2) .'/configs/database.php';

use Dotenv\Dotenv;
use Composer\CaBundle\CaBundle;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));$dotenv->load();

$apiKey = $_ENV['FinnhubKey'] ?? $_ENV['API_KEY'] ?? '';

// --- CONNEXION (bdnovabank) ---

$marketData = [];

// --- SYSTÈME DE CACHE FINNHUB ---
$cacheFile = dirname(__DIR__, 2) . '/storage/market_cache.json';$cacheLifetime = 60; // 60 secondes

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) <$cacheLifetime)) {
    $cachedContent = file_get_contents($cacheFile);
    $marketData = json_decode($cachedContent, true) ?? [];
} else {
    $caBundle = CaBundle::getSystemCaRootBundlePath();

    foreach ($symbols as $sym) {$url = 'https://finnhub.io/api/v1/quote?' . http_build_query([
            'symbol' => $sym,
            'token'  => $apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CAINFO,$caBundle);

        $res = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $quote = json_decode($res, true);

        if ($httpStatus === 200 && $quote && isset($quote['c'], $quote['dp'])) {$stockId = null;
            $priceFormatted = number_format($quote['c'], 2, '.', '');
            $changeFormatted = round($quote['dp'], 2);

            $stockDal->AddStock($sym, $priceFormatted, $changeFormatted);
            }

            $marketData[] = [
                'id'     => $stockId, 
                'symbol' => $sym,
                'price'  => $priceFormatted,
                'change' => $changeFormatted
            ];
        }
    }

    // Sauvegarde dans le cache local
    if (!empty($marketData)) {
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheFile, json_encode($marketData));
    } else if (file_exists($cacheFile)) {
        $marketData = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/investment.css">
  <link rel="stylesheet" href="public/css/marcher.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
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
        <a href="index.php#banking">Gestion Bancaire</a>
        <a href="index.php#leaderboard">Classement</a>
        <a href="index.php#features">Fonctionnalités</a>
      <?php endif; ?>
    </nav>

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