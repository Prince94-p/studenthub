<?php
// Practical 9 - secure registration: MySQLi prepared statements, duplicate check, password_hash(), CSRF, audit log.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
$errors = []; $old = ['name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $errors[] = 'Invalid security token.'; }
    else {
        $old['name'] = clean($_POST['name'] ?? '');
        $old['username'] = clean($_POST['username'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $pw = $_POST['password'] ?? ''; $cpw = $_POST['confirm'] ?? '';

        // Backend validation (same rules as the JavaScript below)
        if (!preg_match('/^[A-Za-z ]{2,60}$/', $old['name'])) $errors[] = 'Name must be 2-60 letters.';
        if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $old['username'])) $errors[] = 'Username must be 4-20 letters, digits or underscore.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $pw)) $errors[] = 'Password needs 8+ characters with upper, lower and a digit.';
        if ($pw !== $cpw) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            // Duplicate email / username check (prepared statement)
            $chk = $mysqli->prepare('SELECT email, username FROM users WHERE email = ? OR username = ?');
            $chk->bind_param('ss', $old['email'], $old['username']);
            $chk->execute();
            $res = $chk->get_result();
            while ($row = $res->fetch_assoc()) {
                if (strcasecmp($row['email'], $old['email']) === 0) $errors[] = 'This email is already registered.';
                if (strcasecmp($row['username'], $old['username']) === 0) $errors[] = 'This username is already taken.';
            }
            $chk->close();
        }
        if (!$errors) {
            $hash = password_hash($pw, PASSWORD_DEFAULT);                 // never store plain passwords
            $ins = $mysqli->prepare("INSERT INTO users (name, username, email, password_hash, role) VALUES (?,?,?,?, 'student')");
            $ins->bind_param('ssss', $old['name'], $old['username'], $old['email'], $hash);
            try {
                $ins->execute();
                $_SESSION['user'] = null;
                $uid = $mysqli->insert_id;
                $_SESSION['user'] = ['id' => $uid];                       // so audit() can record the user id
                audit($pdo, 'user_registered', 'username=' . $old['username']);
                unset($_SESSION['user']);
                flash('success', 'Registration successful! You can log in now.');
                header('Location: ../p10/login.php'); exit;
            } catch (mysqli_sql_exception $ex) {
                $errors[] = ($ex->getCode() === 1062) ? 'Email or username already exists.' : 'Registration failed. Try again later.';
            }
        }
    }
}
$title = 'Register'; require __DIR__ . '/../includes/header.php';
?>
<h3>Practical 9 - Create account</h3>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div id="jsErr" class="alert alert-danger d-none"></div>
<form method="post" id="regForm" class="card card-body" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="name" value="<?= e($old['name']) ?>"></div>
  <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" value="<?= e($old['username']) ?>"></div>
  <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" value="<?= e($old['email']) ?>"></div>
  <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password"></div>
  <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" class="form-control" name="confirm"></div>
  <button class="btn btn-primary">Register</button>
</form>
<script>
// Frontend validation - mirrors the PHP rules
document.getElementById('regForm').addEventListener('submit', function (ev) {
  const f = this, err = [];
  if (!/^[A-Za-z ]{2,60}$/.test(f.name.value)) err.push('Name must be 2-60 letters.');
  if (!/^[A-Za-z0-9_]{4,20}$/.test(f.username.value)) err.push('Username must be 4-20 letters, digits or underscore.');
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email.value)) err.push('Enter a valid email.');
  if (!/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(f.password.value)) err.push('Password needs 8+ characters with upper, lower and a digit.');
  if (f.password.value !== f.confirm.value) err.push('Passwords do not match.');
  const box = document.getElementById('jsErr');
  if (err.length) { ev.preventDefault(); box.innerHTML = err.join('<br>'); box.classList.remove('d-none'); }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
