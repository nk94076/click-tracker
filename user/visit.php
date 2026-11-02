<?php
require_once __DIR__ . '/campaign-lib.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: strict-origin-when-cross-origin');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}
$token = $_GET['c'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{48}$/D', $token)) {
    http_response_code(404);
    exit('Link not found.');
}
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/../include/config2.php';
    $stmt = $conn->prepare('SELECT id, url FROM visitor_campaigns WHERE token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $campaign = $stmt->get_result()->fetch_assoc();
    if (!$campaign || !campaign_url_valid($campaign['url'])) {
        http_response_code(404);
        exit('Link not found.');
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $purpose = ($_SERVER['HTTP_SEC_PURPOSE'] ?? '') . ' ' . ($_SERVER['HTTP_PURPOSE'] ?? '');
    if (campaign_countable($method, $ua, $purpose)) {
        $column = campaign_device($ua) . '_visits'; // Fixed allowlist from helper.
        $stmt = $conn->prepare("UPDATE visitor_campaigns SET $column = $column + 1 WHERE id = ? AND active = 1");
        $stmt->bind_param('i', $campaign['id']);
        $stmt->execute();
    }
    header('Location: ' . $campaign['url'], true, 302);
} catch (Throwable $e) {
    error_log('Visitor tracking unavailable: ' . $e->getMessage());
    if (isset($campaign) && $campaign && campaign_url_valid($campaign['url'])) {
        header('Location: ' . $campaign['url'], true, 302);
    } else {
        http_response_code(503);
        echo 'Tracking temporarily unavailable. Please try again.';
    }
}
