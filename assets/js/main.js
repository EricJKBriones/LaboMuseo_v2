/* ============================================================
   Museo de Labo — Main JavaScript
============================================================ */

/* ── HAMBURGER MENU ─────────────────────────────────────────── */
var mobileMenuCloseTimer = null;

function toggleMenu() {
  var nav = document.getElementById('navLinks');
  var btn = document.getElementById('hamburgerBtn');
  if (!nav || !btn) return;

  if (nav.classList.contains('open')) {
    closeMobileMenu();
    return;
  }

  if (mobileMenuCloseTimer) {
    clearTimeout(mobileMenuCloseTimer);
    mobileMenuCloseTimer = null;
  }
  nav.classList.remove('is-collapsing');
  nav.classList.add('open');
  btn.classList.add('open');
}

function closeMobileMenu() {
  var nav = document.getElementById('navLinks');
  var btn = document.getElementById('hamburgerBtn');
  if (!nav || !btn) return;

  if (!nav.classList.contains('open') || nav.classList.contains('is-collapsing')) {
    btn.classList.remove('open');
    return;
  }

  nav.classList.add('is-collapsing');
  btn.classList.remove('open');

  if (mobileMenuCloseTimer) {
    clearTimeout(mobileMenuCloseTimer);
  }
  mobileMenuCloseTimer = window.setTimeout(function() {
    nav.classList.remove('open');
    nav.classList.remove('is-collapsing');
    mobileMenuCloseTimer = null;
  }, 560);
}

function initMobileMenuAutoCollapse() {
  var nav = document.getElementById('navLinks');
  var btn = document.getElementById('hamburgerBtn');
  if (!nav || !btn) return;

  document.addEventListener('click', function(e) {
    if (window.innerWidth > 768) return;
    if (!nav.classList.contains('open')) return;
    if (e.target.closest('#hamburgerBtn')) return;
    if (e.target.closest('#navLinks')) return;
    closeMobileMenu();
  });

  window.addEventListener('scroll', function() {
    if (window.innerWidth > 768) return;
    if (!nav.classList.contains('open')) return;
    closeMobileMenu();
  }, { passive: true });
}

/* ── SILEO TOAST BAR ───────────────────────────────────────── */
var sileoToastHost = null;
var sileoToastDefaults = {
  position: 'top-right'
};
var sileoToastMaxVisible = 4;
var GUESTBOOK_TOAST_TITLE = 'Guestbook Required';

function normalizeToastSpec(input, fallbackVariant) {
  if (typeof input === 'string') {
    return {
      message: input,
      title: '',
      variant: fallbackVariant || 'info',
      actions: []
    };
  }

  var spec = input || {};
  var actions = [];
  if (Array.isArray(spec.actions)) {
    actions = spec.actions.slice();
  } else if (spec.action) {
    actions = [spec.action];
  }

  return {
    message: spec.message || spec.description || spec.text || '',
    title: spec.title || '',
    variant: spec.variant || spec.type || fallbackVariant || 'info',
    position: spec.position || sileoToastDefaults.position,
    duration: typeof spec.duration === 'number' ? spec.duration : undefined,
    actions: actions,
    persistent: Boolean(spec.persistent),
    icon: spec.icon || '',
    loading: Boolean(spec.loading)
  };
}

function setSileoToastPosition(position) {
  var host = getSileoToastHost();
  var allowed = {
    'top-left': true,
    'top-center': true,
    'top-right': true,
    'bottom-left': true,
    'bottom-center': true,
    'bottom-right': true
  };
  var resolved = allowed[position] ? position : sileoToastDefaults.position;

  host.dataset.position = resolved;
  host.className = 'sileo-toast-host sileo-toast-host--' + resolved;
  return host;
}

function getSileoToastHost() {
  if (sileoToastHost && document.body.contains(sileoToastHost)) {
    return sileoToastHost;
  }

  sileoToastHost = document.getElementById('sileoToastHost');
  if (!sileoToastHost) {
    sileoToastHost = document.createElement('div');
    sileoToastHost.id = 'sileoToastHost';
    sileoToastHost.setAttribute('aria-live', 'polite');
    sileoToastHost.setAttribute('aria-atomic', 'true');
    document.body.appendChild(sileoToastHost);
  }

  setSileoToastPosition(sileoToastHost.dataset.position || sileoToastDefaults.position);

  return sileoToastHost;
}

function dismissSileoToast(toast) {
  if (!toast || toast.dataset.leaving === '1') return;
  toast.dataset.leaving = '1';
  toast.classList.add('is-leaving');

  window.setTimeout(function() {
    if (toast && toast.parentNode) {
      toast.parentNode.removeChild(toast);
    }
  }, 240);
}

function dismissSileoToastNow(toast) {
  if (!toast || !toast.parentNode) return;
  toast.dataset.leaving = '1';
  toast.parentNode.removeChild(toast);
}

function enforceSileoToastLimit(host) {
  if (!host) return;

  var activeToasts = host.querySelectorAll('.sileo-toastbar:not([data-leaving="1"])');
  while (activeToasts.length >= sileoToastMaxVisible) {
    dismissSileoToastNow(activeToasts[0]);
    activeToasts = host.querySelectorAll('.sileo-toastbar:not([data-leaving="1"])');
  }
}

function showSileoToastBar(input, variant, timeout) {
  if (!document.body) return null;

  var spec = normalizeToastSpec(input, variant);
  if (!spec.message && !spec.title) return null;

  var host = getSileoToastHost();
  var toast = document.createElement('div');
  var level = spec.variant || 'info';
  var title = spec.title || (level === 'error' ? 'Error' : level === 'success' ? 'Success' : level === 'warning' ? 'Warning' : level === 'loading' ? 'Loading' : 'Notice');
  var icon = spec.icon || (level === 'error' ? '!' : level === 'success' ? '✓' : level === 'warning' ? '!' : level === 'loading' ? '⋯' : 'i');
  var duration = typeof timeout === 'number' ? timeout : (typeof spec.duration === 'number' ? spec.duration : (level === 'loading' || spec.persistent ? 0 : 2400));

  toast.className = 'sileo-toastbar sileo-toastbar--' + level;
  toast.setAttribute('role', level === 'error' ? 'alert' : 'status');
  toast.dataset.variant = level;
  if (spec.position) {
    setSileoToastPosition(spec.position);
  }

  var iconEl = document.createElement('span');
  iconEl.className = 'sileo-toastbar__icon';
  iconEl.setAttribute('aria-hidden', 'true');
  iconEl.textContent = icon;

  var bodyEl = document.createElement('div');
  bodyEl.className = 'sileo-toastbar__body';

  var titleEl = document.createElement('span');
  titleEl.className = 'sileo-toastbar__title';
  titleEl.textContent = title;

  var msgEl = document.createElement('div');
  msgEl.className = 'sileo-toastbar__msg';
  msgEl.textContent = String(spec.message).replace(/\s+/g, ' ').trim();
  if (!msgEl.textContent) {
    msgEl.style.display = 'none';
  }

  bodyEl.appendChild(titleEl);
  bodyEl.appendChild(msgEl);

  var actionBtns = [];
  if (spec.actions && spec.actions.length) {
    var actionsWrap = document.createElement('div');
    actionsWrap.className = 'sileo-toastbar__actions';

    spec.actions.forEach(function(actionSpec) {
      if (!actionSpec || !actionSpec.label) return;
      var actionBtn = document.createElement('button');
      actionBtn.type = 'button';
      actionBtn.className = 'sileo-toastbar__action';
      actionBtn.textContent = actionSpec.label;
      actionsWrap.appendChild(actionBtn);
      actionBtns.push({ button: actionBtn, spec: actionSpec });
    });

    if (actionsWrap.childNodes.length) {
      bodyEl.appendChild(actionsWrap);
    }
  }

  var closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'sileo-toastbar__close';
  closeBtn.setAttribute('aria-label', 'Dismiss notification');
  closeBtn.textContent = '×';

  toast.appendChild(iconEl);
  toast.appendChild(bodyEl);
  toast.appendChild(closeBtn);

  var timerBar = null;
  if (duration > 0) {
    timerBar = document.createElement('div');
    timerBar.className = 'sileo-toastbar__timer';
    timerBar.style.setProperty('--toast-duration', duration + 'ms');
    toast.appendChild(timerBar);
  }

  enforceSileoToastLimit(host);
  host.appendChild(toast);

  var timer = null;
  if (duration > 0) {
    timer = window.setTimeout(function() {
      dismissSileoToast(toast);
    }, duration);
  }

  closeBtn.addEventListener('click', function() {
    if (timer) window.clearTimeout(timer);
    dismissSileoToast(toast);
  });

  actionBtns.forEach(function(entry) {
    entry.button.addEventListener('click', function() {
      if (entry.spec && typeof entry.spec.onClick === 'function') {
        entry.spec.onClick(toast);
      }
      if (entry.spec && entry.spec.href) {
        window.location.href = entry.spec.href;
      }
      if (!entry.spec || entry.spec.dismiss !== false) {
        if (timer) window.clearTimeout(timer);
        dismissSileoToast(toast);
      }
    });
  });

  toast.addEventListener('mouseenter', function() {
    if (timer) window.clearTimeout(timer);
  });

  toast.addEventListener('mouseleave', function() {
    if (toast.dataset.leaving === '1') return;
    if (!duration) return;
    timer = window.setTimeout(function() {
      dismissSileoToast(toast);
    }, 350);
  });

  toast.dismiss = function() {
    if (timer) window.clearTimeout(timer);
    dismissSileoToast(toast);
  };

  toast.update = function(nextInput) {
    var nextSpec = normalizeToastSpec(nextInput, level);
    if (nextSpec.position) {
      setSileoToastPosition(nextSpec.position);
    }
    if (nextSpec.variant) {
      toast.className = 'sileo-toastbar sileo-toastbar--' + nextSpec.variant;
      toast.dataset.variant = nextSpec.variant;
    }
    if (nextSpec.title) {
      titleEl.textContent = nextSpec.title;
    }
    if (typeof nextSpec.message === 'string') {
      msgEl.textContent = nextSpec.message.replace(/\s+/g, ' ').trim();
      msgEl.style.display = msgEl.textContent ? '' : 'none';
    }
  };

  return toast;
}

