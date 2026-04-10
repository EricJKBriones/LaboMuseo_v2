<?php
// admin/showcase.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

$displayMode = isset($_GET['display']) && $_GET['display'] === '1';
$tourismLogoPath = __DIR__ . '/../uploads/tourism-logo.png';
$tourismLogoUrl = '../uploads/tourism-logo.png';
$mainLogoPath = __DIR__ . '/../uploads/logo.png';
$mainLogoUrl = '../uploads/logo.png';

$artifacts = dbQuery(
    "SELECT id, title, artifact_year, donated_by, image_path FROM exhibits WHERE title IS NOT NULL AND title <> '' ORDER BY id DESC LIMIT 40"
);

if (empty($artifacts)) {
    $artifacts[] = [
        'id' => 0,
        'title' => 'No artifacts yet',
        'artifact_year' => '',
        'donated_by' => '',
        'image_path' => ''
    ];
}

$pageTitle = 'Showcase Display - ' . SITE_NAME;
$bodyClass = trim(($bodyClass ?? '') . ' showcase-quick-fade');
require_once 'admin_header.php';
?>

<div class="adm-layout<?= $displayMode ? ' showcase-display-shell' : '' ?>">
  <?php if (!$displayMode): ?>
    <?php require_once 'sidebar.php'; ?>
  <?php endif; ?>

  <main class="adm-main showcase-main<?= $displayMode ? ' is-display-mode' : '' ?>">
    <section class="showcase-page" id="showcasePage" data-display-mode="<?= $displayMode ? '1' : '0' ?>">
      <?php if (!$displayMode): ?>
      <div class="showcase-toolbar">
        <div>
          <h2 class="showcase-title">Museum Showcase</h2>
          <p class="showcase-sub">Admin-only rotating display of artifact highlights.</p>
        </div>
        <div class="showcase-toolbar-actions">
          <a href="showcase.php?display=1" target="_blank" rel="noopener" class="btn-navy showcase-open-btn">Open in New Tab</a>
          <button type="button" class="btn-exp showcase-open-btn" onclick="openShowcaseOnSecondScreen()">Open in Second Screen</button>
        </div>
      </div>
      <?php endif; ?>

      <div class="showcase-stage-wrap" id="showcaseStageWrap">
        <div class="showcase-stage" id="showcaseStage" aria-live="polite">
          <?php foreach ($artifacts as $idx => $item): ?>
            <?php
              $imgPath = !empty($item['image_path']) ? __DIR__ . '/../uploads/' . $item['image_path'] : '';
              $hasImg = $imgPath && file_exists($imgPath);
            ?>
            <article class="showcase-slide<?= $idx === 0 ? ' is-active' : '' ?>" data-index="<?= $idx ?>">
              <div class="showcase-card">
                <div class="showcase-media">
                  <?php if ($hasImg): ?>
                    <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                  <?php else: ?>
                    <div class="showcase-media-ph">Museo de Labo</div>
                  <?php endif; ?>
                </div>
                <div class="showcase-overlay"></div>
                <div class="showcase-info">
                  <h3><?= htmlspecialchars($item['title']) ?></h3>
                  <p>
                    <span><strong>Year:</strong> <?= htmlspecialchars($item['artifact_year'] ?: 'Unknown') ?></span>
                    <span><strong>Donated by:</strong> <?= htmlspecialchars($item['donated_by'] ?: 'Not specified') ?></span>
                  </p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>

          <div class="showcase-branding" id="showcaseBranding">
            <div class="showcase-logo-row">
              <div class="showcase-logo-mark">
                <?php if (file_exists($tourismLogoPath)): ?>
                  <img src="<?= $tourismLogoUrl ?>" alt="Labo Tourism Logo">
                <?php elseif (file_exists($mainLogoPath)): ?>
                  <img src="<?= $mainLogoUrl ?>" alt="Museo de Labo Logo">
                <?php else: ?>
                  <span aria-hidden="true">ML</span>
                <?php endif; ?>
              </div>
              <div>
                <div class="showcase-logo-text">MUSEO DE LABO</div>
                <div class="showcase-logo-baybayin">ᜋᜓᜐᜒᜂ ᜇᜒ ᜎᜊᜓ</div>
              </div>
            </div>
          </div>

          <div class="showcase-datetime" id="showcaseDateTime" aria-live="off"></div>

          <button type="button" class="showcase-arrow prev" id="showcasePrev" aria-label="Previous artifact">&#10094;</button>
          <button type="button" class="showcase-arrow next" id="showcaseNext" aria-label="Next artifact">&#10095;</button>

          <div class="showcase-top-actions">
            <button type="button" class="showcase-chip" id="showcaseFullscreenBtn" data-icon-name="full_screen" aria-label="Fullscreen" title="Fullscreen" onclick="enterShowcaseFullscreen()"></button>
          </div>
        </div>

        <aside class="showcase-qr-float" id="showcaseQrFloat" aria-label="QR code placeholder">
          <div class="showcase-qr-blank-card">
            <div class="showcase-qr-blank-box" aria-hidden="true"></div>
            <div class="showcase-qr-blank-label">QR Code</div>
          </div>
        </aside>
      </div>
    </section>
  </main>
