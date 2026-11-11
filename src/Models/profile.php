<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectRoot = dirname(__DIR__, 2);
$pdo = getPDO();
$profileQuery = $pdo->prepare('SELECT `Alias`, `Description` FROM `User` WHERE `idUser` = :userId LIMIT 1');
$profileQuery->execute(['userId' => (int) $_SESSION['user']['id']]);
$profile = $profileQuery->fetch();

if (!$profile) {
    unset($_SESSION['user']);
    header('Location: index.php?action=loginPage');
    exit;
}

$profilePictureColumn = $pdo->query("SHOW COLUMNS FROM `User` LIKE 'PFP'")->fetch();

if (!$profilePictureColumn) {
    throw new RuntimeException('La colonne de photo de profil est absente de la table User.');
}

if (strtolower((string) $profilePictureColumn['Type']) === 'blob') {
    $pdo->exec('ALTER TABLE `User` MODIFY `PFP` MEDIUMBLOB NULL');
}

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['profile_csrf_token'];
$error = null;
$success = $_SESSION['profile_success'] ?? null;
unset($_SESSION['profile_success']);
$passwordChange = $_SESSION['password_change'] ?? null;

if (is_array($passwordChange) && (
    ($passwordChange['expires_at'] ?? 0) <= time()
    || ($passwordChange['user_id'] ?? null) !== (int) $_SESSION['user']['id']
    || (isset($passwordChange['verified_at']) && $passwordChange['verified_at'] + 300 <= time())
)) {
    unset($_SESSION['password_change']);
    $passwordChange = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Votre session a expiré. Rechargez la page et réessayez.';
    } elseif (($_POST['form_action'] ?? '') === 'request_password_code') {
        $lastSentAt = (int) ($_SESSION['password_code_last_sent_at'] ?? 0);
        if ($lastSentAt > 0 && $lastSentAt + 60 > time()) {
            $error = 'Veuillez patienter une minute avant de demander un autre code.';
        } else {
            $code = (string) random_int(100000, 999999);
            $_SESSION['password_code_last_sent_at'] = time();
            try {
                sendPasswordChangeCode((string) $profile['Description'], (string) $profile['Alias'], $code);
                $_SESSION['password_change'] = [
                    'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                    'user_id' => (int) $_SESSION['user']['id'],
                    'expires_at' => time() + 600,
                    'attempts' => 0,
                ];
                $_SESSION['profile_success'] = 'Un code de confirmation a été envoyé à votre adresse courriel.';
                header('Location: index.php?action=profile');
                exit;
            } catch (Throwable $e) {
                error_log('index.php failed to send a password change code: ' . $e->getMessage());
                $error = 'Impossible d’envoyer le courriel pour le moment. Réessayez plus tard.';
            }
        }
    } elseif (($_POST['form_action'] ?? '') === 'verify_password_code') {
        $passwordChange = $_SESSION['password_change'] ?? null;
        $submittedCode = trim((string) ($_POST['verification_code'] ?? ''));

        if (!is_array($passwordChange)
            || ($passwordChange['user_id'] ?? null) !== (int) $_SESSION['user']['id']
            || ($passwordChange['expires_at'] ?? 0) <= time()
        ) {
            unset($_SESSION['password_change']);
            $passwordChange = null;
            $error = 'Le code a expiré. Demandez-en un nouveau.';
        } elseif (!preg_match('/^\d{6}$/', $submittedCode)) {
            $error = 'Entrez le code à six chiffres reçu par courriel.';
        } elseif (!password_verify($submittedCode, (string) $passwordChange['code_hash'])) {
            $passwordChange['attempts'] = (int) ($passwordChange['attempts'] ?? 0) + 1;
            if ($passwordChange['attempts'] >= 5) {
                unset($_SESSION['password_change']);
                $passwordChange = null;
                $error = 'Trop de tentatives. Demandez un nouveau code.';
            } else {
                $_SESSION['password_change'] = $passwordChange;
                $error = 'Le code est invalide.';
            }
        } else {
            $_SESSION['password_change']['verified_at'] = time();
            $_SESSION['profile_success'] = 'Identité confirmée. Vous pouvez maintenant choisir un nouveau mot de passe.';
            header('Location: index.php?action=profile');
            exit;
        }
    } elseif (($_POST['form_action'] ?? '') === 'update_password') {
        $passwordChange = $_SESSION['password_change'] ?? null;
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!is_array($passwordChange)
            || !isset($passwordChange['verified_at'])
            || ($passwordChange['user_id'] ?? null) !== (int) $_SESSION['user']['id']
            || $passwordChange['verified_at'] + 300 <= time()
            || ($passwordChange['expires_at'] ?? 0) <= time()
        ) {
            unset($_SESSION['password_change']);
            $passwordChange = null;
            $error = 'La confirmation a expiré. Demandez un nouveau code.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif (strlen($newPassword) > 72) {
            $error = 'Le mot de passe ne doit pas dépasser 72 octets.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $updatePassword = $pdo->prepare('UPDATE `User` SET `Password` = :password WHERE `idUser` = :userId');
            $updatePassword->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'userId' => (int) $_SESSION['user']['id'],
            ]);
            unset($_SESSION['password_change']);
            session_regenerate_id(true);
            $_SESSION['profile_success'] = 'Votre mot de passe a été changé avec succès.';
            header('Location: index.php?action=profile');
            exit;
        }
    } elseif (!isset($_FILES['profile_photo'])) {
        $error = 'Sélectionnez une photo à importer.';
    } elseif ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['profile_photo']['error'];
        if ($uploadError === UPLOAD_ERR_INI_SIZE) {
            $error = 'La photo dépasse la limite de téléversement configurée sur le serveur.';
        } elseif ($uploadError === UPLOAD_ERR_FORM_SIZE) {
            $error = 'La photo ne doit pas dépasser 2 Mo.';
        } elseif ($uploadError === UPLOAD_ERR_NO_FILE) {
            $error = 'Sélectionnez une photo à importer.';
        } else {
            $error = 'Le téléversement de la photo a échoué. Réessayez.';
        }
    } elseif (!is_uploaded_file($_FILES['profile_photo']['tmp_name'])) {
        $error = 'Le fichier importé est invalide.';
    } else {
        $temporaryFile = $_FILES['profile_photo']['tmp_name'];
        $fileSize = filesize($temporaryFile);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
        $imageInfo = getimagesize($temporaryFile);

        if ($fileSize === false || $fileSize > 2 * 1024 * 1024) {
            $error = 'La photo ne doit pas dépasser 2 Mo.';
        } elseif (!in_array($mimeType, $allowedMimeTypes, true) || $imageInfo === false || $imageInfo['mime'] !== $mimeType) {
            $error = 'Choisissez une image JPEG, PNG ou WebP valide.';
        } else {
            $photo = file_get_contents($temporaryFile);
            if ($photo === false) {
                throw new RuntimeException('Impossible de lire la photo importée.');
            }

            $update = $pdo->prepare('UPDATE `User` SET `PFP` = :photo WHERE `idUser` = :userId');
            $update->bindValue(':photo', $photo, PDO::PARAM_LOB);
            $update->bindValue(':userId', (int) $_SESSION['user']['id'], PDO::PARAM_INT);
            $update->execute();

            $_SESSION['profile_success'] = 'Votre photo de profil a été mise à jour.';
            header('Location: index.php?action=profile');
            exit;
        }
    }
}

