<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Models/SupportModel.php';
$userId = (int) $_SESSION['user']['id'];
$pageTitle = 'Service clientèle | NovaBank';
$pageStylesheets = ['dashboard.css', 'support.css'];
$activePage = 'support';
$supportError = null;
$supportActionError = null;
$requests = [];
$submittedSubject = is_string($_POST['subject'] ?? null) ? $_POST['subject'] : '';
$submittedMessage = is_string($_POST['message'] ?? null) ? $_POST['message'] : '';
$supportSuccess = $_SESSION['support_success'] ?? null;
unset($_SESSION['support_success']);

if (empty($_SESSION['support_csrf_token'])) {
    $_SESSION['support_csrf_token'] = bin2hex(random_bytes(32));
}
$supportCsrfToken = $_SESSION['support_csrf_token'];

try {
    $supportModel = new SupportModel();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!is_string($submittedToken) || !hash_equals($supportCsrfToken, $submittedToken)) {
            $supportActionError = 'Votre session a expiré. Rechargez la page et réessayez.';
        } elseif (!is_string($_POST['subject'] ?? null) || !is_string($_POST['message'] ?? null)) {
            $supportActionError = 'Les informations saisies sont invalides.';
        } else {
            try {
                $supportModel->createRequest($userId, $submittedSubject, $submittedMessage);
                $_SESSION['support_success'] = 'Votre demande a été envoyée au service clientèle.';
                header('Location: index.php?action=support');
                exit;
            } catch (InvalidArgumentException $e) {
                $supportActionError = $e->getMessage();
            }
        }
    }

    $requests = $supportModel->getRequestsForUser($userId);
} catch (Throwable $e) {
    error_log('support.php failed to process a support request: ' . $e->getMessage());
    $supportError = 'Impossible de charger le service clientèle pour le moment.';
}

require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main support-page" aria-label="Service clientèle">
        <header class="support-header">
            <p class="support-eyebrow">Nous sommes là pour vous aider</p>
            <h1>Service clientèle</h1>
            <p>Envoyez-nous votre question et consultez ici les réponses de notre équipe.</p>
        </header>

        <?php if ($supportSuccess !== null): ?>
            <div class="support-alert is-success" role="status"><?= htmlspecialchars((string) $supportSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($supportActionError !== null): ?>
            <div class="support-alert is-error" role="alert"><?= htmlspecialchars($supportActionError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($supportError !== null): ?>
            <div class="support-alert is-error" role="alert"><?= htmlspecialchars($supportError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <section class="support-panel" aria-labelledby="support-form-title">
                <div class="support-panel-heading">
                    <h2 id="support-form-title">Écrire au service clientèle</h2>
                    <p>Décrivez votre besoin. Notre équipe vous répondra dans cet espace.</p>
                </div>
                <form class="support-form" method="post" action="index.php?action=support">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($supportCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <label>
                        <span>Sujet</span>
                        <input type="text" name="subject" maxlength="150" value="<?= htmlspecialchars($submittedSubject, ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        <span>Votre message</span>
                        <textarea name="message" rows="6" maxlength="4000" required><?= htmlspecialchars($submittedMessage, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                    <button class="support-submit" type="submit">Envoyer la demande</button>
                </form>
            </section>

            <section class="support-requests" aria-labelledby="support-requests-title">
                <div class="support-panel-heading">
                    <h2 id="support-requests-title">Mes demandes</h2>
                    <p>Vos demandes et les réponses du service clientèle.</p>
                </div>
                <?php if ($requests === []): ?>
                    <p class="support-empty">Vous n’avez pas encore envoyé de demande.</p>
                <?php else: ?>
                    <div class="support-request-list">
                        <?php foreach ($requests as $request): ?>
                            <?php $createdAt = getQuebecDateTime((string) $request['CreatedAt']); ?>
                            <article class="support-request-card">
                                <div class="support-request-heading">
                                    <div>
                                        <span class="support-status<?= $request['Status'] === 'answered' ? ' is-answered' : '' ?>">
                                            <?= $request['Status'] === 'answered' ? 'Répondue' : 'En attente' ?>
                                        </span>
                                        <h3><?= htmlspecialchars((string) $request['Subject'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    </div>
                                    <time datetime="<?= htmlspecialchars($createdAt->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($createdAt->format('d/m/Y à H:i'), ENT_QUOTES, 'UTF-8') ?>
                                    </time>
                                </div>
                                <p class="support-message"><?= nl2br(htmlspecialchars((string) $request['Message'], ENT_QUOTES, 'UTF-8')) ?></p>
                                <?php if (!empty($request['AdminResponse'])): ?>
                                    <div class="support-response">
                                        <strong>Réponse du service clientèle</strong>
                                        <p><?= nl2br(htmlspecialchars((string) $request['AdminResponse'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
