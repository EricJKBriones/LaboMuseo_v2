<?php
// admin/admin_footer.php
if (!isset($quickArtifactCategories) && function_exists('dbQuery')) {
  $quickArtifactCategories = dbQuery("SELECT id, name FROM categories ORDER BY name ASC");
}
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if ($forwardedProto !== '') {
  $protocol = strtolower(trim(explode(',', $forwardedProto)[0]));
} elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
  $protocol = $_SERVER['REQUEST_SCHEME'];
} else {
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}
$base = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\') . '/';
?>
<footer style="background:var(--navy3);color:#8a9db0;text-align:center;padding:20px;border-top:2px solid var(--gold);font-size:.82rem;margin-top:auto">
  <strong style="color:var(--gold2)"><?= SITE_NAME ?></strong> &mdash; Admin Panel &bull; &copy; <?= date('Y') ?> &bull; &copy; <?= projectCreditsHtml() ?>
</footer>
<div class="adm-quick-dock" id="adminQuickDock">
  <button type="button" class="adm-quick-fab" id="adminQuickFab" aria-label="Quick actions" aria-expanded="false">
    <img src="<?= $base ?>assets/Icon/quick_action.png" alt="" aria-hidden="true">
  </button>
  <div class="adm-quick-menu" id="adminQuickMenu" aria-hidden="true" aria-label="Quick actions menu">
    <div class="adm-quick-menu-head">
      <span>Quick Actions</span>
      <small>Admin only</small>
    </div>
    <button type="button" class="adm-quick-item" data-quick-target="quickAddArtifactForm">
      <img src="<?= $base ?>assets/Icon/add.png" data-png="<?= $base ?>assets/Icon/add.png" data-gif="<?= $base ?>assets/Icon/add.gif" alt="" aria-hidden="true">
      <span>Add Artifact</span>
    </button>
    <button type="button" class="adm-quick-item" data-quick-target="quickAddDeptForm">
      <img src="<?= $base ?>assets/Icon/add_department.png?rev=<?= time() ?>" data-png="<?= $base ?>assets/Icon/add_department.png?rev=<?= time() ?>" data-gif="<?= $base ?>assets/Icon/add_department.gif?rev=<?= time() ?>" alt="" aria-hidden="true">
      <span>Add Department</span>
    </button>
    <button type="button" class="adm-quick-item" data-quick-target="quickAddNewsForm">
      <img src="<?= $base ?>assets/Icon/post_news.png?rev=<?= time() ?>" data-png="<?= $base ?>assets/Icon/post_news.png?rev=<?= time() ?>" data-gif="<?= $base ?>assets/Icon/post_news.gif?rev=<?= time() ?>" alt="" aria-hidden="true">
      <span>Post News</span>
    </button>
    <a href="<?= $base ?>admin/export.php?format=xlsx" class="adm-quick-item adm-quick-link">
      <img src="<?= $base ?>assets/Icon/export1.png" data-png="<?= $base ?>assets/Icon/export1.png" data-gif="<?= $base ?>assets/Icon/export1.gif" alt="" aria-hidden="true">
      <span>Export Visitors</span>
    </a>
  </div>
