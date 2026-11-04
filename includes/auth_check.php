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

require_once __DIR__ . '/config.php';

function require_role(array $allowedRoles) {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: ' . BASE_URL . '/login/index.php?msg=session_expired');
        exit;
    }

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header('Location: ' . BASE_URL . '/login/index.php?msg=unauthorized');
        exit;
    }
}
