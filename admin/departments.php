<?php
// admin/departments.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$msg = '';
$action = $_GET['action'] ?? '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// DELETE
if (isset($_GET['delete'])) {
    $cnt = dbCount("SELECT COUNT(*) FROM exhibits WHERE category_id=?", [(int)$_GET['delete']]);
    if ($cnt > 0 && !isset($_GET['force'])) {
        header('Location: departments.php?warn='.(int)$_GET['delete'].'&cnt='.$cnt);
        exit;
    }
    $cat = dbOne("SELECT image_path FROM categories WHERE id=?", [(int)$_GET['delete']]);
    if ($cat && $cat['image_path'] && file_exists('../uploads/'.$cat['image_path'])) {
        unlink('../uploads/'.$cat['image_path']);
    }
    dbExec("DELETE FROM categories WHERE id=?", [(int)$_GET['delete']]);
    header('Location: departments.php?msg=deleted');
    exit;
}

// INSERT
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='insert') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $img  = handleUpload('image_file');
    if (!$img && !empty($_POST['image_path'])) $img = trim($_POST['image_path']);
    if ($name) {
        dbExec("INSERT INTO categories (name,description,image_path) VALUES (?,?,?)", [$name,$desc,$img]);
        header('Location: departments.php?msg=added');
        exit;
    }
    $msg = 'Type of Artifact name is required.';
}

// UPDATE
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $cat  = dbOne("SELECT image_path FROM categories WHERE id=?", [$id]);
    $img  = handleUpload('image_file') ?: ($cat['image_path'] ?? null);
    if (!$img && !empty($_POST['image_path'])) $img = trim($_POST['image_path']);
    dbExec("UPDATE categories SET name=?,description=?,image_path=? WHERE id=?", [$name,$desc,$img,$id]);
    header('Location: departments.php?msg=updated');
    exit;
}

$search    = trim($_GET['q'] ?? '');
$hasExhibs = $_GET['has_artifacts'] ?? 'all';
$sort      = $_GET['sort'] ?? 'name_asc';

$sortMap = [
  'name_asc'   => 'c.name ASC',
  'name_desc'  => 'c.name DESC',
  'newest'     => 'c.id DESC',
  'oldest'     => 'c.id ASC',
  'count_desc' => 'artifact_count DESC, c.name ASC',
  'count_asc'  => 'artifact_count ASC, c.name ASC'
];
if (!isset($sortMap[$sort])) $sort = 'name_asc';
if (!in_array($hasExhibs, ['all','yes','no'], true)) $hasExhibs = 'all';

$params = [];
$sql = "SELECT c.*, COUNT(e.id) as artifact_count FROM categories c LEFT JOIN exhibits e ON e.category_id=c.id WHERE 1=1";
if ($search !== '') {
  $sql .= " AND c.name LIKE ?";
  $like = "%$search%";
  $params[] = $like;
}
$sql .= " GROUP BY c.id";
if ($hasExhibs === 'yes') {
  $sql .= " HAVING COUNT(e.id) > 0";
} elseif ($hasExhibs === 'no') {
  $sql .= " HAVING COUNT(e.id) = 0";
}
$sql .= " ORDER BY " . $sortMap[$sort];

$cats    = dbQuery($sql, $params);
$resultCount = count($cats);
$isFiltered = ($search !== '' || $hasExhibs !== 'all');
$editRow = $editId ? dbOne("SELECT * FROM categories WHERE id=?", [$editId]) : null;