$passwordChange = $_SESSION['password_change'] ?? null;
$passwordChangeVerified = is_array($passwordChange)
    && isset($passwordChange['verified_at'])
    && $passwordChange['verified_at'] + 300 > time()
    && $passwordChange['expires_at'] > time();
$profileName = (string) $profile['Alias'];
$profileInitial = mb_strtoupper(mb_substr($profileName, 0, 1, 'UTF-8'), 'UTF-8');
$pageTitle = 'Mon profil | NovaBank';
$pageStylesheets = ['profile.css'];
require $projectRoot . '/views/layout/header.php';
?>

<main class="profile-page">
  <section class="profile-card" aria-labelledby="profile-title">
    <div class="profile-heading">
      <div class="profile-avatar">
        <span aria-hidden="true"><?= htmlspecialchars($profileInitial, ENT_QUOTES, 'UTF-8') ?></span>
        <img src="index.php?action=profilePhoto" alt="Photo de profil de <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>" onerror="this.style.display='none'">
      </div>
      <div>
        <p class="profile-eyebrow">Espace personnel</p>
        <h1 id="profile-title">Mon profil</h1>
      </div>
    </div>

    <?php if ($error !== null): ?>
      <div class="profile-alert profile-alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($success !== null): ?>
      <div class="profile-alert profile-alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <dl class="profile-details">
      <div>
        <dt>Nom d’utilisateur</dt>
        <dd><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
      <div>
        <dt>Adresse email</dt>
        <dd><?= htmlspecialchars((string) $profile['Description'], ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
    </dl>

    <form class="profile-photo-form" action="index.php?action=profile" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="form_action" value="upload_profile_photo">
      <label for="profile-photo">Photo de profil</label>
      <p>Formats acceptés : JPEG, PNG ou WebP. Taille maximale : 2 Mo.</p>
      <input id="profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" required>
      <button class="btn-primary" type="submit">Enregistrer la photo</button>
    </form>

    <section class="profile-password-section" aria-labelledby="password-title">
      <h2 id="password-title">Changer mon mot de passe</h2>
      <?php if (!$passwordChangeVerified && !is_array($passwordChange)): ?>
        <p>Un code à six chiffres sera envoyé à votre adresse courriel pour confirmer votre identité.</p>
        <form action="index.php?action=profile" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="request_password_code">
          <button class="btn-primary" type="submit">Recevoir un code par courriel</button>
        </form>
      <?php elseif (!$passwordChangeVerified): ?>
        <p>Entrez le code envoyé à votre adresse courriel. Il est valable 10 minutes.</p>
        <form action="index.php?action=profile" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="verify_password_code">
          <label for="verification-code">Code de confirmation</label>
          <input id="verification-code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
          <button class="btn-primary" type="submit">Confirmer mon identité</button>
        </form>
        <form action="index.php?action=profile" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="request_password_code">
          <button class="profile-resend-button" type="submit">Renvoyer le code</button>
        </form>
      <?php else: ?>
        <p>Votre identité est confirmée. Choisissez un nouveau mot de passe d’au moins 8 caractères.</p>
        <form action="index.php?action=profile" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="update_password">
          <label for="new-password">Nouveau mot de passe</label>
          <input id="new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required>
          <label for="confirm-password">Confirmer le nouveau mot de passe</label>
          <input id="confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
          <button class="btn-primary" type="submit">Enregistrer le nouveau mot de passe</button>
        </form>
      <?php endif; ?>
    </section>
  </section>
</main>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
