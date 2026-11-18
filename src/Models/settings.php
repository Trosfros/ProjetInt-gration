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
$pageStylesheets = ['dashboard.css', 'settings.css'];
$activePage = 'settings';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main settings-page" aria-label="Paramètres">
        <header class="settings-header">
            <p class="settings-eyebrow">Préférences de votre compte</p>
            <h1>Paramètres</h1>
            <p>Personnalisez l’affichage de votre espace NovaBank.</p>
        </header>

        <div class="settings-grid">
            <section class="settings-card" aria-labelledby="currency-setting-title">
                <div class="settings-card-heading">
                    <span class="settings-card-icon" aria-hidden="true">$</span>
                    <div>
                        <p class="settings-eyebrow">Affichage</p>
                        <h2 id="currency-setting-title">Devise des montants</h2>
                    </div>
                </div>

                <p class="settings-description">
                    Utilisez le sélecteur CAD/USD dans le header pour choisir la devise d’affichage sur l’ensemble du site.
                </p>
            </section>

            <aside class="settings-info-card" aria-labelledby="currency-info-title">
                <h2 id="currency-info-title">À savoir</h2>
                <ul>
                    <li>Votre choix s’applique aux montants affichés sur toutes les pages.</li>
                    <li>Le taux de référence CAD/USD est actualisé quotidiennement.</li>
                    <li>Ce réglage ne modifie ni les soldes enregistrés ni la devise des opérations.</li>
                </ul>
            </aside>
        </div>
    </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
