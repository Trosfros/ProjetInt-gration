<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Controllers/AdminController.php';
require_once $projectRoot . '/src/Models/NotificationModel.php';

$adminController = new AdminController(getPDO());
$adminUsers = [];
$adminError = null;
$adminActionError = null;
$adminSuccess = $_SESSION['admin_balance_success'] ?? null;
$adminMessageSuccess = $_SESSION['admin_message_success'] ?? null;
$adminEducationSuccess = $_SESSION['admin_education_success'] ?? null;
unset($_SESSION['admin_balance_success'], $_SESSION['admin_message_success'], $_SESSION['admin_education_success']);
$adminMessageTitle = is_string($_POST['message_title'] ?? null) ? trim($_POST['message_title']) : '';
$adminMessageBody = is_string($_POST['message_body'] ?? null) ? trim($_POST['message_body']) : '';
$adminEducationModuleId = is_scalar($_POST['education_module_id'] ?? null) ? (string) $_POST['education_module_id'] : '';
$adminEducationQuestion = is_string($_POST['education_question'] ?? null) ? trim($_POST['education_question']) : '';
$adminEducationExplanation = is_string($_POST['education_explanation'] ?? null) ? trim($_POST['education_explanation']) : '';
$adminEducationOptions = is_array($_POST['education_options'] ?? null)
    ? array_values($_POST['education_options'])
    : ['', '', '', ''];
$adminEducationCorrect = is_scalar($_POST['education_correct'] ?? null) ? (int) $_POST['education_correct'] : 0;
$educationModules = [];
$educationQuestions = [];
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
            $formAction = (string) ($_POST['form_action'] ?? 'adjust_balance');
            if ($formAction === 'delete_education_question') {
                $questionId = filter_var($_POST['education_question_id'] ?? null, FILTER_VALIDATE_INT);
                if ($questionId === false) {
                    throw new InvalidArgumentException('Question éducative invalide.');
                }

                $adminController->deleteEducationQuestion((int) $questionId);
                $_SESSION['admin_education_success'] = 'La question éducative a été supprimée.';
                header('Location: index.php?action=admin');
                exit;
            }

            if ($formAction === 'add_education_question') {
                $moduleId = filter_var($_POST['education_module_id'] ?? null, FILTER_VALIDATE_INT);
                if ($moduleId === false) {
                    throw new InvalidArgumentException('Veuillez sélectionner un niveau éducatif.');
                }

                $optionTexts = is_array($_POST['education_options'] ?? null)
                    ? array_values($_POST['education_options'])
                    : [];
                $correctOption = filter_var($_POST['education_correct'] ?? null, FILTER_VALIDATE_INT);
                if ($correctOption === false) {
                    throw new InvalidArgumentException('Veuillez indiquer la bonne réponse.');
                }

                $adminController->addEducationQuestion(
                    (int) $moduleId,
                    $adminEducationQuestion,
                    $adminEducationExplanation,
                    $optionTexts,
                    [$correctOption]
                );
                $_SESSION['admin_education_success'] = 'La question éducative a été ajoutée au niveau sélectionné.';
                header('Location: index.php?action=admin');
                exit;
            }

            if ($formAction === 'publish_admin_message') {
                if ($adminMessageTitle === '' || $adminMessageBody === '') {
                    throw new InvalidArgumentException('Le titre et le message sont obligatoires.');
                }
                if (mb_strlen($adminMessageTitle, 'UTF-8') > 150 || mb_strlen($adminMessageBody, 'UTF-8') > 5000) {
                    throw new InvalidArgumentException('Le titre doit contenir au plus 150 caractères et le message au plus 5 000 caractères.');
                }

                $recipientCount = (new NotificationModel())->broadcastAdminMessage($adminMessageTitle, $adminMessageBody);
                if ($recipientCount === 0) {
                    throw new InvalidArgumentException('Aucun compte vérifié n’est disponible pour recevoir ce message.');
                }

                $_SESSION['admin_message_success'] = 'Le message a été envoyé à ' . $recipientCount . ' utilisateur(s).';
                header('Location: index.php?action=admin');
                exit;
            }

            if ($formAction !== 'adjust_balance') {
                throw new InvalidArgumentException('Action invalide.');
            }

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
            error_log('admin.php failed to process an admin action: ' . $e->getMessage());
            $adminActionError = 'Impossible de traiter l’action d’administration pour le moment.';
        }
    }
}

