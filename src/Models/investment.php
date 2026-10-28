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

$pageTitle = 'NovaBank | Investissements';
$pageStylesheets = ['dashboard.css', 'investment.css'];
$activePage = 'investment';

require $projectRoot . '/views/layout/header.php';

$portfolio = null;
$portfolioError = null;
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT u.Balance, u.RealizedPnl, si.idStock, s.Name,
                si.Shares, s.LastPrice, si.Investement
         FROM `User` u
         LEFT JOIN StockInvest si ON si.IdUser = u.idUser
         LEFT JOIN Stock s ON s.idStock = si.idStock
         WHERE u.Alias = :alias
         ORDER BY s.Name'
    );
    $stmt->execute(['alias' => (string) $_SESSION['user']['username']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$rows) {
        throw new RuntimeException('No user found for the current session.');
    }

    $portfolio = [
        'balance' => (float) $rows[0]['Balance'],
        'realizedPnl' => (float) $rows[0]['RealizedPnl'],
        'positions' => [],
    ];
    foreach ($rows as $row) {
        if ($row['idStock'] === null) {
            continue;
        }

        $portfolio['positions'][] = [
            'idStock' => (int) $row['idStock'],
            'name' => (string) $row['Name'],
            'shares' => (float) $row['Shares'],
            'currentPrice' => $row['LastPrice'] === null ? null : (float) $row['LastPrice'],
            'invested' => (float) $row['Investement'],
        ];
    }
} catch (Throwable $e) {
    error_log('investment.php failed to load portfolio: ' . $e->getMessage());
    $portfolioError = 'Impossible de charger votre portefeuille pour le moment.';
}

$totalValue = 0.0;
$totalShares = 0.0;
$totalPnl = 0.0;
$pricesAvailable = true;

if ($portfolio && !empty($portfolio['positions'])) {
    foreach ($portfolio['positions'] as &$position) {
        $totalShares += $position['shares'];
        if ($position['currentPrice'] === null || $position['currentPrice'] <= 0) {
            $position['marketValue'] = null;
            $position['pnl'] = null;
            $position['pnlPercent'] = null;
            $pricesAvailable = false;
            continue;
        }

        $position['marketValue'] = $position['shares'] * $position['currentPrice'];
        $position['pnl'] = $position['marketValue'] - $position['invested'];
        $position['pnlPercent'] = $position['invested'] > 0
            ? ($position['pnl'] / $position['invested']) * 100
            : 0.0;
        $totalValue += $position['marketValue'];
        $totalPnl += $position['pnl'];
    }
    unset($position);
}

