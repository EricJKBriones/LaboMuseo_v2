<?php
// pages/pdf_reader.php
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

$pdfFileName = 'ksay-say layout fin1 final 6-8-23.pdf';
$pdfPath = 'uploads/' . $pdfFileName;
$pdfUrl = 'uploads/' . rawurlencode($pdfFileName);
$pdfAbsolute = __DIR__ . '/../' . $pdfPath;

if (!file_exists($pdfAbsolute)) {
    echo '<div style="text-align:center;padding:80px"><h2>Document not found.</h2><a href="index.php?page=exhibits">← Back to Collection</a></div>';
    return;
}
?>

<div class="pdf-reader-wrap">
  <div class="pdf-reader-toolbar">
    <div class="pdf-reader-title-wrap">
      <div class="sec-label" style="margin-bottom:4px">Reading Mode</div>
      <h1 class="pdf-reader-title">Ksay-say Layout (Final 6-8-23)</h1>
    </div>
    <div class="pdf-reader-actions">
      <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener" class="btn-outline">Open in New Tab</a>
      <button type="button" class="btn-reader-fullscreen" id="readerFullscreenBtn" onclick="toggleReaderFullscreen()">Enter Fullscreen</button>
      <button type="button" class="btn-exit-reader" onclick="openReaderExitPopup()">Exit Reader</button>
    </div>
  </div>

  <div class="pdf-reader-frame-wrap" id="pdfReaderFrameWrap">
    <iframe
      src="<?= htmlspecialchars($pdfUrl) ?>#view=FitH"
      title="Ksay-say Layout full reader"
      class="pdf-reader-frame"></iframe>
  </div>
</div>

<div class="reader-exit-popup" id="readerExitPopup" aria-hidden="true" onclick="closeReaderExitPopup(event)">
  <div class="reader-exit-dialog" role="dialog" aria-modal="true" aria-labelledby="readerExitTitle" onclick="event.stopPropagation()">
    <h2 id="readerExitTitle">Exit Reading Mode?</h2>
    <p>You will return to the document detail page.</p>
    <div class="reader-exit-actions">
      <button type="button" class="btn-outline" onclick="closeReaderExitPopup(event)">Stay</button>
      <a href="index.php?page=pdf_detail" class="btn-gold">Yes, Exit</a>
    </div>
  </div>
</div>

<script>
function openReaderExitPopup() {
  var popup = document.getElementById('readerExitPopup');
  if (!popup) return;
  popup.classList.add('is-open');
  popup.setAttribute('aria-hidden', 'false');
}

function closeReaderExitPopup(event) {
  if (event) event.preventDefault();
  var popup = document.getElementById('readerExitPopup');
  if (!popup) return;
  popup.classList.remove('is-open');
  popup.setAttribute('aria-hidden', 'true');
}

function toggleReaderFullscreen() {
  var wrap = document.getElementById('pdfReaderFrameWrap');
  if (!wrap) return;

  var inFullscreen = document.fullscreenElement === wrap;
  if (inFullscreen) {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
    return;
  }

  if (wrap.requestFullscreen) {
    wrap.requestFullscreen();
  }
}

function syncReaderFullscreenButton() {
  var btn = document.getElementById('readerFullscreenBtn');
  var wrap = document.getElementById('pdfReaderFrameWrap');
  if (!btn || !wrap) return;

  var inFullscreen = document.fullscreenElement === wrap;
  btn.textContent = inFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen';
}

document.addEventListener('fullscreenchange', syncReaderFullscreenButton);
document.addEventListener('DOMContentLoaded', syncReaderFullscreenButton);

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeReaderExitPopup();
  }
});
</script>
