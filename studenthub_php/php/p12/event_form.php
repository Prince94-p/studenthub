<?php
// Practical 12 - add / edit event with poster upload; validates type, size and real MIME; renames the file.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
const MAX_POSTER = 2 * 1024 * 1024;                       // 2 MB
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$ev = ['title' => '', 'description' => '', 'event_date' => '', 'venue' => '', 'category' => 'Technical', 'status' => 'open', 'poster' => null];
if ($id) {
    $st = $mysqli->prepare('SELECT * FROM events WHERE id = ?'); $st->bind_param('i', $id); $st->execute();
    $ev = $st->get_result()->fetch_assoc();
    if (!$ev) { flash('danger', 'Event not found.'); header('Location: events.php'); exit; }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $errors[] = 'Invalid security token.';
    $ev['title'] = clean($_POST['title'] ?? ''); $ev['description'] = clean($_POST['description'] ?? '');
    $ev['event_date'] = $_POST['event_date'] ?? ''; $ev['venue'] = clean($_POST['venue'] ?? '');
    $ev['category'] = $_POST['category'] ?? ''; $ev['status'] = $_POST['status'] ?? '';
    if (strlen($ev['title']) < 3 || strlen($ev['title']) > 150) $errors[] = 'Title must be 3-150 characters.';
    if (!($d = DateTime::createFromFormat('Y-m-d', $ev['event_date'])) || $d->format('Y-m-d') !== $ev['event_date']) $errors[] = 'Enter a valid date.';
    if ($ev['venue'] === '') $errors[] = 'Venue is required.';
    if (!in_array($ev['category'], ['Technical', 'Cultural', 'Sports', 'Workshop'], true)) $errors[] = 'Invalid category.';
    if (!in_array($ev['status'], ['open', 'closed'], true)) $errors[] = 'Invalid status.';

    $newPoster = null;
    if (!empty($_FILES['poster']['name'])) {
        $f = $_FILES['poster'];
        if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) $errors[] = 'Poster must be 2 MB or smaller.';
        elseif ($f['error'] !== UPLOAD_ERR_OK) $errors[] = 'Upload failed (error code ' . (int)$f['error'] . ').';
        elseif ($f['size'] > MAX_POSTER) $errors[] = 'Poster must be 2 MB or smaller.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);       // real type, not the browser-provided one
            if (!isset($allowed[$mime])) $errors[] = 'Only JPG, PNG or WEBP images are allowed.';
            else $newPoster = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];   // advanced: image renaming
        }
    }
    if (!$errors) {
        if ($newPoster && !move_uploaded_file($_FILES['poster']['tmp_name'], __DIR__ . '/uploads/' . $newPoster)) {
            $errors[] = 'Could not save the uploaded file.'; $newPoster = null;
        }
    }
    if (!$errors) {
        $poster = $newPoster ?: $ev['poster'];
        if ($id) {
            $st = $mysqli->prepare('UPDATE events SET title=?, description=?, event_date=?, venue=?, category=?, status=?, poster=? WHERE id=?');
            $st->bind_param('sssssssi', $ev['title'], $ev['description'], $ev['event_date'], $ev['venue'], $ev['category'], $ev['status'], $poster, $id);
        } else {
            $st = $mysqli->prepare('INSERT INTO events (title, description, event_date, venue, category, status, poster) VALUES (?,?,?,?,?,?,?)');
            $st->bind_param('sssssss', $ev['title'], $ev['description'], $ev['event_date'], $ev['venue'], $ev['category'], $ev['status'], $poster);
        }
        $st->execute();
        audit($pdo, $id ? 'event_updated' : 'event_created', $ev['title']);
        flash('success', $id ? 'Event updated successfully.' : 'Event created successfully.');
        header('Location: events.php'); exit;
    }
}
$title = $id ? 'Edit Event' : 'Add Event'; require __DIR__ . '/../includes/header.php';
?>
<h3><?= $id ? 'Edit' : 'Add' ?> event</h3>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card card-body">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($ev['title']) ?>"></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($ev['description']) ?></textarea></div>
  <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="event_date" value="<?= e($ev['event_date']) ?>"></div>
  <div class="col-md-4 mb-3"><label class="form-label">Venue</label><input class="form-control" name="venue" value="<?= e($ev['venue']) ?>"></div>
  <div class="col-md-2 mb-3"><label class="form-label">Category</label><select class="form-select" name="category"><?php foreach (['Technical', 'Cultural', 'Sports', 'Workshop'] as $c): ?><option <?= $ev['category'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2 mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option <?= $ev['status'] === 'open' ? 'selected' : '' ?>>open</option><option <?= $ev['status'] === 'closed' ? 'selected' : '' ?>>closed</option></select></div></div>
  <div class="mb-3"><label class="form-label">Poster (JPG/PNG/WEBP, max 2 MB)</label><input type="file" class="form-control" name="poster" accept="image/*">
    <?php if ($ev['poster']): ?><img src="uploads/<?= e($ev['poster']) ?>" height="80" class="mt-2" alt="current poster"><?php endif; ?></div>
  <div><button class="btn btn-primary">Save</button> <a class="btn btn-link" href="events.php">Cancel</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
