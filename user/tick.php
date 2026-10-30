<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ignore_user_abort(true);
@set_time_limit(0);

$jobId = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? '');
$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$expected = substr(hash('sha256', $jobId . 'tick_salt_v1'), 0, 16);
if (!$jobId || $token !== $expected) {
    http_response_code(403);
    exit('forbidden');
}

$jobDir     = __DIR__ . '/jobs';
$configFile = "$jobDir/$jobId.config.json";
$statusFile = "$jobDir/$jobId.status.json";
if (!file_exists($configFile) || !file_exists($statusFile)) exit('config missing');

$config = json_decode(file_get_contents($configFile), true);
$status = json_decode(file_get_contents($statusFile), true);

if (!empty($status['stop_requested'])) {
    $status['state'] = 'stopped';
    $status['next_delay'] = 0;
    file_put_contents($statusFile, json_encode($status), LOCK_EX);
    exit('stopped');
}
if (($status['state'] ?? '') === 'done') exit('done');

$geos = $config['geo_codes'] ?? ['IN'];
$geo = $geos[array_rand($geos)];
$config['geo_code'] = $geo; 

$result = call_click_handler($config);
$result['geo'] = $geo; 

$status['sent']    = ($status['sent'] ?? 0) + 1;
$status['ok']      = ($status['ok']   ?? 0) + (!empty($result['success']) ? 1 : 0);
$status['fail']    = ($status['fail'] ?? 0) + (empty($result['success']) ? 1 : 0);
$status['current'] = $status['sent'];
$logs = $status['logs'] ?? [];
$logs[] = array_merge(['i' => $status['sent']], $result);
$status['logs'] = $logs;

$total = (int)$config['total'];

if ($status['sent'] >= $total) {
    $status['state'] = 'done';
    $status['next_delay'] = 0;
    file_put_contents($statusFile, json_encode($status), LOCK_EX);
    exit('done');
}

$minDelay = 2;
$maxDelay = (int)$config['max_delay'];
$delay = rand($minDelay, $maxDelay);

$status['state'] = 'running';
$status['next_delay'] = $delay;
$status['next_at'] = time() + $delay;
file_put_contents($statusFile, json_encode($status), LOCK_EX);

spawn_tick($config['tick_url'], $jobId, $delay);
echo 'ok';

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

function call_click_handler($config) {
    if (empty($config['click_handler_url'])) {
        return ['success'=>false,'status_code'=>'ERR','ip'=>'—','final_url'=>'Missing click_handler_url'];
    }
    $ch = curl_init($config['click_handler_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'url' => $config['url'],
            'referral_website' => $config['referral_website'],
            'geo_code' => $config['geo_code'],
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIE         => 'PHPSESSID=' . $config['session_id'],
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false) return ['success'=>false,'status_code'=>'ERR','ip'=>'—','final_url'=>'cURL: '.$err];
    $data = json_decode($resp, true);
    if (!is_array($data)) return ['success'=>false,'status_code'=>$code,'ip'=>'—','final_url'=>'Bad response'];
    return $data;
}