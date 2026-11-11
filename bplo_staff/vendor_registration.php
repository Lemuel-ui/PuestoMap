<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['bplo_staff']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Vendor Registration';
$activePage = 'register';
$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration'])) {
    verify_csrf();
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';
    $businessName = trim($_POST['business_name'] ?? '');

    if ($businessName === '' || trim($_POST['owner_name'] ?? '') === '' || trim($_POST['category'] ?? '') === '' || trim($_POST['barangay'] ?? '') === '' || trim($_POST['contact'] ?? '') === '' || trim($_POST['permit_no'] ?? '') === '' || trim($_POST['expiry_date'] ?? '') === '' || $lat === '' || $lng === '') {
        $error = 'Please complete all required fields and select the business location on the map.';
    } else {
        $result = create_vendor($pdo, [
            'business_name' => $businessName,
            'owner_name'    => trim($_POST['owner_name'] ?? ''),
            'category'      => $_POST['category'] ?? '',
            'barangay'      => $_POST['barangay'] ?? '',
            'address'       => trim($_POST['address'] ?? ''),
            'contact_no'    => trim($_POST['contact'] ?? ''),
            'lat'           => (float)$lat,
            'lng'           => (float)$lng,
            'permit_no'     => trim($_POST['permit_no'] ?? ('BP-' . date('Y') . '-' . random_int(1000, 9999))),
            'expiry_date'   => $_POST['expiry_date'] ?: date('Y-m-d', strtotime('+1 year')),
            'registered_by' => $_SESSION['user_id'],
            'vendor_user_id' => (int)($_POST['vendor_user_id'] ?? 0),
        ]);
        if ($result['ok']) {
            $saved = true;
        } else {
            $error = $result['error'];
        }
    }
}

// Only approved, active Vendor accounts that are not linked to another business.
$vendorAccounts = $pdo->query("SELECT u.user_id, u.full_name, u.email
    FROM users u
    WHERE u.role = 'vendor' AND u.is_active = 1
      AND NOT EXISTS (SELECT 1 FROM vendors v WHERE v.vendor_user_id = u.user_id)
    ORDER BY u.full_name")->fetchAll(PDO::FETCH_ASSOC);
$vendors = get_all_vendors($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Vendor Registration</h1>
        <p>Register a new vendor and pin their exact business location on the map.</p>
    </div>
</div>

<?php if ($saved): ?>
    <div class="card" style="border-left:4px solid var(--color-success); margin-bottom:1.25rem;">
        <div class="card-body" style="display:flex; align-items:center; gap:.75rem;">
            <span class="pin-status active">Saved</span>
            <span>Vendor "<strong><?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?></strong>" was registered successfully and written to the database.</span>
        </div>
    </div>
<?php elseif ($error): ?>
    <div class="card" style="border-left:4px solid var(--color-danger); margin-bottom:1.25rem;">
        <div class="card-body"><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div class="row" style="display:grid;grid-template-columns:1fr 1.1fr;gap:1.25rem;align-items:start;">

    <div class="card">
        <div class="card-header"><h3>Business &amp; Owner Details</h3></div>
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="business_name">Business Name</label>
                        <input class="form-control" id="business_name" name="business_name" placeholder="e.g. Amora Sari-Sari Store" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="owner_name">Owner's Full Name</label>
                        <input class="form-control" id="owner_name" name="owner_name" placeholder="e.g. Cristy Amora" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="category">Business Category</label>
                        <select class="form-control" id="category" name="category" required>
                            <option>Retail / Sari-Sari Store</option>
                            <option>Food / Carinderia</option>
                            <option>Food / Bakery</option>
                            <option>Hardware / Construction Supplies</option>
                            <option>Wet Market / Vegetables</option>
                            <option>Automotive Parts</option>
                            <option>Services</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="barangay">Barangay</label>
                        <select class="form-control" id="barangay" name="barangay" required>
                            <option>Poblacion</option>
                            <option>Alae</option>
                            <option>Dahilayan</option>
                            <option>Sankanan</option>
                            <option>Damilag</option>
                            <option>Lindaban</option>
                            <option>San Miguel</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vendor_user_id">Link Approved Vendor Account (Optional)</label>
                    <select class="form-control" id="vendor_user_id" name="vendor_user_id">
                        <option value="0">-- Link later / No account yet --</option>
                        <?php foreach ($vendorAccounts as $account): ?>
                            <option value="<?php echo (int)$account['user_id']; ?>" <?php echo (int)($_POST['vendor_user_id'] ?? 0) === (int)$account['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['full_name'] . ' (' . $account['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">Select the owner's approved Vendor login. Only unlinked active Vendor accounts appear here.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact">Contact Number</label>
                        <input class="form-control" id="contact" name="contact" placeholder="09XX-XXX-XXXX" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="address">Complete Address</label>
                        <input class="form-control" id="address" name="address" placeholder="Street / Purok, Barangay">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="lat">Latitude</label>
                        <input class="form-control mono" id="lat" name="lat" placeholder="8.3706" readonly required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="lng">Longitude</label>
                        <input class="form-control mono" id="lng" name="lng" placeholder="124.8681" readonly required>
                    </div>
                </div>
                <p class="form-hint" style="margin-top:-.5rem;margin-bottom:1rem;">Click anywhere on the map to drop a pin at the vendor's exact location.</p>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="permit_no">Initial Business Permit No.</label>
                        <input class="form-control mono" id="permit_no" name="permit_no" placeholder="BP-2026-XXXX" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expiry_date">Permit Expiry Date</label>
                        <input class="form-control" type="date" id="expiry_date" name="expiry_date" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                    </div>
                </div>

                <button type="submit" name="submit_registration" class="btn btn-primary" style="width:100%;justify-content:center;">Save Vendor Record</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Pin Business Location</h3></div>
        <div class="card-body">
            <div id="gis-map"></div>
            <div class="map-legend">
                <span><i style="background:#0F3D3E"></i> New pin (unsaved)</span>
                <span><i style="background:#2E8B57"></i> Existing active vendor</span>
                <span><i style="background:#E8871E"></i> Existing expiring vendor</span>
                <span><i style="background:#C1443C"></i> Existing expired vendor</span>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/map.js"></script>
<script>
    var existingVendors = <?php echo json_encode($vendors); ?>;
    var mapHandle = initPuestoMap('gis-map', existingVendors);

    // Registration-specific behaviour: clicking the map drops a draft pin
    // and fills in the latitude/longitude fields.
    var draftMarker = null;
    mapHandle.map.on('click', function (e) {
        document.getElementById('lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('lng').value = e.latlng.lng.toFixed(6);
        if (draftMarker) mapHandle.map.removeLayer(draftMarker);
        draftMarker = L.circleMarker(e.latlng, { radius: 9, color: '#0F3D3E', fillColor: '#0F3D3E', fillOpacity: 0.85 })
            .addTo(mapHandle.map)
            .bindPopup('New vendor pin (unsaved)').openPopup();
    });
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
