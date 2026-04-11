<?php
// admin/account.php
require_once '../includes/init.php';
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
                $_SESSION['account_success'] = 'Username updated successfully.';
                header('Location: account.php');
                exit;
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
            $_SESSION['account_success'] = 'Password updated successfully.';
            header('Location: account.php');
            exit;
        }
    }

      if ($action === 'update_logo') {
        if (empty($_FILES['site_logo']['name'])) {
          $errors[] = 'Please choose a logo file to upload.';
        } elseif (!isset($_FILES['site_logo']['error']) || $_FILES['site_logo']['error'] !== UPLOAD_ERR_OK) {
          $errors[] = 'Logo upload failed. Please try again.';
        } else {
          $tmpPath = $_FILES['site_logo']['tmp_name'] ?? '';
          $size = (int)($_FILES['site_logo']['size'] ?? 0);
          $mime = '';
          if ($tmpPath && is_uploaded_file($tmpPath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
              $mime = (string)finfo_file($finfo, $tmpPath);
              finfo_close($finfo);
            }
          }

          if ($size <= 0 || $size > 5 * 1024 * 1024) {
            $errors[] = 'Logo must be a valid PNG image up to 5MB.';
          } elseif ($mime !== 'image/png') {
            $errors[] = 'Only PNG files are allowed for the website logo.';
          } else {
            $logoPath = __DIR__ . '/../uploads/logo.png';
            if (!move_uploaded_file($tmpPath, $logoPath)) {
              $errors[] = 'Unable to save the new logo file.';
            } else {
              $_SESSION['account_success'] = 'Website logo updated successfully.';
              header('Location: account.php');
              exit;
            }
          }
        }
      }
}

// Check for success message from session (one-time display)
if (isset($_SESSION['account_success'])) {
    $success = $_SESSION['account_success'];
    unset($_SESSION['account_success']);
}

$pageTitle = 'Account Settings — ' . SITE_NAME;
require_once 'admin_header.php';

$logoFile = __DIR__ . '/../uploads/logo.png';
$logoVersion = file_exists($logoFile) ? (string)filemtime($logoFile) : (string)time();
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
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          showSileoToastBar({
            title: 'Success',
            message: '<?= htmlspecialchars($success, ENT_QUOTES) ?>',
            variant: 'success',
            position: 'top-right',
            duration: 3000
          });
        });
      </script>
    <?php endif; ?>

    <div class="adm-account-grid">
      <section class="adm-account-card">
        <h3>Change Username</h3>
        <p>Current username: <strong><?= htmlspecialchars($admin['username']) ?></strong></p>
        <form method="POST" action="account.php" data-account-confirm="username">
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
        <form method="POST" action="account.php" data-account-confirm="password">
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

      <section class="adm-account-card adm-account-card--full">
        <h3>Change Website Logo</h3>
        <p>Upload a PNG logo (max 5MB) to update the public website header logo.</p>
        <form method="POST" action="account.php" enctype="multipart/form-data" data-account-confirm="logo">
          <input type="hidden" name="action" value="update_logo">
          <div class="fg2">
            <div>
              <label class="al">Current Logo</label>
              <div style="width:82px;height:82px;border-radius:50%;border:2px solid rgba(201,146,42,.35);background:#1b2a3b;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <?php if (file_exists($logoFile)): ?>
                  <img src="../uploads/logo.png?v=<?= htmlspecialchars($logoVersion, ENT_QUOTES) ?>" alt="Current website logo" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                  <span style="color:#c9922a;font-size:1.6rem;line-height:1;">&#9711;</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="full">
              <label class="al">New Logo (PNG)</label>
              <input type="file" name="site_logo" class="ai" accept="image/png" required>
            </div>
          </div>
          <button type="submit" class="btn-save" style="margin-top:12px">Update Logo</button>
        </form>
      </section>
    </div>
  </main>
</div>

<?php require_once 'admin_footer.php'; ?>
