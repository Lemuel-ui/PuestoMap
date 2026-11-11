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
$linkMessage = '';
$linkError = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_vendor_account'])) {
    verify_csrf();
    $selectedUserId = (int)($_POST['vendor_user_id'] ?? 0);
    try {
        if ($selectedUserId > 0) {
            $eligible = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'vendor' AND is_active = 1 LIMIT 1");
            $eligible->execute([$selectedUserId]);
            if (!$eligible->fetchColumn()) {
                throw new RuntimeException('Please select an active, approved Vendor account.');
            }
            $alreadyLinked = $pdo->prepare('SELECT vendor_id FROM vendors WHERE vendor_user_id = ? AND vendor_id <> ? LIMIT 1');
            $alreadyLinked->execute([$selectedUserId, $vendorId]);
            if ($alreadyLinked->fetchColumn()) {
                throw new RuntimeException('This Vendor account is already linked to another business.');
            }
        }
        $link = $pdo->prepare('UPDATE vendors SET vendor_user_id = ? WHERE vendor_id = ?');
        $link->execute([$selectedUserId > 0 ? $selectedUserId : null, $vendorId]);
        $linkMessage = $selectedUserId > 0 ? 'Vendor login account linked successfully.' : 'Vendor login account unlinked.';
        $vendor = get_vendor($pdo, $vendorId);
    } catch (Throwable $e) {
        $linkError = $e instanceof RuntimeException ? $e->getMessage() : 'Could not update the Vendor account link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendor'])) {
    verify_csrf();
    $updateResult = update_vendor($pdo, $vendorId, [
        'business_name' => trim($_POST['business_name'] ?? ''),
        'owner_name'    => trim($_POST['owner_name'] ?? ''),
        'category'      => trim($_POST['category'] ?? ''),
        'barangay'      => trim($_POST['barangay'] ?? ''),
        'address'       => trim($_POST['address'] ?? ''),
        'contact_no'    => trim($_POST['contact'] ?? ''),
        'lat'           => $_POST['lat'] ?? '',
        'lng'           => $_POST['lng'] ?? '',
    ]);
    if ($updateResult['ok']) { $vendor = get_vendor($pdo, $vendorId); $saved = true; }
    else { $updateError = $updateResult['error']; }
}

$accountStmt = $pdo->prepare("SELECT user_id, full_name, email FROM users
    WHERE role = 'vendor' AND is_active = 1
      AND (NOT EXISTS (SELECT 1 FROM vendors v WHERE v.vendor_user_id = users.user_id) OR user_id = ?)
    ORDER BY full_name");
$accountStmt->execute([(int)($vendor['vendor_user_id'] ?? 0)]);
$vendorAccounts = $accountStmt->fetchAll(PDO::FETCH_ASSOC);
$currentLinkedAccount = null;
if (!empty($vendor['vendor_user_id'])) {
    $currentAccountStmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE user_id = ? LIMIT 1');
    $currentAccountStmt->execute([(int)$vendor['vendor_user_id']]);
    $currentLinkedAccount = $currentAccountStmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
<?php if ($updateError): ?><div class="card" style="border-left:4px solid var(--color-danger);margin-bottom:1.25rem;"><div class="card-body"><?php echo htmlspecialchars($updateError); ?></div></div><?php endif; ?>
<?php if ($linkMessage): ?>
    <div class="card" style="border-left:4px solid var(--color-success); margin-bottom:1.25rem;"><div class="card-body"><?php echo htmlspecialchars($linkMessage); ?></div></div>
<?php endif; ?>
<?php if ($linkError): ?>
    <div class="card" style="border-left:4px solid var(--color-danger); margin-bottom:1.25rem;"><div class="card-body"><?php echo htmlspecialchars($linkError); ?></div></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header"><h3>Link Vendor Login Account</h3></div>
    <div class="card-body">
        <p class="form-hint">Choose the approved Vendor account belonging to this business owner. One Vendor account can only be linked to one business.</p>
        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="form-row" style="align-items:end;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="vendor_user_id">Approved Vendor Account</label>
                    <select class="form-control" id="vendor_user_id" name="vendor_user_id">
                        <option value="0">-- No account linked --</option>
                        <?php foreach ($vendorAccounts as $account): ?>
                            <option value="<?php echo (int)$account['user_id']; ?>" <?php echo (int)($vendor['vendor_user_id'] ?? 0) === (int)$account['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['full_name'] . ' (' . $account['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><button type="submit" name="link_vendor_account" class="btn btn-primary">Save Account Link</button></div>
            </div>
        </form>
        <?php if ($currentLinkedAccount): ?>
            <p class="form-hint">Currently linked to: <strong><?php echo htmlspecialchars($currentLinkedAccount['full_name']); ?></strong> (<?php echo htmlspecialchars($currentLinkedAccount['email']); ?>)</p>
        <?php else: ?>
            <p class="form-hint">No Vendor login account is currently linked.</p>
        <?php endif; ?>
    </div>
</div>

<div class="row" style="display:grid;grid-template-columns:1fr 1.1fr;gap:1.25rem;align-items:start;">
    <div class="card">
        <div class="card-header"><h3><?php echo htmlspecialchars($vendor['name']); ?></h3></div>
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
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
                        <input class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($vendor['category']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="barangay">Barangay</label>
                        <input class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($vendor['barangay']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact">Contact Number</label>
                        <input class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($vendor['contact']); ?>" required>
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
