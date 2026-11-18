<?php

$projectRoot = dirname(__DIR__, 2);
$userId = (int) $_SESSION['user']['id'];
$pageTitle = 'Notifications | NovaBank';
$pageStylesheets = ['dashboard.css', 'notifications.css'];
$activePage = 'notifications';
$notificationError = null;
$notifications = [];
$notificationSuccess = $_SESSION['notification_success'] ?? null;
$notificationActionError = $_SESSION['notification_error'] ?? null;
unset($_SESSION['notification_success'], $_SESSION['notification_error']);

if (empty($_SESSION['notifications_csrf_token'])) {
    $_SESSION['notifications_csrf_token'] = bin2hex(random_bytes(32));
}
$notificationsCsrfToken = $_SESSION['notifications_csrf_token'];

try {
    $notifications = (new NotificationModel())->getRecentForUser($userId);
} catch (Throwable $e) {
    error_log('notifications.php failed to load notifications: ' . $e->getMessage());
    $notificationError = 'Impossible de charger vos notifications pour le moment.';
}

require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main notifications-page" aria-label="Centre de notifications">
        <header class="notifications-header">
            <div>
                <p class="notifications-eyebrow">Restez informé</p>
                <h1>Centre de notifications</h1>
                <p>Retrouvez les transactions complétées et les messages de l’administration.</p>
            </div>
            <?php if ($notificationError === null && $notifications !== []): ?>
                <form method="post" action="index.php?action=notifications">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($notificationsCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="mark_all_read">
                    <button class="notifications-mark-read" type="submit">Tout marquer comme lu</button>
                </form>
            <?php endif; ?>
        </header>

        <?php if ($notificationSuccess !== null): ?>
            <div class="notifications-alert is-success" role="status"><?= htmlspecialchars((string) $notificationSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($notificationActionError !== null): ?>
            <div class="notifications-alert is-error" role="alert"><?= htmlspecialchars((string) $notificationActionError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($notificationError !== null): ?>
            <div class="notifications-alert is-error" role="alert"><?= htmlspecialchars($notificationError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif ($notifications === []): ?>
            <section class="notifications-empty">
                <span class="notifications-empty-icon" aria-hidden="true">♧</span>
                <h2>Aucune notification</h2>
                <p>Les transactions complétées et les messages de l’administration apparaîtront ici.</p>
            </section>
        <?php else: ?>
            <section class="notifications-list" aria-label="Notifications récentes">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $isAdminMessage = $notification['NotificationType'] === 'admin_message';
                    $createdAt = getQuebecDateTime((string) $notification['CreatedAt']);
                    ?>
                    <article class="notification-card<?= (int) $notification['IsRead'] === 0 ? ' is-unread' : '' ?>">
                        <span class="notification-type-icon<?= $isAdminMessage ? ' is-admin-message' : '' ?>" aria-hidden="true">
                            <?= $isAdminMessage ? '!' : '↗' ?>
                        </span>
                        <div class="notification-content">
                            <div class="notification-card-heading">
                                <div>
                                    <span class="notification-type-label"><?= $isAdminMessage ? 'Message de l’administration' : 'Transaction' ?></span>
                                    <h2><?= htmlspecialchars((string) $notification['Title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                                <?php if ((int) $notification['IsRead'] === 0): ?>
                                    <span class="notification-unread-label">Non lu</span>
                                <?php endif; ?>
                            </div>
                            <p><?= nl2br(htmlspecialchars((string) $notification['Message'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <time datetime="<?= htmlspecialchars($createdAt->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($createdAt->format('d/m/Y à H:i'), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
