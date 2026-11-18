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

$pageStylesheets = ['dashboard.css', 'investment.css', 'account.css'];
$activePage = 'investment';


$favorites = [];

$favorites = $stockDal->getUsersFavorite($username);

?>


<?php if (count($favorites) > 0): ?>

    <head>
        <link rel="stylesheet" href="public/css/account.css">
    </head>
    <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;"></section>
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
        <div class="account-card" style="display:inline-flex;">
            <div class="account-card-header">
                <div>
                    <h3 class="account-name"><?= htmlspecialchars($Name) ?></h3>
                </div>
                <div class="ticker-item">
                    <span class="<?= $change <= 0 ? "trend down" : "trend up" ?>">
                        <?= htmlspecialchars($change . "%") ?>
                    </span>
                </div>

            </div>

            <div class="account-balance-block">
                <span class="account-balance-label">Prix</span>
                <span class="account-balance">
                    <?= renderCurrencyAmount($LastPrice, 'CAD') ?>
                </span>
            </div>



        </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>