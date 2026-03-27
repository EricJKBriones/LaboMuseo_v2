<?php
// pages/pdf_detail.php
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

$pdfFileName = 'ksay-say layout fin1 final 6-8-23.pdf';
$pdfPath = 'uploads/' . $pdfFileName;
$pdfUrl = 'uploads/' . rawurlencode($pdfFileName);
$pdfCoverFile = 'PDF cover.png';
$pdfCoverPath = 'uploads/' . $pdfCoverFile;
$pdfCoverUrl = 'uploads/' . rawurlencode($pdfCoverFile);
$pdfAbsolute = __DIR__ . '/../' . $pdfPath;
$pdfCoverAbsolute = __DIR__ . '/../' . $pdfCoverPath;

if (!file_exists($pdfAbsolute)) {
    echo '<div style="text-align:center;padding:80px"><h2>Document not found.</h2><a href="index.php?page=exhibits">← Back to Collection</a></div>';
    return;
}
?>

<div class="det-hero">
  <div style="max-width:1080px;margin:0 auto">
    <div class="sec-label">Artifact Document</div>
    <h1 style="color:#fff;font-family:\'Playfair Display\',serif;font-size:1.9rem;margin:0">Ksay-say Layout (Final 6-8-23)</h1>
  </div>
</div>

<div class="det-wrap">
  <a href="index.php?page=exhibits" class="det-back">&#8592; Back to Collection</a>
  <div class="det-grid">
    <div class="det-img-wrap det-pdf-preview-wrap">
      <?php if (file_exists($pdfCoverAbsolute)): ?>
        <img
          src="<?= htmlspecialchars($pdfCoverUrl) ?>"
          alt="Ksay-say Layout cover"
          class="det-zoomable"
          data-full-src="<?= htmlspecialchars($pdfCoverUrl) ?>"
          data-full-alt="Ksay-say Layout cover"
          onclick="openDetailImage(this)">
      <?php else: ?>
        <iframe
          src="<?= htmlspecialchars($pdfUrl) ?>#page=1&toolbar=0&navpanes=0&scrollbar=0&view=FitH"
          title="Ksay-say Layout first page preview"
          class="det-pdf-preview"
          loading="lazy"></iframe>
      <?php endif; ?>
    </div>
    <div>
      <div class="det-dept">Document Archive</div>
      <h2 class="det-title">Ksay-say Layout (Final 6-8-23)</h2>
      <div class="det-meta">
        <div class="det-mi"><div class="det-ml">Type</div><div class="det-mv">PDF Document</div></div>
        <div class="det-mi"><div class="det-ml">Period / Year</div><div class="det-mv">2023</div></div>
        <div class="det-mi"><div class="det-ml">Origin</div><div class="det-mv">Labo</div></div>
        <div class="det-mi"><div class="det-ml">Department</div><div class="det-mv">Document Archive</div></div>
      </div>
      <div class="det-dh">Description</div>
      <p class="det-desc">This artifact is preserved as a digital PDF. The panel on the left shows the first page preview. Use the button below to open full reading mode with a larger viewer optimized for reading.</p>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
        <a href="index.php?page=pdf_reader" class="btn-gold">Read Full Document</a>
      </div>
    </div>
  </div>
</div>

<div class="img-lightbox" id="detailLightbox" aria-hidden="true" onclick="closeDetailImage(event)">
  <button type="button" class="img-lightbox-close" aria-label="Close image" onclick="closeDetailImage(event)">&times;</button>
  <img id="detailLightboxImg" src="" alt="" class="img-lightbox-media">
</div>
