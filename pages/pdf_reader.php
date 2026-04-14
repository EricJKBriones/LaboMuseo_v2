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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">
<style>
    /* Fix for mobile viewport height changes (address bar show/hide) */
    html, body { height: 100%; }
    
    /* Prevent zoom on iOS input focus */
    input { font-size: 16px !important; }
    
    /* Disable user selection of PDF viewer controls on mobile */
    @media(max-width:768px) {
        .pdf-reader-frame-wrap { position: relative; top: 0; }
        body { height: 100dvh; }
    }
    
    /* Panel and UI Styles */
    .ai-search-popup {
        width: 320px;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s;
    }
    
    .ai-search-popup.minimized {
        transform: translateY(calc(100% - 55px));
        opacity: 0.95;
    }

    .btn-minimize-panel {
        background: rgba(0,0,0,0.05);
        border: none;
        font-size: 20px;
        cursor: pointer;
        line-height: 1;
        padding: 0 8px;
        border-radius: 4px;
        color: #666;
    }

    .ai-search-result {
        max-height: 200px;
        overflow-y: auto;
        scrollbar-width: thin;
        margin-top: 10px;
    }

    .search-jump-item {
        background: #d4af37;
        color: #000;
        margin-bottom: 8px;
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        transition: 0.2s;
    }
    .search-jump-item:hover { transform: translateX(-5px); filter: brightness(1.1); }
    
    .speed-ctrl {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
</style>

<div class="pdf-reader-wrap">
  <div class="pdf-reader-toolbar">
    <div class="pdf-reader-title-wrap">
      <div class="sec-label" style="margin-bottom:4px">Reading Mode</div>
      <h1 class="pdf-reader-title">Ksay-say Layout (Final 6-8-23)</h1>
    </div>
    <div class="pdf-reader-actions">
      <button type="button" class="btn-reader-fullscreen" id="readerFullscreenBtn" onclick="toggleReaderFullscreen()">Enter Fullscreen</button>
      <button type="button" class="btn-exit-reader" onclick="openReaderExitPopup()">Exit Reader</button>
    </div>
  </div>

  <div class="pdf-reader-frame-wrap" id="pdfReaderFrameWrap">
    <iframe
      id="pdfIframe"
      src="<?= htmlspecialchars($pdfUrl) ?>#view=FitH"
      title="Ksay-say Layout full reader"
      class="pdf-reader-frame"></iframe>

    <div id="pdfMobileFallback" style="display:none;flex-direction:column;gap:16px;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;background:#faf7ef;min-height:300px;">
      <strong style="font-size:18px;color:#2b2b2b;">📄 PDF Viewer</strong>
      <p style="margin:0;color:#5b5b5b;max-width:460px;line-height:1.6;">Your browser works best when opening PDFs directly. Tap the button below to view the document in fullscreen mode.</p>
      <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener" class="btn-gold" style="background:#d4af37;color:#000;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:700;display:inline-block;margin-top:8px;">Open PDF in Full View</a>
      <small style="color:#8b8b8b;margin-top:8px;">Opens in a new tab</small>
    </div>

    <button
      type="button"
      class="ai-float-btn"
      id="aiFloatBtn"
      onclick="toggleAiSearchPopup()"
      title="Search & Scroll">
      Search & Scroll
    </button>

    <div class="ai-search-popup" id="aiSearchPopup" aria-hidden="true">
      <div class="ai-search-head">
        <div class="ai-head-meta">
          <span class="ai-head-kicker">Navigation</span>
          <strong>Find & Scroll</strong>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="btn-minimize-panel" onclick="toggleMinimizePanel()" id="minBtn">−</button>
            <button type="button" class="ai-close-btn" onclick="closeAiSearchPopup()">&times;</button>
        </div>
      </div>
      
      <div id="panelContent">
          <form onsubmit="runPdfSearch(event)" class="ai-search-form">
            <input type="text" id="aiSearchInput" class="ai-search-input" placeholder="Search keyword..." required>
            <button type="submit" class="ai-search-go" id="aiSearchGoBtn">Find</button>
          </form>

          <div class="ai-search-result" id="aiSearchResult" aria-live="polite">
            <div class="ai-empty">Results will appear here.</div>
          </div>

          <div class="speed-ctrl">
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <label style="font-size:11px; font-weight:bold; color:#666;">AUTOSCROLL SPEED</label>
                <span id="speedVal" style="font-size:11px; color:#d4af37; font-weight:bold;">2x</span>
            </div>
            <input type="range" id="scrollSpeedRange" min="0" max="15" value="2" style="width:100%; accent-color:#d4af37;" oninput="updateScrollSpeed()">
          </div>
          
      </div>
    </div>
  </div>
</div>
<div class="reader-exit-popup" id="readerExitPopup" aria-hidden="true" onclick="closeReaderExitPopup(event)">
  <div class="reader-exit-dialog" role="dialog" aria-modal="true" onclick="event.stopPropagation()">
    <h2 style="margin-top:0; color:#333;">Exit Reading Mode?</h2>
    <p style="color:#666;">You will return to the document detail page.</p>
    <div class="reader-exit-actions" style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
      <button type="button" class="btn-outline" onclick="closeReaderExitPopup(event)" style="padding:10px 20px; cursor:pointer;">Stay</button>
      <a href="index.php?page=pdf_detail" class="btn-gold" style="background:#d4af37; color:#000; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">Yes, Exit</a>
    </div>
  </div>
</div>
<script>
const PDF_URL = "<?= $pdfUrl ?>";
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

let pdfDoc = null;
let isScrolling = false;
let scrollInterval = null;
let scrollSpeed = 2;

// Load PDF background for scanning
pdfjsLib.getDocument(PDF_URL).promise.then(doc => { pdfDoc = doc; });

async function runPdfSearch(event) {
    if (event) event.preventDefault();
    const query = document.getElementById('aiSearchInput').value.trim();
    const resultDiv = document.getElementById('aiSearchResult');
    
    if (!query || !pdfDoc) return;

    resultDiv.innerHTML = '<div style="padding:10px; text-align:center;">Scanning...</div>';
    let matches = [];

    for (let i = 1; i <= pdfDoc.numPages; i++) {
        const page = await pdfDoc.getPage(i);
        const textContent = await page.getTextContent();
        const text = textContent.items.map(item => item.str).join(' ').toLowerCase();
        if (text.includes(query.toLowerCase())) matches.push(i);
    }

    if (matches.length > 0) {
        resultDiv.innerHTML = '';
        matches.forEach(pageNum => {
            const div = document.createElement('div');
            div.className = 'search-jump-item';
            div.innerHTML = `<span>Page ${pageNum}</span> <small>Jump & Highlight</small>`;
            div.onclick = () => jumpToPage(pageNum, query);
            resultDiv.appendChild(div);
        });
    } else {
        resultDiv.innerHTML = '<div class="ai-empty">No matches found.</div>';
    }
}


function jumpToPage(pageNum, query) {
    const iframe = document.getElementById('pdfIframe');
  if (!iframe || iframe.style.display === 'none') {
    window.open(`${PDF_URL}#page=${pageNum}`, '_blank', 'noopener');
    return;
  }
    
    // 1. We add a 't' parameter (timestamp) to the URL.
    // This forces the browser to treat it as a new request so it re-scans for highlights.
    const baseUrl = PDF_URL.split('#')[0]; // Get URL without existing hashes
    const targetUrl = `${baseUrl}?t=${Date.now()}#page=${pageNum}&search="${encodeURIComponent(query)}"`;

    // 2. Clear the iframe briefly to reset the browser's internal Find state
    iframe.src = 'about:blank'; 
    
    setTimeout(() => {
        iframe.src = targetUrl;
    }, 60); // 60ms is the "sweet spot" to trigger a reload without a long flicker
}

/** UI & SCROLL LOGIC **/
function toggleMinimizePanel() {
    const popup = document.getElementById('aiSearchPopup');
    const minBtn = document.getElementById('minBtn');
    const isMinimized = popup.classList.toggle('minimized');
    minBtn.textContent = isMinimized ? '+' : '−';
}

function updateScrollSpeed() {
    scrollSpeed = parseInt(document.getElementById('scrollSpeedRange').value);
    document.getElementById('speedVal').textContent = scrollSpeed + 'x';
    if (scrollSpeed > 0 && !isScrolling) startScrolling();
    if (scrollSpeed === 0) stopScrolling();
}

function startScrolling() {
    if (isScrolling) clearInterval(scrollInterval);
    isScrolling = true;
    const iframe = document.getElementById('pdfIframe');
  if (!iframe) return;
    scrollInterval = setInterval(() => {
        if(iframe.contentWindow) iframe.contentWindow.scrollBy(0, scrollSpeed);
    }, 30);
}

function stopScrolling() {
    clearInterval(scrollInterval);
    isScrolling = false;
}

function toggleAiSearchPopup() {
    var popup = document.getElementById('aiSearchPopup');
    var btn = document.getElementById('aiFloatBtn');
    if (popup.classList.contains('is-open')) {
        closeAiSearchPopup();
    } else {
        popup.classList.add('is-open');
        popup.classList.remove('minimized');
        document.getElementById('minBtn').textContent = '−';
        btn.classList.add('is-active');
        startScrolling();
    }
}

function closeAiSearchPopup() {
    var popup = document.getElementById('aiSearchPopup');
    var btn = document.getElementById('aiFloatBtn');
    popup.classList.remove('is-open');
    btn.classList.remove('is-active');
    stopScrolling();
}

function toggleReaderFullscreen() {
    var wrap = document.getElementById('pdfReaderFrameWrap');
    if (document.fullscreenElement === wrap) document.exitFullscreen();
    else wrap.requestFullscreen();
}

function isLikelyMobilePdfEmbedUnsupported() {
  var ua = navigator.userAgent || '';
  var isIOS = /iPad|iPhone|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isAndroidMobile = /Android/i.test(ua) && /Mobile/i.test(ua);
  var isTouchDevice = () => (('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0));
  
  // Return true if it's mobile/touch and likely has issues with embedded PDFs
  return (isIOS || isAndroidMobile || isTouchDevice());
}

function applyMobilePdfFallback() {
  var iframe = document.getElementById('pdfIframe');
  var fallback = document.getElementById('pdfMobileFallback');
  var aiBtn = document.getElementById('aiFloatBtn');

  if (!iframe || !fallback) return;
  if (!isLikelyMobilePdfEmbedUnsupported()) return;

  iframe.style.display = 'none';
  fallback.style.display = 'flex';
  if (aiBtn) aiBtn.style.display = 'none';
  stopScrolling();
}

// Additional fallback: if PDF fails to load on mobile after timeout
function enableMobilePdfLoadFallback() {
  if (!isLikelyMobilePdfEmbedUnsupported()) return;
  
  var iframe = document.getElementById('pdfIframe');
  var fallback = document.getElementById('pdfMobileFallback');
  var aiBtn = document.getElementById('aiFloatBtn');
  var loadTimeout;
  
  try {
    // Check if iframe loaded successfully
    loadTimeout = setTimeout(function() {
      // If no contentWindow access (PDF load failed), show fallback
      if (!iframe.contentWindow || !iframe.contentDocument) {
        iframe.style.display = 'none';
        fallback.style.display = 'flex';
        if (aiBtn) aiBtn.style.display = 'none';
        stopScrolling();
      }
    }, 3000); // Wait 3 seconds for PDF to load
    
    // Clear timeout if iframe loads
    iframe.addEventListener('load', function() {
      clearTimeout(loadTimeout);
    });
  } catch(e) {
    // Error accessing iframe, show fallback
    iframe.style.display = 'none';
    fallback.style.display = 'flex';
    if (aiBtn) aiBtn.style.display = 'none';
    stopScrolling();
  }
}

function openReaderExitPopup() {
    document.getElementById('readerExitPopup').classList.add('is-open');
}

function closeReaderExitPopup(event) {
    if (event) event.preventDefault();
    document.getElementById('readerExitPopup').classList.remove('is-open');
}

document.addEventListener('fullscreenchange', function() {
    var btn = document.getElementById('readerFullscreenBtn');
    var wrap = document.getElementById('pdfReaderFrameWrap');
    btn.textContent = (document.fullscreenElement === wrap) ? 'Exit Fullscreen' : 'Enter Fullscreen';
});

document.addEventListener('DOMContentLoaded', function() {
  applyMobilePdfFallback();
  enableMobilePdfLoadFallback();
});
</script>