<?php
/**
 * config.php
 * -----------------------------------------------------------------------
 * Central configuration for PuestoMap.
 * Defines application settings, detects the base URL, hardens and starts
 * the session, and declares the role → label / dashboard maps.
 * -----------------------------------------------------------------------
 */

/* ===========================================================================
 * Application identity
 * ======================================================================= */
define('APP_NAME',    'PuestoMap');
define('APP_TAGLINE', 'Vendor Mapping and Business Permit Monitoring System');
define('APP_SLUG',    'puestomap');
define('APP_VERSION', '1.0');
define('APP_OFFICE',  'Business Permits and Licensing Office, Municipality of Manolo Fortich, Bukidnon');

// Local timezone for every date calculation (permit expiry, "time ago", backups).
date_default_timezone_set('Asia/Manila');

/* ===========================================================================
 * Base URL
 * The URL path under which the app is served, WITHOUT a trailing slash.
 * It is detected from the folder's location inside the web root, so
 *   C:\xampp\htdocs\PuestoMap  ->  /PuestoMap  (http://localhost/PuestoMap)
 * If detection ever fails on your machine, set $PM_BASE_URL_OVERRIDE below,
 * e.g. '/PuestoMap'.
 * ======================================================================= */
$PM_BASE_URL_OVERRIDE = null;

function pm_detect_base_url(): string
{
    $projectRoot = realpath(__DIR__ . '/..');
    $docRoot     = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot !== false && $docRoot !== false) {
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $docRoot     = rtrim(str_replace('\\', '/', $docRoot), '/');

        if (stripos($projectRoot, $docRoot) === 0) {
            return rtrim(substr($projectRoot, strlen($docRoot)), '/');
        }
    }

    return '/PuestoMap'; // sensible default for a standard XAMPP install
}

define('BASE_URL', $PM_BASE_URL_OVERRIDE !== null
    ? rtrim($PM_BASE_URL_OVERRIDE, '/')
    : pm_detect_base_url());

/* ===========================================================================
 * Session (own cookie name + path so it never collides with another
 * application running on http://localhost)
 * ======================================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_name('PUESTOMAP_SID');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_URL === '' ? '/' : BASE_URL . '/'),
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/* ===========================================================================
 * Database connection
 * Local XAMPP defaults are retained for development. Configure environment
 * variables in hosting for production; never commit real credentials.
 * ======================================================================= */
// Optional protected local configuration for shared hosting panels that do
// not expose environment variables. Keep local_config.php out of version control.
$PM_LOCAL_CONFIG = [];
$PM_LOCAL_CONFIG_FILE = __DIR__ . '/local_config.php';
if (is_file($PM_LOCAL_CONFIG_FILE)) {
    $loadedConfig = require $PM_LOCAL_CONFIG_FILE;
    if (is_array($loadedConfig)) {
        $PM_LOCAL_CONFIG = $loadedConfig;
    }
}

function pm_env(string $key, string $default, array $localConfig = []): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }
    if (isset($localConfig[$key]) && is_scalar($localConfig[$key]) && (string) $localConfig[$key] !== '') {
        return (string) $localConfig[$key];
    }
    return $default;
}

define('APP_ENV', pm_env('APP_ENV', 'local', $PM_LOCAL_CONFIG));
define('DB_HOST', pm_env('DB_HOST', 'localhost', $PM_LOCAL_CONFIG));
define('DB_PORT', pm_env('DB_PORT', '3306', $PM_LOCAL_CONFIG));
define('DB_NAME', pm_env('DB_NAME', 'puestomap', $PM_LOCAL_CONFIG));
define('DB_USER', pm_env('DB_USER', 'root', $PM_LOCAL_CONFIG));
define('DB_PASS', pm_env('DB_PASS', '', $PM_LOCAL_CONFIG));
define('DB_CHARSET', 'utf8mb4');

// Backups should be stored outside the public website directory. Set
// PUESTOMAP_BACKUP_DIR to an absolute private path on the hosting account.
$defaultPrivateBackupDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'puestomap_private'
    . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
define('BACKUP_DIR', rtrim(pm_env('PUESTOMAP_BACKUP_DIR', $defaultPrivateBackupDir, $PM_LOCAL_CONFIG), '/\\\\') . DIRECTORY_SEPARATOR);

if (APP_ENV === 'production' && (DB_USER === 'root' || DB_PASS === '')) {
    error_log(APP_NAME . ': Production database credentials are not safely configured.');
    http_response_code(500);
    exit('The production database is not configured. Contact the system administrator.');
}

/* ===========================================================================
 * Inspection photo uploads
 * ======================================================================= */
define('UPLOAD_DIR', __DIR__ . '/../uploads/inspections/');
define('UPLOAD_URL', BASE_URL . '/uploads/inspections/');

/* ===========================================================================
 * Roles
 * ======================================================================= */
define('ROLE_LABELS', [
    'admin'        => 'Admin / BPLO Head Officer',
    'bplo_staff'   => 'BPLO Staff',
    'inspector'    => 'Business Permit Inspector',
    'vendor'       => 'Vendor',
]);

// Landing page for each role after login
define('ROLE_HOME', [
    'admin'        => BASE_URL . '/admin/dashboard.php',
    'bplo_staff'   => BASE_URL . '/bplo_staff/dashboard.php',
    'inspector'    => BASE_URL . '/inspector/dashboard.php',
    'vendor'       => BASE_URL . '/vendor/dashboard.php',
]);
