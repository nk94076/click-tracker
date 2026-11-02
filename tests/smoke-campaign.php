<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require getcwd() . '/include/config2.php';
require getcwd() . '/user/campaign-lib.php';
campaign_schema($conn);
$token = bin2hex(random_bytes(24));
$target = 'https://example.invalid/campaign-qa';
$name = 'Temporary deployment QA';
$source = '';
$device = 'both';
$id = null;
function qa_visit($token, $ua, $expected) {
    $ch = curl_init('https://testing.adtrackr.org/user/visit.php?c=' . $token);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true,
        CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>20, CURLOPT_USERAGENT=>$ua]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    if ($response === false || $status !== $expected) throw new RuntimeException('Unexpected tracking response: ' . $status);
    if ($expected === 302 && $redirect !== 'https://example.invalid/campaign-qa') throw new RuntimeException('Incorrect redirect');
}
function qa_counts($conn, $id, $android, $desktop) {
    $stmt = $conn->prepare('SELECT android_visits, desktop_visits FROM visitor_campaigns WHERE id=?');
    $stmt->bind_param('i', $id); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();
    if ((int)$row['android_visits'] !== $android || (int)$row['desktop_visits'] !== $desktop) throw new RuntimeException('Incorrect visit counts');
}
try {
    $user = 0;
    $stmt = $conn->prepare('INSERT INTO visitor_campaigns (user_id, token, name, url, source_url, device) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $user, $token, $name, $target, $source, $device);
    $stmt->execute(); $id = $conn->insert_id;
    qa_visit($token, 'Mozilla/5.0 (Linux; Android 14) Mobile', 302);
    qa_visit($token, 'Mozilla/5.0 (Windows NT 10.0)', 302);
    qa_counts($conn, $id, 1, 1);
    $conn->query('UPDATE visitor_campaigns SET active=0 WHERE id=' . (int)$id);
    qa_visit($token, 'Mozilla/5.0 (Windows NT 10.0)', 302);
    qa_counts($conn, $id, 1, 1);
    $conn->query('UPDATE visitor_campaigns SET active=1 WHERE id=' . (int)$id);
    qa_visit($token, 'Mozilla/5.0 (Windows NT 10.0)', 302);
    qa_visit($token, 'Googlebot', 302);
    qa_counts($conn, $id, 1, 2);
    $conn->query('DELETE FROM visitor_campaigns WHERE id=' . (int)$id);
    qa_visit($token, 'Mozilla/5.0', 404);
    echo "Database, redirects, device counts, pause/resume, bot exclusion and deleted links passed.\n";
} finally {
    if ($id !== null) $conn->query('DELETE FROM visitor_campaigns WHERE id=' . (int)$id);
}
