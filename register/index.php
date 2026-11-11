<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$error = '';
$success = '';
$allowedRoles = [
    'bplo_staff' => 'BPLO Staff',
    'inspector'  => 'Business Permit Inspector',
    'vendor'     => 'Vendor',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $role = (string)($_POST['role'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '' || strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a valid full name and email address.';
    } elseif (!isset($allowedRoles[$role])) {
        $error = 'Select an allowed account type.';
    } elseif (strlen($password) < 12) {
        $error = 'Your password must contain at least 12 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The password confirmation does not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $existingUser = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $existingUser->execute([$email]);
            $existingPending = $pdo->prepare("SELECT request_id FROM account_registration_requests WHERE email = ? AND status = 'pending' LIMIT 1 FOR UPDATE");
            $existingPending->execute([$email]);

            if ($existingUser->fetch()) {
                $pdo->rollBack();
                $error = 'An account with this email already exists. Please log in or contact the administrator.';
            } elseif ($existingPending->fetch()) {
                $pdo->rollBack();
                $error = 'A registration request for this email is already awaiting administrator approval.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO account_registration_requests (full_name, email, password_hash, requested_role, status) VALUES (?, ?, ?, ?, 'pending')");
                $insert->execute([$fullName, $email, $hash, $role]);
                $pdo->commit();
                $success = 'Your registration request has been submitted. Your account will only be created after the Admin / BPLO Head Officer approves it. You may log in after approval.';
                $_POST = [];
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('PuestoMap registration request failed: ' . $e->getMessage());
            $error = 'We could not submit your request right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request an Account · <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/auth.css">
    <style>
        .auth-card { max-width: 480px; }
        .auth-card .form-group { margin-bottom: .9rem; }
        .approval-note { padding: .8rem 1rem; border-radius: 8px; background: var(--color-info-bg); color: var(--color-info); font-size: .85rem; margin: 1rem 0; }
        .auth-links { margin-top: 1.2rem; text-align: center; font-size: .9rem; }
        .auth-links a { color: var(--color-primary); font-weight: 600; }
    </style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="brand">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#E8871E" stroke-width="1.8" aria-hidden="true"><path d="M12 22s7-7.58 7-12A7 7 0 0 0 5 10c0 4.42 7 12 7 12z" fill="rgba(232,135,30,0.2)"/><circle cx="12" cy="10" r="2.6" fill="#E8871E" stroke="none"/></svg>
            <div><div class="brand-text"><?php echo htmlspecialchars(APP_NAME); ?></div><div class="brand-sub">Municipality of Manolo Fortich</div></div>
        </div>
        <div class="pitch"><h1>Request access to PuestoMap.</h1><p>Submit your account details for review. The Admin / BPLO Head Officer must approve your request before a login account is created.</p></div>
        <div><div class="pin-legend"><span><i style="background:#2E8B57"></i> Account approval required</span></div><p class="footnote" style="margin-top:1.25rem;">Business Permits and Licensing Office · Manolo Fortich, Bukidnon</p></div>
    </div>
    <div class="auth-form-col">
        <div class="auth-card">
            <div class="kicker">New user registration</div>
            <h2>Request an account</h2>
            <p class="sub">Complete the form below. Your account will remain pending until approved by the Admin / BPLO Head Officer.</p>
            <?php if ($error !== ''): ?><div class="auth-alert" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="auth-alert info" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($success === ''): ?>
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="form-group"><label class="form-label" for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" maxlength="150" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required></div>
                <div class="form-group"><label class="form-label" for="email">Email address</label><input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required></div>
                <div class="form-group"><label class="form-label" for="role">Account type</label><select class="form-control" id="role" name="role" required><option value="">Select account type</option><option value="bplo_staff" <?php echo (($_POST['role'] ?? '') === 'bplo_staff') ? 'selected' : ''; ?>>BPLO Staff</option><option value="inspector" <?php echo (($_POST['role'] ?? '') === 'inspector') ? 'selected' : ''; ?>>Business Permit Inspector</option><option value="vendor" <?php echo (($_POST['role'] ?? '') === 'vendor') ? 'selected' : ''; ?>>Vendor</option></select></div>
                <div class="form-group"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required><small class="form-hint">Use at least 12 characters.</small></div>
                <div class="form-group"><label class="form-label" for="confirm_password">Confirm password</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="12" autocomplete="new-password" required></div>
                <div class="approval-note">Submitting this form does not create an active account. The administrator must review and approve your request first.</div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Submit for Approval</button>
            </form>
            <?php endif; ?>
            <div class="auth-links"><a href="<?php echo BASE_URL; ?>/login/index.php">Back to Log In</a></div>
        </div>
    </div>
</div>
</body>
</html>
