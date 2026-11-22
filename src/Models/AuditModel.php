<?php

require_once dirname(__DIR__, 2) . '/configs/database.php';

class AuditModel
{
    private const RISK_LEVELS = ['info', 'low', 'medium', 'high', 'critical'];
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getPDO();
    }

    public static function record(
        string $eventType,
        string $riskLevel,
        string $summary,
        ?int $actorUserId = null,
        ?int $targetUserId = null,
        array $details = [],
        ?PDO $pdo = null
    ): void {
        if (!in_array($riskLevel, self::RISK_LEVELS, true)) {
            $riskLevel = 'medium';
        }

        try {
            $model = new self($pdo);
            $stmt = $model->pdo->prepare(
                'INSERT INTO `SecurityAuditLog`
                    (`eventType`, `riskLevel`, `summary`, `actorUserId`, `targetUserId`, `detailsJson`, `ipAddress`, `userAgent`)
                 VALUES (:eventType, :riskLevel, :summary, :actorUserId, :targetUserId, :detailsJson, :ipAddress, :userAgent)'
            );
            $stmt->execute([
                'eventType' => substr($eventType, 0, 64),
                'riskLevel' => $riskLevel,
                'summary' => mb_substr($summary, 0, 255, 'UTF-8'),
                'actorUserId' => $actorUserId && $actorUserId > 0 ? $actorUserId : null,
                'targetUserId' => $targetUserId && $targetUserId > 0 ? $targetUserId : null,
                'detailsJson' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
                'ipAddress' => self::clientIp(),
                'userAgent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512, 'UTF-8'),
            ]);
        } catch (Throwable $e) {
            error_log('Security audit event could not be stored: ' . $e->getMessage());
        }
    }

    public static function countRecentEvent(
        string $eventType,
        int $minutes = 15,
        ?string $ipAddress = null
    ): int {
        if ($minutes < 1 || !preg_match('/^[a-z0-9_.-]{1,64}$/i', $eventType)) {
            return 0;
        }

        try {
            $model = new self();
            $where = 'a.`eventType` = :eventType
                      AND a.`createdAt` >= (UTC_TIMESTAMP() - INTERVAL :minutes MINUTE)';
            $params = ['eventType' => $eventType, 'minutes' => $minutes];
            if ($ipAddress !== null && $ipAddress !== '') {
                $where .= ' AND a.`ipAddress` = :ipAddress';
                $params['ipAddress'] = $ipAddress;
            }
            $stmt = $model->pdo->prepare('SELECT COUNT(*) FROM `SecurityAuditLog` a WHERE ' . $where);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, $key === 'minutes' ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Unable to count recent audit events: ' . $e->getMessage());
            return 0;
        }
    }

    public static function isLoginLockedOut(int $failedAttempts = 5, int $minutes = 15): bool
    {
        $ipAddress = self::clientIp();
        return $ipAddress !== null
            && self::countRecentEvent('auth.login.failed', $minutes, $ipAddress) >= $failedAttempts;
    }

    public static function recordLoginBruteForceIfNeeded(int $failedAttempts = 5, int $minutes = 15): void
    {
        $ipAddress = self::clientIp();
        $observedAttempts = $ipAddress === null
            ? 0
            : self::countRecentEvent('auth.login.failed', $minutes, $ipAddress);
        if ($ipAddress === null
            || $observedAttempts < $failedAttempts
            || self::countRecentEvent('security.login.brute_force', $minutes, $ipAddress) > 0
        ) {
            return;
        }

        $details = self::getRecentLoginFailureDetails($minutes, $ipAddress);
        $details['failedAttemptsObserved'] = $observedAttempts;
        $details['failedAttemptsThreshold'] = $failedAttempts;
        $details['windowMinutes'] = $minutes;
        $details['lockoutMinutesRemaining'] = $minutes;
        $details['sourceIp'] = $ipAddress;
        $details['userAgent'] = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512, 'UTF-8');

        self::record(
            'security.login.brute_force',
            'critical',
            'Tentatives répétées de connexion détectées.',
            null,
            null,
            $details
        );
    }

    public static function recordUnusualLocationIfNeeded(
        int $userId,
        string $timezone,
        string $locale
    ): void {
        $timezone = trim($timezone);
        $locale = trim($locale);
        if ($userId <= 0 || $timezone === '' && $locale === '') {
            return;
        }

        try {
            $model = new self();
            $stmt = $model->pdo->prepare(
                'SELECT `detailsJson`
                 FROM `SecurityAuditLog`
                 WHERE `eventType` = :eventType
                   AND `targetUserId` = :userId
                 ORDER BY `createdAt` DESC
                 LIMIT 10'
            );
            $stmt->execute(['eventType' => 'auth.login.success', 'userId' => $userId]);

            $timezones = [];
            $regions = [];
            $previousLogins = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $details = json_decode((string) ($row['detailsJson'] ?? ''), true);
                if (!is_array($details)) {
                    continue;
                }
                $previousLogins++;
                $previousTimezone = trim((string) ($details['timezone'] ?? ''));
                $previousLocale = trim((string) ($details['locale'] ?? ''));
                if ($previousTimezone !== '') {
                    $timezones[$previousTimezone] = ($timezones[$previousTimezone] ?? 0) + 1;
                }
                $previousRegion = self::localeRegion($previousLocale);
                if ($previousRegion !== '') {
                    $regions[$previousRegion] = ($regions[$previousRegion] ?? 0) + 1;
                }
            }

            if ($previousLogins < 3) {
                return;
            }

            arsort($timezones);
            arsort($regions);
            $baselineTimezone = (string) array_key_first($timezones);
            $baselineRegion = (string) array_key_first($regions);
            $currentRegion = self::localeRegion($locale);

            $timezoneChanged = $baselineTimezone !== '' && $timezone !== '' && $baselineTimezone !== $timezone;
            $regionChanged = $baselineRegion !== '' && $currentRegion !== '' && $baselineRegion !== $currentRegion;
            if (!$timezoneChanged && !$regionChanged) {
                return;
            }

            self::record(
                'security.login.location_changed',
                'critical',
                'Connexion depuis un lieu inhabituel.',
                $userId,
                $userId,
                [
                    'timezoneCurrent' => $timezone,
                    'timezoneUsual' => $baselineTimezone,
                    'localeCurrent' => $locale,
                    'regionCurrent' => $currentRegion,
                    'regionUsual' => $baselineRegion,
                    'previousSuccessfulLogins' => $previousLogins,
                    'detectionMethod' => 'timezone and browser locale',
                ]
            );
        } catch (Throwable $e) {
            error_log('Unable to detect unusual login location: ' . $e->getMessage());
        }
    }

    private static function localeRegion(string $locale): string
    {
        if (preg_match('/(?:^|-)([A-Z]{2})(?:-|$)/', $locale, $matches)) {
            return strtoupper($matches[1]);
        }
        return '';
    }

    private static function getRecentLoginFailureDetails(int $minutes, string $ipAddress): array
    {
        try {
            $model = new self();
            $stmt = $model->pdo->prepare(
                'SELECT `createdAt`, `detailsJson`
                 FROM `SecurityAuditLog`
                 WHERE `eventType` = :eventType
                   AND `ipAddress` = :ipAddress
                   AND `createdAt` >= (UTC_TIMESTAMP() - INTERVAL :minutes MINUTE)
                 ORDER BY `createdAt` ASC'
            );
            $stmt->bindValue(':eventType', 'auth.login.failed');
            $stmt->bindValue(':ipAddress', $ipAddress);
            $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
            $stmt->execute();

            $firstAttempt = null;
            $lastAttempt = null;
            $targetedAccounts = [];
            $existingAccounts = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $firstAttempt ??= (string) $row['createdAt'];
                $lastAttempt = (string) $row['createdAt'];
                $data = json_decode((string) ($row['detailsJson'] ?? ''), true);
                if (!is_array($data)) {
                    continue;
                }
                if (!empty($data['accountFound'])) {
                    $existingAccounts++;
                }
                $email = trim((string) ($data['attemptedEmail'] ?? ''));
                if ($email !== '' && !in_array($email, $targetedAccounts, true)) {
                    $targetedAccounts[] = mb_substr($email, 0, 255, 'UTF-8');
                }
            }

            return [
                'firstAttemptAt' => $firstAttempt,
                'lastAttemptAt' => $lastAttempt,
                'existingAccountsTargeted' => $existingAccounts,
                'targetedAccounts' => $targetedAccounts,
            ];
        } catch (Throwable $e) {
            error_log('Unable to aggregate recent login failures: ' . $e->getMessage());
            return [];
        }
    }

    public function getEvents(array $filters, int $page = 1, int $perPage = 50): array
    {
        $where = [];
        $params = [];
        if (in_array($filters['risk'] ?? '', self::RISK_LEVELS, true)) {
            $where[] = 'a.`riskLevel` = :risk';
            $params['risk'] = $filters['risk'];
        }
        if (is_string($filters['event'] ?? null) && preg_match('/^[a-z0-9_.-]{1,64}$/i', $filters['event'])) {
            $where[] = 'a.`eventType` = :eventType';
            $params['eventType'] = $filters['event'];
        }
        foreach (['from' => '>=', 'to' => '<'] as $key => $operator) {
            $date = $this->validDate((string) ($filters[$key] ?? ''));
            if ($date !== null) {
                $where[] = 'a.`createdAt` ' . $operator . ' :date_' . $key;
                $params['date_' . $key] = $key === 'to' ? $date->modify('+1 day')->format('Y-m-d') : $date->format('Y-m-d');
            }
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM `SecurityAuditLog` a ' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $page = max(1, $page);
        $perPage = max(10, min($perPage, 100));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $stmt = $this->pdo->prepare(
            'SELECT a.`idAudit`, a.`eventType`, a.`riskLevel`, a.`summary`, a.`actorUserId`, a.`targetUserId`,
                    a.`detailsJson`, a.`ipAddress`, a.`userAgent`, a.`createdAt`,
                    actor.`Alias` AS `actorAlias`, target.`Alias` AS `targetAlias`
             FROM `SecurityAuditLog` a
             LEFT JOIN `User` actor ON actor.`idUser` = a.`actorUserId`
             LEFT JOIN `User` target ON target.`idUser` = a.`targetUserId`
             ' . $whereSql . '
             ORDER BY a.`createdAt` DESC, a.`idAudit` DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['events' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function getEventTypes(): array
    {
        return $this->pdo->query('SELECT DISTINCT `eventType` FROM `SecurityAuditLog` ORDER BY `eventType`')->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function clientIp(): ?string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private function validDate(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
