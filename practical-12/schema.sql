CREATE DATABASE IF NOT EXISTS studenthub_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub_db;

CREATE TABLE IF NOT EXISTS p12_events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    category VARCHAR(60) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    poster_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_p12_events_date (event_date),
    INDEX idx_p12_events_category (category),
    INDEX idx_p12_events_status (status)
) ENGINE=InnoDB;

INSERT INTO p12_events
    (event_name, description, event_date, venue, category, status)
SELECT 'Web Development Workshop', 'Hands-on workshop on modern web development.', '2026-11-15',
       'Computer Lab', 'Workshop', 'open'
WHERE NOT EXISTS (SELECT 1 FROM p12_events WHERE event_name = 'Web Development Workshop');

INSERT INTO p12_events
    (event_name, description, event_date, venue, category, status)
SELECT 'Coding Competition', 'Solve programming challenges and compete with other students.', '2026-11-20',
       'Seminar Hall', 'Competition', 'open'
WHERE NOT EXISTS (SELECT 1 FROM p12_events WHERE event_name = 'Coding Competition');

INSERT INTO p12_events
    (event_name, description, event_date, venue, category, status)
SELECT 'Project Exhibition', 'Showcase student projects to faculty and visitors.', '2026-11-25',
       'Main Auditorium', 'Exhibition', 'open'
WHERE NOT EXISTS (SELECT 1 FROM p12_events WHERE event_name = 'Project Exhibition');
