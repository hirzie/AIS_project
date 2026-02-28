<?php
header('Content-Type: application/json');
$secret = getenv('GH_WEBHOOK_SECRET') ?: '';
$allowedIps = array_filter(array_map('trim', explode(',', getenv('GH_ALLOWED_IPS') ?: '')));
$cfgPath = __DIR__ . '/config/webhook_secret.php';
if (is_file($cfgPath)) {
    $cfg = include $cfgPath;
    if (is_array($cfg)) {
        if (!$secret && isset($cfg['secret'])) { $secret = (string)$cfg['secret']; }
        if (!$allowedIps && isset($cfg['allowed_ips'])) {
            $allowedIps = array_filter(array_map('trim', is_array($cfg['allowed_ips']) ? $cfg['allowed_ips'] : explode(',', (string)$cfg['allowed_ips'])));
        }
    }
}
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if ($allowedIps && !in_array($remoteIp, $allowedIps, true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'ip_not_allowed']); exit; }
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (!$secret || !$sig) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'missing_secret_or_signature']); exit; }
$calc = 'sha256=' . hash_hmac('sha256', $raw, $secret);
if (!hash_equals($calc, $sig)) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'invalid_signature']); exit; }
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($event !== 'push') { echo json_encode(['success'=>true,'ignored'=>true]); exit; }
$cmd = '/usr/local/bin/deploy_staging';
$out = [];
$code = 0;
exec('bash -lc ' . escapeshellarg($cmd) . ' 2>&1', $out, $code);
echo json_encode(['success'=>($code===0),'code'=>$code,'output'=>implode("\n",$out)]);
