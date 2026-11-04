<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['vendor']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Business Profile';
$activePage = 'profile';

$myBusiness = get_vendor_for_user($pdo, (int) $_SESSION['user_id']);
$error = '';
$success = '';

if (empty($_SESSION['location_csrf'])) {
    $_SESSION['location_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['location_csrf'];

/*
 * Submit a location-change request.
 * The current business record is not changed here.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_location_request'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid form token. Refresh the page and try again.';
    } elseif (!$myBusiness) {
        $error = 'No business record is linked to your account.';
    } else {
        $address = trim($_POST['address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $latInput = $_POST['lat'] ?? '';
        $lngInput = $_POST['lng'] ?? '';

        if (
            $address === '' ||
            $barangay === '' ||
            $latInput === '' ||
            $lngInput === '' ||
            !is_numeric($latInput) ||
            !is_numeric($lngInput)
        ) {
            $error = 'Enter the address and barangay, then select a location on the map.';
        } else {
            $lat = (float) $latInput;
            $lng = (float) $lngInput;

            // Validate geographic coordinate ranges.
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $error = 'The latitude or longitude is outside the valid range.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Lock the business row and verify the account still owns it.
                    $check = $pdo->prepare(
                        'SELECT vendor_id FROM vendors WHERE vendor_id = ? AND vendor_user_id = ? FOR UPDATE'
                    );
                    $check->execute([(int) $myBusiness['vendor_id'], (int) $_SESSION['user_id']]);
                    $business = $check->fetch(PDO::FETCH_ASSOC);

                    if (!$business) {
                        throw new RuntimeException('Business record not found.');
                    }

                    // Only one pending request per business.
                    $pending = $pdo->prepare(
                        "SELECT id FROM business_location_requests
                         WHERE vendor_id = ? AND status = 'Pending'
                         LIMIT 1"
                    );
                    $pending->execute([(int) $myBusiness['vendor_id']]);

                    if ($pending->fetch()) {
                        $pdo->rollBack();
                        $error = 'You already have a pending location-change request.';
                    } else {
                        $insert = $pdo->prepare(
                            "INSERT INTO business_location_requests
                            (
                                vendor_id,
                                requested_address,
                                requested_barangay,
                                requested_lat,
                                requested_lng,
                                status,
                                requested_by
                            )
                            VALUES (?, ?, ?, ?, ?, 'Pending', ?)"
                        );

                        $insert->execute([
                            (int) $myBusiness['vendor_id'],
                            $address,
                            $barangay,
                            $lat,
                            $lng,
                            (int) $_SESSION['user_id']
                        ]);

                        $pdo->commit();
                        $success = 'Your location-change request was submitted for BPLO staff approval.';
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log('Location request error: ' . $e->getMessage());
                    $error = 'Unable to submit the request. Please try again.';
                }
            }
        }
    }
}

// Fetch the latest request for this business.
$latestRequest = null;
$requestHistory = [];

if ($myBusiness) {
    $history = $pdo->prepare(
        'SELECT id, requested_address, requested_barangay,
                requested_lat, requested_lng, status,
                review_note, requested_at, reviewed_at
         FROM business_location_requests
         WHERE vendor_id = ?
         ORDER BY requested_at DESC'
    );
    $history->execute([(int) $myBusiness['vendor_id']]);
    $requestHistory = $history->fetchAll(PDO::FETCH_ASSOC);
    $latestRequest = $requestHistory[0] ?? null;
}

include __DIR__ . '/../components/head_open.php';
?>

<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
.location-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    align-items: start;
}
.location-map {
    width: 100%;
    height: 380px;
    border-radius: 8px;
    z-index: 1;
}
.location-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.location-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fff0c2; color: #805600; }
.status-approved { background: #d8f3df; color: #176b35; }
.status-rejected { background: #ffe0e0; color: #9d2020; }
.location-note { margin: 12px 0; }
@media (max-width: 800px) {
    .location-grid { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .location-fields { grid-template-columns: 1fr; }
}
</style>

<div class="page-head">
    <div>
        <h1>Business Profile</h1>
        <p>View your registered details and request a change to your business location.</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="card" style="margin-bottom:1rem;border-left:4px solid var(--color-danger);">
        <div class="card-body">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="card" style="margin-bottom:1rem;border-left:4px solid var(--color-success);">
        <div class="card-body">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!$myBusiness): ?>

    <div class="card">
        <div class="card-body">
            No business record is linked to your account yet. Please visit the BPLO office.
        </div>
    </div>

<?php else: ?>

    <div class="location-grid">

        <div class="card">
            <div class="card-header">
                <h3>Registered Business Details</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Business Name</label>
                    <div><?php echo htmlspecialchars($myBusiness['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Owner</label>
                    <div><?php echo htmlspecialchars($myBusiness['owner'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <div><?php echo htmlspecialchars($myBusiness['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Registered Address</label>
                    <div>
                        <?php echo htmlspecialchars($myBusiness['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>,
                        <?php echo htmlspecialchars($myBusiness['barangay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Approved Coordinates</label>
                    <div>
                        <?php echo htmlspecialchars((string) ($myBusiness['lat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                        <?php echo htmlspecialchars((string) ($myBusiness['lng'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <div><?php echo htmlspecialchars($myBusiness['contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Request Address Change</h3>
            </div>

            <div class="card-body">
                <?php if ($latestRequest && $latestRequest['status'] === 'Pending'): ?>

                    <p class="location-note">
                        <span class="location-status status-pending">Pending Approval</span>
                    </p>
                    <p>
                        You already have a request awaiting BPLO review.
                        Please wait for the decision before submitting another.
                    </p>

                <?php else: ?>

                    <p class="location-note">
                        Your changes will not become official until approved by BPLO staff.
                    </p>

                    <form method="POST" id="location-request-form">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-group">
                            <label class="form-label" for="address">New Complete Address</label>
                            <input class="form-control" id="address" name="address"
                                   maxlength="255" required
                                   value="<?php echo htmlspecialchars($myBusiness['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Street / Purok">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="barangay">New Barangay</label>
                            <select class="form-control" id="barangay" name="barangay" required>
                                <?php
                                $barangays = [
                                    'Poblacion', 'Alae', 'Dahilayan',
                                    'Sankanan', 'Damilag', 'Lindaban', 'San Miguel'
                                ];
                                foreach ($barangays as $b):
                                ?>
                                    <option value="<?php echo htmlspecialchars($b, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo (($myBusiness['barangay'] ?? '') === $b) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="location-fields">
                            <div class="form-group">
                                <label class="form-label" for="lat">Latitude</label>
                                <input class="form-control" type="number" step="any"
                                       id="lat" name="lat" required
                                       min="-90" max="90"
                                       value="<?php echo htmlspecialchars((string) ($myBusiness['lat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="lng">Longitude</label>
                                <input class="form-control" type="number" step="any"
                                       id="lng" name="lng" required
                                       min="-180" max="180"
                                       value="<?php echo htmlspecialchars((string) ($myBusiness['lng'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <p class="form-hint">
                            Click the map to place the new pin, or enter coordinates manually.
                        </p>

                        <button type="submit" name="submit_location_request"
                                class="btn btn-primary"
                                style="width:100%;justify-content:center;">
                            Submit for Staff Approval
                        </button>
                    </form>

                <?php endif; ?>

                <div id="location-map" class="location-map" style="margin-top:1rem;"></div>

                <p class="form-hint">
                    The existing approved marker is shown in green. Your proposed location is shown in blue.
                </p>
            </div>
        </div>

    </div>

    <div class="card" style="margin-top:1.25rem;">
        <div class="card-header">
            <h3>Location Request History</h3>
        </div>
        <div class="card-body">
            <?php if (!$requestHistory): ?>
                <p>You have not submitted any address-change requests.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:10px;">Requested Address</th>
                                <th style="text-align:left;padding:10px;">Coordinates</th>
                                <th style="text-align:left;padding:10px;">Date</th>
                                <th style="text-align:left;padding:10px;">Status</th>
                                <th style="text-align:left;padding:10px;">Staff Note</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requestHistory as $request): ?>
                            <tr>
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars($request['requested_address'] . ', ' . $request['requested_barangay'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars($request['requested_lat'] . ', ' . $request['requested_lng'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars($request['requested_at'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td style="padding:10px;">
                                    <span class="location-status status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars($request['review_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function () {
        const currentLat = Number(<?php echo json_encode((float) ($myBusiness['lat'] ?? 8.3706)); ?>);
        const currentLng = Number(<?php echo json_encode((float) ($myBusiness['lng'] ?? 124.8681)); ?>);

        const map = L.map('location-map').setView([currentLat, currentLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([currentLat, currentLng])
            .addTo(map)
            .bindPopup('Currently registered location');

        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');
        const form = document.getElementById('location-request-form');

        let proposedMarker = null;

        function validCoordinates(lat, lng) {
            return Number.isFinite(lat) && Number.isFinite(lng)
                && lat >= -90 && lat <= 90
                && lng >= -180 && lng <= 180;
        }

        function updateProposedLocation(lat, lng, moveMap) {
            if (!validCoordinates(lat, lng)) return;

            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);

            if (proposedMarker) {
                proposedMarker.setLatLng([lat, lng]);
            } else {
                proposedMarker = L.marker([lat, lng], { draggable: true })
                    .addTo(map)
                    .bindPopup('Proposed location');

                proposedMarker.on('dragend', function (event) {
                    const point = event.target.getLatLng();
                    updateProposedLocation(point.lat, point.lng, false);
                });
            }

            if (moveMap) {
                map.setView([lat, lng], Math.max(map.getZoom(), 16));
                proposedMarker.openPopup();
            }
        }

        map.on('click', function (event) {
            updateProposedLocation(event.latlng.lat, event.latlng.lng, false);
        });

        function syncFromInputs() {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);

            if (latInput.value !== '' && lngInput.value !== ''
                && validCoordinates(lat, lng)) {
                updateProposedLocation(lat, lng, false);
            }
        }

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);

        syncFromInputs();

        if (form) {
            form.addEventListener('submit', function (event) {
                const lat = Number(latInput.value);
                const lng = Number(lngInput.value);

                if (!latInput.value || !lngInput.value
                    || !validCoordinates(lat, lng)) {
                    event.preventDefault();
                    alert('Please select a valid location on the map.');
                }
            });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 150);
    })();
    </script>

<?php endif; ?>

<?php include __DIR__ . '/../components/footer.php'; ?>
