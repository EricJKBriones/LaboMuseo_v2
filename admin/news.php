<?php
// admin/news.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

$msg    = '';
$action = $_GET['action'] ?? '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$view   = $_GET['view'] ?? 'active'; // 'active' or 'archive'

// ARCHIVE / UNARCHIVE
if (isset($_GET['archive'])) {
    dbExec("UPDATE news_events SET is_archived=1 WHERE id=?", [(int)$_GET['archive']]);
    header('Location: news.php?view=active&msg=archived');
    exit;
}
if (isset($_GET['unarchive'])) {
    dbExec("UPDATE news_events SET is_archived=0 WHERE id=?", [(int)$_GET['unarchive']]);
    header('Location: news.php?view=archive&msg=unarchived');
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $row = dbOne("SELECT image_path FROM news_events WHERE id=?", [(int)$_GET['delete']]);
    if ($row && $row['image_path'] && file_exists('../uploads/'.$row['image_path'])) {
        unlink('../uploads/'.$row['image_path']);
    }
    dbExec("DELETE FROM news_events WHERE id=?", [(int)$_GET['delete']]);
    header('Location: news.php?view='.$view.'&msg=deleted');
    exit;
}

// INSERT
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='insert') {
    $title  = trim($_POST['title'] ?? '');
    $cont   = trim($_POST['content'] ?? '');
    $type   = $_POST['type'] ?? 'news';
    $evDate = $_POST['event_date'] ?: null;
    $img    = handleUpload('image_file');
    if (!$img && !empty($_POST['image_path'])) $img = trim($_POST['image_path']);
    if ($title && $cont) {
        dbExec("INSERT INTO news_events (title,content,type,event_date,date_posted,image_path,is_archived) VALUES (?,?,?,?,CURDATE(),?,0)",
            [$title,$cont,$type,$evDate,$img]);
        header('Location: news.php?view=active&msg=added');
        exit;
    }
    $msg = 'Title and content are required.';
}

// UPDATE
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
    $id     = (int)$_POST['id'];
    $title  = trim($_POST['title'] ?? '');
    $cont   = trim($_POST['content'] ?? '');
    $type   = $_POST['type'] ?? 'news';
    $evDate = $_POST['event_date'] ?: null;
    $old    = dbOne("SELECT image_path FROM news_events WHERE id=?", [$id]);
    $img    = handleUpload('image_file') ?: ($old['image_path'] ?? null);
    if (!$img && !empty($_POST['image_path'])) $img = trim($_POST['image_path']);
    dbExec("UPDATE news_events SET title=?,content=?,type=?,event_date=?,image_path=? WHERE id=?",
        [$title,$cont,$type,$evDate,$img,$id]);
    header('Location: news.php?view='.$view.'&msg=updated');
    exit;
}

// Load data based on view
$search = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

if (!in_array($typeFilter, ['all','news','event'], true)) $typeFilter = 'all';
$sortMap = [
  'newest'       => 'id DESC',
  'oldest'       => 'id ASC',
  'title_asc'    => 'title ASC',
  'title_desc'   => 'title DESC',
  'posted_desc'  => 'date_posted DESC',
  'posted_asc'   => 'date_posted ASC',
  'event_desc'   => 'event_date DESC',
  'event_asc'    => 'event_date ASC'
];
if (!isset($sortMap[$sort])) $sort = 'newest';

