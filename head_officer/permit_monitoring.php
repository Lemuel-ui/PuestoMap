<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Permit Monitoring';
$activePage = 'permits';
$vendors = get_all_vendors($pdo);
$expiryWindow = max(1, min(365, (int)($_GET['window'] ?? 30)));
$expiringVendors = get_permits_in_expiry_window($pdo, $expiryWindow);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Permit Monitoring</h1>
        <p>Supervisory view of all business permits for decision-making and compliance tracking.</p>
    </div>
    <a class="btn btn-outline" target="_blank" href="<?php echo BASE_URL; ?>/admin/permit_report.php">Print Permit Report</a>
</div>

<div class="toolbar">
    <input type="text" class="form-control" id="permitSearch" placeholder="Search vendor or barangay...">
    <select class="form-control" id="statusFilter" style="max-width:180px;">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="expiring">Expiring</option>
        <option value="expired">Expired</option>
    </select>
</div>

<div class="card" style="margin-bottom:1rem;">
    <div class="card-header"><h3>Expiring Permits</h3><form method="get" class="toolbar" style="margin:0;"><label class="form-label" for="window">Expiration window</label><select class="form-control" id="window" name="window" onchange="this.form.submit()"><option value="7" <?php echo $expiryWindow===7?'selected':''; ?>>Next 7 days</option><option value="15" <?php echo $expiryWindow===15?'selected':''; ?>>Next 15 days</option><option value="30" <?php echo $expiryWindow===30?'selected':''; ?>>Next 30 days</option><option value="60" <?php echo $expiryWindow===60?'selected':''; ?>>Next 60 days</option><option value="90" <?php echo $expiryWindow===90?'selected':''; ?>>Next 90 days</option></select></form></div>
    <div class="card-body" style="padding:0;"><table class="pm-table"><thead><tr><th>Business</th><th>Permit No.</th><th>Barangay</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody><?php foreach ($expiringVendors as $ev): ?><tr><td><?php echo htmlspecialchars($ev['name']); ?></td><td><?php echo htmlspecialchars($ev['permit_no']); ?></td><td><?php echo htmlspecialchars($ev['barangay']); ?></td><td><?php echo htmlspecialchars($ev['expiry']); ?></td><td><span class="pin-status <?php echo $ev['permit_status']; ?>"><?php echo ucfirst($ev['permit_status']); ?></span></td></tr><?php endforeach; ?><?php if (!$expiringVendors): ?><tr><td colspan="5">No permits are expiring within the selected window.</td></tr><?php endif; ?></tbody></table></div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="pm-table" id="permitTable">
            <thead>
                <tr><th>Permit No.</th><th>Vendor</th><th>Barangay</th><th>Category</th><th>Expiry</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($vendors as $v): ?>
                <tr data-name="<?php echo strtolower($v['name']); ?>" data-barangay="<?php echo strtolower($v['barangay']); ?>" data-status="<?php echo $v['permit_status']; ?>">
                    <td class="mono"><?php echo htmlspecialchars($v['permit_no']); ?></td>
                    <td><strong><?php echo htmlspecialchars($v['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($v['barangay']); ?></td>
                    <td><?php echo htmlspecialchars($v['category']); ?></td>
                    <td><?php echo htmlspecialchars($v['expiry']); ?></td>
                    <td><span class="pin-status <?php echo $v['permit_status']; ?>"><?php echo ucfirst($v['permit_status']); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    var searchInput = document.getElementById('permitSearch');
    var statusSelect = document.getElementById('statusFilter');
    var rows = document.querySelectorAll('#permitTable tbody tr');
    function applyFilters() {
        var q = searchInput.value.toLowerCase().trim();
        var status = statusSelect.value;
        rows.forEach(function (row) {
            var matchesText = !q || row.dataset.name.includes(q) || row.dataset.barangay.includes(q);
            var matchesStatus = !status || row.dataset.status === status;
            row.style.display = (matchesText && matchesStatus) ? '' : 'none';
        });
    }
    searchInput.addEventListener('input', applyFilters);
    statusSelect.addEventListener('change', applyFilters);
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
