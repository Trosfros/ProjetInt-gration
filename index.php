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

<?php require __DIR__ . '/views/layout/footer.php'; ?>

      </div>
    </section>

    <!-- 5. Appel à l'action final -->
    <section class="cta-banner">
      <h2>Démarrez votre formation financière dès aujourd'hui</h2>
      <p>Rejoignez la communauté d'apprenants, débloquez vos cours et prenez en main votre capital virtuel de 100 000 $.</p>
      <a href="#" class="btn-primary btn-large">Commencer la formation</a>
    </section>

  </main>

  <!-- Pied de page -->
  <footer>
    <div class="footer-inner">
      <p>© 2026 NovaBank. Académie et simulateur éducatif d'investissement boursier et bancaire. Ne constitue pas un conseil financier.</p>
    </div>
  </footer>
=======
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
>>>>>>> 49baf60 (création dun compte plus verif via mail)

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



<?php require __DIR__ . '/views/layout/footer.php'; ?>