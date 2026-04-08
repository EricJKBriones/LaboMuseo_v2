<?php
// includes/header.php
require_once __DIR__ . '/db.php';
sessionStart();
$currentPage = $_GET['page'] ?? 'home';
$bodyClass = $currentPage === 'home' ? 'page-home' : 'page-inner';
$bodyClass .= ' page-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $currentPage);

// Bulletproof base URL — works on localhost/subfolder AND domain root
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$script   = $_SERVER['SCRIPT_NAME']; // e.g. /LaboMuseo/index.php or /index.php
$dir      = rtrim(dirname($script), '/\\');
$base     = $protocol . '://' . $host . $dir . '/';

// Logo
$logoFile = __DIR__ . '/../uploads/logo.png';
$logoUrl  = $base . 'uploads/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="<?= $bodyClass ?>">

<header class="site-header">
  <div class="header-inner">
    <a href="<?= $base ?>index.php?page=home" class="logo-area">
      <div class="logo-seal">
        <?php if (file_exists($logoFile)): ?>
          <img src="<?= $logoUrl ?>" alt="Logo">
        <?php else: ?>
          <div class="logo-ph">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
        <?php endif; ?>
      </div>
      <div class="logo-text">
        <span class="logo-name"><?= SITE_NAME ?></span>
        <span class="logo-baybayin">ᜋᜓᜐᜒᜂ ᜇᜒ ᜎᜊᜓ</span>
        <span class="logo-sub"><?= SITE_SUBTITLE ?></span>
      </div>
    </a>

    <button class="hamburger" id="hamburgerBtn" onclick="toggleMenu()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>

    <nav>
      <ul class="nav-links" id="navLinks">
        <li><a href="<?= $base ?>index.php?page=home"       class="nav-lnk <?= $currentPage==='home'       ? 'active-page':'' ?>">Home</a></li>
        <li><a href="<?= $base ?>index.php?page=about"      class="nav-lnk <?= $currentPage==='about'      ? 'active-page':'' ?>">About</a></li>
        <li><a href="<?= $base ?>index.php?page=news"       class="nav-lnk <?= $currentPage==='news'       ? 'active-page':'' ?>">News &amp; Events</a></li>
        <?php if (isAdmin() || isGuest()): ?>
          <li><a href="<?= $base ?>index.php?page=categories" class="nav-lnk <?= $currentPage==='categories' ? 'active-page':'' ?>">Departments</a></li>
          <li><a href="<?= $base ?>index.php?page=exhibits"   class="nav-lnk <?= $currentPage==='exhibits'   ? 'active-page':'' ?>">All Artifacts</a></li>
        <?php else: ?>
          <li><a href="#" class="nav-lnk" aria-label="Sign the Digital Guestbook to access departments" onclick="return promptGuestbookAccess('Departments')">Departments</a></li>
          <li><a href="#" class="nav-lnk" aria-label="Sign the Digital Guestbook to access all artifacts" onclick="return promptGuestbookAccess('All Artifacts')">All Artifacts</a></li>
        <?php endif; ?>
        <li><span class="nav-sep">|</span></li>
        <?php if (isAdmin()): ?>
          <li><a href="<?= $base ?>admin/index.php" class="nav-lnk nav-dashboard">&#9881; Dashboard</a></li>
          <li><a href="<?= $base ?>index.php?action=logout" class="nav-lnk nav-logout">Logout</a></li>
        <?php elseif (isGuest()): ?>
          <li><span class="guest-badge">&#128075; <?= htmlspecialchars(guestName()) ?></span></li>
          <li><a href="<?= $base ?>index.php?action=logout" class="nav-lnk nav-logout">Leave</a></li>
        <?php else: ?>
          <li><a href="<?= $base ?>index.php?page=login" class="nav-lnk nav-login <?= $currentPage==='login' ? 'active-page':'' ?>">Login / Access</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
