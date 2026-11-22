<?php
$pageTitle = 'Cookies et confidentialité | NovaBank';
$pageStylesheets = ['privacy.css'];
require __DIR__ . '/../../views/layout/header.php';
?>

<main class="privacy-page">
  <section class="privacy-card" aria-labelledby="privacy-title">
    <span class="privacy-eyebrow">Confidentialité</span>
    <h1 id="privacy-title">Informations sur les cookies</h1>
    <p class="privacy-intro">Cette page explique les cookies et le stockage local utilisés par NovaBank pour faire fonctionner le site et mémoriser vos préférences.</p>

    <h2>Cookies utilisés</h2>
    <ul>
      <li><strong>Cookie de session :</strong> maintient votre connexion pendant votre visite. Il expire à la fin de la session.</li>
      <li><strong>Cookies de préférences :</strong> <code>novabank_theme</code>, <code>novabank_display_currency</code> et <code>novabank_language</code> mémorisent votre thème, votre devise d’affichage et votre langue. Ils peuvent être conservés jusqu’à un an.</li>
    </ul>

    <h2>Stockage local</h2>
    <p>Le choix « Compris » de la bannière est enregistré dans le stockage local de votre navigateur pour ne pas réafficher l’avis à chaque visite. Ce mécanisme n’est pas un cookie.</p>

    <h2>Mesure d’audience et publicité</h2>
    <p>NovaBank ne configure actuellement aucun cookie publicitaire ni outil de mesure d’audience.</p>

    <h2>Vos choix</h2>
    <p>Vous pouvez supprimer ou bloquer les cookies et le stockage local dans les paramètres de votre navigateur. Le blocage du cookie de session peut empêcher la connexion; la suppression des préférences réinitialisera vos choix.</p>

    <a class="privacy-back-link" href="index.php">Retour à NovaBank</a>
  </section>
</main>

<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
