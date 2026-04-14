<?php
// admin/visitors.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update_visitor')) {
  $id = (int)($_POST['id'] ?? 0);
  $guestName = trim((string)($_POST['guest_name'] ?? ''));
  $visitorType = ($_POST['visitor_type'] ?? 'Individual') === 'Group' ? 'Group' : 'Individual';
  $organization = trim((string)($_POST['organization'] ?? ''));
  $genderRaw = (string)($_POST['gender'] ?? 'Other');
  $gender = in_array($genderRaw, ['Male', 'Female', 'Other'], true) ? $genderRaw : 'Other';
  $residence = trim((string)($_POST['residence'] ?? ''));
  $nationality = trim((string)($_POST['nationality'] ?? 'Filipino'));
  $purpose = trim((string)($_POST['purpose'] ?? ''));
  $contactNo = trim((string)($_POST['contact_no'] ?? ''));
  $visitDate = trim((string)($_POST['visit_date'] ?? ''));

  $maleCount = max(0, (int)($_POST['male_count'] ?? 0));
  $femaleCount = max(0, (int)($_POST['female_count'] ?? 0));
  $headcount = 1;

  if ($visitorType === 'Group') {
    $headcount = max(1, $maleCount + $femaleCount);
  } else {
    $organization = '';
    $maleCount = 0;
    $femaleCount = 0;
    $headcount = 1;
  }

  if ($id > 0 && $guestName !== '' && $visitDate !== '') {
    dbExec(
      "UPDATE guests SET guest_name=?, visitor_type=?, organization=?, gender=?, residence=?, nationality=?, headcount=?, male_count=?, female_count=?, purpose=?, contact_no=?, visit_date=? WHERE id=?",
      [$guestName, $visitorType, $organization, $gender, $residence, $nationality, $headcount, $maleCount, $femaleCount, $purpose, $contactNo, $visitDate, $id]
    );

    header('Location: visitors.php?msg=updated');
    exit;
  }

  header('Location: visitors.php?msg=error');
  exit;
}

// Delete
if (isset($_GET['delete'])) {
    dbExec("DELETE FROM guests WHERE id=?", [(int)$_GET['delete']]);
    header('Location: visitors.php?msg=deleted');
    exit;
}

$month  = $_GET['month'] ?? '';
$exportFormat = $_GET['export_format'] ?? 'xlsx';
$exportMonth = $_GET['export_month'] ?? 'all';
if (!in_array($exportFormat, ['xlsx', 'csv'], true)) $exportFormat = 'xlsx';

$monthOptionsRaw = dbQuery("SELECT DISTINCT DATE_FORMAT(visit_date, '%Y-%m') AS ym FROM guests WHERE visit_date IS NOT NULL ORDER BY ym DESC");
$monthOptions = [];
foreach ($monthOptionsRaw as $m) {
  $ym = $m['ym'] ?? '';
  if ($ym !== '') {
    $monthOptions[] = $ym;
  }
}

if ($exportMonth !== 'all' && !in_array($exportMonth, $monthOptions, true)) {
  $exportMonth = 'all';
}

$params = [];
$sql    = "SELECT * FROM guests WHERE 1=1";
if ($month) { $sql .= " AND DATE_FORMAT(visit_date,'%Y-%m')=?"; $params[] = $month; }
$sql .= " ORDER BY id DESC";
$guests = dbQuery($sql, $params);

$editGuest = $editId > 0 ? dbOne("SELECT * FROM guests WHERE id=?", [$editId]) : null;

