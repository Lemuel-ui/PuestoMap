-- Combine the former MISU Administrator and BPLO Head Officer roles.
-- BACK UP THE DATABASE BEFORE RUNNING THIS MIGRATION.
-- Existing head_officer accounts are promoted to admin so they retain access.
-- This migration does not delete users or other application records.

UPDATE users
SET role = 'admin'
WHERE role = 'head_officer';

ALTER TABLE users
MODIFY role ENUM('admin','bplo_staff','inspector','vendor') NOT NULL;