function showSileoActionToast(payload) {
  var spec = normalizeToastSpec(payload, 'info');
  if (typeof spec.duration !== 'number') {
    spec.duration = 2400;
  }
  return showSileoToastBar(spec, 'info');
}

function promptGuestbookAccess(sectionLabel) {
  if (typeof window.showSileoToastBar === 'function') {
    window.showSileoToastBar({
      title: GUESTBOOK_TOAST_TITLE,
      message: 'You need to sign the Digital Guestbook to unlock latest acquisitions.',
      variant: 'warning',
      duration: 2800,
      position: 'top-right',
      actions: [
        { label: 'Sign Guestbook', href: 'index.php?page=login' },
        { label: 'Not Now', dismiss: true }
      ]
    }, 'warning');
  } else {
    window.alert('You need to sign the Digital Guestbook to unlock latest acquisitions.');
  }
  return false;
}

function showSileoPromiseToast(promiseOrFactory, payload) {
  var spec = normalizeToastSpec(payload, 'loading');
  spec.variant = 'loading';
  spec.persistent = true;
  spec.title = spec.title || 'Loading';
  var toast = showSileoToastBar(spec, 'loading');
  var promise = typeof promiseOrFactory === 'function' ? promiseOrFactory() : promiseOrFactory;

  return Promise.resolve(promise).then(function(result) {
    if (toast && toast.update) {
      toast.update({
        variant: 'success',
        title: spec.successTitle || 'Success',
        message: spec.successMessage || 'Completed successfully.'
      });
      window.setTimeout(function() {
        if (toast && toast.dismiss) toast.dismiss();
      }, 1400);
    }
    return result;
  }).catch(function(error) {
    var errorMessage = spec.errorMessage || (error && error.message) || 'Something went wrong.';
    if (toast && toast.update) {
      toast.update({
        variant: 'error',
        title: spec.errorTitle || 'Error',
        message: errorMessage
      });
    }
    throw error;
  });
}

function initSileoToastBars() {
  var toastParams = new URLSearchParams(window.location.search || '');
  if (toastParams.get('no_toast') === '1') {
    return;
  }

  var alerts = document.querySelectorAll('.alert-ok, .alert-err, .msg-box.msg-err');
  if (!alerts.length) return;

  alerts.forEach(function(alertEl) {
    if (!alertEl || alertEl.dataset.toastShown === '1') return;
    var text = '';
    var actions = [];

    if (alertEl.querySelector('a, button')) {
      var clone = alertEl.cloneNode(true);
      clone.querySelectorAll('a, button').forEach(function(node) {
        node.parentNode.removeChild(node);
      });
      text = (clone.textContent || '').replace(/\s+/g, ' ').trim();

      alertEl.querySelectorAll('a').forEach(function(anchor) {
        var label = (anchor.textContent || '').replace(/\s+/g, ' ').trim();
        if (!label || !anchor.getAttribute('href')) return;
        actions.push({ label: label, href: anchor.getAttribute('href') });
      });
    } else {
      text = (alertEl.textContent || '').replace(/\s+/g, ' ').trim();
    }

    if (!text) return;

    alertEl.dataset.toastShown = '1';
    alertEl.classList.add('is-toast-hidden');

    var level = (alertEl.classList.contains('alert-err') || alertEl.classList.contains('msg-err')) ? 'error' : 'success';
    showSileoToastBar({
      message: text,
      variant: level,
      actions: actions,
      persistent: actions.length > 0
    }, level, level === 'error' ? 3600 : 3000);
  });
}

window.showSileoToastBar = showSileoToastBar;
window.showToastBar = showSileoToastBar;
window.sileo = {
  success: function(payload) { return showSileoToastBar(payload, 'success'); },
  error: function(payload) { return showSileoToastBar(payload, 'error'); },
  warning: function(payload) { return showSileoToastBar(payload, 'warning'); },
  info: function(payload) { return showSileoToastBar(payload, 'info'); },
  loading: function(payload) { return showSileoToastBar(normalizeToastSpec(payload, 'loading'), 'loading'); },
  action: function(payload) { return showSileoActionToast(payload); },
  promise: function(promiseOrFactory, payload) { return showSileoPromiseToast(promiseOrFactory, payload); },
  setPosition: function(position) { return setSileoToastPosition(position); },
  toast: function(payload) { return showSileoToastBar(payload, payload && payload.variant ? payload.variant : 'info'); }
};
window.promptGuestbookAccess = promptGuestbookAccess;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSileoToastBars);
} else {
  initSileoToastBars();
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
  if (window.sileo && typeof window.sileo.action === 'function') {
    window.sileo.action({
      title: GUESTBOOK_TOAST_TITLE,
      message: 'You need to sign the Digital Guestbook to unlock latest acquisitions.',
      variant: 'warning',
      duration: 5000,
      actions: [
        { label: 'Sign Guestbook', href: loginUrl },
        { label: 'Not Now', dismiss: true }
      ]
    });
    return;
  }

  window.location.href = loginUrl;
}

/* ── HOME: LATEST ACQUISITIONS SCROLLER ───────────────────── */
var teaserScrollEl = null;
var teaserStepPx = 0;

