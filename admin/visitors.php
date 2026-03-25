<?php
// admin/visitors.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

// Delete
if (isset($_GET['delete'])) {
    dbExec("DELETE FROM guests WHERE id=?", [(int)$_GET['delete']]);
    header('Location: visitors.php?msg=deleted');
    exit;
}

$month  = $_GET['month'] ?? '';
$params = [];
$sql    = "SELECT * FROM guests WHERE 1=1";
if ($month) { $sql .= " AND DATE_FORMAT(visit_date,'%Y-%m')=?"; $params[] = $month; }
$sql .= " ORDER BY id DESC";
$guests = dbQuery($sql, $params);

$pageTitle = 'Visitor Log — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>
  <main class="adm-main">

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert-ok">&#10003; Visitor record deleted.</div>
    <?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px">
      <h3 class="adm-sec-title" style="margin:0">&#128101; Visitor Log</h3>
      <a href="export.php" class="toggle-btn bg-green" style="text-decoration:none">&#128229; Export CSV</a>
    </div>

    <form method="GET" action="visitors.php" class="mbar" data-auto-submit="1" data-debounce="350">
      <label for="monthFilter">Filter by Month:</label>
      <input type="month" id="monthFilter" name="month" class="mi" value="<?= htmlspecialchars($month) ?>">
      <a href="visitors.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
      <span style="color:#888;font-size:.82rem;margin-left:8px"><?= count($guests) ?> record(s)</span>
    </form>

    <div class="tbl-wrap">
      <table class="adm-tbl">
        <thead>
          <tr>
            <th>#</th><th>Date</th><th>Name</th><th>Type / Org</th>
            <th>Pax</th><th>Gender</th><th>From</th><th>Purpose</th><th>Contact</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($guests)): ?>
            <tr><td colspan="10" style="text-align:center;padding:28px;color:#888">No visitors found.</td></tr>
          <?php else: foreach ($guests as $g): $ig = $g['visitor_type']==='Group'; ?>
            <tr>
              <td><?= $g['id'] ?></td>
              <td style="font-size:.82rem"><?= date('M j, Y', strtotime($g['visit_date'])) ?></td>
              <td><strong><?= htmlspecialchars($g['guest_name']) ?></strong></td>
              <td>
                <span class="<?= $ig?'vg':'vi2' ?>"><?= $g['visitor_type'] ?></span>
                <?php if ($ig && $g['organization']): ?>
                  <br><small><?= htmlspecialchars($g['organization']) ?></small>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <strong style="color:#c9922a"><?= $g['headcount'] ?></strong>
                <?php if ($ig): ?><br><small style="color:#888"><?= $g['male_count'] ?>M/<?= $g['female_count'] ?>F</small><?php endif; ?>
              </td>
              <td><?= htmlspecialchars($g['gender']) ?></td>
              <td><?= htmlspecialchars($g['residence']) ?></td>
              <td style="font-size:.82rem"><?= htmlspecialchars($g['purpose']) ?></td>
              <td style="font-size:.82rem"><?= htmlspecialchars($g['contact_no']) ?></td>
              <td>
                <a href="visitors.php?delete=<?= $g['id'] ?>" class="btn-del"
                   onclick="return confirm('Delete this visitor record?')">&#128465;</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php require_once 'admin_footer.php'; ?>
