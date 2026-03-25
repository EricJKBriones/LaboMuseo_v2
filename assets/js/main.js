/* ============================================================
   Museo de Labo — Main JavaScript
============================================================ */

/* ── HAMBURGER MENU ─────────────────────────────────────────── */
function toggleMenu() {
  document.getElementById('navLinks').classList.toggle('open');
  document.getElementById('hamburgerBtn').classList.toggle('open');
}

/* ── NEWS TABS ───────────────────────────────────────────────── */

// News page tabs
function switchNewsTab(tab, btn) {
  // Hide all panels
  document.querySelectorAll('#newsTabPanels .tab-panel').forEach(function(p) {
    p.style.display = 'none';
  });
  // Show selected
  var target = document.getElementById('tab-' + tab);
  if (target) target.style.display = 'block';
  // Update buttons
  document.querySelectorAll('#newsTabBtns .nfbtn').forEach(function(b) {
    b.classList.remove('active');
  });
  if (btn) btn.classList.add('active');
}

// Home page news tabs
function switchHomeTab(tab, btn) {
  // Hide all panels
  document.querySelectorAll('#homeNewsTabPanels .tab-panel').forEach(function(p) {
    p.style.display = 'none';
  });
  // Show selected
  var target = document.getElementById('tab-' + tab);
  if (target) target.style.display = 'block';
  // Update buttons
  document.querySelectorAll('#homeNewsTabBtns .ntab').forEach(function(b) {
    b.classList.remove('active');
  });
  if (btn) btn.classList.add('active');
}

// Legacy aliases (keep for backward compat)
function setNewsTab(tab, btn)     { switchNewsTab(tab, btn); }
function setHomeNewsTab(tab, btn) { switchHomeTab(tab, btn); }
function setTab(tab, btn)         { switchNewsTab(tab, btn); }

/* ── NEWS TICKER ────────────────────────────────────────────── */
var newsIdx = 0, newsTimer = null;

function showTick(idx) {
  var slides = document.querySelectorAll('.t-slide');
  var dots   = document.querySelectorAll('.t-dot');
  if (!slides.length) return;
  newsIdx = (idx + slides.length) % slides.length;
  slides.forEach(function(s, i) { s.classList.toggle('active', i === newsIdx); });
  dots.forEach(function(d, i)   { d.classList.toggle('active', i === newsIdx); });
}

(function initTicker() {
  var slides = document.querySelectorAll('.t-slide');
  if (slides.length > 1) {
    newsTimer = setInterval(function() {
      showTick((newsIdx + 1) % slides.length);
    }, 5000);
  }
})();

/* ── GALLERY SLIDER ─────────────────────────────────────────── */
var sliderIdx = 0;

function moveSlider(dir) {
  var track = document.getElementById('galleryTrack');
  if (!track) return;
  var cards = track.querySelectorAll('.g-card');
  if (!cards.length) return;
  var vis = window.innerWidth <= 480 ? 1 : window.innerWidth <= 768 ? 1 : window.innerWidth <= 992 ? 2 : 4;
  var max = Math.max(0, cards.length - vis);
  sliderIdx = Math.max(0, Math.min(sliderIdx + dir, max));
  var w = cards[0].getBoundingClientRect().width;
  track.style.transform = 'translateX(-' + (sliderIdx * (w + 20)) + 'px)';
}

window.addEventListener('resize', function() {
  sliderIdx = 0;
  var t = document.getElementById('galleryTrack');
  if (t) t.style.transform = 'translateX(0)';
});

/* ── CALENDAR ───────────────────────────────────────────────── */
var calYear, calMonth, calSelectedDate = null;

var CAL_MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

function initCalendar() {
  var now  = new Date();
  calYear  = now.getFullYear();
  calMonth = now.getMonth();
  renderCalendar();
}

function buildEventMap() {
  var map = {};
  (window.calendarEvents || []).forEach(function(ev) {
    if (ev.event_date) {
      map[ev.event_date] = map[ev.event_date] || [];
      map[ev.event_date].push(ev);
    }
  });
  return map;
}

