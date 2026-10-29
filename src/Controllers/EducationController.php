<?php

final class EducationController
{
    private const LESSONS = [
        'module-1' => [
            [
                'title' => 'Comprendre ce que vous achetez',
                'text' => 'Une action représente une part d’une entreprise. Sa valeur peut monter ou baisser : le capital investi n’est jamais garanti.',
            ],
            [
                'title' => 'Le temps et les fluctuations',
                'text' => 'Les marchés bougent chaque jour. Un horizon de placement adapté aide à traverser les variations sans confondre une baisse temporaire avec une perte définitive.',
            ],
        ],
        'module-2' => [
            [
                'title' => 'Répartir pour limiter le risque',
                'text' => 'Diversifier entre plusieurs entreprises et secteurs réduit la dépendance à un seul titre. Cela ne protège toutefois pas d’une baisse générale des marchés.',
            ],
            [
                'title' => 'Risque et rendement',
                'text' => 'Un rendement potentiel élevé s’accompagne souvent d’un risque de perte plus important. Méfiez-vous des promesses de gains garantis.',
            ],
        ],
        'module-3' => [
            [
                'title' => 'Lire le marché avant d’agir',
                'text' => 'Un cours reflète les échanges et les anticipations des investisseurs. Les nouvelles, la liquidité et le contexte économique peuvent provoquer des variations rapides.',
            ],
            [
                'title' => 'Maîtriser ses décisions',
                'text' => 'Définissez à l’avance votre budget, votre horizon et votre tolérance aux pertes. Un ordre au marché peut être exécuté à un prix différent de celui observé.',
            ],
        ],
    ];

    public static function getLevels(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->query(
            'SELECT m.idModule, m.ModuleKey, m.Title, m.Badge, m.Summary, m.DisplayOrder,
                    q.idQuestion, q.Question, q.Explanation, q.OptionsJson
             FROM EducationModule m
             LEFT JOIN EducationQuestion q ON q.idModule = m.idModule
             ORDER BY m.DisplayOrder, m.idModule, q.idQuestion'
        );

        $levels = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $moduleId = (int) $row['idModule'];
            if (!isset($levels[$moduleId])) {
                $key = (string) $row['ModuleKey'];
                $levels[$moduleId] = [
                    'id' => $moduleId,
                    'key' => $key,
                    'number' => sprintf('%02d', count($levels) + 1),
                    'title' => (string) $row['Title'],
                    'badge' => (string) $row['Badge'],
                    'summary' => (string) ($row['Summary'] ?? ''),
                    'lessons' => self::LESSONS[$key] ?? [],
                    'questions' => [],
                    'isUnlocked' => false,
                    'completedLessons' => [],
                    'correctQuestions' => [],
                    'progress' => 0,
                    'completedActivities' => 0,
                    'totalActivities' => count(self::LESSONS[$key] ?? []),
                ];
            }

            if ($row['idQuestion'] === null) {
                continue;
            }

            $options = json_decode((string) $row['OptionsJson'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($options) || count($options) < 2) {
                throw new UnexpectedValueException('Invalid education question options.');
            }

            foreach ($options as $option) {
                if (
                    !is_array($option)
                    || !isset($option['text'])
                    || !is_string($option['text'])
                    || !isset($option['isCorrect'])
                    || !is_bool($option['isCorrect'])
                ) {
                    throw new UnexpectedValueException('Invalid education question option format.');
                }
            }

            $levels[$moduleId]['questions'][] = [
                'id' => (int) $row['idQuestion'],
                'question' => (string) $row['Question'],
                'explanation' => (string) ($row['Explanation'] ?? ''),
                'options' => $options,
            ];
        }

        $unlockStmt = $pdo->prepare(
            'SELECT idModule FROM EducationLevelUnlock WHERE idUser = :userId'
        );
        $unlockStmt->execute(['userId' => $userId]);
        foreach ($unlockStmt->fetchAll(PDO::FETCH_COLUMN) as $moduleId) {
            $moduleId = (int) $moduleId;
            if (isset($levels[$moduleId])) {
                $levels[$moduleId]['isUnlocked'] = true;
            }
        }

        $lessonStmt = $pdo->prepare(
            'SELECT idModule, LessonNumber FROM EducationLessonProgress WHERE idUser = :userId'
        );
        $lessonStmt->execute(['userId' => $userId]);
        foreach ($lessonStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $moduleId = (int) $row['idModule'];
            if (isset($levels[$moduleId])) {
                $levels[$moduleId]['completedLessons'][] = (int) $row['LessonNumber'];
            }
        }

        $questionStmt = $pdo->prepare(
            'SELECT q.idModule, p.idQuestion
             FROM EducationQuestionProgress p
             INNER JOIN EducationQuestion q ON q.idQuestion = p.idQuestion
             WHERE p.idUser = :userId'
        );
        $questionStmt->execute(['userId' => $userId]);
        foreach ($questionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $moduleId = (int) $row['idModule'];
            if (isset($levels[$moduleId])) {
                $levels[$moduleId]['correctQuestions'][] = (int) $row['idQuestion'];
            }
        }

        $levels = array_values($levels);
        $allActivities = 0;
        $completedActivities = 0;
        $completedLessons = 0;
        $correctAnswers = 0;

        foreach ($levels as &$level) {
            $level['totalActivities'] += count($level['questions']);
            $level['completedActivities'] = count($level['completedLessons']) + count($level['correctQuestions']);
            if ($level['completedActivities'] > 0) {
                $level['isUnlocked'] = true;
            }
            $level['progress'] = $level['totalActivities'] > 0
                ? (int) floor($level['completedActivities'] * 100 / $level['totalActivities'])
                : 0;
            $allActivities += $level['totalActivities'];
            $completedActivities += $level['completedActivities'];
            $completedLessons += count($level['completedLessons']);
            $correctAnswers += count($level['correctQuestions']);
        }
        unset($level);

        $totalProgress = $allActivities > 0 ? (int) floor($completedActivities * 100 / $allActivities) : 0;
        $achievements = [
            [
                'key' => 'first-step',
                'title' => 'Premier pas',
                'description' => 'Terminer une première leçon.',
                'unlocked' => $completedLessons > 0,
            ],
            [
                'key' => 'quiz-starter',
                'title' => 'Esprit curieux',
                'description' => 'Réussir une première question.',
                'unlocked' => $correctAnswers > 0,
            ],
            [
                'key' => 'academy-graduate',
                'title' => 'Diplômé NovaBank',
                'description' => 'Terminer toutes les leçons et questions.',
                'unlocked' => $allActivities > 0 && $completedActivities >= $allActivities,
            ],
        ];

        return [
            'levels' => $levels,
            'progress' => $totalProgress,
            'completedActivities' => $completedActivities,
            'totalActivities' => $allActivities,
            'achievements' => $achievements,
        ];
    }

