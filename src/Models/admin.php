<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Controllers/AdminController.php';

$adminUsers = [];
$adminError = null;
try {
    $adminUsers = (new AdminController(getPDO()))->getUsers();
} catch (Throwable $e) {
    error_log('admin.php failed to load users: ' . $e->getMessage());
    $adminError = 'Impossible de charger la liste des utilisateurs pour le moment.';
}

$totalUserBalance = array_sum(array_map(
    static fn (array $user): float => (float) ($user['Balance'] ?? 0),
    $adminUsers
));
$pageTitle = 'Administration | NovaBank';
$pageStylesheets = ['dashboard.css', 'admin.css'];
$activePage = 'admin';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
    <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main admin-page" aria-label="Administration">
        <header class="admin-header">
            <div>
                <p class="admin-eyebrow">Espace réservé aux administrateurs</p>
                <h1>Administration</h1>
                <p>Consultez les utilisateurs inscrits et leurs soldes actuels.</p>
            </div>
            <span class="admin-header-icon" aria-hidden="true">⌘</span>
        </header>

        <?php if ($adminError !== null): ?>
            <div class="admin-alert" role="alert">
                <?= htmlspecialchars($adminError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <section class="admin-overview" aria-label="Résumé des utilisateurs">
                <article class="admin-stat-card">
                    <span>Utilisateurs</span>
                    <strong><?= number_format(count($adminUsers), 0, ',', ' ') ?></strong>
                    <small>Comptes inscrits sur NovaBank</small>
                </article>
                <article class="admin-stat-card admin-stat-balance">
                    <span>Soldes cumulés</span>
                    <strong><?= number_format($totalUserBalance, 2, ',', ' ') ?> $</strong>
                    <small>Somme des soldes individuels en CAD</small>
                </article>
            </section>

            <section class="admin-users-section" aria-labelledby="admin-users-title">
                <div class="admin-section-heading">
                    <div>
                        <h2 id="admin-users-title">Liste des utilisateurs</h2>
                        <p><?= count($adminUsers) === 1 ? '1 utilisateur' : number_format(count($adminUsers), 0, ',', ' ') . ' utilisateurs' ?></p>
                    </div>
                </div>

                <?php if ($adminUsers === []): ?>
                    <p class="admin-empty-state">Aucun utilisateur à afficher.</p>
                <?php else: ?>
                    <div class="admin-table-scroll">
                        <table class="admin-users-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Utilisateur</th>
                                    <th scope="col">Adresse courriel</th>
                                    <th scope="col">Rôle</th>
                                    <th scope="col" class="admin-balance-column">Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminUsers as $user): ?>
                                    <tr>
                                        <td class="admin-user-id">#<?= (int) $user['idUser'] ?></td>
                                        <td class="admin-username"><?= htmlspecialchars((string) $user['Alias'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($user['Description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="admin-role<?= (int) $user['IsAdmin'] === 1 ? ' admin-role-admin' : '' ?>">
                                                <?= (int) $user['IsAdmin'] === 1 ? 'Administrateur' : 'Utilisateur' ?>
                                            </span>
                                        </td>
                                        <td class="admin-balance-column"><?= number_format((float) ($user['Balance'] ?? 0), 2, ',', ' ') ?> $</td>
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
