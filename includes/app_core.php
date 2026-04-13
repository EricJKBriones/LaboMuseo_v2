<?php
// ============================================================
// includes/app_core.php — App core settings and helpers
// ============================================================

// Force Philippine timezone (UTC+8)
date_default_timezone_set('Asia/Manila');

define('SITE_NAME', 'Museo de Labo');
define('SITE_SUBTITLE', 'Camarines Norte');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');
define('PROJECT_CREDITS_HTML', 'Vincent T. Malague&ntilde;o, Eric John Kenneth P. Briones, and Jonel C. Ramos');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Auto-add is_archived column if it doesn't exist yet
            try {
                $pdo->exec("ALTER TABLE news_events ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
            } catch (PDOException $e) {
                // Column already exists; ignore.
            }
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                <h2 style="color:#c0392b">Database Connection Failed</h2>
                <p>Please check your database settings in <code>includes/db.php</code></p>
                <p style="color:#888;font-size:.9rem">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }
    return $pdo;
}

// ── Session helpers ──────────────────────────────────────────
function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isAdmin(): bool {
    sessionStart();
    return !empty($_SESSION['admin_logged_in']);
}

function isGuest(): bool {
    sessionStart();
    return !empty($_SESSION['guest_logged_in']);
}

function isLoggedIn(): bool {
    return isAdmin() || isGuest();
}

function guestName(): string {
    sessionStart();
    return $_SESSION['guest_name'] ?? '';
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ../index.php?page=login');
        exit;
    }
}

function renderGuestbookLockView(string $heading, string $message, string $buttonLabel = 'Sign Digital Guestbook'): void {
    echo '<div class="locked-view">';
    echo '<div class="lock-icon">&#128274;</div>';
    echo '<h3>' . htmlspecialchars($heading) . '</h3>';
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '<a href="index.php?page=login" class="btn-gold" style="margin-top:16px">' . htmlspecialchars($buttonLabel) . ' &#10022;</a>';
    echo '</div>';
}

function projectCreditsHtml(): string {
    return defined('PROJECT_CREDITS_HTML')
        ? PROJECT_CREDITS_HTML
        : 'Vincent T. Malague&ntilde;o, Eric John Kenneth P. Briones, and Jonel C. Ramos';
}

// ── DB helpers ───────────────────────────────────────────────
function dbQuery(string $sql, array $params = []): array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbOne(string $sql, array $params = []): ?array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function dbExec(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int) getDB()->lastInsertId();
}

function dbCount(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// ── Image upload helper ──────────────────────────────────────
function handleUpload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $file = $_FILES[$field];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB max
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return null;
}
