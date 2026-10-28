<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Controllers/TransferController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) $_SESSION['user']['id'];
$transferError = null;
$transferSuccess = $_SESSION['transfer_success'] ?? null;
unset($_SESSION['transfer_success']);

if (empty($_SESSION['transfer_csrf_token'])) {
    $_SESSION['transfer_csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $transferController = new TransferController();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals($_SESSION['transfer_csrf_token'], $submittedToken)) {
            $transferError = 'Votre session a expiré. Veuillez réessayer.';
        } else {
            try {
                $transferController->sendTransfer(
                    $userId,
                    (string) ($_POST['recipient_email'] ?? ''),
                    (string) ($_POST['amount'] ?? '')
                );
                $_SESSION['transfer_success'] = 'Votre virement a été effectué avec succès.';
                header('Location: index.php?action=transfers');
                exit;
            } catch (InvalidArgumentException $e) {
                $transferError = $e->getMessage();
            } catch (Throwable $e) {
                error_log('transfers.php failed to send transfer: ' . $e->getMessage());
                $transferError = 'Impossible d’effectuer le virement pour le moment. Veuillez réessayer.';
            }
        }
    }

    $transferData = $transferController->getPageData($userId);
} catch (Throwable $e) {
    error_log('transfers.php failed to load transfer page: ' . $e->getMessage());
    $transferData = null;
    $transferError = 'Impossible de charger vos informations de virement pour le moment.';
}

$pageTitle = 'NovaBank | Virements';
$pageStylesheets = ['dashboard.css', 'transfer.css'];
$activePage = 'transfers';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main transfer-page" aria-label="Virements">
        <header class="transfer-header">
            <div>
                <p class="transfer-kicker">Envoyez de l’argent simplement</p>
                <h1>Virements</h1>
                <p>Transférez des fonds à un autre utilisateur NovaBank à l’aide de son adresse email.</p>
            </div>
            <span class="transfer-header-icon" aria-hidden="true">↗</span>
        </header>

        <?php if ($transferSuccess !== null): ?>
            <div class="transfer-alert transfer-alert-success" role="status">
                <?= htmlspecialchars((string) $transferSuccess, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($transferError !== null): ?>
            <div class="transfer-alert" role="alert">
                <?= htmlspecialchars($transferError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($transferData !== null): ?>
            <section class="transfer-overview" aria-label="Résumé des limites">
                <article class="transfer-stat-card">
                    <span>Solde disponible</span>
                    <strong><?= number_format($transferData['balance'], 2, ',', ' ') ?> $</strong>
                    <small>Solde actuel de votre compte</small>
                </article>
                <article class="transfer-stat-card">
                    <span>Limite quotidienne</span>
                    <strong>10 000,00 $</strong>
                    <small>Montant total maximal envoyé par jour</small>
                </article>
                <article class="transfer-stat-card">
                    <span>Encore disponible aujourd’hui</span>
                    <strong><?= number_format($transferData['dailyRemaining'], 2, ',', ' ') ?> $</strong>
                    <small>Déjà envoyé : <?= number_format($transferData['dailySent'], 2, ',', ' ') ?> $</small>
                </article>
            </section>

            <section class="transfer-card">
                <div class="transfer-card-heading">
                    <h2>Envoyer un virement</h2>
                    <p>Vérifiez soigneusement l’adresse email et le montant avant de confirmer.</p>
                </div>

                <form class="transfer-form" method="post" action="index.php?action=transfers">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['transfer_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="transfer-field">
                        <label for="recipient-email">Email du destinataire</label>
                        <input
                            id="recipient-email"
                            name="recipient_email"
                            type="email"
                            autocomplete="email"
                            maxlength="255"
                            required
                            value="<?= htmlspecialchars((string) ($_POST['recipient_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="exemple@courriel.com"
                        >
                    </div>

                    <div class="transfer-field">
                        <label for="transfer-amount">Montant à envoyer</label>
                        <div class="transfer-amount-input">
                            <span aria-hidden="true">$</span>
                            <input
                                id="transfer-amount"
                                name="amount"
                                type="number"
                                min="0.01"
                                max="10000.00"
                                step="0.01"
                                inputmode="decimal"
                                required
                                value="<?= htmlspecialchars((string) ($_POST['amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="0,00"
                            >
                            <span>CAD</span>
                        </div>
                        <small>Le total de vos virements sortants ne peut pas dépasser 10 000,00 $ par jour.</small>
                    </div>

                    <button class="transfer-submit" type="submit"<?= $transferData['dailyRemaining'] <= 0 || $transferData['balance'] <= 0 ? ' disabled' : '' ?>>
                        Confirmer le virement
                    </button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
