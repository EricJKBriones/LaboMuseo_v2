<?php
// pages/home.php
$now     = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');
$dayNow = (int)date('j');
$daysInMonth = (int)date('t');
$daysRemaining = $daysInMonth - $dayNow;

$nextMonthStart = date('Y-m-01', strtotime('first day of next month'));
$nextMonthFifth = date('Y-m-d', strtotime($nextMonthStart . ' +4 days'));
$imgBase = __DIR__ . '/../uploads/';

$totalArtifacts    = dbCount("SELECT COUNT(*) FROM exhibits");
$totalDepts        = dbCount("SELECT COUNT(*) FROM categories");
$totalVisitors     = dbCount("SELECT COALESCE(SUM(headcount), 0) FROM guests WHERE visit_date = ?", [date('Y-m-d')]);
$latestNews        = dbOne("SELECT * FROM news_events WHERE is_archived=0 AND COALESCE(event_date, date_posted) BETWEEN ? AND ? ORDER BY COALESCE(event_date, date_posted) DESC, id DESC LIMIT 1", [$monthStart, $monthEnd]);
$latestArt         = dbOne("SELECT e.*,c.name as cat_name FROM exhibits e LEFT JOIN categories c ON e.category_id=c.id ORDER BY e.id DESC LIMIT 1");
$tickerItems       = dbQuery("SELECT * FROM news_events WHERE is_archived=0 AND COALESCE(event_date, date_posted) BETWEEN ? AND ? ORDER BY COALESCE(event_date, date_posted) DESC, id DESC LIMIT 5", [$monthStart, $monthEnd]);
$latestNewsList    = dbQuery("SELECT * FROM news_events WHERE is_archived=0 AND type='news' AND date_posted BETWEEN ? AND ? ORDER BY date_posted DESC, id DESC LIMIT 6", [$monthStart, $monthEnd]);
$upcomingEvents    = dbQuery("SELECT * FROM news_events WHERE is_archived=0 AND type='event' AND event_date BETWEEN ? AND ? AND event_date >= ? ORDER BY event_date ASC LIMIT 6", [$monthStart, $monthEnd, $now]);
$oldNews           = dbQuery("SELECT * FROM news_events WHERE ((is_archived=1 AND date_posted BETWEEN ? AND ?) OR (type='event' AND event_date BETWEEN ? AND ? AND event_date < ?)) ORDER BY COALESCE(event_date, date_posted) DESC LIMIT 6", [$monthStart, $monthEnd, $monthStart, $monthEnd, $now]);
$teaserArtifacts   = dbQuery("SELECT e.*,c.name as cat_name FROM exhibits e LEFT JOIN categories c ON e.category_id=c.id ORDER BY e.id DESC LIMIT 8");
$calEvents         = dbQuery("SELECT id,title,event_date,type FROM news_events WHERE event_date IS NOT NULL ORDER BY event_date ASC");
$calUpcomingSql = "SELECT * FROM news_events WHERE type='event' AND is_archived=0 AND ((event_date BETWEEN ? AND ? AND event_date >= ?)";
$calUpcomingParams = [$monthStart, $monthEnd, $now];
if ($daysRemaining <= 5) {
  $calUpcomingSql .= " OR (event_date BETWEEN ? AND ? )";
  $calUpcomingParams[] = $nextMonthStart;
  $calUpcomingParams[] = $nextMonthFifth;
}
$calUpcomingSql .= ") ORDER BY event_date ASC LIMIT 8";
$calEventsUpcoming = dbQuery($calUpcomingSql, $calUpcomingParams);
$loggedIn = isLoggedIn();