function renderCalendar() {
  var wrap = document.getElementById('calGrid');
  if (!wrap) return;

  document.getElementById('calMonthLabel').textContent = CAL_MONTHS[calMonth] + ' ' + calYear;

  var today       = new Date();
  var firstDay    = new Date(calYear, calMonth, 1).getDay();
  var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
  var daysInPrev  = new Date(calYear, calMonth, 0).getDate();
  var eventMap    = buildEventMap();
  var totalCells  = Math.ceil((firstDay + daysInMonth) / 7) * 7;
  var html        = '';

  for (var i = 0; i < totalCells; i++) {
    var day = 0, cls = 'cal-day', dateStr = '', extra = '';

    if (i < firstDay) {
      day = daysInPrev - firstDay + i + 1;
      cls += ' other-month';
    } else if (i >= firstDay + daysInMonth) {
      day = i - firstDay - daysInMonth + 1;
      cls += ' other-month';
    } else {
      day = i - firstDay + 1;
      var mm  = (calMonth + 1 < 10 ? '0' : '') + (calMonth + 1);
      var dd  = (day < 10 ? '0' : '') + day;
      dateStr = calYear + '-' + mm + '-' + dd;

      var isToday = today.getFullYear() === calYear
                 && today.getMonth()    === calMonth
                 && today.getDate()     === day;

      if (isToday)              cls += ' today';
      if (eventMap[dateStr])    cls += ' has-event';
      if (dateStr === calSelectedDate) cls += ' selected';

      if (eventMap[dateStr]) {
        var titles = eventMap[dateStr].map(function(e){ return e.title; }).join(', ');
        extra = ' data-date="' + dateStr + '" title="' + escHTML(titles) + '"';
      }
      // ALL current-month days are clickable
      extra += ' onclick="selectCalDay(\'' + dateStr + '\')" style="cursor:pointer"';
    }

    html += '<div class="' + cls + '"' + extra + '>' + day + '</div>';
  }

  wrap.innerHTML = html;
}

function calNav(dir) {
  calMonth += dir;
  if (calMonth < 0)  { calMonth = 11; calYear--; }
  if (calMonth > 11) { calMonth = 0;  calYear++; }
  renderCalendar();
}

function selectCalDay(dateStr) {
  var panel    = document.getElementById('calEventsList');
  var panelHdr = document.getElementById('calEventsTitle');
  if (!panel || !panelHdr) return;

  // Update selected date and re-render to show gold highlight
  calSelectedDate = dateStr;
  renderCalendar();

  // Format header date
  var parts = dateStr.split('-');
  var d     = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  var dateLabel = d.toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric', year:'numeric' });

  var eventMap  = buildEventMap();
  var dayEvents = eventMap[dateStr] || [];
  var now       = new Date(); now.setHours(0,0,0,0);

  if (!dayEvents.length) {
    // No events — just update header, clear the list silently
    panelHdr.textContent = dateLabel;
    panel.innerHTML = '<p style="color:var(--text3);font-size:.88rem;padding:4px 0;font-style:italic">No events on this date.</p>';
    return;
  }

  // Has events — show them
  panelHdr.textContent = dateLabel;
  panel.innerHTML = dayEvents.map(function(ev) {
    var evDate = new Date(ev.event_date + 'T00:00:00');
    var isPast = evDate < now;
    var url    = 'index.php?page=news_detail&id=' + ev.id;
    return '<a href="' + url + '" class="cal-event-item' + (isPast ? ' past' : '') + '" style="text-decoration:none;display:block">'
      + '<div class="cal-event-date' + (isPast ? ' past' : '') + '">'
      + (isPast ? '&#128193; Past Event' : '&#128197; Upcoming Event')
      + '</div>'
      + '<div class="cal-event-title">' + escHTML(ev.title) + '</div>'
      + '<div style="font-size:.75rem;color:var(--text3);margin-top:4px">Click to view details &rarr;</div>'
      + '</a>';
  }).join('');
}

