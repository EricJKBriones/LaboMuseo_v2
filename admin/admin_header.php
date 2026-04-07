<?php
// admin/admin_header.php — used by all admin pages
require_once '../includes/db.php';
sessionStart();
requireAdmin();

// Detect base URL — works on localhost/subfolder AND domain root
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$dir  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$base = $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin — ' . SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
<link rel="stylesheet" href="<?= $base ?>assets/css/admin.css">
</head>
<body>
<script>
  (function () {
    try {
      if (window.innerWidth > 900 && localStorage.getItem('adminSidebarCollapsed') === '1') {
        document.body.classList.add('admin-sidebar-collapsed');
      }
    } catch (e) {
      // Ignore storage access issues.
    }
  })();
</script>

<header class="site-header">
  <div class="header-inner">
    <a href="<?= $base ?>index.php?page=home" class="logo-area">
      <div class="logo-seal">
        <?php if (file_exists('../uploads/logo.png')): ?>
          <img src="<?= $base ?>uploads/logo.png" alt="Logo">
        <?php else: ?>
          <div class="logo-ph">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
        <?php endif; ?>
      </div>
      <div class="logo-text">
        <span class="logo-name"><?= SITE_NAME ?></span>
        <span class="logo-baybayin">ᜋᜓᜐᜒᜂ ᜇᜒ ᜎᜊᜓ</span>
        <span class="logo-sub">Admin Panel</span>
      </div>
    </a>

    <button class="hamburger" id="hamburgerBtn" onclick="toggleMenu()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>

    <nav>
      <ul class="nav-links" id="navLinks">
        <li><a href="<?= $base ?>index.php?page=home" class="nav-lnk" style="color:#9bb8cc">&#127968; View Site</a></li>
        <li><a href="<?= $base ?>admin/showcase.php" class="nav-lnk" style="color:#d9c18a">&#127909; Showcase</a></li>
        <li><a href="<?= $base ?>index.php?action=logout" class="nav-lnk nav-logout">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>
