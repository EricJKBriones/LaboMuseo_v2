<?php
// pages/news.php
$now     = date('Y-m-d');
$tab     = $_GET['tab'] ?? 'all';
$search  = trim($_GET['q'] ?? '');
$imgBase = __DIR__ . '/../uploads/';

// Build search condition
$searchSql    = $search ? " AND (title LIKE ? OR content LIKE ?)" : "";
$searchParams = $search ? ["%$search%", "%$search%"] : [];

// Active posts only (not admin-archived)
$allNews    = dbQuery("SELECT * FROM news_events WHERE is_archived=0 $searchSql ORDER BY date_posted DESC, id DESC", $searchParams);
$latestNews = dbQuery("SELECT * FROM news_events WHERE is_archived=0 AND type='news' $searchSql ORDER BY date_posted DESC, id DESC", $searchParams);
$upcoming   = dbQuery("SELECT * FROM news_events WHERE is_archived=0 AND type='event' AND event_date>=? $searchSql ORDER BY event_date ASC", array_merge([$now], $searchParams));
// Archive tab = admin-archived posts (is_archived=1) OR past events (event_date already passed)
$archive    = dbQuery("SELECT * FROM news_events WHERE (is_archived=1 OR (type='event' AND event_date IS NOT NULL AND event_date < ?)) $searchSql ORDER BY COALESCE(event_date, date_posted) DESC", array_merge([$now], $searchParams));

function newsCard(array $n, string $imgBase, string $now): string {
    $isEv   = $n['type'] === 'event';
    $isPast = $isEv && $n['event_date'] && $n['event_date'] < $now;
    $hasImg = $n['image_path'] && file_exists($imgBase.$n['image_path']);
    $url    = 'index.php?page=news_detail&id='.(int)$n['id'];
    $cls    = 'nc'.($isEv?' is-event':'').($isPast?' is-past':'');
    if ($isPast)   $badge = '<span class="pill pill-past">&#128193; Archive</span>';
    elseif ($isEv) $badge = '<span class="pill pill-event">&#128197; Upcoming Event</span>';
    else           $badge = '<span class="pill pill-news">&#128240; Museum News</span>';
    $meta    = ($isEv && $n['event_date']) ? 'Scheduled: '.date('F j, Y',strtotime($n['event_date'])) : 'Posted: '.date('F j, Y',strtotime($n['date_posted']));
    $imgBg   = ($isEv && !$hasImg) ? ' style="background:linear-gradient(135deg,#2d1b4e,#4a2b7a)"' : '';
    $imgHtml = $hasImg ? '<img src="uploads/'.htmlspecialchars($n['image_path']).'" alt="'.htmlspecialchars($n['title']).'">' : '<div class="nc-img-ph">'.($isEv?'&#128197;':'&#128240;').'</div>';
    $lbl     = $isEv ? 'View Details' : 'Read More';
    return '<a href="'.$url.'" class="'.$cls.'" style="text-decoration:none">
      <div class="nc-img"'.$imgBg.'>'.$imgHtml.'</div>
      <div class="nc-body">'.$badge.'
        <div class="nc-meta">'.$meta.'</div>
        <h3 class="nc-title">'.htmlspecialchars($n['title']).'</h3>
        <p class="nc-text">'.htmlspecialchars(mb_substr($n['content'],0,180)).'...</p>
        <div class="nc-foot"><span class="btn-ghost">'.$lbl.' &rarr;</span></div>
      </div></a>';
}

// Which items to show
switch($tab) {
    case 'latest':   $items=$latestNews; $emptyIcon='&#128240;'; $emptyMsg='No news articles found.';    $emptyDetail=''; break;
    case 'upcoming': $items=$upcoming;   $emptyIcon='&#128197;'; $emptyMsg='No upcoming events found.';  $emptyDetail=''; break;
    case 'archive':  $items=$archive;    $emptyIcon='&#128193;'; $emptyMsg='No archived items yet.';     $emptyDetail='Past events and admin-archived posts will appear here.'; break;
    default:         $items=$allNews;    $emptyIcon='&#128235;'; $emptyMsg='No posts found.';             $emptyDetail=''; $tab='all';
}

$baseUrl = 'index.php?page=news';
?>

