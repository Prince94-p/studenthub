<?php
// Practical 12 - delete event (POST + CSRF) and remove its poster file; audit logged.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(400); die('Bad request.'); }
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT title, poster FROM events WHERE id = ?'); $st->execute([$id]); $ev = $st->fetch();
if ($ev) {
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    if ($ev['poster'] && is_file(__DIR__ . '/uploads/' . basename($ev['poster']))) unlink(__DIR__ . '/uploads/' . basename($ev['poster']));
    audit($pdo, 'event_deleted', $ev['title']);
    flash('success', 'Event deleted.');
} else flash('danger', 'Event not found.');
header('Location: events.php');
