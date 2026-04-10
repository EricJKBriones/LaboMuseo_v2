<?php
// admin/admin_header.php — used by all admin pages
require_once '../includes/db.php';
sessionStart();
requireAdmin();

// Path-based base URL to avoid protocol/host mismatch behind reverse proxies.
$dir  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$base = ($dir === '' ? '/' : $dir . '/');
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
<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">
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


