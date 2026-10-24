<?php
// Practical 14 - audit log viewer. Admin only.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
$logs = $pdo->query('SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 100')->fetchAll();
$title = 'Audit Log'; require __DIR__ . '/../includes/header.php';
?>
<h3>Audit log (latest 100)</h3>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['username'] ?? '-') ?></td><td><?= e($l['action']) ?></td><td><?= e($l['details']) ?></td><td><?= e($l['ip_address']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
