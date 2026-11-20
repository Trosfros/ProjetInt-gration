<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Models/SupportModel.php';

$pageTitle = 'Demandes d’aide | NovaBank';
$pageStylesheets = ['dashboard.css', 'support.css'];
$activePage = 'adminSupport';
$supportError = null;
$supportActionError = null;
$supportRequests = [];
$supportSuccess = $_SESSION['admin_support_success'] ?? null;
unset($_SESSION['admin_support_success']);
$submittedRequestId = is_scalar($_POST['request_id'] ?? null) ? (string) $_POST['request_id'] : '';
$submittedResponse = is_string($_POST['response'] ?? null) ? $_POST['response'] : '';

if (empty($_SESSION['admin_support_csrf_token'])) {
    $_SESSION['admin_support_csrf_token'] = bin2hex(random_bytes(32));
}
$adminSupportCsrfToken = $_SESSION['admin_support_csrf_token'];

try {
    $supportModel = new SupportModel();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!is_string($submittedToken) || !hash_equals($adminSupportCsrfToken, $submittedToken)) {
            $supportActionError = 'Votre session a expiré. Rechargez la page et réessayez.';
        } else {
            $requestIdInput = $_POST['request_id'] ?? null;
            $responseInput = $_POST['response'] ?? null;
            $requestId = is_string($requestIdInput) ? filter_var($requestIdInput, FILTER_VALIDATE_INT) : false;
            if ($requestId === false || !is_string($responseInput)) {
                $supportActionError = 'Les informations saisies sont invalides.';
            } else {
                try {
                    $supportModel->replyToRequest((int) $requestId, $responseInput);
                    require_once $projectRoot . '/src/Models/AuditModel.php';
                    AuditModel::record('admin.support.replied', 'medium', 'Réponse ajoutée à une demande de soutien.', (int) $_SESSION['user']['id'], null, ['requestId' => (int) $requestId]);
                    $_SESSION['admin_support_success'] = 'Votre réponse a été envoyée à l’utilisateur.';
                    header('Location: index.php?action=adminSupport');
                    exit;
                } catch (InvalidArgumentException $e) {
                    $supportActionError = $e->getMessage();
                }
            }
        }
    }

    $supportRequests = $supportModel->getAllRequests();
} catch (Throwable $e) {
    error_log('adminSupport.php failed to load or answer support requests: ' . $e->getMessage());
    $supportError = 'Impossible de charger les demandes d’aide pour le moment.';
}

require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main support-page" aria-label="Demandes d’aide du service clientèle">
        <header class="support-header">
            <p class="support-eyebrow">Espace réservé aux administrateurs</p>
            <h1>Demandes d’aide</h1>
            <p>Consultez les demandes des utilisateurs et répondez-leur.</p>
        </header>

        <?php if ($supportSuccess !== null): ?>
            <div class="support-alert is-success" role="status"><?= htmlspecialchars((string) $supportSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($supportActionError !== null): ?>
            <div class="support-alert is-error" role="alert"><?= htmlspecialchars($supportActionError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($supportError !== null): ?>
            <div class="support-alert is-error" role="alert"><?= htmlspecialchars($supportError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif ($supportRequests === []): ?>
            <section class="support-panel support-empty-state">
                <h2>Aucune demande pour le moment</h2>
                <p>Les nouvelles demandes des utilisateurs apparaîtront ici.</p>
            </section>
        <?php else: ?>
            <section class="support-admin-list" aria-label="Liste des demandes">
                <?php foreach ($supportRequests as $request): ?>
                    <?php $createdAt = getQuebecDateTime((string) $request['CreatedAt']); ?>
                    <article class="support-request-card">
                        <div class="support-request-heading">
                            <div>
                                <span class="support-status<?= $request['Status'] === 'answered' ? ' is-answered' : '' ?>">
                                    <?= $request['Status'] === 'answered' ? 'Répondue' : 'En attente' ?>
                                </span>
                                <h2><?= htmlspecialchars((string) $request['Subject'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="support-request-user">
                                    <?= htmlspecialchars((string) $request['Alias'], ENT_QUOTES, 'UTF-8') ?>
                                    · <?= htmlspecialchars((string) ($request['Description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <time datetime="<?= htmlspecialchars($createdAt->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($createdAt->format('d/m/Y à H:i'), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </div>
                        <p class="support-message"><?= nl2br(htmlspecialchars((string) $request['Message'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php if ($request['Status'] === 'answered'): ?>
                            <div class="support-response">
                                <strong>Réponse envoyée</strong>
                                <p><?= nl2br(htmlspecialchars((string) $request['AdminResponse'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        <?php else: ?>
                            <form class="support-form support-reply-form" method="post" action="index.php?action=adminSupport">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminSupportCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="request_id" value="<?= (int) $request['idSupportRequest'] ?>">
                                <label>
                                    <span>Votre réponse</span>
                                    <textarea name="response" rows="4" maxlength="4000" required<?= $submittedRequestId === (string) $request['idSupportRequest'] ? ' autofocus' : '' ?>><?= $submittedRequestId === (string) $request['idSupportRequest'] ? htmlspecialchars($submittedResponse, ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                                </label>
                                <button class="support-submit" type="submit">Envoyer la réponse</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
