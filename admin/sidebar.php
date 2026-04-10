<?php
// admin/sidebar.php
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<button type="button" class="adm-mobile-fab" onclick="toggleAdminSidebar()" aria-label="Toggle admin menu">
  &#9776;
</button>
<aside class="adm-sidebar">
  <div class="adm-side-head">
    <h3>Admin Menu</h3>
    <button type="button" class="adm-side-toggle" onclick="toggleAdminSidebar()" title="Collapse menu" aria-label="Toggle admin menu">
      &#9776;
    </button>
  </div>
  <ul class="adm-menu">
    <li><a href="index.php"       class="<?= $adminPage==='index'       ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/dashboard.png" data-png="../assets/Icon/dashboard.png" data-gif="../assets/Icon/dashboard.gif" alt="" aria-hidden="true"></span><span class="adm-label">Overview</span></a></li>
    <li><a href="charts.php"      class="<?= $adminPage==='charts'      ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/chart.png" data-png="../assets/Icon/chart.png" data-gif="../assets/Icon/chart.gif" alt="" aria-hidden="true"></span><span class="adm-label">Charts</span></a></li>
    <li><a href="visitors.php"    class="<?= $adminPage==='visitors'    ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/visitor.png" data-png="../assets/Icon/visitor.png" data-gif="../assets/Icon/visitor.gif" alt="" aria-hidden="true"></span><span class="adm-label">Visitor Log</span></a></li>
    <li><a href="artifacts.php"   class="<?= $adminPage==='artifacts'   ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/artifacts.png" data-png="../assets/Icon/artifacts.png" data-gif="../assets/Icon/artifacts.gif" alt="" aria-hidden="true"></span><span class="adm-label">Artifacts</span></a></li>
    <li><a href="departments.php" class="<?= $adminPage==='departments' ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/categories.png" data-png="../assets/Icon/categories.png" data-gif="../assets/Icon/categories.gif" alt="" aria-hidden="true"></span><span class="adm-label">Departments</span></a></li>
    <li><a href="news.php"        class="<?= $adminPage==='news'        ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/news.png" data-png="../assets/Icon/news.png" data-gif="../assets/Icon/news.gif" alt="" aria-hidden="true"></span><span class="adm-label">News &amp; Events</span></a></li>
    <li><a href="showcase.php"    class="<?= $adminPage==='showcase'    ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/showcase.png" data-png="../assets/Icon/showcase.png" data-gif="../assets/Icon/showcase.gif" alt="" aria-hidden="true"></span><span class="adm-label">Showcase</span></a></li>
    <li><a href="account.php"     class="<?= $adminPage==='account'     ?'active':'' ?>"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/profile.png" data-png="../assets/Icon/profile.png" data-gif="../assets/Icon/profile.gif" alt="" aria-hidden="true"></span><span class="adm-label">Account Settings</span></a></li>
    <li class="adm-menu-sep" aria-hidden="true"></li>
    <li><a href="../index.php?page=home&no_toast=1"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/viewsite.png" data-png="../assets/Icon/viewsite.png" data-gif="../assets/Icon/viewsite.gif" alt="" aria-hidden="true"></span><span class="adm-label">View Site</span></a></li>
    <li><a href="../index.php?action=logout" class="logout-link" aria-label="Log Out" title="Log Out"><span class="adm-ico"><img class="icon-swap" src="../assets/Icon/log-out.png" data-png="../assets/Icon/log-out.png" data-gif="../assets/Icon/log-out.gif" alt="" aria-hidden="true"></span><span class="adm-label">Log Out</span></a></li>
  </ul>
</aside>
