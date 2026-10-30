<?php
session_start();

// Session data clear karo
$_SESSION = [];

// Session cookie delete karo (Remember me wali cookie bhi)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'domain'   => $params['domain'],
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

session_destroy();

// Back button se cached page na dikhe
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

header("Location: /index.php");
exit;