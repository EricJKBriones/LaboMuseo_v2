<?php
// admin/index.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$today         = date('Y-m-d');
$monthStart    = date('Y-m-01');
$monthEnd      = date('Y-m-t');
$totalVisitors  = dbCount("SELECT COALESCE(SUM(headcount), 0) FROM guests");
$monthVisitors  = dbCount("SELECT COALESCE(SUM(headcount), 0) FROM guests WHERE visit_date BETWEEN ? AND ?", [$monthStart, $monthEnd]);
$todayVisitors  = dbCount("SELECT COALESCE(SUM(headcount), 0) FROM guests WHERE visit_date = ?", [$today]);
$totalArtifacts = dbCount("SELECT COUNT(*) FROM exhibits");
$totalDepts     = dbCount("SELECT COUNT(*) FROM categories");
$totalNews      = dbCount("SELECT COUNT(*) FROM news_events");
$categories     = dbQuery("SELECT id, name FROM categories ORDER BY name ASC");
$recentGuests   = dbQuery("SELECT * FROM guests ORDER BY id DESC LIMIT 5");
$recentNews     = dbQuery("SELECT * FROM news_events ORDER BY id DESC LIMIT 5");

$pageTitle = 'Dashboard — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>

  <main class="adm-main">
    <div class="adm-welcome">
      <h2>&#128202; Admin Dashboard</h2>
      <p>Welcome back! Here is an overview of Museo de Labo.</p>
    </div>

    <div class="adm-stats">
      <div class="astat blue astat-visitor">
        <div class="astat-main">
          <div class="astat-n"><?= $totalVisitors ?></div>
          <div class="astat-l">Total Visitors</div>
          <div class="astat-metrics">
            <span class="astat-chip">This Month <strong><?= $monthVisitors ?></strong></span>
            <span class="astat-chip">Today <strong><?= $todayVisitors ?></strong></span>
          </div>
        </div>
        <div class="astat-i">&#128101;</div>
      </div>
      <div class="astat green"><div><div class="astat-n"><?= $totalArtifacts ?></div><div class="astat-l">Artifacts</div></div><div class="astat-i">&#127994;</div></div>
      <div class="astat purple"><div><div class="astat-n"><?= $totalDepts ?></div><div class="astat-l">Departments</div></div><div class="astat-i">&#128193;</div></div>
      <div class="astat orange"><div><div class="astat-n"><?= $totalNews ?></div><div class="astat-l">News &amp; Events</div></div><div class="astat-i">&#128240;</div></div>
    </div>

    <div class="adm-dashboard-grid">
      <div>
        <h3 class="adm-sec-title">&#128101; Recent Visitors</h3>
        <div class="tbl-wrap adm-dashboard-table-wrap">
          <table class="adm-tbl">
            <thead><tr><th>Name</th><th>Type</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (empty($recentGuests)): ?>
                <tr><td colspan="3" style="text-align:center;color:#888;padding:18px">No visitors yet.</td></tr>
              <?php else: foreach ($recentGuests as $g): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($g['guest_name']) ?></strong></td>
                  <td><span class="<?= $g['visitor_type']==='Group'?'vg':'vi2' ?>"><?= $g['visitor_type'] ?></span></td>
                  <td style="font-size:.8rem;color:#888"><?= date('M j, Y', strtotime($g['visit_date'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <a href="visitors.php" class="btn-navy" data-icon-name="right-arrow" data-icon-position="end" style="font-size:.85rem;padding:9px 18px">View All Visitors</a>
      </div>
      <div>
        <h3 class="adm-sec-title">&#128240; Recent News</h3>
        <div class="tbl-wrap adm-dashboard-table-wrap">
          <table class="adm-tbl">
            <thead><tr><th>Title</th><th>Type</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (empty($recentNews)): ?>
                <tr><td colspan="3" style="text-align:center;color:#888;padding:18px">No posts yet.</td></tr>
              <?php else: foreach ($recentNews as $n): ?>
                <tr>
                  <td><strong><?= htmlspecialchars(mb_substr($n['title'],0,40)) ?></strong></td>
                  <td><span class="tpill <?= $n['type'] ?>"><?= ucfirst($n['type']) ?></span></td>
                  <td style="font-size:.8rem;color:#888"><?= date('M j, Y', strtotime($n['date_posted'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <a href="news.php" class="btn-navy" data-icon-name="right-arrow" data-icon-position="end" style="font-size:.85rem;padding:9px 18px">Manage News</a>
      </div>
    </div>
  </main>
</div>

<?php require_once 'admin_footer.php'; ?>
