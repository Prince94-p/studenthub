<?php
// Practical 7 - PHP form processing with server-side validation, sanitization, CSV + JSON storage.
require_once __DIR__ . '/../includes/helpers.php';
$errors = []; $old = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {                       // Q1: is the form submitted using POST?
    if (!csrf_ok()) {                                                // Advanced: CSRF token validation
        $errors[] = 'Invalid or missing security token. Reload the page and try again.';
    } else {
        // Sanitize
        $old['name']    = clean($_POST['name'] ?? '');
        $old['email']   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $old['phone']   = preg_replace('/\D/', '', $_POST['phone'] ?? '');
        $old['message'] = clean($_POST['message'] ?? '');

        // Validate
        if (!preg_match("/^[A-Za-z ]{2,50}$/", $old['name'])) $errors[] = 'Name must be 2-50 letters and spaces.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!preg_match('/^[6-9]\d{9}$/', $old['phone'])) $errors[] = 'Phone must be a valid 10-digit Indian mobile number.';
        if (strlen($old['message']) < 10 || strlen($old['message']) > 500) $errors[] = 'Message must be 10-500 characters.';

        if (!$errors) {
            $dir = __DIR__ . '/data';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $row = ['id' => uniqid(), 'name' => $old['name'], 'email' => $old['email'], 'phone' => $old['phone'],
                    'message' => $old['message'], 'submitted_at' => date('Y-m-d H:i:s')];

            // CSV storage (safe writing: exclusive lock, header on first write)
            $csv = $dir . '/records.csv'; $new = !file_exists($csv);
            $fh = fopen($csv, 'a');
            if ($fh && flock($fh, LOCK_EX)) {
                if ($new) fputcsv($fh, array_keys($row));
                fputcsv($fh, $row); fflush($fh); flock($fh, LOCK_UN); fclose($fh);
                $okCsv = true;
            } else { $okCsv = false; }

            // JSON storage (read -> append -> write with lock)
            $json = $dir . '/records.json';
            $all = file_exists($json) ? (json_decode(file_get_contents($json), true) ?: []) : [];
            $all[] = $row;
            $okJson = file_put_contents($json, json_encode($all, JSON_PRETTY_PRINT), LOCK_EX) !== false;

            if ($okCsv && $okJson) { $success = 'Thank you! Your details were saved successfully.'; $old = array_map(fn($v) => '', $old); }
            else $errors[] = 'Could not write to the data files. Check folder permissions.';
        }
    }
}
$title = 'P7 Contact Form'; require __DIR__ . '/../includes/header.php';
?>
<h3>Practical 7 - Contact / Registration Form</h3>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card card-body" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($old['name']) ?>"></div>
  <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" value="<?= e($old['email']) ?>"></div>
  <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($old['phone']) ?>"></div>
  <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="3"><?= e($old['message']) ?></textarea></div>
  <button class="btn btn-primary">Submit</button> <a href="records.php" class="btn btn-link">View stored records</a>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
