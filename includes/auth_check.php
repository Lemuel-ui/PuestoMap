<?php
/**
 * auth_check.php
 * -----------------------------------------------------------------------
 * Include this at the very top of any protected page:
 *
 *   require_once '../includes/auth_check.php';
 *   require_role(['bplo_staff']);   // pass the list of roles allowed here
 *
 * If the user is not logged in, or logged in as the wrong role, they are
 * redirected back to the login page.
 * -----------------------------------------------------------------------
 */

require_once __DIR__ . '/db.php';

function require_role(array $allowedRoles) {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: ' . BASE_URL . '/login/index.php?msg=session_expired');
        exit;
    }

    // Re-check the account on every protected request so deactivated users
    // cannot continue using an already-open session.
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT role, is_active FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $account = $stmt->fetch();

        if (!$account || (int) $account['is_active'] !== 1 ||
            !hash_equals((string) $account['role'], (string) $_SESSION['role'])) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
                session_destroy();
            }
            header('Location: ' . BASE_URL . '/login/index.php?msg=session_expired');
            exit;
        }
    } catch (Throwable $e) {
        error_log(APP_NAME . ' authentication check failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Unable to verify your account right now. Please try again later.');
    }

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header('Location: ' . BASE_URL . '/login/index.php?msg=unauthorized');
        exit;
    }
}
