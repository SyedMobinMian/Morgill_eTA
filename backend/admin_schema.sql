-- Admin module schema for dcForm
USE dcform_db;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('master','admin','staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_access_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    traveller_id INT UNSIGNED NOT NULL,
    form_number VARCHAR(30) NOT NULL UNIQUE,
    token VARCHAR(80) NOT NULL UNIQUE,
    form_country ENUM('Canada','Vietnam','UK') NOT NULL DEFAULT 'Canada',
    email_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_form_token_traveller FOREIGN KEY (traveller_id) REFERENCES travellers(id) ON DELETE CASCADE,
    INDEX idx_form_token_traveller (traveller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_email_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    traveller_id INT UNSIGNED NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject_line VARCHAR(255) NOT NULL,
    send_status ENUM('sent','failed') NOT NULL,
    error_message VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_email_traveller FOREIGN KEY (traveller_id) REFERENCES travellers(id) ON DELETE CASCADE,
    INDEX idx_admin_email_traveller (traveller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    payment_id VARCHAR(100) NOT NULL,
    reference VARCHAR(50) NOT NULL,
    receipt_file VARCHAR(255) NOT NULL,
    form_pdf_file VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_docs_app (application_id),
    INDEX idx_payment_docs_reference (reference),
    CONSTRAINT fk_payment_docs_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Do not seed a known default admin password in SQL.
-- Admin user bootstrap is handled by application code
-- and should read ADMIN_SEED_PASSWORD from environment.