</div>

<script>
(function() {
  var page = document.getElementById('showcasePage');
  if (!page) return;

  var isDisplayMode = page.getAttribute('data-display-mode') === '1';
  if (isDisplayMode) {
    document.body.classList.add('showcase-display-mode');
  }

  var slides = Array.prototype.slice.call(document.querySelectorAll('.showcase-slide'));
  var prevBtn = document.getElementById('showcasePrev');
  var nextBtn = document.getElementById('showcaseNext');
  var fullscreenBtn = document.getElementById('showcaseFullscreenBtn');
  var controls = document.querySelector('.showcase-top-actions');
  var branding = document.getElementById('showcaseBranding');
  var dateTimeEl = document.getElementById('showcaseDateTime');
  var stage = document.getElementById('showcaseStage');
  var stageWrap = document.getElementById('showcaseStageWrap');

  var current = 0;
  var timer = null;
  var controlsTimer = null;
  var isInFullscreen = false;
  var clockTimer = null;
  var isAnimating = false;
  var SWIPE_MS = 420;
  var PREP_MS = 120;

  function updateDateTime() {
    if (!dateTimeEl) return;
    var now = new Date();
    var dateText = now.toLocaleDateString(undefined, {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    var timeText = now.toLocaleTimeString(undefined, {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
    dateTimeEl.textContent = dateText + ' | ' + timeText;
  }

  function setupImageBackgrounds() {
    slides.forEach(function(slide) {
      var img = slide.querySelector('.showcase-media img');
      var media = slide.querySelector('.showcase-media');
      if (!img || !media) return;

      var applyBackground = function() {
        var src = img.currentSrc || img.src;
        if (src) {
          media.style.setProperty('--artifact-bg', 'url("' + src + '")');
        }
      };

      applyBackground();
      if (!img.complete) {
        img.addEventListener('load', applyBackground, { once: true });
      }
    });
  }

  function showSlide(index) {
    if (!slides.length) return;
    var next = (index + slides.length) % slides.length;
    if (next === current || isAnimating) return;

    var prev = current;
    var prevSlide = slides[prev];
    var nextSlide = slides[next];
    if (!prevSlide || !nextSlide) return;

    var forward = next > prev;
    if (prev === slides.length - 1 && next === 0) forward = true;
    if (prev === 0 && next === slides.length - 1) forward = false;

    isAnimating = true;

    slides.forEach(function(slide) {
      slide.classList.remove('pre-change', 'swipe-in-right', 'swipe-in-left', 'swipe-out-left', 'swipe-out-right');
    });

    prevSlide.classList.add('pre-change');

    setTimeout(function() {
      nextSlide.classList.add('is-active');
      if (forward) {
        prevSlide.classList.add('swipe-out-left');
        nextSlide.classList.add('swipe-in-right');
      } else {
        prevSlide.classList.add('swipe-out-right');
        nextSlide.classList.add('swipe-in-left');
      }

      setTimeout(function() {
        prevSlide.classList.remove('is-active', 'pre-change', 'swipe-out-left', 'swipe-out-right');
        nextSlide.classList.remove('swipe-in-right', 'swipe-in-left');
        current = next;
        isAnimating = false;
      }, SWIPE_MS);
    }, PREP_MS);
  }

  function goNext() {
    showSlide(current + 1);
  }

  function goPrev() {
    showSlide(current - 1);
  }

  function stopAuto() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  function startAuto() {
    stopAuto();
    timer = setInterval(goNext, 5000);
  }

  function syncFullscreenButton() {
    if (!fullscreenBtn || !stageWrap) return;
    isInFullscreen = document.fullscreenElement === stageWrap;
    fullscreenBtn.setAttribute('aria-label', isInFullscreen ? 'Exit Fullscreen' : 'Fullscreen');
    fullscreenBtn.setAttribute('title', isInFullscreen ? 'Exit Fullscreen' : 'Fullscreen');
    fullscreenBtn.dataset.iconName = 'full_screen';
  }

  function showControls() {
    if (!controls) return;
    controls.classList.add('is-visible');
    if (prevBtn) prevBtn.classList.add('is-visible');
    if (nextBtn) nextBtn.classList.add('is-visible');
    stage.classList.add('controls-visible');
    if (controlsTimer) clearTimeout(controlsTimer);
    controlsTimer = setTimeout(function() {
      if (!controls) return;
      controls.classList.remove('is-visible');
      if (prevBtn) prevBtn.classList.remove('is-visible');
      if (nextBtn) nextBtn.classList.remove('is-visible');
      stage.classList.remove('controls-visible');
    }, 2200);
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function() {
      goPrev();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      goNext();
    });
  }

  if (stage) {
    stage.addEventListener('mouseenter', function() {
      showControls();
    });
    stage.addEventListener('mouseleave', function() {
      if (!isInFullscreen && controls) {
        controls.classList.remove('is-visible');
      }
    });
  }

  if (stageWrap) {
    stageWrap.addEventListener('mousemove', function() {
      showControls();
    });
    stageWrap.addEventListener('mouseenter', function() {
      showControls();
    });
  }

  document.addEventListener('keydown', function() {
    showControls();
  });

  document.addEventListener('fullscreenchange', function() {
    syncFullscreenButton();
    if (isInFullscreen) {
      showControls();
    }
  });

  if (slides.length > 1) {
    startAuto();
  }
  setupImageBackgrounds();
  updateDateTime();
  clockTimer = setInterval(updateDateTime, 1000);
  syncFullscreenButton();

  if (isDisplayMode) {
    setTimeout(function() {
      enterShowcaseFullscreen();
    }, 180);
  }

  window.addEventListener('beforeunload', function() {
    stopAuto();
    if (clockTimer) {
      clearInterval(clockTimer);
      clockTimer = null;
    }
  });

  window.openShowcaseOnSecondScreen = function() {
    var url = 'showcase.php?display=1';
    var width = window.screen && window.screen.availWidth ? window.screen.availWidth : 1280;
    var height = window.screen && window.screen.availHeight ? window.screen.availHeight : 720;
    var left = width;

    var features = [
      'noopener=yes',
      'noreferrer=yes',
      'popup=yes',
      'width=' + width,
      'height=' + height,
      'left=' + left,
      'top=0'
    ].join(',');

    var win = window.open(url, 'showcaseDisplayWindow', features);
    if (win) {
      win.focus();
    } else {
      window.open(url, '_blank');
    }
  };
})();

function enterShowcaseFullscreen() {
  var target = document.getElementById('showcaseStageWrap');
  if (!target) return;
  if (document.fullscreenElement === target) {
    if (document.exitFullscreen) document.exitFullscreen();
    return;
  }
  if (target.requestFullscreen) target.requestFullscreen();
}
</script>

<?php require_once 'admin_footer.php'; ?>
