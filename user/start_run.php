<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$url      = trim($_POST['url'] ?? '');
$ref      = trim($_POST['referral_website'] ?? '');
$geoRaw   = strtoupper(trim($_POST['geo_code'] ?? 'IN'));
$geos     = array_filter(array_map('trim', explode(',', $geoRaw)));
if (empty($geos)) $geos = ['IN'];

$total    = min(500, max(1, (int)($_POST['total'] ?? 10)));
$maxDelay = min(600, max(2, (int)($_POST['max_delay'] ?? 600)));

if (!$url || !$ref) exit(json_encode(['error' => 'Missing url or referral']));

$jobId  = bin2hex(random_bytes(8));
$jobDir = __DIR__ . '/jobs';
if (!is_dir($jobDir)) @mkdir($jobDir, 0755, true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$clickUrl = $scheme . '://' . $host . $base . '/click_handler.php';
$tickUrl  = $scheme . '://' . $host . $base . '/tick.php';

$config = [
    'id'                => $jobId,
    'url'               => $url,
    'referral_website'  => $ref,
    'geo_codes'         => array_values($geos),
    'total'             => $total,
    'max_delay'         => $maxDelay,
    'session_id'        => session_id(),
    'click_handler_url' => $clickUrl,
    'tick_url'          => $tickUrl,
];
file_put_contents("$jobDir/$jobId.config.json", json_encode($config));

file_put_contents("$jobDir/$jobId.status.json", json_encode([
    'id' => $jobId, 'state' => 'running',
    'sent' => 0, 'ok' => 0, 'fail' => 0,
    'total' => $total, 'current' => 0,
    'next_delay' => 0, 'next_at' => 0,
    'logs' => [], 'stop_requested' => false,
]));

spawn_tick($tickUrl, $jobId, 0);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'job_id' => $jobId, 'mode' => 'tick-chain']);

function spawn_tick($tickUrl, $jobId, $delaySec = 0) {
    $target = $tickUrl . '?job=' . urlencode($jobId) . '&token=' . substr(hash('sha256', $jobId . 'tick_salt_v1'), 0, 16);
    $inner  = ($delaySec > 0 ? 'sleep ' . (int)$delaySec . '; ' : '')
            . 'curl -s -k -m 120 "' . $target . '" > /dev/null 2>&1';
    $cmd = 'sh -c ' . escapeshellarg($inner) . ' > /dev/null 2>&1 &';
    if (function_exists('proc_open')) {
        $desc = [0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']];
        $pipes = [];
        $p = @proc_open($cmd, $desc, $pipes, '/tmp');
        if (is_resource($p)) { @proc_close($p); return true; }
    }
    if (function_exists('exec')) { @exec($cmd); return true; }
    return false;
}