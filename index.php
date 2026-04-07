<?php
// ============================================================
// index.php — Front-end router
// ============================================================
require_once 'includes/db.php';
sessionStart();

// ── Handle logout ────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php?page=home');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'admin_login') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $row  = dbOne("SELECT * FROM admins WHERE username=?", [$user]);
        // Support both plain password (seed) and hashed
        $ok = $row && ($pass === $row['password'] || password_verify($pass, $row['password']));
        if ($ok) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['id'];
            header('Location: admin/index.php');
            exit;
        }
        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: index.php?page=login');
        exit;
    }

    if ($_POST['action'] === 'guest_register') {
        $type    = $_POST['visitor_type'] ?? 'Individual';
        $name    = trim($_POST['guest_name'] ?? '');
        $gender  = $_POST['gender'] ?? 'Other';
        $contact = trim($_POST['contact_no'] ?? '');
        $res     = trim($_POST['residence'] ?? '');
        $nat     = trim($_POST['nationality'] ?? 'Filipino');
        $purpose = trim($_POST['purpose'] ?? '');

        if (!$name || !$contact || !$res || !$nat || !$purpose) {
            $_SESSION['reg_error'] = 'Please fill in all required fields.';
            header('Location: index.php?page=login');
            exit;
        }

        $org = 'N/A'; $hc = 1; $mc = 0; $fc = 0;
        if ($type === 'Group') {
            $org = trim($_POST['organization'] ?? '');
            $mc  = max(0, (int)($_POST['male_count'] ?? 0));
            $fc  = max(0, (int)($_POST['female_count'] ?? 0));

            // Include the representative in the group total.
            if ($gender === 'Male') {
                $mc++;
            } elseif ($gender === 'Female') {
                $fc++;
            }

            $hc  = $mc + $fc;
            if (!$org) {
                $_SESSION['reg_error'] = 'Please enter organization name.';
                header('Location: index.php?page=login');
                exit;
            }
        } else {
            $mc = $gender === 'Male' ? 1 : 0;
            $fc = $gender === 'Female' ? 1 : 0;
        }

        $contact = '+63' . ltrim($contact, '0');

        dbExec("INSERT INTO guests (guest_name,visitor_type,organization,gender,residence,nationality,headcount,male_count,female_count,purpose,contact_no,visit_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [$name,$type,$org,$gender,$res,$nat,$hc,$mc,$fc,$purpose,$contact,date('Y-m-d')]);

        $_SESSION['guest_logged_in'] = true;
        $_SESSION['guest_name']      = $name;
        $_SESSION['reg_success']     = 'Welcome, ' . htmlspecialchars($name) . '! You now have access to the catalog.';
        header('Location: index.php?page=home');
        exit;
    }
}

// ── Page routing ─────────────────────────────────────────────
$page = $_GET['page'] ?? 'home';
$allowedPages = ['home','about','news','news_detail','categories','exhibits','detail','pdf_detail','pdf_reader','login'];
if (!in_array($page, $allowedPages)) $page = 'home';

$pageTitle = SITE_NAME;
switch($page) {
    case 'about':      $pageTitle = 'About — ' . SITE_NAME; break;
    case 'news':       $pageTitle = 'News & Events — ' . SITE_NAME; break;
    case 'news_detail': $pageTitle = 'News Detail — ' . SITE_NAME; break;
    case 'categories': $pageTitle = 'Departments — ' . SITE_NAME; break;
    case 'exhibits':   $pageTitle = 'All Artifacts — ' . SITE_NAME; break;
    case 'detail':     $pageTitle = 'Artifact Detail — ' . SITE_NAME; break;
    case 'pdf_detail': $pageTitle = 'Artifact Document Detail — ' . SITE_NAME; break;
    case 'pdf_reader': $pageTitle = 'Artifact Reading Mode — ' . SITE_NAME; break;
    case 'login':      $pageTitle = 'Login / Access — ' . SITE_NAME; break;
}

require_once 'includes/header.php';
?>

<?php if (!empty($_SESSION['reg_success'])): ?>
<div class="alert-ok" style="background:#eef8f3;border-bottom:3px solid #1e7e4f;padding:14px 28px;text-align:center;font-weight:600;color:#155a35;">
  &#10003; <?= htmlspecialchars($_SESSION['reg_success']) ?>
</div>
<?php unset($_SESSION['reg_success']); endif; ?>

<?php
// ── Load page content ────────────────────────────────────────
$pageFile = 'pages/' . $page . '.php';
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    echo '<div style="text-align:center;padding:80px"><h2>Page not found.</h2></div>';
}
require_once 'includes/footer.php';
?>
