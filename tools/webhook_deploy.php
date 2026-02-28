<?php
header('Content-Type: application/json');
$secret = getenv('GH_WEBHOOK_SECRET') ?: '';
$allowedIps = array_filter(array_map('trim', explode(',', getenv('GH_ALLOWED_IPS') ?: '')));
$debug = (getenv('GH_WEBHOOK_DEBUG') === '1');
$logFile = getenv('GH_WEBHOOK_LOG') ?: '/tmp/ais_webhook.log';
if ($debug) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$ts] init remote=" . ($_SERVER['REMOTE_ADDR'] ?? '') . PHP_EOL, FILE_APPEND);
}
$cfgPath = __DIR__ . '/config/webhook_secret.php';
if (is_file($cfgPath)) {
    $cfg = include $cfgPath;
    if (is_array($cfg)) {
        if (!$secret && isset($cfg['secret'])) { $secret = (string)$cfg['secret']; }
        if (!$allowedIps && isset($cfg['allowed_ips'])) {
            $allowedIps = array_filter(array_map('trim', is_array($cfg['allowed_ips']) ? $cfg['allowed_ips'] : explode(',', (string)$cfg['allowed_ips'])));
        }
        if (isset($cfg['debug'])) { $debug = (bool)$cfg['debug']; }
        if (isset($cfg['log']) && $cfg['log']) { $logFile = (string)$cfg['log']; }
    }
}
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if ($allowedIps && !in_array($remoteIp, $allowedIps, true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'ip_not_allowed']); exit; }
$raw = file_get_contents('php://input');
$sig256 = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$sig1 = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$sig = $sig256 ?: $sig1;
$algo = $sig256 ? 'sha256' : ($sig1 ? 'sha1' : '');
if ($debug) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$ts] event=" . ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '') . " algo=" . $algo . " sig_present=" . ($sig ? '1':'0') . PHP_EOL, FILE_APPEND);
}
if (!$secret || !$sig || !$algo) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'missing_secret_or_signature']); exit; }
$calc = $algo . '=' . hash_hmac($algo, $raw, $secret);
if ($debug) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$ts] calc=$calc recv=$sig", FILE_APPEND);
}
if (!hash_equals($calc, $sig)) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'invalid_signature']); exit; }
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($event !== 'push') { echo json_encode(['success'=>true,'ignored'=>true]); exit; }
$cmd = '/usr/local/bin/deploy_staging';
$out = [];
$code = 0;
exec('bash -lc ' . escapeshellarg($cmd) . ' 2>&1', $out, $code);
if ($debug) {
    $ts = date('Y-m-d H:i:s');
    $snippet = implode("\n", array_slice($out, -10));
    file_put_contents($logFile, "[$ts] exec_code=$code\n$snippet\n", FILE_APPEND);
}
echo json_encode(['success'=>($code===0),'code'=>$code,'output'=>implode("\n",$out)]);
