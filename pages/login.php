<?php
// pages/login.php
if (isLoggedIn()) {
    header('Location: index.php?page=home');
    exit;
}
$loginError = $_SESSION['login_error'] ?? '';
$regError   = $_SESSION['reg_error'] ?? '';
$mode = strtolower(trim($_GET['mode'] ?? ''));
$showAdminPanel = !empty($loginError) || $mode === 'admin';
$showGuestPanel = !$showAdminPanel;
unset($_SESSION['login_error'], $_SESSION['reg_error']);
?>

<div class="login-page">
  <div class="login-wrap">

    <?php if ($loginError || $regError): ?>
      <div class="msg-box msg-err"><?= htmlspecialchars($loginError ?: $regError) ?></div>
    <?php endif; ?>

    <!-- Admin Panel (hidden by default) -->
    <div id="adminPanel" style="display:<?= $showAdminPanel ? 'block' : 'none' ?>">
      <div class="lcard blue-top">
        <div class="lcard-head">
          <div class="icon">&#9881;</div>
          <h2>Admin Login</h2>
          <p>Access the museum control panel</p>
        </div>
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="admin_login">
          <div class="fg"><label>Username</label><input type="text" name="username" class="fc" required autocomplete="username"></div>
          <div class="fg"><label>Password</label><input type="password" name="password" class="fc" required autocomplete="current-password"></div>
          <button type="submit" class="lbtn blue">Login to Dashboard</button>
        </form>
      </div>
      <div class="l-link-row"><a href="#" onclick="showGuestPanel();return false;">&#8592; Back to Guestbook</a></div>
    </div>

    <!-- Guest Panel -->
    <div id="guestPanel" style="display:<?= $showGuestPanel ? 'block' : 'none' ?>">
      <div class="lcard">
        <div class="lcard-head">
          <div class="icon">&#128220;</div>
          <h2>Sign Digital Guestbook</h2>
          <p>Register to access the artifact catalog</p>
        </div>
        <form method="POST" action="index.php">
          <input type="hidden" name="action" value="guest_register">
          <div class="fg">
            <label>Visitor Type</label>
            <select id="visitorType" name="visitor_type" class="fc" onchange="toggleGroupFields()">
              <option value="Individual">Individual / Family</option>
              <option value="Group">School / Organization / Group</option>
            </select>
          </div>
          <div class="gfields" id="groupFields">
            <div class="fg"><label>Organization / School Name</label><input type="text" name="organization" class="fc" placeholder="e.g., Labo National High School"></div>
            <div class="frow">
              <div class="fg"><label>No. of Males</label><input type="number" name="male_count" class="fc" min="0" value="0"></div>
              <div class="fg"><label>No. of Females</label><input type="number" name="female_count" class="fc" min="0" value="0"></div>
            </div>
          </div>
          <div class="fg"><label id="nameLbl">Full Name</label><input type="text" name="guest_name" class="fc" placeholder="Juan Dela Cruz" required></div>
          <div class="frow">
            <div class="fg">
              <label>Gender</label>
              <select name="gender" class="fc">
                <option>Male</option><option>Female</option><option>Other</option>
              </select>
            </div>
            <div class="fg"><label>Contact Number</label><input type="tel" name="contact_no" class="fc" placeholder="09123456789" maxlength="11" required></div>
          </div>
          <div class="frow">
            <div class="fg"><label>Residence</label><input type="text" name="residence" class="fc" placeholder="e.g., Labo" required></div>
            <div class="fg"><label>Nationality</label><input type="text" name="nationality" class="fc" value="Filipino" required></div>
          </div>
          <div class="fg"><label>Purpose of Visit</label><input type="text" name="purpose" class="fc" placeholder="e.g., Research, Tour" required></div>
          <button type="submit" class="lbtn gold">&#10022; Sign &amp; Enter Catalog</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function showAdminPanel(){document.getElementById('adminPanel').style.display='block';document.getElementById('guestPanel').style.display='none';}
function showGuestPanel(){document.getElementById('adminPanel').style.display='none';document.getElementById('guestPanel').style.display='block';}
</script>
