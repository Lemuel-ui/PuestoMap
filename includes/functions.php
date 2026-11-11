<?php
/**
 * functions.php
 * -----------------------------------------------------------------------
 * Data-access helpers used across every PuestoMap page. All queries use
 * PDO prepared statements against the MySQL/MariaDB `puestomap` database.
 * -----------------------------------------------------------------------
 */

require_once __DIR__ . '/db.php';


/* ===========================================================================
 * CSRF protection helpers
 * Add csrf_field() to every state-changing POST form and call verify_csrf()
 * before processing its submitted values.
 * ======================================================================= */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Invalid or expired security token. Please go back, reload the page, and try again.');
    }
}

/* ===========================================================================
 * Permit status helpers
 * ======================================================================= */

// Derives the live display status of a permit from its expiry date, unless
// it has been explicitly revoked. "Expiring" = active and due within 30 days.
function permit_display_status(string $expiryDate, string $storedStatus): string {
    if ($storedStatus === 'revoked') {
        return 'revoked';
    }
    $today = new DateTime('today');
    $expiry = new DateTime($expiryDate);
    $daysLeft = (int)$today->diff($expiry)->format('%r%a');

    if ($daysLeft < 0) return 'expired';
    if ($daysLeft <= 30) return 'expiring';
    return 'active';
}

// Tallies active/expiring/expired counts the way the dashboards display them.
function get_permit_counts(array $vendors): array {
    $counts = ['active' => 0, 'expiring' => 0, 'expired' => 0, 'revoked' => 0];
    foreach ($vendors as $v) {
        $status = $v['permit_status'] ?? 'active';
        if (!isset($counts[$status])) $counts[$status] = 0;
        $counts[$status]++;
    }
    return $counts;
}

/* ===========================================================================
 * Users
 * ======================================================================= */

function get_all_users(PDO $pdo): array {
    $stmt = $pdo->query('SELECT user_id, full_name, email, role, is_active, created_at FROM users ORDER BY full_name');
    return $stmt->fetchAll();
}

function get_user_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user(PDO $pdo, string $fullName, string $email, string $password, string $role): array {
    $fullName = trim($fullName);
    $email = strtolower(trim($email));
    $allowedRoles = ['admin', 'bplo_staff', 'inspector', 'vendor'];

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Enter a valid name and email address.'];
    }
    if (strlen($password) < 12) {
        return ['ok' => false, 'error' => 'Use a password with at least 12 characters.'];
    }
    if (!in_array($role, $allowedRoles, true)) {
        return ['ok' => false, 'error' => 'The selected account role is invalid.'];
    }

    $existing = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    if ($existing->fetch()) {
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$fullName, $email, $hash, $role]);
    return ['ok' => true, 'user_id' => (int)$pdo->lastInsertId()];
}

function set_user_active(PDO $pdo, int $userId, bool $active): void {
    $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
    $stmt->execute([$active ? 1 : 0, $userId]);
}

/* ===========================================================================
 * Vendors (joined with their latest permit; each row is mapped into the
 * shape: id, name, owner, category, barangay, lat, lng, permit_no,
 * permit_status, expiry, contact)
 * ======================================================================= */

const VENDOR_SELECT = "
    SELECT v.vendor_id, v.business_name, v.owner_name, v.category, v.barangay,
           v.address, v.contact_no, v.latitude, v.longitude, v.vendor_user_id, v.is_active,
           p.permit_id, p.permit_no, p.issued_date, p.expiry_date, p.status AS permit_stored_status
    FROM vendors v
    LEFT JOIN permits p ON p.permit_id = (
        SELECT p2.permit_id FROM permits p2 WHERE p2.vendor_id = v.vendor_id
        ORDER BY p2.issued_date DESC, p2.permit_id DESC LIMIT 1
    )
";

