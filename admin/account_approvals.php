<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'Account Approvals';
$activePage = 'approvals';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $action = (string)($_POST['action'] ?? '');
    $reviewNote = trim((string)($_POST['review_note'] ?? ''));

    if (!$requestId || !in_array($action, ['approve', 'reject'], true)) {
        $message = 'Invalid approval request.';
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare("SELECT request_id, full_name, email, password_hash, requested_role, status FROM account_registration_requests WHERE request_id = ? FOR UPDATE");
            $find->execute([$requestId]);
            $request = $find->fetch(PDO::FETCH_ASSOC);

            if (!$request || $request['status'] !== 'pending') {
                throw new RuntimeException('This request is no longer pending. Refresh the page.');
            }

            if ($action === 'approve') {
                $exists = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
                $exists->execute([$request['email']]);
                if ($exists->fetch()) {
                    throw new RuntimeException('An account with this email already exists. Reject this request or resolve the duplicate first.');
                }

                $create = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)');
                $create->execute([$request['full_name'], $request['email'], $request['password_hash'], $request['requested_role']]);
                $newUserId = (int)$pdo->lastInsertId();

                $review = $pdo->prepare("UPDATE account_registration_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_note = ? WHERE request_id = ? AND status = 'pending'");
                $review->execute([(int)$_SESSION['user_id'], $reviewNote !== '' ? $reviewNote : 'Approved by Admin / BPLO Head Officer.', $requestId]);
                if ($review->rowCount() !== 1) throw new RuntimeException('The request could not be marked as approved.');
                $pdo->commit();
                $message = 'Approved. The login account for ' . $request['full_name'] . ' has now been created.';
            } else {
                $review = $pdo->prepare("UPDATE account_registration_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_note = ? WHERE request_id = ? AND status = 'pending'");
                $review->execute([(int)$_SESSION['user_id'], $reviewNote !== '' ? $reviewNote : 'Rejected by Admin / BPLO Head Officer.', $requestId]);
                if ($review->rowCount() !== 1) throw new RuntimeException('The request could not be rejected.');
                $pdo->commit();
                $message = 'Registration request rejected. No user account was created.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('PuestoMap account approval error: ' . $e->getMessage());
            $message = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to process this request. Please try again.';
            $messageType = 'danger';
        }
    }
}

$pending = $pdo->query("SELECT request_id, full_name, email, requested_role, requested_at FROM account_registration_requests WHERE status = 'pending' ORDER BY requested_at ASC")->fetchAll(PDO::FETCH_ASSOC);
$recent = $pdo->query("SELECT r.request_id, r.full_name, r.email, r.requested_role, r.status, r.review_note, r.requested_at, r.reviewed_at, u.full_name AS reviewer_name FROM account_registration_requests r LEFT JOIN users u ON u.user_id = r.reviewed_by WHERE r.status IN ('approved','rejected') ORDER BY r.reviewed_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head"><div><h1>Account Approvals</h1><p>Review self-registration requests. A user account is created only after approval.</p></div></div>
<?php if ($message !== ''): ?><div class="card" style="border-left:4px solid var(--color-<?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>);margin-bottom:1.25rem;"><div class="card-body"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
<div class="card" style="margin-bottom:1.25rem;"><div class="card-header"><h3>Pending Requests (<?php echo count($pending); ?>)</h3></div><div class="card-body" style="padding:0;overflow-x:auto;">
<?php if (!$pending): ?><p style="padding:1rem;margin:0;">There are no pending registration requests.</p><?php else: ?>
<table class="pm-table"><thead><tr><th>Name</th><th>Email</th><th>Requested Role</th><th>Date Submitted</th><th>Review Action</th></tr></thead><tbody>
<?php foreach ($pending as $r): ?><tr><td><strong><?php echo htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="badge-soft"><?php echo htmlspecialchars(ROLE_LABELS[$r['requested_role']] ?? $r['requested_role'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($r['requested_at'])), ENT_QUOTES, 'UTF-8'); ?></td><td>
<form method="POST" style="min-width:220px;display:grid;gap:.45rem;" onsubmit="return confirm(this.elements.action.value === 'approve' ? 'Approve this request and create the user account?' : 'Reject this registration request?');">
<?php echo csrf_field(); ?><input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>"><input type="text" name="review_note" class="form-control" maxlength="500" placeholder="Optional review note"><div style="display:flex;gap:.4rem;"><button class="btn btn-primary btn-sm" type="submit" name="action" value="approve">Approve</button><button class="btn btn-danger-outline btn-sm" type="submit" name="action" value="reject">Reject</button></div></form>
</td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<div class="card"><div class="card-header"><h3>Recently Reviewed</h3></div><div class="card-body" style="padding:0;overflow-x:auto;">
<?php if (!$recent): ?><p style="padding:1rem;margin:0;">No reviewed requests yet.</p><?php else: ?><table class="pm-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Decision</th><th>Reviewed By</th><th>Review Note</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td><?php echo htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars(ROLE_LABELS[$r['requested_role']] ?? $r['requested_role'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="pin-status <?php echo $r['status'] === 'approved' ? 'active' : 'expired'; ?>"><?php echo htmlspecialchars(ucfirst($r['status']), ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars($r['reviewer_name'] ?? 'Admin / BPLO Head Officer unavailable', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($r['review_note'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php include __DIR__ . '/../components/footer.php'; ?>
