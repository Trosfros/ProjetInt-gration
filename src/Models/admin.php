<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Controllers/AdminController.php';

$adminController = new AdminController(getPDO());
$adminUsers = [];
$adminError = null;
$adminActionError = null;
$adminSuccess = $_SESSION['admin_balance_success'] ?? null;
unset($_SESSION['admin_balance_success']);
$selectedUserId = is_scalar($_POST['user_id'] ?? null) ? (string) $_POST['user_id'] : '';
$adjustmentDirection = is_scalar($_POST['direction'] ?? null) ? (string) $_POST['direction'] : 'credit';
$adjustmentAmount = is_scalar($_POST['amount'] ?? null) ? (string) $_POST['amount'] : '';
$adjustmentReason = is_scalar($_POST['reason'] ?? null) ? (string) $_POST['reason'] : '';

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$adminCsrfToken = $_SESSION['admin_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!is_string($submittedToken) || !hash_equals($adminCsrfToken, $submittedToken)) {
        $adminActionError = 'Votre session a expiré. Rechargez la page et réessayez.';
    } else {
        try {
            $postedUserId = $_POST['user_id'] ?? null;
            $validatedUserId = is_string($postedUserId) ? filter_var($postedUserId, FILTER_VALIDATE_INT) : false;
            if ($validatedUserId === false) {
                throw new InvalidArgumentException('Veuillez sélectionner un utilisateur.');
            }
            if (!is_string($_POST['direction'] ?? null)
                || !is_string($_POST['amount'] ?? null)
                || !is_string($_POST['reason'] ?? null)
            ) {
                throw new InvalidArgumentException('Les informations saisies sont invalides.');
            }

            $adminController->adjustUserBalance(
                (int) $validatedUserId,
                $adjustmentDirection,
                $adjustmentAmount,
                $adjustmentReason,
                (string) ($_SESSION['user']['username'] ?? 'administrateur')
            );
            $_SESSION['admin_balance_success'] = 'Le solde a été ajusté et l’opération a été ajoutée à l’historique.';
            header('Location: index.php?action=admin');
            exit;
        } catch (InvalidArgumentException $e) {
            $adminActionError = $e->getMessage();
        } catch (Throwable $e) {
            error_log('admin.php failed to adjust a user balance: ' . $e->getMessage());
            $adminActionError = 'Impossible d’ajuster le solde pour le moment.';
        }
    }
}

try {
    $adminUsers = $adminController->getUsers();
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
                <p>Consultez les utilisateurs et gérez leurs soldes.</p>
            </div>
            <span class="admin-header-icon" aria-hidden="true">⌘</span>
        </header>

        <?php if ($adminSuccess !== null): ?>
            <div class="admin-success" role="status"><?= htmlspecialchars((string) $adminSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($adminActionError !== null): ?>
            <div class="admin-alert" role="alert"><?= htmlspecialchars($adminActionError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

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

            <section class="admin-adjustment-section" aria-labelledby="admin-adjustment-title">
                <div class="admin-section-heading">
                    <div>
                        <h2 id="admin-adjustment-title">Ajuster un solde</h2>
                        <p>Chaque ajustement est enregistré dans l’historique de l’utilisateur.</p>
                    </div>
                </div>
                <?php if ($adminUsers === []): ?>
                    <p class="admin-empty-state">Aucun utilisateur à ajuster.</p>
                <?php else: ?>
                    <form class="admin-adjustment-form" method="post" action="index.php?action=admin">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <label>
                            <span>Utilisateur</span>
                            <select name="user_id" required>
                                <option value="">Sélectionner un utilisateur</option>
                                <?php foreach ($adminUsers as $user): ?>
                                    <option value="<?= (int) $user['idUser'] ?>"<?= $selectedUserId === (string) $user['idUser'] ? ' selected' : '' ?>>
                                        <?= htmlspecialchars((string) $user['Alias'], ENT_QUOTES, 'UTF-8') ?>
                                        (<?= number_format((float) ($user['Balance'] ?? 0), 2, ',', ' ') ?> $)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Opération</span>
                            <select name="direction" required>
                                <option value="credit"<?= $adjustmentDirection === 'credit' ? ' selected' : '' ?>>Ajouter de l’argent</option>
                                <option value="debit"<?= $adjustmentDirection === 'debit' ? ' selected' : '' ?>>Retirer de l’argent</option>
                            </select>
                        </label>
                        <label>
                            <span>Montant (CAD)</span>
                            <input type="number" name="amount" min="0.01" max="9999999999999.99" step="0.01" value="<?= htmlspecialchars($adjustmentAmount, ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="admin-adjustment-reason">
                            <span>Motif</span>
                            <input type="text" name="reason" maxlength="120" value="<?= htmlspecialchars($adjustmentReason, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. correction d’une erreur" required>
                        </label>
                        <button class="admin-adjustment-submit" type="submit">Confirmer l’ajustement</button>
                    </form>
                <?php endif; ?>
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