function initTeaserFader() {
  teaserScrollEl = document.getElementById('teaserScrollport');
  if (!teaserScrollEl) return;

  teaserScrollEl.scrollLeft = 0;
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

  var prevBtn = document.getElementById('teaserPrevBtn');
  var nextBtn = document.getElementById('teaserNextBtn');
  var stat = document.getElementById('teaserPageStat');

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
    '.adm-stats .astat'
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
function setPanelToggleButtonIcon(btn, isOpen) {
  if (!btn) return;

  var icon = btn.querySelector('img.auto-btn-icon, img.icon-swap');
  if (!icon) return;

  var baseIcon = btn.getAttribute('data-base-icon-name') || btn.getAttribute('data-icon-name') || 'add';
  if (!btn.getAttribute('data-base-icon-name')) {
    btn.setAttribute('data-base-icon-name', baseIcon);
  }

  var nextIcon = isOpen ? 'minus' : baseIcon;
  var basePath = '../assets/Icon/';
  icon.setAttribute('data-png', basePath + nextIcon + '.png');
  icon.setAttribute('data-gif', basePath + nextIcon + '.gif');
  icon.setAttribute('src', basePath + nextIcon + '.png');
}

function togglePanel(id, options) {
  options = options || {};
  var el = document.getElementById(id);
  if (!el) return;
  var isCurrentlyOpen = el.classList.contains('is-open');
  if (isCurrentlyOpen && !options.skipConfirm && typeof window.requestAdminPanelClose === 'function') {
    window.requestAdminPanelClose(el, function() {
      togglePanel(id, { skipConfirm: true });
    });
    return false;
  }
  var isOpen = el.classList.toggle('is-open');
  
  // Find and toggle the button that controls this panel
  var buttons = document.querySelectorAll('[onclick="togglePanel(\'' + id + '\')"]');
  buttons.forEach(function(btn) {
    btn.classList.toggle('active', isOpen);
    setPanelToggleButtonIcon(btn, isOpen);
  });

  if (typeof window.syncAdminQuickOverlayState === 'function') {
    window.syncAdminQuickOverlayState();
  }

  return isOpen;
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
        setPanelToggleButtonIcon(btn, true);
      });
    } else {
      // This form should be closed - explicitly remove is-open just in case
      form.classList.remove('is-open');
      // Remove active state from buttons
      var buttons = document.querySelectorAll('[onclick="togglePanel(\'' + formId + '\')"]');
      buttons.forEach(function(btn) {
        btn.classList.remove('active');
        setPanelToggleButtonIcon(btn, false);
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

function navigateAfterFullscreenExit(href) {
  if (!href) return;

  var activeFs = document.fullscreenElement;
  if (activeFs && document.exitFullscreen) {
    Promise.resolve(document.exitFullscreen()).catch(function() {
      // Ignore fullscreen exit errors and continue navigation.
    }).finally(function() {
      window.location.href = href;
    });
    return;
  }

  window.location.href = href;
}

/* ── EXPORT CSV (admin) ─────────────────────────────────────── */
function exportCSV() {
  window.location.href = 'admin/export.php';
}

/* ── ADMIN SIDEBAR COLLAPSE ───────────────────────────────── */
function applyAdminSidebarState(collapsed) {
  if (collapsed) {
    document.body.classList.add('admin-sidebar-collapsed');
  } else {
    document.body.classList.remove('admin-sidebar-collapsed');
  }
}

function closeAdminSidebarMobile(immediate) {
  if (!document.body || window.innerWidth > 900) return;
  if (!document.body.classList.contains('admin-sidebar-open')) return;

  if (immediate) {
    document.body.classList.remove('admin-sidebar-closing');
    document.body.classList.remove('admin-sidebar-open');
    syncAdminSidebarFabState();
    return;
  }

  document.body.classList.add('admin-sidebar-closing');
  document.body.classList.remove('admin-sidebar-open');
  syncAdminSidebarFabState();

  window.setTimeout(function() {
    if (!document.body) return;
    document.body.classList.remove('admin-sidebar-closing');
  }, 380);
}

function toggleAdminSidebar() {
  if (!document.body) return;

  if (window.innerWidth <= 900) {
    if (document.body.classList.contains('admin-sidebar-open')) {
      closeAdminSidebarMobile(false);
    } else {
      document.body.classList.remove('admin-sidebar-closing');
      document.body.classList.add('admin-sidebar-open');
      syncAdminSidebarFabState();
    }
    syncAdminSidebarFabState();
    return;
  }

  var collapsed = !document.body.classList.contains('admin-sidebar-collapsed');
  applyAdminSidebarState(collapsed);
  try {
    localStorage.setItem('adminSidebarCollapsed', collapsed ? '1' : '0');
  } catch (e) {
    // Ignore storage errors and keep current UI state.
  }
}

function syncAdminSidebarFabState() {
  var isOpen = document.body && document.body.classList.contains('admin-sidebar-open');
  document.querySelectorAll('.adm-mobile-fab').forEach(function(btn) {
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
}

function triggerButtonSpin(btn) {
  if (!btn) return;
  btn.classList.remove('is-spinning');
  void btn.offsetWidth;
  btn.classList.add('is-spinning');
}

function initAdminSidebarCollapse() {
  if (!document.querySelector('.adm-sidebar')) return;

  document.querySelectorAll('.adm-side-toggle, .adm-mobile-fab').forEach(function(btn) {
    if (btn.dataset.sidebarToggleBound === '1') return;
    btn.dataset.sidebarToggleBound = '1';
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      triggerButtonSpin(btn);
      toggleAdminSidebar();
    });
  });

  var collapsed = false;
  try {
    collapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
  } catch (e) {
    collapsed = false;
  }

  if (window.innerWidth <= 900) {
    closeAdminSidebarMobile(true);
    applyAdminSidebarState(false);
    syncAdminSidebarFabState();
  } else {
    applyAdminSidebarState(collapsed);
    syncAdminSidebarFabState();
  }

  window.addEventListener('resize', function() {
    if (window.innerWidth <= 900) {
      closeAdminSidebarMobile(true);
      applyAdminSidebarState(false);
      syncAdminSidebarFabState();
      return;
    }
    var keepCollapsed = false;
    try {
      keepCollapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
    } catch (e) {
      keepCollapsed = false;
    }
    applyAdminSidebarState(keepCollapsed);
    syncAdminSidebarFabState();
  });

  document.addEventListener('click', function(e) {
    if (window.innerWidth > 900) return;
    if (!document.body.classList.contains('admin-sidebar-open')) return;
    if (e.target.closest('.adm-sidebar')) return;
    if (e.target.closest('.adm-mobile-fab')) return;
    closeAdminSidebarMobile(false);
  });
}

function initAdminMenuNavAnimation() {
  if (!document.querySelector('.adm-sidebar')) return;

  document.body.classList.add('admin-page-enter');
  setTimeout(function() {
    if (document.body) document.body.classList.remove('admin-page-enter');
  }, 210);

  var links = document.querySelectorAll('.adm-sidebar .adm-menu a[href]');
  links.forEach(function(link) {
    link.addEventListener('click', function(e) {
      // Preserve default browser behavior for new tab/window or special clicks.
      if (e.defaultPrevented) return;
      if (e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      var href = link.getAttribute('href') || '';
      if (!href || href.charAt(0) === '#') return;

      e.preventDefault();
      link.classList.add('is-clicking');
      document.body.classList.add('admin-page-leaving');

      requestAnimationFrame(function() {
        setTimeout(function() {
          navigateAfterFullscreenExit(href);
        }, 110);
      });
    });
  });
}

function bindHoverSwapIcon(img) {
  if (!img || img.dataset.iconSwapBound === '1') return;

  function withIconRev(url) {
    if (!url) return url;
    if (url.indexOf('rev=') !== -1) return url;
    return url + (url.indexOf('?') === -1 ? '?' : '&') + 'rev=20260410c';
  }

  var pngSrc = withIconRev(img.getAttribute('data-png') || img.getAttribute('src') || '');
  var gifSrc = withIconRev(img.getAttribute('data-gif') || '');
  if (!pngSrc || !gifSrc) return;

  img.setAttribute('data-png', pngSrc);
  img.setAttribute('data-gif', gifSrc);

  img.dataset.iconSwapBound = '1';
  img.setAttribute('src', pngSrc);

  var hoverTarget = img.closest('a, button, label, .toggle-btn, .btn-save, .btn-cancel-f, .btn-edit, .btn-del, .btn-filt, .btn-exp, .btn-clf') || img;
  var hoverBindKey = 'iconSwapHoverBound';
  var restoreBindKey = 'iconSwapRestoreBound';

  function showGif() {
    img.setAttribute('src', gifSrc);
  }

  function showPng() {
    img.setAttribute('src', pngSrc);
  }

  if (hoverTarget === img) {
    img.addEventListener('mouseenter', showGif);
    img.addEventListener('mouseleave', showPng);
    img.addEventListener('pointerenter', showGif);
    img.addEventListener('pointerleave', showPng);
    img.addEventListener('focus', showGif);
    img.addEventListener('blur', showPng);
    return;
  }

  if (hoverTarget.dataset[hoverBindKey] !== '1') {
    hoverTarget.dataset[hoverBindKey] = '1';
    hoverTarget.addEventListener('mouseenter', function() {
      hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
        var iconGif = icon.getAttribute('data-gif');
        if (iconGif) icon.setAttribute('src', iconGif);
        icon.style.transform = 'scale(1.2)';
      });
    });
    hoverTarget.addEventListener('pointerenter', function() {
      hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
        var iconGif = icon.getAttribute('data-gif');
        if (iconGif) icon.setAttribute('src', iconGif);
        icon.style.transform = 'scale(1.2)';
      });
    });
    hoverTarget.addEventListener('focusin', function() {
      hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
        var iconGif = icon.getAttribute('data-gif');
        if (iconGif) icon.setAttribute('src', iconGif);
        icon.style.transform = 'scale(1.2)';
      });
    });
  }

  if (hoverTarget.dataset[restoreBindKey] !== '1') {
    hoverTarget.dataset[restoreBindKey] = '1';
    hoverTarget.addEventListener('mouseleave', function() {
      hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
        var iconPng = icon.getAttribute('data-png');
        if (iconPng) icon.setAttribute('src', iconPng);
        icon.style.transform = '';
      });
    });
    hoverTarget.addEventListener('pointerleave', function() {
      hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
        var iconPng = icon.getAttribute('data-png');
        if (iconPng) icon.setAttribute('src', iconPng);
        icon.style.transform = '';
      });
    });
    hoverTarget.addEventListener('focusout', function() {
      setTimeout(function() {
        if (hoverTarget.contains(document.activeElement)) return;
        hoverTarget.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(icon) {
          var iconPng = icon.getAttribute('data-png');
          if (iconPng) icon.setAttribute('src', iconPng);
          icon.style.transform = '';
        });
      }, 0);
    });
  }
}

