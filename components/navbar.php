
<?php
/**
 * navbar.php
 * Renders the sticky top navigation bar.
 * Expects $pageTitle to be set by the including page.
 */

require_once __DIR__ . '/../includes/functions.php';

$userName = $_SESSION['name'] ?? 'Guest User';
$roleKey = $_SESSION['role'] ?? 'vendor';

$initials = '';

foreach (explode(' ', trim($userName)) as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}

$initials = substr($initials, 0, 2);

// Get the real notification count from the database.
$notifCount = 0;

try {
    $notifCount = count_notifications(get_db());
} catch (Throwable $e) {
    error_log('Unable to load navbar notification count: ' . $e->getMessage());
}
?>

<header class="topbar">
    <div style="display:flex;align-items:center;gap:.9rem;">
        <button
            id="pmSidebarToggle"
            class="btn-outline btn btn-sm"
            style="display:none;"
            aria-label="Toggle navigation"
            type="button"
        >☰</button>

        <div class="topbar-title">
            <?php echo htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>

    <div class="topbar-actions">
        <button
            class="bell-btn"
            aria-label="Notifications"
            title="<?php echo (int) $notifCount; ?> notifications"
            type="button"
        >
            <?php if ($notifCount > 0): ?>
                <span class="bell-dot"></span>
            <?php endif; ?>

            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </button>

        <div class="user-chip">
            <div class="user-avatar">
                <?php echo htmlspecialchars($initials ?: 'PM', ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="user-meta">
                <div class="name">
                    <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="role">
                    <?php echo htmlspecialchars(ROLE_LABELS[$roleKey] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>
</header>
