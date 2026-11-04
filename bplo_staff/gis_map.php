<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['bplo_staff']);
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
        <p>Interactive GIS view of every registered vendor in Manolo Fortich (Leaflet.js + OpenStreetMap).</p>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:.75rem;">
        <h3>Municipality of Manolo Fortich</h3>
        <div class="toolbar" style="margin:0;">
            <input type="text" class="form-control" id="mapSearch" placeholder="Search vendor or barangay...">
        </div>
    </div>
    <div class="card-body">
        <div id="gis-map"></div>
        <div class="map-legend">
            <span><i style="background:#2E8B57"></i> Active Permit</span>
            <span><i style="background:#E8871E"></i> Expiring Soon (within 30 days)</span>
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