function map_vendor_row(array $row): array {
    $status = $row['permit_stored_status']
        ? permit_display_status($row['expiry_date'], $row['permit_stored_status'])
        : 'active';
    return [
        'vendor_id'     => (int)$row['vendor_id'],
        'vendor_user_id' => isset($row['vendor_user_id']) && $row['vendor_user_id'] !== null ? (int)$row['vendor_user_id'] : null,
        'id'            => 'VND-' . str_pad((string)$row['vendor_id'], 4, '0', STR_PAD_LEFT),
        'name'          => $row['business_name'],
        'owner'         => $row['owner_name'],
        'category'      => $row['category'],
        'barangay'      => $row['barangay'],
        'address'       => $row['address'],
        'contact'       => $row['contact_no'],
        'lat'           => (float)$row['latitude'],
        'lng'           => (float)$row['longitude'],
        'permit_id'     => $row['permit_id'] ? (int)$row['permit_id'] : null,
        'permit_no'     => $row['permit_no'] ?? '—',
        'permit_status' => $status,
        'expiry'        => $row['expiry_date'] ?? '—',
        'is_active'     => (bool)$row['is_active'],
    ];
}

function get_all_vendors(PDO $pdo, bool $activeOnly = true): array {
    $sql = VENDOR_SELECT . ($activeOnly ? ' WHERE v.is_active = 1' : '') . ' ORDER BY v.business_name';
    $rows = $pdo->query($sql)->fetchAll();
    return array_map('map_vendor_row', $rows);
}

function get_vendor(PDO $pdo, int $vendorId): ?array {
    $stmt = $pdo->prepare(VENDOR_SELECT . ' WHERE v.vendor_id = ?');
    $stmt->execute([$vendorId]);
    $row = $stmt->fetch();
    return $row ? map_vendor_row($row) : null;
}

// Looks up the vendor record tied to a logged-in Vendor user account.
function get_vendor_for_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare(VENDOR_SELECT . ' WHERE v.vendor_user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? map_vendor_row($row) : null;
}

function create_vendor(PDO $pdo, array $data): array {
    // Server-side validation is authoritative; browser required attributes alone are not enough.
    foreach (['business_name', 'owner_name', 'category', 'barangay', 'contact_no', 'lat', 'lng', 'permit_no', 'expiry_date'] as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            return ['ok' => false, 'error' => 'Please complete all required business, contact, location, and permit fields.'];
        }
    }
    if (!is_numeric($data['lat']) || !is_numeric($data['lng']) || (float)$data['lat'] < -90 || (float)$data['lat'] > 90 || (float)$data['lng'] < -180 || (float)$data['lng'] > 180) {
        return ['ok' => false, 'error' => 'Enter valid latitude and longitude coordinates by selecting a point on the map.'];
    }
    $expiryCheck = DateTime::createFromFormat('Y-m-d', (string)$data['expiry_date']);
    if (!$expiryCheck || $expiryCheck->format('Y-m-d') !== $data['expiry_date']) {
        return ['ok' => false, 'error' => 'Please provide a valid permit expiry date.'];
    }
    $duplicate = $pdo->prepare('SELECT vendor_id FROM vendors WHERE LOWER(TRIM(business_name)) = LOWER(TRIM(?)) AND LOWER(TRIM(barangay)) = LOWER(TRIM(?)) LIMIT 1');
    $duplicate->execute([$data['business_name'], $data['barangay']]);
    if ($duplicate->fetchColumn()) {
        return ['ok' => false, 'error' => 'A business with the same name is already registered in this barangay. Please check the existing vendor records.'];
    }
    $linkedUserId = !empty($data['vendor_user_id']) ? (int)$data['vendor_user_id'] : 0;
    if ($linkedUserId > 0) {
        $eligible = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'vendor' AND is_active = 1 LIMIT 1");
        $eligible->execute([$linkedUserId]);
        if (!$eligible->fetchColumn()) {
            return ['ok' => false, 'error' => 'The selected Vendor account is not active or approved.'];
        }
        $alreadyLinked = $pdo->prepare('SELECT vendor_id FROM vendors WHERE vendor_user_id = ? LIMIT 1');
        $alreadyLinked->execute([$linkedUserId]);
        if ($alreadyLinked->fetchColumn()) {
            return ['ok' => false, 'error' => 'The selected Vendor account is already linked to another business.'];
        }
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO vendors (business_name, owner_name, category, barangay, address, contact_no, latitude, longitude, registered_by, vendor_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['business_name'], $data['owner_name'], $data['category'], $data['barangay'],
            $data['address'], $data['contact_no'], $data['lat'], $data['lng'], $data['registered_by'],
            $linkedUserId > 0 ? $linkedUserId : null,
        ]);
        $vendorId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO permits (vendor_id, permit_no, issued_date, expiry_date, status) VALUES (?, ?, CURDATE(), ?, ?)'
        );
        $stmt->execute([$vendorId, $data['permit_no'], $data['expiry_date'], 'active']);

        $stmt = $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('success', ?)");
        $stmt->execute(['Vendor "' . $data['business_name'] . '" was successfully registered.']);

        $pdo->commit();
        return ['ok' => true, 'vendor_id' => $vendorId];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $msg = $e instanceof PDOException && str_contains($e->getMessage(), 'permit_no') ? 'That permit number is already in use.' : 'Could not save the vendor record.';
        return ['ok' => false, 'error' => $msg];
    }
}