function newsCard(array $n, string $imgBase, string $now): string {
    $isEv   = $n['type'] === 'event';
    $isPast = $isEv && $n['event_date'] && $n['event_date'] < $now;
    $hasImg = $n['image_path'] && file_exists($imgBase . $n['image_path']);
    $url    = 'index.php?page=news_detail&id=' . (int)$n['id'];
    $cls    = 'nc' . ($isEv?' is-event':'') . ($isPast?' is-past':'');
    if ($isPast)   $badge = '<span class="pill pill-past">&#128193; Archive</span>';
    elseif ($isEv) $badge = '<span class="pill pill-event">&#128197; Upcoming Event</span>';
    else           $badge = '<span class="pill pill-news">&#128240; Museum News</span>';
    $meta   = ($isEv && $n['event_date']) ? 'Scheduled: '.date('F j, Y',strtotime($n['event_date'])) : 'Posted: '.date('F j, Y',strtotime($n['date_posted']));
    $imgBg  = ($isEv && !$hasImg) ? ' style="background:linear-gradient(135deg,#2d1b4e,#4a2b7a)"' : '';
    $imgHtml= $hasImg ? '<img src="uploads/'.htmlspecialchars($n['image_path']).'" alt="'.htmlspecialchars($n['title']).'">' : '<div class="nc-img-ph">'.($isEv?'&#128197;':'&#128240;').'</div>';
    $lbl    = $isEv ? 'View Details' : 'Read More';
    return '<a href="'.$url.'" class="'.$cls.'" style="text-decoration:none;cursor:pointer">
      <div class="nc-img"'.$imgBg.'>'.$imgHtml.'</div>
      <div class="nc-body">'.$badge.'
        <div class="nc-meta">'.$meta.'</div>
        <h3 class="nc-title">'.htmlspecialchars($n['title']).'</h3>
        <p class="nc-text">'.htmlspecialchars(mb_substr($n['content'],0,160)).'...</p>
        <div class="nc-foot"><span class="btn-ghost">'.$lbl.' &rarr;</span></div>
      </div></a>';
}
?>
<section class="hero">
  <div class="hero-pattern"></div><div class="hero-glow"></div>
  <div class="hero-inner">
    <div>
      <div class="hero-eyebrow">Heritage &middot; Culture &middot; History</div>
      <h1 class="hero-title">Welcome to<br><em>Museo de Labo</em></h1>
      <p class="hero-desc">Preserving the rich history, culture, and heritage of Camarines Norte. Step through our doors to uncover the stories of our ancestors and the treasures of our past.</p>
      <div class="hero-btns">
        <?php if ($loggedIn): ?>
          <a href="index.php?page=categories" class="btn-gold">Enter the Catalog &rarr;</a>
        <?php else: ?>
          <a href="index.php?page=login" class="btn-gold">Sign Digital Guestbook &#10022;</a>
        <?php endif; ?>
        <a href="index.php?page=about" class="btn-outline" style="color:#9bb8cc;border-color:rgba(155,184,204,.4)">Learn More</a>
      </div>
      <div class="hero-stats">
        <div><div class="stat-n"><?= $totalArtifacts ?></div><div class="stat-l">Artifacts</div></div>
        <div><div class="stat-n"><?= $totalDepts ?></div><div class="stat-l">Departments</div></div>
        <div><div class="stat-n"><?= $totalVisitors ?></div><div class="stat-l">Today's Visitors</div></div>
      </div>
    </div>
    <div class="hero-cards">
      <?php if ($latestNews): ?>
      <a href="index.php?page=news_detail&id=<?= $latestNews['id'] ?>" class="hcard featured" style="text-decoration:none;display:block">
        <div class="hcard-badge">Latest News</div>
        <div class="hcard-title"><?= htmlspecialchars(mb_substr($latestNews['title'],0,60)) ?></div>
        <div class="hcard-meta"><?= date('F j, Y',strtotime($latestNews['date_posted'])) ?> &rarr;</div>
      </a>
      <?php endif; ?>
      <?php if (!empty($upcomingEvents)): ?>
      <a href="index.php?page=news_detail&id=<?= $upcomingEvents[0]['id'] ?>" class="hcard" style="text-decoration:none;display:block">
        <div class="hcard-badge" style="background:rgba(106,62,154,.25);color:#d4b8f0">Upcoming Event</div>
        <div class="hcard-title"><?= htmlspecialchars(mb_substr($upcomingEvents[0]['title'],0,60)) ?></div>
        <div class="hcard-meta">&#128197; <?= date('F j, Y',strtotime($upcomingEvents[0]['event_date'])) ?> &rarr;</div>
      </a>
      <?php elseif ($latestArt): ?>
      <a href="index.php?page=<?= $loggedIn ? 'detail&id=' . (int)$latestArt['id'] : 'login' ?>" class="hcard hcard-link" style="text-decoration:none;display:block">
        <div class="hcard-badge">Latest Artifact</div>
        <div class="hcard-title"><?= htmlspecialchars(mb_substr($latestArt['title'],0,60)) ?></div>
        <div class="hcard-meta">Origin: <?= htmlspecialchars($latestArt['origin']??'Labo') ?> &rarr;</div>
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (!empty($tickerItems)): ?>
<div class="ticker-wrap">
  <div class="ticker-inner">
    <div class="ticker-label">Latest</div>
    <?php foreach ($tickerItems as $i => $item): ?>
    <div class="t-slide <?= $item['type']==='event'?'event':'' ?> <?= $i===0?'active':'' ?>">
      <div class="t-content">
        <span class="pill <?= $item['type']==='event'?'pill-event':'pill-news' ?>"><?= $item['type']==='event'?'&#128197; Event':'&#128240; News' ?></span>
        <h4><?= htmlspecialchars($item['title']) ?></h4>
        <p><?= htmlspecialchars(mb_substr($item['content'],0,100)) ?>...</p>
        <a href="index.php?page=news_detail&id=<?= $item['id'] ?>" class="t-more">Read full story &rarr;</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($tickerItems)>1): ?>
    <div class="t-dots"><?php foreach ($tickerItems as $i=>$_): ?><button class="t-dot <?= $i===0?'active':'' ?>" onclick="showTick(<?= $i ?>)"></button><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<section class="home-news-section" id="home-news-anchor">
  <div class="wrap">
    <div class="news-tabs-row">
      <div>
        <div class="sec-label">Museum Chronicle</div>
        <h2 class="sec-title">News &amp; Events</h2>
      </div>
      <div class="news-tab-btns">
        <button class="ntab active" id="hntab-latest"   onclick="homeTab('latest',this)">&#128240; Latest News</button>
        <button class="ntab"        id="hntab-upcoming" onclick="homeTab('upcoming',this)">&#128197; Upcoming Events</button>
        <button class="ntab"        id="hntab-archive"  onclick="homeTab('archive',this)">&#128193; Archive</button>
        <a href="index.php?page=news" class="ntab" style="text-decoration:none">View All &rarr;</a>
      </div>
      <div class="news-tab-combo-wrap">
        <button type="button" class="news-tab-combo-btn" id="homeNewsComboBtn" aria-expanded="false" aria-controls="homeNewsComboMenu">
          <span class="news-tab-combo-label" id="homeNewsComboLabel">&#128240; Latest News</span>
          <span class="news-tab-combo-caret" aria-hidden="true">&#9662;</span>
        </button>
        <div class="news-tab-combo-menu" id="homeNewsComboMenu" role="listbox" aria-label="Choose News and Events view">
          <button type="button" class="news-tab-combo-opt is-active" data-tab="latest">&#128240; Latest News</button>
          <button type="button" class="news-tab-combo-opt" data-tab="upcoming">&#128197; Upcoming Events</button>
          <button type="button" class="news-tab-combo-opt" data-tab="archive">&#128193; Archive</button>
          <button type="button" class="news-tab-combo-opt" data-tab="all">View All &rarr;</button>
        </div>
      </div>
    </div>

    <!-- Latest News panel -->
    <div id="hnpanel-latest" class="home-news-panel">
      <?php if (empty($latestNewsList)): ?>
        <div class="empty-state"><div class="ei">&#128240;</div><h3>No news yet</h3><p>Check back soon.</p></div>
      <?php else: ?>
        <div class="news-grid-home"><?php foreach ($latestNewsList as $n) echo newsCard($n,$imgBase,$now); ?></div>
      <?php endif; ?>
    </div>

    <!-- Upcoming Events panel -->
    <div id="hnpanel-upcoming" class="home-news-panel" style="display:none">
      <?php if (empty($upcomingEvents)): ?>
        <div class="empty-state"><div class="ei">&#128197;</div><h3>No upcoming events</h3><p>Check back for future events.</p></div>
      <?php else: ?>
        <div class="news-grid-home"><?php foreach ($upcomingEvents as $n) echo newsCard($n,$imgBase,$now); ?></div>
      <?php endif; ?>
    </div>

    <!-- Archive panel -->
    <div id="hnpanel-archive" class="home-news-panel" style="display:none">
      <?php if (empty($oldNews)): ?>
        <div class="empty-state"><div class="ei">&#128193;</div><h3>No archived items</h3><p>Past events and old news appear here.</p></div>
      <?php else: ?>
        <div class="news-grid-home"><?php foreach ($oldNews as $n) echo newsCard($n,$imgBase,$now); ?></div>
      <?php endif; ?>
    </div>

  </div>
