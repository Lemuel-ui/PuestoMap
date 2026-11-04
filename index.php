<?php
/**
 * index.php
 * -----------------------------------------------------------------------
 * Entry point of the application. Redirects to the appropriate
 * role dashboard if a session already exists, otherwise sends the
 * visitor to the login page.
 * -----------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/config.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header('Location: ' . ROLE_HOME[$_SESSION['role']]);
    exit;
}

header('Location: ' . BASE_URL . '/login/index.php');
exit;
