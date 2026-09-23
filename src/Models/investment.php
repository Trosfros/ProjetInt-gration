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
if (!function_exists('getPDO')) {
    require_once $projectRoot . '/configs/database.php';
}

$pageTitle = 'NovaBank | Investissements';
$activePage = 'investment';
require $projectRoot . '/views/layout/header.php';
?>

<?php if (!empty($_SESSION['user'])): ?>
  <div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main investment-page" aria-label="Page investissement">
      <div class="investment-shell">
        <header class="investment-header">
          <div>
            <p class="investment-kicker">Portefeuille</p>
            <h1>Investissements</h1>
          </div>
          <button class="investment-button" type="button">Nouvelle analyse</button>
        </header>

        <div class="investment-grid">
          <section class="investment-card investment-summary">
            <p class="card-label">Valeur totale</p>
            <h2>0$</h2>
            <span class="positive-change">0% ce mois</span>
          </section>

          <section class="investment-card investment-summary muted">
            <p class="card-label">Disponibles</p>
            <h2>0$</h2>
            <span class="neutral-text">Prêt à investir</span>
          </section>
        </div>

        <section class="investment-panel">
          <div class="panel-header">
            <h3>Portefeuille</h3>
            <span>0 actifs</span>
          </div>
          <div class="empty-state">
            
          </div>
        </section>
      </div>
    </main>
  </div>
<?php else: ?>
  <main class="main-content"></main>
<?php endif; ?>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
