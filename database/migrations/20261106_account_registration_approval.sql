-- Run this migration once on an EXISTING PuestoMap database.
-- It creates a queue for self-registration requests. It does not delete data.
CREATE TABLE IF NOT EXISTS account_registration_requests (
    request_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name        VARCHAR(150) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    requested_role   ENUM('bplo_staff','inspector','vendor') NOT NULL,
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    review_note      VARCHAR(500) NULL,
    reviewed_by      INT UNSIGNED NULL,
    requested_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at      TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_registration_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_registration_status (status),
    INDEX idx_registration_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
