
(() => {
  const sidebar = document.getElementById('dashboard-sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  const backdrop = document.querySelector('.sidebar-backdrop');
  if (!sidebar || !toggle || !backdrop) {
    return;
  }

  const closeButton = sidebar.querySelector('.sidebar-close');
  const mobile = window.matchMedia('(max-width: 980px)');
  const isOpen = () => sidebar.classList.contains('is-open');

  const setOpen = (open, moveFocus = true) => {
    sidebar.classList.toggle('is-open', open);
    document.body.classList.toggle('sidebar-open', open);
    backdrop.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');

  
    sidebar.inert = mobile.matches && !open;

    if (moveFocus) {
      const target = open
        ? sidebar.querySelector('.sidebar-link.active') || sidebar.querySelector('.sidebar-link')
        : toggle;
      target?.focus({ preventScroll: true });
    }
  };

  toggle.addEventListener('click', () => setOpen(!isOpen()));
  closeButton?.addEventListener('click', () => setOpen(false));
  backdrop.addEventListener('click', () => setOpen(false));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      setOpen(false);
    }
  });

  
  sidebar.addEventListener('click', (event) => {
    if (mobile.matches && event.target.closest('a')) {
      setOpen(false, false);
    }
  });


  mobile.addEventListener('change', () => setOpen(false, false));
  setOpen(false, false);
})();
