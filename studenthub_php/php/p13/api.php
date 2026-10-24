<?php
// Practical 13 - JSON API for events. GET = list/search, POST (action=add|update|delete) = write (admin only).
// Always returns JSON with proper HTTP status codes and one central error handler (advanced).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $body): never { http_response_code($code); echo json_encode($body); exit; }
set_exception_handler(function (Throwable $t) { error_log($t->getMessage()); respond(500, ['success' => false, 'message' => 'Server error.']); });

if (empty($_SESSION['user'])) respond(401, ['success' => false, 'message' => 'Login required.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = '%' . clean($_GET['q'] ?? '') . '%';
    $st = $pdo->prepare('SELECT id, title, description, event_date, venue, category, status FROM events
                         WHERE title LIKE ? OR venue LIKE ? ORDER BY event_date DESC');
    $st->execute([$q, $q]);
    respond(200, ['success' => true, 'data' => $st->fetchAll()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['success' => false, 'message' => 'Method not allowed.']);
if (!is_admin()) respond(403, ['success' => false, 'message' => 'Admin access only.']);
if (!csrf_ok($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) respond(403, ['success' => false, 'message' => 'Invalid CSRF token.']);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) respond(400, ['success' => false, 'message' => 'Invalid JSON body.']);
$action = $in['action'] ?? '';
$id = (int)($in['id'] ?? 0);

if ($action === 'delete') {
    if ($id < 1) respond(422, ['success' => false, 'message' => 'Invalid id.']);
    $st = $pdo->prepare('DELETE FROM events WHERE id = ?'); $st->execute([$id]);
    if (!$st->rowCount()) respond(404, ['success' => false, 'message' => 'Event not found.']);
    audit($pdo, 'event_deleted_api', "id=$id");
    respond(200, ['success' => true, 'message' => 'Event deleted.']);
}
if (!in_array($action, ['add', 'update'], true)) respond(400, ['success' => false, 'message' => 'Unknown action.']);

// Validation before any DB operation
$e = ['title' => clean($in['title'] ?? ''), 'description' => clean($in['description'] ?? ''), 'event_date' => $in['event_date'] ?? '',
      'venue' => clean($in['venue'] ?? ''), 'category' => $in['category'] ?? '', 'status' => $in['status'] ?? 'open'];
$errs = [];
if (strlen($e['title']) < 3) $errs[] = 'Title must be at least 3 characters.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['event_date']) || !strtotime($e['event_date'])) $errs[] = 'Invalid date.';
if ($e['venue'] === '') $errs[] = 'Venue is required.';
if (!in_array($e['category'], ['Technical', 'Cultural', 'Sports', 'Workshop'], true)) $errs[] = 'Invalid category.';
if (!in_array($e['status'], ['open', 'closed'], true)) $errs[] = 'Invalid status.';
if ($errs) respond(422, ['success' => false, 'message' => implode(' ', $errs)]);

if ($action === 'add') {
    $pdo->prepare('INSERT INTO events (title, description, event_date, venue, category, status) VALUES (?,?,?,?,?,?)')
        ->execute([$e['title'], $e['description'], $e['event_date'], $e['venue'], $e['category'], $e['status']]);
    $newId = (int)$pdo->lastInsertId();          // read before audit() runs another INSERT
    audit($pdo, 'event_created_api', $e['title']);
    respond(201, ['success' => true, 'message' => 'Event added.', 'id' => $newId]);
}
$st = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, venue=?, category=?, status=? WHERE id=?');
$st->execute([$e['title'], $e['description'], $e['event_date'], $e['venue'], $e['category'], $e['status'], $id]);
audit($pdo, 'event_updated_api', "id=$id");
respond(200, ['success' => true, 'message' => 'Event updated.']);
