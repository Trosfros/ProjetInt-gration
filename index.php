<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/configs/database.php';
require_once __DIR__ . '/configs/constants.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $email, string $username, string$token): void {
    $config = require __DIR__ . '/configs/config.php';$mail = new PHPMailer(true);

    try {
        $mail->isSMTP();$mail->Host = $config['mail']['host'];$mail->SMTPAuth = true;
        $mail->Username = $config['mail']['username'];$mail->Password = $config['mail']['password'];$mail->SMTPSecure = $config['mail']['smtp_secure'];$mail->Port = $config['mail']['port'];$mail->CharSet = 'UTF-8';

        $mail->setFrom($config['mail']['from_email'],$config['mail']['from_name']);
        $mail->addAddress($email, $username);$mail->Subject = 'Confirmez votre adresse email - NovaBank';

        $verificationUrl = rtrim($config['app_url'], '/') . '/index.php?' . http_build_query([
            'action' => 'verify',
            'token' => $token,
        ]);
        $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeVerificationUrl = htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $mail->isHTML(true);$mail->Body = "
            <div style=\"font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; max-width: 600px; margin: 0 auto;\">
                <h1 style=\"color: #0f172a; font-size: 24px;\">Bienvenue sur NovaBank, $safeUsername&nbsp;!</h1>
                <p>Merci d’avoir créé votre compte. Pour terminer votre inscription et accéder à votre espace, confirmez votre adresse email en cliquant sur le bouton ci-dessous.</p>
                <p style=\"margin: 28px 0;\">
                    <a href=\"$safeVerificationUrl\" style=\"display: inline-block; padding: 12px 20px; background-color: #0284c7; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;\">
                        Vérifier mon adresse email
                    </a>
                </p>
                <p>Si le bouton ne fonctionne pas, copiez ce lien dans la barre d’adresse de votre navigateur&nbsp;:<br>
                    <a href=\"$safeVerificationUrl\">$safeVerificationUrl</a>
                </p>
                <p style=\"color: #64748b; font-size: 14px;\">Si vous n’êtes pas à l’origine de cette inscription, vous pouvez ignorer ce message.</p>
            </div>";
        $mail->AltBody = "Bonjour $username,\r\n\r\nMerci d’avoir créé votre compte NovaBank. Pour terminer votre inscription, ouvrez ce lien dans votre navigateur :\r\n$verificationUrl\r\n\r\nSi vous n’êtes pas à l’origine de cette inscription, ignorez ce message.";

        $mail->send();
    } catch (Exception $e) {
        throw new RuntimeException('Impossible d’envoyer le mail de vérification : ' . $e->getMessage());
    }
}

$action =$_GET['action'] ?? '';
$isLoggedIn = !empty($_SESSION['user']);

