<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Models/TransactionModel.php';

$userId = (int) $_SESSION['user']['id'];

$pageTitle = 'NovaBank | Historique';
$pageStylesheets = ['dashboard.css', 'history.css'];
$activePage = 'history';
$historyError = null;
$transactions = [];

try {
    $transactionModel = new TransactionModel();
    $transactions = $transactionModel->getRecentForUser($userId);
} catch (Throwable $e) {
    error_log('history.php failed to load transactions: ' . $e->getMessage());
    $historyError = 'Impossible de charger votre historique pour le moment.';
}

$typeLabels = [
    'deposit' => 'Dépôt',
    'withdrawal' => 'Retrait',
    'purchase' => 'Achat',
    'sale' => 'Vente',
];

require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main history-page" aria-label="Historique des transactions">
        <header class="history-header">
            <div>
                <p class="history-kicker">Votre espace financier</p>
                <h1>Historique des transactions</h1>
                <p>Consultez vos achats, ventes, dépôts et retraits récents.</p>
            </div>
        </header>

        <?php if ($historyError !== null): ?>
            <div class="history-alert" role="alert"><?= htmlspecialchars($historyError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <section class="history-list-card">
                <div class="history-list-heading">
                    <div>
                        <h2>Transactions récentes</h2>
                        <p>Les transactions sont classées de la plus récente à la plus ancienne.</p>
                    </div>
                </div>

                <?php if ($transactions === []): ?>
                    <div class="history-empty">
                        <span class="history-empty-icon" aria-hidden="true">↗</span>
                        <h3>Aucune transaction pour le moment</h3>
                        <p>Vos prochaines opérations apparaîtront ici.</p>
                        <a class="history-action-link" href="index.php?action=accounts">Voir mes comptes</a>
                    </div>
                <?php else: ?>
                    <div class="history-table-wrap">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Transaction</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    $type = (string) $transaction['TransactionType'];
                                    $isIncoming = in_array($type, ['deposit', 'sale'], true);
                                    $transactionDate = new DateTimeImmutable((string) $transaction['CreatedAt']);
                                    ?>
                                    <tr>
                                        <td data-label="Transaction">
                                            <div class="history-operation">
                                                <span class="history-operation-icon <?= $isIncoming ? 'is-incoming' : 'is-outgoing' ?>" aria-hidden="true">
                                                    <?= $isIncoming ? '↓' : '↑' ?>
                                                </span>
                                                <span>
                                                    <strong><?= htmlspecialchars((string) $transaction['Description'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <small><?= htmlspecialchars($typeLabels[$type] ?? 'Transaction', ENT_QUOTES, 'UTF-8') ?></small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="Date">
                                            <span class="history-date"><?= htmlspecialchars($transactionDate->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="history-time"><?= htmlspecialchars($transactionDate->format('H:i'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td data-label="Montant" class="<?= $isIncoming ? 'history-positive' : 'history-negative' ?>">
                                            <?= $isIncoming ? '+' : '−' ?><?= number_format((float) $transaction['Amount'], 2, ',', ' ') ?>
                                            <?= htmlspecialchars((string) $transaction['Currency'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
