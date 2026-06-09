<?php
// EcoSprout standalone MySQL/XAMPP diagnostic page.
// Open http://localhost/ecosprout/db_check.php if the project cannot connect.
mysqli_report(MYSQLI_REPORT_OFF);
$ports = [3306, 3307];
$hosts = ['127.0.0.1', 'localhost'];
$ok = false;
$rows = [];
foreach ($ports as $port) {
    foreach ($hosts as $host) {
        $conn = @new mysqli($host, 'root', '', '', $port);
        if (!$conn->connect_error) {
            $rows[] = [$host, $port, 'OK', 'Connected successfully'];
            $ok = true;
            $conn->close();
        } else {
            $rows[] = [$host, $port, 'FAILED', $conn->connect_error];
        }
    }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>EcoSprout DB Check</title>
<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;line-height:1.5}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:10px;text-align:left}.ok{color:#087b32;font-weight:bold}.bad{color:#b00020;font-weight:bold}code{background:#f4f4f4;padding:2px 5px}</style>
</head><body>
<h2>EcoSprout MySQL/XAMPP Check</h2>
<?php if ($ok): ?>
<p class="ok">MySQL is reachable. Open <a href="index.php">EcoSprout home page</a>.</p>
<?php else: ?>
<p class="bad">MySQL is not reachable from PHP.</p>
<ol>
<li>Open XAMPP Control Panel.</li>
<li>Start MySQL and make sure it turns green.</li>
<li>If MySQL stops again, change MySQL port from <code>3306</code> to <code>3307</code>.</li>
<li>This fixed project automatically tries both <code>3306</code> and <code>3307</code>.</li>
</ol>
<?php endif; ?>
<table><tr><th>Host</th><th>Port</th><th>Status</th><th>Message</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?=htmlspecialchars($r[0])?></td><td><?=htmlspecialchars((string)$r[1])?></td><td class="<?=$r[2]==='OK'?'ok':'bad'?>"><?=htmlspecialchars($r[2])?></td><td><?=htmlspecialchars($r[3])?></td></tr><?php endforeach; ?>
</table>
<h3>Manual database import</h3>
<p>After MySQL is green, double-click <code>RESET_AND_IMPORT_DATABASE.bat</code> inside the project folder, or import <code>database.sql</code> in phpMyAdmin.</p>
</body></html>