</div>
<div class="adm-quick-form-overlay" id="adminQuickOverlay" aria-hidden="true">
  <button type="button" class="adm-quick-backdrop" id="adminQuickBackdrop" aria-label="Close quick action forms"></button>
  <div class="adm-quick-form-shell">
    <div class="adm-form adm-quick-form-panel" id="quickAddArtifactForm">
      <div class="adm-quick-form-head">
        <h3>New Artifact</h3>
        <button type="button" class="adm-quick-close" onclick="togglePanel('quickAddArtifactForm')" aria-label="Close artifact form">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= $base ?>admin/artifacts.php">
        <input type="hidden" name="action" value="insert">
        <div class="fg2">
          <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" required></div>
          <div>
            <label class="al">Department</label>
            <select name="category_id" class="ai">
              <option value="">-- Select --</option>
              <?php if (!empty($quickArtifactCategories)): foreach ($quickArtifactCategories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
          <div><label class="al">Year / Period</label><input type="text" name="artifact_year" class="ai" placeholder="e.g., 18th Century"></div>
          <div><label class="al">Origin</label><input type="text" name="origin" class="ai" placeholder="e.g., Labo"></div>
          <div class="full"><label class="al">Donated By</label><input type="text" name="donated_by" class="ai"></div>
          <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">Description *</label><textarea name="description" class="ai" required></textarea></div>
        </div>
        <div class="adm-quick-form-actions">
          <button type="submit" class="btn-save">Save Artifact</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddArtifactForm')">Cancel</button>
        </div>
      </form>
    </div>

    <div class="adm-form adm-quick-form-panel" id="quickAddDeptForm">
      <div class="adm-quick-form-head">
        <h3>New Department</h3>
        <button type="button" class="adm-quick-close" onclick="togglePanel('quickAddDeptForm')" aria-label="Close department form">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= $base ?>admin/departments.php">
        <input type="hidden" name="action" value="insert">
        <div class="fg2">
          <div><label class="al">Name *</label><input type="text" name="name" class="ai" required></div>
          <div><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">Description</label><textarea name="description" class="ai"></textarea></div>
        </div>
        <div class="adm-quick-form-actions">
          <button type="submit" class="btn-save">Save Department</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddDeptForm')">Cancel</button>
        </div>
      </form>
    </div>

    <div class="adm-form adm-quick-form-panel" id="quickAddNewsForm">
      <div class="adm-quick-form-head">
        <h3>New Post</h3>
        <button type="button" class="adm-quick-close" onclick="togglePanel('quickAddNewsForm')" aria-label="Close news form">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= $base ?>admin/news.php?view=active">
        <input type="hidden" name="action" value="insert">
        <div class="fg2">
          <div class="full"><label class="al">Title *</label><input type="text" name="title" class="ai" required></div>
          <div>
            <label class="al">Type</label>
            <select name="type" class="ai" onchange="toggleEvDate('quickNewsEventDate',this.value)">
              <option value="news">Museum News</option>
              <option value="event">Upcoming Event</option>
            </select>
          </div>
          <div><label class="al">Event Date <small style="color:#aaa">(for events only)</small></label><input type="date" name="event_date" id="quickNewsEventDate" class="ai"></div>
          <div class="full"><label class="al">Upload Image</label><input type="file" name="image_file" class="ai" accept="image/*"></div>
          <div class="full"><label class="al">Content *</label><textarea name="content" class="ai" rows="5" required></textarea></div>
        </div>
        <div class="adm-quick-form-actions">
          <button type="submit" class="btn-save" data-icon-name="post_news">Publish</button>
          <button type="button" class="btn-cancel-f" onclick="togglePanel('quickAddNewsForm')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div id="sileoToastHost" class="sileo-toast-host" aria-live="polite" aria-atomic="true"></div>
<div id="pageLoadingOverlay" class="page-loading-overlay" aria-hidden="true">
  <div class="page-loading-card">
    <img src="<?= $base ?>assets/Icon/loading.gif" alt="Loading" class="page-loading-icon">
    <div class="page-loading-text">Loading...</div>
  </div>
</div>
<script>
window.__admSubmitTimers = window.__admSubmitTimers || new WeakMap();
function adminDebounceSubmit(form, delay) {
  if (!form) return;
  var timers = window.__admSubmitTimers;
  var prev = timers.get(form);
  if (prev) clearTimeout(prev);
  var wait = typeof delay === 'number' ? delay : 700;
  var t = setTimeout(function() {
    form.submit();
  }, wait);
  timers.set(form, t);
}
</script>
<script src="<?= $base ?>assets/js/main.js"></script>
</body>
</html>
