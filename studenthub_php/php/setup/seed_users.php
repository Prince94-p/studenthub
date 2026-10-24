<?php
// Run ONCE in the browser (http://localhost/studenthub/php/setup/seed_users.php) after importing the SQL file.
// Creates demo users with password_hash(). Delete this file after running.
require __DIR__ . '/../includes/db.php';
$users = [
    ['Admin User',   'admin',   'admin@studenthub.test',   'Admin@123',   'admin'],
    ['Student User', 'student', 'student@studenthub.test', 'Student@123', 'student'],
];
$st = $pdo->prepare('INSERT IGNORE INTO users (name, username, email, password_hash, role) VALUES (?,?,?,?,?)');
foreach ($users as [$n, $u, $em, $pw, $r]) {
    $st->execute([$n, $u, $em, password_hash($pw, PASSWORD_DEFAULT), $r]);
}
echo 'Done. Login: admin / Admin@123  and  student / Student@123. Now DELETE this file.';
