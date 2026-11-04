<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Backup & Restore';
$activePage = 'backup';
$action = $_POST['action'] ?? '';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'backup') {
        $result = run_backup($pdo, 'manual');
        $message = 'A new backup snapshot was created successfully: ' . $result['file'];
    } elseif ($action === 'restore') {
        $path = $_POST['snapshot'] ?? '';
        $result = run_restore($pdo, $path);
        if ($result['ok']) {
            $message = 'System restore from the selected snapshot completed.';
        } else {
            $message = 'Restore failed: ' . $result['error'];
            $messageType = 'danger';
        }
    }
}

$backups = get_backup_logs($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Backup &amp; Restore</h1>
        <p>Protect the integrity of vendor, permit, and account data stored in MySQL/MariaDB.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="card" style="border-left:4px solid var(--color-<?php echo $messageType; ?>); margin-bottom:1.25rem;">
        <div class="card-body"><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
    <div class="card">
        <div class="card-header"><h3>Create Backup</h3></div>
        <div class="card-body">
            <p style="color:var(--color-text-muted);font-size:.9rem;">Generates a full snapshot of vendor records, permit data, and user accounts as a SQL file under <code>/backups</code>.</p>
            <form method="POST">
                <input type="hidden" name="action" value="backup">
                <button type="submit" class="btn btn-primary">Run Backup Now</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Restore from Snapshot</h3></div>
        <div class="card-body">
            <?php if ($backups): ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="snapshot">Choose a Backup Snapshot</label>
                    <select class="form-control" id="snapshot" name="snapshot">
                        <?php foreach ($backups as $b): ?>
                            <option value="<?php echo htmlspecialchars($b['file_path']); ?>">
                                <?php echo htmlspecialchars(date('Y-m-d h:i A', strtotime($b['created_at']))); ?> &mdash; <?php echo ucfirst($b['backup_type']); ?> Backup
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="action" value="restore">
                <button type="submit" class="btn btn-outline" onclick="return pmConfirmDelete('Restoring will overwrite current data with the selected snapshot. Continue?');">Restore System</button>
            </form>
            <?php else: ?>
                <p class="form-hint">No backups yet. Run one first.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top:1.25rem;">
    <div class="card-header"><h3>Backup History</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="pm-table">
            <thead><tr><th>Date &amp; Time</th><th>Type</th><th>Size</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td class="mono"><?php echo htmlspecialchars(date('Y-m-d h:i A', strtotime($b['created_at']))); ?></td>
                    <td><?php echo ucfirst($b['backup_type']); ?></td>
                    <td><?php echo $b['file_size_mb'] !== null ? number_format((float)$b['file_size_mb'], 2) . ' MB' : '—'; ?></td>
                    <td><span class="pin-status completed"><?php echo htmlspecialchars(ucfirst($b['status'])); ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$backups): ?><tr><td colspan="4" style="text-align:center;color:var(--color-text-muted);">No backups recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