$params = [$view === 'archive' ? 1 : 0];
$sql    = "SELECT * FROM news_events WHERE is_archived=?";
if ($typeFilter !== 'all') {
  $sql .= " AND type=?";
  $params[] = $typeFilter;
}
if ($search) {
  $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY " . $sortMap[$sort];
$allNews = dbQuery($sql, $params);
$resultCount = count($allNews);
$isFiltered = ($search !== '' || $typeFilter !== 'all');

$totalActive  = dbCount("SELECT COUNT(*) FROM news_events WHERE is_archived=0");
$totalArchive = dbCount("SELECT COUNT(*) FROM news_events WHERE is_archived=1");
$editRow = $editId ? dbOne("SELECT * FROM news_events WHERE id=?", [$editId]) : null;

$pageTitle = 'Manage News — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>
  <main class="adm-main">

    <?php if (isset($_GET['msg'])): ?>
      <?php $msgs = ['added'=>'Post published.','updated'=>'Post updated.','deleted'=>'Post deleted.','archived'=>'Moved to Archive.','unarchived'=>'Restored from Archive.']; ?>
      <div class="alert-ok">&#10003; <?= htmlspecialchars($msgs[$_GET['msg']] ?? $_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?><div class="alert-err">&#9888; <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <!-- Header row -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px">
      <h3 class="adm-sec-title" style="margin:0">&#128240; Manage News &amp; Events</h3>
      <?php if ($view === 'active'): ?>
        <button class="toggle-btn bg-orange" data-icon-name="post_news" onclick="togglePanel('addNewsForm')">&#10133; Post News</button>
      <?php endif; ?>
    </div>

    <!-- View Toggle Tabs -->
    <div style="display:flex;gap:0;margin-bottom:22px;background:#e8eaed;border-radius:8px;padding:4px;width:fit-content">
      <a href="news.php?view=active" style="padding:8px 20px;border-radius:6px;text-decoration:none;font-size:.85rem;font-weight:600;transition:all .2s;<?= $view==='active' ? 'background:#fff;color:var(--navy);box-shadow:0 2px 8px rgba(0,0,0,.1)' : 'color:#666' ?>">
        &#128240; Active <span style="background:#27ae60;color:#fff;border-radius:99px;padding:1px 7px;font-size:.72rem;margin-left:4px"><?= $totalActive ?></span>
      </a>
      <a href="news.php?view=archive" style="padding:8px 20px;border-radius:6px;text-decoration:none;font-size:.85rem;font-weight:600;transition:all .2s;<?= $view==='archive' ? 'background:#fff;color:var(--navy);box-shadow:0 2px 8px rgba(0,0,0,.1)' : 'color:#666' ?>">
        &#128193; Archive <span style="background:#95a5a6;color:#fff;border-radius:99px;padding:1px 7px;font-size:.72rem;margin-left:4px"><?= $totalArchive ?></span>
      </a>
    </div>

    <!-- ADD FORM (active view only) -->
    <?php if ($view === 'active'): ?>
    <div class="adm-form" id="addNewsForm">
      <h3>New Post</h3>
      <form method="POST" enctype="multipart/form-data" action="news.php">
        <input type="hidden" name="action" value="insert">
        <div class="fg2">
          <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" required></div>
          <div>
            <label class="al">Type</label>
            <select name="type" class="ai" id="addType" onchange="toggleEvDate('addEvDate',this.value)">
              <option value="news">Museum News</option>
              <option value="event">Upcoming Event</option>
            </select>
          </div>
          <div><label class="al">Event Date <small style="color:#aaa">(for events only)</small></label><input type="date" name="event_date" id="addEvDate" class="ai"></div>
          <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">Content *</label><textarea name="content" class="ai" rows="5" required></textarea></div>
        </div>
        <button type="submit" class="btn-save" data-icon-name="post_news">Publish</button>
        <button type="button" class="btn-cancel-f" onclick="togglePanel('addNewsForm')">Cancel</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- EDIT FORM -->
    <?php if ($editRow): ?>
    <div class="adm-form is-open" id="editNewsForm">
      <h3>Edit Post</h3>
      <form method="POST" enctype="multipart/form-data" action="news.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
        <div class="fg2">
          <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" value="<?= htmlspecialchars($editRow['title']) ?>" required></div>
          <div>
            <label class="al">Type</label>
            <select name="type" class="ai">
              <option value="news"  <?= $editRow['type']==='news' ?'selected':'' ?>>Museum News</option>
              <option value="event" <?= $editRow['type']==='event'?'selected':'' ?>>Upcoming Event</option>
            </select>
          </div>
          <div><label class="al">Event Date</label><input type="date" name="event_date" class="ai" value="<?= htmlspecialchars($editRow['event_date'] ?? '') ?>"></div>
          <?php if ($editRow['image_path'] && file_exists('../uploads/'.$editRow['image_path'])): ?>
            <div class="full"><label class="al">Current Image</label><br><img src="../uploads/<?= htmlspecialchars($editRow['image_path']) ?>" style="height:70px;border-radius:6px;margin-top:4px"></div>
          <?php endif; ?>
          <div class="full"><label class="al">Upload New Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">OR Image Filename</label><input type="text" name="image_path" class="ai" value="<?= htmlspecialchars($editRow['image_path'] ?? '') ?>"></div>
          <div class="full"><label class="al">Content *</label><textarea name="content" class="ai" rows="5" required><?= htmlspecialchars($editRow['content']) ?></textarea></div>
        </div>
        <button type="submit" class="btn-save">Update</button>
        <a href="news.php?view=<?= $view ?>" class="btn-cancel-f" style="text-decoration:none;display:inline-block;margin-left:7px">Cancel</a>
      </form>
    </div>
    <?php endif; ?>

    <!-- Search / Filter / Sort -->
    <form method="GET" action="news.php" class="mbar">
      <input type="hidden" name="view" value="<?= $view ?>">
      <label for="newsQ">Search</label>
      <input id="newsQ" type="text" name="q" class="mi" placeholder="Search by post title..." value="<?= htmlspecialchars($search) ?>" autocomplete="off" oninput="adminDebounceSubmit(this.form, 700)">

      <label for="newsType">Type</label>
      <select id="newsType" name="type" class="mi" onchange="this.form.submit()">
        <option value="all" <?= $typeFilter==='all'?'selected':'' ?>>All Types</option>
        <option value="news" <?= $typeFilter==='news'?'selected':'' ?>>News</option>
        <option value="event" <?= $typeFilter==='event'?'selected':'' ?>>Events</option>
      </select>

      <label for="newsSort">Sort</label>
      <select id="newsSort" name="sort" class="mi" onchange="this.form.submit()">
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
        <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        <option value="title_asc" <?= $sort==='title_asc'?'selected':'' ?>>Title A-Z</option>
        <option value="title_desc" <?= $sort==='title_desc'?'selected':'' ?>>Title Z-A</option>
        <option value="posted_desc" <?= $sort==='posted_desc'?'selected':'' ?>>Posted Date New-Old</option>
        <option value="posted_asc" <?= $sort==='posted_asc'?'selected':'' ?>>Posted Date Old-New</option>
        <option value="event_desc" <?= $sort==='event_desc'?'selected':'' ?>>Event Date New-Old</option>
        <option value="event_asc" <?= $sort==='event_asc'?'selected':'' ?>>Event Date Old-New</option>
      </select>

      <a href="news.php?view=<?= $view ?>" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Clear</a>
    </form>

    <div class="result-meta">
      Showing <strong><?= $resultCount ?></strong> <?= $view === 'archive' ? 'archived' : 'active' ?> post<?= $resultCount!==1?'s':'' ?><?= $isFiltered ? ' (filtered)' : '' ?>
    </div>

    <!-- Archive banner -->
    <?php if ($view === 'archive'): ?>
    <div style="background:#fef9e7;border:1px solid #f39c12;border-radius:8px;padding:12px 16px;margin-bottom:18px;font-size:.88rem;color:#7d6608">
      &#128193; <strong>Archive Storage</strong> — These posts are hidden from the public website. You can restore them to Active or permanently delete them.
    </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="tbl-wrap">
      <table class="adm-tbl">
        <thead>
          <tr>
            <th>Img</th><th>Type</th><th>Title</th><th>Posted</th><th>Event Date</th>
            <?php if ($view === 'active'): ?>
              <th>Actions</th>
            <?php else: ?>
              <th>Archived Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allNews)): ?>
            <tr><td colspan="6" style="text-align:center;padding:28px;color:#888">
              <?= $search ? 'No results for "'.htmlspecialchars($search).'"' : ($view==='archive' ? 'Archive is empty.' : 'No posts yet.') ?>
            </td></tr>
          <?php else: foreach ($allNews as $n): ?>
            <tr>
              <td>
                <?php if ($n['image_path'] && file_exists('../uploads/'.$n['image_path'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($n['image_path']) ?>" class="tbl-img">
                <?php else: ?>
                  <div class="tbl-img" style="background:#1b2a3b;display:flex;align-items:center;justify-content:center;font-size:1rem;color:rgba(255,255,255,.3)"><?= $n['type']==='event'?'&#128197;':'&#128240;' ?></div>
                <?php endif; ?>
              </td>
              <td><span class="tpill <?= $n['type'] ?>"><?= ucfirst($n['type']) ?></span></td>
              <td>
                <strong><?= htmlspecialchars(mb_substr($n['title'],0,55)) ?><?= mb_strlen($n['title'])>55?'...':'' ?></strong>
              </td>
              <td style="font-size:.8rem;color:#888"><?= date('M j, Y', strtotime($n['date_posted'])) ?></td>
              <td style="font-size:.8rem;color:#888"><?= $n['event_date'] ? date('M j, Y', strtotime($n['event_date'])) : '—' ?></td>
              <td style="white-space:nowrap">
                <?php if ($view === 'active'): ?>
                  <a href="news.php?edit=<?= $n['id'] ?>&view=active" class="btn-edit">&#9999; Edit</a>
                  <a href="news.php?archive=<?= $n['id'] ?>" class="btn-del btn-archive" data-icon-name="folder" style="background:#e67e22"
                     onclick="return confirm('Move this post to Archive?')" title="Move to Archive">&#128193; Archive</a>
                  <a href="news.php?delete=<?= $n['id'] ?>&view=active" class="btn-del"
                     onclick="return confirm('Permanently delete this post?')" title="Delete permanently">&#128465;</a>
                <?php else: ?>
                  <a href="news.php?unarchive=<?= $n['id'] ?>" class="btn-edit" style="background:#27ae60"
                     onclick="return confirm('Restore this post to Active?')" title="Restore">&#9654; Restore</a>
                  <a href="news.php?delete=<?= $n['id'] ?>&view=archive" class="btn-del"
                     onclick="return confirm('Permanently delete this post?')" title="Delete permanently">&#128465; Delete</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
function toggleEvDate(id, type) {
  var el = document.getElementById(id);
  if (el) el.style.opacity = type==='event' ? '1' : '0.4';
}
document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('newsQ');
  if (searchInput && searchInput.value) {
    searchInput.focus();
    searchInput.selectionStart = searchInput.selectionEnd = searchInput.value.length;
  }
});
</script>

<?php require_once 'admin_footer.php'; ?>
