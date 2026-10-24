<?php
// Practical 8 - connection test using PDO + prepared statements. Take a screenshot of this page.
$title = 'P8 Connection Test'; require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/db.php';
$counts = [];
foreach (['users', 'students', 'events', 'registrations'] as $t) {      // table names are fixed, not user input
    $counts[$t] = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}
$st = $pdo->prepare('SELECT title, event_date FROM events WHERE status = :s ORDER BY event_date');   // prepared statement
$st->execute([':s' => 'open']);
$open = $st->fetchAll();
$sp = $pdo->prepare('CALL sp_event_registration_count(?)');               // stored procedure (advanced)
$sp->execute([1]); $proc = $sp->fetch(); $sp->closeCursor();
?>
<h3>Practical 8 - Database connection test</h3>
<div class="alert alert-success">Connected to MySQL database <b>studenthub</b> successfully (PDO).</div>
<table class="table bg-white w-auto"><?php foreach ($counts as $t => $c): ?><tr><th><?= e($t) ?></th><td><?= (int)$c ?></td></tr><?php endforeach; ?></table>
<h5>Open events (prepared statement)</h5><ul><?php foreach ($open as $o): ?><li><?= e($o['title']) ?> - <?= e($o['event_date']) ?></li><?php endforeach; ?></ul>
<h5>Stored procedure result</h5><p><?= e($proc['title'] ?? '') ?>: <?= (int)($proc['total_registrations'] ?? 0) ?> registrations</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
