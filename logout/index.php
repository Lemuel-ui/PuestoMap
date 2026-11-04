<?php
/**
 * logout/index.php
 * -----------------------------------------------------------------------
 * Destroys the session and returns the user to the login page.
 * -----------------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/config.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/login/index.php');
exit;