/* ── TEASER ARTIFACT CLICK ──────────────────────────────────── */
function teaserClick(isLoggedIn, loginUrl) {
  if (isLoggedIn) {
    // already handled by link
    return;
  }
  // Show inline prompt or redirect
  if (confirm('You need to sign the Digital Guestbook to view artifact details.\n\nWould you like to sign now?')) {
    window.location.href = loginUrl;
  }
}

/* ── HOME: LATEST ACQUISITIONS SCROLLER ───────────────────── */
var teaserScrollEl = null;
var teaserStepPx = 0;

function initTeaserFader() {
  teaserScrollEl = document.getElementById('teaserScrollport');
  if (!teaserScrollEl) return;

  setTeaserStep();
  updateTeaserFaderMeta();

  var prevBtn = document.getElementById('teaserPrevBtn');
  if (prevBtn && prevBtn.dataset.bound !== '1') {
    prevBtn.dataset.bound = '1';
    prevBtn.addEventListener('click', function() {
      showPrevTeaserSlide();
    });
  }

  var nextBtn = document.getElementById('teaserNextBtn');
  if (nextBtn && nextBtn.dataset.bound !== '1') {
    nextBtn.dataset.bound = '1';
    nextBtn.addEventListener('click', function() {
      showNextTeaserSlide();
    });
  }

  teaserScrollEl.addEventListener('scroll', updateTeaserFaderMeta, { passive: true });
  window.addEventListener('resize', function() {
    setTeaserStep();
    updateTeaserFaderMeta();
  });
}

function setTeaserStep() {
  if (!teaserScrollEl) return;
  var firstCard = teaserScrollEl.querySelector('.teaser-card');
  if (!firstCard) {
    teaserStepPx = 0;
    return;
  }

  var styles = window.getComputedStyle(teaserScrollEl);
  var gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
  teaserStepPx = firstCard.getBoundingClientRect().width + gap;
}

function showNextTeaserSlide() {
  if (!teaserScrollEl || !teaserStepPx) return;
  teaserScrollEl.scrollBy({ left: teaserStepPx, behavior: 'smooth' });
}

function showPrevTeaserSlide() {
  if (!teaserScrollEl || !teaserStepPx) return;
  teaserScrollEl.scrollBy({ left: -teaserStepPx, behavior: 'smooth' });
}

function updateTeaserFaderMeta() {
  if (!teaserScrollEl) return;

  var stat = document.getElementById('teaserPageStat');
  var prevBtn = document.getElementById('teaserPrevBtn');
  var nextBtn = document.getElementById('teaserNextBtn');

  var cards = teaserScrollEl.querySelectorAll('.teaser-card');
  if (!cards.length) return;

  var firstCard = cards[0];
  var styles = window.getComputedStyle(teaserScrollEl);
  var gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
  var step = teaserStepPx || (firstCard.getBoundingClientRect().width + gap);
  if (!step) return;

  var maxScroll = Math.max(0, teaserScrollEl.scrollWidth - teaserScrollEl.clientWidth);
  var visibleCount = Math.max(1, Math.floor((teaserScrollEl.clientWidth + gap) / step));
  var total = Math.max(1, cards.length - visibleCount + 1);
  var current = Math.min(total, Math.max(1, Math.round(teaserScrollEl.scrollLeft / step) + 1));

  if (stat) {
    stat.textContent = current + ' / ' + total;
  }
  if (prevBtn) {
    prevBtn.disabled = teaserScrollEl.scrollLeft <= 4;
  }
  if (nextBtn) {
    nextBtn.disabled = teaserScrollEl.scrollLeft >= maxScroll - 4;
  }
}

