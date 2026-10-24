# StudentHub - PHP Practicals 7 to 14

Back-end (PHP + MySQL) for the StudentHub project. Lives in the `php/` folder; the database script is `sql/studenthub.sql`.

## Setup (XAMPP)
1. Copy this repo folder to `C:\xampp\htdocs\studenthub` and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin` -> **Import** -> choose `sql/studenthub.sql` -> Go.
3. Open `http://localhost/studenthub/php/setup/seed_users.php` once (creates the admin and student accounts), then delete that file.
4. Open `http://localhost/studenthub/php/`.

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | Admin@123 |
| Student | student | Student@123 |

## Practicals
| No. | Folder | What it does |
|-----|--------|--------------|
| 7 | `php/p7` | Contact form, POST handling, validation + sanitization, CSV and JSON storage, records page, CSRF token |
| 8 | `php/p8`, `php/includes/db.php`, `sql/` | ER schema (users, students, events, registrations), PDO connection, prepared statement, stored procedure |
| 9 | `php/p9` | Registration with MySQLi, duplicate check, `password_hash()`, JS + PHP validation, CSRF, audit log |
| 10 | `php/p10` | Login/logout, `password_verify()`, `session_regenerate_id()`, role redirect, 15-minute timeout, remember-me (hashed token), last login |
| 11 | `php/p11` | Student CRUD, search + course filter, pagination, delete confirmation, CSV export, soft delete |
| 12 | `php/p12` | Event CRUD, poster upload (type/size/MIME check, renamed files, protected folder), category + open/closed filter, audit log |
| 13 | `php/p13` | JSON API (`api.php`) + Fetch-based CRUD page, debounced search, HTTP status codes, central error handling |
| 14 | `php/p14` | Admin dashboard (aggregates, latest registrations, monthly chart), role-based menu, user status toggle, audit log viewer |

Requires PHP 8.1+ with `mysqli`, `pdo_mysql` and `fileinfo` (all enabled in XAMPP by default).
