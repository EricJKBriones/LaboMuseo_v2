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
    <li><a href="index.php"       class="<?= $adminPage==='index'       ?'active':'' ?>"><span class="adm-ico">&#128202;</span><span class="adm-label">Overview</span></a></li>
    <li><a href="charts.php"      class="<?= $adminPage==='charts'      ?'active':'' ?>"><span class="adm-ico">&#128200;</span><span class="adm-label">Charts</span></a></li>
    <li><a href="visitors.php"    class="<?= $adminPage==='visitors'    ?'active':'' ?>"><span class="adm-ico">&#128101;</span><span class="adm-label">Visitor Log</span></a></li>
    <li><a href="artifacts.php"   class="<?= $adminPage==='artifacts'   ?'active':'' ?>"><span class="adm-ico">&#128444;</span><span class="adm-label">Artifacts</span></a></li>
    <li><a href="departments.php" class="<?= $adminPage==='departments' ?'active':'' ?>"><span class="adm-ico">&#128193;</span><span class="adm-label">Departments</span></a></li>
    <li><a href="news.php"        class="<?= $adminPage==='news'        ?'active':'' ?>"><span class="adm-ico">&#128240;</span><span class="adm-label">News &amp; Events</span></a></li>
    <li><a href="account.php"     class="<?= $adminPage==='account'     ?'active':'' ?>"><span class="adm-ico">&#128274;</span><span class="adm-label">Account Settings</span></a></li>
  </ul>
</aside>
