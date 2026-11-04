<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['inspector']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Inspection Map';
$activePage = 'gis_map';
$vendors = get_all_vendors($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Inspection Map</h1>
        <p>Locate vendors in the field quickly using GPS-accurate pins instead of asking around the neighborhood.</p>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:.75rem;">
        <h3>Find a Vendor</h3>
        <div class="toolbar" style="margin:0;">
            <input type="text" class="form-control" id="mapSearch" placeholder="Search vendor name or barangay...">
        </div>
    </div>
    <div class="card-body">
        <div id="gis-map"></div>
        <div class="map-legend">
            <span><i style="background:#2E8B57"></i> Active Permit</span>
            <span><i style="background:#E8871E"></i> Expiring Soon</span>
            <span><i style="background:#C1443C"></i> Expired &mdash; priority inspection</span>
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