function initAdminButtonIcons() {
  if (!document.querySelector('.adm-main, .adm-sidebar')) return;

  var iconBase = '../assets/Icon/';

  function inferIconNameByLabel(el) {
    if (!el) return '';
    var text = (el.textContent || '').toLowerCase().replace(/\s+/g, ' ').trim();

    if (!text) return '';
    if (text.indexOf('add department') !== -1 || text.indexOf('new department') !== -1) return 'add_department';
    if (text.indexOf('clear') !== -1 || text.indexOf('clear filter') !== -1) return 'clear_filter';
    if (text.indexOf('post news') !== -1 || text === 'publish') return 'post_news';
    if (text.indexOf('save') !== -1) return 'save';
    if (text.indexOf('reset') !== -1) return 'reset';
    if (text === 'search') return 'search';
    if (text.indexOf('open in new tab') !== -1 || text.indexOf('new tab') !== -1) return 'new_tab';
    if (text.indexOf('second screen') !== -1) return 'second_screen';
    if (text.indexOf('fullscreen') !== -1) return 'full_screen';
    if (text.indexOf('log out') !== -1 || text.indexOf('logout') !== -1) return 'log-out';
    if (text.indexOf('view site') !== -1) return 'viewsite';

    return '';
  }

  function stripLeadingButtonSymbol(btn) {
    if (!btn || btn.dataset.iconTextCleaned === '1') return;

    var cleaned = false;
    var leadSymbolRegex = /^\s*(?:[\u2190-\u2BFF\u2600-\u27BF\u{1F000}-\u{1FAFF}]+)\s*/u;

    btn.childNodes.forEach(function(node) {
      if (cleaned) return;
      if (node.nodeType !== Node.TEXT_NODE) return;

      var value = node.nodeValue || '';
      if (!value.trim()) return;

      var next = value.replace(leadSymbolRegex, '');
      if (next !== value) {
        node.nodeValue = next;
      }
      cleaned = true;
    });

    btn.dataset.iconTextCleaned = '1';
  }

  function addIconToButtons(selector, iconName) {
    document.querySelectorAll(selector).forEach(function(btn) {
      if (btn.querySelector('img.auto-btn-icon')) return;

      stripLeadingButtonSymbol(btn);

      var resolvedIconName = btn.getAttribute('data-icon-name') || inferIconNameByLabel(btn) || iconName;

      var png = iconBase + resolvedIconName + '.png';
      var gif = iconBase + resolvedIconName + '.gif';
      var icon = document.createElement('img');
      icon.className = 'auto-btn-icon';
      icon.alt = '';
      icon.setAttribute('aria-hidden', 'true');
      icon.setAttribute('src', png);
      icon.setAttribute('data-png', png);
      icon.setAttribute('data-gif', gif);

      if ((btn.getAttribute('data-icon-position') || '').toLowerCase() === 'end') {
        btn.appendChild(icon);
      } else {
        btn.insertBefore(icon, btn.firstChild);
      }
      bindHoverSwapIcon(icon);
    });
  }

  function addIconsToSearchLabels() {
    document.querySelectorAll('.mbar label').forEach(function(label) {
      var text = (label.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
      if (text !== 'search') return;
      if (label.querySelector('img.auto-btn-icon')) return;

      var png = iconBase + 'search.png';
      var gif = iconBase + 'search.gif';
      var icon = document.createElement('img');
      icon.className = 'auto-btn-icon';
      icon.alt = '';
      icon.setAttribute('aria-hidden', 'true');
      icon.setAttribute('src', png);
      icon.setAttribute('data-png', png);
      icon.setAttribute('data-gif', gif);

      label.textContent = '';
      label.setAttribute('aria-label', 'Search');
      label.classList.add('search-icon-only');
      label.insertBefore(icon, label.firstChild);
      bindHoverSwapIcon(icon);
    });
  }

  addIconToButtons('.toggle-btn', 'add');
  addIconToButtons('.btn-save', 'save');
  addIconToButtons('.btn-cancel-f, .adm-main .btn-navy', 'next');
  addIconToButtons('.btn-clf', 'clear_filter');
  addIconToButtons('.btn-edit', 'edit');
  addIconToButtons('.btn-del', 'delete');
  addIconToButtons('.btn-exp', 'export1');
  addIconToButtons('.btn-filt', 'chart');
  addIconToButtons('.showcase-chip', 'full_screen');
  addIconsToSearchLabels();

  document.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(img) {
    bindHoverSwapIcon(img);
  });

  // Ensure toggle buttons reflect current panel state after icons are injected.
  document.querySelectorAll('[onclick^="togglePanel("]').forEach(function(btn) {
    var onclickVal = btn.getAttribute('onclick') || '';
    var m = onclickVal.match(/togglePanel\('([^']+)'\)/);
    if (!m) return;

    var panel = document.getElementById(m[1]);
    var isOpen = !!(panel && panel.classList.contains('is-open'));
    setPanelToggleButtonIcon(btn, isOpen);
  });
}