<div class="page-hero">
  <div class="sec-label">Museum Chronicle</div>
  <h1 class="sec-title">News &amp; Events</h1>
  <p class="page-hero-sub">Stay updated with the latest announcements, upcoming events, and past stories.</p>

  <!-- Tab buttons -->
  <div class="news-filter-bar" style="margin-top:24px">
    <a href="<?= $baseUrl ?>&tab=all<?= $search?'&q='.urlencode($search):'' ?>"      class="nfbtn <?= $tab==='all'     ?'active':'' ?>" style="text-decoration:none">All</a>
    <a href="<?= $baseUrl ?>&tab=latest<?= $search?'&q='.urlencode($search):'' ?>"   class="nfbtn <?= $tab==='latest'  ?'active':'' ?>" style="text-decoration:none">&#128240; Latest News</a>
    <a href="<?= $baseUrl ?>&tab=upcoming<?= $search?'&q='.urlencode($search):'' ?>" class="nfbtn <?= $tab==='upcoming'?'active':'' ?>" style="text-decoration:none">&#128197; Upcoming Events</a>
    <a href="<?= $baseUrl ?>&tab=archive<?= $search?'&q='.urlencode($search):'' ?>"  class="nfbtn <?= $tab==='archive' ?'active':'' ?>" style="text-decoration:none">&#128193; Archive</a>
  </div>
  <div style="height:28px"></div>
</div>

<div class="wrap" style="padding-top:36px">

  <!-- Search Bar -->
  <div style="max-width:560px;margin:0 auto 36px">
    <form method="GET" action="index.php" style="display:flex;gap:0;background:#fff;border-radius:99px;box-shadow:0 2px 16px rgba(27,42,59,.12);border:1.5px solid var(--cream);overflow:hidden;transition:border-color .2s" id="newsSearchForm">
      <input type="hidden" name="page" value="news">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <input type="text" name="q" id="newsSearchInput" placeholder="&#128269;  Search news &amp; events..." value="<?= htmlspecialchars($search) ?>"
        style="flex:1;padding:13px 20px;border:none;outline:none;font-family:'DM Sans',sans-serif;font-size:.95rem;background:transparent;color:var(--text)"
        oninput="liveNewsSearch(this.value)">
      <?php if ($search): ?>
        <a href="<?= $baseUrl ?>&tab=<?= $tab ?>" style="padding:0 16px;display:flex;align-items:center;color:var(--text3);text-decoration:none;font-size:1.1rem" title="Clear search">&#10005;</a>
      <?php endif; ?>
      <button type="submit" style="padding:13px 24px;background:var(--gold);color:#fff;border:none;cursor:pointer;font-weight:600;font-family:'DM Sans',sans-serif;font-size:.9rem;transition:background .2s" onmouseover="this.style.background='#b8811c'" onmouseout="this.style.background='var(--gold)'">Search</button>
    </form>
    <?php if ($search): ?>
      <p style="text-align:center;margin-top:10px;font-size:.85rem;color:var(--text3)">
        Showing <strong style="color:var(--navy)"><?= count($items) ?></strong> result<?= count($items)!=1?'s':'' ?> for &ldquo;<strong><?= htmlspecialchars($search) ?></strong>&rdquo;
      </p>
    <?php endif; ?>
  </div>

  <!-- Results -->
  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="ei"><?= $emptyIcon ?></div>
      <h3><?= $emptyMsg ?></h3>
      <?php if ($search): ?>
        <p>Try different keywords or <a href="<?= $baseUrl ?>&tab=<?= $tab ?>" style="color:var(--gold)">clear the search</a>.</p>
      <?php elseif ($tab === 'archive'): ?>
        <p><?= $emptyDetail ?? 'Past events and admin-archived posts will appear here.' ?></p>
        <p style="font-size:.82rem;color:var(--text3);margin-top:8px">Tip: Go to <a href="admin/news.php" style="color:var(--gold)">Admin → News</a> and click <strong>&#128193; Archive</strong> on any post to move it here.</p>
      <?php else: ?>
        <p>Check back soon for updates.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if (!$search): ?>
      <p style="margin-bottom:18px;color:var(--text3);font-size:.84rem">
        Showing <strong style="color:var(--navy)"><?= count($items) ?></strong>
        <?= $tab==='all'?'total posts':($tab==='latest'?'news articles':($tab==='upcoming'?'upcoming events':'archived items')) ?>
      </p>
    <?php endif; ?>
    <div class="news-grid" id="newsResultsGrid">
      <?php foreach ($items as $n): echo newsCard($n,$imgBase,$now); endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
// Live search: filter visible cards after 2+ characters typed
var searchTimer = null;
function liveNewsSearch(val) {
  clearTimeout(searchTimer);
  if (val.length < 2) {
    // Show all cards if less than 2 chars
    document.querySelectorAll('#newsResultsGrid .nc').forEach(function(c){ c.style.display=''; });
    return;
  }
  // Debounce 300ms
  searchTimer = setTimeout(function() {
    var q = val.toLowerCase();
    document.querySelectorAll('#newsResultsGrid .nc').forEach(function(card) {
      var text = card.textContent.toLowerCase();
      card.style.display = text.includes(q) ? '' : 'none';
    });
  }, 300);
}
</script>
