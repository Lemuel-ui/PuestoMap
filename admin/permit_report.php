<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/functions.php';
$pdo = get_db();
$vendors = get_all_vendors($pdo, false);
usort($vendors, function($a,$b){ return strcmp($a['expiry'], $b['expiry']); });
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permit Records Report - PuestoMap</title><style>body{font:14px Arial,sans-serif;color:#172b2b;margin:28px}h1{margin-bottom:4px}p{color:#526563}.toolbar{margin:18px 0}button{padding:9px 14px;border:1px solid #777;background:#fff;border-radius:5px;cursor:pointer}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #cbd5d3;padding:9px;text-align:left}th{background:#edf2f1}.status{text-transform:capitalize}@media print{.toolbar{display:none}body{margin:10mm}tr{page-break-inside:avoid}}</style></head><body>
<h1>PuestoMap - Business Permit Records</h1><p>Municipality of Manolo Fortich, Bukidnon | Generated: <?php echo date('F d, Y h:i A'); ?></p>
<div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
<table><thead><tr><th>Business Name</th><th>Owner</th><th>Permit Number</th><th>Barangay</th><th>Business Type</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>
<?php foreach($vendors as $v): ?><tr><td><?php echo h($v['name']); ?></td><td><?php echo h($v['owner']); ?></td><td><?php echo h($v['permit_no']); ?></td><td><?php echo h($v['barangay']); ?></td><td><?php echo h($v['category']); ?></td><td><?php echo h($v['expiry']); ?></td><td class="status"><?php echo h($v['permit_status']); ?></td></tr><?php endforeach; ?>
<?php if(!$vendors): ?><tr><td colspan="7">No permit records found.</td></tr><?php endif; ?></tbody></table></body></html>
