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
       
       
  
        <!-- TradingView Widget END -->
      </section>

      <!-- PANNEAU D'ACHAT / VENTE D'ACTIONS -->
      <section class="trade-panel-card">
        <div class="trade-panel-title">Passer un ordre sur le marché</div>
        
        <form action="" method="POST" class="trade-form-grid">
          
          <!-- BARRE DE RECHERCHE / CHOIX DE L'ACTION (A GAUCHE) -->
          <div class="trade-field-group">
            <label for="trade-symbol-input">Rechercher ou sélectionner une action</label>
            <div class="trade-input-wrapper">
             <select
                name="stock_symbol"
                id="trade-symbol-select"
                class="trade-input"
                required
                onchange="updateTab(this.value)">
                <?php if (!empty($marketData)): ?>
                  <?php foreach ($marketData as $stock): ?>
                    <option
                     data-id-stock="<?= htmlspecialchars($stock['id'] ?? '') ?>"
                     value="<?= htmlspecialchars($stock['symbol']) ?>" 
                     data-price="<?= htmlspecialchars($stock['price'] ?? 0) ?>"
                      <?= $stock['symbol'] === 'AAPL' ? 'selected' : '' ?>>
                      <?= htmlspecialchars($stock['symbol']) ?> - $<?= htmlspecialchars($stock['price'] ?? 0) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="AAPL" data-price="180.00" selected>AAPL - Apple Inc.</option>
                  <option value="TSLA" data-price="240.00">TSLA - Tesla Inc.</option>
                  <option value="NVDA" data-price="120.00">NVDA - NVIDIA Corp.</option>
                  <option value="MSFT" data-price="410.00">MSFT - Microsoft Corp.</option>
                  <option value="AMZN" data-price="175.00">AMZN - Amazon.com Inc.</option>
                <?php endif; ?>
              </select>
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
              onchange="updateTotal()"
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
            <button type="submit" name="type" value="buy" class="btn-trade btn-trade-buy" onclick="doPayment('buy')">
              Acheter
            </button>
            <button type="submit" name="type" value="sell" class="btn-trade btn-trade-sell" onclick="doPayment('sell')">
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
                });
  function getSelectedStockPrice(symbol) {
    const cleanSym = symbol.trim().toUpperCase();
    const selectOptions = document.querySelectorAll('#trade-symbol-select option');
 
    for (const opt of selectOptions) {
      if (opt.value.toUpperCase() === cleanSym) {
        const p = parseFloat(opt.dataset.price);
        if (!isNaN(p) && p > 0) return p;
      }
    }
 
    if (defaultPrices[cleanSym]) {
      return defaultPrices[cleanSym];
    }
 
    return 150.00; 
  }

  
 function updateTab(symbol) {
   
    const tabsec = document.querySelector(".market-widget-card");
 
    if (!tabsec) {
        console.error("market-widget-card element not found");
        return;
    }
 

    tabsec.innerHTML = "";
 
    const container = document.createElement("div");
    container.className = "tradingview-widget-container";
    container.style.height = "100%";
    container.style.width = "100%";
 
    const widgetDiv = document.createElement("div");
    widgetDiv.className = "tradingview-widget-container__widget";
    widgetDiv.style.height = "calc(100% - 32px)";
    widgetDiv.style.width = "100%";
 
    const copyrightDiv = document.createElement("div");
    copyrightDiv.className = "tradingview-widget-copyright";
    copyrightDiv.innerHTML = `
        <a href="https://www.tradingview.com/symbols/NASDAQ-${symbol}/" rel="noopener nofollow" target="_blank">
            <span class="blue-text">${symbol} stock chart</span>
        </a>
        <span class="trademark">&nbsp;by TradingView</span>
    `;
 
    
    const script = document.createElement("script");
    script.type = "text/javascript";
    script.src = "https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js";
    script.async = true;
    script.textContent = JSON.stringify({
        allow_symbol_change: false,
        calendar: false,
        details: false,
        hide_side_toolbar: true,
        hide_top_toolbar: false,
        hide_legend: false,
        hide_volume: false,
        hotlist: false,
        interval: "D",
        locale: "fr",
        save_image: true,
        style: "1",
        symbol: symbol, 
        theme: "dark",
        timezone: "Etc/UTC",
        backgroundColor: "#0F0F0F",
        gridColor: "rgba(255, 255, 255, 0.2)",
        watchlist: [],
        withdateranges: false,
        compareSymbols: [],
        support_host: "https://www.tradingview.com",
        studies: [],
        autosize: true,
    });
 
    container.appendChild(widgetDiv);
    container.appendChild(copyrightDiv);
    container.appendChild(script);
 
    tabsec.appendChild(container);

  

 
  updateTotal();
}
 function updateTotal() {
    const symbolSelect = document.getElementById('trade-symbol-select');
    const qtyInput = document.getElementById('trade-qty-input');
    const totalDisplay = document.getElementById('trade-total-display');
 
    const symbol = symbolSelect.value;
    const qty = parseFloat(qtyInput.value) || 0;
    const unitPrice = getSelectedStockPrice(symbol);
    const total = qty * unitPrice;
 
    totalDisplay.value = '$' + total.toFixed(2);
  }
   async function doPayment(side) {
    const symbolSelect = document.getElementById('trade-symbol-select');
    const qtyInput = document.getElementById('trade-qty-input');
 
    const selectedOption = symbolSelect.options[symbolSelect.selectedIndex];
    const idStock = parseInt(selectedOption.dataset.idStock, 10);
    const amount = parseFloat(qtyInput.value);
 
    if (!idStock) {
      alert("Cette action n'a pas d'identifiant valide en base de données.");
      
      return;
    }
    if (!amount || amount <= 0) {
      alert('Entrez une quantité valide.');
      return;
    }
 
    try {
      const res = await fetch('\ProjetInt-gration\src\DAL\placeOrder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ side, idStock, amount, unit: 'shares' }),
      });
 
      const data = await res.json();
      alert(data.message);
 
      if (data.success) {
        updateTotal();
      }
    } catch (err) {
      alert('Erreur réseau lors de la commande.'+ err);
    }
  }
  updateTotal();
  updateTab("NASDAQ:AAPL");
</script>

<?php require $projectRoot . '/views/layout/footer.php'; ?>