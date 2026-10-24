<?php
// Practical 11 - add / edit a student (INSERT / UPDATE with MySQLi prepared statements).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s = ['enrollment_no' => '', 'name' => '', 'email' => '', 'course' => '', 'semester' => ''];
if ($id) {
    $st = $mysqli->prepare("SELECT * FROM students WHERE id = ? AND status = 'active'");
    $st->bind_param('i', $id); $st->execute();
    $s = $st->get_result()->fetch_assoc();
    if (!$s) { flash('danger', 'Student not found.'); header('Location: students.php'); exit; }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $errors[] = 'Invalid security token.';
    $s['enrollment_no'] = clean($_POST['enrollment_no'] ?? ''); $s['name'] = clean($_POST['name'] ?? '');
    $s['email'] = trim($_POST['email'] ?? ''); $s['course'] = clean($_POST['course'] ?? ''); $s['semester'] = (int)($_POST['semester'] ?? 0);
    if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $s['enrollment_no'])) $errors[] = 'Enrollment number must be 5-20 letters/digits.';
    if (!preg_match('/^[A-Za-z ]{2,100}$/', $s['name'])) $errors[] = 'Name must contain only letters (2-100).';
    if (!filter_var($s['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($s['course'] === '') $errors[] = 'Course is required.';
    if ($s['semester'] < 1 || $s['semester'] > 8) $errors[] = 'Semester must be 1-8.';
    if (!$errors) {
        try {
            if ($id) {
                $st = $mysqli->prepare('UPDATE students SET enrollment_no=?, name=?, email=?, course=?, semester=? WHERE id=?');
                $st->bind_param('ssssii', $s['enrollment_no'], $s['name'], $s['email'], $s['course'], $s['semester'], $id);
                $msg = 'Student updated successfully.';
            } else {
                $st = $mysqli->prepare('INSERT INTO students (enrollment_no, name, email, course, semester) VALUES (?,?,?,?,?)');
                $st->bind_param('ssssi', $s['enrollment_no'], $s['name'], $s['email'], $s['course'], $s['semester']);
                $msg = 'Student added successfully.';
            }
            $st->execute();
            flash('success', $msg); header('Location: students.php'); exit;
        } catch (mysqli_sql_exception $ex) {
            $errors[] = $ex->getCode() === 1062 ? 'Enrollment number or email already exists.' : 'Database error. Operation failed.';
        }
    }
}
$title = $id ? 'Edit Student' : 'Add Student'; require __DIR__ . '/../includes/header.php';
?>
<h3><?= $id ? 'Edit' : 'Add' ?> student</h3>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card card-body">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="mb-3"><label class="form-label">Enrollment no.</label><input class="form-control" name="enrollment_no" value="<?= e($s['enrollment_no']) ?>"></div>
  <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($s['name']) ?>"></div>
  <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" value="<?= e($s['email']) ?>"></div>
  <div class="mb-3"><label class="form-label">Course</label><input class="form-control" name="course" value="<?= e($s['course']) ?>"></div>
  <div class="mb-3"><label class="form-label">Semester</label><input type="number" min="1" max="8" class="form-control" name="semester" value="<?= e($s['semester']) ?>"></div>
  <div><button class="btn btn-primary">Save</button> <a class="btn btn-link" href="students.php">Cancel</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
