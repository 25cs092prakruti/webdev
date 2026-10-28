CREATE DATABASE IF NOT EXISTS studenthub_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub_db;

CREATE TABLE IF NOT EXISTS p11_students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    course VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_p11_students_name (full_name),
    INDEX idx_p11_students_course (course),
    INDEX idx_p11_students_status (status)
) ENGINE=InnoDB;

INSERT IGNORE INTO p11_students (full_name, email, course, phone) VALUES
('Aarav Patel', 'aarav.patel@example.com', 'B.Tech Computer Engineering', '9876500001'),
('Diya Shah', 'diya.shah@example.com', 'B.Tech Information Technology', '9876500002'),
('Ishaan Mehta', 'ishaan.mehta@example.com', 'BCA', '9876500003'),
('Kavya Desai', 'kavya.desai@example.com', 'B.Sc Computer Science', '9876500004'),
('Rohan Joshi', 'rohan.joshi@example.com', 'B.Tech Computer Engineering', '9876500005'),
('Meera Trivedi', 'meera.trivedi@example.com', 'B.Tech Information Technology', '9876500006'),
('Vivaan Shah', 'vivaan.shah@example.com', 'BCA', '9876500007'),
('Anaya Patel', 'anaya.patel@example.com', 'B.Sc Computer Science', '9876500008'),
('Krish Soni', 'krish.soni@example.com', 'B.Tech Computer Engineering', '9876500009'),
('Myra Dave', 'myra.dave@example.com', 'B.Tech Information Technology', '9876500010'),
('Aditya Modi', 'aditya.modi@example.com', 'BCA', '9876500011'),
('Siya Bhatt', 'siya.bhatt@example.com', 'B.Sc Computer Science', '9876500012'),
('Arjun Vyas', 'arjun.vyas@example.com', 'B.Tech Computer Engineering', '9876500013'),
('Ira Shah', 'ira.shah@example.com', 'B.Tech Information Technology', '9876500014'),
('Dhruv Patel', 'dhruv.patel@example.com', 'BCA', '9876500015'),
('Navya Joshi', 'navya.joshi@example.com', 'B.Sc Computer Science', '9876500016'),
('Reyansh Mehta', 'reyansh.mehta@example.com', 'B.Tech Computer Engineering', '9876500017'),
('Aanya Desai', 'aanya.desai@example.com', 'B.Tech Information Technology', '9876500018'),
('Yash Trivedi', 'yash.trivedi@example.com', 'BCA', '9876500019'),
('Kiara Soni', 'kiara.soni@example.com', 'B.Sc Computer Science', '9876500020');
