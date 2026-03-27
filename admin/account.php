<?php
// admin/account.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$admin = dbOne("SELECT id, username, password FROM admins WHERE id = ?", [$adminId]);
if (!$admin) {
    session_destroy();
    header('Location: ../index.php?page=login');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_username') {
        $newUsername = trim($_POST['username'] ?? '');

        if ($newUsername === '') {
            $errors[] = 'Username is required.';
        } elseif (strlen($newUsername) < 4) {
            $errors[] = 'Username must be at least 4 characters.';
        } else {
            $exists = dbOne("SELECT id FROM admins WHERE username = ? AND id <> ?", [$newUsername, $adminId]);
            if ($exists) {
                $errors[] = 'That username is already in use.';
            } else {
                $stmt = getDB()->prepare("UPDATE admins SET username = ? WHERE id = ?");
                $stmt->execute([$newUsername, $adminId]);
                $admin['username'] = $newUsername;
                $success = 'Username updated successfully.';
            }
        }
    }

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validCurrent = ($currentPassword === $admin['password']) || password_verify($currentPassword, $admin['password']);

        if (!$validCurrent) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = getDB()->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $adminId]);
            $admin['password'] = $hashed;
            $success = 'Password updated successfully.';
        }
    }
}

$pageTitle = 'Account Settings — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>

  <main class="adm-main">
    <div class="adm-welcome">
      <h2>&#128274; Account Settings</h2>
      <p>Manage your admin username and password.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert-err">
        <?php foreach ($errors as $i => $err): ?>
          <?= $i ? '<br>' : '' ?>&#10007; <?= htmlspecialchars($err) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert-ok">&#10003; <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="adm-account-grid">
      <section class="adm-account-card">
        <h3>Change Username</h3>
        <p>Current username: <strong><?= htmlspecialchars($admin['username']) ?></strong></p>
        <form method="POST" action="account.php">
          <input type="hidden" name="action" value="update_username">
          <div class="fg2">
            <div class="full">
              <label class="al">New Username</label>
              <input type="text" name="username" class="ai" value="<?= htmlspecialchars($admin['username']) ?>" minlength="4" required>
            </div>
          </div>
          <button type="submit" class="btn-save" style="margin-top:12px">Save Username</button>
        </form>
      </section>

      <section class="adm-account-card">
        <h3>Change Password</h3>
        <p>Use at least 8 characters for better security.</p>
        <form method="POST" action="account.php">
          <input type="hidden" name="action" value="update_password">
          <div class="fg2">
            <div class="full">
              <label class="al">Current Password</label>
              <input type="password" name="current_password" class="ai" autocomplete="current-password" required>
            </div>
            <div>
              <label class="al">New Password</label>
              <input type="password" name="new_password" class="ai" minlength="8" autocomplete="new-password" required>
            </div>
            <div>
              <label class="al">Confirm New Password</label>
              <input type="password" name="confirm_password" class="ai" minlength="8" autocomplete="new-password" required>
            </div>
          </div>
          <button type="submit" class="btn-save" style="margin-top:12px">Save Password</button>
        </form>
      </section>
    </div>
  </main>
</div>

<?php require_once 'admin_footer.php'; ?>
