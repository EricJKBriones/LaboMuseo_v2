<?php
// pages/exhibits.php
if (!isLoggedIn()) {
    renderGuestbookLockView(
      'Sign In to View All Artifacts',
      'The full artifact catalog is available exclusively to registered visitors. Sign our Digital Guestbook for complete access.'
    );
    return;
}

$search    = trim($_GET['q'] ?? '');
$catFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
$originFilter = trim($_GET['origin'] ?? '');
$yearFilter   = trim($_GET['year'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$sortMap = [
  'newest'     => 'e.id DESC',
  'oldest'     => 'e.id ASC',
  'title_asc'  => 'e.title ASC',
  'title_desc' => 'e.title DESC',
  'year_desc'  => 'e.artifact_year DESC',
  'year_asc'   => 'e.artifact_year ASC'
];
if (!isset($sortMap[$sort])) $sort = 'newest';

$params = [];
$sql    = "SELECT e.*, c.name as cat_name FROM exhibits e LEFT JOIN categories c ON e.category_id=c.id WHERE 1=1";
if ($catFilter) { $sql .= " AND e.category_id=?"; $params[] = $catFilter; }
if ($search)    { $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.origin LIKE ? OR e.donated_by LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
if ($originFilter !== '') { $sql .= " AND e.origin=?"; $params[] = $originFilter; }
if ($yearFilter !== '')   { $sql .= " AND e.artifact_year=?"; $params[] = $yearFilter; }
$sql .= " ORDER BY " . $sortMap[$sort];
$exhibits = dbQuery($sql, $params);

$categories = dbQuery("SELECT * FROM categories ORDER BY name ASC");
$origins = dbQuery("SELECT DISTINCT origin FROM exhibits WHERE origin IS NOT NULL AND TRIM(origin)<>'' ORDER BY origin ASC");
$years   = dbQuery("SELECT DISTINCT artifact_year FROM exhibits WHERE artifact_year IS NOT NULL AND TRIM(artifact_year)<>'' ORDER BY artifact_year DESC");
$currentCat = $catFilter ? dbOne("SELECT * FROM categories WHERE id=?", [$catFilter]) : null;

$pdfArtifact = [
  'title' => 'Ksay-say Layout (Final 6-8-23)',
  'description' => 'Museum publication in PDF format. Click to view document details, first-page preview, and open full reading mode.',
  'origin' => 'Labo',
  'artifact_year' => '2023',
  'cat_name' => 'Document Archive'
];
$pdfCoverFile = 'PDF cover.png';
$pdfCoverPath = 'uploads/' . $pdfCoverFile;
$pdfCoverUrl = 'uploads/' . rawurlencode($pdfCoverFile);
$pdfCoverExists = file_exists(__DIR__ . '/../' . $pdfCoverPath);

$showPdfArtifact = true;
if ($catFilter) {
  $showPdfArtifact = false;
}
if ($showPdfArtifact && $originFilter !== '' && strcasecmp($originFilter, $pdfArtifact['origin']) !== 0) {
  $showPdfArtifact = false;
}
if ($showPdfArtifact && $yearFilter !== '' && $yearFilter !== $pdfArtifact['artifact_year']) {
  $showPdfArtifact = false;
}
if ($showPdfArtifact && $search !== '') {
  $pdfHaystack = strtolower($pdfArtifact['title'] . ' ' . $pdfArtifact['description'] . ' ' . $pdfArtifact['origin']);
  if (strpos($pdfHaystack, strtolower($search)) === false) {
    $showPdfArtifact = false;
  }
}
?>

<div class="page-hero">
  <div class="sec-label">Collection</div>
  <h1 class="sec-title"><?= $currentCat ? htmlspecialchars($currentCat['name']) : 'All Artifacts' ?></h1>
  <p class="page-hero-sub"><?= $currentCat ? 'Browse artifacts in the '.htmlspecialchars($currentCat['name']).' department.' : 'Discover the complete museum collection documenting the rich legacy of Labo.' ?></p>
</div>

<div class="wrap">
  <!-- Search + Filter -->
  <form method="GET" action="index.php" class="artifact-filter-form" data-artifact-filter-form="1">
    <input type="hidden" name="page" value="exhibits">
    <div class="artifact-filter-top">
      <div class="artifact-filter-row artifact-filter-row-search">
        <div class="s-wrap artifact-search-wrap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" id="exSearch" class="s-inp" placeholder="Search artifacts..." value="<?= htmlspecialchars($search) ?>" oninput="liveSearch('exSearch','ex-card')">
        </div>
        <button type="submit" class="btn-srch artifact-search-icon-btn" aria-label="Search artifacts">
          <img class="icon-swap" src="<?= $base ?>assets/Icon/search.png" data-png="<?= $base ?>assets/Icon/search.png" data-gif="<?= $base ?>assets/Icon/search.gif" alt="" aria-hidden="true">
        </button>
        <?php if ($search || $catFilter || $originFilter!=='' || $yearFilter!=='' || $sort!=='newest'): ?>
          <a href="index.php?page=exhibits" class="btn-clr artifact-clear-icon-btn" aria-label="Clear selected filters">
            <img class="icon-swap" src="<?= $base ?>assets/Icon/clear_filter.png" data-png="<?= $base ?>assets/Icon/clear_filter.png" data-gif="<?= $base ?>assets/Icon/clear_filter.gif" alt="" aria-hidden="true">
          </a>
        <?php endif; ?>
      </div>
      <div class="artifact-filter-row artifact-filter-row-controls">
        <button type="button" class="artifact-filter-trigger" data-filter-open aria-expanded="false" aria-controls="artifactMoreFilters">More Filters</button>
      </div>
      <?php if ($search || $catFilter || $originFilter!=='' || $yearFilter!=='' || $sort!=='newest'): ?>
        <?php
          $activeFilterSummary = [];
          if ($catFilter && $currentCat) $activeFilterSummary[] = 'Category: '. $currentCat['name'];
          if ($originFilter !== '') $activeFilterSummary[] = 'Origin: '. $originFilter;
          if ($yearFilter !== '') $activeFilterSummary[] = 'Period/Year: '. $yearFilter;
          if ($sort !== 'newest') {
            $sortLabelMap = [
              'newest' => 'Newest',
              'oldest' => 'Oldest',
              'title_asc' => 'Title A-Z',
              'title_desc' => 'Title Z-A',
              'year_desc' => 'Year/Period Desc',
              'year_asc' => 'Year/Period Asc'
            ];
            if (isset($sortLabelMap[$sort])) {
              $activeFilterSummary[] = 'Sort: ' . $sortLabelMap[$sort];
            }
          }
          if ($search !== '') $activeFilterSummary[] = 'Search: "'. $search .'"';
        ?>
        <div class="artifact-filter-row artifact-filter-clear-row" aria-live="polite">
          <span class="artifact-filter-summary"><?= htmlspecialchars(implode(' | ', $activeFilterSummary)) ?></span>
        </div>
      <?php endif; ?>
    </div>
    <div class="artifact-filter-panel" data-filter-panel id="artifactMoreFilters">
      <div class="artifact-filter-grid">
        <label class="artifact-filter-field">
          <span>Category</span>
          <select id="mobileCategorySelect" name="cat" class="s-sel js-combo-skin" onchange="this.form.submit()">
            <option value="" <?= !$catFilter ? 'selected' : '' ?>>All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $catFilter===(int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="artifact-filter-field">
          <span>Origin</span>
          <select name="origin" class="s-sel js-combo-skin" onchange="this.form.submit()">
            <option value="">All Origins</option>
            <?php foreach ($origins as $o): ?>
              <option value="<?= htmlspecialchars($o['origin']) ?>" <?= $originFilter===$o['origin']?'selected':'' ?>><?= htmlspecialchars($o['origin']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="artifact-filter-field">
          <span>Period/Year</span>
          <select name="year" class="s-sel js-combo-skin" onchange="this.form.submit()">
            <option value="">All Periods/Years</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= htmlspecialchars($y['artifact_year']) ?>" <?= $yearFilter===$y['artifact_year']?'selected':'' ?>><?= htmlspecialchars($y['artifact_year']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="artifact-filter-field artifact-filter-sort">
          <span>Sort By</span>
          <select name="sort" class="s-sel js-combo-skin" onchange="this.form.submit()">
            <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
            <option value="title_asc" <?= $sort==='title_asc'?'selected':'' ?>>Title A-Z</option>
            <option value="title_desc" <?= $sort==='title_desc'?'selected':'' ?>>Title Z-A</option>
            <option value="year_desc" <?= $sort==='year_desc'?'selected':'' ?>>Year/Period Desc</option>
            <option value="year_asc" <?= $sort==='year_asc'?'selected':'' ?>>Year/Period Asc</option>
          </select>
        </label>
      </div>
      <div class="artifact-filter-actions">
        <button type="submit" class="btn-srch">Apply Filters</button>
      </div>
    </div>
  </form>

  <!-- Category filter chips -->
  <?php
    $chipBase = ['page' => 'exhibits'];
    if ($search !== '') $chipBase['q'] = $search;
    if ($originFilter !== '') $chipBase['origin'] = $originFilter;
    if ($yearFilter !== '') $chipBase['year'] = $yearFilter;
    if ($sort !== 'newest') $chipBase['sort'] = $sort;
  ?>
  <div class="filter-bar">
    <a href="index.php?<?= http_build_query($chipBase) ?>" class="fchip <?= !$catFilter ? 'active':'' ?>">All</a>
    <?php foreach ($categories as $c): ?>
      <?php $chipQuery = $chipBase; $chipQuery['cat'] = (int)$c['id']; ?>
      <a href="index.php?<?= http_build_query($chipQuery) ?>" class="fchip <?= $catFilter===(int)$c['id'] ? 'active':'' ?>"><?= htmlspecialchars($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="artifact-filter-divider" aria-hidden="true"></div>

  <?php if (empty($exhibits) && !$showPdfArtifact): ?>
    <div class="empty-state"><div class="ei">&#127994;</div><h3>No artifacts found</h3><p>Try adjusting your search or filter.</p></div>
  <?php else: ?>
  <div class="grid4">
    <?php if ($showPdfArtifact): ?>
    <a href="index.php?page=pdf_detail" class="ex-card ex-card-pdf" style="text-decoration:none">
      <div class="ex-img ex-img-pdf">
        <div class="pdf-icon-badge">PDF</div>
        <?php if ($pdfCoverExists): ?>
          <img src="<?= htmlspecialchars($pdfCoverUrl) ?>" alt="<?= htmlspecialchars($pdfArtifact['title']) ?> cover">
        <?php else: ?>
          <div class="ex-img-ph">&#128196;</div>
        <?php endif; ?>
      </div>
      <div class="ex-body">
        <div class="ex-title"><?= htmlspecialchars($pdfArtifact['title']) ?></div>
        <div class="ex-meta">
          <strong>Period:</strong> <?= htmlspecialchars($pdfArtifact['artifact_year']) ?><br>
          <strong>Origin:</strong> <?= htmlspecialchars($pdfArtifact['origin']) ?><br>
          <strong>Type:</strong> Document Archive
        </div>
        <span class="ex-dept"><?= htmlspecialchars($pdfArtifact['cat_name']) ?></span>
      </div>
    </a>
    <?php endif; ?>

    <?php foreach ($exhibits as $ex): ?>
    <a href="index.php?page=detail&id=<?= $ex['id'] ?>" class="ex-card" style="text-decoration:none">
      <div class="ex-img">
        <?php if ($ex['image_path'] && file_exists('uploads/'.$ex['image_path'])): ?>
          <img src="uploads/<?= htmlspecialchars($ex['image_path']) ?>" alt="<?= htmlspecialchars($ex['title']) ?>">
        <?php else: ?>
          <div class="ex-img-ph">&#127994;</div>
        <?php endif; ?>
      </div>
      <div class="ex-body">
        <div class="ex-title"><?= htmlspecialchars($ex['title']) ?></div>
        <div class="ex-meta">
          <strong>Period:</strong> <?= htmlspecialchars($ex['artifact_year'] ?? 'Unknown') ?><br>
          <strong>Origin:</strong> <?= htmlspecialchars($ex['origin'] ?? 'Unknown') ?>
        </div>
        <?php if ($ex['cat_name']): ?>
          <span class="ex-dept"><?= htmlspecialchars($ex['cat_name']) ?></span>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
