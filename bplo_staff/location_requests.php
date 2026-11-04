<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['bplo_staff']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Location Requests';
$activePage = 'location_requests';
$error = '';
$success = '';

if (empty($_SESSION['review_csrf'])) {
    $_SESSION['review_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['review_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['decision'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $error = 'Invalid form token. Refresh the page and try again.';
    } elseif (!in_array($_POST['decision'], ['approve', 'reject'], true)) {
        $error = 'Unknown decision.';
    } else {
        $result = review_location_request(
            $pdo,
            (int) $_POST['request_id'],
            $_POST['decision'] === 'approve',
            (int) $_SESSION['user_id'],
            trim($_POST['review_note'] ?? '')
        );
        if ($result['ok']) {
            $success = $_POST['decision'] === 'approve'
                ? 'Request approved. The vendor\'s map location was updated.'
                : 'Request rejected. The vendor\'s record was not changed.';
        } else {
            $error = $result['error'];
        }
    }
}

$requests = get_location_requests($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Location Requests</h1>
        <p>Review vendors' requests to change their registered business location on the map.</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="card" style="border-left:4px solid var(--color-danger); margin-bottom:1.25rem;">
        <div class="card-body"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="card" style="border-left:4px solid var(--color-success); margin-bottom:1.25rem;">
        <div class="card-body"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>All Requests</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (!$requests): ?>
            <p style="padding:1.25rem;margin:0;color:var(--color-text-muted);">No location-change requests have been submitted yet.</p>
        <?php else: ?>
        <table class="pm-table">
            <thead>
                <tr><th>Vendor</th><th>Current Location</th><th>Requested Location</th><th>Submitted</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($r['business_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <small><?php echo htmlspecialchars($r['owner_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($r['current_address'] . ', ' . $r['current_barangay'], ENT_QUOTES, 'UTF-8'); ?><br>
                        <small class="mono"><?php echo htmlspecialchars($r['current_lat'] . ', ' . $r['current_lng'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($r['requested_address'] . ', ' . $r['requested_barangay'], ENT_QUOTES, 'UTF-8'); ?><br>
                        <small class="mono"><?php echo htmlspecialchars($r['requested_lat'] . ', ' . $r['requested_lng'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($r['requested_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <span class="badge-soft"><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if (!empty($r['review_note'])): ?>
                            <br><small><?php echo htmlspecialchars($r['review_note'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['status'] === 'Pending'): ?>
                        <form method="POST" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="request_id" value="<?php echo (int) $r['id']; ?>">
                            <input type="text" name="review_note" class="form-control btn-sm" style="max-width:160px;" placeholder="Note (optional)" maxlength="255">
                            <button type="submit" name="decision" value="approve" class="btn btn-accent btn-sm">Approve</button>
                            <button type="submit" name="decision" value="reject" class="btn btn-sm">Reject</button>
                        </form>
                        <?php else: ?>
                            <span class="badge-soft">Reviewed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
