<?php
$escapeEducation = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$totalLevels = count($educationModules);
?>

<section class="edu-container education-academy" aria-labelledby="education-title" data-education-root>
  <header class="edu-top education-hero">
    <span class="edu-tag">Académie NovaBank</span>
    <h1 id="education-title">Votre parcours d’apprentissage</h1>
    <p>Choisissez un niveau, déverrouillez-le et avancez à votre rythme pour mieux comprendre les marchés et leurs risques.</p>
    <div class="education-overview-progress">
      <div class="overview-progress-copy">
        <span>Progression générale</span>
        <strong data-overall-progress-label><?= (int) $educationProgress ?> %</strong>
      </div>
      <div class="progress-track" role="progressbar" aria-label="Progression générale" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $educationProgress ?>" data-overall-progress>
        <span style="width: <?= (int) $educationProgress ?>%"></span>
      </div>
      <span class="overview-progress-hint"><?= $totalLevels ?> niveaux · progression enregistrée sur votre compte</span>
    </div>
  </header>

  <?php if ($educationError !== null): ?>
    <div class="education-state education-state-error" role="alert">
      <strong>Parcours indisponibles</strong>
      <p><?= $escapeEducation($educationError) ?></p>
    </div>
  <?php elseif (!$educationModules): ?>
    <div class="education-state" role="status">
      <strong>Aucun parcours n’est disponible pour le moment.</strong>
      <p>Ajoutez les niveaux et leurs questions dans la base de données pour les afficher ici.</p>
    </div>
  <?php else: ?>
    <div class="education-level-view" data-level-overview>
      <div class="education-section-heading">
        <div>
          <span class="section-eyebrow">Votre itinéraire</span>
          <h2>Trois niveaux pour progresser</h2>
        </div>
        <span class="level-count"><?= $totalLevels ?> étapes</span>
      </div>

      <div class="education-level-grid">
        <?php foreach ($educationModules as $index => $module): ?>
          <?php
            $hasProgress = $module['isUnlocked'];
            $levelTitle = $escapeEducation($module['title']);
          ?>
          <article
            class="level-card <?= $hasProgress ? 'is-unlocked' : 'is-locked' ?>"
            data-level-card
            data-level-id="<?= (int) $module['id'] ?>"
          >
            <div class="level-card-top">
              <span class="level-number"><?= $escapeEducation($module['number']) ?></span>
              <span class="level-badge"><?= $escapeEducation($module['badge']) ?></span>
              <span class="level-lock-icon" data-lock-icon aria-label="<?= $hasProgress ? 'Déverrouillé' : 'Verrouillé' ?>">
                <svg class="lock-closed" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                <svg class="lock-open" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/></svg>
              </span>
            </div>
            <h3><?= $levelTitle ?></h3>
            <p class="level-summary"><?= $escapeEducation($module['summary']) ?></p>
            <div class="level-card-progress">
              <div class="level-progress-copy">
                <span>Votre progression</span>
                <strong data-level-progress-label><?= (int) $module['progress'] ?> %</strong>
              </div>
              <div class="progress-track" role="progressbar" aria-label="Progression du niveau <?= $levelTitle ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $module['progress'] ?>" data-level-progress>
                <span style="width: <?= (int) $module['progress'] ?>%"></span>
              </div>
            </div>
            <button type="button" class="level-unlock-button" data-unlock-button>
              <span data-level-action><?= $hasProgress ? 'Entrer dans le niveau' : 'Déverrouiller le niveau' ?></span>
              <span class="level-action-icon" aria-hidden="true"><?= $hasProgress ? '→' : '⌑' ?></span>
            </button>
            <span class="level-unlock-status" data-unlock-status aria-live="polite"><?= $hasProgress ? 'Votre parcours est ouvert' : 'Cliquez pour ouvrir ce parcours' ?></span>
          </article>
        <?php endforeach; ?>
      </div>

      <section class="education-achievements" aria-labelledby="achievements-title">
        <div class="education-section-heading achievements-heading">
          <div>
            <span class="section-eyebrow">Vos récompenses</span>
            <h2 id="achievements-title">Les succès à débloquer</h2>
          </div>
          <span class="achievement-count" data-achievement-count><?= count(array_filter($educationAchievements, static function ($item): bool { return $item['unlocked']; })) ?> / <?= count($educationAchievements) ?> obtenus</span>
        </div>
        <div class="achievement-grid" data-achievements>
          <?php foreach ($educationAchievements as $achievement): ?>
            <article class="achievement-card <?= $achievement['unlocked'] ? 'is-earned' : '' ?>" data-achievement="<?= $escapeEducation($achievement['key']) ?>">
              <span class="achievement-medal" aria-hidden="true"><?= $achievement['unlocked'] ? '✦' : '◇' ?></span>
              <div>
                <h3><?= $escapeEducation($achievement['title']) ?></h3>
                <p><?= $escapeEducation($achievement['description']) ?></p>
              </div>
              <span class="achievement-status"><?= $achievement['unlocked'] ? 'Obtenu' : 'À obtenir' ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <?php foreach ($educationModules as $module): ?>
      <?php
        $completedLessons = array_map('intval', $module['completedLessons']);
        $correctQuestions = array_map('intval', $module['correctQuestions']);
        $isStarted = $module['completedActivities'] > 0;
      ?>
      <section class="education-level-detail" data-level-detail="<?= (int) $module['id'] ?>" hidden>
        <button type="button" class="education-back-button" data-back-to-levels>
          <span aria-hidden="true">←</span> Retour aux niveaux
        </button>
        <header class="education-detail-hero">
          <div class="detail-title-group">
            <span class="level-badge"><?= $escapeEducation($module['badge']) ?></span>
            <span class="section-eyebrow">Niveau <?= $escapeEducation($module['number']) ?></span>
            <h2><?= $escapeEducation($module['title']) ?></h2>
            <p><?= $escapeEducation($module['summary']) ?></p>
          </div>
          <div class="detail-progress-card">
            <span>Progression du niveau</span>
            <strong data-detail-progress-label><?= (int) $module['progress'] ?> %</strong>
            <div class="progress-track" role="progressbar" aria-label="Progression du niveau <?= $escapeEducation($module['title']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $module['progress'] ?>" data-detail-progress>
              <span style="width: <?= (int) $module['progress'] ?>%"></span>
            </div>
            <small data-detail-activity-count><?= (int) $module['completedActivities'] ?> / <?= (int) $module['totalActivities'] ?> étapes complétées</small>
          </div>
        </header>

        <div class="education-start-panel" data-start-panel>
          <span class="start-symbol" aria-hidden="true">✦</span>
          <div>
            <strong><?= $isStarted ? 'Reprendre votre apprentissage' : 'Prêt à commencer ?' ?></strong>
            <p>Parcourez les notions, marquez les leçons terminées et répondez au quiz pour faire progresser votre niveau.</p>
          </div>
          <button type="button" class="education-start-button" data-start-learning>
            <?= $isStarted ? 'Continuer' : 'Commencer à apprendre' ?> <span aria-hidden="true">→</span>
          </button>
        </div>

        <div class="education-learning-content" data-learning-content hidden>
          <section class="pane-lessons" aria-label="Leçons du niveau">
            <div class="education-section-heading lesson-section-heading">
              <div>
                <span class="section-eyebrow">Étape 1</span>
                <h3>Les notions à retenir</h3>
              </div>
              <span class="level-count"><?= count($module['lessons']) ?> leçons</span>
            </div>
            <?php foreach ($module['lessons'] as $lessonIndex => $lesson): ?>
              <?php $lessonDone = in_array($lessonIndex + 1, $completedLessons, true); ?>
              <article class="lesson-card <?= $lessonDone ? 'is-complete' : '' ?>" data-lesson-card>
                <span class="lesson-index"><?= sprintf('%02d', $lessonIndex + 1) ?></span>
                <div class="lesson-copy">
                  <h4><?= $escapeEducation($lesson['title']) ?></h4>
                  <p><?= $escapeEducation($lesson['text']) ?></p>
                </div>
                <button
                  type="button"
                  class="lesson-complete-button"
                  data-complete-lesson
                  data-module-id="<?= (int) $module['id'] ?>"
                  data-lesson-number="<?= $lessonIndex + 1 ?>"
                  <?= $lessonDone ? 'disabled' : '' ?>
                ><?= $lessonDone ? 'Leçon terminée ✓' : 'Marquer comme terminée' ?></button>
              </article>
            <?php endforeach; ?>
          </section>

          <section class="pane-quizzes" aria-label="Quiz du niveau">
            <div class="education-section-heading lesson-section-heading">
              <div>
                <span class="section-eyebrow">Étape 2</span>
                <h3>Vérifiez vos acquis</h3>
              </div>
              <span class="level-count"><?= count($module['questions']) ?> questions</span>
            </div>
            <?php if (!$module['questions']): ?>
              <div class="quiz-empty">Les questions de ce niveau seront bientôt disponibles.</div>
            <?php else: ?>
              <?php foreach ($module['questions'] as $questionIndex => $question): ?>
                <?php $questionDone = in_array((int) $question['id'], $correctQuestions, true); ?>
                <article class="pane-quiz-box" data-quiz-box data-question-id="<?= (int) $question['id'] ?>" data-completed="<?= $questionDone ? 'true' : 'false' ?>">
                  <div class="quiz-header">
                    <span class="quiz-question-number">Question <?= $questionIndex + 1 ?></span>
                    <h4><?= $escapeEducation($question['question']) ?></h4>
                  </div>
                  <div class="quiz-options">
                    <?php foreach ($question['options'] as $optionIndex => $option): ?>
                      <button type="button" class="quiz-option-btn" data-option-index="<?= $optionIndex ?>">
                        <span class="opt-letter"><?= chr(65 + $optionIndex) ?></span>
                        <span class="opt-text"><?= $escapeEducation($option['text']) ?></span>
                      </button>
                    <?php endforeach; ?>
                  </div>
                  <div class="quiz-feedback" hidden aria-live="polite">
                    <p class="feedback-msg"></p>
                    <small class="feedback-desc"></small>
                    <button type="button" class="quiz-retry-button" data-retry-question hidden>Réessayer</button>
                  </div>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </section>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <aside class="education-notice">
    <span class="notice-icon" aria-hidden="true">i</span>
    <div>
      <strong>Un espace pour apprendre, pas un conseil financier</strong>
      <p>La simulation aide à découvrir les mécanismes des marchés. Tout investissement comporte un risque de perte en capital.</p>
    </div>
  </aside>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-education-root]');
  if (!root) return;

  const csrfToken = <?= json_encode($educationCsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const overview = root.querySelector('[data-level-overview]');
  const details = Array.from(root.querySelectorAll('[data-level-detail]'));

  const sendProgress = async (payload) => {
    const response = await fetch('index.php?action=education', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(payload)
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Impossible d’enregistrer votre progression.');
    return result;
  };

  const setProgressBar = (bar, label, value) => {
    if (bar) {
      bar.setAttribute('aria-valuenow', String(value));
      const fill = bar.querySelector('span');
      if (fill) fill.style.width = `${value}%`;
    }
    if (label) label.textContent = `${value} %`;
  };

  const updateAchievements = (achievements) => {
    const container = root.querySelector('[data-achievements]');
    if (!container) return;
    let earned = 0;
    achievements.forEach((achievement) => {
      const card = container.querySelector(`[data-achievement="${achievement.key}"]`);
      if (!card) return;
      card.classList.toggle('is-earned', achievement.unlocked);
      card.querySelector('.achievement-medal').textContent = achievement.unlocked ? '✦' : '◇';
      card.querySelector('.achievement-status').textContent = achievement.unlocked ? 'Obtenu' : 'À obtenir';
      if (achievement.unlocked) earned += 1;
    });
    const count = root.querySelector('[data-achievement-count]');
    if (count) count.textContent = `${earned} / ${achievements.length} obtenus`;
  };

  const updateProgress = (moduleId, result) => {
    const card = root.querySelector(`[data-level-card][data-level-id="${moduleId}"]`);
    const detail = root.querySelector(`[data-level-detail="${moduleId}"]`);
    if (card) {
      setProgressBar(card.querySelector('[data-level-progress]'), card.querySelector('[data-level-progress-label]'), result.levelProgress);
      if (result.levelProgress > 0) card.classList.add('is-unlocked');
    }
    if (detail) {
      setProgressBar(detail.querySelector('[data-detail-progress]'), detail.querySelector('[data-detail-progress-label]'), result.levelProgress);
      const count = detail.querySelector('[data-detail-activity-count]');
      if (count) count.textContent = `${result.completedActivities} / ${result.totalActivities} étapes complétées`;
    }
    setProgressBar(root.querySelector('[data-overall-progress]'), root.querySelector('[data-overall-progress-label]'), result.progress);
    updateAchievements(result.achievements);
  };

  root.querySelectorAll('[data-level-card]').forEach((card) => {
    const button = card.querySelector('[data-unlock-button]');
    const moduleId = card.dataset.levelId;
    const detail = root.querySelector(`[data-level-detail="${moduleId}"]`);
    let unlocked = card.classList.contains('is-unlocked');

    button.addEventListener('click', async () => {
      if (!unlocked) {
        button.disabled = true;
        try {
          await sendProgress({ type: 'unlock', moduleId: Number(moduleId) });
          card.classList.add('is-unlocking');
          window.setTimeout(() => {
            unlocked = true;
            card.classList.remove('is-locked', 'is-unlocking');
            card.classList.add('is-unlocked');
            card.querySelector('[data-lock-icon]').setAttribute('aria-label', 'Déverrouillé');
            card.querySelector('[data-level-action]').textContent = 'Entrer dans le niveau';
            card.querySelector('.level-action-icon').textContent = '→';
            card.querySelector('[data-unlock-status]').textContent = 'Niveau déverrouillé · prêt à explorer';
            button.disabled = false;
          }, 700);
        } catch (error) {
          button.disabled = false;
          window.alert(error.message);
        }
        return;
      }

      overview.hidden = true;
      detail.hidden = false;
      detail.classList.remove('detail-entering');
      void detail.offsetWidth;
      detail.classList.add('detail-entering');
      detail.querySelector('[data-start-learning]').focus();
    });
  });

  root.querySelectorAll('[data-back-to-levels]').forEach((button) => {
    button.addEventListener('click', () => {
      details.forEach((detail) => {
        detail.hidden = true;
      });
      overview.hidden = false;
      overview.querySelector('.level-unlock-button:focus')?.blur();
    });
  });

  root.querySelectorAll('[data-start-learning]').forEach((button) => {
    button.addEventListener('click', () => {
      const detail = button.closest('[data-level-detail]');
      const content = detail.querySelector('[data-learning-content]');
      content.hidden = false;
      button.closest('[data-start-panel]').hidden = true;
      content.classList.add('detail-entering');
      content.querySelector('[data-complete-lesson]:not(:disabled)')?.focus();
    });
  });

  root.querySelectorAll('[data-complete-lesson]').forEach((button) => {
    button.addEventListener('click', async () => {
      button.disabled = true;
      button.textContent = 'Enregistrement…';
      try {
        const result = await sendProgress({
          type: 'lesson',
          moduleId: Number(button.dataset.moduleId),
          lessonNumber: Number(button.dataset.lessonNumber)
        });
        button.textContent = 'Leçon terminée ✓';
        button.closest('[data-lesson-card]').classList.add('is-complete');
        updateProgress(button.dataset.moduleId, result);
      } catch (error) {
        button.disabled = false;
        button.textContent = 'Marquer comme terminée';
        window.alert(error.message);
      }
    });
  });

  root.querySelectorAll('[data-quiz-box]').forEach((box) => {
    const options = Array.from(box.querySelectorAll('.quiz-option-btn'));
    const feedback = box.querySelector('.quiz-feedback');
    const message = box.querySelector('.feedback-msg');
    const explanation = box.querySelector('.feedback-desc');
    const retryButton = box.querySelector('[data-retry-question]');

    const showCompleted = () => {
      options.forEach((option) => {
        option.disabled = true;
      });
      message.textContent = 'Question déjà validée ✓';
      message.classList.add('is-correct');
      explanation.textContent = 'Bonne réponse enregistrée dans votre progression.';
      feedback.hidden = false;
    };

    if (box.dataset.completed === 'true') showCompleted();

    options.forEach((option) => {
      option.addEventListener('click', async () => {
        options.forEach((item) => {
          item.disabled = true;
        });
        try {
          const result = await sendProgress({
            type: 'answer',
            questionId: Number(box.dataset.questionId),
            optionIndex: Number(option.dataset.optionIndex)
          });
          message.textContent = result.correct ? 'Bravo, bonne réponse !' : 'Ce n’est pas la bonne réponse.';
          message.classList.toggle('is-correct', result.correct);
          message.classList.toggle('is-incorrect', !result.correct);
          explanation.textContent = result.explanation;
          feedback.hidden = false;
          retryButton.hidden = result.correct;
          if (result.correct) {
            option.classList.add('is-valid');
            box.dataset.completed = 'true';
          } else {
            option.classList.add('is-invalid');
            const correctOption = options.find((item) => Number(item.dataset.optionIndex) === result.correctOptionIndex);
            if (correctOption) correctOption.classList.add('is-valid');
            retryButton.hidden = false;
          }
          updateProgress(box.closest('[data-level-detail]').dataset.levelDetail, result);
        } catch (error) {
          options.forEach((item) => {
            item.disabled = false;
          });
          window.alert(error.message);
        }
      });
    });

    retryButton.addEventListener('click', () => {
      options.forEach((option) => {
        option.disabled = false;
        option.classList.remove('is-invalid', 'is-valid');
      });
      message.textContent = '';
      message.classList.remove('is-correct', 'is-incorrect');
      explanation.textContent = '';
      feedback.hidden = true;
      retryButton.hidden = true;
    });
  });
});
</script>
