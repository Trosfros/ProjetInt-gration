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


$favoriteIds = [];
try {
  $stockDal = new StockDAL();


  $stockIds = $stockDal->getStockIdsBySymbols(array_column($marketData, 'symbol'));
  foreach ($marketData as &$stock) {
    $stock['id'] = $stockIds[$stock['symbol']] ?? null;
  }
  unset($stock);

  $sessionUser = $_SESSION['user'];
  $alias = is_array($sessionUser)
    ? ($sessionUser['username'] ?? $sessionUser['Alias'] ?? $sessionUser['alias'] ?? '')
    : (string) $sessionUser;
  if ($alias !== '') {
    $favoriteIds = $stockDal->getUsersFavoriteIds($alias);
  }
} catch (Throwable $e) {
  error_log('Market page DB error: ' . $e->getMessage());
  foreach ($marketData as &$stock) {
    $stock['id'] = null;
  }
  unset($stock);
}
?>


<script type="module" src="https://widgets.tradingview-widget.com/w/en/tv-forex-table.js"></script>
<style>
  .trade-symbol-row {
    display: flex;
    gap: 8px;
    align-items: stretch;
  }

  .trade-symbol-row .trade-input {
    flex: 1;
  }

  .btn-favorite {
    min-width: 44px;
    max-width: 50px;
    border-right: -30px;
    border-radius: 8px;
    cursor: pointer;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: transparent;
    color: #bbb;
    font-size: 1.3rem;
    line-height: 1;
    transition: color .15s, border-color .15s;
  }

  .btn-favorite:hover:not(:disabled) {
    color: #f5c518;
    border-color: #f5c518;
  }

  .btn-favorite.is-favorite {
    color: #f5c518;
    border-color: #f5c518;
  }

  .btn-favorite:disabled {
    opacity: .4;
    cursor: not-allowed;
  }
