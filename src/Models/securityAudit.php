<?php

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/src/Models/AuditModel.php';

$filters = [
    'risk' => is_string($_GET['risk'] ?? null) ? $_GET['risk'] : '',
    'event' => is_string($_GET['event'] ?? null) ? $_GET['event'] : '',
    'from' => is_string($_GET['from'] ?? null) ? $_GET['from'] : '',
    'to' => is_string($_GET['to'] ?? null) ? $_GET['to'] : '',
];
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page === false ? 1 : max(1, $page);
$auditError = null;
$result = ['events' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$eventTypes = [];

try {
    $auditModel = new AuditModel();
    $result = $auditModel->getEvents($filters, $page);
    $eventTypes = $auditModel->getEventTypes();
} catch (Throwable $e) {
    error_log('securityAudit.php failed to load audit events: ' . $e->getMessage());
    $auditError = 'Le journal n’est pas encore installé. Importez le fichier schemes/audit.sql dans la base bdnovabank.';
}

$riskLabels = [
    'info' => 'Information',
    'low' => 'Faible',
    'medium' => 'Modéré',
    'high' => 'Élevé',
    'critical' => 'Critique',
];
$detailLabels = [
    'action' => 'Opération',
    'accountId' => 'ID du compte',
    'accountType' => 'Type de compte',
    'amount' => 'Montant',
    'currency' => 'Devise',
    'direction' => 'Sens',
    'estimatedValue' => 'Valeur estimée',
    'isFavorite' => 'Ajoutée aux favoris',
    'moduleId' => 'ID du niveau',
    'questionId' => 'ID de la question',
    'recipientCount' => 'Nombre de destinataires',
    'requestId' => 'ID de la demande',
    'shares' => 'Nombre d’actions',
    'stockId' => 'ID de l’action',
    'stockName' => 'Action',
    'unitPrice' => 'Prix unitaire',
    'method' => 'Méthode HTTP',
    'endpoint' => 'Endpoint',
    'failedAttemptsWindow' => 'Nombre de tentatives',
    'windowMinutes' => 'Fenêtre de détection',
];
$pageTitle = 'Audit de sécurité | NovaBank';
$pageStylesheets = ['dashboard.css', 'admin.css', 'audit.css'];
$activePage = 'securityAudit';
require $projectRoot . '/views/layout/header.php';
?>

<div class="dashboard-layout">
  <?php require $projectRoot . '/views/layout/sidebar.php'; ?>
  <main class="dashboard-main audit-page">
    <div class="audit-shell">
      <header class="audit-heading">
        <p class="audit-kicker">Contrôle et traçabilité</p>
        <h1>Journal d’audit</h1>
        <p>Connexions, comptes, opérations financières et actions d’administration.</p>
      </header>

      <?php if ($auditError !== null): ?>
        <div class="audit-notice" role="alert"><?= htmlspecialchars($auditError, ENT_QUOTES, 'UTF-8') ?></div>
      <?php else: ?>
        <form class="audit-filters" method="get" action="index.php">
          <input type="hidden" name="action" value="securityAudit">
          <label>Niveau de risque
            <select name="risk">
              <option value="">Tous les niveaux</option>
              <?php foreach ($riskLabels as $key => $label): ?>
                <option value="<?= $key ?>"<?= $filters['risk'] === $key ? ' selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Événement
            <select name="event">
              <option value="">Tous les événements</option>
              <?php foreach ($eventTypes as $eventType): ?>
                <option value="<?= htmlspecialchars((string) $eventType, ENT_QUOTES, 'UTF-8') ?>"<?= $filters['event'] === $eventType ? ' selected' : '' ?>><?= htmlspecialchars((string) $eventType, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Du
            <input type="date" name="from" value="<?= htmlspecialchars($filters['from'], ENT_QUOTES, 'UTF-8') ?>">
          </label>
          <label>Au
            <input type="date" name="to" value="<?= htmlspecialchars($filters['to'], ENT_QUOTES, 'UTF-8') ?>">
          </label>
          <button type="submit" class="btn-primary">Filtrer</button>
          <a class="audit-reset" href="index.php?action=securityAudit">Réinitialiser</a>
        </form>

        <div class="audit-summary"><?= number_format($result['total'], 0, ',', ' ') ?> événement(s) enregistré(s)</div>
        <?php if (!$result['events']): ?>
          <div class="audit-empty">Aucun événement ne correspond à ces filtres.</div>
        <?php else: ?>
          <div class="audit-table-wrap">
            <table class="audit-table">
              <thead><tr><th>Date</th><th>Risque</th><th>Événement</th><th>Résumé</th><th>Acteur</th><th>Utilisateur concerné</th><th>Détails</th></tr></thead>
              <tbody>
              <?php foreach ($result['events'] as $event): ?>
                <?php $risk = (string) $event['riskLevel']; $details = json_decode((string) ($event['detailsJson'] ?? ''), true); ?>
                <tr>
                  <td class="audit-date"><?= htmlspecialchars(getQuebecDateTime((string) $event['createdAt'])->format('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="audit-risk risk-<?= htmlspecialchars($risk, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($riskLabels[$risk] ?? $risk, ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><code><?= htmlspecialchars((string) $event['eventType'], ENT_QUOTES, 'UTF-8') ?></code></td>
                  <td><?= htmlspecialchars((string) $event['summary'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($event['actorAlias'] ?? ($event['actorUserId'] ? '#' . $event['actorUserId'] : 'Système/inconnu')), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($event['targetAlias'] ?? ($event['targetUserId'] ? '#' . $event['targetUserId'] : '—')), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <button type="button" class="audit-detail-button" data-audit-detail="<?= (int) $event['idAudit'] ?>">Voir</button>
                    <template id="audit-detail-<?= (int) $event['idAudit'] ?>">
                      <dl class="audit-detail-list">
                        <dt>ID de l’événement</dt><dd><?= (int) $event['idAudit'] ?></dd>
                        <dt>Type d’événement</dt><dd><code><?= htmlspecialchars((string) $event['eventType'], ENT_QUOTES, 'UTF-8') ?></code></dd>
                        <dt>Niveau de risque</dt><dd><?= htmlspecialchars($riskLabels[$risk] ?? $risk, ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Utilisateur acteur</dt><dd><?= $event['actorUserId'] !== null ? '#' . (int) $event['actorUserId'] : 'Système/inconnu' ?></dd>
                        <dt>Utilisateur concerné</dt><dd><?= $event['targetUserId'] !== null ? '#' . (int) $event['targetUserId'] : '—' ?></dd>
                        <?php foreach (is_array($details) ? $details : [] as $key => $value): ?>
                          <?php
                            $detailKey = (string) $key;
                            $detailLabel = $detailLabels[$detailKey] ?? ucfirst(str_replace('_', ' ', $detailKey));
                            if ($detailKey === 'action') {
                              $detailValue = $value === 'buy' ? 'Achat' : ($value === 'sell' ? 'Vente' : (string) $value);
                            } elseif ($detailKey === 'direction') {
                              $detailValue = $value === 'credit' ? 'Crédit' : ($value === 'debit' ? 'Débit' : (string) $value);
                            } elseif ($detailKey === 'isFavorite') {
                              $detailValue = $value ? 'Oui' : 'Non';
                            } elseif (is_bool($value)) {
                              $detailValue = $value ? 'Oui' : 'Non';
                            } elseif (is_scalar($value)) {
                              $detailValue = (string) $value;
                            } else {
                              $detailValue = (string) json_encode($value, JSON_UNESCAPED_UNICODE);
                            }
                            if (in_array($detailKey, ['amount', 'estimatedValue', 'unitPrice'], true) && is_numeric($detailValue)) {
                              $detailValue .= ' CAD';
                            }
                          ?>
                          <dt><?= htmlspecialchars($detailLabel, ENT_QUOTES, 'UTF-8') ?></dt>
                          <dd><?= htmlspecialchars($detailValue, ENT_QUOTES, 'UTF-8') ?></dd>
                        <?php endforeach; ?>
                        <dt>Adresse IP</dt><dd><?= htmlspecialchars((string) ($event['ipAddress'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Navigateur</dt><dd><?= htmlspecialchars((string) ($event['userAgent'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                      </dl>
                    </template>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <dialog class="audit-detail-dialog" id="audit-detail-dialog" aria-labelledby="audit-detail-title">
            <div class="audit-detail-dialog-header">
              <div>
                <p class="audit-kicker">Événement d’audit</p>
                <h2 id="audit-detail-title">Détails de l’événement</h2>
              </div>
              <button type="button" class="audit-detail-close" data-audit-detail-close aria-label="Fermer">×</button>
            </div>
            <div class="audit-detail-dialog-body" data-audit-detail-body></div>
          </dialog>
          <script>
            (() => {
              const dialog = document.getElementById('audit-detail-dialog');
              const body = dialog?.querySelector('[data-audit-detail-body]');
              if (!dialog || !body) return;

              document.querySelectorAll('[data-audit-detail]').forEach((button) => {
                button.addEventListener('click', () => {
                  const template = document.getElementById(`audit-detail-${button.dataset.auditDetail}`);
                  body.replaceChildren();
                  if (template) body.append(template.content.cloneNode(true));
                  dialog.showModal();
                });
              });

              dialog.querySelector('[data-audit-detail-close]')?.addEventListener('click', () => dialog.close());
              dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
              });
            })();
          </script>
          <?php if ($result['pages'] > 1): ?>
            <nav class="audit-pagination" aria-label="Pagination">
              <?php for ($pageNumber = max(1, $result['page'] - 3); $pageNumber <= min($result['pages'], $result['page'] + 3); $pageNumber++): ?>
                <?php $query = http_build_query(array_filter(['action' => 'securityAudit', 'risk' => $filters['risk'], 'event' => $filters['event'], 'from' => $filters['from'], 'to' => $filters['to'], 'page' => $pageNumber], static fn($value) => $value !== '')); ?>
                <a href="index.php?<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"<?= $pageNumber === $result['page'] ? ' aria-current="page"' : '' ?>><?= $pageNumber ?></a>
              <?php endfor; ?>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php require $projectRoot . '/views/layout/footer.php'; ?>
