<?php
// includes/footer.php
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$base = ($dir === '' ? '/' : $dir . '/');
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo-name"><?= SITE_NAME ?></div>
      <div class="footer-baybayin">ᜋᜓᜐᜒᜂ ᜇᜒ ᜎᜊᜓ</div>
      <div class="footer-divider"></div>
    </div>
    <div class="footer-cols">
      <div class="footer-col">
        <h4>Visit Us</h4>
        <p>Labo People's Park<br>Labo, Camarines Norte</p>
      </div>
      <div class="footer-col">
        <h4>Hours</h4>
        <p>Monday – Friday<br>8:00 AM – 5:00 PM</p>
        <p style="margin-top:6px">Weekends<br>Closed</p>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <p>josecarlosblagatuz@gmail.com<br>labotourism08@yahoo.com</p>
        <p>(054) 885-1074<br>+63 0928 661 2138</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="<?= $base ?>index.php?page=home">Home</a></li>
          <li><a href="<?= $base ?>index.php?page=about">About</a></li>
          <li><a href="<?= $base ?>index.php?page=news">News &amp; Events</a></li>
          <?php if (!isAdmin() && !isGuest()): ?>
            <li><a href="<?= $base ?>index.php?page=login">Sign Guestbook</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Museo de Labo. All rights reserved. &bull; Free Admission</p>
    </div>
  </div>
</footer>
<div id="sileoToastHost" class="sileo-toast-host" aria-live="polite" aria-atomic="true"></div>
<div id="pageLoadingOverlay" class="page-loading-overlay" aria-hidden="true">
  <div class="page-loading-card">
    <img src="<?= $base ?>assets/Icon/loading.gif" alt="Loading" class="page-loading-icon">
    <div class="page-loading-text">Loading...</div>
  </div>
</div>
<script src="<?= $base ?>assets/js/main.js"></script>
</body>
</html>
