<?php
// Lê flash da sessão antes de limpar
$_toastFlash = null;
if (!empty($_SESSION['flash'])) {
    $_toastFlash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<footer style="border-top:1px solid rgba(240,236,228,0.07);padding:24px 32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;background:#0e0e0e;">
  <p style="font-size:12px;font-weight:500;color:rgba(240,236,228,0.65);letter-spacing:0.04em;margin:0">
    &copy; <?= date('Y') ?> — Todos os direitos reservados.
  </p>
  <p style="font-size:12px;font-weight:300;color:rgba(240,236,228,0.25);letter-spacing:0.06em;margin:0">
    Um trabalho NOTA 10
  </p>
</footer>

<!-- ── Toast container ── -->
<div id="toast-container" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:360px;width:calc(100% - 48px)"></div>

<script>
(function() {
  var _styles = {
    success: 'rgba(31,198,156,0.18)|rgba(31,198,156,0.42)|#d5f7ef',
    error:   'rgba(226,75,74,0.16)|rgba(226,75,74,0.42)|#fecaca',
    info:    'rgba(96,165,250,0.16)|rgba(96,165,250,0.42)|#bfdbfe',
    warning: 'rgba(251,191,36,0.16)|rgba(251,191,36,0.42)|#fef08a',
  };

  window.showToast = function(msg, type) {
    type = type || 'info';
    var parts = (_styles[type] || _styles.info).split('|');
    var el = document.createElement('div');
    el.style.cssText = [
      'background:' + parts[0],
      'border:1px solid ' + parts[1],
      'color:' + parts[2],
      'border-radius:12px',
      'padding:13px 18px',
      'font-size:13px',
      'font-family:"DM Sans",sans-serif',
      'font-weight:500',
      'line-height:1.5',
      'pointer-events:all',
      'cursor:pointer',
      'backdrop-filter:blur(14px)',
      'box-shadow:0 4px 24px rgba(0,0,0,0.45)',
      'transform:translateX(110%)',
      'transition:transform 0.3s cubic-bezier(.17,.67,.28,1.2),opacity 0.25s',
      'opacity:0',
      'word-break:break-word',
    ].join(';');
    el.innerHTML = msg;
    var c = document.getElementById('toast-container');
    c.appendChild(el);
    requestAnimationFrame(function() { requestAnimationFrame(function() {
      el.style.transform = 'translateX(0)';
      el.style.opacity = '1';
    }); });
    var timer = setTimeout(function() { _dismiss(el); }, 4500);
    el.addEventListener('click', function() { clearTimeout(timer); _dismiss(el); });
  };

  function _dismiss(el) {
    el.style.transform = 'translateX(110%)';
    el.style.opacity = '0';
    setTimeout(function() { el && el.parentNode && el.parentNode.removeChild(el); }, 350);
  }

  <?php if ($_toastFlash): ?>
  document.addEventListener('DOMContentLoaded', function() {
    showToast(<?= json_encode($_toastFlash['message']) ?>, <?= json_encode($_toastFlash['type'] ?? 'info') ?>);
  });
  <?php endif ?>
})();
</script>
</body>
</html>
