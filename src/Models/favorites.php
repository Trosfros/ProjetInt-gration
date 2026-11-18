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
require_once $projectRoot . '/src/DAL/StockDAL.php';

$username = $_SESSION['user']['username'];

$pageStylesheets = ['dashboard.css', 'investment.css', 'account.css'];
$activePage = 'investment';

$favorites = [];
try {
    $stockDal = $stockDal ?? new StockDAL();
    $result = $stockDal->getUsersFavorite($username);
 
    $favorites = isset($result['success']) ? [] : $result;
} catch (Throwable $e) {
    error_log('Favorites error: ' . $e->getMessage());
}
?>

<?php if (count($favorites) > 0): ?>
    <link rel="stylesheet" href="public/css/account.css">

    <header class="investment-header">
        <div>
            <h1>Vos favoris</h1>
        </div>
    </header>

    <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($favorites as $fav): ?>
            <?php
            $name = (string) ($fav['Name'] ?? '');
            $lastPrice = (float) ($fav['LastPrice'] ?? 0);
        
            $change = $fav['Change'] ?? 0.0;

            $trendClass = $change < 0 ? 'trend down' : 'trend up';
            
            ?>
            <div class="account-card">
                <div class="account-card-header">
                    <div>
                        <h3 class="account-name"><?= htmlspecialchars($name) ?></h3>
                    </div>
                    <div class="ticker-item">
                        <span class="<?= $trendClass ?>"><?= htmlspecialchars($change . "%") ?></span>
                    </div>
                </div>

                <div class="account-balance-block">
                    <span class="account-balance-label">Prix</span>
                    <span class="account-balance">
                        <?= renderCurrencyAmount($lastPrice, 'USD') ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>