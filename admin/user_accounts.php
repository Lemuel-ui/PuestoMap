<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$pageTitle = 'User Accounts';
$activePage = 'accounts';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_account'])) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'vendor';
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $email === '' || strlen($password) < 6) {
            $message = 'Please provide a full name, email, and a password of at least 6 characters.';
            $messageType = 'danger';
        } elseif (!in_array($role, ['admin', 'bplo_staff', 'head_officer', 'inspector', 'vendor'], true)) {
            $message = 'Invalid role selected.';
            $messageType = 'danger';
        } else {
            $result = create_user($pdo, $fullName, $email, $password, $role);
            if ($result['ok']) {
                $message = "Account for \"$fullName\" was created successfully.";
            } else {
                $message = $result['error'];
                $messageType = 'danger';
            }
        }
    } elseif (isset($_POST['toggle_user_id'])) {
        $userId = (int)$_POST['toggle_user_id'];
        $active = $_POST['toggle_to'] === '1';
        set_user_active($pdo, $userId, $active);
        $message = $active ? 'Account reactivated.' : 'Account deactivated.';
    }
}

$users = get_all_users($pdo);
include __DIR__ . '/../components/head_open.php';
?>
<div class="page-head">
    <div>
        <h1>User Accounts</h1>
        <p>Create and manage login accounts for BPLO Staff, Head Officers, Inspectors, and Vendors.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="card" style="border-left:4px solid var(--color-<?php echo $messageType; ?>); margin-bottom:1.25rem;">
        <div class="card-body"><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="row" style="display:grid;grid-template-columns:1.5fr 1fr;gap:1.25rem;align-items:start;">
    <div class="card">
        <div class="card-header"><h3>All Accounts</h3></div>
        <div class="card-body" style="padding:0;">
            <table class="pm-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td class="mono"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="badge-soft"><?php echo htmlspecialchars(ROLE_LABELS[$u['role']]); ?></span></td>
                        <td><span class="pin-status <?php echo $u['is_active'] ? 'active' : 'expired'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Deactivated'; ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return <?php echo $u['is_active'] ? "pmConfirmDelete('Deactivate this account?')" : 'true'; ?>;">
                                <input type="hidden" name="toggle_user_id" value="<?php echo (int)$u['user_id']; ?>">
                                <input type="hidden" name="toggle_to" value="<?php echo $u['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn <?php echo $u['is_active'] ? 'btn-danger-outline' : 'btn-outline'; ?> btn-sm">
                                    <?php echo $u['is_active'] ? 'Deactivate' : 'Reactivate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Create Account</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input class="form-control" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Temporary Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="role">Assign Role</label>
                    <select class="form-control" id="role" name="role">
                        <option value="bplo_staff">BPLO Staff</option>
                        <option value="head_officer">BPLO Head Officer</option>
                        <option value="inspector">Business Permit Inspector</option>
                        <option value="vendor">Vendor</option>
                        <option value="admin">MISU Administrator</option>
                    </select>
                </div>
                <button type="submit" name="create_account" class="btn btn-primary" style="width:100%;justify-content:center;">Create Account</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