/* ── SCROLL FLOAT REVEAL ───────────────────────────────────── */
function initScrollFloatReveal() {
  var selectors = [
    '.acard',
    '.hcard',
    '.nc',
    '.ex-card',
    '.cat-card',
    '.teaser-card',
    '.calendar-wrap',
    '.cal-events-panel',
    '.tbl-wrap',
    '.adm-welcome',
    '.adm-stats .astat',
    '.adm-qgrid .adm-qcard'
  ];

  var nodes = document.querySelectorAll(selectors.join(','));
  if (!nodes.length) return;

  var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

  nodes.forEach(function(el) {
    el.classList.add('float-reveal');
  });

  if (!('IntersectionObserver' in window)) {
    nodes.forEach(function(el) { el.classList.add('is-visible'); });
    return;
  }

  var observer = new IntersectionObserver(function(entries, obs) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        obs.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.12,
    rootMargin: '0px 0px -6% 0px'
  });

  nodes.forEach(function(el, idx) {
    el.style.transitionDelay = ((idx % 6) * 45) + 'ms';
    observer.observe(el);
  });
}

/* ── DETAIL IMAGE LIGHTBOX ─────────────────────────────────── */
function openDetailImage(imgEl) {
  if (!imgEl) return;
  var lightbox = document.getElementById('detailLightbox');
  var fullImg = document.getElementById('detailLightboxImg');
  if (!lightbox || !fullImg) return;

  fullImg.src = imgEl.getAttribute('data-full-src') || imgEl.src;
  fullImg.alt = imgEl.getAttribute('data-full-alt') || imgEl.alt || 'Artifact image';
  lightbox.classList.add('is-open');
  lightbox.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeDetailImage(event) {
  var lightbox = document.getElementById('detailLightbox');
  var fullImg = document.getElementById('detailLightboxImg');
  if (!lightbox || !fullImg) return;

  if (event && event.currentTarget === lightbox && event.target !== lightbox) return;
  if (event) event.preventDefault();

  lightbox.classList.remove('is-open');
  lightbox.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';

  setTimeout(function() {
    if (!lightbox.classList.contains('is-open')) {
      fullImg.src = '';
      fullImg.alt = '';
    }
  }, 280);
}

/* ── GUEST REGISTRATION FORM ────────────────────────────────── */
function toggleGroupFields() {
  var type = document.getElementById('visitorType');
  var gf   = document.getElementById('groupFields');
  var nl   = document.getElementById('nameLbl');
  if (!type || !gf) return;
  if (type.value === 'Group') {
    gf.style.display = 'block';
    if (nl) nl.textContent = "Representative's Full Name";
  } else {
    gf.style.display = 'none';
    if (nl) nl.textContent = 'Full Name';
  }
}

/* ── ADMIN: TOGGLE FORM PANEL ───────────────────────────────── */
function togglePanel(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('is-open');
  
  // Find and toggle the button that controls this panel
  var buttons = document.querySelectorAll('[onclick="togglePanel(\'' + id + '\')"]');
  buttons.forEach(function(btn) {
    btn.classList.toggle('active');
  });
}

// Initialize buttons to match form states on page load
document.addEventListener('DOMContentLoaded', function() {
  // First, ensure all forms without is-open class are properly hidden
  var forms = document.querySelectorAll('.adm-form[id]');
  forms.forEach(function(form) {
    var formId = form.getAttribute('id');
    var hasOpenClass = form.classList.contains('is-open');
    
    if (hasOpenClass) {
      // This form should be open - add active state to its button
      var buttons = document.querySelectorAll('[onclick="togglePanel(\'' + formId + '\')"]');
      buttons.forEach(function(btn) {
        btn.classList.add('active');
      });
    } else {
      // This form should be closed - explicitly remove is-open just in case
      form.classList.remove('is-open');
      // Remove active state from buttons
      var buttons = document.querySelectorAll('[onclick="togglePanel(\'' + formId + '\')"]');
      buttons.forEach(function(btn) {
        btn.classList.remove('active');
      });
    }
  });
});

/* ── ADMIN: MONTH FILTER ────────────────────────────────────── */
function applyMonthFilter() {
  var mf  = document.getElementById('monthFilter');
  var url = new URL(window.location.href);
  if (mf && mf.value) {
    url.searchParams.set('month', mf.value);
  } else {
    url.searchParams.delete('month');
  }
  window.location.href = url.toString();
}

function clearMonthFilter() {
  var url = new URL(window.location.href);
  url.searchParams.delete('month');
  window.location.href = url.toString();
}

/* ── ADMIN: DEBOUNCED FORM SUBMIT ──────────────────────────── */
var adminDebounceSubmitTimers = {};

function adminDebounceSubmit(form, delay) {
  if (!form) return;
  var formId = form.getAttribute('id') || 'form_' + Date.now();
  var timerKey = 'admin_' + formId;
  
  if (adminDebounceSubmitTimers[timerKey]) {
    clearTimeout(adminDebounceSubmitTimers[timerKey]);
  }
  
  adminDebounceSubmitTimers[timerKey] = setTimeout(function() {
    form.submit();
  }, delay || 700);
}

/* ── ADMIN: AUTO SUBMIT FILTERS (debounced) ────────────────── */
function initAutoSubmitFilters() {
  var forms = document.querySelectorAll('form[data-auto-submit="1"]');
  forms.forEach(function(form) {
    if (form.dataset.autoBound === '1') return;
    form.dataset.autoBound = '1';

    var delay = parseInt(form.getAttribute('data-debounce') || '350', 10);
    var timer = null;
    var loadingEl = form.querySelector('.mbar-loading');
    if (!loadingEl) {
      loadingEl = document.createElement('div');
      loadingEl.className = 'mbar-loading';
      loadingEl.setAttribute('aria-live', 'polite');
      loadingEl.innerHTML = '<span class="mbar-dot"></span>Loading...';
      form.appendChild(loadingEl);
    }

    function submitWithLoading() {
      form.classList.add('is-loading');

      // For GET filters, build URL explicitly so empty fields are dropped reliably.
      var method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method === 'GET') {
        var action = form.getAttribute('action') || window.location.pathname;
        var url = new URL(action, window.location.href);
        var formData = new FormData(form);
        formData.forEach(function(value, key) {
          var val = String(value == null ? '' : value).trim();
          if (val !== '') {
            url.searchParams.append(key, val);
          }
        });
        window.location.href = url.toString();
        return;
      }

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        var ev = new Event('submit', { cancelable: true });
        if (form.dispatchEvent(ev)) {
          HTMLFormElement.prototype.submit.call(form);
        }
      }
    }

    function submitGetFormNow() {
      form.classList.add('is-loading');
      var action = form.getAttribute('action') || window.location.pathname;
      var url = new URL(action, window.location.href);
      var formData = new FormData(form);
      formData.forEach(function(value, key) {
        var val = String(value == null ? '' : value).trim();
        if (val !== '') {
          url.searchParams.append(key, val);
        }
      });
      window.location.href = url.toString();
    }

    // Ensure plain form.submit()/submit button/inline onchange submit all use the same GET fetch path.
    form.addEventListener('submit', function(e) {
      var method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method === 'GET') {
        e.preventDefault();
        submitGetFormNow();
      }
    });

    form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function(input) {
      function queueSubmit() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function() {
          submitWithLoading();
        }, delay);
      }

      // input: typing/backspace/delete (primary live-search trigger)
      input.addEventListener('input', queueSubmit);
      // search: clear button (x) on type=search in some browsers
      input.addEventListener('search', queueSubmit);
    });

    form.querySelectorAll('input[type="month"], input[type="date"]').forEach(function(input) {
      input.addEventListener('change', function() {
        submitWithLoading();
      });
    });

    form.querySelectorAll('select').forEach(function(sel) {
      sel.addEventListener('change', function() {
        submitWithLoading();
      });
    });
  });
}

