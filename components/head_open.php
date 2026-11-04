<?php
/**
 * head_open.php
 * -----------------------------------------------------------------------
 * Opens the HTML document and the app shell. Include this AFTER setting:
 *   $pageTitle    (string) — browser tab title AND topbar heading
 *   $activePage   (string) — active sidebar nav key
 * and AFTER require_role() has already run.
 * Pair with components/footer.php to close the document.
 * -----------------------------------------------------------------------
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle ?? APP_NAME); ?> · <?php echo APP_NAME; ?></title>

<!-- Bootstrap 5 (layout utilities only; visual design comes from style.css) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Leaflet.js for GIS mapping -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<!-- PuestoMap design system -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-col">
        <?php include __DIR__ . '/../components/navbar.php'; ?>
        <div class="content">
