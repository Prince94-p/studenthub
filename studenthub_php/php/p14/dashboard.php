<?php
// Practical 14 - admin dashboard: dynamic statistics (SQL aggregation), latest registrations, monthly chart. Admin only.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
$stats = $pdo->query("SELECT
   (SELECT COUNT(*) FROM users) AS users,
   (SELECT COUNT(*) FROM users WHERE status='active') AS active_users,
   (SELECT COUNT(*) FROM students WHERE status='active') AS students,
   (SELECT COUNT(*) FROM events) AS events,
   (SELECT COUNT(*) FROM registrations) AS registrations")->fetch();
$latest = $pdo->query("SELECT s.name, e.title, r.registered_at FROM registrations r
   JOIN students s ON s.id = r.student_id JOIN events e ON e.id = r.event_id ORDER BY r.registered_at DESC LIMIT 5")->fetchAll();
$monthly = $pdo->query("SELECT DATE_FORMAT(registered_at, '%Y-%m') AS m, COUNT(*) AS c FROM registrations GROUP BY m ORDER BY m")->fetchAll();
$max = max(array_merge([1], array_column($monthly, 'c')));
$title = 'Admin Dashboard'; require __DIR__ . '/../includes/header.php';
?>
<h3>Admin Dashboard</h3>
<div class="row mb-4">
<?php foreach (['Users' => $stats['users'], 'Active users' => $stats['active_users'], 'Students' => $stats['students'], 'Events' => $stats['events'], 'Registrations' => $stats['registrations']] as $label => $v): ?>
  <div class="col"><div class="card text-center"><div class="card-body"><div class="fs-2 fw-bold"><?= (int)$v ?></div><div class="text-muted"><?= e($label) ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row">
  <div class="col-md-6"><div class="card mb-3"><div class="card-header">Latest registrations</div><ul class="list-group list-group-flush">
    <?php foreach ($latest as $l): ?><li class="list-group-item"><b><?= e($l['name']) ?></b> registered for <?= e($l['title']) ?><br><small class="text-muted"><?= e($l['registered_at']) ?></small></li><?php endforeach; ?>
  </ul></div></div>
  <div class="col-md-6"><div class="card"><div class="card-header">Event registrations by month</div><div class="card-body">
    <?php foreach ($monthly as $m): ?>
      <div class="d-flex align-items-center mb-2"><span class="me-2" style="width:80px"><?= e($m['m']) ?></span>
      <div class="bg-primary" style="height:18px;width:<?= (int)($m['c'] / $max * 100) ?>%"></div><span class="ms-2"><?= (int)$m['c'] ?></span></div>
    <?php endforeach; ?>
  </div></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
