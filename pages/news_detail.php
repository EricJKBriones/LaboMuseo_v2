<?php
// pages/news_detail.php
$id  = (int)($_GET['id'] ?? 0);
$now = date('Y-m-d');

if (!$id) {
    header('Location: index.php?page=news');
    exit;
}

$item = dbOne("SELECT * FROM news_events WHERE id=?", [$id]);
if (!$item) {
    header('Location: index.php?page=news');
    exit;
}

$isEvent = $item['type'] === 'event';
$isPast  = $isEvent && $item['event_date'] && $item['event_date'] < $now;

// Related items (same type, not this one)
$related = dbQuery(
    "SELECT * FROM news_events WHERE type=? AND id!=? ORDER BY date_posted DESC, id DESC LIMIT 3",
    [$item['type'], $id]
);

// Image path helper
$imgBase = __DIR__ . '/../uploads/';
$hasImg  = $item['image_path'] && file_exists($imgBase . $item['image_path']);
?>

<!-- Detail Hero -->
<div class="page-hero" style="<?= $isEvent ? 'background:linear-gradient(135deg,#1a0d2e,#2d1b4e,#3a2060)' : '' ?>">
  <div style="position:relative;z-index:1">
    <div class="sec-label" style="justify-content:center">
      <?php if ($isPast): ?>
        &#128193; Archive
      <?php elseif ($isEvent): ?>
        &#128197; Upcoming Event
      <?php else: ?>
        &#128240; Museum News
      <?php endif; ?>
    </div>
    <h1 class="sec-title" style="max-width:780px;margin:0 auto 12px"><?= htmlspecialchars($item['title']) ?></h1>
    <p class="page-hero-sub">
      <?php if ($isEvent && $item['event_date']): ?>
        &#128197; Scheduled: <strong style="color:var(--gold2)"><?= date('F j, Y', strtotime($item['event_date'])) ?></strong>
      <?php else: ?>
        &#128197; Posted: <?= date('F j, Y', strtotime($item['date_posted'])) ?>
      <?php endif; ?>
    </p>
  </div>
</div>

<!-- Back Button -->
<div style="max-width:900px;margin:0 auto;padding:28px 28px 0">
  <a href="index.php?page=news" style="display:inline-flex;align-items:center;gap:7px;color:var(--text3);font-size:.88rem;font-weight:500;text-decoration:none;transition:color .2s" onmouseover="this.style.color='var(--navy)'" onmouseout="this.style.color='var(--text3)'">
    &#8592; Back to News &amp; Events
  </a>
</div>

