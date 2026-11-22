<?php
$sidebarCurrent = $activePage ?? '';
$sidebarClass = static fn(array $pages, string $extra = ''): string =>
    'sidebar-link' . ($extra !== '' ? ' ' . $extra : '') . (in_array($sidebarCurrent, $pages, true) ? ' active' : '');
$sidebarAria = static fn(array $pages): string =>
    in_array($sidebarCurrent, $pages, true) ? ' aria-current="page"' : '';
?>
<button type="button" class="sidebar-toggle" aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  <span>Menu</span>
</button>
<div class="sidebar-backdrop" hidden></div>

<aside id="dashboard-sidebar" class="dashboard-sidebar" aria-label="Menu principal">
  <div class="sidebar-drawer-header">
    <span>Menu</span>
    <button type="button" class="sidebar-close" aria-label="Fermer le menu">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
  </div>

  <nav class="sidebar-nav">
    <a class="<?= $sidebarClass(['dashboard']) ?>" href="index.php"<?= $sidebarAria(['dashboard']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V18a2 2 0 0 1-2 2h-3v-7H9v7H6a2 2 0 0 1-2-2v-7.5Z"/></svg>
      </span>
      <span>Tableau de bord</span>
    </a>
    <a class="<?= $sidebarClass(['accounts', 'Accounts']) ?>" href="index.php?action=accounts"<?= $sidebarAria(['accounts', 'Accounts']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm3 2h10M8 15h5"/></svg>
      </span>
      <span>Comptes</span>
    </a>
    <a class="<?= $sidebarClass(['transfers']) ?>" href="index.php?action=transfers"<?= $sidebarAria(['transfers']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 3 21 7l-4 4" />
          <path d="M3 7h18" />
          <path d="m7 21-4-4 4-4" />
          <path d="M21 17H3" />
        </svg>
      </span>
      <span>Virements</span>
    </a>
    <a class="<?= $sidebarClass(['education']) ?>" href="index.php?action=education"<?= $sidebarAria(['education']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
          <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
      </span>
      <span>Éducation</span>
    </a>
    <a class="<?= $sidebarClass(['investment']) ?>" href="index.php?action=investment"<?= $sidebarAria(['investment']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17" />
          <path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9" />
          <path d="m2 16 6 6" />
          <circle cx="16" cy="6" r="3" />
        </svg>
      </span>
      <span>Vos Investissements</span>
    </a>
    <a class="<?= $sidebarClass(['market', 'marketPage']) ?>" href="index.php?action=marketPage"<?= $sidebarAria(['market', 'marketPage']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
          <polyline points="16 7 22 7 22 13" />
        </svg>
      </span>
      <span>Marché</span>
    </a>
    <a class="<?= $sidebarClass(['history']) ?>" href="index.php?action=history"<?= $sidebarAria(['history']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
      </span>
      <span>Historique</span>
    </a>
    <a class="<?= $sidebarClass(['notifications']) ?>" href="index.php?action=notifications"<?= $sidebarAria(['notifications']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
      </span>
      <span>Notifications</span>
    </a>
    <?php if (!empty($_SESSION['user']['isAdmin'])): ?>
      <a class="<?= $sidebarClass(['admin']) ?>" href="index.php?action=admin"<?= $sidebarAria(['admin']) ?>>
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </span>
        <span>Administration</span>
      </a>
      <a class="<?= $sidebarClass(['adminSupport']) ?>" href="index.php?action=adminSupport"<?= $sidebarAria(['adminSupport']) ?>>
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 5h16v12H8l-4 4V5Z" />
            <path d="M8 9h8M8 13h5" />
          </svg>
        </span>
        <span>Centre d’aide</span>
      </a>
      <a class="<?= $sidebarClass(['securityAudit']) ?>" href="index.php?action=securityAudit"<?= $sidebarAria(['securityAudit']) ?>>
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3 20 6v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3Z" />
            <path d="m9 12 2 2 4-4" />
          </svg>
        </span>
        <span>Journal d’audit</span>
      </a>
    <?php endif; ?>
    <a class="<?= $sidebarClass(['support'], 'sidebar-support-link') ?>" href="index.php?action=support"<?= $sidebarAria(['support']) ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 5h16v12H8l-4 4V5Z" />
          <path d="M8 9h8M8 13h5" />
        </svg>
      </span>
      <span>Service clientèle</span>
    </a>
  </nav>
</aside>
<script src="public/js/sidebar.js" defer></script>
