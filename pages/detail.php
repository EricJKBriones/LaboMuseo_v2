<?php
// pages/detail.php
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
$ex = dbOne("SELECT e.*, c.name as cat_name FROM exhibits e LEFT JOIN categories c ON e.category_id=c.id WHERE e.id=?", [$id]);
if (!$ex) {
    echo '<div style="text-align:center;padding:80px"><h2>Artifact not found.</h2><a href="index.php?page=exhibits">← Back to Collection</a></div>';
    return;
}
$back = isset($_GET['cat']) ? 'index.php?page=exhibits&cat='.(int)$_GET['cat'] : 'index.php?page=exhibits';
?>

<div class="det-wrap">
  <a href="<?= $back ?>" class="det-back">&#8592; Back to Collection</a>
  <div class="det-grid">
    <div class="det-img-wrap">
      <?php if ($ex['image_path'] && file_exists('uploads/'.$ex['image_path'])): ?>
        <img
          src="uploads/<?= htmlspecialchars($ex['image_path']) ?>"
          alt="<?= htmlspecialchars($ex['title']) ?>"
          class="det-zoomable"
          data-full-src="uploads/<?= htmlspecialchars($ex['image_path']) ?>"
          data-full-alt="<?= htmlspecialchars($ex['title']) ?>"
          onclick="openDetailImage(this)">
      <?php else: ?>
        <div class="det-img-ph">&#127994;</div>
      <?php endif; ?>
    </div>
    <div>
      <div class="det-dept"><?= htmlspecialchars($ex['cat_name'] ?? 'Uncategorized') ?></div>
      <h2 class="det-title"><?= htmlspecialchars($ex['title']) ?></h2>
      <div class="det-meta">
        <div class="det-mi"><div class="det-ml">Period / Year</div><div class="det-mv"><?= htmlspecialchars($ex['artifact_year'] ?? 'Unknown') ?></div></div>
        <div class="det-mi"><div class="det-ml">Origin</div><div class="det-mv"><?= htmlspecialchars($ex['origin'] ?? 'Unknown') ?></div></div>
        <div class="det-mi"><div class="det-ml">Donated By</div><div class="det-mv"><?= htmlspecialchars($ex['donated_by'] ?? 'Museum Archive') ?></div></div>
        <div class="det-mi"><div class="det-ml">Department</div><div class="det-mv"><?= htmlspecialchars($ex['cat_name'] ?? '—') ?></div></div>
      </div>
      <div class="det-dh">Description</div>
      <p class="det-desc"><?= nl2br(htmlspecialchars($ex['description'] ?? '')) ?></p>
    </div>
  </div>
</div>

<div class="img-lightbox" id="detailLightbox" aria-hidden="true" onclick="closeDetailImage(event)">
  <button type="button" class="img-lightbox-close" aria-label="Close image" onclick="closeDetailImage(event)">&times;</button>
  <img id="detailLightboxImg" src="" alt="" class="img-lightbox-media">
</div>
