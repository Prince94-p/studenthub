<?php
// helpers.php - sessions, escaping, CSRF, flash messages, access control, audit log.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
const SESSION_TIMEOUT = 900;  // 15 minutes of inactivity

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function clean($v): string { return trim(strip_tags((string)$v)); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_ok(?string $t = null): bool {
    $t = $t ?? ($_POST['csrf'] ?? '');
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
}

function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        [$t, $m] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($t) . '">' . e($m) . '</div>';
    }
}

function base_url(): string {   // URL of the /php folder, works wherever the project is placed
    $p = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    return preg_replace('#/(p\d+|includes|setup)$#', '', $p);
}

function check_timeout(): void {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset(); session_destroy(); session_start();
        flash('warning', 'Session timed out. Please log in again.');
        header('Location: ' . base_url() . '/p10/login.php?timeout=1'); exit;
    }
    $_SESSION['last_activity'] = time();
}
function require_login(): void {
    check_timeout();
    if (empty($_SESSION['user'])) {
        flash('danger', 'Please log in to continue.');
        header('Location: ' . base_url() . '/p10/login.php'); exit;
    }
}
function require_admin(): void {
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        die('403 Forbidden - admin access only. <a href="' . e(base_url()) . '/p10/login.php">Back</a>');
    }
}
function is_admin(): bool { return ($_SESSION['user']['role'] ?? '') === 'admin'; }

function audit(PDO $pdo, string $action, string $details = ''): void {
    $st = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)');
    $st->execute([$_SESSION['user']['id'] ?? null, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}
