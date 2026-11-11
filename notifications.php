<?php
require_once __DIR__ . '/includes/auth_check.php';
require_role(['admin', 'bplo_staff', 'inspector', 'vendor']);
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db();
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    verify_csrf();
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
    $stmt->execute([(int)$_POST['mark_read'], $userId]);
    header('Location: ' . BASE_URL . '/notifications.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    verify_csrf();
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
    header('Location: ' . BASE_URL . '/notifications.php');
    exit;
}

$pageTitle = 'Notifications';
$activePage = '';
$notifications = get_notifications($pdo, 100, $userId);
include __DIR__ . '/components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>Notifications</h1>
        <p>Permit expiry reminders and system updates for your account.</p>
    </div>
    <form method="post">
        <?php echo csrf_field(); ?>
        <button class="btn btn-outline btn-sm" type="submit" name="mark_all_read" value="1">Mark all as read</button>
    </form>
</div>
<div class="card">
    <div class="card-header"><h3>Notification History</h3></div>
    <div>
        <?php if (!$notifications): ?>
            <div class="card-body">You have no notifications yet.</div>
        <?php else: foreach ($notifications as $n): ?>
            <div class="notif-item" style="<?php echo empty($n['is_read']) ? 'background:rgba(232,135,30,.06);' : ''; ?>">
                <span class="notif-dot <?php echo htmlspecialchars($n['type'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                <div style="flex:1;">
                    <div class="notif-text"><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="notif-time"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($n['created_at'])), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($n['time'], ENT_QUOTES, 'UTF-8'); ?></div><?php if (!empty($n['business_name'])): ?><div class="form-hint">Business: <?php echo htmlspecialchars($n['business_name']); ?><?php if (!empty($n['permit_no'])): ?> · Permit: <?php echo htmlspecialchars($n['permit_no']); ?><?php endif; ?></div><?php endif; ?>
                </div>
                <?php if (empty($n['is_read'])): ?>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="mark_read" value="<?php echo (int)$n['id']; ?>">
                        <button class="btn btn-outline btn-sm" type="submit">Mark read</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php include __DIR__ . '/components/footer.php'; ?>
