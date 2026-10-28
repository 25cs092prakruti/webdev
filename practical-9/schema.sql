CREATE DATABASE IF NOT EXISTS studenthub_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub_db;

CREATE TABLE IF NOT EXISTS user_accounts (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_accounts_username (username),
    UNIQUE KEY uq_user_accounts_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registration_audit (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_p9_registration_audit_user
        FOREIGN KEY (user_id)
        REFERENCES user_accounts (user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