$formatMoney = static function (float $amount): string {
    return ($amount < 0 ? '-$' : '$') . number_format(abs($amount), 2, '.', ',');
};
$formatShares = static function (float $shares): string {
    $formatted = rtrim(rtrim(number_format($shares, 8, '.', ','), '0'), '.');
    return $formatted === '' ? '0' : $formatted;
};

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

        <!-- CARTES DE RÉSUMÉ HAUT DE PAGE -->
        <div class="summary-cards-grid">
          <div class="summary-card">
            <div class="summary-card-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
              VALEUR TOTALE
            </div>
            <div class="summary-card-value">
              <?= $portfolio === null || !$pricesAvailable ? '—' : htmlspecialchars($formatMoney($totalValue), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-card-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
              ACTIONS DÉTENUES
            </div>
            <div class="summary-card-value">
              <?= $portfolio === null ? '—' : htmlspecialchars($formatShares($totalShares), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-card-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
              GAIN / PERTE LATENT(E)
            </div>
            <div class="summary-card-value <?= $totalPnl >= 0 ? 'text-positive' : 'text-negative' ?>">
              <?= $portfolio === null || !$pricesAvailable ? '—' : ($totalPnl > 0 ? '+' : '') . htmlspecialchars($formatMoney($totalPnl), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-card-label">SOLDE RESTANT</div>
            <div class="summary-card-value">
              <?= $portfolio === null ? '—' : htmlspecialchars($formatMoney($portfolio['balance']), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
        </div>

        <div id="investment-feedback" class="investment-feedback" role="status" aria-live="polite" hidden></div>

        <!-- GRILLE DES POSITIONS -->
        <?php if ($portfolioError !== null): ?>
          <div class="empty-state">
            <p><?= htmlspecialchars($portfolioError, ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php elseif ($portfolio && !empty($portfolio['positions'])): ?>
          <div class="portfolio-filter">
            <label class="portfolio-filter-label" for="portfolio-stock-filter">Rechercher une action</label>
            <div class="portfolio-filter-control">
              <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                <path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"></path>
              </svg>
              <input
                id="portfolio-stock-filter"
                class="portfolio-filter-input"
                type="search"
                placeholder="Nom ou symbole"
                autocomplete="off"
                aria-controls="portfolio-stocks-grid"
              >
            </div>
            <p id="portfolio-filter-status" class="portfolio-filter-status" aria-live="polite"></p>
          </div>

          <div class="stocks-grid" id="portfolio-stocks-grid">
            <?php foreach ($portfolio['positions'] as $position):
              $symbol = htmlspecialchars($position['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
              $name = $symbol;
              $qty = $position['shares'];
              $hasPrice = $position['currentPrice'] !== null && $position['currentPrice'] > 0;
              $currentPrice = $position['currentPrice'] ?? 0.0;
              $invested = $position['invested'];
              $totalVal = $position['marketValue'];
              $pnl = $position['pnl'];
              $pnlPercent = $position['pnlPercent'];
              $isPositive = $hasPrice && $pnl >= 0;
            ?>
              <div class="stock-card <?= $hasPrice ? ($isPositive ? 'card-positive' : 'card-negative') : '' ?>" data-stock-search="<?= $symbol ?>">
                <div class="stock-card-header">
                  <div>
                    <h3 class="stock-name"><?= $name ?></h3>
                    <span class="stock-symbol-badge"><?= $symbol ?></span>
                  </div>
                  <?php if ($hasPrice): ?>
                    <div class="stock-badge <?= $isPositive ? 'badge-positive' : 'badge-negative' ?>">
                      <?= $isPositive ? '↗' : '↘' ?> <?= ($pnl > 0 ? '+' : '') . number_format($pnlPercent, 1) ?> %
                    </div>
                  <?php else: ?>
                    <div class="stock-badge">Cours indisponible</div>
                  <?php endif; ?>
                </div>

                <div class="stock-data-grid">
                  <div class="data-group">
                    <span class="data-label">QUANTITÉ</span>
                    <span class="data-value-large"><?= htmlspecialchars($formatShares($qty), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <div class="data-group align-right">
                    <span class="data-label">PRIX ACTUEL</span>
                    <span class="data-value-medium"><?= $hasPrice ? htmlspecialchars($formatMoney($currentPrice), ENT_QUOTES, 'UTF-8') : '—' ?></span>
                  </div>
                  <div class="data-group">
                    <span class="data-label">VALEUR TOTALE</span>
                    <span class="data-value-bold"><?= $hasPrice ? htmlspecialchars($formatMoney($totalVal), ENT_QUOTES, 'UTF-8') : '—' ?></span>
                  </div>
                  <div class="data-group align-right">
                    <span class="data-label">COÛT D’ACHAT</span>
                    <span class="data-value-medium"><?= htmlspecialchars($formatMoney($invested), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <div class="data-group">
                    <span class="data-label">GAIN / PERTE LATENT(E)</span>
                    <span class="data-value-bold <?= $hasPrice ? ($isPositive ? 'text-positive' : 'text-negative') : '' ?>">
                      <?= $hasPrice ? ($pnl > 0 ? '+' : '') . htmlspecialchars($formatMoney($pnl), ENT_QUOTES, 'UTF-8') : '—' ?>
                    </span>
                  </div>
                </div>

                <div class="sell-controls">
                  <label class="data-label" for="sell-quantity-<?= (int) $position['idStock'] ?>">QUANTITÉ À VENDRE</label>
                  <input
                    id="sell-quantity-<?= (int) $position['idStock'] ?>"
                    class="sell-quantity"
                    type="number"
                    min="0.00000001"
                    max="<?= htmlspecialchars(number_format($qty, 8, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                    step="0.00000001"
                    value="<?= htmlspecialchars(number_format($qty, 8, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                    inputmode="decimal"
                    aria-label="Quantité de <?= $symbol ?> à vendre"
                    <?= $qty <= 0 ? 'disabled' : '' ?>
                  >
                  <button
                    type="button"
                    class="btn-sell-card"
                    data-id-stock="<?= (int) $position['idStock'] ?>"
                    data-stock-name="<?= $symbol ?>"
                    data-current-price="<?= $hasPrice ? htmlspecialchars((string) $currentPrice, ENT_QUOTES, 'UTF-8') : '' ?>"
                    <?= $qty <= 0 ? 'disabled' : '' ?>
                  >Vendre la quantité</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div id="portfolio-filter-empty" class="empty-state portfolio-filter-empty" hidden>
            <p>Aucune action ne correspond à votre recherche.</p>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <p>Vous n’avez pas encore d’action dans votre portefeuille.</p>
            <a href="index.php?action=marketPage" class="btn-discover">Découvrir le marché.</a>
          </div>
        <?php endif; ?>

      </div>
    </main>
  </div>
<?php else: ?>
  <main class="main-content"></main>
<?php endif; ?>

<dialog class="sell-confirm-dialog" id="sell-confirm-dialog" aria-labelledby="sell-dialog-title">
  <form method="dialog" class="sell-dialog-close-form">
    <button class="sell-dialog-close" type="submit" aria-label="Fermer la confirmation">×</button>
  </form>
  <div class="sell-dialog-icon" aria-hidden="true">↗</div>
  <p class="investment-kicker">Confirmation de vente</p>
  <h2 id="sell-dialog-title">Vendre <span id="sell-dialog-quantity"></span> <span id="sell-dialog-stock"></span> ?</h2>
  <p class="sell-dialog-description">Cette quantité sera retirée de votre portefeuille et le solde sera mis à jour après la vente.</p>
  <p class="sell-dialog-proceeds" id="sell-dialog-proceeds" hidden></p>
  <div class="sell-dialog-actions">
    <button type="button" class="sell-dialog-cancel" id="sell-dialog-cancel">Annuler</button>
    <button type="button" class="sell-dialog-confirm" id="sell-dialog-confirm">Confirmer la vente</button>
  </div>
</dialog>



<script>
const sellDialog = document.getElementById('sell-confirm-dialog');
const sellConfirmButton = document.getElementById('sell-dialog-confirm');
const sellFeedback = document.getElementById('investment-feedback');
let pendingSale = null;

const portfolioFilter = document.getElementById('portfolio-stock-filter');
const portfolioFilterStatus = document.getElementById('portfolio-filter-status');
const portfolioFilterEmpty = document.getElementById('portfolio-filter-empty');
const portfolioStockCards = [...document.querySelectorAll('[data-stock-search]')];

const normalizeSearchText = (value) => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLocaleLowerCase();

const updatePortfolioFilter = () => {
  const search = normalizeSearchText(portfolioFilter?.value || '');
  let visibleCount = 0;

  portfolioStockCards.forEach((card) => {
    const matches = normalizeSearchText(card.dataset.stockSearch || '').includes(search);
    card.hidden = !matches;
    if (matches) visibleCount += 1;
  });

  if (portfolioFilterStatus) {
    portfolioFilterStatus.textContent = search
      ? `${visibleCount} action${visibleCount === 1 ? '' : 's'} trouvée${visibleCount === 1 ? '' : 's'}`
      : `${visibleCount} action${visibleCount === 1 ? '' : 's'} dans votre portefeuille`;
  }
  if (portfolioFilterEmpty) {
    portfolioFilterEmpty.hidden = visibleCount > 0;
  }
};

portfolioFilter?.addEventListener('input', updatePortfolioFilter);
updatePortfolioFilter();

document.querySelectorAll('.btn-sell-card').forEach((button) => {
  button.addEventListener('click', () => {
    const card = button.closest('.stock-card');
    const quantityInput = card?.querySelector('.sell-quantity');
    const quantity = quantityInput?.valueAsNumber;
    const maxQuantity = Number(quantityInput?.max);

    if (!quantityInput || !quantityInput.reportValidity() ||
        !Number.isFinite(quantity) || quantity <= 0 || quantity > maxQuantity) {
      return;
    }

    pendingSale = { button, quantityInput, quantity };
    document.getElementById('sell-dialog-quantity').textContent = quantity.toLocaleString('fr-CA', {
      maximumFractionDigits: 8
    });
    document.getElementById('sell-dialog-stock').textContent = button.dataset.stockName;

    const proceedsElement = document.getElementById('sell-dialog-proceeds');
    const currentPrice = Number(button.dataset.currentPrice);
    if (Number.isFinite(currentPrice) && currentPrice > 0) {
      proceedsElement.textContent = `Montant estimé : ${(quantity * currentPrice).toLocaleString('fr-CA', {
        style: 'currency',
        currency: 'USD'
      })}`;
      proceedsElement.hidden = false;
    } else {
      proceedsElement.hidden = true;
    }

    sellFeedback.hidden = true;
    sellDialog.showModal();
    document.getElementById('sell-dialog-cancel').focus();
  });
});

document.getElementById('sell-dialog-cancel').addEventListener('click', () => {
  sellDialog.close();
  pendingSale = null;
});

sellDialog.addEventListener('close', () => {
  if (pendingSale) {
    pendingSale.button.focus();
    pendingSale = null;
  }
});

sellConfirmButton.addEventListener('click', async () => {
  if (!pendingSale) return;

  const sale = pendingSale;
  sellConfirmButton.disabled = true;
  sellConfirmButton.textContent = 'Vente en cours…';

  try {
    const response = await fetch('src/DAL/placeOrder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        side: 'sell',
        idStock: Number(sale.button.dataset.idStock),
        amount: sale.quantity,
        unit: 'shares'
      })
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || 'La vente a échoué.');
    }

    pendingSale = null;
    sellDialog.close();
    window.location.reload();
  } catch (error) {
    sellDialog.close();
    sellFeedback.textContent = error instanceof Error
      ? error.message
      : 'Impossible de vendre cette quantité.';
    sellFeedback.classList.add('is-error');
    sellFeedback.hidden = false;
    sale.button.focus();
  } finally {
    sellConfirmButton.disabled = false;
    sellConfirmButton.textContent = 'Confirmer la vente';
  }
});

window.setInterval(() => {
  const activeElement = document.activeElement;
  const editingPosition = activeElement instanceof HTMLInputElement;

  if (document.visibilityState === 'visible' && !sellDialog.open && !editingPosition) {
    window.location.reload();
  }
}, 60_000);
</script>

<?php require $projectRoot . '/views/layout/footer.php'; ?>