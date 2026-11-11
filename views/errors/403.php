<?php
$pageStylesheets = ['errors.css'];
require __DIR__ . '/../layout/header.php';
?>

<main class="error-page" aria-labelledby="error-title">
  <section class="error-panel">
    <div class="error-code" aria-hidden="true">403</div>
    <h1 id="error-title">Accès réservé</h1>
    <p>Cette section est accessible uniquement aux administrateurs.</p>
    <a href="index.php" class="btn-primary btn-large">Retour au tableau de bord</a>
  </section>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
