<?php
// src/Models/education.php
// Les modules avec leur cours et leur quiz
$modules = [
    'module-1' => [
        'id' => 'module-1',
        'number' => '01',
        'title' => 'Les bases de l’investissement',
        'badge' => 'Débutant',
        'summary' => 'Comprendre le fonctionnement des actions, le capital et l’horizon d’investissement.',
        'lessons' => [
            [
                'title' => 'Qu’est-ce qu’une action ?',
                'text' => 'Une action représente une part de propriété d’une société. Quand vous en achetez une, vous devenez actionnaire et profitez potentiellement de la hausse de sa valeur ou de dividendes.'
            ],
            [
                'title' => 'L’horizon de placement',
                'text' => 'En bourse, le court terme est volatile. Définir un horizon (moyen ou long terme) évite de paniquer lors des corrections de marché et permet de lisser les variations.'
            ]
        ],
        'quiz' => [
            'question' => 'Que représente réellement une action en bourse ?',
            'options' => [
                ['text' => 'Un prêt accordé à une entreprise avec intérêts garantis.', 'correct' => false],
                ['text' => 'Une fraction du capital et de la propriété d’une entreprise.', 'correct' => true],
                ['text' => 'Une promesse que le cours montera obligatoirement.', 'correct' => false]
            ],
            'explanation' => 'Une action est un titre de propriété représentant une fraction du capital de l’entreprise.'
        ]
    ],
    'module-2' => [
        'id' => 'module-2',
        'number' => '02',
        'title' => 'Risque et diversification',
        'badge' => 'Essentiel',
        'summary' => 'Réduire la volatilité en répartissant son capital sur plusieurs secteurs d’activité.',
        'lessons' => [
            [
                'title' => 'Le principe de diversification',
                'text' => 'Ne mettez pas tous vos œufs dans le même panier. Si vous investissez seulement dans la tech et que le secteur chute, tout votre portefeuille encaisse la baisse.'
            ],
            [
                'title' => 'Le ratio risque / rendement',
                'text' => 'Un potentiel de rendement élevé implique toujours un risque de perte supérieur. Il n’existe aucun investissement très rentable sans risque.'
            ]
        ],
        'quiz' => [
            'question' => 'Pourquoi diversifie-t-on un portefeuille boursier ?',
            'options' => [
                ['text' => 'Pour éliminer 100 % du risque de marché.', 'correct' => false],
                ['text' => 'Pour réduire l’impact de la chute d’un titre ou d’un secteur isolé.', 'correct' => true],
                ['text' => 'Pour payer moins de taxes sur les transactions.', 'correct' => false]
            ],
            'explanation' => 'La diversification atténue les risques spécifiques à une entreprise, bien qu’elle ne supprime jamais le risque global du marché.'
        ]
    ],
    'module-3' => [
        'id' => 'module-3',
        'number' => '03',
        'title' => 'Lire une cotation & passer un ordre',
        'badge' => 'Pratique',
        'summary' => 'Maîtriser les tickers, le carnet d’ordres et la passation dans le simulateur.',
        'lessons' => [
            [
                'title' => 'Le Ticker (Symbole)',
                'text' => 'Chaque titre a un symbole boursier unique (ex. : AAPL pour Apple, MSFT pour Microsoft, NVDA pour Nvidia). C’est ce code que vous recherchez sur les marchés.'
            ],
            [
                'title' => 'Achat vs Vente',
                'text' => 'Un ordre d’achat consomme votre solde virtuel pour ajouter des titres. Un ordre de vente liquide vos positions et crédite à nouveau votre solde disponible.'
            ]
        ],
        'quiz' => [
            'question' => 'Si vous achetez 5 actions à 150 $ et les revendez à 180 $, quel est votre gain ?',
            'options' => [
                ['text' => '30 $ de profit au total.', 'correct' => false],
                ['text' => '150 $ de profit au total (30 $ x 5).', 'correct' => true],
                ['text' => '900 $ de profit au total.', 'correct' => false]
            ],
            'explanation' => 'Gain par action = 180 $ - 150 $ = 30 $. Pour 5 actions : 30 $ x 5 = 150 $ de gain brut.'
        ]
    ]
];
?>