$pageTitle = 'Manage Types of Artifacts — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>
  <main class="adm-main">

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert-ok">&#10003; Type of Artifact <?= htmlspecialchars($_GET['msg']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['warn'])): ?>
      <div class="alert-err" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <span>&#9888; This type of artifact has <?= (int)$_GET['cnt'] ?> artifact(s). Deleting it will unlink those artifacts. Continue?</span>
        <div>
          <a href="departments.php?delete=<?= (int)$_GET['warn'] ?>&force=1" class="btn-del">Yes, Delete</a>
          <a href="departments.php" class="btn-cancel-f" style="margin-left:6px;text-decoration:none;display:inline-block">Cancel</a>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($msg): ?><div class="alert-err">&#9888; <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
      <h3 class="adm-sec-title" style="margin:0">&#128193; Manage Types of Artifacts</h3>
      <button class="toggle-btn bg-blue2" onclick="togglePanel('quickAddDeptForm')">&#10133; Add Type of Artifact</button>
    </div>

    <form method="GET" action="departments.php" class="mbar">
      <label for="depQ">Search</label>
      <input id="depQ" type="text" name="q" class="mi" placeholder="Search by type of artifact name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off" oninput="adminDebounceSubmit(this.form, 700)">

      <label for="depHas">Filter</label>
      <select id="depHas" name="has_artifacts" class="mi" onchange="this.form.submit()">
        <option value="all" <?= $hasExhibs==='all'?'selected':'' ?>>All Types of Artifacts</option>
        <option value="yes" <?= $hasExhibs==='yes'?'selected':'' ?>>With Artifacts</option>
        <option value="no" <?= $hasExhibs==='no'?'selected':'' ?>>Without Artifacts</option>
      </select>

      <label for="depSort">Sort</label>
      <select id="depSort" name="sort" class="mi" onchange="this.form.submit()">
        <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name A-Z</option>
        <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Name Z-A</option>
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
        <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        <option value="count_desc" <?= $sort==='count_desc'?'selected':'' ?>>Most Artifacts</option>
        <option value="count_asc" <?= $sort==='count_asc'?'selected':'' ?>>Least Artifacts</option>
      </select>

      <a href="departments.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
    </form>

    <div class="result-meta">
      Showing <strong><?= $resultCount ?></strong> type<?= $resultCount!==1?'s':'' ?> of artifacts<?= $isFiltered ? ' (filtered)' : '' ?>
    </div>

    <!-- EDIT FORM MODAL -->
    <?php if ($editRow): ?>
    <div class="adm-quick-form-overlay adm-edit-modal is-open is-form-open" id="editDeptOverlay" aria-hidden="false">
      <button type="button" class="adm-quick-backdrop" onclick="closeEditDeptModal()" aria-label="Close edit type of artifact form"></button>
      <div class="adm-quick-form-shell">
        <div class="adm-form adm-quick-form-panel is-open" id="editDeptForm">
          <div class="adm-quick-form-head">
            <h3>Edit Type of Artifact</h3>
            <button type="button" class="adm-quick-close" onclick="closeEditDeptModal()" aria-label="Close edit type of artifact form">&times;</button>
          </div>
          <form method="POST" enctype="multipart/form-data" action="departments.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
            <div class="fg2">
              <div><label class="al">Name *</label><input type="text" name="name" class="ai" value="<?= htmlspecialchars($editRow['name']) ?>" required></div>
              <div><label class="al">Upload New Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
              <?php if ($editRow['image_path']): ?>
                <div><label class="al">Current Image</label><br><img src="../uploads/<?= htmlspecialchars($editRow['image_path']) ?>" style="height:70px;border-radius:6px;margin-top:4px"></div>
              <?php endif; ?>
              <div class="full"><label class="al">OR Image Filename</label><input type="text" name="image_path" class="ai" value="<?= htmlspecialchars($editRow['image_path']) ?>"></div>
              <div class="full"><label class="al">Description</label><textarea name="description" class="ai"><?= htmlspecialchars($editRow['description'] ?? '') ?></textarea></div>
            </div>
            <div class="adm-quick-form-actions">
              <button type="submit" class="btn-save">Update</button>
              <button type="button" class="btn-cancel-f" onclick="closeEditDeptModal()"><img class="auto-btn-icon" src="../assets/Icon/reset.png" data-png="../assets/Icon/reset.png" data-gif="../assets/Icon/reset.gif" alt="" aria-hidden="true">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="tbl-wrap tbl-wrap-mobile-fix">
      <table class="adm-tbl">
        <thead><tr><th>Image</th><th>Name</th><th>Description</th><th>Artifacts</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($cats)): ?>
            <tr><td colspan="5" style="text-align:center;padding:20px;color:#888">No types of artifacts found.</td></tr>
          <?php else: foreach ($cats as $c): ?>
            <tr>
              <td>
                <?php if ($c['image_path'] && file_exists('../uploads/'.$c['image_path'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($c['image_path']) ?>" class="tbl-img">
                <?php else: ?>
                  <div class="tbl-img" style="background:#1b2a3b;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem">&#127963;</div>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
              <td style="font-size:.82rem;color:#888"><?= htmlspecialchars(mb_substr($c['description']??'',0,60)) ?><?= strlen($c['description']??'')>60?'...':'' ?></td>
              <td><span class="vi2"><?= $c['artifact_count'] ?> artifact<?= $c['artifact_count']!=1?'s':'' ?></span></td>
              <td class="adm-row-actions">
                <div class="adm-row-actions-wrap">
                <a href="departments.php?edit=<?= $c['id'] ?>" class="btn-edit">&#9999; Edit</a>
                <a href="departments.php?delete=<?= $c['id'] ?>" class="btn-del" onclick="return handleDeleteWithToast(<?= $c['id'] ?>, 'departments.php?delete=<?= $c['id'] ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>')">&#128465;</a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<script>
function closeEditDeptModal() {
  window.location.href = 'departments.php';
}

document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('depQ');
  if (searchInput && searchInput.value) {
    searchInput.focus();
    searchInput.selectionStart = searchInput.selectionEnd = searchInput.value.length;
  }
});
</script>

<?php require_once 'admin_footer.php'; ?>
