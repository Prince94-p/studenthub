<?php
// Practical 11 - soft delete (advanced): sets status='deleted' instead of removing the row. POST + CSRF only.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); die('Bad request.'); }
$id = (int)($_POST['id'] ?? 0);
$st = $mysqli->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
$st->bind_param('i', $id); $st->execute();
if ($st->affected_rows > 0) { audit($pdo, 'student_deleted', "id=$id"); flash('success', 'Student deleted.'); }
else flash('danger', 'Student could not be deleted.');
header('Location: students.php');
