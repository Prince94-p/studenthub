<?php
// Practical 14 - list users and toggle active/inactive status (intermediate). Admin only, POST + CSRF, audit logged.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if (!csrf_ok()) flash('danger', 'Invalid security token.');
    elseif ($id === (int)$_SESSION['user']['id']) flash('danger', 'You cannot deactivate your own account.');
    else {
        $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
        audit($pdo, 'user_status_toggled', "id=$id"); flash('success', 'User status updated.');
    }
    header('Location: users.php'); exit;
}
$users = $pdo->query('SELECT id, name, username, email, role, status, last_login FROM users ORDER BY id')->fetchAll();
$title = 'Users'; require __DIR__ . '/../includes/header.php';
?>
<h3>Users</h3>
<table class="table table-striped bg-white"><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr><td><?= e($u['name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td>
<td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td><td><?= e($u['last_login'] ?? '-') ?></td>
<td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-sm btn-outline-dark">Toggle</button></form></td></tr>
<?php endforeach; ?></tbody></table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
