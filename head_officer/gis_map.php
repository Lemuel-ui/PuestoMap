<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['head_officer']);
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
        <p>Municipality-wide view of vendor locations and permit compliance.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Municipality of Manolo Fortich</h3>
        <input type="text" class="form-control" id="mapSearch" placeholder="Search vendor or barangay..." style="max-width:260px;">
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
    document.getElementById('mapSearch').addEventListener('input', function (e) {
        pmFilterMapBySearch(mapHandle, vendors, e.target.value);
    });
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
