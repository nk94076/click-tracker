<?php
require __DIR__ . '/../user/campaign-lib.php';
function check($value, $message) { if (!$value) { throw new RuntimeException($message); } }
check(campaign_url_valid('https://example.com/path?a=1&b=2'), 'HTTPS URL');
foreach (['javascript:alert(1)', "https://example.com/\r\nX-Test:1", 'https://user:pass@example.com', '//example.com', ['invalid']] as $url) {
    check(!campaign_url_valid($url), 'Reject unsafe URL');
}
check(campaign_device('Mozilla/5.0 (Linux; Android 14) Mobile') === 'android', 'Android');
check(campaign_device('Mozilla/5.0 (Windows NT 10.0)') === 'desktop', 'Desktop');
check(campaign_device('Mozilla/5.0 (iPad; CPU OS like Mac OS X)') === 'other', 'iPad');
check(campaign_device('unknown') === 'other', 'Unknown device');
check(campaign_countable('GET', 'Mozilla/5.0 Windows NT', ''), 'Browser GET');
check(!campaign_countable('HEAD', 'Mozilla/5.0', ''), 'Ignore HEAD');
check(!campaign_countable('GET', 'Googlebot', ''), 'Ignore bots');
check(!campaign_countable('GET', 'Mozilla/5.0', 'prefetch'), 'Ignore prefetch');
echo "Campaign helper checks passed.\n";
