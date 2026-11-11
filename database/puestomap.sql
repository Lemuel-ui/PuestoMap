-- =============================================================================
-- PuestoMap Database Schema
-- MySQL / MariaDB 10.4+ (XAMPP) — phpMyAdmin compatible
-- -----------------------------------------------------------------------------
-- Vendor locations are stored as plain DECIMAL latitude/longitude columns
-- (no spatial extension required) and rendered with Leaflet.js.
--
-- Import this file through phpMyAdmin (XAMPP Control Panel -> Admin -> Import)
-- or via the command line:
--   mysql -u root -p < puestomap.sql
-- The CREATE DATABASE statement below creates the `puestomap` schema for you.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS puestomap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE puestomap;

-- ---------------------------------------------------------------------------
-- Users: Admin / BPLO Head Officer, BPLO Staff,
--        Business Permit Inspector, Vendor
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS account_registration_requests;
DROP TABLE IF EXISTS business_location_requests;
DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS inspection_photos;
DROP TABLE IF EXISTS inspections;
DROP TABLE IF EXISTS permits;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','bplo_staff','inspector','vendor') NOT NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Self-registration requests. New staff, inspector, and vendor accounts
-- remain here until an administrator approves them. No login account exists
-- in `users` until approval.
-- ---------------------------------------------------------------------------
CREATE TABLE account_registration_requests (
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

-- ---------------------------------------------------------------------------
-- Vendors: registered businesses with map location (plain lat/lng, no GIS)
-- ---------------------------------------------------------------------------
CREATE TABLE vendors (
    vendor_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name  VARCHAR(150) NOT NULL,
    owner_name     VARCHAR(150) NOT NULL,
    category       VARCHAR(100),
    barangay       VARCHAR(100) NOT NULL,
    address        VARCHAR(255),
    contact_no     VARCHAR(30),
    latitude       DECIMAL(10,7) NOT NULL,   -- pin dropped on Leaflet.js map
    longitude      DECIMAL(10,7) NOT NULL,   -- pin dropped on Leaflet.js map
    registered_by  INT UNSIGNED NULL,
    vendor_user_id INT UNSIGNED NULL,        -- links to the vendor's own login account
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendors_registered_by FOREIGN KEY (registered_by) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_vendors_user FOREIGN KEY (vendor_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_vendors_latlng (latitude, longitude),
    INDEX idx_vendors_barangay (barangay)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Business Permits: one active/historical permit trail per vendor
-- ---------------------------------------------------------------------------
CREATE TABLE permits (
    permit_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id    INT UNSIGNED NOT NULL,
    permit_no    VARCHAR(50) NOT NULL UNIQUE,
    issued_date  DATE NOT NULL,
    expiry_date  DATE NOT NULL,
    status       ENUM('active','expiring','expired','revoked') NOT NULL DEFAULT 'active',
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_permits_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE,
    INDEX idx_permits_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Inspections: field visits performed by Business Permit Inspectors
-- ---------------------------------------------------------------------------
CREATE TABLE inspections (
    inspection_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id       INT UNSIGNED NOT NULL,
    inspector_id    INT UNSIGNED NOT NULL,
    scheduled_date  DATE NOT NULL,
    status          ENUM('pending','completed','flagged') NOT NULL DEFAULT 'pending',
    remarks         TEXT NULL,
    completed_at    TIMESTAMP NULL,
    CONSTRAINT fk_inspections_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE,
    CONSTRAINT fk_inspections_inspector FOREIGN KEY (inspector_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Inspection Photos: field photos uploaded by inspectors
-- ---------------------------------------------------------------------------
CREATE TABLE inspection_photos (
    photo_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT UNSIGNED NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photos_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Notifications: renewal reminders and system alerts
-- ---------------------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NULL,
    permit_id        INT UNSIGNED NULL,
    type             ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    message          TEXT NOT NULL,
    is_read          TINYINT(1) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_permit FOREIGN KEY (permit_id) REFERENCES permits(permit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- System backups log (Administrator's Backup & Restore module)
-- ---------------------------------------------------------------------------
CREATE TABLE backup_logs (
    backup_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path    VARCHAR(255) NOT NULL,
    backup_type  ENUM('automatic','manual') NOT NULL DEFAULT 'manual',
    file_size_mb DECIMAL(10,2) NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'completed',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Location-change requests: a vendor asks to move their pin; BPLO Staff
-- approve or reject it (Approve updates the vendor's address and coordinates)
-- ---------------------------------------------------------------------------
CREATE TABLE business_location_requests (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id          INT UNSIGNED NOT NULL,
    requested_address  VARCHAR(255) NOT NULL,
    requested_barangay VARCHAR(100) NOT NULL,
    requested_lat      DECIMAL(10,7) NOT NULL,
    requested_lng      DECIMAL(10,7) NOT NULL,
    status             ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    requested_by       INT UNSIGNED NOT NULL,
    reviewed_by        INT UNSIGNED NULL,
    review_note        TEXT NULL,
    requested_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at        TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_locreq_vendor    FOREIGN KEY (vendor_id)    REFERENCES vendors(vendor_id) ON DELETE CASCADE,
    CONSTRAINT fk_locreq_requester FOREIGN KEY (requested_by) REFERENCES users(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_locreq_reviewer  FOREIGN KEY (reviewed_by)  REFERENCES users(user_id)     ON DELETE SET NULL,
    INDEX idx_locreq_status (status),
    INDEX idx_locreq_vendor (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;


-- Clean schema only: no demo users, vendors, permits, inspections, or sample logs.
-- IMPORTANT: the installer above drops/recreates tables. Import ONLY into a new/empty database.