<!-- Main Content -->
<div style="max-width:900px;margin:0 auto;padding:32px 28px 70px">

  <!-- Full Image -->
  <?php if ($hasImg): ?>
  <div style="border-radius:14px;overflow:hidden;margin-bottom:36px;box-shadow:var(--shadow2);max-height:480px">
    <img src="uploads/<?= htmlspecialchars($item['image_path']) ?>"
         alt="<?= htmlspecialchars($item['title']) ?>"
         style="width:100%;max-height:480px;object-fit:cover;display:block">
  </div>
  <?php endif; ?>

  <!-- Tags Row -->
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px">
    <?php if ($isPast): ?>
      <span class="pill pill-past">&#128193; Archive</span>
    <?php elseif ($isEvent): ?>
      <span class="pill pill-event">&#128197; Upcoming Event</span>
    <?php else: ?>
      <span class="pill pill-news">&#128240; Museum News</span>
    <?php endif; ?>

    <?php if ($isEvent && $item['event_date']): ?>
    <div style="display:flex;align-items:center;gap:6px;background:<?= $isPast ? 'var(--ivory2)' : 'rgba(106,62,154,.08)' ?>;border:1px solid <?= $isPast ? 'var(--cream)' : 'rgba(106,62,154,.2)' ?>;padding:6px 14px;border-radius:99px;font-size:.82rem;font-weight:600;color:<?= $isPast ? 'var(--text3)' : 'var(--purple)' ?>">
      &#128197; <?= date('l, F j, Y', strtotime($item['event_date'])) ?>
      <?php if (!$isPast): ?>
        &nbsp;&bull;&nbsp;
        <?php
          $daysLeft = (int)round((strtotime($item['event_date']) - time()) / 86400);
          echo $daysLeft <= 0 ? 'Today!' : ($daysLeft === 1 ? '1 day away' : $daysLeft . ' days away');
        ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <span style="font-size:.8rem;color:var(--text3)">&#128197; Posted: <?= date('F j, Y', strtotime($item['date_posted'])) ?></span>
  </div>

  <!-- Article Content -->
  <div style="background:#fff;border-radius:14px;padding:36px;box-shadow:var(--shadow);border:1px solid var(--cream)">
    <div style="font-size:1.05rem;line-height:1.9;color:var(--text2)">
      <?= nl2br(htmlspecialchars($item['content'])) ?>
    </div>
  </div>

  <!-- Share / Action -->
  <div style="margin-top:28px;padding:20px 24px;background:var(--ivory2);border-radius:12px;border:1px solid var(--cream);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
    <div>
      <div style="font-size:.78rem;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Want to learn more?</div>
      <div style="font-size:.92rem;color:var(--text2)">Visit us at the museum or sign our Digital Guestbook to access the full catalog.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <?php if (!isLoggedIn()): ?>
        <a href="index.php?page=login" class="btn-gold">Sign Guestbook &#10022;</a>
      <?php endif; ?>
      <a href="index.php?page=news" class="btn-outline">&#8592; All News</a>
    </div>
  </div>

</div>

<!-- Related Items -->
<?php if (!empty($related)): ?>
<div style="background:var(--ivory2);padding:60px 28px;border-top:1px solid var(--cream)">
  <div style="max-width:1100px;margin:0 auto">
    <div class="sec-label">More <?= $isEvent ? 'Events' : 'News' ?></div>
    <h2 class="sec-title" style="margin-bottom:32px">Related Posts</h2>
    <div class="news-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
      <?php foreach ($related as $r):
        $rIsEv  = $r['type'] === 'event';
        $rIsPast= $rIsEv && $r['event_date'] && $r['event_date'] < $now;
        $rHasImg= $r['image_path'] && file_exists($imgBase . $r['image_path']);
      ?>
      <a href="index.php?page=news_detail&id=<?= $r['id'] ?>" class="nc <?= $rIsEv?'is-event':'' ?> <?= $rIsPast?'is-past':'' ?>" style="text-decoration:none;cursor:pointer">
        <div class="nc-img" <?= $rIsEv && !$rHasImg ? 'style="background:linear-gradient(135deg,#2d1b4e,#4a2b7a)"' : '' ?>>
          <?php if ($rHasImg): ?>
            <img src="uploads/<?= htmlspecialchars($r['image_path']) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
          <?php else: ?>
            <div class="nc-img-ph"><?= $rIsEv?'&#128197;':'&#128240;' ?></div>
          <?php endif; ?>
        </div>
        <div class="nc-body">
          <?php if ($rIsPast): ?>
            <span class="pill pill-past">&#128193; Archive</span>
          <?php elseif ($rIsEv): ?>
            <span class="pill pill-event">&#128197; Event</span>
          <?php else: ?>
            <span class="pill pill-news">&#128240; News</span>
          <?php endif; ?>
          <div class="nc-meta"><?= $rIsEv && $r['event_date'] ? date('F j, Y',strtotime($r['event_date'])) : date('F j, Y',strtotime($r['date_posted'])) ?></div>
          <h3 class="nc-title"><?= htmlspecialchars($r['title']) ?></h3>
          <p class="nc-text"><?= htmlspecialchars(mb_substr($r['content'],0,120)) ?>...</p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
