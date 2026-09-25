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

<!-- Script Web Component TradingView -->
<script type="module" src="https://widgets.tradingview-widget.com/w/fr/tv-market-overview.js"></script>

<style>
  /* Style pour s'assurer que le widget s'intègre parfaitement dans ton design */
  .market-widget-card {
    background-color: #1e222d; /* Ajuste la couleur si nécessaire pour coller à NovaBank */
    border-radius: 12px;
    padding: 16px;
    margin-top: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
  
  tv-market-overview {
    display: block;
    width: 100%;
    height: 600px;
  }
</style>

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

      <!-- INTÉGRATION DU WIDGET TRADINGVIEW -->
      <section class="market-widget-card">
        <tv-market-overview 
          color-theme="dark" 
          symbol-sectors='[{"sectionName":"Indices","symbols":["FOREXCOM:SPXUSD","FOREXCOM:NSXUSD","FOREXCOM:DJI","FOREXCOM:UKXGBP","NASDAQ:AAPL","OANDA:XAUUSD","NASDAQ:NVDA","NASDAQ:TSLA","NASDAQ:META","NASDAQ:AMZN","NASDAQ:MSFT","NASDAQ:INTC"]},{"sectionName":"Actions","symbols":["NASDAQ:AAPL","NASDAQ:ADBE","NASDAQ:NVDA","NASDAQ:TSLA"]},{"sectionName":"Crypto","symbols":["BITSTAMP:BTCUSD","BITSTAMP:ETHUSD","CRYPTO:XRPUSD"]}]'>
        </tv-market-overview>
      </section>

    </div>
  </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>