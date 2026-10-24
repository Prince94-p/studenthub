<?php
// Practical 12 - event list with category and open/closed filter (admin only).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
$cat = clean($_GET['category'] ?? ''); $stat = clean($_GET['status'] ?? '');
$sql = 'SELECT * FROM events WHERE 1=1'; $p = [];
if (in_array($cat, ['Technical', 'Cultural', 'Sports', 'Workshop'], true)) { $sql .= ' AND category = ?'; $p[] = $cat; }
if (in_array($stat, ['open', 'closed'], true)) { $sql .= ' AND status = ?'; $p[] = $stat; }
$st = $mysqli->prepare($sql . ' ORDER BY event_date DESC');
if ($p) $st->bind_param(str_repeat('s', count($p)), ...$p);
$st->execute(); $events = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$title = 'Events'; require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between mb-3"><h3>Events</h3><a class="btn btn-success" href="event_form.php">+ Add event</a></div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-3"><select class="form-select" name="category"><option value="">All categories</option>
    <?php foreach (['Technical', 'Cultural', 'Sports', 'Workshop'] as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="status"><option value="">Open &amp; closed</option>
    <option value="open" <?= $stat === 'open' ? 'selected' : '' ?>>Open</option><option value="closed" <?= $stat === 'closed' ? 'selected' : '' ?>>Closed</option></select></div>
  <div class="col-md-3"><button class="btn btn-primary">Filter</button> <a class="btn btn-link" href="events.php">Reset</a></div>
</form>
<div class="row">
<?php foreach ($events as $ev): ?>
  <div class="col-md-4 mb-3"><div class="card h-100">
    <?php if ($ev['poster']): ?><img src="uploads/<?= e($ev['poster']) ?>" class="card-img-top" style="height:180px;object-fit:cover" alt="poster"><?php endif; ?>
    <div class="card-body"><h5><?= e($ev['title']) ?></h5>
      <span class="badge bg-info"><?= e($ev['category']) ?></span> <span class="badge bg-<?= $ev['status'] === 'open' ? 'success' : 'secondary' ?>"><?= e($ev['status']) ?></span>
      <p class="mt-2 mb-1 small"><?= e($ev['event_date']) ?> - <?= e($ev['venue']) ?></p><p class="small text-muted"><?= e($ev['description']) ?></p></div>
    <div class="card-footer bg-white"><a class="btn btn-sm btn-primary" href="event_form.php?id=<?= (int)$ev['id'] ?>">Edit</a>
      <form method="post" action="event_delete.php" class="d-inline" onsubmit="return confirm('Delete this event?');">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ev['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div>
  </div></div>
<?php endforeach; if (!$events): ?><p class="text-muted">No events found.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
