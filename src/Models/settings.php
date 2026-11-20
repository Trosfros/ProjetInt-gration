<?php

$projectRoot = dirname(__DIR__, 2);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: index.php?action=loginPage');
    exit;
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
