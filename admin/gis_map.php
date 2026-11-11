<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Vendor Map (GIS)';
$activePage = 'gis_map';
$vendors = get_all_vendors($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Vendor Map</h1>
        <p>System-wide GIS view for administrative oversight.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Municipality of Manolo Fortich</h3>
        <div class="toolbar" style="margin:0;flex-wrap:wrap;">
            <input type="text" class="form-control" id="mapSearch" placeholder="Search vendor..." style="max-width:220px;">
            <select class="form-control" id="mapBarangay" style="max-width:180px;"><option value="">All Barangays</option><?php foreach (array_unique(array_column($vendors, 'barangay')) as $b): ?><option value="<?php echo htmlspecialchars($b, ENT_QUOTES); ?>"><?php echo htmlspecialchars($b); ?></option><?php endforeach; ?></select>
            <select class="form-control" id="mapCategory" style="max-width:200px;"><option value="">All Business Types</option><?php foreach (array_unique(array_filter(array_column($vendors, 'category'))) as $c): ?><option value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>"><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select>
            <select class="form-control" id="mapStatus" style="max-width:170px;"><option value="">All Permit Statuses</option><option value="active">Active</option><option value="expiring">Expiring</option><option value="expired">Expired</option></select>
        </div>
    </div>
    <div class="card-body">
        <div id="gis-map"></div>
        <div class="map-legend">
            <span><i style="background:#2E8B57"></i> Active Permit</span>
            <span><i style="background:#E8871E"></i> Expiring Soon</span>
            <span><i style="background:#C1443C"></i> Expired</span>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/map.js"></script>
<script>
    var vendors = <?php echo json_encode($vendors); ?>;
    var mapHandle = initPuestoMap('gis-map', vendors);
    function applyMapFilters() {
        pmFilterMap(mapHandle, vendors, {
            query: document.getElementById('mapSearch').value,
            barangay: document.getElementById('mapBarangay').value,
            category: document.getElementById('mapCategory').value,
            status: document.getElementById('mapStatus').value
        });
    }
    ['mapSearch','mapBarangay','mapCategory','mapStatus'].forEach(function(id) {
        document.getElementById(id).addEventListener(id === 'mapSearch' ? 'input' : 'change', applyMapFilters);
    });
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
