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
$marketData = $marketData ?? [];

require $projectRoot . '/views/layout/header.php';
?>

<link rel="stylesheet" href="public/css/market.css">

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

      <!-- GRAPHIQUE TRADINGVIEW -->
      <section class="market-widget-card">
        <!-- TradingView Widget BEGIN -->
        <div class="tradingview-widget-container" style="height:100%;width:100%">
          <div class="tradingview-widget-container__widget" style="height:calc(100% - 32px);width:100%"></div>
          <div class="tradingview-widget-copyright">
            <a href="https://www.tradingview.com/symbols/NASDAQ-AAPL/" rel="noopener nofollow" target="_blank">
              <span class="blue-text">AAPL stock chart</span>
            </a>
            <span class="trademark">&nbsp;by TradingView</span>
          </div>
          <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
          {
            "allow_symbol_change": true,
            "calendar": false,
            "details": false,
            "hide_side_toolbar": true,
            "hide_top_toolbar": false,
            "hide_legend": false,
            "hide_volume": false,
            "hotlist": false,
            "interval": "D",
            "locale": "fr",
            "save_image": true,
            "style": "1",
            "symbol": "NASDAQ:AAPL",
            "theme": "dark",
            "timezone": "Etc/UTC",
            "backgroundColor": "#0F0F0F",
            "gridColor": "rgba(255, 255, 255, 0.2)",
            "watchlist": [],
            "withdateranges": false,
            "compareSymbols": [],
            "support_host": "https://www.tradingview.com",
            "studies": [],
            "autosize": true
          }
          </script>
        </div>
        <!-- TradingView Widget END -->
      </section>

      <!-- PANNEAU D'ACHAT / VENTE D'ACTIONS -->
      <section class="trade-panel-card">
        <div class="trade-panel-title">Passer un ordre sur le marché</div>
        
        <form action="index.php?action=transaction" method="POST" class="trade-form-grid">
          
          <!-- BARRE DE RECHERCHE / CHOIX DE L'ACTION (A GAUCHE) -->
          <div class="trade-field-group">
            <label for="trade-symbol-input">Rechercher ou sélectionner une action</label>
            <div class="trade-input-wrapper">
              <input 
                type="text" 
                name="stock_symbol" 
                id="trade-symbol-input" 
                class="trade-input" 
                list="stocks-datalist" 
                placeholder="Ex: AAPL, TSLA, NVDA..." 
                value="AAPL"
                autocomplete="off"
                required
              >
              <datalist id="stocks-datalist">
                <?php if (!empty($marketData)): ?>
                  <?php foreach ($marketData as $stock): ?>
                    <option value="<?= htmlspecialchars($stock['symbol']) ?>" data-price="<?= htmlspecialchars($stock['price'] ?? 0) ?>">
                      <?= htmlspecialchars($stock['symbol']) ?> - $<?= htmlspecialchars($stock['price'] ?? 0) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="AAPL" data-price="180.00">Apple Inc.</option>
                  <option value="TSLA" data-price="240.00">Tesla Inc.</option>
                  <option value="NVDA" data-price="120.00">NVIDIA Corp.</option>
                  <option value="MSFT" data-price="410.00">Microsoft Corp.</option>
                  <option value="AMZN" data-price="175.00">Amazon.com Inc.</option>
                <?php endif; ?>
              </datalist>
            </div>
          </div>

          <!-- QUANTITE DE TITRES -->
          <div class="trade-field-group">
            <label for="trade-qty-input">Nombre d'actions</label>
            <input 
              type="number" 
              name="amount" 
              id="trade-qty-input" 
              class="trade-input" 
              step="1" 
              min="1" 
              value="1" 
              required
            >
          </div>

          <!-- TOTAL ESTIME -->
          <div class="trade-field-group">
            <label for="trade-total-display">Total estimé</label>
            <input 
              type="text" 
              id="trade-total-display" 
              class="trade-input trade-input-readonly" 
              value="Calcul..." 
              readonly
            >
          </div>

          <!-- BOUTONS ACHAT ET VENTE -->
          <div class="trade-action-btns">
            <button type="submit" name="type" value="buy" class="btn-trade btn-trade-buy">
              Acheter
            </button>
            <button type="submit" name="type" value="sell" class="btn-trade btn-trade-sell">
              Vendre
            </button>
          </div>

        </form>
      </section>

    </div>
  </main>
</div>

<!-- CALCULATEUR DYNAMIQUE DU TOTAL DE LA TRANSACTION -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', () => {
  const symbolInput = document.getElementById('trade-symbol-input');
  const qtyInput = document.getElementById('trade-qty-input');
  const totalDisplay = document.getElementById('trade-total-display');
  const datalistOptions = document.querySelectorAll('#stocks-datalist option');

  // Prix par défaut pour la démo si l'action n'est pas dans la BDD
  const defaultPrices = {
    'AAPL': 180.00,
    'TSLA': 240.00,
    'NVDA': 120.00,
    'MSFT': 410.00,
    'AMZN': 175.00
  };

  function getSelectedStockPrice(symbol) {
    const cleanSym = symbol.trim().toUpperCase();
    
    // Chercher dans les options du datalist BDD
    for (const opt of datalistOptions) {
      if (opt.value.toUpperCase() === cleanSym) {
        const p = parseFloat(opt.dataset.price);
        if (!isNaN(p) && p > 0) return p;
      }
    }

    // Fallback sur le dictionnaire par défaut
    if (defaultPrices[cleanSym]) {
      return defaultPrices[cleanSym];
    }

    return 150.00; // Prix estimé générique si inconnu
  }

  function updateTotal() {
    const symbol = symbolInput.value;
    const qty = parseFloat(qtyInput.value) || 0;
    const unitPrice = getSelectedStockPrice(symbol);
    const total = qty * unitPrice;

    totalDisplay.value = '$' + total.toFixed(2);
  }

  symbolInput.addEventListener('input', updateTotal);
  qtyInput.addEventListener('input', updateTotal);

  updateTotal();
});
</script>

<?php require $projectRoot . '/views/layout/footer.php'; ?>