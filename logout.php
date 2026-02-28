<?php
require_once __DIR__ . '/includes/guard.php';
ais_init_session();

function __ais_cookie_path() {
    if (ini_get('session.cookie_path')) {
        return ini_get('session.cookie_path');
    }
    // Fallback if not set
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^/([^/]+)/#', $scriptName, $m)) return '/' . $m[1] . '/';
    return '/';
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $path = __ais_cookie_path();
    setcookie(session_name(), '', time() - 42000, $path, $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}
session_destroy();

header('Location: ' . __ais_redirect_prefix() . 'login.php');
exit;
