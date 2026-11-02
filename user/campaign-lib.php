<?php
// Shared helpers for visitor-initiated tracking. No outbound requests are made.
function campaign_url_valid($url) {
    if (!is_string($url) || strlen($url) > 4096 || preg_match('/[\x00-\x20\x7f]/', $url)) return false;
    $parts = parse_url($url);
    return filter_var($url, FILTER_VALIDATE_URL) && $parts &&
        in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) &&
        !isset($parts['user']) && !isset($parts['pass']);
}
function campaign_device($ua) {
    if (stripos($ua, 'Android') !== false) return 'android';
    if (preg_match('/iPhone|iPad|iPod|Mobile|Tablet/i', $ua)) return 'other';
    if (preg_match('/Windows NT|Macintosh|X11|CrOS/i', $ua)) return 'desktop';
    return 'other';
}
function campaign_countable($method, $ua, $purpose) {
    return $method === 'GET' && $ua !== '' &&
        !preg_match('/bot|crawler|spider|preview|headless|curl|wget/i', $ua) &&
        !preg_match('/prefetch|prerender|preview/i', $purpose);
}
function campaign_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS visitor_campaigns (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token CHAR(48) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        url TEXT NOT NULL,
        source_url TEXT NOT NULL,
        geos VARCHAR(255) NOT NULL DEFAULT '',
        device VARCHAR(10) NOT NULL DEFAULT 'both',
        active TINYINT NOT NULL DEFAULT 1,
        android_visits BIGINT UNSIGNED NOT NULL DEFAULT 0,
        desktop_visits BIGINT UNSIGNED NOT NULL DEFAULT 0,
        other_visits BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $column = $conn->query("SHOW COLUMNS FROM visitor_campaigns LIKE 'geos'");
    if ($column->num_rows === 0) {
        try { $conn->query("ALTER TABLE visitor_campaigns ADD COLUMN geos VARCHAR(255) NOT NULL DEFAULT ''"); }
        catch (mysqli_sql_exception $e) { if ((int)$e->getCode() !== 1060) throw $e; }
    }
}