</section>

<script>
function homeTab(tab, btn) {
  // Hide all panels
  ['latest','upcoming','archive'].forEach(function(t) {
    var p = document.getElementById('hnpanel-' + t);
    if (p) p.style.display = 'none';
    var b = document.getElementById('hntab-' + t);
    if (b) b.classList.remove('active');
  });
  // Show selected
  var panel = document.getElementById('hnpanel-' + tab);
  if (panel) {
    panel.style.display = 'block';
    panel.classList.remove('is-entering');
    void panel.offsetWidth;
    panel.classList.add('is-entering');
    window.setTimeout(function() {
      panel.classList.remove('is-entering');
    }, 320);
  }
  if (btn) btn.classList.add('active');

  var comboLabel = document.getElementById('homeNewsComboLabel');
  var comboOpts = document.querySelectorAll('.news-tab-combo-opt[data-tab]');
  comboOpts.forEach(function(opt) {
    var isActive = opt.getAttribute('data-tab') === tab;
    opt.classList.toggle('is-active', isActive);
    if (isActive && comboLabel) {
      comboLabel.textContent = opt.textContent;
    }
  });
}

function homeTabSelect(tab) {
  if (tab === 'all') {
    window.location.href = 'index.php?page=news';
    return;
  }
  var btn = document.getElementById('hntab-' + tab);
  homeTab(tab, btn || null);
}

