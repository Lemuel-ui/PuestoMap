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
function pm_env(string $key, string $default): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

define('APP_ENV', pm_env('APP_ENV', 'local'));
define('DB_HOST', pm_env('DB_HOST', 'localhost'));
define('DB_PORT', pm_env('DB_PORT', '3306'));
define('DB_NAME', pm_env('DB_NAME', 'puestomap'));
define('DB_USER', pm_env('DB_USER', 'root'));
define('DB_PASS', pm_env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

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
    'admin'        => 'MISU Administrator',
    'bplo_staff'   => 'BPLO Staff',
    'head_officer' => 'BPLO Head Officer',
    'inspector'    => 'Business Permit Inspector',
    'vendor'       => 'Vendor',
]);

// Landing page for each role after login
define('ROLE_HOME', [
    'admin'        => BASE_URL . '/admin/dashboard.php',
    'bplo_staff'   => BASE_URL . '/bplo_staff/dashboard.php',
    'head_officer' => BASE_URL . '/head_officer/dashboard.php',
    'inspector'    => BASE_URL . '/inspector/dashboard.php',
    'vendor'       => BASE_URL . '/vendor/dashboard.php',
]);
