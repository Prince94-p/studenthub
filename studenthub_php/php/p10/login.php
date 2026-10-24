<?php
// Practical 10 - secure login: password_verify(), session_regenerate_id(), role redirect, remember-me token.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

function login_user(PDO $pdo, array $u): void {
    session_regenerate_id(true);                                 // prevents session fixation
    $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role']];
    $_SESSION['last_activity'] = time();
    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);   // intermediate: last-login
    audit($pdo, 'login', 'user=' . $u['username']);
}
function go_dashboard(): void {
    header('Location: ' . ($_SESSION['user']['role'] === 'admin' ? '../p14/dashboard.php' : 'student_dashboard.php')); exit;
}

// Auto-login through remember-me cookie (selector:token, only a hash of the token is stored in the DB)
if (empty($_SESSION['user']) && !empty($_COOKIE['remember'])) {
    [$sel, $tok] = array_pad(explode(':', $_COOKIE['remember'], 2), 2, '');
    $st = $pdo->prepare('SELECT u.*, r.token_hash FROM remember_tokens r JOIN users u ON u.id = r.user_id
                         WHERE r.selector = ? AND r.expires_at > NOW() AND u.status = "active"');
    $st->execute([$sel]);
    if (($row = $st->fetch()) && hash_equals($row['token_hash'], hash('sha256', $tok))) {
        login_user($pdo, $row); go_dashboard();
    }
}
if (!empty($_SESSION['user'])) go_dashboard();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $error = 'Invalid security token.'; }
    else {
        $login = clean($_POST['login'] ?? ''); $pw = $_POST['password'] ?? '';
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $st->execute([$login, $login]);
        $u = $st->fetch();
        if (!$u || !password_verify($pw, $u['password_hash'])) {
            $error = 'Invalid username/email or password.';          // same message for both cases (no user enumeration)
        } elseif ($u['status'] !== 'active') {
            $error = 'Your account is inactive. Contact the administrator.';
        } else {
            login_user($pdo, $u);
            if (!empty($_POST['remember'])) {                          // Advanced: remember-me with random token stored as hash
                $sel = bin2hex(random_bytes(12)); $tok = bin2hex(random_bytes(32));
                $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 30 DAY))')
                    ->execute([$u['id'], $sel, hash('sha256', $tok)]);
                setcookie('remember', "$sel:$tok", ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            }
            go_dashboard();
        }
    }
}
$title = 'Login'; require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5">
<h3>Login</h3>
<?php if (!empty($_GET['timeout'])): ?><div class="alert alert-warning">Your session expired due to inactivity.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="card card-body">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Username or email</label><input class="form-control" name="login" required></div>
  <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
  <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="remember" id="rm"><label class="form-check-label" for="rm">Remember me (30 days)</label></div>
  <button class="btn btn-primary">Login</button>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