function initAdminFloatingQuickActions() {
  var dock = document.getElementById('adminQuickDock');
  var fab = document.getElementById('adminQuickFab');
  var overlay = document.getElementById('adminQuickOverlay');
  var backdrop = document.getElementById('adminQuickBackdrop');
  var menu = document.getElementById('adminQuickMenu');
  if (!dock || !fab || !overlay || !menu) return;

  var quickForms = overlay.querySelectorAll('.adm-form[id]');
  var activeDiscardToast = null;

  function serializeQuickForm(form) {
    var formEl = form ? form.querySelector('form') : null;
    if (!formEl) return '';

    var parts = [];
    Array.prototype.forEach.call(formEl.elements, function(field) {
      if (!field || !field.name || field.disabled) return;

      var type = (field.type || '').toLowerCase();
      if (type === 'button' || type === 'submit' || type === 'reset') return;

      if (type === 'checkbox' || type === 'radio') {
        parts.push(field.name + '=' + (field.checked ? '1' : '0'));
        return;
      }

      if (type === 'file') {
        parts.push(field.name + '=' + (field.value || ''));
        return;
      }

      parts.push(field.name + '=' + (field.value || ''));
    });

    return parts.join('&');
  }

  function markQuickFormBaseline(form) {
    if (!form) return;
    form.dataset.quickInitialState = serializeQuickForm(form);
    form.dataset.quickSubmitting = '0';
  }

  function isQuickFormDirty(form) {
    if (!form) return false;
    if (form.dataset.quickSubmitting === '1') return false;

    // Some admin panels use togglePanel but are outside the quick overlay.
    // Initialize baseline lazily so first close doesn't trigger a false unsaved prompt.
    if (!form.hasAttribute('data-quick-initial-state')) {
      markQuickFormBaseline(form);
      return false;
    }

    var initialState = form.dataset.quickInitialState || '';
    return serializeQuickForm(form) !== initialState;
  }

  function discardQuickFormChanges(form) {
    if (!form) return;
    var formEl = form.querySelector('form');
    if (formEl) {
      formEl.reset();
      formEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    markQuickFormBaseline(form);
  }

  function requestQuickFormDiscard(form, onDiscard, onKeep) {
    if (!form || !isQuickFormDirty(form)) {
      if (typeof onDiscard === 'function') onDiscard();
      return;
    }

    if (activeDiscardToast && typeof activeDiscardToast.dismiss === 'function') {
      activeDiscardToast.dismiss();
    }

    activeDiscardToast = showSileoToastBar({
      title: 'Unsaved input',
      message: 'Discard draft?',
      variant: 'warning',
      persistent: true,
      position: 'top-right',
      actions: [
        {
          label: 'Keep',
          dismiss: true,
          onClick: function() {
            activeDiscardToast = null;
            if (typeof onKeep === 'function') onKeep();
          }
        },
        {
          label: 'Discard',
          dismiss: true,
          onClick: function() {
            discardQuickFormChanges(form);
            activeDiscardToast = null;
            if (typeof onDiscard === 'function') onDiscard();
          }
        }
      ]
    });
  }

  quickForms.forEach(function(form) {
    var formEl = form.querySelector('form');
    if (!formEl) return;

    markQuickFormBaseline(form);

    formEl.addEventListener('submit', function() {
      form.dataset.quickSubmitting = '1';
    });
  });

  window.requestAdminPanelClose = function(panelEl, onDiscard, onKeep) {
    if (!panelEl || !panelEl.classList || !panelEl.classList.contains('adm-form')) {
      if (typeof onDiscard === 'function') onDiscard();
      return;
    }
    requestQuickFormDiscard(panelEl, onDiscard, onKeep);
  };

  if (!fab.querySelector('img.auto-btn-icon') && !fab.querySelector('img.icon-swap')) {
    var fabIcon = fab.querySelector('img');
    if (fabIcon) bindHoverSwapIcon(fabIcon);
  }
  menu.querySelectorAll('img.icon-swap, img.auto-btn-icon').forEach(function(img) {
    bindHoverSwapIcon(img);
  });

  function closeQuickForms(options, onDone) {
    options = options || {};
    var exceptId = options.exceptId || null;
    var formsToClose = [];

    quickForms.forEach(function(form) {
      if (exceptId && form.id === exceptId) return;
      if (form.classList.contains('is-open')) {
        formsToClose.push(form);
      }
    });

    if (!formsToClose.length) {
      if (typeof onDone === 'function') onDone(true);
      return;
    }

    function closeNext(index) {
      if (index >= formsToClose.length) {
        if (typeof onDone === 'function') onDone(true);
        return;
      }

      var form = formsToClose[index];
      if (!form || !form.classList.contains('is-open')) {
        closeNext(index + 1);
        return;
      }

      requestQuickFormDiscard(
        form,
        function() {
          if (form.classList.contains('is-open')) {
            togglePanel(form.id, { skipConfirm: true });
          }
          closeNext(index + 1);
        },
        function() {
          if (typeof onDone === 'function') onDone(false);
        }
      );
    }

    closeNext(0);
  }

  function syncAdminQuickOverlayState() {
    var anyFormOpen = !!overlay.querySelector('.adm-form.is-open');
    var menuOpen = dock.classList.contains('is-menu-open') && !anyFormOpen;
    var isVisible = menuOpen || anyFormOpen;
    var activeEl = document.activeElement;

    // Prevent aria-hidden warnings by removing focus from the quick menu
    // before it is hidden from assistive technologies.
    if (!menuOpen && activeEl && menu.contains(activeEl) && typeof activeEl.blur === 'function') {
      activeEl.blur();
    }
    if (!anyFormOpen && activeEl && overlay.contains(activeEl)) {
      if (typeof activeEl.blur === 'function') {
        activeEl.blur();
      }
      if (fab && typeof fab.focus === 'function') {
        fab.focus({ preventScroll: true });
      }
    }

    dock.classList.toggle('is-open', isVisible);
    overlay.classList.toggle('is-open', anyFormOpen);
    overlay.classList.toggle('is-form-open', anyFormOpen);
    document.body.classList.toggle('admin-quick-open', anyFormOpen);

    fab.setAttribute('aria-expanded', menuOpen ? 'true' : 'false');
    overlay.setAttribute('aria-hidden', anyFormOpen ? 'false' : 'true');
    if (anyFormOpen) {
      overlay.removeAttribute('inert');
    } else {
      overlay.setAttribute('inert', '');
    }
    menu.setAttribute('aria-hidden', menuOpen ? 'false' : 'true');
    if (menuOpen) {
      menu.removeAttribute('inert');
    } else {
      menu.setAttribute('inert', '');
    }

    if (anyFormOpen) {
      dock.classList.remove('is-menu-open');
    }
  }

  function openAdminQuickMenu(onDone) {
    if (overlay.querySelector('.adm-form.is-open')) {
      closeQuickForms({}, function(closed) {
        if (!closed) {
          syncAdminQuickOverlayState();
          if (typeof onDone === 'function') onDone(false);
          return;
        }

        dock.classList.toggle('is-menu-open');
        syncAdminQuickOverlayState();
        if (typeof onDone === 'function') onDone(true);
      });
      return;
    }

    dock.classList.toggle('is-menu-open');
    syncAdminQuickOverlayState();
    if (typeof onDone === 'function') onDone(true);
  }

  function closeAdminQuickDock(options, onDone) {
    options = options || {};
    dock.classList.remove('is-menu-open');
    closeQuickForms(options, function(closed) {
      if (!closed) {
        syncAdminQuickOverlayState();
        if (typeof onDone === 'function') onDone(false);
        return;
      }
      syncAdminQuickOverlayState();
      if (typeof onDone === 'function') onDone(true);
    });
  }

  function openAdminQuickPanel(panelId) {
    if (!panelId) return;

    dock.classList.remove('is-menu-open');
    closeQuickForms({ exceptId: panelId }, function(closed) {
      if (!closed) {
        syncAdminQuickOverlayState();
        return;
      }

      var target = document.getElementById(panelId);
      if (target) {
        if (!target.classList.contains('is-open')) {
          togglePanel(panelId);
        }
        target.scrollTop = 0;
        var shell = overlay.querySelector('.adm-quick-form-shell');
        if (shell) shell.scrollTop = 0;
        syncAdminQuickOverlayState();
        var firstField = target.querySelector('input, select, textarea, button');
        var shouldAutoFocus = window.matchMedia && window.matchMedia('(min-width: 901px)').matches;
        if (shouldAutoFocus && firstField && typeof firstField.focus === 'function') {
          window.setTimeout(function() {
            firstField.focus();
          }, 160);
        }
      }
    });
  }

  window.syncAdminQuickOverlayState = syncAdminQuickOverlayState;
  window.openAdminQuickPanel = openAdminQuickPanel;
  window.openAdminQuickMenu = openAdminQuickMenu;
  window.closeAdminQuickDock = closeAdminQuickDock;

  fab.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    triggerButtonSpin(fab);

    var hasOpenForm = !!overlay.querySelector('.adm-form.is-open');
    if (hasOpenForm) {
      closeAdminQuickDock({}, function(closed) {
        if (!closed) return;
        window.setTimeout(function() {
          if (overlay.querySelector('.adm-form.is-open')) return;
          if (dock.classList.contains('is-menu-open')) return;
          openAdminQuickMenu();
        }, 220);
      });
      return;
    }

    if (dock.classList.contains('is-menu-open')) {
      closeAdminQuickDock();
      return;
    }

    openAdminQuickMenu();
  });

  backdrop.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
  });

  menu.querySelectorAll('[data-quick-target]').forEach(function(button) {
    button.addEventListener('click', function() {
      openAdminQuickPanel(button.getAttribute('data-quick-target'));
    });
  });

  menu.querySelectorAll('a[href]').forEach(function(link) {
    link.addEventListener('click', function() {
      dock.classList.remove('is-menu-open');
      syncAdminQuickOverlayState();
    });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && dock.classList.contains('is-open')) {
      closeAdminQuickDock();
    }
  });

  function openLinkedQuickPanelFromHash() {
    var hash = window.location.hash || '';
    if (!hash) return;
    if (hash === '#quickAddDeptForm' || hash === '#quickAddNewsForm') {
      var targetId = hash.slice(1);
      var target = document.getElementById(targetId);
      if (target && typeof window.openAdminQuickPanel === 'function') {
        window.openAdminQuickPanel(targetId);
      }
    }
  }

  syncAdminQuickOverlayState();

  if (document.body.classList.contains('admin-page-enter') || document.readyState === 'loading') {
    window.setTimeout(openLinkedQuickPanelFromHash, 220);
  } else {
    openLinkedQuickPanelFromHash();
  }
}

