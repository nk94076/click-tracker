<?php
ignore_user_abort(true);
@set_time_limit(0);

require __DIR__ . '/../include/config.php';

$campaign_id = (int)($argv[1] ?? 0);
if ($campaign_id <= 0) exit("Invalid campaign ID\n");

function db() {
    $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($c->connect_error) return null;
    $c->set_charset('utf8mb4');
    return $c;
}

$conn = db();
if (!$conn) exit("DB fail\n");

$s = $conn->prepare("SELECT website, total_clicks, completed_clicks, country, duration_minutes FROM traffic_campaigns WHERE id = ?");
$s->bind_param("i", $campaign_id);
$s->execute();
$camp = $s->get_result()->fetch_assoc();
$s->close();
$conn->close();

if (!$camp) exit("Campaign not found\n");

$website   = $camp['website'];
$total     = (int)$camp['total_clicks'];
$country   = $camp['country'] ?: 'in';
$duration  = (int)$camp['duration_minutes'];

$interval = ($duration > 0 && $total > 0)
    ? max(1, (int)floor(($duration * 60) / $total))
    : 1;

for ($i = 0; $i < $total; $i++) {
    $conn = db();
    if (!$conn) { sleep(2); continue; }

    $chk = $conn->prepare("SELECT status FROM traffic_campaigns WHERE id = ?");
    $chk->bind_param("i", $campaign_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row || $row['status'] === 'stopped') {
        $conn->close();
        exit("Stopped\n");
    }

    sendHit($website, $country, $campaign_id, $conn);

    $up = $conn->prepare("UPDATE traffic_campaigns SET completed_clicks = completed_clicks + 1 WHERE id = ?");
    $up->bind_param("i", $campaign_id);
    $up->execute();
    $up->close();
    $conn->close();

    if ($i < $total - 1) sleep($interval);
}

$conn = db();
if ($conn) {
    $done = $conn->prepare("UPDATE traffic_campaigns SET status='completed' WHERE id = ?");
    $done->bind_param("i", $campaign_id);
    $done->execute();
    $done->close();
    $conn->close();
}

function sendHit($url, $country, $campaign_id, $conn) {
    $proxyHost = "gw.dataimpulse.com";
    $proxyPort = 823;
    $baseUser  = "3ef0a21a53aae883cab";
    $password  = "255e04d20c70a8c1";

    $username = $baseUser . "-country-" . $country;
    $proxyStr = "$username:$password@$proxyHost:$proxyPort";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_PROXY           => "$proxyHost:$proxyPort",
        CURLOPT_PROXYUSERPWD    => "$username:$password",
        CURLOPT_PROXYTYPE       => CURLPROXY_HTTP,
        CURLOPT_PROXYAUTH       => CURLAUTH_BASIC,
        CURLOPT_HTTPPROXYTUNNEL => true,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_IPRESOLVE       => CURL_IPRESOLVE_V4,
        CURLOPT_FRESH_CONNECT   => true,
        CURLOPT_FORBID_REUSE    => true,
        CURLOPT_TIMEOUT         => 60,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_SSL_VERIFYHOST  => false,
        CURLOPT_USERAGENT       => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($conn) {
        $stmt = $conn->prepare("
            INSERT INTO traffic_logs
            (campaign_id, website, country, proxy, http_code, error_message)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->bind_param("isssis", $campaign_id, $url, $country, $proxyStr, $httpCode, $error);
        $stmt->execute();
        $stmt->close();
    }

    return $httpCode === 200;
}