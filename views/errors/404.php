<?php
$pageStylesheets = ['errors.css'];
require __DIR__ . '/../layout/header.php';
?>

<main class="error-page" aria-labelledby="error-title">
  <section class="error-panel">
    <div class="error-code" aria-hidden="true">404</div>
    <h1 id="error-title">Cette page est introuvable</h1>
    <p>L'adresse demandée n'existe pas ou n'est plus disponible. Retournez à l'accueil pour poursuivre votre navigation.</p>
    <a href="index.php" class="btn-primary btn-large">Retour à l'accueil</a>
  </section>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
