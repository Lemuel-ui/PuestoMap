<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['bplo_staff']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Edit Vendor';
$activePage = 'vendors';

$vendorId = (int)($_GET['id'] ?? $_POST['vendor_id'] ?? 0);
$vendor = get_vendor($pdo, $vendorId);
if (!$vendor) {
    header('Location: vendor_management.php');
    exit;
}

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendor'])) {
    update_vendor($pdo, $vendorId, [
        'business_name' => trim($_POST['business_name']),
        'owner_name'    => trim($_POST['owner_name']),
        'category'      => $_POST['category'],
        'barangay'      => $_POST['barangay'],
        'address'       => trim($_POST['address']),
        'contact_no'    => trim($_POST['contact']),
        'lat'           => (float)$_POST['lat'],
        'lng'           => (float)$_POST['lng'],
    ]);
    $vendor = get_vendor($pdo, $vendorId);
    $saved = true;
}

include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Edit Vendor</h1>
        <p>Update business details and, if needed, drag the pin to the vendor's correct location.</p>
    </div>
    <a href="vendor_management.php" class="btn btn-outline">Back to Vendors</a>
</div>

<?php if ($saved): ?>
    <div class="card" style="border-left:4px solid var(--color-success); margin-bottom:1.25rem;">
        <div class="card-body">Vendor record updated successfully.</div>
    </div>
<?php endif; ?>

<div class="row" style="display:grid;grid-template-columns:1fr 1.1fr;gap:1.25rem;align-items:start;">
    <div class="card">
        <div class="card-header"><h3><?php echo htmlspecialchars($vendor['name']); ?></h3></div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="vendor_id" value="<?php echo $vendor['vendor_id']; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="business_name">Business Name</label>
                        <input class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($vendor['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="owner_name">Owner's Full Name</label>
                        <input class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($vendor['owner']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="category">Business Category</label>
                        <input class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($vendor['category']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="barangay">Barangay</label>
                        <input class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($vendor['barangay']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact">Contact Number</label>
                        <input class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($vendor['contact']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="address">Complete Address</label>
                        <input class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="lat">Latitude</label>
                        <input class="form-control mono" id="lat" name="lat" value="<?php echo $vendor['lat']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="lng">Longitude</label>
                        <input class="form-control mono" id="lng" name="lng" value="<?php echo $vendor['lng']; ?>" required>
                    </div>
                </div>
                <p class="form-hint" style="margin-top:-.5rem;margin-bottom:1rem;">Click anywhere on the map to move this vendor's pin.</p>
                <button type="submit" name="update_vendor" class="btn btn-primary" style="width:100%;justify-content:center;">Save Changes</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Business Location</h3></div>
        <div class="card-body">
            <div id="gis-map"></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/map.js"></script>
<script>
    var thisVendor = <?php echo json_encode($vendor); ?>;
    var mapHandle = initPuestoMap('gis-map', [thisVendor], { center: [thisVendor.lat, thisVendor.lng], zoom: 16 });
    var marker = mapHandle.markers[0];
    mapHandle.map.on('click', function (e) {
        document.getElementById('lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('lng').value = e.latlng.lng.toFixed(6);
        marker.setLatLng(e.latlng);
    });
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
