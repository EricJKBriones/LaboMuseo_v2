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

    <button
      type="button"
      class="ai-float-btn"
      id="aiFloatBtn"
      onclick="toggleAiSearchPopup()"
      title="AI Search"
      aria-label="Open AI search popup">
      Ask AI
    </button>

    <div class="ai-search-popup" id="aiSearchPopup" aria-hidden="true">
      <div class="ai-search-head">
        <div class="ai-head-meta">
          <span class="ai-head-kicker">Local Assistant</span>
          <strong>Ask this PDF</strong>
        </div>
        <button type="button" class="ai-close-btn" onclick="closeAiSearchPopup()" aria-label="Close AI search">&times;</button>
      </div>
      <p class="ai-search-sub">Powered by local Ollama + LangChain. Nothing is sent to cloud services.</p>
      <div class="ai-suggest-row">
        <button type="button" class="ai-suggest-btn" onclick="setAiPrompt('Give me a 5 bullet summary of this PDF.')">Quick summary</button>
        <button type="button" class="ai-suggest-btn" onclick="setAiPrompt('What are the key names, places, and dates in this document?')">Key details</button>
        <button type="button" class="ai-suggest-btn" onclick="setAiPrompt('Explain this document in simple Filipino.')">Simple explain</button>
      </div>
      <form onsubmit="runAiSearch(event)" class="ai-search-form">
        <input type="text" id="aiSearchInput" class="ai-search-input" placeholder="Ask about this document..." required>
        <button type="submit" class="ai-search-go" id="aiSearchGoBtn">Ask</button>
      </form>
      <div class="ai-search-result" id="aiSearchResult" aria-live="polite">
        <div class="ai-empty">Try a question above to start reading insights from this PDF.</div>
      </div>
    </div>
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

function toggleAiSearchPopup() {
  var popup = document.getElementById('aiSearchPopup');
  var btn = document.getElementById('aiFloatBtn');
  if (!popup) return;

  var isOpen = popup.classList.contains('is-open');
  if (isOpen) {
    closeAiSearchPopup();
    return;
  }

  popup.classList.add('is-open');
  popup.setAttribute('aria-hidden', 'false');
  if (btn) btn.classList.add('is-active');

  var input = document.getElementById('aiSearchInput');
  if (input) input.focus();
}

function closeAiSearchPopup() {
  var popup = document.getElementById('aiSearchPopup');
  var btn = document.getElementById('aiFloatBtn');
  if (!popup) return;

  popup.classList.remove('is-open');
  popup.setAttribute('aria-hidden', 'true');
  if (btn) btn.classList.remove('is-active');
}

function setAiPrompt(text) {
  var input = document.getElementById('aiSearchInput');
  if (!input) return;
  input.value = text;
  input.focus();
}

function runAiSearch(event) {
  if (event) event.preventDefault();
  var input = document.getElementById('aiSearchInput');
  var result = document.getElementById('aiSearchResult');
  var btn = document.getElementById('aiSearchGoBtn');
  if (!input) return;

  var query = input.value.trim();
  if (!query) return;

  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Thinking...';
  }
  if (result) {
    result.innerHTML = '<div class="ai-status"><span class="ai-status-dot"></span>Connecting to local AI service...</div>';
  }

  postAiAsk(query)
    .then(function(response) {
      if (!response.ok) {
        return response.json().then(function(data) {
          throw new Error((data && data.detail) ? data.detail : 'AI request failed.');
        }).catch(function() {
          throw new Error('AI request failed.');
        });
      }
      return response.json();
    })
    .then(function(data) {
      if (!result) return;
      var answer = (data && data.answer) ? data.answer : 'No answer returned.';
      var sources = Array.isArray(data && data.sources) ? data.sources : [];

      var srcHtml = '';
      if (sources.length) {
        srcHtml = '<div class="ai-sources"><strong>Source snippets:</strong><ul>'
          + sources.map(function(s) { return '<li>' + escapeHtml(s) + '</li>'; }).join('')
          + '</ul></div>';
      }

      result.innerHTML = '<div class="ai-answer">' + escapeHtml(answer) + '</div>' + srcHtml;
    })
    .catch(function(error) {
      if (!result) return;
      var message = (error && error.message) ? error.message : 'Unknown error';
      result.innerHTML = '<div class="ai-error">Local AI is not ready.<br>'
        + '1) Start API: <code>python ai/rag_server.py</code><br>'
        + '2) Install/start Ollama and run: <code>ollama serve</code><br>'
        + '3) Pull model: <code>ollama pull llama3</code> (or <code>phi3</code>)<br>'
        + 'Error: ' + escapeHtml(message) + '</div>';
    })
    .finally(function() {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Ask';
      }
    });
}

function postAiAsk(question) {
  var endpoints = ['http://127.0.0.1:8008/ask', 'http://localhost:8008/ask'];

  function tryIndex(i) {
    if (i >= endpoints.length) {
      return Promise.reject(new Error('Failed to fetch'));
    }

    return fetch(endpoints[i], {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question: question })
    }).catch(function() {
      return tryIndex(i + 1);
    });
  }

  return tryIndex(0);
}

function escapeHtml(value) {
  if (value == null) return '';
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

document.addEventListener('fullscreenchange', syncReaderFullscreenButton);
document.addEventListener('DOMContentLoaded', syncReaderFullscreenButton);

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeReaderExitPopup();
    closeAiSearchPopup();
  }
});

document.addEventListener('click', function(event) {
  var popup = document.getElementById('aiSearchPopup');
  var btn = document.getElementById('aiFloatBtn');
  if (!popup || !btn) return;
  if (!popup.classList.contains('is-open')) return;

  if (popup.contains(event.target) || btn.contains(event.target)) return;
  closeAiSearchPopup();
});
</script>
