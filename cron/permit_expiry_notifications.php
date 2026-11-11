<?php
/**
 * Run daily from Windows Task Scheduler or cron:
 * php C:\\xampp\\htdocs\\PuestoMap\\cron\\permit_expiry_notifications.php
 */
require_once __DIR__ . '/../includes/functions.php';
try {
    $pdo = get_db();
    $count = generate_permit_expiry_notifications($pdo);
    echo date('Y-m-d H:i:s') . " - Permit expiry notification scan complete. New notifications: {$count}" . PHP_EOL;
} catch (Throwable $e) {
    error_log('Permit expiry notification task failed: ' . $e->getMessage());
    fwrite(STDERR, 'Notification task failed. Check the PHP error log.' . PHP_EOL);
    exit(1);
}
