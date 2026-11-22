<?php
$projectRoot = dirname(__DIR__, 2);
$pageTitle = 'Classement général | NovaBank';
$pageStylesheets = ['dashboard.css', 'leaderboard.css'];
$activePage = 'leaderboard';
$leaderboardRows = [];
$leaderboardError = null;
$leaderboardWarning = null;
$initialBalance = 1500.0;

require_once $projectRoot . '/configs/currency_display.php';
$cachedRate = getCachedCadUsdReferenceRate();

try {
    $pdo = getPDO();
    $stmt = $pdo->query(
        'SELECT u.`idUser`, u.`Alias`,
                COALESCE(u.`Balance`, 0) AS `CashBalance`,
                COALESCE(a.`CadAccounts`, 0) AS `CadAccounts`,
                COALESCE(a.`UsdAccounts`, 0) AS `UsdAccounts`,
                COALESCE(p.`InvestmentValue`, 0) AS `InvestmentValue`,
                COALESCE(p.`MissingPrices`, 0) AS `MissingPrices`
         FROM `User` u
         LEFT JOIN (
             SELECT `idUser`,
                    SUM(CASE WHEN `Currency` = \'CAD\' THEN `Money` ELSE 0 END) AS `CadAccounts`,
                    SUM(CASE WHEN `Currency` = \'USD\' THEN `Money` ELSE 0 END) AS `UsdAccounts`
             FROM `Account`
             WHERE `Status` NOT IN (\'closed\', \'Fermée\')
             GROUP BY `idUser`
         ) a ON a.`idUser` = u.`idUser`
         LEFT JOIN (
             SELECT si.`IdUser`,
                    SUM(CASE WHEN s.`LastPrice` > 0 THEN si.`Shares` * s.`LastPrice` ELSE 0 END) AS `InvestmentValue`,
                    SUM(CASE WHEN si.`Investement` > 0 AND (si.`Shares` <= 0 OR s.`LastPrice` IS NULL OR s.`LastPrice` <= 0) THEN 1 ELSE 0 END) AS `MissingPrices`
             FROM `StockInvest` si
             LEFT JOIN `Stock` s ON s.`idStock` = si.`idStock`
             GROUP BY si.`IdUser`
         ) p ON p.`IdUser` = u.`idUser`'
    );
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rankedUsers = [];

    foreach ($users as $user) {
        $usdAccounts = (float) $user['UsdAccounts'];
        if (abs($usdAccounts) > 0 && $cachedRate === null) {
            throw new RuntimeException('Le taux de conversion CAD/USD est indisponible.');
        }
        if ((int) $user['MissingPrices'] > 0) {
            $leaderboardWarning = 'Certains comptes sont temporairement exclus, car un cours de placement est indisponible.';
            continue;
        }

        $totalWealth = (float) $user['CashBalance']
            + (float) $user['CadAccounts']
            + ($cachedRate !== null ? $usdAccounts / $cachedRate['rate'] : 0.0)
            + (float) $user['InvestmentValue'];

        $rankedUsers[] = [
            'alias' => (string) $user['Alias'],
            'totalWealth' => $totalWealth,
            'gainPercent' => (($totalWealth - $initialBalance) / $initialBalance) * 100,
        ];
    }

    usort($rankedUsers, static function (array $first, array $second): int {
        $wealthOrder = $second['totalWealth'] <=> $first['totalWealth'];
        return $wealthOrder !== 0 ? $wealthOrder : strcasecmp($first['alias'], $second['alias']);
    });
    $leaderboardRows = array_slice($rankedUsers, 0, 10);
} catch (Throwable $e) {
    error_log('leaderboard.php failed to calculate account rankings: ' . $e->getMessage());
    $leaderboardError = 'Le classement est temporairement indisponible. Réessayez plus tard.';
}

$formatGainPercent = static function (float $gainPercent): string {
    $formatted = getCurrentLanguage() === 'en'
        ? number_format(abs($gainPercent), 2, '.', ',')
        : number_format(abs($gainPercent), 2, ',', ' ');
    return ($gainPercent > 0 ? '+' : ($gainPercent < 0 ? '−' : '')) . $formatted . ' %';
};

require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
  <?php require $projectRoot . '/views/layout/sidebar.php'; ?>

  <main class="dashboard-main leaderboard-page">
    <header class="leaderboard-header">
      <div>
        <p class="leaderboard-kicker">Votre espace financier</p>
        <h1>Classement général</h1>
        <p>Les 10 comptes ayant le patrimoine total le plus élevé.</p>
      </div>
      <div class="leaderboard-header-icon" aria-hidden="true">🏆</div>
    </header>

    <section class="leaderboard-card" aria-labelledby="leaderboard-title">
      <div class="leaderboard-card-heading">
        <div>
          <h2 id="leaderboard-title">Patrimoine et gains totaux</h2>
          <p>Classement cumulatif, mis à jour à chaque visite · montants affichés en CAD, USD ou EUR selon votre préférence</p>
        </div>
      </div>

      <?php if ($leaderboardError !== null): ?>
        <p class="leaderboard-state is-error" role="alert"><?= htmlspecialchars($leaderboardError, ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <?php if ($leaderboardWarning !== null): ?>
          <p class="leaderboard-warning" role="status"><?= htmlspecialchars($leaderboardWarning, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($leaderboardRows === []): ?>
          <div class="leaderboard-state">
            <span class="leaderboard-empty-icon" aria-hidden="true">↗</span>
            <h3>Aucun compte à afficher</h3>
            <p>Les comptes apparaîtront ici lorsque leurs soldes et placements pourront être évalués.</p>
          </div>
        <?php else: ?>
          <div class="leaderboard-columns" aria-hidden="true">
            <span>Rang</span>
            <span>Compte</span>
            <span>Patrimoine total</span>
            <span>Gain total</span>
          </div>
          <ol class="leaderboard-list">
            <?php foreach ($leaderboardRows as $index => $row): ?>
              <li class="leaderboard-row<?= $index === 0 ? ' is-first' : ($index === 1 ? ' is-second' : ($index === 2 ? ' is-third' : '')) ?>">
                <span class="leaderboard-rank" aria-label="Rang <?= $index + 1 ?>"><?= $index + 1 ?></span>
                <span class="leaderboard-alias"><?= htmlspecialchars($row['alias'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="leaderboard-total">
                  <small>Patrimoine</small>
                  <?= renderCurrencyAmount($row['totalWealth'], 'CAD') ?>
                </span>
                <strong class="leaderboard-profit<?= $row['gainPercent'] < 0 ? ' is-negative' : '' ?>">
                  <small>Gain total</small>
                  <?= htmlspecialchars($formatGainPercent($row['gainPercent']), ENT_QUOTES, 'UTF-8') ?>
                </strong>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
