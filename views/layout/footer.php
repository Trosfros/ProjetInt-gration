<footer>
  <div class="footer-inner">
    <p>© 2026 NovaBank. Simulateur éducatif d'investissement boursier et bancaire. Ne constitue pas un conseil financier.</p>
  </div>
</footer>

<!-- Pour le défilement des éléments -->
<!-- Réalisé par codepen.io ainsi que l'aide de l'ia -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
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

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
  });
</script>
</body>
</html>