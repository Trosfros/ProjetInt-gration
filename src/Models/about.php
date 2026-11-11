<?php
$projectRoot = dirname(__DIR__, 2);
$pageTitle = 'À propos | NovaBank';
$pageStylesheets = ['about.css'];
require $projectRoot . '/views/layout/header.php';
?>

<main class="about-page">
  <h1>À propos de NovaBank</h1>
  <p class="about-intro">
    NovaBank est un projet éducatif qui permet de découvrir la gestion d’un compte
    virtuel et la simulation d’investissements boursiers, sans utiliser d’argent réel.
  </p>

  <section class="about-people" aria-labelledby="team-title">
    <h2 id="team-title">Créateur</h2>
    <p><strong>Gabriel Perron</strong> — <a href="mailto:gabrielperron39@gmail.com">gabrielperron39@gmail.com</a></p>
    <p><strong>Superviseur :</strong> François Simard</p>
  </section>
</main>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