function update_vendor(PDO $pdo, int $vendorId, array $data): array {
    foreach (['business_name', 'owner_name', 'category', 'barangay', 'contact_no', 'lat', 'lng'] as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            return ['ok' => false, 'error' => 'Business name, owner, category, barangay, contact number, and map coordinates are required.'];
        }
    }
    if (!is_numeric($data['lat']) || !is_numeric($data['lng']) || (float)$data['lat'] < -90 || (float)$data['lat'] > 90 || (float)$data['lng'] < -180 || (float)$data['lng'] > 180) {
        return ['ok' => false, 'error' => 'Please enter valid map coordinates.'];
    }
    $duplicate = $pdo->prepare('SELECT vendor_id FROM vendors WHERE LOWER(TRIM(business_name)) = LOWER(TRIM(?)) AND LOWER(TRIM(barangay)) = LOWER(TRIM(?)) AND vendor_id <> ? LIMIT 1');
    $duplicate->execute([$data['business_name'], $data['barangay'], $vendorId]);
    if ($duplicate->fetchColumn()) return ['ok' => false, 'error' => 'Another business with the same name is already registered in this barangay.'];
    $stmt = $pdo->prepare(
        'UPDATE vendors SET business_name = ?, owner_name = ?, category = ?, barangay = ?, address = ?, contact_no = ?, latitude = ?, longitude = ?
         WHERE vendor_id = ?'
    );
    $stmt->execute([
        $data['business_name'], $data['owner_name'], $data['category'], $data['barangay'],
        $data['address'], $data['contact_no'], $data['lat'], $data['lng'], $vendorId,
    ]);
    return ['ok' => true];
}

function set_vendor_active(PDO $pdo, int $vendorId, bool $active): void {
    $stmt = $pdo->prepare('UPDATE vendors SET is_active = ? WHERE vendor_id = ?');
    $stmt->execute([$active ? 1 : 0, $vendorId]);
}

/* ===========================================================================
 * Permits
 * ======================================================================= */

function renew_permit(PDO $pdo, int $permitId, string $newExpiryDate): bool {
    $stmt = $pdo->prepare("UPDATE permits SET status = 'active', issued_date = CURDATE(), expiry_date = ? WHERE permit_id = ?");
    return $stmt->execute([$newExpiryDate, $permitId]);
}

function revoke_permit(PDO $pdo, int $permitId): bool {
    $stmt = $pdo->prepare("UPDATE permits SET status = 'revoked' WHERE permit_id = ?");
    return $stmt->execute([$permitId]);
}

/* ===========================================================================
 * Inspections
 * ======================================================================= */

const INSPECTION_SELECT = "
    SELECT i.inspection_id, i.vendor_id, i.inspector_id, i.scheduled_date, i.status, i.remarks, i.completed_at,
           v.business_name, v.barangay
    FROM inspections i
    JOIN vendors v ON v.vendor_id = i.vendor_id
";

