<?php
// Practical 11 - student list with search, filter, pagination, CSV export (all queries use MySQLi prepared statements).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();

$q = clean($_GET['q'] ?? ''); $course = clean($_GET['course'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 8; $off = ($page - 1) * $per;
$like = '%' . $q . '%';

// Build WHERE with bound parameters only (soft-deleted students are hidden)
$where = "status = 'active' AND (name LIKE ? OR email LIKE ? OR course LIKE ?)"; $types = 'sss'; $params = [$like, $like, $like];
if ($course !== '') { $where .= ' AND course = ?'; $types .= 's'; $params[] = $course; }

// Export to CSV (intermediate)
if (isset($_GET['export'])) {
    $st = $mysqli->prepare("SELECT enrollment_no, name, email, course, semester FROM students WHERE $where ORDER BY name");
    $st->bind_param($types, ...$params); $st->execute(); $res = $st->get_result();
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="students.csv"');
    $out = fopen('php://output', 'w'); fputcsv($out, ['Enrollment', 'Name', 'Email', 'Course', 'Semester']);
    while ($r = $res->fetch_assoc()) fputcsv($out, $r);
    exit;
}

$st = $mysqli->prepare("SELECT COUNT(*) FROM students WHERE $where");
$st->bind_param($types, ...$params); $st->execute(); $st->bind_result($total); $st->fetch(); $st->close();
$pages = max(1, (int)ceil($total / $per));

$types2 = $types . 'ii'; $params2 = array_merge($params, [$per, $off]);
$st = $mysqli->prepare("SELECT * FROM students WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
$st->bind_param($types2, ...$params2); $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$courses = $mysqli->query("SELECT DISTINCT course FROM students ORDER BY course")->fetch_all(MYSQLI_ASSOC);

$title = 'Students'; require __DIR__ . '/../includes/header.php';
$qs = http_build_query(['q' => $q, 'course' => $course]);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Students (<?= (int)$total ?>)</h3>
  <div><a class="btn btn-success" href="student_form.php">+ Add student</a> <a class="btn btn-outline-secondary" href="?<?= e($qs) ?>&export=1">Export CSV</a></div>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input class="form-control" name="q" placeholder="Search name / email / course" value="<?= e($q) ?>"></div>
  <div class="col-md-4"><select class="form-select" name="course"><option value="">All courses</option>
    <?php foreach ($courses as $c): ?><option <?= $course === $c['course'] ? 'selected' : '' ?>><?= e($c['course']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><button class="btn btn-primary">Search</button> <a class="btn btn-link" href="students.php">Reset</a></div>
</form>
<table class="table table-striped bg-white"><thead><tr><th>Enrollment</th><th>Name</th><th>Email</th><th>Course</th><th>Sem</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['enrollment_no']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['course']) ?></td><td><?= (int)$r['semester'] ?></td>
<td><a class="btn btn-sm btn-primary" href="student_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
<form method="post" action="student_delete.php" class="d-inline" onsubmit="return confirm('Delete this student?');">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">No students found.</td></tr><?php endif; ?>
</tbody></table>
<nav><ul class="pagination"><?php for ($i = 1; $i <= $pages; $i++): ?>
<li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs) ?>&page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav>
<?php require __DIR__ . '/../includes/footer.php'; ?>
