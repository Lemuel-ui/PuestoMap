<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['bplo_staff']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$vendors = get_all_vendors($pdo);
$counts = get_permit_counts($vendors);
$notifications = get_notifications($pdo, 4);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Good day, <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?>.</h1>
        <p>Here's what's happening with registered vendors and permits today.</p>
    </div>
    <a href="vendor_registration.php" class="btn btn-accent">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Register New Vendor
    </a>
</div>

<div class="stat-grid">
    <div class="stat-card" style="--stat-color:var(--color-primary);">
        <div class="stat-label">Total Vendors</div>
        <div class="stat-value"><?php echo count($vendors); ?></div>
        <div class="stat-sub">Registered in Manolo Fortich</div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-success);">
        <div class="stat-label">Active Permits</div>
        <div class="stat-value"><?php echo $counts['active']; ?></div>
        <div class="stat-sub">In good standing</div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-accent);">
        <div class="stat-label">Expiring Soon</div>
        <div class="stat-value"><?php echo $counts['expiring']; ?></div>
        <div class="stat-sub">Within 30 days</div>
    </div>
    <div class="stat-card" style="--stat-color:var(--color-danger);">
        <div class="stat-label">Expired</div>
        <div class="stat-value"><?php echo $counts['expired']; ?></div>
        <div class="stat-sub">Needs immediate follow-up</div>
    </div>
</div>

<div class="row" style="display:grid;grid-template-columns:1.6fr 1fr;gap:1.25rem;align-items:start;">
    <div class="card">
        <div class="card-header">
            <h3>Vendors Needing Renewal Attention</h3>
            <a href="permit_management.php" class="btn btn-outline btn-sm">View All Permits</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="pm-table">
                <thead>
                    <tr><th>Vendor</th><th>Barangay</th><th>Permit No.</th><th>Expiry</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($vendors as $v): if ($v['permit_status'] === 'active') continue; ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($v['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($v['barangay']); ?></td>
                        <td class="mono"><?php echo htmlspecialchars($v['permit_no']); ?></td>
                        <td><?php echo htmlspecialchars($v['expiry']); ?></td>
                        <td><span class="pin-status <?php echo $v['permit_status']; ?>"><?php echo ucfirst($v['permit_status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Recent Notifications</h3></div>
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
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
