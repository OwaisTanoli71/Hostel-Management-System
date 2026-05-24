-- =============================================================
--  Smart Hostel Management System — hostel_db.sql
--  Developer : Muhammad Owais Arshad, Ehtisham ul Haq, Marwa Noor
-- =============================================================

CREATE DATABASE IF NOT EXISTS hostel_db;
USE hostel_db;

-- ---------------------------------------------------------
-- TABLE 1: admins
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id       INT          AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    CONSTRAINT chk_admin_username CHECK (CHAR_LENGTH(username) >= 3)
);

-- ---------------------------------------------------------
-- TABLE 2: rooms
-- NOTE: is_occupied REMOVED — it is a DERIVED attribute.
--       Both is_occupied and current_occupants are now
--       calculated exclusively by the room_occupancy VIEW.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
    id          INT         AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    capacity    INT         NOT NULL,
    gender      ENUM('Male','Female') NOT NULL,
    CONSTRAINT chk_capacity CHECK (capacity > 0)
);

-- ---------------------------------------------------------
-- TABLE 3: students
-- assigned_room is NULL = not yet assigned (optional participation)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    roll_no       VARCHAR(30)  NOT NULL UNIQUE,
    gender        ENUM('Male','Female') NOT NULL,
    assigned_room INT          NULL,
    CONSTRAINT fk_student_room
        FOREIGN KEY (assigned_room) REFERENCES rooms(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- VIEW: room_occupancy
-- Single source of truth for room availability.
-- Derives BOTH current_occupants AND is_occupied.
-- PHP must NEVER manually set is_occupied — read this VIEW.
-- ---------------------------------------------------------
CREATE OR REPLACE VIEW room_occupancy AS
SELECT
    r.id                                AS room_id,
    r.room_number,
    r.capacity,
    r.gender,
    COUNT(s.id)                         AS current_occupants,
    (COUNT(s.id) >= r.capacity)         AS is_occupied,
    (r.capacity - COUNT(s.id))          AS available_slots
FROM  rooms r
LEFT  JOIN students s ON s.assigned_room = r.id
GROUP BY r.id, r.room_number, r.capacity, r.gender;

-- ---------------------------------------------------------
-- TABLE 4: notifications
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT       AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT       NOT NULL,
    message    TEXT      NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- TABLE 5: student_notifications  (M:N junction)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_notifications (
    student_id      INT       NOT NULL,
    notification_id INT       NOT NULL,
    is_read         TINYINT   NOT NULL DEFAULT 0,
    received_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, notification_id),
    CONSTRAINT fk_sn_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sn_notification
        FOREIGN KEY (notification_id) REFERENCES notifications(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- TABLE 6: applications
-- admin_id NULL = pending (not yet reviewed)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
    id         INT       AUTO_INCREMENT PRIMARY KEY,
    student_id INT       NOT NULL,
    room_id    INT       NOT NULL,
    admin_id   INT       NULL,
    status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_app_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_app_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- TABLE 7: payments
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id           INT           AUTO_INCREMENT PRIMARY KEY,
    student_id   INT           NOT NULL,
    admin_id     INT           NULL,
    amount       DECIMAL(10,2) NOT NULL,
    semester     VARCHAR(50)   NOT NULL,
    status       ENUM('Pending','Paid') NOT NULL DEFAULT 'Pending',
    payment_date DATE          NULL,
    CONSTRAINT chk_amount CHECK (amount > 0),
    CONSTRAINT fk_pay_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pay_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- TABLE 8: complaints
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    id          INT       AUTO_INCREMENT PRIMARY KEY,
    student_id  INT       NOT NULL,
    room_id     INT       NOT NULL,
    admin_id    INT       NULL,
    category    ENUM('Electrical','Plumbing','Furniture','Cleanliness','Other') NOT NULL,
    description TEXT      NOT NULL,
    status      ENUM('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comp_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comp_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comp_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- =============================================================
--  SAMPLE DATA
--  Passwords are hashed with PHP password_hash($pass, PASSWORD_BCRYPT)
--  Plaintext → Hash mapping listed below for reference:
--    admin123 → see below
--    pw1..pw8 → see below
-- =============================================================

-- Admin (username: adminowais | password: owais123)
INSERT INTO admins (username, password) VALUES
('adminowais', '$2y$12$1sIdwZUVftG3xE7.koVHaeFoICGScyCa8NoguNKozpsd/k88Jplhm');

-- Rooms (no is_occupied column — derived by VIEW)
INSERT INTO rooms (room_number, capacity, gender) VALUES
('B-101', 2, 'Male'),
('B-102', 3, 'Male'),
('B-103', 2, 'Male'),
('B-104', 3, 'Male'),
('B-105', 2, 'Male'),
('B-106', 4, 'Male'),
('B-107', 3, 'Male'),
('B-108', 2, 'Male'),
('B-201', 3, 'Male'),
('B-202', 2, 'Male'),
('B-203', 4, 'Male'),
('B-204', 3, 'Male'),
('B-205', 2, 'Male'),
('B-206', 3, 'Male'),
('B-301', 2, 'Male'),
('B-302', 3, 'Male'),
('B-303', 4, 'Male'),
('B-304', 2, 'Male'),
('G-101', 2, 'Female'),
('G-102', 3, 'Female'),
('G-103', 2, 'Female'),
('G-104', 3, 'Female'),
('G-105', 2, 'Female'),
('G-106', 4, 'Female'),
('G-107', 3, 'Female'),
('G-108', 2, 'Female'),
('G-201', 3, 'Female'),
('G-202', 2, 'Female'),
('G-203', 4, 'Female'),
('G-204', 3, 'Female'),
('G-205', 2, 'Female'),
('G-206', 3, 'Female'),
('G-301', 2, 'Female'),
('G-302', 3, 'Female'),
('G-303', 4, 'Female'),
('G-304', 2, 'Female');

-- Students (passwords: pw1 through pw8, all hashed)
INSERT INTO students (name, email, password, roll_no, gender, assigned_room) VALUES
('Muhammad Owais','owais@pafiast.edu',  '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI321','Male',   1),
('Marwa Khan',    'marwa@pafiast.edu',  '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI432','Female', 19),
('Anus Raza',     'anus@pafiast.edu',   '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI315','Male',   1),
('Yaseen Malik',  'yaseen@pafiast.edu', '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI178','Male',   2),
('Masooma Zaidi', 'masooma@pafiast.edu','$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI509','Female', 19),
('Ali Hassan',    'ali@pafiast.edu',    '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI641','Male',   NULL),
('Daniyal Shah',  'daniyal@pafiast.edu','$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI387','Male',   2),
('Marwa Bibi',    'marwa2@pafiast.edu', '$2y$10$M1sbPTVXEu.R7TMFC2N6bupgcrXkFbEIVfGGlfjUrABAmgNL2NyaW','B24F0445AI290','Female', NULL);

-- NOTE: All student passwords above hash to "password" for demo simplicity.
-- The bcrypt hash '$2y$10$TKh8H1.PfunDnXPRTHx1GOpiVqfH6R.zl5V1K0rLh9EFQDp4VkNEe'
-- is the hash of the string "password".
-- Use "password" to log in as any student in the demo.

INSERT INTO notifications (admin_id, message) VALUES
(1,'Hostel fee for Spring 2026 is due by 15th January.'),
(1,'Water supply suspended on 10th Jan 8AM-12PM for maintenance.'),
(1,'All students must register for room allocation before 20th Jan.'),
(1,'Mess menu updated. New schedule posted on the notice board.');

INSERT INTO student_notifications (student_id, notification_id, is_read) VALUES
(1,1,1),(2,1,0),(3,1,0),(4,1,1),(5,1,1),(6,1,0),(7,1,0),(8,1,0),
(1,2,1),(2,2,0),(3,2,0),(4,2,1),(5,2,1),(6,2,0),(7,2,0),(8,2,1),
(6,3,0),(8,3,0),
(1,4,1),(2,4,1),(3,4,0),(4,4,0),(5,4,1),(6,4,0),(7,4,0),(8,4,0);

INSERT INTO applications (student_id, room_id, admin_id, status) VALUES
(1,1,1,'approved'),(2,19,1,'approved'),(3,1,1,'approved'),
(4,2,1,'approved'),(5,19,1,'approved'),(7,2,1,'approved'),
(6,3,NULL,'pending'),(8,20,NULL,'pending');

INSERT INTO payments (student_id, admin_id, amount, semester, status, payment_date) VALUES
(1,1,15000.00,'Spring 2026','Paid','2026-03-05'),
(2,1,15000.00,'Spring 2026','Paid','2026-03-07'),
(3,1,15000.00,'Spring 2026','Paid','2026-03-08'),
(4,NULL,15000.00,'Spring 2026','Pending',NULL),
(5,1,15000.00,'Spring 2026','Paid','2026-03-04'),
(6,NULL,15000.00,'Spring 2026','Pending',NULL),
(7,NULL,15000.00,'Spring 2026','Pending',NULL),
(8,NULL,15000.00,'Spring 2026','Pending',NULL);

INSERT INTO complaints (student_id, room_id, admin_id, category, description, status) VALUES
(1,1,1,'Electrical','Ceiling fan in B-101 is not working.','In Progress'),
(3,1,NULL,'Plumbing','Water leakage from bathroom tap in B-101.','Open'),
(4,2,1,'Furniture','Study chair in B-102 has a broken leg.','Resolved'),
(2,19,NULL,'Cleanliness','Corridor outside G-101 not cleaned for 2 days.','Open'),
(5,19,NULL,'Electrical','Power socket near study table not working.','Open'),
(7,2,1,'Other','Window latch in B-102 is broken.','In Progress');