function initLogoutToastActions() {
  document.querySelectorAll('a[href*="action=logout"]').forEach(function(link) {
    if (link.dataset.logoutToastBound === '1') return;
    link.dataset.logoutToastBound = '1';

    link.addEventListener('click', function(e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      } else {
        e.stopPropagation();
      }

      var href = link.getAttribute('href') || 'index.php?action=logout';
      var isGuestLogout = link.classList.contains('nav-logout-icon-only');
      showSileoToastBar({
        title: 'Confirm Logout',
        message: 'Do you really want to log out?',
        variant: 'warning',
        persistent: true,
        position: 'top-right',
        actions: [
          {
            label: 'YES',
            onClick: function() {
              showSileoToastBar({
                title: isGuestLogout ? 'Thank You' : 'Logged Out',
                message: isGuestLogout ? 'Thank you, come again!' : 'Signing out...',
                variant: 'success',
                duration: 700,
                position: 'top-right'
              }, 'success', 700);
              window.setTimeout(function() {
                window.location.href = href;
              }, 260);
            }
          },
          {
            label: 'NO',
            dismiss: true
          }
        ]
      }, 'warning');
    }, true);
  });
}

function initPublicHeaderNavTransition() {
  if (document.querySelector('.adm-sidebar')) return;
  if (window.innerWidth <= 992) return;

  if (document.body.classList.contains('page-home')) {
    try {
      var fromHomeFlag = sessionStorage.getItem('homeReturnAnim') === '1';
      if (fromHomeFlag) {
        document.body.classList.add('page-entering');
        setTimeout(function() {
          document.body.classList.remove('page-entering');
          sessionStorage.removeItem('homeReturnAnim');
        }, 340);
      }
    } catch (e) {
      // Ignore storage errors.
    }
  }

  var links = document.querySelectorAll('.site-header a[href]');
  links.forEach(function(link) {
    link.addEventListener('click', function(e) {
      if (e.defaultPrevented) return;
      if (e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      var href = link.getAttribute('href') || '';
      if (!href || href.charAt(0) === '#') return;
      if (href.indexOf('javascript:') === 0) return;

      var isGoingHome = /(?:\?|&)page=home(?:&|$)/.test(href) || /index\.php(?:$|\?)/.test(href);

      e.preventDefault();
      if (document.body.classList.contains('page-home')) {
        document.body.classList.add('page-leaving');
      }

      try {
        sessionStorage.setItem('homeReturnAnim', isGoingHome ? '1' : '0');
      } catch (err) {
        // Ignore storage errors.
      }

      setTimeout(function() {
        navigateAfterFullscreenExit(href);
      }, 170);
    });
  });
}

function initArtifactFilterModal() {
  var form = document.querySelector('[data-artifact-filter-form="1"]');
  if (!form) return;

  var toggleBtn = form.querySelector('[data-filter-open]');
  var panel = form.querySelector('[data-filter-panel]');
  if (!toggleBtn || !panel) return;

  function isMobile() {
    return window.innerWidth <= 768;
  }

  function setExpanded(nextOpen) {
    var open = Boolean(nextOpen) && isMobile();
    form.classList.toggle('is-filter-open', open);
    toggleBtn.classList.toggle('is-open', open);
    toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  toggleBtn.addEventListener('click', function() {
    setExpanded(!form.classList.contains('is-filter-open'));
  });

  window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && form.classList.contains('is-filter-open')) {
      setExpanded(false);
    }
  });

  window.addEventListener('resize', function() {
    if (!isMobile()) {
      setExpanded(false);
    }
  });

  panel.querySelectorAll('select').forEach(function(select) {
    select.addEventListener('change', function() {
      if (isMobile()) {
        setExpanded(false);
      }
    });
  });

  form.addEventListener('submit', function() {
    setExpanded(false);
  });
}

