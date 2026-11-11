<?php
/**
 * Copy this file to local_config.php and fill in your hosting values.
 * Keep local_config.php private and never upload it to a public repository.
 * Environment variables take precedence over values in this file.
 */
return [
    'APP_ENV' => 'production',
    'DB_HOST' => 'localhost', // use the database host shown by your provider
    'DB_PORT' => '3306',
    'DB_NAME' => 'your_cpanelprefix_puestomap',
    'DB_USER' => 'your_cpanelprefix_dbuser',
    'DB_PASS' => 'replace_with_a_long_unique_password',
    // Absolute path outside public_html / web root. Create this directory first.
    'PUESTOMAP_BACKUP_DIR' => '/home/YOUR_CPANEL_USERNAME/puestomap_private/backups',
];
