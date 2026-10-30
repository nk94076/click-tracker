<?php
require __DIR__ . '/../include/config.php';

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid request']));
}

$website  = trim($_POST['website'] ?? '');
$clicks   = (int)($_POST['clicks'] ?? 0);
$country  = strtolower(trim($_POST['country'] ?? 'in'));
$duration = (int)($_POST['duration'] ?? 0);

if ($website === '') exit(json_encode(['error' => 'Website required']));

$country = substr($country, 0, 2);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB failed: ' . $conn->connect_error);
    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare("
        INSERT INTO traffic_campaigns 
        (website, total_clicks, completed_clicks, country, duration_minutes, status)
        VALUES (?, ?, 0, ?, ?, 'running')
    ");
    $stmt->bind_param("sisi", $website, $clicks, $country, $duration);
    $stmt->execute();
    $campaign_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    $worker = __DIR__ . '/worker-campaign.php';
    $cmd = escapeshellarg(PHP_BINARY) . ' '
         . escapeshellarg($worker) . ' '
         . escapeshellarg((string)$campaign_id);

    $desc = [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', '/dev/null', 'w'],
        2 => ['file', '/dev/null', 'w'],
    ];
    $pipes = [];
    $proc = @proc_open($cmd, $desc, $pipes, __DIR__);
    if (!is_resource($proc)) {
        @exec($cmd . ' > /dev/null 2>&1 &');
    }

    echo json_encode([
        'success'     => true,
        'campaign_id' => $campaign_id,
        'total'       => $clicks
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}