$pageTitle = 'Visitor Log — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>
  <main class="adm-main">

    <?php if (isset($_GET['msg'])): ?>
      <?php
        $msgCode = (string)($_GET['msg'] ?? '');
        $msgMap = [
          'deleted' => 'Visitor record deleted.',
          'updated' => 'Visitor record updated successfully.',
          'error' => 'Unable to update visitor record. Please check required fields.'
        ];
        $msgClass = $msgCode === 'error' ? 'alert-err' : 'alert-ok';
      ?>
      <div class="<?= $msgClass ?>">&#10003; <?= htmlspecialchars($msgMap[$msgCode] ?? 'Action completed.') ?></div>
    <?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px">
      <h3 class="adm-sec-title" style="margin:0">&#128101; Visitor Log</h3>
      <a href="export.php?format=<?= urlencode($exportFormat) ?><?= $exportMonth !== 'all' ? '&month=' . urlencode($exportMonth) : '' ?>" class="toggle-btn bg-green" style="text-decoration:none">
        &#128229; Export <?= $exportFormat === 'csv' ? 'CSV' : 'Excel' ?>
      </a>
    </div>

    <form method="GET" action="visitors.php" class="mbar" data-auto-submit="1" data-debounce="350">
      <label for="monthFilter">Filter by Month:</label>
      <input type="month" id="monthFilter" name="month" class="mi" value="<?= htmlspecialchars($month) ?>">

      <label for="exportFormat">Export Format:</label>
      <select id="exportFormat" name="export_format" class="mi">
        <option value="xlsx" <?= $exportFormat==='xlsx'?'selected':'' ?>>Excel (.xlsx)</option>
        <option value="csv" <?= $exportFormat==='csv'?'selected':'' ?>>CSV (.csv)</option>
      </select>

      <label for="exportMonth">Export Month:</label>
      <select id="exportMonth" name="export_month" class="mi">
        <option value="all" <?= $exportMonth==='all'?'selected':'' ?>>All Months</option>
        <?php foreach ($monthOptions as $opt): ?>
          <?php $dt = DateTime::createFromFormat('Y-m', $opt); ?>
          <option value="<?= htmlspecialchars($opt) ?>" <?= $exportMonth===$opt?'selected':'' ?>>
            <?= htmlspecialchars($dt ? $dt->format('F Y') : $opt) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <a href="visitors.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
      <span style="color:#888;font-size:.82rem;margin-left:8px"><?= count($guests) ?> record(s)</span>
    </form>

    <?php if ($editGuest): ?>
      <div class="adm-form is-open" id="editVisitorForm">
        <h3>Edit Visitor Record</h3>
        <form method="POST" action="visitors.php">
          <input type="hidden" name="action" value="update_visitor">
          <input type="hidden" name="id" value="<?= (int)$editGuest['id'] ?>">

          <div class="fg2">
            <div>
              <label class="al">Visit Date *</label>
              <input type="date" name="visit_date" class="ai" value="<?= htmlspecialchars((string)$editGuest['visit_date']) ?>" required>
            </div>
            <div>
              <label class="al">Visitor Type</label>
              <select name="visitor_type" class="ai" id="editVisitorType">
                <option value="Individual" <?= ($editGuest['visitor_type'] ?? '') === 'Individual' ? 'selected' : '' ?>>Individual</option>
                <option value="Group" <?= ($editGuest['visitor_type'] ?? '') === 'Group' ? 'selected' : '' ?>>Group</option>
              </select>
            </div>

            <div class="full">
              <label class="al">Guest Name *</label>
              <input type="text" name="guest_name" class="ai" value="<?= htmlspecialchars((string)$editGuest['guest_name']) ?>" required>
            </div>

            <div class="full" id="editOrgWrap">
              <label class="al">Organization / School</label>
              <input type="text" name="organization" class="ai" value="<?= htmlspecialchars((string)($editGuest['organization'] ?? '')) ?>">
            </div>

            <div>
              <label class="al">No. of Males</label>
              <input type="number" name="male_count" class="ai" min="0" value="<?= (int)($editGuest['male_count'] ?? 0) ?>">
            </div>
            <div>
              <label class="al">No. of Females</label>
              <input type="number" name="female_count" class="ai" min="0" value="<?= (int)($editGuest['female_count'] ?? 0) ?>">
            </div>

            <div>
              <label class="al">Gender</label>
              <select name="gender" class="ai">
                <option value="Male" <?= ($editGuest['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($editGuest['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= ($editGuest['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
            <div>
              <label class="al">Contact Number</label>
              <input type="text" name="contact_no" class="ai" value="<?= htmlspecialchars((string)($editGuest['contact_no'] ?? '')) ?>">
            </div>

            <div>
              <label class="al">Residence</label>
              <input type="text" name="residence" class="ai" value="<?= htmlspecialchars((string)($editGuest['residence'] ?? '')) ?>">
            </div>
            <div>
              <label class="al">Nationality</label>
              <input type="text" name="nationality" class="ai" value="<?= htmlspecialchars((string)($editGuest['nationality'] ?? 'Filipino')) ?>">
            </div>

            <div class="full">
              <label class="al">Purpose of Visit</label>
              <input type="text" name="purpose" class="ai" value="<?= htmlspecialchars((string)($editGuest['purpose'] ?? '')) ?>">
            </div>
          </div>

          <button type="submit" class="btn-save">Save Changes</button>
          <a href="visitors.php" class="btn-cancel-f" style="text-decoration:none;display:inline-block;margin-left:7px">Cancel</a>
        </form>
      </div>
    <?php endif; ?>

    <div class="tbl-wrap tbl-wrap-mobile-fix">
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
                <a href="visitors.php?edit=<?= (int)$g['id'] ?>" class="btn-edit" title="Edit visitor" aria-label="Edit visitor">&#9999; Edit</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  var typeSelect = document.getElementById('editVisitorType');
  var orgWrap = document.getElementById('editOrgWrap');

  function syncEditVisitorType() {
    if (!typeSelect || !orgWrap) return;
    var isGroup = typeSelect.value === 'Group';
    orgWrap.style.display = isGroup ? '' : 'none';
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', syncEditVisitorType);
    syncEditVisitorType();
  }
});
</script>

<?php require_once 'admin_footer.php'; ?>
