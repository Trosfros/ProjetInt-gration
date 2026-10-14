<?php
$projectRoot = dirname(__DIR__, 2);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
}

$pageTitle = 'NovaBank | Marché';
$pageStylesheets = ['dashboard.css', 'market.css'];
$activePage = 'market';
$marketData = $marketData ?? [];


require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main investment-page" aria-label="Page marché">
        <div class="investment-shell">
            <header class="investment-header">
                <div>
                    <p class="investment-kicker"><?= $_SESSION['user']['username']?></p>
                    <h1>Comptes</h1>
                </div>
        </div>
        </header>
    </main>
</div>


<?php require $projectRoot . '/views/layout/footer.php'; ?>