try {
    $adminUsers = $adminController->getUsers();
} catch (Throwable $e) {
    error_log('admin.php failed to load users: ' . $e->getMessage());
    $adminError = 'Impossible de charger la liste des utilisateurs pour le moment.';
}

try {
    $educationModules = $adminController->getEducationModules();
    $educationQuestions = $adminController->getEducationQuestions();
} catch (Throwable $e) {
    error_log('admin.php failed to load education content: ' . $e->getMessage());
    $adminActionError = $adminActionError ?? 'Impossible de charger la section éducative pour le moment.';
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
        <?php if ($adminMessageSuccess !== null): ?>
            <div class="admin-success" role="status"><?= htmlspecialchars((string) $adminMessageSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($adminEducationSuccess !== null): ?>
            <div class="admin-success" role="status"><?= htmlspecialchars((string) $adminEducationSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($adminActionError !== null): ?>
            <div class="admin-alert" role="alert"><?= htmlspecialchars($adminActionError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="admin-adjustment-section admin-message-section" aria-labelledby="admin-message-title">
            <div class="admin-section-heading admin-message-heading">
                <span class="admin-message-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 5.75A2.75 2.75 0 0 1 7.75 3h8.5A2.75 2.75 0 0 1 19 5.75v6.5A2.75 2.75 0 0 1 16.25 15H11l-4.5 4v-4.18A2.75 2.75 0 0 1 5 12.25z"/></svg>
                </span>
                <div>
                    <p class="admin-message-kicker">Communication</p>
                    <h2 id="admin-message-title">Envoyer un message aux utilisateurs</h2>
                    <p>Rédigez une annonce pour le centre de notifications NovaBank.</p>
                </div>
            </div>
            <form class="admin-adjustment-form admin-message-form" id="adminMessageForm" method="post" action="index.php?action=admin">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="publish_admin_message">
                <label class="admin-message-template-field">
                    <span>Modèle <span class="admin-message-optional">facultatif</span></span>
                    <select id="adminMessageTemplate">
                        <option value="">Choisir un modèle…</option>
                        <option value="maintenance">Maintenance planifiée</option>
                        <option value="security">Conseil de sécurité</option>
                        <option value="update">Mise à jour du service</option>
                    </select>
                </label>
                <label class="admin-message-title-field">
                    <span>Titre</span>
                    <input id="adminMessageTitle" type="text" name="message_title" maxlength="150" value="<?= htmlspecialchars($adminMessageTitle, ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="admin-adjustment-reason">
                    <span>Message</span>
                    <textarea id="adminMessageBody" name="message_body" maxlength="5000" rows="5" required><?= htmlspecialchars($adminMessageBody, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <small class="admin-message-hint">Votre message apparaîtra dans le centre de notifications de vos utilisateurs.</small>
                </label>
                <div class="admin-message-footer">
                    <p class="admin-message-audience"><span aria-hidden="true"></span>Envoyé à tous les comptes vérifiés</p>
                    <button class="admin-adjustment-submit" type="submit">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        Vérifier l’annonce
                    </button>
                </div>
            </form>
            <section class="admin-message-confirmation" id="adminMessageConfirmation" hidden role="region" aria-live="polite" aria-labelledby="adminMessageConfirmationTitle">
                <div class="admin-message-confirmation-heading">
                    <span class="admin-message-confirmation-icon" aria-hidden="true">✓</span>
                    <div>
                        <p class="admin-message-kicker">Dernière vérification</p>
                        <h3 id="adminMessageConfirmationTitle">Prêt à envoyer cette annonce ?</h3>
                        <p>Elle sera envoyée à tous les comptes vérifiés.</p>
                    </div>
                </div>
                <div class="admin-message-preview">
                    <strong id="adminMessagePreviewTitle"></strong>
                    <p id="adminMessagePreviewBody"></p>
                </div>
                <div class="admin-message-confirmation-actions">
                    <button class="admin-message-edit-button" id="adminMessageEdit" type="button">Modifier le message</button>
                    <button class="admin-adjustment-submit admin-message-confirm-submit" id="adminMessageConfirm" type="button">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/></svg>
                        Confirmer l’envoi
                    </button>
                </div>
            </section>
        </section>
        <script>
            (() => {
                const form = document.getElementById('adminMessageForm');
                const templateSelect = document.getElementById('adminMessageTemplate');
                const titleInput = document.getElementById('adminMessageTitle');
                const bodyInput = document.getElementById('adminMessageBody');
                const confirmationPanel = document.getElementById('adminMessageConfirmation');
                const previewTitle = document.getElementById('adminMessagePreviewTitle');
                const previewBody = document.getElementById('adminMessagePreviewBody');
                const editButton = document.getElementById('adminMessageEdit');
                const confirmButton = document.getElementById('adminMessageConfirm');
                if (!form || !templateSelect || !titleInput || !bodyInput
                    || !confirmationPanel || !previewTitle || !previewBody
                    || !editButton || !confirmButton) return;

                const templates = {
                    maintenance: {
                        title: 'Maintenance planifiée',
                        body: 'Une maintenance de NovaBank est prévue prochainement. Certaines fonctionnalités pourraient être temporairement indisponibles. Merci de votre compréhension.'
                    },
                    security: {
                        title: 'Conseil de sécurité',
                        body: 'Pour protéger votre compte, ne partagez jamais votre mot de passe ni vos codes de vérification. Si vous remarquez une activité inhabituelle, contactez notre équipe.'
                    },
                    update: {
                        title: 'Mise à jour de NovaBank',
                        body: 'NovaBank a été mis à jour afin d’améliorer votre expérience. Connectez-vous à votre compte pour découvrir les améliorations.'
                    }
                };

                templateSelect.addEventListener('change', () => {
                    const template = templates[templateSelect.value];
                    if (!template) return;

                    titleInput.value = template.title;
                    bodyInput.value = template.body;
                    confirmationPanel.hidden = true;
                });

                form.addEventListener('input', () => {
                    confirmationPanel.hidden = true;
                });

                let confirmed = false;
                form.addEventListener('submit', (event) => {
                    if (confirmed) {
                        confirmed = false;
                        return;
                    }

                    event.preventDefault();
                    previewTitle.textContent = titleInput.value.trim();
                    previewBody.textContent = bodyInput.value.trim();
                    confirmationPanel.hidden = false;
                    confirmationPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    confirmButton.focus({ preventScroll: true });
                });

                editButton.addEventListener('click', () => {
                    confirmationPanel.hidden = true;
                    titleInput.focus();
                });

                confirmButton.addEventListener('click', () => {
                    if (!form.reportValidity()) return;

                    confirmed = true;
                    form.requestSubmit();
                });
            })();
        </script>

        <section class="admin-adjustment-section admin-education-section" aria-labelledby="admin-education-title">
            <div class="admin-section-heading admin-education-heading">
                <div>
                    <p class="admin-message-kicker">Section éducative</p>
                    <h2 id="admin-education-title">Ajouter une question au parcours</h2>
                    <p>Créez une question de quiz avec une explication pour aider les utilisateurs.</p>
                </div>
            </div>
            <form class="admin-education-form" method="post" action="index.php?action=admin" id="adminEducationForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="add_education_question">

                <label class="admin-education-module-field">
                    <span>Niveau éducatif</span>
                    <select name="education_module_id" required>
                        <option value="">Sélectionner un niveau</option>
                        <?php foreach ($educationModules as $module): ?>
                            <option value="<?= (int) $module['idModule'] ?>"<?= $adminEducationModuleId === (string) $module['idModule'] ? ' selected' : '' ?>>
                                <?= htmlspecialchars((string) $module['Title'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="admin-education-question-field">
                    <span>Question</span>
                    <input type="text" name="education_question" maxlength="500" value="<?= htmlspecialchars($adminEducationQuestion, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. Que représente une action en bourse ?" required>
                </label>

                <div class="admin-education-options-field">
                    <div class="admin-education-options-heading">
                        <span>Choix de réponse</span>
                        <small>Sélectionnez le bouton correspondant à la bonne réponse.</small>
                    </div>
                    <div class="admin-education-options" id="adminEducationOptions">
                        <?php for ($optionIndex = 0; $optionIndex < 3; $optionIndex++): ?>
                            <div class="admin-education-option">
                                <label class="admin-education-correct">
                                    <input type="radio" name="education_correct" value="<?= $optionIndex ?>"<?= $adminEducationCorrect === $optionIndex ? ' checked' : '' ?>>
                                    <span>Bonne réponse</span>
                                </label>
                                <input type="text" name="education_options[]" maxlength="500" value="<?= htmlspecialchars((string) ($adminEducationOptions[$optionIndex] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Choix <?= $optionIndex + 1 ?>" required>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button type="button" class="admin-education-add-option" id="adminEducationAddOption">+ Ajouter un autre choix</button>
                </div>

                <label class="admin-education-explanation-field">
                    <span>Explication</span>
                    <textarea name="education_explanation" maxlength="5000" rows="4" placeholder="Expliquez pourquoi cette réponse est correcte." required><?= htmlspecialchars($adminEducationExplanation, ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>

                <div class="admin-education-footer">
                    <p><?= count($educationQuestions) ?> question(s) affichée(s) récemment</p>
                    <button class="admin-adjustment-submit" type="submit">Ajouter la question</button>
                </div>
            </form>

            <?php if ($educationQuestions !== []): ?>
                <div class="admin-education-recent">
                    <h3>Questions récemment ajoutées</h3>
                    <div class="admin-education-question-list">
                        <?php foreach ($educationQuestions as $educationQuestion): ?>
                            <article>
                                <div>
                                    <span><?= htmlspecialchars((string) $educationQuestion['ModuleTitle'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= htmlspecialchars((string) $educationQuestion['Question'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="admin-education-question-actions">
                                    <span><?= count(is_array($educationQuestion['Options']) ? $educationQuestion['Options'] : []) ?> choix</span>
                                    <form method="post" action="index.php?action=admin" class="admin-education-delete-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="form_action" value="delete_education_question">
                                        <input type="hidden" name="education_question_id" value="<?= (int) $educationQuestion['idQuestion'] ?>">
                                        <button type="submit" class="admin-education-delete-button" title="Supprimer cette question">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V3h8v3M19 6l-1 15H6L5 6M10 10v7M14 10v7"/></svg>
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        <script>
            (() => {
                const options = document.getElementById('adminEducationOptions');
                const addButton = document.getElementById('adminEducationAddOption');
                if (!options || !addButton) return;

                addButton.addEventListener('click', () => {
                    const count = options.querySelectorAll('.admin-education-option').length;
                    if (count >= 6) {
                        addButton.disabled = true;
                        return;
                    }

                    const option = document.createElement('div');
                    option.className = 'admin-education-option';
                    option.innerHTML = `
                        <label class="admin-education-correct">
                            <input type="radio" name="education_correct" value="${count}">
                            <span>Bonne réponse</span>
                        </label>
                        <input type="text" name="education_options[]" maxlength="500" placeholder="Choix ${count + 1}" required>
                        <button type="button" class="admin-education-remove-option" aria-label="Retirer ce choix">×</button>
                    `;
                    options.appendChild(option);
                    if (count + 1 >= 6) addButton.disabled = true;
                });

                options.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('.admin-education-remove-option');
                    if (!removeButton) return;
                    const optionCount = options.querySelectorAll('.admin-education-option').length;
                    if (optionCount <= 2) return;
                    removeButton.closest('.admin-education-option').remove();

                    options.querySelectorAll('.admin-education-option').forEach((option, index) => {
                        const radio = option.querySelector('input[type="radio"]');
                        const text = option.querySelector('input[type="text"]');
                        radio.value = String(index);
                        text.placeholder = `Choix ${index + 1}`;
                    });
                    addButton.disabled = false;
                });

                document.querySelectorAll('.admin-education-delete-form').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (!window.confirm('Supprimer définitivement cette question éducative ?')) {
                            event.preventDefault();
                        }
                    });
                });
            })();
        </script>

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
                    <strong><?= renderCurrencyAmount($totalUserBalance, 'CAD') ?></strong>
                    <small>Soldes enregistrés en CAD, convertis pour l’affichage</small>
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
                                        (<?= number_format((float) ($user['Balance'] ?? 0), 2, ',', ' ') ?> CAD)
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
                                        <td class="admin-balance-column"><?= renderCurrencyAmount((float) ($user['Balance'] ?? 0), 'CAD') ?></td>
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
