<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/configs/database.php';
require_once __DIR__. '/configs/constants.php';
/*
require __DIR__ . '/src/Middlewares/FinnhubMiddleware.php';


$finnhub = new FinnhubMiddleware();
//interaval in seconds

$finnhub->startPolling(60);
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $email, string $username, string $token): void {
    $config = require __DIR__ . '/configs/config.php';
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['mail']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['mail']['username'];
        $mail->Password = $config['mail']['password'];
        $mail->SMTPSecure = $config['mail']['smtp_secure'];
        $mail->Port = $config['mail']['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['mail']['from_email'], $config['mail']['from_name']);
        $mail->addAddress($email, $username);
        $mail->Subject = 'Vérification de votre compte NovaBank';

        $verificationUrl = $config['app_url'] . '/index.php?action=verify&token=' . urlencode($token);
        $mail->Body = "Bonjour $username,\r\n\r\nMerci de vous être inscrit sur NovaBank.\r\nCliquez sur le lien ci-dessous pour vérifier votre adresse email :\r\n$verificationUrl\r\n\r\nSi vous n'êtes pas à l'origine de cette inscription, ignorez ce message.";
        $mail->AltBody = "Bonjour $username,\r\n\r\nMerci de vous être inscrit sur NovaBank.\r\nUtilisez ce lien pour vérifier votre email : $verificationUrl";

        $mail->send();
    } catch (Exception $e) {
        throw new RuntimeException('Impossible d’envoyer le mail de vérification : ' . $e->getMessage());
    }
}

$action = $_GET['action'] ?? '';

$knownActions = [
    'logout',
    'market',
    'loginPage',
    'registerPage',
    'marketPage',
    'investment',
    'verify',
    'login',
    'register',
];

if ($action !== '' && !in_array($action, $knownActions, true)) {
    http_response_code(404);
    $pageTitle = 'Page introuvable | NovaBank';
    require __DIR__ . '/views/errors/404.php';
    exit;
}

if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['user']);
    header('Location: index.php');
    exit;
}

if (($_GET['action'] ?? '') === 'market') {
    header('Location: index.php?action=investment');
    exit;
}

if (($_GET['action'] ?? '') === 'loginPage') {
    require __DIR__ . '/src/Models/login.php';
    exit;
}

if (($_GET['action'] ?? '') === 'registerPage') {
    require __DIR__ . '/src/Models/register.php';
    exit;
}

if (($_GET['action'] ?? '') === 'marketPage') {
    if (empty($_SESSION['user'])) {
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/market.php';
    exit;
}

if (($_GET['action'] ?? '') === 'investment') {
    if (empty($_SESSION['user'])) {
        header('Location: index.php?action=loginPage');
        exit;
    }

    require __DIR__ . '/src/Models/investment.php';
    exit;
}

if (($_GET['action'] ?? '') === 'verify') {
    $token = trim($_GET['token'] ?? '');

    if ($token !== '') {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT idUser FROM `User` WHERE `email_verification_token` = :token LIMIT 1');
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();

            if ($user) {
                $update = $pdo->prepare('UPDATE `User` SET `email_verified` = 1, `email_verification_token` = NULL WHERE `idUser` = :id');
                $update->execute(['id' => $user['idUser']]);
                $_SESSION['verify_success'] = 'Votre adresse email a bien été vérifiée. Vous pouvez maintenant vous connecter.';
            } else {
                $_SESSION['verify_error'] = 'Lien de vérification invalide ou déjà utilisé.';
            }
        } catch (Throwable $e) {
            $_SESSION['verify_error'] = 'Erreur lors de la vérification de l’email.';
        }
    } else {
        $_SESSION['verify_error'] = 'Aucun token de vérification fourni.';
    }

    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Veuillez renseigner votre email et votre mot de passe.';
        header('Location: index.php?action=loginPage');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM `User` WHERE `Description` = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            if ((int)($user['email_verified'] ?? 0) !== 1) {
                $_SESSION['login_error'] = 'Veuillez vérifier votre adresse email avant de vous connecter.';
                header('Location: index.php?action=loginPage');
                exit;
            }

            $_SESSION['user'] = [
                'id' => $user['idUser'],
                'username' => $user['Alias'],
                'email' => $user['Description'],
            ];
            header('Location: index.php');
            exit;
        }

        $_SESSION['login_error'] = 'Identifiants invalides.';
        header('Location: index.php?action=loginPage');
        exit;
    } catch (Throwable $e) {
        $_SESSION['login_error'] = 'Erreur de connexion à la base de données.';
        header('Location: index.php?action=loginPage');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $_SESSION['register_error'] = 'Tous les champs sont requis.';
        header('Location: index.php?action=registerPage');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Adresse email invalide.';
        header('Location: index.php?action=registerPage');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT idUser FROM `User` WHERE `Alias` = :username OR `Description` = :email LIMIT 1');
        $stmt->execute(['username' => $username, 'email' => $email]);

        if ($stmt->fetch()) {
            $_SESSION['register_error'] = 'Ce nom d’utilisateur ou cet email existe déjà.';
            header('Location: index.php?action=registerPage');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $insert = $pdo->prepare('INSERT INTO `User` (`Alias`, `Description`, `Password`, `Balance`, `email_verified`, `email_verification_token`) VALUES (:username, :email, :password, 1500.00, 0, :token)');
        $insert->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hash,
            'token' => $token,
        ]);

        sendVerificationEmail($email, $username, $token);

        $_SESSION['register_success'] = 'Compte créé avec succès. Un email de vérification a été envoyé à votre adresse.';
        header('Location: index.php?action=loginPage');
        exit;
    } catch (Throwable $e) {
        $_SESSION['register_error'] = 'Erreur pendant la création du compte : ' . $e->getMessage();
        header('Location: index.php?action=registerPage');
        exit;
    }
}

$pageTitle = 'NovaBank | Banque virtuelle & Simulateur de Bourse';
require __DIR__ . '/views/layout/header.php';

?>

<?php if ($isLoggedIn): ?>
  <div class="dashboard-layout">
    <?php require __DIR__ . '/views/layout/sidebar.php'; ?>

    <main class="dashboard-main" aria-label="Espace principal vide">
      <div class="dashboard-empty-panel"></div>
    </main>
  </div>
<?php else: ?>
  <main class="main-content">
    <section class="home-hero" aria-labelledby="hero-title" style="position: relative; width: 100vw; margin-left: calc(-50vw + 50%); min-height: 85vh; display: flex; align-items: center; justify-content: space-between; padding: 60px 8%; box-sizing: border-box; overflow: hidden;">
      <video autoplay muted loop playsinline style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 1; pointer-events: none;">
        <source src="public/video/invest.mp4" type="video/mp4">
        Votre navigateur ne supporte pas la lecture vidéo.
      </video>

      <div style="position: absolute; inset: 0; background: rgba(10, 15, 29, 0.65); z-index: 2; pointer-events: none;"></div>

      <div class="home-copy" style="position: relative; z-index: 10; max-width: 580px; color: #ffffff;">
        <span class="home-eyebrow" style="color: #60a5fa; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; display: inline-block; margin-bottom: 8px;">NovaBank</span>
        <h1 id="hero-title" style="color: #ffffff; font-size: 2.75rem; line-height: 1.2; margin-bottom: 16px;">La banque simple, <span style="color: #38bdf8;">pour votre quotidien.</span></h1>
        <p class="hero-lead" style="color: #e2e8f0; font-size: 1.15rem; line-height: 1.6; margin-bottom: 24px;">
          Gérez votre compte, suivez vos finances et découvrez l'investissement depuis un seul espace.
        </p>
        <div class="hero-buttons" style="display: flex; gap: 14px; margin-bottom: 20px;">
          <a href="index.php?action=registerPage" class="btn-primary btn-large">Ouvrir un compte</a>
          <a href="index.php?action=loginPage" class="btn-secondary btn-large">Se connecter</a>
        </div>
        <p class="home-note" style="color: #94a3b8; font-size: 0.9rem;">Simple à utiliser · Accessible en ligne · Pensée pour apprendre</p>
      </div>

      <!-- 4. Panneau des marchés au-dessus de la vidéo -->
      <aside class="home-side-panel" aria-label="Marchés en direct" style="position: relative; z-index: 10; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); padding: 24px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); width: 340px;">
        <span class="side-panel-label" style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Marchés en direct</span>
        <h2 style="color: #ffffff; font-size: 1.3rem; margin: 6px 0 16px;">Les valeurs du moment</h2>
        <div class="market-preview-list">
          <?php if (!empty($marketData)): ?>
            <?php foreach (array_slice($marketData, 0, 3) as $stock): ?>
              <div class="market-preview-row" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: #ffffff;">
                <strong><?= htmlspecialchars($stock['symbol']) ?></strong>
                <span>$<?= htmlspecialchars($stock['price']) ?></span>
                <span class="market-preview-change <?= $stock['change'] >= 0 ? 'up' : 'down' ?>" style="color: <?= $stock['change'] >= 0 ? '#4ade80' : '#f87171' ?>; font-weight: 600;">
                  <?= $stock['change'] >= 0 ? '+' : '' ?><?= htmlspecialchars($stock['change']) ?>%
                </span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="market-preview-empty" style="color: #94a3b8; font-size: 0.9rem;">Les données de marché seront disponibles après la configuration de l'API.</p>
          <?php endif; ?>
        </div>
        <p class="security-note" style="margin-top: 16px; color: #94a3b8; font-size: 0.85rem;"><span class="security-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #22c55e; margin-right: 6px;"></span> Vos données restent protégées.</p>
      </aside>
    </section>

    <section class="home-basics" id="home-basics" aria-label="Services NovaBank">
      <article>
        <strong>Compte bancaire</strong>
        <span>Un aperçu clair de votre solde et de vos opérations.</span>
      </article>
      <article>
        <strong>Suivi des marchés</strong>
        <span>Les principales valeurs accessibles en quelques secondes.</span>
      </article>
      <article>
        <strong>Éducation financière</strong>
        <span>Des ressources pour avancer à votre rythme.</span>
      </article>
    </section>
  </main>
  
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>