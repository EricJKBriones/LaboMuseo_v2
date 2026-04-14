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
  $postToNews = isset($_POST['post_to_news']) && $_POST['post_to_news'] === '1';
    $imgPath  = handleUpload('image_file');
    if (!$imgPath && !empty($_POST['image_path'])) $imgPath = trim($_POST['image_path']);
    if ($title && $desc) {
        dbExec("INSERT INTO exhibits (title,description,category_id,image_path,donated_by,artifact_year,origin) VALUES (?,?,?,?,?,?,?)",
            [$title,$desc,$catId?:null,$imgPath,$donor,$year,$origin]);

    if ($postToNews) {
      $catName = '';
      if ($catId > 0) {
        $cat = dbOne("SELECT name FROM categories WHERE id=?", [$catId]);
        $catName = $cat['name'] ?? '';
      }

      $newsTitle = 'New Donated Artifact: ' . $title;
      $newsParts = ['A newly donated artifact has been added to the museum collection.'];
      if ($donor !== '') $newsParts[] = 'Donated by: ' . $donor . '.';
      if ($catName !== '') $newsParts[] = 'Department: ' . $catName . '.';
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
    $msg = 'Title and description are required.';
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
    $ex       = dbOne("SELECT image_path FROM exhibits WHERE id=?", [$id]);
    $imgPath  = handleUpload('image_file') ?: ($ex['image_path'] ?? null);
    if (!$imgPath && !empty($_POST['image_path'])) $imgPath = trim($_POST['image_path']);
    dbExec("UPDATE exhibits SET title=?,description=?,category_id=?,image_path=?,donated_by=?,artifact_year=?,origin=? WHERE id=?",
        [$title,$desc,$catId?:null,$imgPath,$donor,$year,$origin,$id]);
    header('Location: artifacts.php?msg=updated');
    exit;
}

$search = trim($_GET['q'] ?? '');
$deptId = (int)($_GET['dept'] ?? 0);
$sort   = $_GET['sort'] ?? 'newest';

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
$sql = "SELECT e.*,c.name as cat_name FROM exhibits e LEFT JOIN categories c ON e.category_id=c.id WHERE 1=1";
if ($search !== '') {
  $sql .= " AND e.title LIKE ?";
  $like = "%$search%";
  $params[] = $like;
}
if ($deptId > 0) {
  $sql .= " AND e.category_id=?";
  $params[] = $deptId;
}
$sql .= " ORDER BY " . $sortMap[$sort];

$exhibits = dbQuery($sql, $params);
$resultCount = count($exhibits);
$isFiltered = ($search !== '' || $deptId > 0);
$categories = dbQuery("SELECT * FROM categories ORDER BY name");
$editRow = $editId ? dbOne("SELECT * FROM exhibits WHERE id=?", [$editId]) : null;

$pageTitle = 'Manage Artifacts — ' . SITE_NAME;
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
        <button class="toggle-btn bg-green" onclick="togglePanel('addArtForm')">&#10133; Add New Artifact</button>
      </div>
    </div>

    <form method="GET" action="artifacts.php" class="mbar">
      <label for="artQ">Search</label>
      <input id="artQ" type="text" name="q" class="mi" placeholder="Search by artifact title..." value="<?= htmlspecialchars($search) ?>" autocomplete="off" oninput="adminDebounceSubmit(this.form, 700)">

      <label for="artDept">Department</label>
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
        <option value="dept_asc" <?= $sort==='dept_asc'?'selected':'' ?>>Department A-Z</option>
      </select>

      <a href="artifacts.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
    </form>

    <div class="result-meta">
      Showing <strong><?= $resultCount ?></strong> artifact<?= $resultCount!==1?'s':'' ?><?= $isFiltered ? ' (filtered)' : '' ?>
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

    <!-- ADD FORM -->
    <div class="adm-form" id="addArtForm">
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
          <div class="full" style="display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border:1px dashed #d7dee7;border-radius:6px;background:#f8fbff">
            <input type="checkbox" id="postToNews" name="post_to_news" value="1" style="margin-top:3px">
            <label for="postToNews" style="margin:0;color:#2b3f52;font-size:.85rem;line-height:1.4">
              Mark as newly donated artifact and auto-post to Museum News
            </label>
          </div>
          <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">Description *</label><textarea name="description" class="ai" required></textarea></div>
        </div>
        <button type="submit" class="btn-save">Save Artifact</button>
        <button type="button" class="btn-cancel-f" onclick="togglePanel('addArtForm')">Cancel</button>
      </form>
    </div>

    <!-- EDIT FORM -->
    <?php if ($editRow): ?>
    <div class="adm-form is-open" id="editArtForm">
      <h3>Edit Artifact</h3>
      <form method="POST" enctype="multipart/form-data" action="artifacts.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
        <div class="fg2">
          <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" value="<?= htmlspecialchars($editRow['title']) ?>" required></div>
          <div>
            <label class="al">Department</label>
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
          <?php if ($editRow['image_path']): ?>
            <div class="full"><label class="al">Current Image</label><br><img src="../uploads/<?= htmlspecialchars($editRow['image_path']) ?>" style="height:80px;border-radius:6px;margin-top:4px"></div>
          <?php endif; ?>
          <div class="full"><label class="al">Upload New Image (optional)</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">OR Image Filename</label><input type="text" name="image_path" class="ai" value="<?= htmlspecialchars($editRow['image_path']) ?>"></div>
          <div class="full"><label class="al">Description *</label><textarea name="description" class="ai" required><?= htmlspecialchars($editRow['description']) ?></textarea></div>
        </div>
        <button type="submit" class="btn-save">Update Artifact</button>
        <a href="artifacts.php" class="btn-cancel-f" style="text-decoration:none;display:inline-block;margin-left:7px">Cancel</a>
      </form>
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
        <thead><tr><th class="art-select-col"><input type="checkbox" id="selectAllArtifacts" aria-label="Select all artifacts"></th><th>Image</th><th>Title</th><th>Department</th><th>Year</th><th>Origin</th><th>Actions</th></tr></thead>
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
              <td>
                <a href="artifacts.php?edit=<?= $ex['id'] ?>" class="btn-edit btn-icon" title="Edit artifact" aria-label="Edit artifact">&#9999;</a>
                <a href="artifacts.php?delete=<?= $ex['id'] ?>" class="btn-del" onclick="return confirm('Delete this artifact?')">&#128465;</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    </form>
  </main>
</div>

<script>
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
