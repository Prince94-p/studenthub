-- StudentHub database (Practical 8). Import this file in phpMyAdmin (Import tab).
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

DROP TABLE IF EXISTS remember_tokens, audit_logs, registrations, events, students, users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  enrollment_no VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  course VARCHAR(80) NOT NULL,
  semester TINYINT NOT NULL,
  status ENUM('active','deleted') NOT NULL DEFAULT 'active',   -- soft delete (P11 advanced)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  venue VARCHAR(120) NOT NULL,
  category ENUM('Technical','Cultural','Sports','Workshop') NOT NULL DEFAULT 'Technical',
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  poster VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  event_id INT NOT NULL,
  registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_event (student_id, event_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(60) NOT NULL,
  details VARCHAR(255),
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  selector CHAR(24) NOT NULL UNIQUE,
  token_hash CHAR(64) NOT NULL,          -- sha256 of the random token; the raw token lives only in the cookie
  expires_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed data (users are created by php/setup/seed_users.php so passwords are hashed with password_hash())
INSERT INTO students (enrollment_no, name, email, course, semester, status) VALUES
('25CE101','Aarav Shah','aarav.shah1@example.com','Information Technology',2,'active'),
('25CE102','Vivaan Mehta','vivaan.mehta2@example.com','Electronics',3,'active'),
('25CE103','Aditya Desai','aditya.desai3@example.com','Mechanical',4,'active'),
('25CE104','Krish Joshi','krish.joshi4@example.com','Civil',5,'active'),
('25CE105','Ishaan Parmar','ishaan.parmar5@example.com','Computer Engineering',6,'active'),
('25CE106','Riya Trivedi','riya.trivedi6@example.com','Information Technology',7,'active'),
('25CE107','Anaya Modi','anaya.modi7@example.com','Electronics',8,'active'),
('25CE108','Diya Vyas','diya.vyas8@example.com','Mechanical',1,'active'),
('25CE109','Meera Bhatt','meera.bhatt9@example.com','Civil',2,'active'),
('25CE110','Kavya Patel','kavya.patel10@example.com','Computer Engineering',3,'active'),
('25CE111','Rohan Shah','rohan.shah11@example.com','Information Technology',4,'active'),
('25CE112','Neha Mehta','neha.mehta12@example.com','Electronics',5,'active'),
('25CE113','Yash Desai','yash.desai13@example.com','Mechanical',6,'active'),
('25CE114','Sneha Joshi','sneha.joshi14@example.com','Civil',7,'active'),
('25CE115','Harsh Parmar','harsh.parmar15@example.com','Computer Engineering',8,'active'),
('25CE116','Pooja Trivedi','pooja.trivedi16@example.com','Information Technology',1,'active'),
('25CE117','Dev Modi','dev.modi17@example.com','Electronics',2,'active'),
('25CE118','Isha Vyas','isha.vyas18@example.com','Mechanical',3,'active'),
('25CE119','Jay Bhatt','jay.bhatt19@example.com','Civil',4,'active'),
('25CE120','Mansi Patel','mansi.patel20@example.com','Computer Engineering',5,'active');

INSERT INTO events (title, description, event_date, venue, category, status) VALUES
('HackX 2026','24-hour college hackathon','2026-11-15','Main Auditorium','Technical','open'),
('Cultural Fest','Annual cultural evening','2026-12-05','Open Ground','Cultural','open'),
('Cricket Premier League','Inter-department tournament','2026-11-02','College Ground','Sports','open'),
('PHP Workshop','Hands-on PHP and MySQL','2026-10-30','Lab 3','Workshop','open'),
('Robotics Expo','Student robotics showcase','2026-09-20','Seminar Hall','Technical','closed'),
('Photography Contest','Campus photography contest','2026-08-18','Gallery','Cultural','closed');

INSERT INTO registrations (student_id, event_id, registered_at) VALUES
(1,1,'2026-08-05 10:00:00'),(2,1,'2026-08-20 11:30:00'),(3,1,'2026-09-02 09:15:00'),(4,2,'2026-09-10 14:00:00'),
(5,3,'2026-09-18 16:45:00'),(6,3,'2026-10-01 12:00:00'),(7,4,'2026-10-05 10:10:00'),(8,4,'2026-10-12 13:20:00'),
(9,5,'2026-07-22 15:00:00'),(10,6,'2026-07-30 09:00:00'),(11,1,'2026-10-15 17:00:00'),(12,2,'2026-10-18 18:30:00');

-- Practical 8 advanced: stored procedure
DROP PROCEDURE IF EXISTS sp_event_registration_count;
DELIMITER $$
CREATE PROCEDURE sp_event_registration_count(IN p_event_id INT)
BEGIN
  SELECT e.title, COUNT(r.id) AS total_registrations
  FROM events e LEFT JOIN registrations r ON r.event_id = e.id
  WHERE e.id = p_event_id GROUP BY e.id;
END$$
DELIMITER ;