function map_inspection_row(array $row): array {
    return [
        'inspection_id'  => (int)$row['inspection_id'],
        'id'             => 'INS-' . str_pad((string)$row['inspection_id'], 3, '0', STR_PAD_LEFT),
        'vendor_id'      => (int)$row['vendor_id'],
        'vendor'         => $row['business_name'],
        'barangay'       => $row['barangay'],
        'scheduled'      => $row['scheduled_date'],
        'status'         => $row['status'],
        'status_label'   => ucfirst($row['status'] === 'flagged' ? 'Flagged for Review' : $row['status']),
        'remarks'        => $row['remarks'],
        'completed_at'   => $row['completed_at'],
    ];
}

function get_all_inspections(PDO $pdo): array {
    $rows = $pdo->query(INSPECTION_SELECT . ' ORDER BY i.scheduled_date DESC')->fetchAll();
    return array_map('map_inspection_row', $rows);
}

function get_inspections_for_inspector(PDO $pdo, int $inspectorId): array {
    $stmt = $pdo->prepare(INSPECTION_SELECT . ' WHERE i.inspector_id = ? ORDER BY (i.status = "pending") DESC, i.scheduled_date ASC');
    $stmt->execute([$inspectorId]);
    return array_map('map_inspection_row', $stmt->fetchAll());
}

