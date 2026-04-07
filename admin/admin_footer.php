<?php
// admin/admin_footer.php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$base = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\') . '/';
?>
<footer style="background:var(--navy3);color:#8a9db0;text-align:center;padding:20px;border-top:2px solid var(--gold);font-size:.82rem;margin-top:auto">
  <strong style="color:var(--gold2)"><?= SITE_NAME ?></strong> &mdash; Admin Panel &bull; &copy; <?= date('Y') ?>
</footer>
<div id="sileoToastHost" class="sileo-toast-host" aria-live="polite" aria-atomic="true"></div>
<script>
window.__admSubmitTimers = window.__admSubmitTimers || new WeakMap();
function adminDebounceSubmit(form, delay) {
  if (!form) return;
  var timers = window.__admSubmitTimers;
  var prev = timers.get(form);
  if (prev) clearTimeout(prev);
  var wait = typeof delay === 'number' ? delay : 700;
  var t = setTimeout(function() {
    form.submit();
  }, wait);
  timers.set(form, t);
}
</script>
<script src="<?= $base ?>assets/js/main.js"></script>
</body>
</html>
