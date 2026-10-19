<?php
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . "/src/DAL/AccountDal.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
}
$AccountDal = new AccountDAL();

$accountsToShow = $AccountDal->GetAccounts($_SESSION['user']['username']);

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
                    <p class="investment-kicker"><?= htmlspecialchars($_SESSION['user']['username']) ?></p>
                    <h1>Comptes</h1>
                </div>
                <button type="button" class="btn-new-account" onclick="openNewAccountModal()">
                    + Nouveau compte
                </button>


            </header>
            <section class="account-grid" >
                <div class="account-card account-card-add" onclick="openNewAccountModal()" role="button" tabindex="0">
                    <span class="account-add-icon">+</span>
                    <span class="account-add-label">Nouveau compte</span>
                </div>
                <?php foreach ($accountsToShow as $account): ?>
                    <?php
                    $status = $account['Status'] ?? 'active';
                    $balance = (float) ($account['Money'] ?? 0);
                    $currency = $account['Currency'] ?? 'CAD';
                    $rawNumber = (string) ($account['AccountNumber'] ?? '');
                    $maskedNumber = $rawNumber !== '' ? '•••• ' . substr($rawNumber, -4) : '—';
                    ?>
                    <div class="account-card">
                        <div class="account-card-header">
                            <div>
                                <h3 class="account-name"><?= htmlspecialchars($account['AccountName']) ?></h3>
                                <span
                                    class="account-type"><?= htmlspecialchars($account['AccountType'] ?? 'checking') ?></span>
                            </div>
                            <span class="account-status account-status-<?= htmlspecialchars($status) ?>">
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </span>
                        </div>

                        <div class="account-balance-block">
                            <span class="account-balance-label">Solde</span>
                            <span class="account-balance <?= $balance < 0 ? 'is-negative' : '' ?>">
                                <?= htmlspecialchars($currency) ?>     <?= number_format($balance, 2) ?>
                            </span>
                        </div>

                        <div class="account-meta">
                            <span class="account-number">Compte <?= htmlspecialchars($maskedNumber) ?></span>
                        </div>

                        <div class="account-actions">
                            <a href="index.php?action=addMoney&idAccount=<?= (int) $account['idAccount'] ?>"
                                class="btn-account btn-account-add">Ajouter de l'argent</a>
                            <a href="index.php?action=removeMoney&idAccount=<?= (int) $account['idAccount'] ?>"
                                class="btn-account btn-account-remove">Retirer de l'argent</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
            <div id="newAccountOverlay" class="modal-overlay" hidden>
                <div class="modal-card">
                    <div class="modal-header">
                        <h2>Nouveau compte</h2>
                        <button type="button" class="modal-close" onclick="closeNewAccountModal()"
                            aria-label="Fermer">&times;</button>
                    </div>

                    <form id="newAccountForm">
                        <div class="modal-field">
                            <label for="newAccountName">Nom du compte</label>
                            <input type="text" id="newAccountName" name="accountName" class="trade-input" required
                                maxlength="50">
                        </div>

                        <div class="modal-field">
                            <label for="newAccountType">Type de compte</label>
                            <select id="newAccountType" name="accountType" class="trade-input">
                                <option value="checking">Chèque</option>
                                <option value="savings">Épargne</option>
                                <option value="investment">Investissement</option>
                            </select>
                        </div>

                        <div class="modal-field">
                            <label for="newAccountCurrency">Devise</label>
                            <select id="newAccountCurrency" name="currency" class="trade-input">
                               <?// for the moment only these options are available for simplicity sake ?>
                                <option value="CAD">CAD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>

                        <p id="newAccountFeedback" class="trade-feedback" hidden></p>

                        <div class="modal-actions">
                            <button type="button" class="btn-account" onclick="closeNewAccountModal()">Annuler</button>
                            <button type="submit" class="btn-account btn-account-add">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="AddMoneyOverlay" class="modal-overlay" hidden>
                <div class="modal-card">
                    <div class="modal-header">
                        <h2>Nouveau compte</h2>
                        <button type="button" class="modal-close" onclick="closeAddMoneyModal()"
                            aria-label="Fermer">&times;</button>
                    </div>

                    <form id="newAccountForm">
                        <div class="modal-field">
                            <label for="newAccountName">Quantité d'argent ajouter au compte</label>
                            <input type="number" id="newAccountName" name="CashAdded" class="trade-input" required
                                maxlength="50">
                        </div>
                        </div>

                        <p id="newAccountFeedback" class="trade-feedback" hidden></p>

                        <div class="modal-actions">
                            <button type="button" class="btn-account" onclick="closeNewAccountModal()">Annuler</button>
                            <button type="submit" class="btn-account btn-account-add">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
    </main>