function update_inspection_status(PDO $pdo, int $inspectionId, int $inspectorId, string $status, string $remarks): bool {
    if (!in_array($status, ['completed', 'flagged'], true)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE inspections
        SET status = ?, remarks = ?, completed_at = NOW()
        WHERE inspection_id = ? AND inspector_id = ? AND status = 'pending'");
    $stmt->execute([$status, $remarks, $inspectionId, $inspectorId]);

    if ($stmt->rowCount() !== 1) {
        return false;
    }

    if ($status === 'flagged') {
        $vendorStmt = $pdo->prepare('SELECT v.business_name FROM vendors v JOIN inspections i ON i.vendor_id = v.vendor_id WHERE i.inspection_id = ? AND i.inspector_id = ?');
        $vendorStmt->execute([$inspectionId, $inspectorId]);
        $vendorName = $vendorStmt->fetchColumn() ?: 'a vendor';
        $notif = $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('danger', ?)");
        $notif->execute(["Inspection at $vendorName was flagged for review."]);
    }
    return true;
}

function add_inspection_photo(PDO $pdo, int $inspectionId, string $relativePath): void {
    $stmt = $pdo->prepare('INSERT INTO inspection_photos (inspection_id, file_path) VALUES (?, ?)');
    $stmt->execute([$inspectionId, $relativePath]);
}

function get_inspection_photos(PDO $pdo, int $inspectionId): array {
    $stmt = $pdo->prepare('SELECT file_path, uploaded_at FROM inspection_photos WHERE inspection_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$inspectionId]);
    return $stmt->fetchAll();
}

/* ===========================================================================
 * Notifications
 * ======================================================================= */

/**
 * Create one-time permit expiry reminders. The unique notification_key in the
 * migration prevents duplicate reminders even when several pages are opened.
 * This function is also called by the daily CLI task.
 */
function generate_permit_expiry_notifications(PDO $pdo): int {
    $today = new DateTimeImmutable('today');
    $stmt = $pdo->query("SELECT p.permit_id, p.permit_no, p.expiry_date, v.business_name,
        v.vendor_user_id FROM permits p
        INNER JOIN vendors v ON v.vendor_id = p.vendor_id
        WHERE p.status <> 'revoked' AND v.is_active = 1
        AND p.permit_id = (SELECT p2.permit_id FROM permits p2
            WHERE p2.vendor_id = p.vendor_id
            ORDER BY p2.issued_date DESC, p2.permit_id DESC LIMIT 1)");
    $insert = $pdo->prepare("INSERT IGNORE INTO notifications
        (user_id, permit_id, type, message, notification_key)
        VALUES (?, ?, ?, ?, ?)");
    $created = 0;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $permit) {
        if (empty($permit['vendor_user_id']) || empty($permit['expiry_date'])) continue;
        $expiry = new DateTimeImmutable($permit['expiry_date']);
        $days = (int)$today->diff($expiry)->format('%r%a');
        $events = [];
        if ($days === 30) $events[] = ['warning', '30d', 'Your business permit will expire in 30 days. Please prepare for renewal.'];
        if ($days === 7)  $events[] = ['warning', '7d', 'Your business permit will expire in 7 days.'];
        if ($days === 1)  $events[] = ['warning', '1d', 'Your business permit will expire tomorrow.'];
        if ($days === 0)  $events[] = ['danger', 'expiry', 'Your business permit expires today. Please process your renewal.'];
        if ($days < 0)    $events[] = ['danger', 'expired', 'Your business permit is already expired. Please process your renewal.'];

        foreach ($events as [$type, $event, $message]) {
            $fullMessage = $message . ' Permit No.: ' . $permit['permit_no'] . '.';
            $key = 'permit:' . $permit['permit_id'] . ':' . $permit['expiry_date'] . ':' . $event . ':user:' . (int)$permit['vendor_user_id'];
            $insert->execute([(int)$permit['vendor_user_id'], (int)$permit['permit_id'], $type, $fullMessage, $key]);
            $created += $insert->rowCount();
        }

        // Also alert each Admin / BPLO Head Officer for office-wide monitoring.
        $admins = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($events as [$type, $event, $message]) {
            foreach ($admins as $adminId) {
                $adminMessage = $permit['business_name'] . ': ' . $message . ' Permit No.: ' . $permit['permit_no'] . '.';
                $key = 'permit:' . $permit['permit_id'] . ':' . $permit['expiry_date'] . ':' . $event . ':admin:' . (int)$adminId;
                $insert->execute([(int)$adminId, (int)$permit['permit_id'], $type, $adminMessage, $key]);
                $created += $insert->rowCount();
            }
        }
    }
    return $created;
}

function get_permits_in_expiry_window(PDO $pdo, int $days = 30): array {
    $days = max(0, min(3650, $days));
    $sql = VENDOR_SELECT . " WHERE v.is_active = 1 AND p.status <> 'revoked'
        AND p.permit_id = (SELECT p2.permit_id FROM permits p2 WHERE p2.vendor_id = v.vendor_id ORDER BY p2.issued_date DESC, p2.permit_id DESC LIMIT 1)
        AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . $days . " DAY)
        ORDER BY p.expiry_date ASC";
    return array_map('map_vendor_row', $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
}

function get_notifications(PDO $pdo, int $limit = 10, ?int $userId = null): array {
    $userId = $userId ?? (int)($_SESSION['user_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT n.notification_id AS id, n.type, n.message, n.created_at, n.is_read,
        v.business_name, p.permit_no
        FROM notifications n
        LEFT JOIN permits p ON p.permit_id = n.permit_id
        LEFT JOIN vendors v ON v.vendor_id = p.vendor_id
        WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['time'] = time_ago($r['created_at']);
    return $rows;
}

function count_notifications(PDO $pdo, ?int $userId = null): int {
    $userId = $userId ?? (int)($_SESSION['user_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    $days = floor($diff / 86400);
    return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
}

/* ===========================================================================
 * Backup & Restore (admin/backup_restore.php)
 * ======================================================================= */

function get_backup_logs(PDO $pdo): array {
    return $pdo->query('SELECT backup_id, file_path, backup_type, file_size_mb, status, created_at FROM backup_logs ORDER BY created_at DESC')->fetchAll();
}

// Lightweight logical backup: dumps every table's rows as INSERT statements
// into a timestamped .sql file under /backups (no external mysqldump needed).
function run_backup(PDO $pdo, string $type = 'manual'): array {
    $dir = BACKUP_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Private backup directory could not be created.'];
    }

    $filename = APP_SLUG . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
    $path = $dir . $filename;
    $tables = [
        'users', 'vendors', 'permits', 'inspections',
        'inspection_photos', 'notifications',
        'business_location_requests', 'backup_logs'
    ];

    $out = "-- " . APP_NAME . " backup — " . date('Y-m-d H:i:s') . "\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    try {
        foreach ($tables as $table) {
            // Always truncate every table on restore, including tables that
            // happened to be empty when the snapshot was created.
            $out .= "TRUNCATE TABLE `$table`;\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
            if (!$rows) {
                $out .= "\n";
                continue;
            }

            $columns = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $columns) . '`';
            foreach ($rows as $row) {
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row)
                );
                $out .= "INSERT INTO `$table` ($colList) VALUES (" . implode(', ', $vals) . ");\n";
            }
            $out .= "\n";
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $written = file_put_contents($path, $out, LOCK_EX);
        if ($written === false) {
            return ['ok' => false, 'error' => 'The backup file could not be written. Check private folder permissions.'];
        }

        @chmod($path, 0600);
        $sizeMb = round(filesize($path) / (1024 * 1024), 2);
        $stmt = $pdo->prepare(
            'INSERT INTO backup_logs (file_path, backup_type, file_size_mb, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$filename, $type, $sizeMb, 'completed']);

        return ['ok' => true, 'file' => $filename];
    } catch (Throwable $e) {
        if (is_file($path)) {
            @unlink($path);
        }
        error_log(APP_NAME . ' backup failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Backup failed. Check server logs and private backup folder permissions.'];
    }
}

function run_restore(PDO $pdo, string $filename): array {
    // Accept only a generated filename; never accept a user-supplied path.
    if (!preg_match('/^' . preg_quote(APP_SLUG, '/') . '_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\\.sql$/', $filename)) {
        return ['ok' => false, 'error' => 'Invalid backup file selected.'];
    }

    $fullPath = BACKUP_DIR . $filename;
    if (!is_file($fullPath) || !is_readable($fullPath)) {
        return ['ok' => false, 'error' => 'Backup file not found or not readable.'];
    }

    $sql = file_get_contents($fullPath);
    if ($sql === false || $sql === '') {
        return ['ok' => false, 'error' => 'Backup file is empty or could not be read.'];
    }

    try {
        $pdo->exec($sql);
        return ['ok' => true];
    } catch (PDOException $e) {
        error_log(APP_NAME . ' restore failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Restore failed. Check server logs; current data may require verification.'];
    }
}


/* ===========================================================================
 * Vendor location-change requests
 * Vendors submit a new pin location from their Business Profile; BPLO Staff
 * approve or reject it. Approval updates the vendor's address, barangay and
 * map coordinates in a single transaction.
 * ======================================================================= */

function get_location_requests(PDO $pdo, ?string $status = null): array {
    $sql = "SELECT r.id, r.vendor_id, r.requested_address, r.requested_barangay,
                   r.requested_lat, r.requested_lng, r.status, r.review_note,
                   r.requested_at, r.reviewed_at,
                   v.business_name, v.owner_name, v.address AS current_address,
                   v.barangay AS current_barangay,
                   v.latitude AS current_lat, v.longitude AS current_lng
            FROM business_location_requests r
            JOIN vendors v ON v.vendor_id = r.vendor_id";
    $params = [];
    if ($status !== null) {
        $sql .= ' WHERE r.status = ?';
        $params[] = $status;
    }
    $sql .= " ORDER BY (r.status = 'Pending') DESC, r.requested_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_pending_location_requests(PDO $pdo): int {
    return (int) $pdo->query("SELECT COUNT(*) FROM business_location_requests WHERE status = 'Pending'")->fetchColumn();
}

function review_location_request(PDO $pdo, int $requestId, bool $approve, int $reviewerId, string $note): array {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM business_location_requests WHERE id = ? AND status = 'Pending' FOR UPDATE");
        $stmt->execute([$requestId]);
        $req = $stmt->fetch();
        if (!$req) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'That request was not found or has already been reviewed.'];
        }

        if ($approve) {
            $upd = $pdo->prepare('UPDATE vendors SET address = ?, barangay = ?, latitude = ?, longitude = ? WHERE vendor_id = ?');
            $upd->execute([
                $req['requested_address'], $req['requested_barangay'],
                $req['requested_lat'], $req['requested_lng'], $req['vendor_id'],
            ]);
        }

        $done = $pdo->prepare('UPDATE business_location_requests SET status = ?, reviewed_by = ?, review_note = ?, reviewed_at = NOW() WHERE id = ?');
        $done->execute([$approve ? 'Approved' : 'Rejected', $reviewerId, $note !== '' ? $note : null, $requestId]);

        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log(APP_NAME . ' location review error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save the review. Please try again.'];
    }
}
