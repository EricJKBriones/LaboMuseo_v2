<?php
// admin/sidebar.php
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="adm-sidebar">
  <h3>Admin Menu</h3>
  <ul class="adm-menu">
    <li><a href="index.php"       class="<?= $adminPage==='index'       ?'active':'' ?>">&#128202; Overview</a></li>
    <li><a href="visitors.php"    class="<?= $adminPage==='visitors'    ?'active':'' ?>">&#128101; Visitor Log</a></li>
    <li><a href="artifacts.php"   class="<?= $adminPage==='artifacts'   ?'active':'' ?>">&#128444; Artifacts</a></li>
    <li><a href="departments.php" class="<?= $adminPage==='departments' ?'active':'' ?>">&#128193; Departments</a></li>
    <li><a href="news.php"        class="<?= $adminPage==='news'        ?'active':'' ?>">&#128240; News &amp; Events</a></li>
  </ul>
</aside>
