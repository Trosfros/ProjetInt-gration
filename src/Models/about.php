<?php
$projectRoot = dirname(__DIR__, 2);
$pageTitle = 'À propos | NovaBank';
$pageStylesheets = ['about.css'];
require $projectRoot . '/views/layout/header.php';
?>

<main class="about-page">
  <section class="about-hero" aria-labelledby="about-title">
    <span class="about-eyebrow">Notre projet</span>
    <h1 id="about-title">À propos de <span>NovaBank</span></h1>
    <p class="about-intro">
      Investir en bourse, ça fait souvent peur quand on n'y connaît rien. On a l'impression que c'est réservé aux experts, les mots sont compliqués et personne n'a envie de perdre son argent réel juste pour apprendre.
    </p>
    <p class="about-intro">
      C'est pour régler ça qu'on a créé <strong>NovaBank</strong>.
    </p>
    <p class="about-intro">
      Le projet, c'est une fausse banque en ligne super simple à utiliser. Tu te crées un compte, tu reçois de l'argent virtuel gratuit et tu peux l'utiliser pour faire tes premiers pas dans l'application, exactement comme si tu étais sur une vraie appli bancaire.
    </p>
    <p class="about-intro">Là où ça devient vraiment le fun, c'est pour la bourse :</p>
    <ul class="about-description-points">
      <li><strong>Les vrais prix en direct :</strong> L'application est branchée sur les vrais marchés boursiers. Si l'action d'une grosse compagnie monte ou descend dans la vraie vie, le prix change exactement de la même façon sur ton écran.</li>
      <li><strong>Zéro stress, zéro perte d'argent réel :</strong> Tu achètes et tu vends des actions seulement avec ton argent virtuel. Si tu fais un mauvais coup et que la valeur baisse, ça ne te coûte absolument rien de ta poche.</li>
      <li><strong>Apprendre par la pratique :</strong> Tu vois directement comment tes placements évoluent. Tu apprends quand acheter, quand vendre, et comment réagir sans paniquer.</li>
    </ul>
    <p class="about-intro">
      Bref, NovaBank, c'est comme un jeu d'entraînement. C'est l'endroit parfait pour faire des erreurs, comprendre comment la bourse fonctionne pour vrai et prendre confiance en toi avant d'investir tes vraies économies plus tard.
    </p>
  </section>

  <section class="about-team" aria-labelledby="team-title">
    <div class="about-section-heading">
      <span class="about-eyebrow">Derrière NovaBank</span>
      <h2 id="team-title">Notre équipe</h2>
    </div>

    <div class="about-team-grid">
      <article class="about-person">
        <span class="about-person-initials" aria-hidden="true">GP</span>
        <div>
          <h3>Gabriel Perron</h3>
          <a href="mailto:202490519@edu.clg.qc.ca">202490519@edu.clg.qc.ca</a>
        </div>
      </article>
      <article class="about-person">
        <span class="about-person-initials" aria-hidden="true">AM</span>
        <div>
          <h3>Alexis Moreau</h3>
          <a href="mailto:202268042@edu.clg.qc.ca">202268042@edu.clg.qc.ca</a>
        </div>
      </article>
      <article class="about-person">
        <span class="about-person-initials" aria-hidden="true">MG</span>
        <div>
          <h3>Maxime Guichard</h3>
          <a href="mailto:202486858@edu.clg.qc.ca">202486858@edu.clg.qc.ca</a>
        </div>
      </article>
      <article class="about-person">
        <span class="about-person-initials" aria-hidden="true">AK</span>
        <div>
          <h3>Alexi Keller Cormier</h3>
          <a href="mailto:202384532@edu.clg.qc.ca">202384532@edu.clg.qc.ca</a>
        </div>
      </article>
    </div>

    <article class="about-supervisor">
      <span class="about-supervisor-mark" aria-hidden="true">FS</span>
      <div>
        <span class="about-supervisor-label">Superviseur du projet</span>
        <h3>François Simard</h3>
      </div>
    </article>
  </section>
</main>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