</style>
<div class="dashboard-layout market-layout">
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


      <section class="market-overview-card"
        style="margin-bottom: 24px; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08); background-color: #0F0F0F; padding: 12px;">
        <tv-forex-table theme="dark"></tv-forex-table>
      </section>


      <section class="market-widget-card">

      </section>


      <section class="trade-panel-card">
        <div class="trade-panel-header"
          style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
          <div class="trade-panel-title">Passer un ordre sur le marché</div>
          <button type="button" id="favorite-toggle-btn" class="btn-favorite"
            style="flex: 0 0 auto; width: 44px; height: 36px; position: static; margin: 0;" aria-pressed="false"
            aria-label=" aux favoris" title="Ajouter aux favoris" onclick="toggleFavorite()">☆</button>
        </div>



        <div class="trade-form-grid">

          <div class="trade-field-group">
            <label for="trade-symbol-select">Rechercher ou sélectionner une action</label>

            <div class="trade-input-wrapper">
              <select name="stock_symbol" id="trade-symbol-select" class="trade-input" required
                autocomplete="off"
                onchange="updateTab(this.value); updateFavoriteButton();">
                <?php if (!empty($marketData)): ?>
                  <?php foreach ($marketData as $stock):
                    $isFavStock = !empty($stock['id']) && in_array($stock['id'], $favoriteIds, false);
                  ?>
                    <option
                      data-id-stock="<?= htmlspecialchars((string) ($stock['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-favorite="<?= $isFavStock ? '1' : '0' ?>"
                      value="<?= htmlspecialchars((string) $stock['symbol'], ENT_QUOTES, 'UTF-8') ?>"
                      data-price="<?= htmlspecialchars((string) ($stock['price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                      <?= ($stock['symbol'] ?? '') === 'AAPL' ? 'selected' : '' ?>>
                      <?= htmlspecialchars((string) $stock['symbol'], ENT_QUOTES, 'UTF-8') ?> - $<?= htmlspecialchars((string) ($stock['price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="AAPL" data-price="180.00" data-favorite="0" selected>AAPL - Apple Inc.</option>
                  <option value="TSLA" data-price="240.00" data-favorite="0">TSLA - Tesla Inc.</option>
                  <option value="NVDA" data-price="120.00" data-favorite="0">NVDA - NVIDIA Corp.</option>
                  <option value="MSFT" data-price="410.00" data-favorite="0">MSFT - Microsoft Corp.</option>
                  <option value="AMZN" data-price="175.00" data-favorite="0">AMZN - Amazon.com Inc.</option>
                <?php endif; ?>
              </select>

            </div>
          </div>


          <div class="trade-field-group">
            <label for="trade-qty-input">Nombre d'actions</label>
            <input type="number" name="amount" id="trade-qty-input" class="trade-input" step="1" min="1" value="1"
              onchange="updateTotal()" required>
          </div>


          <div class="trade-field-group">
            <label for="trade-total-display">Total estimé</label>
            <input type="text" id="trade-total-display" class="trade-input trade-input-readonly" value="Calcul..."
              readonly>
          </div>


          <div class="trade-action-btns">
            <button type="submit" name="type" value="buy" class="btn-trade btn-trade-buy" onclick="doPayment('buy')">
              Acheter
            </button>
            <button type="submit" name="type" value="sell" class="btn-trade btn-trade-sell" onclick="doPayment('sell')">
              Vendre
            </button>
          </div>
          <p id="trade-feedback" class="trade-feedback" hidden></p>
        </div>

      </section>

    </div>
  </main>
</div>

<!-- FENÊTRE MODALE DE CONFIRMATION POUR ORDRE > 1000$ -->
<dialog class="sell-confirm-dialog" id="trade-confirm-dialog" aria-labelledby="trade-dialog-title">
  <form method="dialog" class="sell-dialog-close-form">
    <button class="sell-dialog-close" type="submit" aria-label="Fermer la confirmation">×</button>
  </form>
  <div class="sell-dialog-icon" aria-hidden="true">⚠️</div>
  <p class="investment-kicker">Confirmation d'ordre important</p>
  <h2 id="trade-dialog-title"><span id="trade-dialog-action"></span> de <span id="trade-dialog-quantity"></span> <span id="trade-dialog-stock"></span></h2>
  <p class="sell-dialog-description">Le total estimé de cet ordre dépasse 1 000,00 $ (<span id="trade-dialog-total"></span>). Veuillez confirmer pour exécuter la transaction.</p>
  <div class="sell-dialog-actions">
    <button type="button" class="sell-dialog-cancel" id="trade-dialog-cancel">Annuler</button>
    <button type="button" class="sell-dialog-confirm" id="trade-dialog-confirm">Confirmer l'ordre</button>
  </div>
</dialog>

<!-- CALCULATEUR DYNAMIQUE ET SCRIPTS -->
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    const defaultPrices = {
      'AAPL': 180.00,
      'TSLA': 240.00,
      'NVDA': 120.00,
      'MSFT': 410.00,
      'AMZN': 175.00
    };

    window.getSelectedStockPrice = function (symbol) {
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
    };

    updateTotal();
    updateTab("NASDAQ:AAPL");
    updateFavoriteButton();
  });

  // Retour arrière / avant : la page peut être restaurée depuis le cache du navigateur sans recharger
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) location.reload();
  });

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
      locale: document.documentElement.dataset.language === 'en' ? "en" : "fr",
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

  function showTradeFeedback(message, isSuccess) {
    const feedback = document.getElementById('trade-feedback');
    if (!feedback) return;

    if (!message) {
      feedback.hidden = true;
      feedback.textContent = '';
      feedback.classList.remove('is-success', 'is-error');
      return;
    }

    feedback.textContent = message;
    feedback.classList.remove('is-success', 'is-error');
    feedback.classList.add(isSuccess ? 'is-success' : 'is-error');
    feedback.hidden = false;
  }

  function updateTotal() {
    const symbolSelect = document.getElementById('trade-symbol-select');
    const qtyInput = document.getElementById('trade-qty-input');
    const totalDisplay = document.getElementById('trade-total-display');

    if (!symbolSelect || !qtyInput || !totalDisplay) return;

    const symbol = symbolSelect.value;
    const qty = parseFloat(qtyInput.value) || 0;
    const unitPrice = window.getSelectedStockPrice ? window.getSelectedStockPrice(symbol) : 150.00;
    const total = qty * unitPrice;

    totalDisplay.value = '$' + total.toFixed(2);
  }

  async function toggleFavorite() {
    const select = document.getElementById('trade-symbol-select');
    const btn = document.getElementById('favorite-toggle-btn');
    const opt = select.options[select.selectedIndex];
    const idStock = parseInt(opt?.dataset.idStock, 10);

    if (!idStock) {
      showTradeFeedback("Cette action n'a pas d'identifiant valide en base de données.", false);
      return;
    }

    btn.disabled = true;
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
      const headers = { 'Content-Type': 'application/json' };
      if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
      }

      const res = await fetch('src/DAL/toggleFavorite.php', {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({ idStock: idStock }),
      });
      const data = await res.json();
      if (data.success) {
        opt.dataset.favorite = data.isFavorite ? '1' : '0';
      } else {
        showTradeFeedback(data.message, false);
      }
    } catch (err) {
      showTradeFeedback('Erreur réseau lors de la mise à jour des favoris.', false);
    } finally {
      updateFavoriteButton();
    }
  }

  function updateFavoriteButton() {
    const select = document.getElementById('trade-symbol-select');
    const btn = document.getElementById('favorite-toggle-btn');
    if (!select || !btn) return;

    const opt = select.options[select.selectedIndex];
    const hasId = !!(opt && opt.dataset.idStock);
    const isFav = !!(opt && opt.dataset.favorite === '1');
    const label = isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';

    btn.disabled = !hasId;
    btn.classList.toggle('is-favorite', isFav);
    btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
    btn.setAttribute('aria-label', label);
    btn.title = hasId ? label : "Cette action n'est pas en base de données";
    btn.textContent = isFav ? '★' : '☆';
  }

  const tradeDialog = document.getElementById('trade-confirm-dialog');
  const tradeConfirmButton = document.getElementById('trade-dialog-confirm');
  let pendingTrade = null;

  async function executeOrder(side, idStock, amount) {
    showTradeFeedback(null);
    try {
      const res = await fetch('src/DAL/placeOrder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ side: side, idStock: idStock, amount: amount, unit: 'shares' }),
      });

      const data = await res.json();
      if (data.success) {
        updateTotal();
      }
      showTradeFeedback(data['message'], data.success);
    } catch (err) {
      alert('Erreur réseau lors de la commande : ' + err);
    }
  }

  async function doPayment(side) {
    const symbolSelect = document.getElementById('trade-symbol-select');
    const qtyInput = document.getElementById('trade-qty-input');

    const selectedOption = symbolSelect.options[symbolSelect.selectedIndex];
    const idStock = parseInt(selectedOption.dataset.idStock, 10);
    const amount = parseFloat(qtyInput.value);
    const symbol = selectedOption.value;

    if (!idStock) {
      alert("Cette action n'a pas d'identifiant valide en base de données.");
      return;
    }
    if (!amount || amount <= 0) {
      alert('Entrez une quantité valide.');
      return;
    }

    const unitPrice = window.getSelectedStockPrice ? window.getSelectedStockPrice(symbol) : 150.00;
    const total = amount * unitPrice;

    // Si le total dépasse 1000$, on interrompt et on ouvre la modale de confirmation
    if (total > 1000) {
      pendingTrade = { side, idStock, amount };
      document.getElementById('trade-dialog-action').textContent = side === 'buy' ? 'Achat' : 'Vente';
      document.getElementById('trade-dialog-quantity').textContent = amount.toLocaleString('fr-CA');
      document.getElementById('trade-dialog-stock').textContent = symbol;
      document.getElementById('trade-dialog-total').textContent = total.toLocaleString('fr-CA', { style: 'currency', currency: 'USD' });

      tradeDialog.showModal();
      return;
    }

    // Sinon, on exécute directement
    await executeOrder(side, idStock, amount);
  }

  document.getElementById('trade-dialog-cancel').addEventListener('click', () => {
    tradeDialog.close();
    pendingTrade = null;
  });

  tradeConfirmButton.addEventListener('click', async () => {
    if (!pendingTrade) return;
    const { side, idStock, amount } = pendingTrade;
    tradeDialog.close();
    pendingTrade = null;
    await executeOrder(side, idStock, amount);
  });
</script>

<?php require $projectRoot . '/views/layout/footer.php'; ?>