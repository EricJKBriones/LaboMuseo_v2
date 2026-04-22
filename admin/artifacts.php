<?php
// admin/artifacts.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$msg = '';
$action = $_GET['action'] ?? '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// DELETE
if (isset($_GET['delete'])) {
    $ex = dbOne("SELECT image_path FROM exhibits WHERE id=?", [(int)$_GET['delete']]);
    if ($ex && $ex['image_path'] && file_exists('../uploads/'.$ex['image_path'])) {
        unlink('../uploads/'.$ex['image_path']);
    }
    dbExec("DELETE FROM exhibits WHERE id=?", [(int)$_GET['delete']]);
    header('Location: artifacts.php?msg=deleted');
    exit;
}

// INSERT
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='insert') {
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $catId    = (int)($_POST['category_id'] ?? 0);
    $year     = trim($_POST['artifact_year'] ?? '');
    $origin   = trim($_POST['origin'] ?? '');
    $donor    = trim($_POST['donated_by'] ?? '');
    $remarks  = trim($_POST['remarks'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $date_received = trim($_POST['date_received'] ?? '');
    $postToNews = isset($_POST['post_to_news']) && $_POST['post_to_news'] === '1';
    $imgPath  = handleUpload('image_file');
    if (!$imgPath && !empty($_POST['image_path'])) $imgPath = trim($_POST['image_path']);

    // Debug output if insert fails
    if (!$title || !$desc) {
        echo '<pre style="background:#fffbe6;color:#c0392b;padding:24px;font-size:1.1em">';
        echo "DEBUG: Artifact Insert Failed\n";
        echo "POST Data:\n";
        print_r($_POST);
        echo "\nFILES Data:\n";
        print_r($_FILES);
        echo "\nImage Upload Result: ".$imgPath."\n";
        echo "</pre>";
        exit;
    }

    dbExec(
      "INSERT INTO exhibits (title,description,category_id,image_path,donated_by,artifact_year,origin,remarks,quantity,date_received) VALUES (?,?,?,?,?,?,?,?,?,?)",
      [
        $title,
        $desc,
        $catId ?: null,
        $imgPath,
        $donor,
        $year,
        $origin,
        $remarks,
        $quantity,
        $date_received !== '' ? $date_received : date('Y-m-d')
      ]
    );

    if ($postToNews) {
      $catName = '';
      if ($catId > 0) {
        $cat = dbOne("SELECT name FROM categories WHERE id=?", [$catId]);
        $catName = $cat['name'] ?? '';
      }

      $newsTitle = 'New Donated Artifact: ' . $title;
      $newsParts = ['A newly donated artifact has been added to the museum collection.'];
      if ($donor !== '') $newsParts[] = 'Donated by: ' . $donor . '.';
      if ($catName !== '') $newsParts[] = 'Type of Artifact: ' . $catName . '.';
      if ($year !== '') $newsParts[] = 'Year/Period: ' . $year . '.';
      if ($origin !== '') $newsParts[] = 'Origin: ' . $origin . '.';
      $newsParts[] = 'Description: ' . $desc;
      $newsContent = implode("\n\n", $newsParts);

      dbExec(
        "INSERT INTO news_events (title,content,type,event_date,date_posted,image_path,is_archived) VALUES (?,?, 'news', NULL, CURDATE(), ?, 0)",
        [$newsTitle, $newsContent, $imgPath]
      );

      header('Location: artifacts.php?msg=added_posted');
      exit;
    }

    header('Location: artifacts.php?msg=added');
    exit;
}

// UPDATE
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update') {
    $id       = (int)$_POST['id'];
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $catId    = (int)($_POST['category_id'] ?? 0);
    $year     = trim($_POST['artifact_year'] ?? '');
    $origin   = trim($_POST['origin'] ?? '');
    $donor    = trim($_POST['donated_by'] ?? '');
    $remarks  = trim($_POST['remarks'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $date_received = trim($_POST['date_received'] ?? '');
    $ex       = dbOne("SELECT image_path FROM exhibits WHERE id=?", [$id]);
    $imgPath  = handleUpload('image_file') ?: ($ex['image_path'] ?? null);
    if (!$imgPath && !empty($_POST['image_path'])) $imgPath = trim($_POST['image_path']);
    dbExec(
      "UPDATE exhibits SET title=?,description=?,category_id=?,image_path=?,donated_by=?,artifact_year=?,origin=?,remarks=?,quantity=?,date_received=? WHERE id=?",
      [
        $title,
        $desc,
        $catId ?: null,
        $imgPath,
        $donor,
        $year,
        $origin,
        $remarks,
        $quantity,
        $date_received !== '' ? $date_received : date('Y-m-d'),
        $id
      ]
    );
    header('Location: artifacts.php?msg=updated');
    exit;
}

$search = trim($_GET['q'] ?? '');
$deptId = (int)($_GET['dept'] ?? 0);
$sort   = $_GET['sort'] ?? 'newest';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

$sortMap = [
  'newest'    => 'e.id DESC',
  'oldest'    => 'e.id ASC',
  'title_asc' => 'e.title ASC',
  'title_desc'=> 'e.title DESC',
  'year_desc' => 'e.artifact_year DESC',
  'year_asc'  => 'e.artifact_year ASC',
  'dept_asc'  => 'c.name ASC, e.title ASC'
];
if (!isset($sortMap[$sort])) $sort = 'newest';

$params = [];
$whereSql = " WHERE 1=1";
if ($search !== '') {
  $whereSql .= " AND e.title LIKE ?";
  $like = "%$search%";
  $params[] = $like;
}
if ($deptId > 0) {
  $whereSql .= " AND e.category_id=?";
  $params[] = $deptId;
}

$countSql = "SELECT COUNT(*) FROM exhibits e" . $whereSql;
$resultCount = dbCount($countSql, $params);
$totalPages = max(1, (int)ceil($resultCount / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "SELECT e.*,c.name as cat_name
        FROM exhibits e
        LEFT JOIN categories c ON e.category_id=c.id" . $whereSql . "
        ORDER BY " . $sortMap[$sort] . "
        LIMIT $perPage OFFSET $offset";

$exhibits = dbQuery($sql, $params);
$isFiltered = ($search !== '' || $deptId > 0);
$categories = dbQuery("SELECT * FROM categories ORDER BY name");
$editRow = $editId ? dbOne("SELECT * FROM exhibits WHERE id=?", [$editId]) : null;

$pageTitle = 'Manage Artifacts — ' . SITE_NAME;
$bodyClass = 'admin-artifacts-page';
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>
  <main class="adm-main">

    <?php if (isset($_GET['msg'])): ?>
      <?php
        $msgCode = (string)($_GET['msg'] ?? '');
        $msgs = [
          'added' => 'Artifact added successfully.',
          'added_posted' => 'Artifact added and auto-posted to Museum News.',
          'updated' => 'Artifact updated successfully.',
          'deleted' => 'Artifact deleted successfully.',
          'export_none' => 'Select at least one artifact before exporting selected items.'
        ];
        $msgClass = $msgCode === 'export_none' ? 'alert-err' : 'alert-ok';
      ?>
      <div class="<?= $msgClass ?>">&#10003; <?= htmlspecialchars($msgs[$msgCode] ?? 'Action completed successfully.') ?></div>
    <?php endif; ?>
    <?php if ($msg): ?><div class="alert-err">&#9888; <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
      <h3 class="adm-sec-title" style="margin:0">&#128444; Manage Artifacts</h3>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="btn-exp" id="artifactExportToggleBtn" aria-expanded="false">&#128229; Export Artifacts</button>
        <button type="button" class="toggle-btn bg-green" onclick="if (typeof openAdminQuickPanel === 'function') { openAdminQuickPanel('quickAddArtifactForm'); } else { togglePanel('quickAddArtifactForm'); }">&#10133; Add New Artifact</button>
      </div>
    </div>

    <form method="GET" action="artifacts.php" class="mbar">
      <label for="artQ">Search</label>
      <input id="artQ" type="text" name="q" class="mi" placeholder="Search by artifact title..." value="<?= htmlspecialchars($search) ?>" autocomplete="off" oninput="adminDebounceSubmit(this.form, 700)">

      <label for="artDept">Type of Artifact</label>
      <select id="artDept" name="dept" class="mi" onchange="this.form.submit()">
        <option value="0">All</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $deptId===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="artSort">Sort</label>
      <select id="artSort" name="sort" class="mi" onchange="this.form.submit()">
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
        <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        <option value="title_asc" <?= $sort==='title_asc'?'selected':'' ?>>Title A-Z</option>
        <option value="title_desc" <?= $sort==='title_desc'?'selected':'' ?>>Title Z-A</option>
        <option value="year_desc" <?= $sort==='year_desc'?'selected':'' ?>>Year High-Low</option>
        <option value="year_asc" <?= $sort==='year_asc'?'selected':'' ?>>Year Low-High</option>
        <option value="dept_asc" <?= $sort==='dept_asc'?'selected':'' ?>>Type of Artifact A-Z</option>
      </select>

      <a href="artifacts.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
    </form>

    <div class="result-meta">
      Showing <strong><?= $resultCount ?></strong> artifact<?= $resultCount!==1?'s':'' ?><?= $isFiltered ? ' (filtered)' : '' ?>
      <?php if ($resultCount > 0): ?>
        <span style="margin-left:8px;color:#6b7280">
          Page <?= $page ?> of <?= $totalPages ?>,
          rows <?= $offset + 1 ?>-<?= min($offset + count($exhibits), $resultCount) ?>
        </span>
      <?php endif; ?>
    </div>

    <form method="POST" action="export_artifacts.php" class="mbar artifact-export-bar" id="artifactExportForm">
      <label for="artifactExportFormat">Export Format:</label>
      <select id="artifactExportFormat" name="format" class="mi">
        <option value="pdf">PDF (Print-ready)</option>
        <option value="xlsx">Excel (.xlsx)</option>
        <option value="csv">CSV (.csv)</option>
      </select>

      <label for="artifactExportScope">Scope:</label>
      <select id="artifactExportScope" name="scope" class="mi">
        <option value="selected">Selected Artifacts</option>
        <option value="all">All Filtered Artifacts</option>
      </select>

      <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
      <input type="hidden" name="dept" value="<?= (int)$deptId ?>">
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

      <button type="submit" class="btn-exp" id="artifactExportBtn">&#128229; Export Artifacts</button>
      <span id="artifactSelectedCount" class="artifact-selected-count">0 selected</span>
    </form>

    <!-- EDIT FORM MODAL -->
    <?php if ($editRow): ?>
    <div class="adm-quick-form-overlay adm-edit-modal is-open is-form-open" id="editArtOverlay" aria-hidden="false">
      <button type="button" class="adm-quick-backdrop" onclick="closeEditArtModal()" aria-label="Close edit artifact form"></button>
      <div class="adm-quick-form-shell">
        <div class="adm-form adm-quick-form-panel is-open" id="editArtForm">
          <div class="adm-quick-form-head">
            <h3>Edit Artifact</h3>
            <button type="button" class="adm-quick-close" onclick="closeEditArtModal()" aria-label="Close edit artifact form">&times;</button>
          </div>
          <form method="POST" enctype="multipart/form-data" action="artifacts.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
            <div class="fg2">
              <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" value="<?= htmlspecialchars($editRow['title']) ?>" required></div>
              <div>
                <label class="al">Type of Artifact</label>
                <select name="category_id" class="ai">
                  <option value="">-- Select --</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$editRow['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div><label class="al">Year / Period</label><input type="text" name="artifact_year" class="ai" value="<?= htmlspecialchars($editRow['artifact_year']) ?>"></div>
              <div><label class="al">Origin</label><input type="text" name="origin" class="ai" value="<?= htmlspecialchars($editRow['origin']) ?>"></div>
              <div class="full"><label class="al">Donated By</label><input type="text" name="donated_by" class="ai" value="<?= htmlspecialchars($editRow['donated_by']) ?>"></div>
              <div class="full">
                <label class="al">Choose Image</label>
                <input
                  type="file"
                  name="image_file"
                  class="ai"
                  accept="image/*"
                  <?php if (!empty($editRow['image_path'])): ?>
                    data-preview-src="../uploads/<?= htmlspecialchars($editRow['image_path']) ?>"
                    data-preview-name="<?= htmlspecialchars($editRow['image_path']) ?>"
                    data-preview-size="Current image"
                  <?php endif; ?>
                >
              </div>
              <div class="full"><label class="al">Description *</label><textarea name="description" class="ai" required><?= htmlspecialchars($editRow['description']) ?></textarea></div>
              <div><label class="al">Remarks</label><textarea name="remarks" class="ai"><?= htmlspecialchars($editRow['remarks'] ?? '') ?></textarea></div>
              <div><label class="al">Quantity</label><input type="number" name="quantity" class="ai" min="1" value="<?= htmlspecialchars($editRow['quantity'] ?? 1) ?>"></div>
              <div><label class="al">Date Received</label><input type="date" name="date_received" class="ai" value="<?= htmlspecialchars($editRow['date_received'] ?? date('Y-m-d')) ?>"></div>
            </div>
            <div class="adm-quick-form-actions">
              <button type="submit" class="btn-save">Update Artifact</button>
              <button type="button" class="btn-cancel-f" onclick="closeEditArtModal()"><img class="auto-btn-icon" src="../assets/Icon/reset.png" data-png="../assets/Icon/reset.png" data-gif="../assets/Icon/reset.gif" alt="" aria-hidden="true">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- TABLE -->
    <form method="POST" action="export_artifacts.php" id="artifactTableExportForm">
      <input type="hidden" name="format" id="artifactTableExportFormat" value="pdf">
      <input type="hidden" name="scope" id="artifactTableExportScope" value="selected">
      <input type="hidden" name="single_layout" id="artifactSingleLayout" value="0">
      <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
      <input type="hidden" name="dept" value="<?= (int)$deptId ?>">
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

    <div class="tbl-wrap tbl-wrap-mobile-fix">
      <table class="adm-tbl">
        <thead>
          <tr>
            <th class="art-select-col"><input type="checkbox" id="selectAllArtifacts" aria-label="Select all artifacts"></th>
            <th>Image</th>
            <th>Title</th>
            <th>Type of Artifact</th>
            <th>Year</th>
            <th>Origin</th>
            <th>Quantity</th>
            <th>Date Received</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($exhibits)): ?>
            <tr><td colspan="7" style="text-align:center;padding:20px;color:#888">No artifacts found.</td></tr>
          <?php else: foreach ($exhibits as $ex): ?>
            <tr>
              <td class="art-select-cell"><input type="checkbox" class="artifact-row-check" name="artifact_ids[]" value="<?= (int)$ex['id'] ?>" aria-label="Select artifact <?= (int)$ex['id'] ?>"></td>
              <td>
                <?php if ($ex['image_path'] && file_exists('../uploads/'.$ex['image_path'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($ex['image_path']) ?>" class="tbl-img">
                <?php else: ?>
                  <div class="tbl-img" style="background:#1b2a3b;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem">&#127994;</div>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($ex['title']) ?></strong></td>
              <td><?= htmlspecialchars($ex['cat_name'] ?? '—') ?></td>
              <td style="font-size:.82rem"><?= htmlspecialchars($ex['artifact_year'] ?? '—') ?></td>
              <td style="font-size:.82rem"><?= htmlspecialchars($ex['origin'] ?? '—') ?></td>
              <td style="text-align:center; font-size:.95rem;"><?= htmlspecialchars($ex['quantity'] ?? '—') ?></td>
              <td style="font-size:.82rem; white-space:nowrap;"><?= htmlspecialchars($ex['date_received'] ?? '—') ?></td>
              <td style="font-size:.82rem; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($ex['remarks'] ?? '') ?>"><?= htmlspecialchars($ex['remarks'] ?? '—') ?></td>
              <td class="adm-row-actions">
                <div class="adm-row-actions-wrap">
                <a href="artifacts.php?edit=<?= $ex['id'] ?>" class="btn-edit btn-icon" title="Edit artifact" aria-label="Edit artifact">&#9999;</a>
                <a href="artifacts.php?delete=<?= $ex['id'] ?>" class="btn-del" onclick="return handleDeleteWithToast(<?= $ex['id'] ?>, 'artifacts.php?delete=<?= $ex['id'] ?>', '<?= htmlspecialchars(addslashes($ex['title'])) ?>')">&#128465;</a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    </form>

    <?php if ($totalPages > 1): ?>
      <div class="result-meta" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?php
          $pageBase = 'artifacts.php?q=' . urlencode($search) . '&dept=' . (int)$deptId . '&sort=' . urlencode($sort) . '&page=';
          $prevPage = max(1, $page - 1);
          $nextPage = min($totalPages, $page + 1);
        ?>
        <a href="<?= $pageBase . $prevPage ?>" class="btn-clf" data-no-auto-icon="1" style="text-decoration:none;display:inline-flex;align-items:center;<?= $page <= 1 ? 'pointer-events:none;opacity:.5;' : '' ?>">Previous</a>
        <span>Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong></span>
        <a href="<?= $pageBase . $nextPage ?>" class="btn-clf" data-no-auto-icon="1" style="text-decoration:none;display:inline-flex;align-items:center;<?= $page >= $totalPages ? 'pointer-events:none;opacity:.5;' : '' ?>">Next</a>
      </div>
    <?php endif; ?>
  </main>
</div>

<script>
function closeEditArtModal() {
  window.location.href = 'artifacts.php';
}

document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('artQ');
  if (searchInput && searchInput.value) {
    searchInput.focus();
    searchInput.selectionStart = searchInput.selectionEnd = searchInput.value.length;
  }

  var selectAll = document.getElementById('selectAllArtifacts');
  var rowChecks = document.querySelectorAll('.artifact-row-check');
  var exportBarForm = document.getElementById('artifactExportForm');
  var exportToggleBtn = document.getElementById('artifactExportToggleBtn');
  var tableExportForm = document.getElementById('artifactTableExportForm');
  var exportFormat = document.getElementById('artifactExportFormat');
  var exportScope = document.getElementById('artifactExportScope');
  var countLabel = document.getElementById('artifactSelectedCount');
  var isExportMode = false;

  function selectedCount() {
    var count = 0;
    rowChecks.forEach(function(cb) {
      if (cb.checked) count += 1;
    });
    return count;
  }

  function syncCountLabel() {
    if (!countLabel) return;
    countLabel.textContent = selectedCount() + ' selected';
  }

  function syncSelectAllState() {
    if (!selectAll) return;
    if (!rowChecks.length) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
      return;
    }

    var count = selectedCount();
    selectAll.checked = count > 0 && count === rowChecks.length;
    selectAll.indeterminate = count > 0 && count < rowChecks.length;
  }

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      rowChecks.forEach(function(cb) {
        cb.checked = selectAll.checked;
      });
      syncSelectAllState();
      syncCountLabel();
    });
  }

  rowChecks.forEach(function(cb) {
    cb.addEventListener('change', function() {
      syncSelectAllState();
      syncCountLabel();
    });
  });

  function setExportMode(nextState) {
    isExportMode = !!nextState;
    if (document.body) {
      document.body.classList.toggle('artifact-export-mode', isExportMode);
    }

    if (exportBarForm) {
      exportBarForm.classList.toggle('is-open', isExportMode);
      exportBarForm.setAttribute('aria-hidden', isExportMode ? 'false' : 'true');
    }

    if (exportToggleBtn) {
      exportToggleBtn.setAttribute('aria-expanded', isExportMode ? 'true' : 'false');
      exportToggleBtn.innerHTML = isExportMode ? '&#10005; Close Export' : '&#128229; Export Artifacts';
    }

    if (!isExportMode) {
      if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
        selectAll.disabled = true;
      }
      rowChecks.forEach(function(cb) {
        cb.checked = false;
        cb.disabled = true;
      });
      syncCountLabel();
    } else {
      if (selectAll) selectAll.disabled = false;
      rowChecks.forEach(function(cb) {
        cb.disabled = false;
      });
    }
  }

  if (exportToggleBtn) {
    exportToggleBtn.addEventListener('click', function() {
      setExportMode(!isExportMode);
    });
  }

  if (exportBarForm && tableExportForm && exportFormat && exportScope) {
    exportBarForm.addEventListener('submit', function(e) {
      if (!isExportMode) {
        e.preventDefault();
        return;
      }

      var scope = exportScope.value;
      var count = selectedCount();

      if (scope === 'selected' && count === 0) {
        e.preventDefault();
        if (window.sileo && typeof window.sileo.warning === 'function') {
          window.sileo.warning({
            title: 'No Selection',
            message: 'Please select at least one artifact to export.'
          });
        } else {
          alert('Please select at least one artifact to export.');
        }
        return;
      }

      document.getElementById('artifactTableExportFormat').value = exportFormat.value;
      document.getElementById('artifactTableExportScope').value = scope;
      document.getElementById('artifactSingleLayout').value = (scope === 'selected' && count === 1) ? '1' : '0';

      e.preventDefault();
      tableExportForm.submit();
    });
  }

  syncSelectAllState();
  syncCountLabel();
  setExportMode(false);
});
</script>

<?php require_once 'admin_footer.php'; ?>
