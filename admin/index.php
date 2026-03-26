<?php
// admin/index.php
require_once '../includes/db.php';
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

    <h3 class="adm-sec-title">&#9889; Quick Actions</h3>
    <div class="adm-qgrid">
      <button type="button" class="adm-qcard" onclick="togglePanel('quickAddArtifactForm')"><span>&#10133;</span>Add Artifact</button>
      <button type="button" class="adm-qcard" onclick="togglePanel('quickAddDeptForm')"><span>&#128194;</span>Add Department</button>
      <button type="button" class="adm-qcard" onclick="togglePanel('quickAddNewsForm')"><span>&#128226;</span>Post News</button>
      <a href="export.php?format=xlsx" class="adm-qcard"><span>&#128229;</span>Export Visitors</a>
    </div>

    <div class="adm-quick-panels">
      <div class="adm-form" id="quickAddArtifactForm">
        <h3>New Artifact</h3>
        <form method="POST" enctype="multipart/form-data" action="artifacts.php">
          <input type="hidden" name="action" value="insert">
          <div class="fg2">
            <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" required></div>
            <div>
              <label class="al">Department</label>
              <select name="category_id" class="ai">
                <option value="">-- Select --</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div><label class="al">Year / Period</label><input type="text" name="artifact_year" class="ai" placeholder="e.g., 18th Century"></div>
            <div><label class="al">Origin</label><input type="text" name="origin" class="ai" placeholder="e.g., Labo"></div>
            <div class="full"><label class="al">Donated By</label><input type="text" name="donated_by" class="ai"></div>
            <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
            <div class="full"><label class="al">Description *</label><textarea name="description" class="ai" required></textarea></div>
          </div>
          <button type="submit" class="btn-save">&#128190; Save Artifact</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddArtifactForm')">Cancel</button>
        </form>
      </div>

      <div class="adm-form" id="quickAddDeptForm">
        <h3>New Department</h3>
        <form method="POST" enctype="multipart/form-data" action="departments.php">
          <input type="hidden" name="action" value="insert">
          <div class="fg2">
            <div><label class="al">Name *</label><input type="text" name="name" class="ai" required></div>
            <div><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
            <div class="full"><label class="al">Description</label><textarea name="description" class="ai"></textarea></div>
          </div>
          <button type="submit" class="btn-save">&#128190; Save Department</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddDeptForm')">Cancel</button>
        </form>
      </div>

      <div class="adm-form" id="quickAddNewsForm">
        <h3>New Post</h3>
        <form method="POST" enctype="multipart/form-data" action="news.php?view=active">
          <input type="hidden" name="action" value="insert">
          <div class="fg2">
            <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" required></div>
            <div>
              <label class="al">Type</label>
              <select name="type" class="ai" onchange="toggleEvDate('quickNewsEventDate',this.value)">
                <option value="news">Museum News</option>
                <option value="event">Upcoming Event</option>
              </select>
            </div>
            <div><label class="al">Event Date <small style="color:#aaa">(for events only)</small></label><input type="date" name="event_date" id="quickNewsEventDate" class="ai"></div>
            <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
            <div class="full"><label class="al">Content *</label><textarea name="content" class="ai" rows="5" required></textarea></div>
          </div>
          <button type="submit" class="btn-save">&#128226; Publish</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddNewsForm')">Cancel</button>
        </form>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
      <div>
        <h3 class="adm-sec-title">&#128101; Recent Visitors</h3>
        <div class="tbl-wrap">
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
        <a href="visitors.php" class="btn-navy" style="font-size:.85rem;padding:9px 18px">View All Visitors &rarr;</a>
      </div>
      <div>
        <h3 class="adm-sec-title">&#128240; Recent News</h3>
        <div class="tbl-wrap">
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
        <a href="news.php" class="btn-navy" style="font-size:.85rem;padding:9px 18px">Manage News &rarr;</a>
      </div>
    </div>
  </main>
</div>

<?php require_once 'admin_footer.php'; ?>
