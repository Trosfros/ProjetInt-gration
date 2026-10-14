<aside class="dashboard-sidebar" aria-label="Menu principal">
  <nav class="sidebar-nav">
    <a class="sidebar-link" href="index.php">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V18a2 2 0 0 1-2 2h-3v-7H9v7H6a2 2 0 0 1-2-2v-7.5Z"/></svg>
      </span>
      <span>Tableau de bord</span>
    </a>
    <a class="sidebar-link" href="index.php?action=accounts">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm3 2h10M8 15h5"/></svg>
      </span>
      <span>Comptes</span>
    </a>
    <a class="sidebar-link" href="index.php">
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
    <a class="sidebar-link<?= ($activePage ?? '') === 'education' ? ' active' : '' ?>" href="index.php?action=education"<?= ($activePage ?? '') === 'education' ? ' aria-current="page"' : '' ?>>
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
          <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
      </span>
      <span>Éducation</span>
    </a>
    <a class="sidebar-link" href="index.php?action=investment">
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
    <a class="sidebar-link" href="index.php?action=marketPage">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
          <polyline points="16 7 22 7 22 13" />
        </svg>
      </span>
      <span>Marché</span>
    </a>
    <a class="sidebar-link" href="index.php">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 7.5A2.5 2.5 0 0 1 6 5h12a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 18 19H6a2.5 2.5 0 0 1-2.5-2.5v-9Zm2 1.5h13"/></svg>
      </span>
      <span>Cartes</span>
    </a>
    <a class="sidebar-link" href="index.php">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
      </span>
      <span>Historique</span>
    </a>
    <a class="sidebar-link" href="index.php">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
      </span>
      <span>Paramètres</span>
    </a>
    <a class="sidebar-link logout-link" href="index.php?action=logout">
      <span class="sidebar-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      </span>
      <span>Déconnexion</span>
    </a>
  </nav>
</aside>
