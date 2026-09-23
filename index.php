<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/configs/database.php';

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

if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['user']);
    header('Location: index.php');
    exit;
}

if (($_GET['action'] ?? '') === 'market') {
    header('Location: index.php?action=investment');
    exit;
}

if (($_GET['action'] ?? '') === 'investment') {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    require __DIR__ . '/investment.php';
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
        header('Location: login.php');
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
                header('Location: login.php');
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
        header('Location: login.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['login_error'] = 'Erreur de connexion à la base de données.';
        header('Location: login.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $_SESSION['register_error'] = 'Tous les champs sont requis.';
        header('Location: register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Adresse email invalide.';
        header('Location: register.php');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT idUser FROM `User` WHERE `Alias` = :username OR `Description` = :email LIMIT 1');
        $stmt->execute(['username' => $username, 'email' => $email]);

        if ($stmt->fetch()) {
            $_SESSION['register_error'] = 'Ce nom d’utilisateur ou cet email existe déjà.';
            header('Location: register.php');
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
        header('Location: login.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['register_error'] = 'Erreur pendant la création du compte : ' . $e->getMessage();
        header('Location: register.php');
        exit;
    }
}

$pageTitle = 'NovaBank | Banque virtuelle & Simulateur de Bourse';
require __DIR__ . '/views/layout/header.php';
?>

<?php if ($isLoggedIn): ?>
  <div class="dashboard-layout">
    <aside class="dashboard-sidebar" aria-label="Menu principal">
      <nav class="sidebar-nav">
        <a class="sidebar-link active" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V18a2 2 0 0 1-2 2h-3v-7H9v7H6a2 2 0 0 1-2-2v-7.5Z"/></svg>
          </span>
          <span>Tableau de bord</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm3 2h10M8 15h5"/></svg>
          </span>
          <span>Comptes</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M7 4v6M17 4v6M5 13h14a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2Z"/></svg>
          </span>
          <span>Virements</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 18V9m7 9V5m7 13v-7"/></svg>
          </span>
          <span>Vos Investissements</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 18V8m7 10V4m7 14v-8"/></svg>
          </span>
          <span>Marchés</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 7.5A2.5 2.5 0 0 1 6 5h12a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 18 19H6a2.5 2.5 0 0 1-2.5-2.5v-9Zm2 1.5h13"/></svg>
          </span>
          <span>Cartes</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
          </span>
          <span>Historique</span>
        </a>
        <a class="sidebar-link" href="#">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.5 6.5-2.1-2.1M8.6 8.6 6.5 6.5m11 0-2.1 2.1M8.6 15.4l-2.1 2.1"/></svg>
          </span>
          <span>Paramètres</span>
        </a>
        <a class="sidebar-link logout-link" href="index.php?action=logout">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          </span>
          <span>Déconnexion</span>
        </a>
      </nav>
    </aside>

    <main class="dashboard-main" aria-label="Espace principal vide">
      <div class="dashboard-empty-panel"></div>
    </main>
  </div>
<?php else: ?>
  <main class="main-content"></main>
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>