function initComboSkinSelects() {
  var selects = document.querySelectorAll('select.js-combo-skin');
  if (!selects.length) return;

  var openWrap = null;

  function closeWrap(wrap) {
    if (!wrap) return;
    wrap.classList.remove('is-open');
    var trigger = wrap.querySelector('.combo-skin__trigger');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
    if (openWrap === wrap) openWrap = null;
  }

  function closeAllExcept(keep) {
    document.querySelectorAll('.combo-skin.is-open').forEach(function(wrap) {
      if (wrap !== keep) closeWrap(wrap);
    });
  }

  selects.forEach(function(select, idx) {
    if (select.dataset.comboEnhanced === '1') return;
    select.dataset.comboEnhanced = '1';

    var wrap = document.createElement('div');
    wrap.className = 'combo-skin';

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'combo-skin__trigger';
    trigger.id = 'comboSkinTrigger' + idx;
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    var label = document.createElement('span');
    label.className = 'combo-skin__label';

    var caret = document.createElement('span');
    caret.className = 'combo-skin__caret';
    caret.innerHTML = '&#9662;';

    trigger.appendChild(label);
    trigger.appendChild(caret);

    var menu = document.createElement('div');
    menu.className = 'combo-skin__menu';
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-labelledby', trigger.id);

    function refresh() {
      var selected = select.options[select.selectedIndex];
      label.textContent = selected ? selected.textContent : 'Select';
      menu.querySelectorAll('.combo-skin__option').forEach(function(optBtn) {
        optBtn.classList.toggle('is-active', optBtn.dataset.value === select.value);
      });
    }

    Array.prototype.forEach.call(select.options, function(opt) {
      var optBtn = document.createElement('button');
      optBtn.type = 'button';
      optBtn.className = 'combo-skin__option';
      optBtn.setAttribute('role', 'option');
      optBtn.dataset.value = opt.value;
      optBtn.textContent = opt.textContent;

      optBtn.addEventListener('click', function() {
        if (select.value !== opt.value) {
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeWrap(wrap);
        refresh();
      });

      menu.appendChild(optBtn);
    });

    trigger.addEventListener('click', function() {
      var isOpen = wrap.classList.contains('is-open');
      closeAllExcept(wrap);
      if (isOpen) {
        closeWrap(wrap);
      } else {
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        openWrap = wrap;
      }
    });

    select.classList.add('combo-skin__native');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    wrap.appendChild(trigger);
    wrap.appendChild(menu);

    select.addEventListener('change', refresh);
    refresh();
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.combo-skin')) {
      closeAllExcept(null);
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllExcept(null);
  });
}

function initArtifactMorphTransition() {
  if (!document.body.classList.contains('page-exhibits')) return;

  var cards = document.querySelectorAll('.ex-card[href]');
  if (!cards.length) return;

  cards.forEach(function(card) {
    if (card.dataset.morphBound === '1') return;
    card.dataset.morphBound = '1';

    card.addEventListener('click', function(e) {
      if (e.defaultPrevented) return;
      if (e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      var href = card.getAttribute('href') || '';
      if (!href || href.charAt(0) === '#') return;

      e.preventDefault();

      document.querySelectorAll('.ex-card.is-morphing, .ex-card.is-dimming').forEach(function(el) {
        el.classList.remove('is-morphing', 'is-dimming');
      });

      cards.forEach(function(other) {
        if (other !== card) other.classList.add('is-dimming');
      });

      card.classList.add('is-morphing');

      try {
        sessionStorage.setItem('artifactDetailMorphIn', '1');
      } catch (err) {
        // Ignore storage errors.
      }

      setTimeout(function() {
        navigateAfterFullscreenExit(href);
      }, 240);
    });
  });
}

function initArtifactDetailMorphEntry() {
  if (!document.body.classList.contains('page-detail')) return;

  var shouldAnimate = false;
  try {
    shouldAnimate = sessionStorage.getItem('artifactDetailMorphIn') === '1';
    if (shouldAnimate) sessionStorage.removeItem('artifactDetailMorphIn');
  } catch (err) {
    shouldAnimate = false;
  }

  if (!shouldAnimate) return;

  document.body.classList.add('artifact-detail-enter');
  requestAnimationFrame(function() {
    document.body.classList.add('artifact-detail-enter-active');
  });

  setTimeout(function() {
    document.body.classList.remove('artifact-detail-enter');
    document.body.classList.remove('artifact-detail-enter-active');
  }, 760);
}

function initHeaderScrollVisibility() {
  var header = document.querySelector('.site-header');
  if (!header) return;

  var lastY = window.scrollY || 0;
  var deltaMin = 3;
  var topSafe = 2;

  window.addEventListener('scroll', function() {
    var y = window.scrollY || 0;
    var diff = y - lastY;

    if (Math.abs(diff) < deltaMin) return;

    if (y <= topSafe) {
      document.body.classList.remove('header-hidden');
      lastY = y;
      return;
    }

    if (diff > 0) {
      document.body.classList.add('header-hidden');
    } else {
      document.body.classList.remove('header-hidden');
    }

    lastY = y;
  }, { passive: true });
}

function initIconPathCaseFallback() {
  document.querySelectorAll('img[src], img[data-png], img[data-gif]').forEach(function(img) {
    if (img.dataset.iconCaseFallbackBound === '1') return;
    img.dataset.iconCaseFallbackBound = '1';

    img.addEventListener('error', function() {
      var currentSrc = img.getAttribute('src') || '';
      var fixedSrc = currentSrc;

      if (fixedSrc.indexOf('/assets/Icon/') !== -1) {
        fixedSrc = fixedSrc.replace('/assets/Icon/', '/assets/icon/');
      } else if (fixedSrc.indexOf('/assets/icon/') !== -1) {
        fixedSrc = fixedSrc.replace('/assets/icon/', '/assets/Icon/');
      } else {
        return;
      }

      if (fixedSrc === currentSrc) return;
      img.setAttribute('src', fixedSrc);

      var dataPng = img.getAttribute('data-png') || '';
      var dataGif = img.getAttribute('data-gif') || '';
      if (dataPng.indexOf('/assets/Icon/') !== -1) {
        img.setAttribute('data-png', dataPng.replace('/assets/Icon/', '/assets/icon/'));
      } else if (dataPng.indexOf('/assets/icon/') !== -1) {
        img.setAttribute('data-png', dataPng.replace('/assets/icon/', '/assets/Icon/'));
      }
      if (dataGif.indexOf('/assets/Icon/') !== -1) {
        img.setAttribute('data-gif', dataGif.replace('/assets/Icon/', '/assets/icon/'));
      } else if (dataGif.indexOf('/assets/icon/') !== -1) {
        img.setAttribute('data-gif', dataGif.replace('/assets/icon/', '/assets/Icon/'));
      }
    });
  });
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

      // Always give instant click feedback, even on fast form submissions.
      btn.classList.remove('is-submit-clicked');
      void btn.offsetWidth;
      btn.classList.add('is-submit-clicked');
      setTimeout(function() {
        if (document.body.contains(btn)) {
          btn.classList.remove('is-submit-clicked');
        }
      }, 380);

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

function initPageLoadingOverlay() {
  var overlay = document.getElementById('pageLoadingOverlay');
  if (!overlay) return;

  var showTimer = window.setTimeout(function() {
    overlay.classList.add('is-visible');
  }, 250);

  function hideOverlay() {
    window.clearTimeout(showTimer);
    overlay.classList.remove('is-visible');
    overlay.classList.add('is-hidden');
  }

  if (document.readyState === 'complete') {
    hideOverlay();
  } else {
    window.addEventListener('load', hideOverlay, { once: true });
  }
}

function bindSubmitAnimationOnForm(form) {
  if (!form || form.dataset.submitAnimBound === '1') return;

  // Account settings forms have their own toast-driven submit flow.
  // Skipping submit-animation forwarding here prevents accidental auto-submit.
  if (form.matches && form.matches('form[data-account-confirm]')) return;

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

function initAdminDashboardTableScroll() {
  var wrappers = document.querySelectorAll('.adm-dashboard-table-wrap');
  if (!wrappers.length) return;

  wrappers.forEach(function(wrap) {
    if (wrap.dataset.dragScrollBound === '1') return;
    wrap.dataset.dragScrollBound = '1';

    var startX = 0;
    var startY = 0;
    var startLeft = 0;
    var dragging = false;
    var lockAxis = '';

    wrap.addEventListener('touchstart', function(e) {
      if (!e.touches || !e.touches.length) return;
      var t = e.touches[0];
      startX = t.clientX;
      startY = t.clientY;
      startLeft = wrap.scrollLeft;
      dragging = true;
      lockAxis = '';
    }, { passive: true });

    wrap.addEventListener('touchmove', function(e) {
      if (!dragging || !e.touches || !e.touches.length) return;
      var t = e.touches[0];
      var dx = t.clientX - startX;
      var dy = t.clientY - startY;

      if (!lockAxis) {
        lockAxis = Math.abs(dx) >= Math.abs(dy) ? 'x' : 'y';
      }
      if (lockAxis !== 'x') return;

      wrap.scrollLeft = startLeft - dx;
      e.preventDefault();
    }, { passive: false });

    wrap.addEventListener('touchend', function() {
      dragging = false;
      lockAxis = '';
    }, { passive: true });

    wrap.addEventListener('touchcancel', function() {
      dragging = false;
      lockAxis = '';
    }, { passive: true });
  });
}

function initAdminPullToRefresh() {
  if (!document.querySelector('.adm-layout')) return;
  if (window.innerWidth > 900) return;

  var indicator = document.createElement('div');
  indicator.className = 'adm-pull-refresh-indicator';
  indicator.setAttribute('aria-hidden', 'true');

  var icon = document.createElement('img');
  icon.alt = 'Refreshing';
  var loadingIcon = document.querySelector('.page-loading-icon');
  if (loadingIcon && loadingIcon.getAttribute('src')) {
    icon.src = loadingIcon.getAttribute('src');
  }
  indicator.appendChild(icon);
  document.body.appendChild(indicator);

  var startY = 0;
  var startX = 0;
  var pulling = false;
  var ready = false;
  var threshold = 84;

  function resetIndicator() {
    indicator.classList.remove('is-visible');
    indicator.classList.remove('is-ready');
    indicator.classList.remove('is-loading');
    indicator.style.transform = 'translate(-50%, -72px)';
    ready = false;
    pulling = false;
  }

  document.addEventListener('touchstart', function(e) {
    if (!e.touches || !e.touches.length) return;
    if (window.scrollY > 0) return;
    if (e.touches[0].clientY > 36) return;

    var target = e.target;
    if (target && target.closest('input, textarea, select, button, .adm-dashboard-table-wrap, .showcase-stage, .showcase-stage-wrap')) {
      return;
    }

    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    pulling = true;
    ready = false;
    indicator.classList.add('is-visible');
  }, { passive: true });

  document.addEventListener('touchmove', function(e) {
    if (!pulling || !e.touches || !e.touches.length) return;
    if (window.scrollY > 0) {
      resetIndicator();
      return;
    }

    var dy = e.touches[0].clientY - startY;
    var dx = e.touches[0].clientX - startX;
    if (Math.abs(dx) > Math.abs(dy) + 8) {
      resetIndicator();
      return;
    }
    if (dy <= 0) {
      resetIndicator();
      return;
    }
    if (dy < 12) return;

    var pull = Math.min(dy, 130);
    var translateY = -72 + pull;
    indicator.style.transform = 'translate(-50%, ' + translateY + 'px)';
    ready = pull >= threshold;
    indicator.classList.toggle('is-ready', ready);
  }, { passive: true });

  document.addEventListener('touchend', function() {
    if (!pulling) return;

    if (ready) {
      indicator.classList.add('is-loading');
      indicator.style.transform = 'translate(-50%, 8px)';
      var overlay = document.getElementById('pageLoadingOverlay');
      if (overlay) {
        overlay.classList.remove('is-hidden');
        overlay.classList.add('is-visible');
      }
      window.setTimeout(function() {
        window.location.reload();
      }, 180);
      return;
    }

    resetIndicator();
  }, { passive: true });

  document.addEventListener('touchcancel', function() {
    if (!pulling) return;
    resetIndicator();
  }, { passive: true });

  window.addEventListener('resize', function() {
    if (window.innerWidth > 900) {
      indicator.remove();
    }
  }, { once: true });
}

function initPublicPullToRefresh() {
  if (document.querySelector('.adm-layout')) return;
  if (window.innerWidth > 900) return;

  var indicator = document.createElement('div');
  indicator.className = 'site-pull-refresh-indicator';
  indicator.setAttribute('aria-hidden', 'true');

  var icon = document.createElement('img');
  icon.alt = 'Refreshing';
  var loadingIcon = document.querySelector('.page-loading-icon');
  if (loadingIcon && loadingIcon.getAttribute('src')) {
    icon.src = loadingIcon.getAttribute('src');
  }
  indicator.appendChild(icon);
  document.body.appendChild(indicator);

  var startY = 0;
  var startX = 0;
  var pulling = false;
  var ready = false;
  var threshold = 84;

  function resetIndicator() {
    indicator.classList.remove('is-visible');
    indicator.classList.remove('is-ready');
    indicator.classList.remove('is-loading');
    indicator.style.transform = 'translate(-50%, -72px)';
    ready = false;
    pulling = false;
  }

  document.addEventListener('touchstart', function(e) {
    if (!e.touches || !e.touches.length) return;
    if (window.scrollY > 0) return;
    if (e.touches[0].clientY > 36) return;

    var target = e.target;
    if (target && target.closest('input, textarea, select, button, .pdf-page-wrap, .showcase-stage, .teaser-scrollport')) {
      return;
    }

    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    pulling = true;
    ready = false;
    indicator.classList.add('is-visible');
  }, { passive: true });

  document.addEventListener('touchmove', function(e) {
    if (!pulling || !e.touches || !e.touches.length) return;
    if (window.scrollY > 0) {
      resetIndicator();
      return;
    }

    var dy = e.touches[0].clientY - startY;
    var dx = e.touches[0].clientX - startX;
    if (Math.abs(dx) > Math.abs(dy) + 8) {
      resetIndicator();
      return;
    }
    if (dy <= 0) {
      resetIndicator();
      return;
    }
    if (dy < 12) return;

    var pull = Math.min(dy, 130);
    var translateY = -72 + pull;
    indicator.style.transform = 'translate(-50%, ' + translateY + 'px)';
    ready = pull >= threshold;
    indicator.classList.toggle('is-ready', ready);
  }, { passive: true });

  document.addEventListener('touchend', function() {
    if (!pulling) return;

    if (ready) {
      indicator.classList.add('is-loading');
      indicator.style.transform = 'translate(-50%, 8px)';
      var overlay = document.getElementById('pageLoadingOverlay');
      if (overlay) {
        overlay.classList.remove('is-hidden');
        overlay.classList.add('is-visible');
      }
      window.setTimeout(function() {
        window.location.reload();
      }, 180);
      return;
    }

    resetIndicator();
  }, { passive: true });

  document.addEventListener('touchcancel', function() {
    if (!pulling) return;
    resetIndicator();
  }, { passive: true });

  window.addEventListener('resize', function() {
    if (window.innerWidth > 900) {
      indicator.remove();
    }
  }, { once: true });
}

function initPublicSwipeFade() {
  if (document.querySelector('.adm-layout')) return;

  var startX = 0;
  var startY = 0;
  var active = false;
  var clearTimer = null;
  var lastScrollY = window.scrollY || 0;
  var lastScrollTriggerTs = 0;
  var scrollTicking = false;

  function resetFadeClass() {
    document.body.classList.remove('swipe-fade-up');
    document.body.classList.remove('swipe-fade-down');
  }

  function triggerDirectionalFade(isUpDirection) {
    resetFadeClass();
    void document.body.offsetWidth;
    document.body.classList.add(isUpDirection ? 'swipe-fade-up' : 'swipe-fade-down');

    if (clearTimer) clearTimeout(clearTimer);
    clearTimer = window.setTimeout(function() {
      resetFadeClass();
      clearTimer = null;
    }, 320);
  }

  document.addEventListener('touchstart', function(e) {
    if (!e.touches || !e.touches.length) return;
    var t = e.target;
    if (t && t.closest('input, textarea, select, button, .teaser-scrollport, .pdf-page-wrap')) {
      active = false;
      return;
    }
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    active = true;
  }, { passive: true });

  document.addEventListener('touchend', function(e) {
    if (!active || !e.changedTouches || !e.changedTouches.length) return;
    active = false;

    var dx = e.changedTouches[0].clientX - startX;
    var dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dy) < 46) return;
    if (Math.abs(dx) > Math.abs(dy) * 0.9) return;

    triggerDirectionalFade(dy < 0);
  }, { passive: true });

  window.addEventListener('scroll', function() {
    if (scrollTicking) return;
    scrollTicking = true;

    window.requestAnimationFrame(function() {
      var currentY = window.scrollY || 0;
      var delta = currentY - lastScrollY;
      lastScrollY = currentY;
      scrollTicking = false;

      if (Math.abs(delta) < 10) return;

      var now = Date.now();
      if (now - lastScrollTriggerTs < 180) return;
      lastScrollTriggerTs = now;

      // Scrolling down moves content up, so reuse the "swipe-up" visual.
      triggerDirectionalFade(delta > 0);
    });
  }, { passive: true });
}

function initAdminAccountChangeConfirm() {
  var accountForms = document.querySelectorAll('form[data-account-confirm]');
  if (!accountForms.length) return;

  var activeAccountToast = null;

  accountForms.forEach(function(form) {
    if (form.dataset.accountConfirmBound === '1') return;
    form.dataset.accountConfirmBound = '1';

    form.addEventListener('submit', function(e) {
      if (form.dataset.accountConfirmedSubmit === '1') {
        form.dataset.accountConfirmedSubmit = '0';
        return;
      }

      var mode = form.getAttribute('data-account-confirm') || '';
      var confirmMessage = 'Apply account change?';
      if (mode === 'password') {
        confirmMessage = 'Change password?';
      } else if (mode === 'username') {
        confirmMessage = 'Change username?';
      } else if (mode === 'logo') {
        confirmMessage = 'Change website logo?';
      }
      e.preventDefault();
      if (activeAccountToast && typeof activeAccountToast.dismiss === 'function') {
        activeAccountToast.dismiss();
      }

      activeAccountToast = showSileoToastBar({
        title: 'Confirm',
        message: confirmMessage,
        variant: 'warning',
        persistent: true,
        position: 'top-right',
        actions: [
          {
            label: 'No',
            dismiss: true,
            onClick: function() {
              activeAccountToast = null;
            }
          },
          {
            label: 'Change',
            dismiss: true,
            onClick: function() {
              activeAccountToast = null;
              form.dataset.accountConfirmedSubmit = '1';
              if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
              } else {
                HTMLFormElement.prototype.submit.call(form);
              }
            }
          }
        ]
      });
    });
  });
}

/* ── INIT ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  initMobileMenuAutoCollapse();
  initCalendar();
  initAutoSubmitFilters();
  initTeaserFader();
  initScrollFloatReveal();
  initSubmitButtonAnimations();
  initAdminSidebarCollapse();
  initAdminMenuNavAnimation();
  initAdminButtonIcons();
  document.querySelectorAll('img.icon-swap').forEach(function(img) {
    bindHoverSwapIcon(img);
  });
  initLogoutToastActions();
  initPublicHeaderNavTransition();
  initArtifactMorphTransition();
  initArtifactDetailMorphEntry();
  initIconPathCaseFallback();
  initHeaderScrollVisibility();
  initPageLoadingOverlay();
  initAdminFloatingQuickActions();
  initAdminDashboardTableScroll();
  initAdminPullToRefresh();
  initPublicPullToRefresh();
  initPublicSwipeFade();
  initArtifactFilterModal();
  initComboSkinSelects();
  initAdminAccountChangeConfirm();

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
