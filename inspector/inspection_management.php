<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['inspector']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Inspection Management';
$activePage = 'inspections';
$updated = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inspection_id'])) {
    verify_csrf();
    $inspectionId = (int)$_POST['update_inspection_id'];
    $newStatusLabel = $_POST['new_status'] ?? 'Completed';
    $status = $newStatusLabel === 'Flagged for Review' ? 'flagged' : 'completed';
    $remarks = trim($_POST['remarks'] ?? '');

    $updated = update_inspection_status($pdo, $inspectionId, (int) $_SESSION['user_id'], $status, $remarks);

    // Accept a photo only after the assigned pending inspection was updated.
    if ($updated && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $isImage = @getimagesize($_FILES['photo']['tmp_name']) !== false;
        if ($isImage && in_array($ext, $allowed, true) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $filename)) {
                add_inspection_photo($pdo, $inspectionId, 'uploads/inspections/' . $filename);
            } else {
                $error = 'Status was saved, but the photo could not be uploaded.';
            }
        } else {
            $error = 'Status was saved, but the photo was skipped (use JPG/PNG/WEBP under 5MB).';
        }
    }

    if (!$updated) {
        $error = 'Inspection not found, already processed, or not assigned to your account.';
    }
}

$myInspections = get_inspections_for_inspector($pdo, (int)$_SESSION['user_id']);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Inspection Management</h1>
        <p>Update the results of your field inspections, add remarks, and attach photos.</p>
    </div>
</div>

<?php if ($updated): ?>
    <div class="card" style="border-left:4px solid var(--color-success); margin-bottom:1.25rem;">
        <div class="card-body">Inspection <strong class="mono"><?php echo htmlspecialchars($_POST['inspection_label'] ?? ''); ?></strong> was updated to "<?php echo htmlspecialchars($_POST['new_status'] ?? ''); ?>".<?php if ($error) echo ' ' . htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Assigned Inspections</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="pm-table">
            <thead><tr><th>Inspection ID</th><th>Vendor</th><th>Barangay</th><th>Scheduled</th><th>Status</th><th>Findings &amp; Photo</th></tr></thead>
            <tbody>
            <?php foreach ($myInspections as $ins): ?>
                <tr>
                    <td class="mono"><?php echo htmlspecialchars($ins['id']); ?></td>
                    <td><strong><?php echo htmlspecialchars($ins['vendor']); ?></strong></td>
                    <td><?php echo htmlspecialchars($ins['barangay']); ?></td>
                    <td><?php echo htmlspecialchars($ins['scheduled']); ?></td>
                    <td><span class="pin-status <?php echo $ins['status']; ?>"><?php echo htmlspecialchars($ins['status_label']); ?></span></td>
                    <td>
                        <?php if ($ins['status'] === 'pending'): ?>
                        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="update_inspection_id" value="<?php echo $ins['inspection_id']; ?>">
                            <input type="hidden" name="inspection_label" value="<?php echo htmlspecialchars($ins['id']); ?>">
                            <select name="new_status" class="form-control btn-sm" style="max-width:170px;">
                                <option>Completed</option>
                                <option>Flagged for Review</option>
                            </select>
                            <input type="text" name="remarks" class="form-control btn-sm" style="max-width:220px;" placeholder="Remarks / recommendation">
                            <input type="file" name="photo" accept="image/*" style="max-width:170px;">
                            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                        </form>
                        <?php else: ?>
                            <div style="font-size:.85rem;color:var(--color-text-muted);"><?php echo $ins['remarks'] ? htmlspecialchars($ins['remarks']) : 'No remarks recorded.'; ?></div>
                            <?php $photos = get_inspection_photos($pdo, $ins['inspection_id']); ?>
                            <?php if ($photos): ?>
                                <div style="display:flex;gap:.35rem;margin-top:.35rem;flex-wrap:wrap;">
                                    <?php foreach ($photos as $p): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($p['file_path']); ?>" target="_blank">
                                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($p['file_path']); ?>" alt="Inspection photo" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--color-border);">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$myInspections): ?><tr><td colspan="6" style="text-align:center;color:var(--color-text-muted);">No inspections assigned yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
