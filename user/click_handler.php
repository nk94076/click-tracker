<?php
// click_handler.php — Final Stable, UTM Extractor & DB Fix
ini_set('display_errors', '0');
error_reporting(0);

// ✅ Crash hone par JSON return karega
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['success' => false, 'status_code' => 'FATAL', 'final_url' => 'PHP Crash: ' . $error['message'], 'ip' => '—', 'geo' => '—']);
    }
});

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
include '../include/config2.php';

$DI_CONFIG = [
    'username'   => '3ef0a21a53aae883c3ab',
    'password'   => '3911c7eda63701fe',
    'proxy_host' => 'gw.dataimpulse.com',
    'port'       => 823
];

// ✅ Proxy builder (No Sticky Session - Fixes GEO issue)
function getDataImpulseCreds($geo_code = 'IN') {
    global $DI_CONFIG;
    
    $geo = strtolower(trim($geo_code));
    
    // UK to GB fix (DataImpulse format)
    if ($geo === 'uk' || $geo === 'gb') { $geo = 'gb'; }
    
    if (strlen($geo) !== 2) { $geo = 'in'; }
    
    return [
        'host' => $DI_CONFIG['proxy_host'],
        'port' => $DI_CONFIG['port'],
        'user' => $DI_CONFIG['username'] . '__cr.' . $geo,
        'pass' => $DI_CONFIG['password']
    ];
}

// ✅ IP Fetcher
function fetchRealIP($creds) {
    $ch = curl_init('http://ip-api.com/json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY          => $creds['host'] . ':' . $creds['port'],
        CURLOPT_PROXYUSERPWD   => $creds['user'] . ':' . $creds['pass'],
        CURLOPT_PROXYTYPE      => CURLPROXY_HTTP,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_PROXY_SSL_VERIFYPEER => false,
        CURLOPT_PROXY_SSL_VERIFYHOST => 0,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if ($resp) {
        $data = json_decode($resp, true);
        if (isset($data['query'])) {
            return [
                'ip'           => $data['query'],
                'country_code' => $data['countryCode'] ?? 'Unknown'
            ];
        }
    }
    return ['ip' => 'Unknown', 'country_code' => 'Unknown'];
}

// ✅ URL Cleaner
function cleanFinalUrl($url) {
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) return $url;
    $scheme = $parsed['scheme'] ?? 'https';
    $host   = $parsed['host'];
    $port   = $parsed['port'] ?? null;
    if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) { $port = null; }
    $new_url = $scheme . '://' . $host . ($port ? ':' . $port : '');
    if (isset($parsed['path'])) $new_url .= $parsed['path'];
    if (isset($parsed['query'])) $new_url .= '?' . $parsed['query'];
    if (isset($parsed['fragment'])) $new_url .= '#' . $parsed['fragment'];
    return $new_url;
}

