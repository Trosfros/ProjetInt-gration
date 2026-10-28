<?php
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . "/src/DAL/AccountDAL.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
}
$AccountDal = new AccountDAL();

$accountsToShow = $AccountDal->GetAccounts($_SESSION['user']['username']);
$userBalance = $AccountDal->GetUserBalance($_SESSION['user']['username']);

$pageTitle = 'NovaBank | Comptes';
$pageStylesheets = ['dashboard.css', 'account.css'];
$activePage = 'Accounts';

require $projectRoot . '/views/layout/header.php';
?>

<head>
    <link rel="stylesheet" href="public/css/account.css">
</head>
<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main investment-page" aria-label="Page marché">
        <div class="investment-shell">
            <header class="investment-header">

                <div>
                    <p class="investment-kicker"><?= htmlspecialchars($_SESSION['user']['username']) ?></p>
                    <h1>Comptes</h1>
                </div>
                <div class="user-balance">
                    <p class="user-balance-label">Balance</p>
                    <h1 class="user-balance-amount">$<?= number_format($userBalance, 2) ?></h1>
                </div >
            </header>
                <section class="account-grid">
                    <div class="account-card account-card-add" onclick="openNewAccountModal()" role="button"
                        tabindex="0">
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
                                <button type="button" class="btn-account btn-account-add"
                                    onclick="openMoneyModal( 'add', '<?= htmlspecialchars($account['AccountName'], ENT_QUOTES) ?>')">
                                    Ajouter de l'argent
                                </button>
                                <button type="button" class="btn-account btn-account-remove"
                                    onclick="openMoneyModal( 'remove', '<?= htmlspecialchars($account['AccountName'], ENT_QUOTES) ?>')">
                                    Retirer de l'argent
                                </button>
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
                                <button type="button" class="btn-account"
                                    onclick="closeNewAccountModal()">Annuler</button>
                                <button type="submit" class="btn-account btn-account-add">Créer</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="moneyOverlay" class="modal-overlay" hidden>
                    <div class="modal-card">
                        <div class="modal-header">
                            <h2 id="moneyModalTitle">Ajouter de l'argent</h2>
                            <button type="button" class="modal-close" onclick="closeMoneyModal()"
                                aria-label="Fermer">&times;</button>
                        </div>

                        <form id="moneyForm">
                            <input type="hidden" id="moneyAccountName" name="accountName">
                            <input type="hidden" id="moneyMode" name="mode">

                            <p id="moneyAccountLabel" class="account-meta" style="margin: 0 0 14px;"></p>

                            <div class="modal-field">
                                <label for="moneyAmount">Montant</label>
                                <input type="number" id="moneyAmount" name="amount" class="trade-input" step="0.01"
                                    min="0.01" required>
                            </div>

                            <p id="moneyFeedback" class="trade-feedback" hidden></p>

                            <div class="modal-actions">
                                <button type="button" class="btn-account" onclick="closeMoneyModal()">Annuler</button>
                                <button type="submit" id="moneySubmitBtn"
                                    class="btn-account btn-account-add">Confirmer</button>
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
            const res = await fetch('src/DAL/newAccount.php', {
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


    document.getElementById('newAccountOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeNewAccountModal();
    });
    function openMoneyModal(mode, accountName) {
        document.getElementById('moneyAccountName').value = accountName;
        document.getElementById('moneyMode').value = mode;
        document.getElementById('moneyAccountLabel').textContent = accountName;
        document.getElementById('moneyAmount').value = '';

        const title = document.getElementById('moneyModalTitle');
        const submitBtn = document.getElementById('moneySubmitBtn');

        if (mode === 'add') {
            title.textContent = "Ajouter de l'argent";
            submitBtn.textContent = 'Déposer';
            submitBtn.className = 'btn-account btn-account-add';
        } else {
            title.textContent = "Retirer de l'argent";
            submitBtn.textContent = 'Retirer';
            submitBtn.className = 'btn-account btn-account-remove';
        }

        document.getElementById('moneyOverlay').hidden = false;
    }

    function closeMoneyModal() {
        document.getElementById('moneyOverlay').hidden = true;
        document.getElementById('moneyForm').reset();
        const feedback = document.getElementById('moneyFeedback');
        feedback.hidden = true;
        feedback.classList.remove('is-success', 'is-error');
    }

    document.getElementById('moneyForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const feedback = document.getElementById('moneyFeedback');
        feedback.hidden = true;

        const accountName = document.getElementById('moneyAccountName').value;
        const mode = document.getElementById('moneyMode').value;
        const amount = parseFloat(document.getElementById('moneyAmount').value);

        if (!amount || amount <= 0) {
            feedback.textContent = 'Entrez un montant valide.';
            feedback.classList.add('is-error');
            feedback.hidden = false;
            return;
        }

        try {
            const res = await fetch('src/DAL/updateAccountMoney.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accountName, amount, mode }),
            });
            const data = await res.json();

            feedback.textContent = data.message;
            feedback.classList.add(data.success ? 'is-success' : 'is-error');
            feedback.hidden = false;

            if (data.success) {
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            feedback.textContent = 'Erreur réseau.';
            feedback.classList.add('is-error');
            feedback.hidden = false;
        }
    });
    document.getElementById('moneyOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeMoneyModal();
    });
</script>
<style>

</style>
<?php require $projectRoot . '/views/layout/footer.php'; ?>