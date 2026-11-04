<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$users = get_all_users($pdo);
$vendors = get_all_vendors($pdo);
$counts = get_permit_counts($vendors);
$notifications = get_notifications($pdo, 5);
$backups = get_backup_logs($pdo);
$lastBackup = $backups[0] ?? null;

include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>System Administration</h1>
        <p>Monitor overall system health, user accounts, and data integrity.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card" style="--stat-color:var(--color-primary);">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?php echo count($users); ?></div>
        <div class="stat-sub">Across all roles</div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-success);">
        <div class="stat-label">Registered Vendors</div>
        <div class="stat-value"><?php echo count($vendors); ?></div>
        <div class="stat-sub"><?php echo $counts['active']; ?> with active permits</div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-info);">
        <div class="stat-label">Last Backup</div>
        <div class="stat-value" style="font-size:1.3rem;"><?php echo $lastBackup ? date('M j, Y', strtotime($lastBackup['created_at'])) : 'None yet'; ?></div>
        <div class="stat-sub"><?php echo $lastBackup ? ucfirst($lastBackup['backup_type']) . ' backup' : 'Run one from Backup & Restore'; ?></div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-accent);">
        <div class="stat-label">System Status</div>
        <div class="stat-value" style="font-size:1.3rem;">Operational</div>
        <div class="stat-sub">Connected to MySQL/MariaDB</div>
    </div>
</div>

<div class="row" style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.25rem;align-items:start;">
    <div class="card">
        <div class="card-header">
            <h3>User Accounts</h3>
            <a href="user_accounts.php" class="btn btn-outline btn-sm">Manage Accounts</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="pm-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td class="mono"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="badge-soft"><?php echo htmlspecialchars(ROLE_LABELS[$u['role']]); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>System Activity</h3></div>
        <div>
            <?php foreach ($notifications as $n): ?>
                <div class="notif-item">
                    <span class="notif-dot <?php echo $n['type']; ?>"></span>
                    <div>
                        <div class="notif-text"><?php echo htmlspecialchars($n['message']); ?></div>
                        <div class="notif-time"><?php echo htmlspecialchars($n['time']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$notifications): ?><p class="form-hint" style="padding:1rem;">No recent activity.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