/* ── LIVE SEARCH (categories/exhibits) ─────────────────────── */
function liveSearch(inputId, cardClass) {
  var q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll('.' + cardClass).forEach(function(card) {
    var text = card.textContent.toLowerCase();
    card.style.display = text.includes(q) ? '' : 'none';
  });
}

/* ── UTILS ──────────────────────────────────────────────────── */
function escHTML(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

/* ── EXPORT CSV (admin) ─────────────────────────────────────── */
function exportCSV() {
  window.location.href = 'admin/export.php';
}

/* ── SUBMIT BUTTON CLICK ANIMATION ─────────────────────────── */
var submitAnimDelayMs = 420;
var submitAnimTickMark = '<svg width="38" height="30" viewBox="0 0 58 45" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="nonzero" d="M19.11 44.64L.27 25.81l5.66-5.66 13.18 13.18L52.07.38l5.65 5.65"/></svg>';

function initSubmitButtonAnimations() {
  var buttons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
  if (!buttons.length) return;

  buttons.forEach(function(btn) {
    if (btn.dataset.submitAnimReady === '1' || btn.disabled) return;
    if (btn.getAttribute('data-no-submit-anim') === '1') return;

    btn.dataset.submitAnimReady = '1';
    btn.classList.add('submit-animate-enabled');

    var tag = btn.tagName.toLowerCase();
    if (tag === 'button') {
      var current = btn.innerHTML;
      btn.innerHTML = '<span class="submit-label">' + current + '</span><span class="submit-checkmark">' + submitAnimTickMark + '</span>';
    } else {
      btn.classList.add('submit-animate-input');
      if (!btn.dataset.submitOriginalValue) {
        btn.dataset.submitOriginalValue = btn.value;
      }
    }

    if (btn.form && btn.form.dataset.submitAnimBound !== '1') {
      bindSubmitAnimationOnForm(btn.form);
    }

    btn.addEventListener('click', function() {
      if (btn.disabled) return;

      var form = btn.form;
      if (form && typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
      }

      btn.classList.add('is-submit-animating');
      btn.dataset.submitAnimating = '1';

      if (btn.tagName.toLowerCase() === 'input') {
        btn.value = '\u00A0';
      }

      // If submit does not navigate away, restore visual state.
      setTimeout(function() {
        if (!document.body.contains(btn)) return;
        btn.classList.remove('is-submit-animating');
        btn.dataset.submitAnimating = '0';
        if (btn.tagName.toLowerCase() === 'input') {
          btn.value = btn.dataset.submitOriginalValue || btn.value;
        }
      }, 1200);
    });
  });
}

function bindSubmitAnimationOnForm(form) {
  if (!form || form.dataset.submitAnimBound === '1') return;
  form.dataset.submitAnimBound = '1';

  form.addEventListener('submit', function(e) {
    if (form.dataset.submitAnimForward === '1') {
      form.dataset.submitAnimForward = '0';
      return;
    }

    var active = document.activeElement;
    if (!active || !active.matches('button[type="submit"], input[type="submit"]')) {
      return;
    }

    if (active.dataset.submitAnimating !== '1') {
      return;
    }

    e.preventDefault();
    form.dataset.submitAnimForward = '1';

    setTimeout(function() {
      if (!document.body.contains(form)) return;

      if (typeof form.requestSubmit === 'function' && active.tagName.toLowerCase() === 'button') {
        form.requestSubmit(active);
      } else {
        HTMLFormElement.prototype.submit.call(form);
      }
    }, submitAnimDelayMs);
  });
}

/* ── INIT ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  initCalendar();
  initAutoSubmitFilters();
  initTeaserFader();
  initScrollFloatReveal();
  initSubmitButtonAnimations();

  // Hide all non-active tab panels on load
  document.querySelectorAll('.tab-panel').forEach(function(p, i) {
    // Keep first one visible if inside homeNewsTabPanels or newsTabPanels
    var parent = p.parentElement;
    var panels = parent ? parent.querySelectorAll('.tab-panel') : [];
    if (panels.length && p !== panels[0]) {
      p.style.display = 'none';
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      var lightbox = document.getElementById('detailLightbox');
      if (lightbox && lightbox.classList.contains('is-open')) {
        closeDetailImage();
      }
    }
  });
});
