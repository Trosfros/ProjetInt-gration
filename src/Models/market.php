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
$activePage = 'market';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
  <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

  <main class="dashboard-main investment-page" aria-label="Page marché">
    <div class="investment-shell">
      <header class="investment-header">
        <div>
          <p class="investment-kicker">Bourse</p>
          <h1>Marché</h1>
        </div>
        <button class="investment-button" type="button">Filtrer</button>
      </header>

      <div class="investment-grid">
        <section class="investment-card investment-summary">
          <p class="card-label">Indice principal</p>
          <h2>0</h2>
          <span class="positive-change">0% aujourd’hui</span>
        </section>

        <section class="investment-card investment-summary muted">
          <p class="card-label">Volume</p>
          <h2>0$</h2>
          <span class="neutral-text">Échanges du jour</span>
        </section>
      </div>

    </div>
  </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
