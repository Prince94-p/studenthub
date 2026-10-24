<?php
// Practical 10 - protected student dashboard (any logged-in user; admins are redirected to the admin dashboard).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (is_admin()) { header('Location: ../p14/dashboard.php'); exit; }
$st = $pdo->prepare('SELECT username, email, last_login FROM users WHERE id = ?');
$st->execute([$_SESSION['user']['id']]); $me = $st->fetch();
$title = 'My Dashboard'; require __DIR__ . '/../includes/header.php';
?>
<h3>Welcome, <?= e($_SESSION['user']['name']) ?></h3>
<div class="card card-body">
  <p class="mb-1"><b>Username:</b> <?= e($me['username']) ?></p>
  <p class="mb-1"><b>Email:</b> <?= e($me['email']) ?></p>
  <p class="mb-0"><b>Last login:</b> <?= e($me['last_login'] ?? 'first login') ?></p>
</div>
<p class="mt-3 text-muted">Session auto-expires after 15 minutes of inactivity.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