<section class="edu-container" aria-label="Académie NovaBank">
  <!-- En-tête -->
  <header class="edu-top">
    <h1>Parcours d'apprentissage</h1>
    <p>Consultez les notions clés puis validez vos acquis avec le mini-quiz avant de passer vos ordres en simulation.</p>
  </header>

  <div class="edu-layout">
    <!-- Barre de sélection des modules (onglets) -->
    <nav class="edu-sidebar-tabs" aria-label="Liste des cours">
      <?php $first = true; foreach ($modules as $mod): ?>
        <button type="button" 
                class="edu-tab-btn <?= $first ? 'active' : '' ?>" 
                data-target="<?= htmlspecialchars($mod['id']) ?>">
          <div class="tab-meta">
            <span class="tab-num"><?= htmlspecialchars($mod['number']) ?></span>
            <span class="tab-badge"><?= htmlspecialchars($mod['badge']) ?></span>
          </div>
          <strong class="tab-title"><?= htmlspecialchars($mod['title']) ?></strong>
        </button>
      <?php $first = false; endforeach; ?>
    </nav>

    <!-- Contenu du cours & Quiz -->
    <div class="edu-main-panel">
      <?php $first = true; foreach ($modules as $mod): ?>
        <article id="<?= htmlspecialchars($mod['id']) ?>" class="edu-content-pane <?= $first ? 'active' : '' ?>">
          
          <div class="pane-header">
            <span class="pane-num">Module <?= htmlspecialchars($mod['number']) ?></span>
            <h2><?= htmlspecialchars($mod['title']) ?></h2>
            <p class="pane-lead"><?= htmlspecialchars($mod['summary']) ?></p>
          </div>

          <!-- Section Cours -->
          <div class="pane-lessons">
            <?php foreach ($mod['lessons'] as $lesson): ?>
              <div class="lesson-card">
                <h3>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                  <?= htmlspecialchars($lesson['title']) ?>
                </h3>
                <p><?= htmlspecialchars($lesson['text']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Section Quiz -->
          <div class="pane-quiz-box" data-quiz-box>
            <div class="quiz-header">
              <span class="quiz-pill">Testez vos connaissances</span>
              <h3><?= htmlspecialchars($mod['quiz']['question']) ?></h3>
            </div>

            <div class="quiz-options">
              <?php foreach ($mod['quiz']['options'] as $idx => $opt): ?>
                <button type="button" 
                        class="quiz-option-btn" 
                        data-correct="<?= $opt['correct'] ? 'true' : 'false' ?>">
                  <span class="opt-letter"><?= chr(65 + $idx) ?></span>
                  <span class="opt-text"><?= htmlspecialchars($opt['text']) ?></span>
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Message d'explication (masqué par défaut) -->
            <div class="quiz-feedback" style="display: none;">
              <p class="feedback-msg"></p>
              <small class="feedback-desc"><?= htmlspecialchars($mod['quiz']['explanation']) ?></small>
            </div>
          </div>

        </article>
      <?php $first = false; endforeach; ?>
    </div>
  </div>
</section>

<!-- Scripts d'interaction (Onglets & Validation des quiz) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Navigation par onglets
  const tabBtns = document.querySelectorAll('.edu-tab-btn');
  const panes = document.querySelectorAll('.edu-content-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      panes.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const targetId = btn.getAttribute('data-target');
      document.getElementById(targetId)?.classList.add('active');
    });
  });

  // 2. Gestion des choix de quiz
  const quizBoxes = document.querySelectorAll('[data-quiz-box]');
  quizBoxes.forEach(box => {
    const options = box.querySelectorAll('.quiz-option-btn');
    const feedback = box.querySelector('.quiz-feedback');
    const msg = box.querySelector('.feedback-msg');

    options.forEach(opt => {
      opt.addEventListener('click', () => {
        // Bloquer après une réponse
        options.forEach(b => b.disabled = true);

        const isCorrect = opt.getAttribute('data-correct') === 'true';
        if (isCorrect) {
          opt.classList.add('is-valid');
          msg.textContent = 'Bravo ! Bonne réponse.';
          msg.style.color = '#4ade80';
        } else {
          opt.classList.add('is-invalid');
          // Révéler aussi la bonne réponse
          options.forEach(b => {
            if (b.getAttribute('data-correct') === 'true') b.classList.add('is-valid');
          });
          msg.textContent = 'Ce n’est pas la bonne réponse.';
          msg.style.color = '#f87171';
        }
        feedback.style.display = 'block';
      });
    });
  });
});
</script>