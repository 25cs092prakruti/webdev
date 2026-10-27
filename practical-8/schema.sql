CREATE DATABASE IF NOT EXISTS studenthub_db;
USE studenthub_db;

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reg_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reg_event
        FOREIGN KEY (event_id)
        REFERENCES events(event_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_student_event
        UNIQUE (student_id, event_id)
) ENGINE=InnoDB;

INSERT INTO students
    (full_name, email, department)
VALUES
    ('Prakruti Saradva', 'prakruti@example.com', 'CSE'),
    ('Aarav Patel', 'aarav@example.com', 'IT'),
    ('Diya Shah', 'diya@example.com', 'CSE');

INSERT INTO events
    (event_name, event_date, venue)
VALUES
    ('Web Development Workshop', '2026-11-15', 'Computer Lab'),
    ('Coding Competition', '2026-11-20', 'Seminar Hall'),
    ('Project Exhibition', '2026-11-25', 'Main Auditorium');

INSERT INTO registrations (student_id, event_id)
VALUES
    (1, 1),
    (1, 2),
    (2, 1),
    (3, 3);