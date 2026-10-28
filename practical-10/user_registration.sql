USE studenthub_db;

SET @add_role_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'user_accounts'
       AND COLUMN_NAME = 'role') = 0,
    'ALTER TABLE user_accounts ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT ''student'' AFTER password_hash',
    'SELECT 1'
);
PREPARE add_role_statement FROM @add_role_sql;
EXECUTE add_role_statement;
DEALLOCATE PREPARE add_role_statement;

SET @add_last_login_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'user_accounts'
       AND COLUMN_NAME = 'last_login_at') = 0,
    'ALTER TABLE user_accounts ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER created_at',
    'SELECT 1'
);
PREPARE add_last_login_statement FROM @add_last_login_sql;
EXECUTE add_last_login_statement;
DEALLOCATE PREPARE add_last_login_statement;

CREATE TABLE IF NOT EXISTS p10_remember_tokens (
    token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(18) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    validator_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_p10_remember_tokens_selector (selector),
    KEY idx_p10_remember_tokens_user (user_id),
    CONSTRAINT fk_p10_remember_token_user
        FOREIGN KEY (user_id)
        REFERENCES user_accounts (user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS p10_attendance (
    attendance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    marked_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_p10_attendance_student_date (student_id, attendance_date),
    CONSTRAINT fk_p10_attendance_student FOREIGN KEY (student_id)
        REFERENCES user_accounts (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_p10_attendance_admin FOREIGN KEY (marked_by)
        REFERENCES user_accounts (user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS p10_timetable (
    timetable_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    subject VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS p10_leave_requests (
    leave_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    leave_type VARCHAR(40) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_p10_leave_student FOREIGN KEY (student_id)
        REFERENCES user_accounts (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_p10_leave_admin FOREIGN KEY (reviewed_by)
        REFERENCES user_accounts (user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO user_accounts
    (full_name, username, email, password_hash, role)
VALUES
    ('Student Demo', 'student_demo', 'student.demo@studenthub.test',
     '$2y$10$bd8ijHKFz6OEBcULm09N8OQhHI6MU21Zyb.mDOjMe.O0G/YjxZ6qC', 'student'),
    ('Admin Demo', 'admin_demo', 'admin.demo@studenthub.test',
     '$2y$10$sodm12hcTb8Jlc1Al.G8qOv1ErOh9wrj6SpniUJTqcOMBfBjdxpNG', 'admin');
