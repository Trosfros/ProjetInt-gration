<?php

$projectRoot = dirname(__DIR__, 2);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
}

require_once $projectRoot . '/src/Models/SettingsModel.php';

$userId = (int) $_SESSION['user']['id'];
$settingsModel = new SettingsModel();
$settingsCsrfToken = getCsrfToken();
$settingsError = null;
$settingsSuccess = $_SESSION['settings_success'] ?? null;
unset($_SESSION['settings_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
    if (!validCsrfToken($submittedToken)) {
        $settingsError = 'Votre session a expiré. Rechargez la page et réessayez.';
    } elseif (($_POST['form_action'] ?? '') === 'save_notification_preferences') {
        try {
            $threshold = is_string($_POST['low_balance_threshold'] ?? null)
                ? $_POST['low_balance_threshold']
                : '';
            $settingsModel->saveNotificationPreferences(
                $userId,
                isset($_POST['notify_transfers']),
                isset($_POST['notify_logins']),
                isset($_POST['notify_low_balance']),
                $threshold
            );
            $_SESSION['settings_success'] = 'Vos préférences de notification ont été enregistrées.';
            header('Location: index.php?action=settings');
            exit;
        } catch (InvalidArgumentException $e) {
            $settingsError = $e->getMessage();
        } catch (Throwable $e) {
            error_log('settings.php failed to save notification preferences: ' . $e->getMessage());
            $settingsError = 'Impossible d’enregistrer vos préférences pour le moment.';
        }
    } elseif (($_POST['form_action'] ?? '') === 'delete_user_account') {
        $password = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $confirmation = is_string($_POST['delete_confirmation'] ?? null) ? $_POST['delete_confirmation'] : '';
        try {
            $settingsModel->deleteUserAccount($userId, $password, $confirmation);
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['login_success'] = 'Votre compte et les données associées ont été supprimés.';
            header('Location: index.php?action=loginPage');
            exit;
        } catch (InvalidArgumentException $e) {
            $settingsError = $e->getMessage();
        } catch (Throwable $e) {
            error_log('settings.php failed to delete user account: ' . $e->getMessage());
            $settingsError = 'Impossible de supprimer votre compte pour le moment.';
        }
    } else {
        $settingsError = 'Action invalide. Rechargez la page et réessayez.';
    }
}

$notificationPreferences = null;
try {
    $notificationPreferences = $settingsModel->getNotificationPreferences($userId);
} catch (Throwable $e) {
    error_log('settings.php failed to load notification preferences: ' . $e->getMessage());
    $settingsError = $settingsError ?? 'Impossible de charger vos préférences de notification.';
}

$pageTitle = 'Paramètres | NovaBank';
$pageStylesheets = ['dashboard.css', 'profile.css', 'settings.css'];
$activePage = 'settings';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout settings-layout">
    <main class="dashboard-main settings-page" aria-label="Paramètres">
        <section class="profile-card settings-profile-card" aria-labelledby="settings-title">
            <header class="profile-heading">
            <div class="profile-avatar settings-profile-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
            </div>
                <div>
                    <p class="profile-eyebrow">Préférences de votre compte</p>
                    <h1 id="settings-title">Paramètres</h1>
                </div>
            </header>

            <?php if ($settingsError !== null): ?>
                <div class="settings-feedback is-error" role="alert"><?= htmlspecialchars($settingsError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($settingsSuccess !== null): ?>
                <div class="settings-feedback is-success" role="status"><?= htmlspecialchars((string) $settingsSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <section class="profile-password-section settings-preferences settings-theme-setting" aria-labelledby="theme-setting-title">
                <div>
                    <h2 id="theme-setting-title">Apparence</h2>
                    <p>Choisissez le mode clair ou sombre pour votre espace NovaBank.</p>
                </div>
                <div class="theme-switch-container">
                    <label class="theme-switch" for="themeToggle" aria-label="Basculer entre le mode clair et sombre">
                        <input type="checkbox" id="themeToggle" aria-label="Basculer entre le mode clair et sombre" <?= $isLightMode ? 'checked' : '' ?>>
                        <span class="theme-slider">
                            <svg class="icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                            </svg>
                            <svg class="icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </span>
                    </label>
                </div>
            </section>

            <section class="profile-password-section settings-preferences" aria-labelledby="currency-setting-title">
                <h2 id="currency-setting-title">Devise d’affichage</h2>
                <p>Choisissez CAD ou USD avec le sélecteur du header. Votre préférence s’applique aux montants affichés sur toutes les pages.</p>
            </section>

            <section class="settings-notification-section" aria-labelledby="notification-setting-title">
                <div class="settings-section-heading">
                    <h2 id="notification-setting-title">Notifications</h2>
                    <p>Choisissez les alertes à afficher dans votre centre de notifications.</p>
                </div>
                <?php if ($notificationPreferences !== null): ?>
                    <form class="settings-notification-form" method="post" action="index.php?action=settings">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($settingsCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action" value="save_notification_preferences">

                        <label class="settings-notification-option">
                            <input type="checkbox" name="notify_transfers" value="1"<?= $notificationPreferences['transfers'] ? ' checked' : '' ?>>
                            <span>
                                <strong>Virements</strong>
                                <small>Recevoir une alerte pour les virements envoyés et reçus.</small>
                            </span>
                        </label>

                        <label class="settings-notification-option">
                            <input type="checkbox" name="notify_logins" value="1"<?= $notificationPreferences['logins'] ? ' checked' : '' ?>>
                            <span>
                                <strong>Nouvelles connexions</strong>
                                <small>Être informé lorsqu’une connexion est effectuée à votre compte.</small>
                            </span>
                        </label>

                        <label class="settings-notification-option">
                            <input type="checkbox" name="notify_low_balance" value="1"<?= $notificationPreferences['lowBalance'] ? ' checked' : '' ?>>
                            <span>
                                <strong>Solde bas</strong>
                                <small>Recevoir une alerte lorsque votre solde passe sous le seuil choisi.</small>
                            </span>
                        </label>

                        <label class="settings-threshold-field" for="low-balance-threshold">
                            Seuil de solde bas (CAD)
                            <input
                                id="low-balance-threshold"
                                type="number"
                                name="low_balance_threshold"
                                min="0.01"
                                max="9999999.99"
                                step="0.01"
                                required
                                value="<?= htmlspecialchars($notificationPreferences['lowBalanceThreshold'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </label>

                        <button class="settings-save-button" type="submit">Enregistrer les préférences</button>
                    </form>
                <?php else: ?>
                    <p class="settings-section-notice">Les préférences de notification sont temporairement indisponibles.</p>
                <?php endif; ?>
            </section>

            <section class="settings-danger-section" aria-labelledby="delete-account-title">
                <div>
                    <h2 id="delete-account-title">Supprimer mon compte</h2>
                    <p>Cette action est définitive. Elle supprimera votre profil, vos comptes, vos investissements, vos notifications et votre historique financier.</p>
                    <p>Les virements correspondants dans l’historique des autres utilisateurs resteront présents, mais votre identité y sera anonymisée.</p>
                </div>
                <form class="settings-delete-form" method="post" action="index.php?action=settings">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($settingsCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="delete_user_account">

                    <label for="delete-account-password">Mot de passe actuel</label>
                    <input
                        id="delete-account-password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                    <label for="delete-account-confirmation">Tapez SUPPRIMER pour confirmer</label>
                    <input
                        id="delete-account-confirmation"
                        type="text"
                        name="delete_confirmation"
                        autocomplete="off"
                        pattern="SUPPRIMER"
                        required
                    >

                    <button class="settings-delete-button" type="submit">Supprimer définitivement mon compte</button>
                </form>
            </section>

            <dl class="profile-details settings-details">
                <div>
                    <dt>Taux de référence</dt>
                    <dd>Actualisé quotidiennement</dd>
                </div>
                <div>
                    <dt>Opérations et soldes</dt>
                    <dd>La conversion d’affichage ne modifie pas les montants enregistrés ni la devise des opérations.</dd>
                </div>
            </dl>
        </section>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
