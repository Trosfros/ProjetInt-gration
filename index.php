<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NovaBank</title>
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Halos d'ambiance -->
  <div class="glow-bg top-left"></div>
  <div class="glow-bg top-right"></div>
  <div class="glow-bg bottom-center"></div>

  <!-- Barre de navigation -->
  <header class="navbar">
    <div class="logo">
      <span class="logo-symbol">
        <img src="public/images/logo.png" alt="NovaBank Logo" onerror="this.style.display='none'">
      </span>
      NovaBank
    </div>

    <nav class="nav-links">
      <a href="#programme">Programme</a>
      <a href="#methode">Notre Méthode</a>
      <a href="#avantages">Outils Pédagogiques</a>
    </nav>

    <div class="nav-actions">
      <a href="#" class="btn-ghost">Se connecter</a>
      <a href="#" class="btn-primary">Créer mon compte</a>
    </div>
  </header>

  <!-- Bandeau cours de bourse défilant -->
  <div class="market-ticker">
    <div class="ticker-track">
      <div class="ticker-item"><span class="sym">NVDA</span> <span class="val">$128.45</span> <span class="up">+5.42%</span></div>
      <div class="ticker-item"><span class="sym">AAPL</span> <span class="val">$224.10</span> <span class="up">+1.15%</span></div>
      <div class="ticker-item"><span class="sym">TSLA</span> <span class="val">$248.80</span> <span class="down">-2.30%</span></div>
      <div class="ticker-item"><span class="sym">BTC/USD</span> <span class="val">$64,320</span> <span class="up">+3.80%</span></div>
      <div class="ticker-item"><span class="sym">SPY</span> <span class="val">$562.14</span> <span class="up">+0.65%</span></div>
      <div class="ticker-item"><span class="sym">MSFT</span> <span class="val">$432.20</span> <span class="down">-0.45%</span></div>
      <!-- Répétition pour la fluidité -->
      <div class="ticker-item"><span class="sym">NVDA</span> <span class="val">$128.45</span> <span class="up">+5.42%</span></div>
      <div class="ticker-item"><span class="sym">AAPL</span> <span class="val">$224.10</span> <span class="up">+1.15%</span></div>
      <div class="ticker-item"><span class="sym">TSLA</span> <span class="val">$248.80</span> <span class="down">-2.30%</span></div>
      <div class="ticker-item"><span class="sym">BTC/USD</span> <span class="val">$64,320</span> <span class="up">+3.80%</span></div>
    </div>
  </div>

  <!-- Corps principal -->
  <main class="main-content">
    
    <!-- 1. Hero éducatif -->
    <section class="hero-presentation">

      <h1>Maîtrisez la bourse et vos finances grâce à un <span>apprentissage guidé</span>.</h1>
      <p class="hero-lead">
        La finance ne s’apprend pas uniquement dans les livres. NovaBank combine des cours pas-à-pas, 
        des ateliers interactifs et un simulateur temps réel doté de 100 000 $ virtuels pour tester vos connaissances sans risque de perte.
      </p>

      <div class="hero-buttons">
        <a href="#" class="btn-primary btn-large">Créer mon compte</a>
        <a href="#programme" class="btn-secondary btn-large">Consulter les modules</a>
      </div>

      <!-- Métriques pédagogiques -->
      <div class="key-points">
        <div class="point-box">
          <span class="point-num">4 Niveaux</span>
          <span class="point-label">Du débutant à l'expert</span>
        </div>
        <div class="point-box">
          <span class="point-num">100 000 $</span>
          <span class="point-label">Capital d'entraînement  offert</span>
        </div>
        <div class="point-box">
          <span class="point-num">100% Sans risque</span>
          <span class="point-label">Droit à l'erreur garanti</span>
        </div>
      </div>
    </section>

    <!-- 2. Parcours de formation (Curriculum) -->
    <section class="curriculum-section" id="programme">
      <div class="section-header">
        <span class="section-tag">Parcours structuré</span>
        <h2>Un programme conçu pour progresser pas à pas</h2>
        <p>Une progression claire : apprenez un concept théorique, puis appliquez-le immédiatement dans la simulation.</p>
      </div>

      <div class="curriculum-grid">
        <!-- Module 1 -->
        <div class="module-card">
          <div class="module-level">Module 01</div>
          <div class="module-icon">📘</div>
          <h3>Les Fondations Bancaires</h3>
          <p>Comprendre la gestion des liquidités, l'épargne d'urgence, la création d'un budget personnel équilibré et l'impact de l'inflation.</p>
          <ul class="module-topics">
            <li>✓ Comptes courants & comptes d'épargne</li>
            <li>✓ Gestion des entrées/sorties et virement</li>
            <li>✓ Notion d'intérêt composé</li>
          </ul>
        </div>

        <!-- Module 2 -->
        <div class="module-card">
          <div class="module-level">Module 02</div>
          <div class="module-icon">📊</div>
          <h3>Décrypter la Bourse</h3>
          <p>Découvrir le fonctionnement des marchés financiers, la différence entre actions, obligations et ETF, et la lecture des cours en direct.</p>
          <ul class="module-topics">
            <li>✓ Qu'est-ce qu'une action ?</li>
            <li>✓ Rôle des indices boursiers (S&P 500, TSX)</li>
            <li>✓ Comprendre les spreads et la liquidité</li>
          </ul>
        </div>

        <!-- Module 3 -->
        <div class="module-card">
          <div class="module-level">Module 03</div>
          <div class="module-icon">🛡️</div>
          <h3>Gestion du Risque & Stratégies</h3>
          <p>La clé des investisseurs durables : apprendre à protéger son capital avant de chercher à maximiser les gains.</p>
          <ul class="module-topics">
            <li>✓ Diversification de portefeuille</li>
            <li>✓ Utilisation des ordres Stop-Loss et Limites</li>
            <li>✓ Gestion des émotions et psychologie de marché</li>
          </ul>
        </div>

        <!-- Module 4 -->
        <div class="module-card">
          <div class="module-level">Module 04</div>
          <div class="module-icon">🚀</div>
          <h3>Pratique & Analyse de Performance</h3>
          <p>Passez à l'exécution réelle avec votre solde fictif. Analysez vos trades passés, calculez votre ratio de gain et ajustez votre méthode.</p>
          <ul class="module-topics">
            <li>✓ Lecture et analyse du P&L (Profit & Loss)</li>
            <li>✓ Suivi des résultats trimestriels d'entreprises</li>
            <li>✓ Participation aux défis hebdomadaires</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- 3. Notre méthode pédagogique (Learn by Doing) -->
    <section class="method-section" id="methode">
      <div class="method-card">
        <div class="method-content">
          <span class="section-tag">La méthode NovaBank</span>
          <h2>Apprendre par la pratique : le principe du bac à sable</h2>
          <p>
            Lire des articles théoriques ne suffit pas pour comprendre la volatilité ou savoir quand acheter et vendre. 
            Chez NovaBank, chaque leçon est directement connectée à une action concrète dans le simulateur.
          </p>
          <p>
            Vous commettez une erreur d'achat ? Vous subissez une baisse subite ? C'est le moment idéal pour analyser ce qui s'est passé, 
            sans aucun impact négatif sur votre portefeuille réel.
          </p>
          <div class="method-highlights">
            <div class="m-highlight">
              <strong>Théorie condensée</strong>
              <span>Des fiches synthétiques sans vocabulaire opaque.</span>
            </div>
            <div class="m-highlight">
              <strong>Application instantanée</strong>
              <span>Des exercices concrets exécutés au marché réel.</span>
            </div>
            <div class="m-highlight">
              <strong>Analyse rétroactive</strong>
              <span>Comprenez pourquoi une position a gagné ou perdu.</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. Boîte à outils de l'apprenant -->
    <section class="tools-section" id="avantages">
      <div class="section-header">
        <span class="section-tag">Accompagnement</span>
        <h2>Les outils dédiés à votre apprentissage</h2>
        <p>Tout ce qu'il faut pour accélérer votre compréhension et développer de vrais réflexes financiers.</p>
      </div>

      <div class="tools-grid">
        <div class="tool-box">
          <div class="t-icon">💡</div>
          <h3>Lexique interactif intégré</h3>
          <p>Survolez n'importe quel terme technique (P/E ratio, dividende, ordre limite, ETF) pour obtenir une explication vulgarisée et illustrée.</p>
        </div>

        <div class="tool-box">
          <div class="t-icon">📝</div>
          <h3>Quiz & Validation des acquis</h3>
          <p>Des questionnaires courts à la fin de chaque étape pour vérifier que les fondamentaux sont bien assimilés avant d'investir.</p>
        </div>

        <div class="tool-box">
          <div class="t-icon">🔍</div>
          <h3>Diagnostic de portefeuille</h3>
          <p>Un outil automatisé évalue la répartition de vos actifs et vous signale si votre portefeuille est trop concentré ou trop exposé au risque.</p>
        </div>

        <div class="tool-box">
          <div class="t-icon">🏆</div>
          <h3>Défis pédagogiques</h3>
          <p>Participez à des scénarios guidés (ex: "Construire un portefeuille défensif contre l'inflation") et observez vos résultats.</p>
        </div>
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

</body>
</html>