<?php
$projectRoot = dirname(__DIR__, 2);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
}

require_once $projectRoot . '/vendor/autoload.php';
require_once $projectRoot . '/configs/database.php';

$username = $_SESSION['user']['username'];
$pageTitle = 'NovaBank | Favoris de' . $_SESSION['user']['username'];
$pageStylesheets = ['dashboard.css', 'investment.css'];
$activePage = 'investment';

require $projectRoot . '/views/layout/header.php';

$favorites = [];

$favorites = $stockDal->getUsersFavorite($username);

?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>


    <header class="investment-header">
        <div>

            <h1> Vos favoris</h1>
        </div>
    </header>
    <?php foreach ($favorites as $fav): ?>
        <?php
        $Name = $fav['Name'] ?? '';
        $LastPrice = $fav['LastPrice'] ?? '';
        $change = $fav['Change'] ?? '' . "%";
        ?>
        <div class="account-card">
            <div class="account-card-header">
                <div>
                    <h3 class="account-name"><?= htmlspecialchars($account['AccountName']) ?></h3>
                </div>
                <span class="account-status account-status-<?= htmlspecialchars($change) ?>">
                    <?= htmlspecialchars(ucfirst($status)) ?>
                </span>
            </div>

            <div class="account-balance-block">
                <span class="account-balance-label">Solde</span>
                <span class="account-balance <?= $change < 0 ? 'is-negative' : '' ?>">
                    <?= number_format($LastPrice, 2) ?>
                </span>
            </div>

            <div class="account-meta">
                <span class="account-number">Compte <?= htmlspecialchars($maskedNumber) ?></span>
            </div>

        </div>

    <?php endforeach; ?>
</div>