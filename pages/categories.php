<?php
// pages/categories.php
if (!isLoggedIn()) {
    renderGuestbookLockView(
      'Sign In to Browse Departments',
      'The digital catalog is available exclusively to registered visitors. Sign our Digital Guestbook to gain full access to all departments and artifacts.'
    );
    return;
}

$search = trim($_GET['q'] ?? '');
$params = [];
$sql    = "SELECT c.*, COUNT(e.id) as artifact_count FROM categories c LEFT JOIN exhibits e ON e.category_id=c.id";
if ($search) { $sql .= " WHERE c.name LIKE ?"; $params[] = "%$search%"; }
$sql .= " GROUP BY c.id ORDER BY c.name ASC";
$cats = dbQuery($sql, $params);
?>

<div class="page-hero">
  <div class="sec-label">Explore</div>
  <h1 class="sec-title">Museum Departments</h1>
  <p class="page-hero-sub">Browse our curated collection organized by historical category and period.</p>
</div>

<div class="wrap">
  <form method="GET" action="index.php" class="search-row" style="max-width:500px;margin:0 auto 44px">
    <input type="hidden" name="page" value="categories">
    <div class="s-wrap">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" class="s-inp" placeholder="Search departments..." value="<?= htmlspecialchars($search) ?>" oninput="liveSearch('liveSearchDept','cat-card')">
    </div>
    <button type="submit" class="btn-srch artifact-search-icon-btn" aria-label="Search departments">
      <img class="icon-swap" src="<?= $base ?>assets/Icon/search.png" data-png="<?= $base ?>assets/Icon/search.png" data-gif="<?= $base ?>assets/Icon/search.gif" alt="" aria-hidden="true">
    </button>
    <?php if ($search): ?><a href="index.php?page=categories" class="btn-clr">Clear</a><?php endif; ?>
  </form>

  <?php if (empty($cats)): ?>
    <div class="empty-state"><div class="ei">&#127963;</div><h3>No departments found</h3><p>Try a different search term.</p></div>
  <?php else: ?>
  <div class="grid4" id="catGrid">
    <?php foreach ($cats as $c): ?>
    <a href="index.php?page=exhibits&cat=<?= $c['id'] ?>" class="cat-card" style="text-decoration:none">
      <div class="cat-img">
        <?php if ($c['image_path'] && file_exists('uploads/'.$c['image_path'])): ?>
          <img src="uploads/<?= htmlspecialchars($c['image_path']) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
        <?php else: ?>
          <div class="cat-img-ph">&#127963;</div>
        <?php endif; ?>
        <div class="cat-img-overlay"></div>
      </div>
      <div class="cat-body">
        <div class="cat-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="cat-count"><?= $c['artifact_count'] ?> artifact<?= $c['artifact_count']!=1?'s':'' ?></div>
        <?php if ($c['description']): ?>
          <div style="font-size:.78rem;color:var(--text3);margin-bottom:8px;line-height:1.5"><?= htmlspecialchars(mb_substr($c['description'],0,80)) ?>...</div>
        <?php endif; ?>
        <div class="cat-btn">View Artifacts <img class="icon-swap btn-arrow-icon" src="assets/Icon/right-arrow.png" data-png="assets/Icon/right-arrow.png" data-gif="assets/Icon/right-arrow.gif" alt="" aria-hidden="true"></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