function initHomeNewsCombo() {
  var wrap = document.querySelector('.news-tab-combo-wrap');
  var btn = document.getElementById('homeNewsComboBtn');
  var menu = document.getElementById('homeNewsComboMenu');
  if (!wrap || !btn || !menu) return;

  function closeMenu() {
    wrap.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
  }

  btn.addEventListener('click', function() {
    var isOpen = wrap.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  menu.querySelectorAll('.news-tab-combo-opt').forEach(function(opt) {
    opt.addEventListener('click', function() {
      var tab = opt.getAttribute('data-tab') || 'latest';
      closeMenu();
      homeTabSelect(tab);
    });
  });

  document.addEventListener('click', function(e) {
    if (!wrap.contains(e.target)) closeMenu();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMenu();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHomeNewsCombo);
} else {
  initHomeNewsCombo();
}
</script>

<section class="home-artifacts">
  <div class="home-artifacts-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:18px;margin-bottom:8px">
      <div>
        <div class="sec-label">Collection</div>
        <h2 class="sec-title">Latest Acquisitions</h2>
        <p style="color:#9bb8cc;font-size:.92rem;margin-top:6px"><?= $loggedIn ? 'Browse our full collection below' : '&#128274; Sign the Digital Guestbook to unlock full artifact details' ?></p>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <?php if ($loggedIn): ?><a href="index.php?page=exhibits" class="btn-outline" style="color:#9bb8cc;border-color:rgba(155,184,204,.4)">View All Artifacts &rarr;</a><?php endif; ?>
      </div>
    </div>
    <div class="teaser-fader" id="teaserFader">
      <?php if (empty($teaserArtifacts)): ?>
        <div class="teaser-empty">No recent acquisitions yet.</div>
      <?php else: ?>
        <div class="teaser-scrollport" id="teaserScrollport" data-logged-in="<?= $loggedIn ? '1' : '0' ?>" data-login-url="index.php?page=login">
          <?php foreach ($teaserArtifacts as $art):
            $artHasImg = $art['image_path'] && file_exists($imgBase.$art['image_path']);
          ?>
            <?php if ($loggedIn): ?><a href="index.php?page=detail&id=<?= (int)$art['id'] ?>" class="teaser-card" style="text-decoration:none"><?php else: ?><div class="teaser-card" onclick="teaserClick(false,document.getElementById('teaserScrollport').dataset.loginUrl)"><?php endif; ?>
              <div class="teaser-img">
                <?php if ($artHasImg): ?><img src="uploads/<?= htmlspecialchars($art['image_path']) ?>" alt="<?= htmlspecialchars($art['title']) ?>" <?= !$loggedIn?'style="filter:brightness(.4) blur(2px)"':'' ?>><?php else: ?><div style="width:100%;height:100%;background:#243447;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:rgba(255,255,255,.2)">&#127994;</div><?php endif; ?>
                <?php if (!$loggedIn): ?><div class="teaser-blur"><div class="teaser-blur-icon">&#128274;</div></div><?php endif; ?>
              </div>
              <div class="teaser-body">
                <div class="teaser-title"><?= htmlspecialchars(mb_substr($art['title'],0,55)) ?></div>
                <?php if ($loggedIn): ?>
                  <div class="teaser-meta"><?= $art['cat_name']?'&#128193; '.htmlspecialchars($art['cat_name']).' &bull; ':'' ?><?= htmlspecialchars($art['origin']??'Labo') ?></div>
                <?php else: ?>
                  <div class="teaser-meta" style="color:#4a6a8a;font-style:italic">Details hidden &mdash; sign guestbook</div>
                  <a href="#" class="teaser-cta" onclick="event.preventDefault();event.stopPropagation();teaserClick(false,document.getElementById('teaserScrollport').dataset.loginUrl)">Sign Guestbook to Unlock</a>
                <?php endif; ?>
              </div>
            <?php if ($loggedIn): ?></a><?php else: ?></div><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="teaser-nav-bar" style="<?= count($teaserArtifacts) > 1 ? '' : 'display:none' ?>">
      <button type="button" id="teaserPrevBtn" class="teaser-nav-btn" aria-label="Previous acquisitions">&lt;</button>
      <span id="teaserPageStat" class="teaser-page-stat">1 / 1</span>
      <button type="button" id="teaserNextBtn" class="teaser-nav-btn" aria-label="Next acquisitions">&gt;</button>
    </div>
  </div>
</section>

<section class="home-calendar-section">
  <div class="wrap">
    <div class="sec-label">Schedule</div>
    <h2 class="sec-title">Event Calendar</h2>
    <p class="sec-sub" style="margin-bottom:36px">Click on highlighted dates to see scheduled events.</p>
    <div class="calendar-layout">
      <div class="calendar-wrap">
        <div class="cal-header">
          <button class="cal-nav" onclick="calNav(-1)">&#8249;</button>
          <h3 id="calMonthLabel">Loading...</h3>
          <button class="cal-nav" onclick="calNav(1)">&#8250;</button>
        </div>
        <div class="cal-days-header"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
        <div class="cal-days" id="calGrid"></div>
      </div>
      <div class="cal-events-panel">
        <h3 class="cal-events-title" id="calEventsTitle">Upcoming Events</h3>
        <div id="calEventsList">
          <?php if (empty($calEventsUpcoming)): ?>
            <p style="color:var(--text3);font-size:.9rem">No upcoming events scheduled.</p>
          <?php else: foreach ($calEventsUpcoming as $ev): ?>
            <a href="index.php?page=news_detail&id=<?= $ev['id'] ?>" class="cal-event-item" style="text-decoration:none;display:block">
              <div class="cal-event-date">&#128197; <?= date('F j, Y',strtotime($ev['event_date'])) ?></div>
              <div class="cal-event-title"><?= htmlspecialchars($ev['title']) ?></div>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
window.calendarEvents = <?= json_encode(array_map(function($e){return['id'=>$e['id'],'title'=>$e['title'],'event_date'=>$e['event_date'],'type'=>$e['type']];},$calEvents)) ?>;
</script>

<div style="background:linear-gradient(135deg,var(--navy),#243447);padding:80px 28px">
  <div class="about-strip-grid" style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:70px;align-items:center">
    <div>
      <div class="sec-label" style="color:var(--gold2)">About the Museum</div>
      <h2 class="sec-title" style="color:#fff">Guardians of Labo's Heritage</h2>
      <p style="color:#9bb8cc;line-height:1.8;margin-bottom:24px"><strong style="color:#fff">Museo de Labo</strong> preserves and showcases the town's key historical, cultural, and artistic heritage in one place.</p>
      <a href="index.php?page=about" class="btn-outline" style="color:#9bb8cc;border-color:rgba(155,184,204,.4)">Learn More &rarr;</a>
    </div>
    <ul style="list-style:none">
      <li style="display:flex;gap:13px;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.07)"><div style="width:36px;height:36px;border-radius:50%;background:rgba(201,146,42,.15);border:1px solid rgba(201,146,42,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0">&#128205;</div><div><strong style="color:#fff;display:block;font-size:.85rem">Location</strong><span style="color:#7a9eb5;font-size:.82rem">Municipal Hall Compound, Labo, Camarines Norte</span></div></li>
      <li style="display:flex;gap:13px;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.07)"><div style="width:36px;height:36px;border-radius:50%;background:rgba(201,146,42,.15);border:1px solid rgba(201,146,42,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0">&#128336;</div><div><strong style="color:#fff;display:block;font-size:.85rem">Hours</strong><span style="color:#7a9eb5;font-size:.82rem">Monday &ndash; Friday &middot; 8:00 AM &ndash; 5:00 PM</span></div></li>
      <li style="display:flex;gap:13px;padding:13px 0"><div style="width:36px;height:36px;border-radius:50%;background:rgba(201,146,42,.15);border:1px solid rgba(201,146,42,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0">&#127903;</div><div><strong style="color:#fff;display:block;font-size:.85rem">Admission</strong><span style="color:#7a9eb5;font-size:.82rem">Free &mdash; Please sign visitor logbook upon arrival</span></div></li>
    </ul>
  </div>
</div>
