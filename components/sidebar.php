<?php
/**
 * sidebar.php
 * -----------------------------------------------------------------------
 * Renders the left navigation. Expects the including page to have set:
 *   $activePage  (string) — key of the current nav item, e.g. 'dashboard'
 * Reads the logged-in role from $_SESSION['role'] to decide which
 * navigation items to show.
 * -----------------------------------------------------------------------
 */

$role = $_SESSION['role'] ?? 'vendor';
$activePage = $activePage ?? '';

// Minimal inline icon set (stroke-style, 24x24) so the app has no
// external icon-font dependency.
function pm_icon($name) {
    $icons = [
        'grid'    => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
        'pin'     => '<path d="M12 22s7-7.58 7-12A7 7 0 0 0 5 10c0 4.42 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'users'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'file'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'check'   => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'bell'    => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'shield'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'archive' => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M10 13h4"/>',
        'compass' => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
        'clip'    => '<path d="M9 3h6a1 1 0 0 1 1 1v1H8V4a1 1 0 0 1 1-1z"/><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 12h6M9 16h6"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'map'     => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
    ];
    $path = $icons[$name] ?? $icons['grid'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// Navigation definitions keyed by role
$nav = [];

if ($role === 'admin') {
    $nav = [
        ['section' => 'Overview'],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => BASE_URL . '/admin/dashboard.php'],
        ['section' => 'System Administration'],
        ['key' => 'accounts', 'label' => 'User Accounts', 'icon' => 'users', 'href' => BASE_URL . '/admin/user_accounts.php'],
        ['key' => 'backup', 'label' => 'Backup &amp; Restore', 'icon' => 'archive', 'href' => BASE_URL . '/admin/backup_restore.php'],
        ['key' => 'gis_map', 'label' => 'Vendor Map (GIS)', 'icon' => 'map', 'href' => BASE_URL . '/admin/gis_map.php'],
    ];
} elseif ($role === 'bplo_staff') {
    $nav = [
        ['section' => 'Overview'],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => BASE_URL . '/bplo_staff/dashboard.php'],
        ['section' => 'Vendor Management'],
        ['key' => 'register', 'label' => 'Vendor Registration', 'icon' => 'pin', 'href' => BASE_URL . '/bplo_staff/vendor_registration.php'],
        ['key' => 'vendors', 'label' => 'Manage Vendors', 'icon' => 'users', 'href' => BASE_URL . '/bplo_staff/vendor_management.php'],
        ['section' => 'Permits'],
        ['key' => 'permits', 'label' => 'Permit Management', 'icon' => 'file', 'href' => BASE_URL . '/bplo_staff/permit_management.php'],
        ['key' => 'location_requests', 'label' => 'Location Requests', 'icon' => 'compass', 'href' => BASE_URL . '/bplo_staff/location_requests.php'],
        ['key' => 'gis_map', 'label' => 'Vendor Map (GIS)', 'icon' => 'map', 'href' => BASE_URL . '/bplo_staff/gis_map.php'],
    ];
} elseif ($role === 'head_officer') {
    $nav = [
        ['section' => 'Overview'],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => BASE_URL . '/head_officer/dashboard.php'],
        ['section' => 'Monitoring'],
        ['key' => 'permits', 'label' => 'Permit Monitoring', 'icon' => 'file', 'href' => BASE_URL . '/head_officer/permit_monitoring.php'],
        ['key' => 'inspections', 'label' => 'Inspection Reports', 'icon' => 'clip', 'href' => BASE_URL . '/head_officer/inspection_reports.php'],
        ['key' => 'gis_map', 'label' => 'Vendor Map (GIS)', 'icon' => 'map', 'href' => BASE_URL . '/head_officer/gis_map.php'],
    ];
} elseif ($role === 'inspector') {
    $nav = [
        ['section' => 'Overview'],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => BASE_URL . '/inspector/dashboard.php'],
        ['section' => 'Field Work'],
        ['key' => 'gis_map', 'label' => 'Inspection Map', 'icon' => 'compass', 'href' => BASE_URL . '/inspector/map.php'],
        ['key' => 'inspections', 'label' => 'Inspection Management', 'icon' => 'clip', 'href' => BASE_URL . '/inspector/inspection_management.php'],
    ];
} elseif ($role === 'vendor') {
    $nav = [
        ['section' => 'Overview'],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => BASE_URL . '/vendor/dashboard.php'],
        ['section' => 'My Business'],
        ['key' => 'profile', 'label' => 'Business Profile', 'icon' => 'user', 'href' => BASE_URL . '/vendor/profile.php'],
        ['key' => 'permit', 'label' => 'Permit Status', 'icon' => 'file', 'href' => BASE_URL . '/vendor/permit_status.php'],
    ];
}
?>
<aside class="sidebar" id="pmSidebar">
    <div class="sidebar-brand">
        <svg class="pin" viewBox="0 0 24 24" fill="none" stroke="#E8871E" stroke-width="1.8">
            <path d="M12 22s7-7.58 7-12A7 7 0 0 0 5 10c0 4.42 7 12 7 12z" fill="rgba(232,135,30,0.15)"/>
            <circle cx="12" cy="10" r="2.6" fill="#E8871E" stroke="none"/>
        </svg>
        <div>
            <div class="brand-text"><?php echo APP_NAME; ?></div>
            <div class="brand-sub">Manolo Fortich BPLO</div>
        </div>
    </div>

    <div class="sidebar-role"><?php echo htmlspecialchars(ROLE_LABELS[$role] ?? 'User'); ?> Portal</div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $item): ?>
            <?php if (isset($item['section'])): ?>
                <div class="nav-section-label"><?php echo htmlspecialchars($item['section']); ?></div>
            <?php else: ?>
                <a href="<?php echo $item['href']; ?>" class="<?php echo $activePage === $item['key'] ? 'active' : ''; ?>">
                    <?php echo pm_icon($item['icon']); ?>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="nav-section-label">Session</div>
        <a href="<?php echo BASE_URL; ?>/logout/index.php">
            <?php echo pm_icon('logout'); ?>
            <span>Log Out</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?><br>
        Local Government of Manolo Fortich
    </div>
</aside>
