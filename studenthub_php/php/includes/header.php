<?php
// header.php - Bootstrap layout + dynamic role-based menu (Practical 14).
require_once __DIR__ . '/helpers.php';
$B = base_url();
$title = $title ?? 'StudentHub';
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - StudentHub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4"><div class="container">
  <a class="navbar-brand" href="<?= e($B) ?>/index.php">StudentHub</a>
  <ul class="navbar-nav me-auto">
  <?php if (!empty($_SESSION['user'])): ?>
    <?php if (is_admin()): ?>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p14/dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p11/students.php">Students</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p12/events.php">Events</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p13/events_ajax.php">Events (AJAX)</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p14/users.php">Users</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p14/audit.php">Audit Log</a></li>
    <?php else: ?>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p10/student_dashboard.php">My Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p13/events_ajax.php">Events</a></li>
    <?php endif; ?>
  <?php endif; ?>
  </ul>
  <ul class="navbar-nav">
  <?php if (!empty($_SESSION['user'])): ?>
    <li class="nav-item"><span class="navbar-text me-3"><?= e($_SESSION['user']['name']) ?> (<?= e($_SESSION['user']['role']) ?>)</span></li>
    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= e($B) ?>/p10/logout.php">Logout</a></li>
  <?php else: ?>
    <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p9/register.php">Register</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= e($B) ?>/p10/login.php">Login</a></li>
  <?php endif; ?>
  </ul>
</div></nav>
<div class="container pb-5">
<?php show_flash(); ?>