// ✅ Main Clicker
function sendImpactClick($target_url, $referral_website, $geo_code = 'IN') {
    $creds = getDataImpulseCreds($geo_code);
    $ip_data = fetchRealIP($creds);
    $ip_used = $ip_data['ip'];
    $real_geo = ($ip_data['country_code'] !== 'Unknown') ? $ip_data['country_code'] : strtoupper($geo_code);

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    $redirect_chain = [];
    $referral_url = !empty($referral_website) ? filter_var($referral_website, FILTER_SANITIZE_URL) : 'https://www.google.com/';

    $cookie_jar = tempnam(sys_get_temp_dir(), 'aff_cookie_');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_PROXY,        $creds['host'] . ':' . $creds['port']);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $creds['user'] . ':' . $creds['pass']);
    curl_setopt($ch, CURLOPT_PROXYTYPE,    CURLPROXY_HTTP);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
    curl_setopt($ch, CURLOPT_FORBID_REUSE,  false);
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
    curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie_jar);
    curl_setopt($ch, CURLOPT_ENCODING,   'gzip, deflate');

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_PROXY_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROXY_SSL_VERIFYHOST, 0);

    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'sec-ch-ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: cross-site',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1'
    ]);

    $current_url   = $target_url;
    $max_redirects = 15;
    $httpcode      = 0;
    $visited       = [];
    $curl_errno    = 0;
    $curl_error    = '';

    for ($i = 0; $i < $max_redirects; $i++) {
        $current_url = trim(str_replace(["\n", "\r", "\t"], '', $current_url));
        $current_url = str_replace(' ', '%20', $current_url);

        if (empty($current_url) || in_array($current_url, $visited)) break;
        $visited[] = $current_url;

        curl_setopt($ch, CURLOPT_REFERER, ($i > 0) ? $visited[$i - 1] : $referral_url);
        curl_setopt($ch, CURLOPT_URL, $current_url);
        
        $response = curl_exec($ch);

        if ($response === false) {
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);
            if ($i > 0 && !empty($visited[$i - 1])) { $current_url = $visited[$i - 1]; }
            break;
        }

        $httpcode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers     = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);

        $redirect_chain[] = ['url' => $current_url, 'status' => $httpcode];
        $next_url = null;

        // 1. HTTP Redirect
        if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $matches)) {
            $next_url = trim($matches[1]);
        }

        // 2. Meta Refresh
        if (!$next_url && preg_match('/<meta\s+[^>]*http-equiv=["\']refresh["\'][^>]*content=["\']\d+;\s*url=([^"\']+)["\']/i', $body, $meta_matches)) {
            $next_url = trim($meta_matches[1]);
        }

        // 3. JavaScript Redirect (Partnermatic / Awin / Admitad)
        $body_len = strlen($body);
        if (!$next_url && $body_len < 35000) {
            if (preg_match('/(?:window\.)?location(?:\.href|\.replace|\.assign)?\s*(?:=\s*|\()\s*["\'](https?:\/\/[^"\']+)["\']/i', $body, $js_match)) {
                $next_url = trim($js_match[1]);
            } elseif (preg_match('/var\s+([a-zA-Z0-9_$]+)\s*=\s*["\'](https?:\/\/[^"\']+)["\']/is', $body, $var_match)) {
                $var_name = preg_quote($var_match[1], '/');
                if (preg_match('/location(?:\.href|\.replace|\.assign)?\s*(?:=\s*|\()\s*' . $var_name . '/i', $body)) {
                    $next_url = $var_match[2];
                }
            }
            if ($next_url) { $next_url = html_entity_decode(trim($next_url)); }
        }

        // ✅ Agar final store page aa gaya (Tracking parameters ke sath)
        if ($httpcode === 200 && (stripos($current_url, 'awc=') !== false || stripos($current_url, 'irclickid=') !== false || stripos($current_url, 'thegreatcourses.com') !== false)) {
            break;
        }

        // Next Step Process
        if (!empty($next_url) && $next_url !== $current_url) {
            if (stripos($next_url, 'http') !== 0) {
                $parsed = parse_url($current_url);
                $scheme = $parsed['scheme'] ?? 'https';
                $host   = $parsed['host'] ?? '';
                if (strpos($next_url, '//') === 0) { $next_url = $scheme . ':' . $next_url; }
                elseif (strpos($next_url, '/') === 0) { $next_url = $scheme . '://' . $host . $next_url; }
                else { $dir = dirname($current_url); $next_url = ($dir != '.' && $dir != '/') ? $dir . '/' . $next_url : $current_url . '/' . $next_url; }
            }
            $current_url = $next_url;
            continue;
        }
        break;
    }

    $final_url = cleanFinalUrl($current_url);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);

    if (file_exists($cookie_jar)) { @unlink($cookie_jar); }

    $is_success = ($httpcode > 0 && in_array($httpcode, [200, 301, 302, 303, 307, 403, 429])) || (count($redirect_chain) > 1);

    return [
        'success'        => $is_success,
        'status_code'    => $httpcode ?: ($is_success ? 200 : 'ERR'),
        'ip'             => $ip_used ?: 'Unknown',
        'referral'       => $referral_url,
        'site_name'      => parse_url($referral_url, PHP_URL_HOST) ?: 'Direct',
        'geo'            => $real_geo,
        'requested_geo'  => strtoupper($geo_code),
        'user_agent'     => $ua,
        'final_url'      => $final_url ?: $target_url,
        'redirect_chain' => json_encode($redirect_chain),
        'curl_errno'     => $curl_errno,
        'curl_error'     => $curl_error ? ("cURL $curl_errno: " . $curl_error) : null,
        'connect_time'   => round((float)($curl_info['connect_time'] ?? 0), 3),
        'total_time'     => round((float)($curl_info['total_time'] ?? 0), 3)
    ];
}

// ========================================
// HANDLE REQUEST
// ========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);
$params    = is_array($json_data) ? array_merge($_POST, $json_data) : $_POST;

$target_url       = filter_var($params['url'] ?? '', FILTER_SANITIZE_URL);
$referral_website = filter_var($params['referral_website'] ?? '', FILTER_SANITIZE_URL);
$raw_geo          = trim($params['geo_code'] ?? $params['geo'] ?? $params['country'] ?? 'US');
$geo_code         = strtoupper(substr($raw_geo, 0, 2));

if (empty($target_url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No URL provided']);
    exit;
}

try {
    $result = sendImpactClick($target_url, $referral_website, $geo_code);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'final_url' => 'Error: ' . $e->getMessage(), 'ip' => '—', 'geo' => $geo_code]);
    exit;
}

// ✅ DB INSERT (User ID ke sath)
try {
    $click_id    = uniqid('clk_');
    $success_int = $result['success'] ? 1 : 0;
    $user_id     = (int)($_SESSION['user_id'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO impact_click_logs 
        (user_id, click_id, target_url, final_url, redirect_chain, referral, geo, ip, user_agent, status_code, success) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "issssssssii",
        $user_id,
        $click_id,
        $target_url,
        $result['final_url'],
        $result['redirect_chain'],
        $result['referral'],
        $result['geo'],
        $result['ip'],
        $result['user_agent'],
        $result['status_code'],
        $success_int
    );

    $stmt->execute();
    $stmt->close();
    $result['db_status'] = 'saved';

} catch (Exception $e) {
    $result['db_error'] = $e->getMessage();
}

echo json_encode($result);
exit;