    public static function unlockLevel(PDO $pdo, int $userId, int $moduleId): void
    {
        $module = $pdo->prepare('SELECT 1 FROM EducationModule WHERE idModule = :moduleId');
        $module->execute(['moduleId' => $moduleId]);
        if ($module->fetchColumn() === false) {
            throw new InvalidArgumentException('Niveau invalide.');
        }

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO EducationLevelUnlock (idUser, idModule)
             VALUES (:userId, :moduleId)'
        );
        $stmt->execute(['userId' => $userId, 'moduleId' => $moduleId]);
    }

    public static function recordLesson(PDO $pdo, int $userId, int $moduleId, int $lessonNumber): void
    {
        $module = self::getModuleLessonCount($pdo, $moduleId);
        if ($module === null || $lessonNumber < 1 || $lessonNumber > $module['lessonCount']) {
            throw new InvalidArgumentException('Leçon invalide.');
        }
        self::assertLevelUnlocked($pdo, $userId, $moduleId);

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO EducationLessonProgress (idUser, idModule, LessonNumber)
             VALUES (:userId, :moduleId, :lessonNumber)'
        );
        $stmt->execute([
            'userId' => $userId,
            'moduleId' => $moduleId,
            'lessonNumber' => $lessonNumber,
        ]);
    }

    public static function recordAnswer(PDO $pdo, int $userId, int $questionId, int $optionIndex): array
    {
        $stmt = $pdo->prepare(
            'SELECT idModule, Question, Explanation, OptionsJson
             FROM EducationQuestion
             WHERE idQuestion = :questionId'
        );
        $stmt->execute(['questionId' => $questionId]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$question) {
            throw new InvalidArgumentException('Question invalide.');
        }
        self::assertLevelUnlocked($pdo, $userId, (int) $question['idModule']);

        $options = json_decode((string) $question['OptionsJson'], true, 512, JSON_THROW_ON_ERROR);
        if (!isset($options[$optionIndex]) || !is_array($options[$optionIndex])) {
            throw new InvalidArgumentException('Réponse invalide.');
        }

        $isCorrect = $options[$optionIndex]['isCorrect'] === true;
        $correctOptionIndex = null;
        foreach ($options as $index => $candidate) {
            if (($candidate['isCorrect'] ?? false) === true) {
                $correctOptionIndex = $index;
                break;
            }
        }
        if ($isCorrect) {
            $save = $pdo->prepare(
                'INSERT IGNORE INTO EducationQuestionProgress (idUser, idQuestion)
                 VALUES (:userId, :questionId)'
            );
            $save->execute([
                'userId' => $userId,
                'questionId' => $questionId,
            ]);
        }

        $state = self::getLevels($pdo, $userId);
        foreach ($state['levels'] as $level) {
            if ((int) $level['id'] === (int) $question['idModule']) {
                return [
                    'correct' => $isCorrect,
                    'correctOptionIndex' => $correctOptionIndex,
                    'explanation' => (string) ($question['Explanation'] ?? ''),
                    'levelProgress' => $level['progress'],
                    'completedActivities' => $level['completedActivities'],
                    'totalActivities' => $level['totalActivities'],
                    'progress' => $state['progress'],
                    'achievements' => $state['achievements'],
                ];
            }
        }

        throw new RuntimeException('Question module not found.');
    }

    private static function getModuleLessonCount(PDO $pdo, int $moduleId): ?array
    {
        $stmt = $pdo->prepare('SELECT ModuleKey FROM EducationModule WHERE idModule = :moduleId');
        $stmt->execute(['moduleId' => $moduleId]);
        $key = $stmt->fetchColumn();
        if ($key === false) {
            return null;
        }

        return ['lessonCount' => count(self::LESSONS[(string) $key] ?? [])];
    }

    private static function assertLevelUnlocked(PDO $pdo, int $userId, int $moduleId): void
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM EducationLevelUnlock WHERE idUser = :userId AND idModule = :moduleId'
        );
        $stmt->execute(['userId' => $userId, 'moduleId' => $moduleId]);
        if ($stmt->fetchColumn() === false) {
            throw new InvalidArgumentException('Déverrouillez ce niveau avant de commencer.');
        }
    }
}
