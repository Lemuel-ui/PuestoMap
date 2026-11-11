-- Permit expiry notification deduplication key.
-- Safe for existing notifications: old rows keep NULL keys.
ALTER TABLE notifications
    ADD COLUMN notification_key VARCHAR(190) NULL AFTER message,
    ADD UNIQUE KEY uq_notifications_notification_key (notification_key),
    ADD INDEX idx_notifications_user_unread (user_id, is_read, created_at);
