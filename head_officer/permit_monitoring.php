<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['head_officer']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Permit Monitoring';
$activePage = 'permits';
$vendors = get_all_vendors($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Permit Monitoring</h1>
        <p>Supervisory view of all business permits for decision-making and compliance tracking.</p>
    </div>
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