</div>
<script>
    function openNewAccountModal() {
    document.getElementById('newAccountOverlay').hidden = false;
}

function closeNewAccountModal() {
    document.getElementById('newAccountOverlay').hidden = true;
    document.getElementById('newAccountForm').reset();
    const feedback = document.getElementById('newAccountFeedback');
    feedback.hidden = true;
    feedback.classList.remove('is-success', 'is-error');
}
function closeAddMoneyModal() {
    document.getElementById('newAccountOverlay').hidden = true;
    document.getElementById('newAccountForm').reset();
    const feedback = document.getElementById('newAccountFeedback');
    feedback.hidden = true;
    feedback.classList.remove('is-success', 'is-error');
}

document.getElementById('newAccountForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const feedback = document.getElementById('newAccountFeedback');
    feedback.hidden = true;

    const payload = {
        accountName: document.getElementById('newAccountName').value.trim(),
        accountType: document.getElementById('newAccountType').value,
        currency: document.getElementById('newAccountCurrency').value,
    };

    if (!payload.accountName) {
        feedback.textContent = 'Le nom du compte est requis.';
        feedback.classList.add('is-error');
        feedback.hidden = false;
        return;
    }

    try {
        const res = await fetch('/src/DAL/newAccount.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            feedback.textContent = data.message;
            feedback.classList.add('is-success');
            feedback.hidden = false;
            setTimeout(() => window.location.reload(), 800);
        } else {
            feedback.textContent = data.message;
            feedback.classList.add('is-error');
            feedback.hidden = false;
        }
    } catch (err) {
        feedback.textContent = 'Erreur réseau lors de la création du compte.';
        feedback.classList.add('is-error');
        feedback.hidden = false;
    }
});

// Close on backdrop click
document.getElementById('newAccountOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeNewAccountModal();
});
</script>
<style>
    .btn-new-account {
        background: rgba(125, 211, 252, 0.12);
        color: #7dd3fc;
        border: 1px solid rgba(125, 211, 252, 0.3);
        border-radius: 6px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
    }

    .account-card-add {
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px dashed rgba(125, 211, 252, 0.35);
        cursor: pointer;
        color: #7dd3fc;
    }

    .account-card-add:hover {
        background: rgba(125, 211, 252, 0.06);
    }

    .account-add-icon {
        font-size: 1.8rem;
        line-height: 1;
    }

    .account-add-label {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-overlay[hidden] {
        display: none;
    }

    .modal-card {
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 24px;
        width: 100%;
        max-width: 400px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .modal-header h2 {
        margin: 0;
        color: #e2e8f0;
        font-size: 1.1rem;
    }

    .modal-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.4rem;
        cursor: pointer;
        line-height: 1;
    }

    .modal-field {
        margin-bottom: 14px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .modal-field label {
        font-size: 0.82rem;
        color: #94a3b8;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
    }

    .modal-actions .btn-account {
        flex: 1;
    }

    .account-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .account-card {
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .account-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .account-name {
        margin: 0 0 4px;
        font-size: 1.05rem;
        color: #e2e8f0;
    }

    .account-type {
        font-size: 0.78rem;
        color: #94a3b8;
        text-transform: capitalize;
    }

    .account-status {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .account-status-active {
        color: #4ade80;
        background: rgba(74, 222, 128, 0.12);
    }

    .account-status-frozen {
        color: #facc15;
        background: rgba(250, 204, 21, 0.12);
    }

    .account-status-closed {
        color: #f87171;
        background: rgba(248, 113, 113, 0.12);
    }

    .account-balance-block {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .account-balance-label {
        font-size: 0.78rem;
        color: #94a3b8;
    }

    .account-balance {
        font-size: 1.6rem;
        font-weight: 700;
        color: #7dd3fc;
    }

    .account-balance.is-negative {
        color: #f87171;
    }

    .account-meta {
        font-size: 0.82rem;
        color: #64748b;
    }

    .account-actions {
        display: flex;
        gap: 10px;
        margin-top: auto;
    }

    .btn-account {
        flex: 1;
        text-align: center;
        padding: 10px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
    }

    .btn-account-add {
        background: rgba(74, 222, 128, 0.12);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.3);
    }

    .btn-account-remove {
        background: rgba(248, 113, 113, 0.12);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.3);
    }
</style>
<?php require $projectRoot . '/views/layout/footer.php'; ?>