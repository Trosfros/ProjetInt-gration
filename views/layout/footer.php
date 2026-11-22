<footer>
  <div class="footer-inner">
    <div class="footer-content">
      <p>© 2026 NovaBank. Simulateur éducatif d'investissement boursier et bancaire. Ne constitue pas un conseil financier.</p>
      <a class="footer-privacy-link" href="index.php?action=privacy">Cookies et confidentialité</a>
    </div>
  </div>
</footer>

<script src="public/site-language.js"></script>
<aside class="cookie-notice" id="cookie-notice" role="region" aria-label="Avis sur les cookies"
  aria-labelledby="cookie-notice-title" hidden>
  <div class="cookie-notice-copy">
    <h2 id="cookie-notice-title">Les cookies et le stockage local</h2>
    <p>NovaBank utilise des cookies de session et des préférences (thème, devise et langue) pour faire fonctionner le site et mémoriser vos choix. Aucun cookie publicitaire ou de mesure d’audience n’est actuellement configuré.</p>
    <a href="index.php?action=privacy">En savoir plus</a>
  </div>
  <button class="cookie-notice-dismiss" type="button">Compris</button>
</aside>
<script>
  (() => {
    const notice = document.getElementById('cookie-notice');
    const dismissButton = notice.querySelector('.cookie-notice-dismiss');
    const storageKey = 'novabank_cookie_notice_dismissed_v1';

    try {
      if (localStorage.getItem(storageKey) === 'true') {
        return;
      }
    } catch (error) {
      console.warn('Unable to read the cookie notice preference.', error);
    }

    notice.hidden = false;
    window.requestAnimationFrame(() => notice.classList.add('is-visible'));

    dismissButton.addEventListener('click', () => {
      try {
        localStorage.setItem(storageKey, 'true');
      } catch (error) {
        console.warn('Unable to save the cookie notice preference.', error);
      }

      notice.setAttribute('aria-hidden', 'true');
      notice.classList.remove('is-visible');
      dismissButton.blur();
      const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 350;
      window.setTimeout(() => {
        notice.hidden = true;
        notice.removeAttribute('aria-hidden');
      }, delay);
    });
  })();
</script>

<!-- Pour le défilement des éléments -->
<!-- Réalisé par codepen.io ainsi que l'aide de l'ia -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15
    });

    document.documentElement.classList.add('has-scroll-reveal');
    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
  });
</script>
</body>
</html>