if ($action === 'logout') {
    unset($_SESSION['user']);
    header('Location: index.php');
    exit;
}
else if ($action === 'market') {
    header('Location: index.php?action=investment');
    exit;
}
else if ($action === 'loginPage') {
    require __DIR__ . '/src/Models/login.php';
    exit;
}
else if ($action === 'registerPage') {
    require __DIR__ . '/src/Models/register.php';
    exit;
}
else if ($action === 'marketPage') {
    if (!$isLoggedIn) {
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/market.php';
    exit;
}
else if ($action === 'investment') {
    if (!$isLoggedIn) {
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/investment.php';
    exit;
}
else if ($action === 'accounts') {
    require __DIR__ . '/src/Models/AccountModel.php';
    exit;
}
else if ($action === 'admin') {
    if (!$isLoggedIn) {
        $_SESSION['redirect_after_login'] = $action;
        header('Location: index.php?action=loginPage');
        exit;
    }

    require_once __DIR__ . '/src/Middlewares/AdminMiddleware.php';
    if (!AdminMiddleware::isAdmin(getPDO(), (int) $_SESSION['user']['id'])) {
        http_response_code(403);
        $pageTitle = 'Accès refusé | NovaBank';
        require __DIR__ . '/views/errors/403.php';
        exit;
    }

    require __DIR__ . '/src/Models/admin.php';
    exit;
}
else if ($action === 'history') {
    if (!$isLoggedIn) {
        $_SESSION['redirect_after_login'] = $action;
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/history.php';
    exit;
}
else if ($action === 'transfers') {
    if (!$isLoggedIn) {
        $_SESSION['redirect_after_login'] = $action;
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/transfers.php';
    exit;
}
else if ($action === 'education') {
    if (!$isLoggedIn) {
        $_SESSION['redirect_after_login'] = 'education';
        header('Location: index.php?action=loginPage');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/src/Controllers/EducationController.php';

        try {
            $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (empty($_SESSION['education_csrf_token']) || !hash_equals($_SESSION['education_csrf_token'], $token)) {
                http_response_code(403);
                echo json_encode(['error' => 'Session expirée. Rechargez la page et réessayez.']);
                exit;
            }

            $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new InvalidArgumentException('Données invalides.');
            }

            $pdo = getPDO();
            $userId = (int) $_SESSION['user']['id'];
            if (($payload['type'] ?? '') === 'unlock') {
                EducationController::unlockLevel(
                    $pdo,
                    $userId,
                    filter_var($payload['moduleId'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                $result = ['success' => true];
            } elseif (($payload['type'] ?? '') === 'lesson') {
                EducationController::recordLesson(
                    $pdo,
                    $userId,
                    filter_var($payload['moduleId'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($payload['lessonNumber'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                $state = EducationController::getLevels($pdo, $userId);
                $result = ['success' => true, 'progress' => $state['progress'], 'achievements' => $state['achievements']];
                foreach ($state['levels'] as $level) {
                    if ((int) $level['id'] === (int) $payload['moduleId']) {
                        $result['levelProgress'] = $level['progress'];
                        $result['completedActivities'] = $level['completedActivities'];
                        $result['totalActivities'] = $level['totalActivities'];
                        break;
                    }
                }
            } elseif (($payload['type'] ?? '') === 'answer') {
                $result = EducationController::recordAnswer(
                    $pdo,
                    $userId,
                    filter_var($payload['questionId'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($payload['optionIndex'] ?? null, FILTER_VALIDATE_INT) !== false
                        ? (int) $payload['optionIndex']
                        : -1
                );
                $result['success'] = true;
            } else {
                throw new InvalidArgumentException('Action invalide.');
            }

            echo json_encode($result, JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (JsonException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides.']);
        } catch (Throwable $e) {
            error_log('index.php failed to update education progress: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Impossible d’enregistrer votre progression. Réessayez.']);
        }
        exit;
    }
}
else if ($action === 'verify') {
    $token = trim($_GET['token'] ?? '');

    if ($token !== '') {
        try {
            $pdo = getPDO();
            $stmt =$pdo->prepare('SELECT idUser FROM `User` WHERE `email_verification_token` = :token LIMIT 1');
            $stmt->execute(['token' =>$token]);
            $user =$stmt->fetch();

            if ($user) {
                $update =$pdo->prepare('UPDATE `User` SET `email_verified` = 1, `email_verification_token` = NULL WHERE `idUser` = :id');
                $update->execute(['id' => $user['idUser']]);$_SESSION['verify_success'] = 'Votre adresse email a bien été vérifiée. Vous pouvez maintenant vous connecter.';
            } else {
                $_SESSION['verify_error'] = 'Lien de vérification invalide ou déjà utilisé.';
            }
        } catch (Throwable $e) {$_SESSION['verify_error'] = 'Erreur lors de la vérification de l’email.';
        }
    } else {
        $_SESSION['verify_error'] = 'Aucun token de vérification fourni.';
    }

    header('Location: index.php?action=loginPage');
    exit;
}
else if ($_SERVER['REQUEST_METHOD'] === 'POST' &&$action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password =$_POST['password'] ?? '';

    if ($email === '' || $password === '') {$_SESSION['login_error'] = 'Veuillez renseigner votre email et votre mot de passe.';
        header('Location: index.php?action=loginPage');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt =$pdo->prepare('SELECT * FROM `User` WHERE `Description` = :email LIMIT 1');
        $stmt->execute(['email' =>$email]);
        $user =$stmt->fetch();

        if ($user && password_verify($password,$user['Password'])) {
            if ((int) ($user['email_verified'] ?? 0) !== 1) {$_SESSION['login_error'] = 'Veuillez vérifier votre adresse email avant de vous connecter.';
                header('Location: index.php?action=loginPage');
                exit;
            }

            $_SESSION['user'] = [
                'id' => $user['idUser'],
                'username' => $user['Alias'],
                'email' => $user['Description'],
                'isAdmin' => (int) ($user['IsAdmin'] ?? 0) === 1,
            ];
            $redirectAfterLogin = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            $redirectAction = in_array($redirectAfterLogin, ['education', 'history', 'transfers', 'admin'], true) ? $redirectAfterLogin : '';
            header('Location: ' . ($redirectAction !== '' ? 'index.php?action=' . $redirectAction : 'index.php'));
            exit;
        }

        $_SESSION['login_error'] = 'Identifiants invalides.';
        header('Location: index.php?action=loginPage');
        exit;
    } catch (Throwable $e) {$_SESSION['login_error'] = 'Erreur de connexion à la base de données.';
        header('Location: index.php?action=loginPage');
        exit;
    }
}
else if ($_SERVER['REQUEST_METHOD'] === 'POST' &&$action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password =$_POST['password'] ?? '';
    if ($username === '' || $email === '' || $password === '') {
        $_SESSION['register_error'] = 'Veuillez remplir tous les champs.';
        header('Location: index.php?action=registerPage');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {$_SESSION['register_error'] = 'Adresse email invalide.';
        header('Location: index.php?action=registerPage');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt =$pdo->prepare('SELECT idUser FROM `User` WHERE `Alias` = :username OR `Description` = :email LIMIT 1');
        $stmt->execute(['username' => $username, 'email' =>$email]);

        if ($stmt->fetch()) {$_SESSION['register_error'] = 'Ce nom d’utilisateur ou cet email existe déjà.';
            header('Location: index.php?action=registerPage');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);$token = bin2hex(random_bytes(32));
        $insert =$pdo->prepare('INSERT INTO `User` (`Alias`, `Description`, `Password`, `Balance`, `email_verified`, `email_verification_token`) VALUES (:username, :email, :password, 1500.00, 0, :token)');
        $insert->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hash,
            'token' => $token,
        ]);

        sendVerificationEmail($email, $username,$token);

        $_SESSION['register_success'] = 'Compte créé avec succès. Un email de vérification a été envoyé à votre adresse.';
        header('Location: index.php?action=loginPage');
        exit;
    } catch (Throwable $e) {$_SESSION['register_error'] = 'Erreur pendant la création du compte : ' . $e->getMessage();
        header('Location: index.php?action=registerPage');
        exit;
    }
}
else if ($action !== '' && $action !== 'dashboard' &&$action !== 'education') {
    http_response_code(404);
    $pageTitle = 'Page introuvable | NovaBank';
    require __DIR__ . '/views/errors/404.php';
    exit;
}

$educationModules = [];
$educationError = null;
$educationProgress = 0;
$educationAchievements = [];
$educationCsrfToken = '';
if ($isLoggedIn && $action === 'education') {
    require_once __DIR__ . '/src/Controllers/EducationController.php';
    try {
        if (empty($_SESSION['education_csrf_token'])) {
            $_SESSION['education_csrf_token'] = bin2hex(random_bytes(32));
        }
        $educationCsrfToken = $_SESSION['education_csrf_token'];
        $educationState = EducationController::getLevels(getPDO(), (int) $_SESSION['user']['id']);
        $educationModules = $educationState['levels'];
        $educationProgress = $educationState['progress'];
        $educationAchievements = $educationState['achievements'];
    } catch (Throwable $e) {
        error_log('index.php failed to load education content: ' . $e->getMessage());
        $educationError = 'Impossible de charger les parcours éducatifs pour le moment.';
    }
}

$pageTitle = ($action === 'education') ? 'Éducation financière | NovaBank' : 'NovaBank | Banque virtuelle & Simulateur de Bourse';
$pageStylesheets = !$isLoggedIn
    ? ['home.css']
    : ($action === 'education' ? ['dashboard.css', 'education.css'] : ['dashboard.css']);
$activePage = $action === 'education' ? 'education' : 'dashboard';
require __DIR__ . '/views/layout/header.php';

$dashboardData = null;
$dashboardError = null;
if ($isLoggedIn && ($action === '' || $action === 'dashboard')) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT u.Balance, si.idStock, si.Shares, si.Investement, s.Name, s.LastPrice
             FROM `User` u
             LEFT JOIN StockInvest si ON si.IdUser = u.idUser
             LEFT JOIN Stock s ON s.idStock = si.idStock
             WHERE u.idUser = :userId
             ORDER BY si.idStock'
        );
        $stmt->execute(['userId' => (int) $_SESSION['user']['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$rows) {
            throw new RuntimeException('No user found for the current session.');
        }

        $dashboardData = [
            'balance' => (float) $rows[0]['Balance'],
            'investmentValue' => 0.0,
            'investedAmount' => 0.0,
            'pricesAvailable' => true,
        ];
        $marketPriceBySymbol = [];
        foreach ($marketData as $stock) {
            if (isset($stock['symbol'], $stock['price']) && is_numeric($stock['price']) && (float) $stock['price'] > 0) {
                $marketPriceBySymbol[strtoupper((string) $stock['symbol'])] = (float) $stock['price'];
            }
        }

        foreach ($rows as $row) {
            if ($row['idStock'] === null) {
                continue;
            }

            $dashboardData['investedAmount'] += (float) $row['Investement'];
            $symbol = strtoupper((string) $row['Name']);
            $currentPrice = $marketPriceBySymbol[$symbol] ?? (
                $row['LastPrice'] === null ? null : (float) $row['LastPrice']
            );
            if ($currentPrice === null || $currentPrice <= 0) {
                $dashboardData['pricesAvailable'] = false;
                continue;
            }

            $dashboardData['investmentValue'] += (float) $row['Shares'] * $currentPrice;
        }

        $dashboardData['performance'] = $dashboardData['pricesAvailable'] && $dashboardData['investedAmount'] > 0
            ? (($dashboardData['investmentValue'] - $dashboardData['investedAmount']) / $dashboardData['investedAmount']) * 100
            : null;
        $dashboardData['unrealizedPnl'] = $dashboardData['pricesAvailable']
            ? $dashboardData['investmentValue'] - $dashboardData['investedAmount']
            : null;
    } catch (Throwable $e) {
        error_log('index.php failed to load dashboard data: ' . $e->getMessage());
        $dashboardError = 'Impossible de charger les données du compte pour le moment.';
    }
}
?>

<?php if ($isLoggedIn): ?>
    <div class="dashboard-layout">
        <?php require __DIR__ . '/views/layout/sidebar.php'; ?>

        <main class="dashboard-main" aria-label="Espace principal">
            <?php if ($action === 'education'): ?>
                <!-- Inclusion de la vue éducation dans le conteneur du tableau de bord -->
                <?php require __DIR__ . '/src/Models/education.php'; ?>
            <?php else: ?>
                <!-- Tableau de bord par défaut -->
                <header class="dashboard-header" style="margin-bottom: 28px;">
                    <h1 style="color: #ffffff; font-size: 1.8rem; margin: 0 0 6px;">
                        Bonjour, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'investisseur') ?> 👋
                    </h1>
                    <p style="color: #94a3b8; margin: 0; font-size: 0.95rem;">
                        Bienvenue sur votre espace NovaBank. Voici un résumé de votre compte.
                    </p>
                </header>

                <section class="dashboard-summary-cards" id="dashboard-summary-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 30px;">
                    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 20px;">
                        <div class="dashboard-balance-heading">
                            <span style="color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Solde disponible</span>
                            <button
                                class="balance-visibility-toggle"
                                type="button"
                                aria-label="Masquer les soldes et les investissements"
                                aria-pressed="false"
                                aria-controls="dashboard-summary-cards"
                                data-privacy-storage-key="novabank-dashboard-privacy-<?= (int) $_SESSION['user']['id'] ?>"
                                title="Masquer les soldes et les investissements"
                            >
                                <svg class="balance-eye-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path class="balance-eye-slash" d="m4 4 16 16"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="dashboard-balance-value" id="dashboard-balance-value" style="color: #38bdf8; font-size: 2rem; font-weight: 700; margin-top: 6px;">
                            <span class="privacy-value-visible"><?= $dashboardData === null ? '—' : htmlspecialchars(number_format($dashboardData['balance'], 2, ',', ' ') . ' $', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="privacy-value-hidden" aria-label="Solde masqué" hidden>••••••</span>
                        </div>
                        <span style="color: #94a3b8; font-size: 0.85rem; font-weight: 500;">Solde actuel du compte</span>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 20px;">
                        <span style="color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Investissements en cours</span>
                        <div style="color: #ffffff; font-size: 2rem; font-weight: 700; margin-top: 6px;">
                            <span class="privacy-value-visible"><?php if ($dashboardData === null || !$dashboardData['pricesAvailable']): ?><?= $dashboardData === null ? '—' : 'Indisponible' ?><?php else: ?><?= htmlspecialchars(number_format($dashboardData['investmentValue'], 2, ',', ' ') . ' $', ENT_QUOTES, 'UTF-8') ?><?php endif; ?></span>
                            <span class="privacy-value-hidden" aria-label="Investissements masqués" hidden>••••••</span>
                        </div>
                        <span class="privacy-value-visible" style="color: #94a3b8; font-size: 0.85rem;">
                            <?= $dashboardData !== null && !$dashboardData['pricesAvailable'] ? 'Cours manquant pour une ou plusieurs positions' : 'Valeur actuelle des positions ouvertes' ?>
                        </span>
                        <span class="privacy-value-hidden" style="color: #94a3b8; font-size: 0.85rem;" aria-label="Détails des investissements masqués" hidden>••••••</span>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 20px;">
                        <span style="color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Performance des positions</span>
                        <div style="color: <?= $dashboardData !== null && $dashboardData['performance'] !== null && $dashboardData['performance'] < 0 ? '#f87171' : '#22c55e' ?>; font-size: 2rem; font-weight: 700; margin-top: 6px;">
                            <span class="privacy-value-visible"><?= $dashboardData === null || $dashboardData['performance'] === null ? '—' : htmlspecialchars(number_format($dashboardData['performance'], 2, ',', ' ') . ' %', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="privacy-value-hidden" aria-label="Performance masquée" hidden>••••••</span>
                        </div>
                        <span class="privacy-value-visible" style="color: #94a3b8; font-size: 0.85rem;">
                            <?php if ($dashboardError !== null): ?>
                                <?= htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8') ?>
                            <?php elseif ($dashboardData !== null && $dashboardData['unrealizedPnl'] !== null && $dashboardData['investedAmount'] > 0): ?>
                                Gain/perte latent(e) : <?= htmlspecialchars(number_format($dashboardData['unrealizedPnl'], 2, ',', ' ') . ' $', ENT_QUOTES, 'UTF-8') ?>
                            <?php elseif ($dashboardData !== null && !$dashboardData['pricesAvailable']): ?>
                                Performance indisponible : cours manquant
                            <?php else: ?>
                                Aucune position ouverte
                            <?php endif; ?>
                        </span>
                        <span class="privacy-value-hidden" style="color: #94a3b8; font-size: 0.85rem;" aria-label="Détails de performance masqués" hidden>••••••</span>
                    </div>
                </section>

                <script>
                    const setDashboardPrivacy = (toggle, isHidden) => {
                        const summary = document.getElementById(toggle.getAttribute('aria-controls'));
                        summary.querySelectorAll('.privacy-value-visible').forEach((value) => {
                            value.hidden = isHidden;
                        });
                        summary.querySelectorAll('.privacy-value-hidden').forEach((value) => {
                            value.hidden = !isHidden;
                        });
                        toggle.setAttribute('aria-pressed', String(isHidden));
                        toggle.setAttribute('aria-label', isHidden ? 'Afficher les soldes et les investissements' : 'Masquer les soldes et les investissements');
                        toggle.setAttribute('title', isHidden ? 'Afficher les soldes et les investissements' : 'Masquer les soldes et les investissements');
                    };

                    document.querySelectorAll('.balance-visibility-toggle').forEach((toggle) => {
                        const storageKey = toggle.getAttribute('data-privacy-storage-key');
                        try {
                            setDashboardPrivacy(toggle, localStorage.getItem(storageKey) === 'hidden');
                        } catch (error) {
                            setDashboardPrivacy(toggle, false);
                        }

                        toggle.addEventListener('click', () => {
                            const isHidden = toggle.getAttribute('aria-pressed') !== 'true';
                            setDashboardPrivacy(toggle, isHidden);
                            try {
                                localStorage.setItem(storageKey, isHidden ? 'hidden' : 'visible');
                            } catch (error) {
                                // La visibilité reste active même si le navigateur bloque le stockage local.
                            }
                        });
                    });
                </script>

                <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 22px;">
                        <h3 style="color: #ffffff; font-size: 1.15rem; margin-top: 0;">Simulateur de Bourse</h3>
                        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Consultez les cours réels de Wall Street et passez vos premiers ordres d'achat ou vente.</p>
                        <a href="index.php?action=marketPage" class="btn-primary" style="display: inline-block; padding: 10px 18px; text-decoration: none; border-radius: 6px; font-weight: 600;">Accéder au Marché</a>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 22px;">
                        <h3 style="color: #ffffff; font-size: 1.15rem; margin-top: 0;">Académie & Éducation</h3>
                        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Apprenez les principes de gestion du risque, la lecture des graphiques et les stratégies financières.</p>
                        <a href="index.php?action=education" class="btn-secondary" style="display: inline-block; padding: 10px 18px; text-decoration: none; border-radius: 6px; font-weight: 600;">Consulter les Guides</a>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
<?php else: ?>
    <!-- Page d'accueil publique -->
    <main class="main-content">
        <section class="home-hero" aria-labelledby="hero-title" style="position: relative; width: 100vw; margin-left: calc(-50vw + 50%); min-height: 85vh; display: flex; align-items: center; justify-content: space-between; padding: 60px 8%; box-sizing: border-box; overflow: hidden;">
            <video autoplay muted loop playsinline style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 1; pointer-events: none;">
                <source src="public/video/invest.mp4" type="video/mp4">
                Votre navigateur ne supporte pas la lecture vidéo.
            </video>

            <div style="position: absolute; inset: 0; background: rgba(10, 15, 29, 0.65); z-index: 2; pointer-events: none;"></div>

            <div class="home-copy" style="position: relative; z-index: 10; max-width: 580px; color: #ffffff;">
                <div class="reveal-wrapper">
                    <span class="home-eyebrow reveal-text delay-1" style="font-weight: 600; text-transform: uppercase; letter-spacing: 1px; display: inline-block; margin-bottom: 8px;">
                        NovaBank
                    </span>
                </div>

                <h1 id="hero-title" style="color: #ffffff; font-size: 2.75rem; line-height: 1.25; margin-bottom: 16px;">
                    <span class="reveal-wrapper">
                        <span class="reveal-text delay-2" style="color: #ffffff;">La banque simple,</span>
                    </span>
                    <br>
                    <span class="reveal-wrapper">
                        <span class="reveal-text delay-3" style="color: #38bdf8;">pour votre quotidien.</span>
                    </span>
                </h1>

                <div class="reveal-wrapper">
                    <p class="hero-lead reveal-text delay-4" style="color: #e2e8f0; font-size: 1.15rem; line-height: 1.6; margin-bottom: 24px;">
                        Gérez votre compte, suivez vos finances et découvrez l'investissement depuis un seul espace.
                    </p>
                </div>

                <div class="hero-buttons reveal-fade delay-5" style="display: flex; gap: 14px; margin-bottom: 20px;">
                    <a href="index.php?action=registerPage" class="btn-primary btn-large">Ouvrir un compte</a>
                    <a href="index.php?action=loginPage" class="btn-secondary btn-large">Se connecter</a>
                </div>

                <div class="reveal-wrapper">
                    <p class="home-note reveal-text delay-6" style="color: #94a3b8; font-size: 0.9rem;">
                        Simple à utiliser · Accessible en ligne · Pensée pour apprendre
                    </p>
                </div>
            </div>

            <aside class="home-side-panel reveal-fade delay-4" aria-label="Marchés en direct" style="position: relative; z-index: 10; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); padding: 24px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); width: 340px;">
                <div class="reveal-wrapper">
                    <span class="side-panel-label reveal-text delay-5" style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
                        Marchés en direct
                    </span>
                </div>

                <div class="reveal-wrapper" style="display: block;">
                    <h2 class="reveal-text delay-6" style="color: #ffffff; font-size: 1.3rem; margin: 6px 0 16px;">
                        Les valeurs du moment
                    </h2>
                </div>

                <div class="market-preview-list">
                    <?php if (!empty($marketData)): ?>
                        <?php $delayIndex = 7; ?>
                        <?php foreach (array_slice($marketData, 0, 3) as$stock): ?>
                            <div class="market-preview-row reveal-fade delay-<?= min($delayIndex++, 7) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: #ffffff;">
                                <strong><?= htmlspecialchars($stock['symbol']) ?></strong>
                                <span>$<?= htmlspecialchars($stock['price']) ?></span>
                                <span class="market-preview-change <?= $stock['change'] >= 0 ? 'up' : 'down' ?>" style="color: <?= $stock['change'] >= 0 ? '#4ade80' : '#f87171' ?>; font-weight: 600;">
                                    <?= $stock['change'] >= 0 ? '+' : '' ?><?= htmlspecialchars($stock['change']) ?>%
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="market-preview-empty reveal-fade delay-6" style="color: #94a3b8; font-size: 0.9rem;">
                            Les données de marché seront disponibles après la configuration de l'API.
                        </p>
                    <?php endif; ?>
                </div>

                <p class="security-note reveal-fade delay-7" style="margin-top: 16px; color: #94a3b8; font-size: 0.85rem;">
                    <span class="security-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #22c55e; margin-right: 6px;"></span> Serveur opérationnel.
                </p>
            </aside>
        </section>

        <section class="features" id="home-basics" aria-label="Services NovaBank">
            <div class="features-head">
                <div class="features-head-left">
                    <h2 class="scroll-reveal delay-2">
                        Tout ce qu'il vous faut pour <br>
                        <span style="color: #0284c7;">maîtriser votre capital.</span>
                    </h2>
                </div>
                <p class="scroll-reveal delay-3">
                    Une interface conçue pour combiner la simplicité d'un compte courant moderne et la puissance d'un terminal d'apprentissage boursier.
                </p>
            </div>

            <div class="features-grid">
                <article class="feat-card scroll-reveal delay-1">
                    <div class="feat-icon ic-bank">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                    <div class="feat-content">
                        <h3>Compte & Solde en direct</h3>
                        <p>Gardez un œil sur votre solde virtuel de départ (1 500 $ offerts), effectuez des virements et suivez l'historique complet de vos opérations en temps réel.</p>
                    </div>
                    <a href="index.php?action=registerPage" class="feat-link">
                        Ouvrir un compte 
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </article>

                <article class="feat-card scroll-reveal delay-2">
                    <div class="feat-icon ic-market">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                    </div>
                    <div class="feat-content">
                        <h3>Simulateur de Bourse</h3>
                        <p>Achetez et vendez des actions avec les cotations réelles de Wall Street. Testez vos stratégies d'investissement sans risquer le moindre dollar réel.</p>
                    </div>
                    <a href="index.php?action=registerPage" class="feat-link">
                        Explorer le marché 
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </article>

                <article class="feat-card scroll-reveal delay-3">
                    <div class="feat-icon ic-shield">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="feat-content">
                        <h3>Sécurité & Éducation</h3>
                        <p>Comprenez la volatilité, la composition d'un portefeuille et les bases de la finance dans un environnement totalement sécurisé et authentifié.</p>
                    </div>
                    <a href="index.php?action=education" class="feat-link">
                        Découvrir les guides 
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </article>
            </div>
        </section>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>