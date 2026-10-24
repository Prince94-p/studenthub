<?php
// Practical 7 intermediate: display stored CSV / JSON records on a webpage.
$title = 'P7 Records'; require __DIR__ . '/../includes/header.php';
$src = ($_GET['src'] ?? 'csv') === 'json' ? 'json' : 'csv';
$rows = [];
if ($src === 'csv' && file_exists(__DIR__ . '/data/records.csv')) {
    $fh = fopen(__DIR__ . '/data/records.csv', 'r'); $head = fgetcsv($fh);
    while (($r = fgetcsv($fh)) !== false) if (count($r) === count($head)) $rows[] = array_combine($head, $r);
    fclose($fh);
} elseif ($src === 'json' && file_exists(__DIR__ . '/data/records.json')) {
    $rows = json_decode(file_get_contents(__DIR__ . '/data/records.json'), true) ?: [];
}
?>
<h3>Stored records (<?= e(strtoupper($src)) ?>)</h3>
<a class="btn btn-sm btn-outline-primary" href="?src=csv">CSV</a> <a class="btn btn-sm btn-outline-primary" href="?src=json">JSON</a> <a class="btn btn-sm btn-link" href="form.php">Back to form</a>
<table class="table table-striped mt-3 bg-white"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>Time</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td><td><?= e($r['message']) ?></td><td><?= e($r['submitted_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted">No records yet.</td></tr><?php endif; ?>
</tbody></table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
