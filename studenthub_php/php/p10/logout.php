<?php
// Practical 10 - secure logout: clear remember token, session data, session cookie, destroy session.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
if (!empty($_SESSION['user'])) {
    audit($pdo, 'logout');
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$_SESSION['user']['id']]);
}
if (!empty($_COOKIE['remember'])) setcookie('remember', '', time() - 3600, '/');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('success', 'You have been logged out.');
header('Location: login.php'); exit;
