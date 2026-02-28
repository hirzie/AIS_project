<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

// Helper functions for Settings
function saveSetting($pdo, $key, $value) {
    // Check if key exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $stmt = $pdo->prepare("UPDATE core_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}

function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Load Google Config
    $googleConfigPath = '../config/google_calendar.php';
    $googleConfig = file_exists($googleConfigPath) ? require $googleConfigPath : null;

    // Ensure required tables exist (safe for first-run on test environment)
    function ensureAgendaSchema($pdo) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS acad_school_agenda (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    start_date DATETIME NOT NULL,
                    end_date DATETIME NOT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    type ENUM('ACADEMIC','HOLIDAY','EVENT','MEETING') DEFAULT 'EVENT',
                    google_event_id VARCHAR(128) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_start (start_date),
                    INDEX idx_end (end_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) { /* ignore create error to prevent hard fail */ }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS core_settings (setting_key varchar(50) NOT NULL, setting_value text, PRIMARY KEY (setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\Throwable $e) { /* ignore */ }
        try {
            $cols = $pdo->query("DESCRIBE acad_school_agenda")->fetchAll(PDO::FETCH_COLUMN, 0);
            if ($cols && !in_array('google_event_id', $cols)) {
                $pdo->exec("ALTER TABLE acad_school_agenda ADD COLUMN google_event_id VARCHAR(128) DEFAULT NULL");
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Google cache table for background sync
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS acad_school_agenda_google_cache (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    google_event_id VARCHAR(128) NOT NULL,
                    calendar_id VARCHAR(255) NOT NULL,
                    unit_code VARCHAR(64) DEFAULT 'all',
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    location VARCHAR(255) DEFAULT NULL,
                    start_date DATETIME NOT NULL,
                    end_date DATETIME NOT NULL,
                    color VARCHAR(16) DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_event (google_event_id, calendar_id, unit_code),
                    INDEX idx_range_start (start_date),
                    INDEX idx_range_end (end_date),
                    INDEX idx_unit (unit_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) { /* ignore */ }
    }
    ensureAgendaSchema($pdo);

    // --- Helpers for Google Calendar ---
    function getCalendarIdForUnit($pdo, $unitCode) {
        $calendarId = null;
        try {
            $mapJson = getSetting($pdo, 'google_calendar_map');
            if ($mapJson) {
                $map = json_decode($mapJson, true);
                if (is_array($map) && $unitCode && $unitCode !== 'all') {
                    $key = strtoupper($unitCode);
                    if (isset($map[$key]) && trim($map[$key]) !== '') {
                        $calendarId = $map[$key];
                    }
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        if (!$calendarId || trim($calendarId) === '') {
            $calendarId = getSetting($pdo, 'google_calendar_id'); // legacy global default
        }
        if (!$calendarId || trim($calendarId) === '') $calendarId = 'primary';
        return $calendarId;
    }
    function refreshGoogleAccessToken($pdo, $googleConfig) {
        try {
            $refresh = getSetting($pdo, 'google_calendar_refresh_token');
            if (!$refresh) return null;
            $postFields = http_build_query([
                'client_id' => $googleConfig['client_id'],
                'client_secret' => $googleConfig['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh
            ]);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $postFields,
                    'timeout' => 15
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
            ]);
            $resp = @file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
            if ($resp === false) return null;
            $j = json_decode($resp, true);
            if (!empty($j['access_token'])) {
                saveSetting($pdo, 'google_calendar_token', $j['access_token']);
                return $j['access_token'];
            }
        } catch (\Throwable $e) { /* ignore */ }
        return null;
    }
    function getCalendarColorsMap($pdo, $googleConfig) {
        $map = [];
        try {
            $json = getSetting($pdo, 'google_calendar_colors');
            if ($json) {
                $tmp = json_decode($json, true);
                if (is_array($tmp)) $map = $tmp;
            }
            if (!$map || !is_array($map)) $map = [];
            $token = getSetting($pdo, 'google_calendar_token');
            if (!$token) return $map;
            if (count($map) === 0) {
                $url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Authorization: Bearer " . $token . "\r\n",
                        'timeout' => 20,
                        'ignore_errors' => true
                    ],
                    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                ]);
                $resp = @file_get_contents($url, false, $ctx);
                $code = 0;
                if (isset($http_response_header) && is_array($http_response_header)) {
                    foreach ($http_response_header as $hdr) {
                        if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
                    }
                }
                if ($resp === false || $code === 401) {
                    $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
                    if ($newTok) {
                        $ctx = stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => "Authorization: Bearer " . $newTok . "\r\n",
                                'timeout' => 20,
                                'ignore_errors' => true
                            ],
                            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                        ]);
                        $resp = @file_get_contents($url, false, $ctx);
                    }
                }
                if ($resp !== false) {
                    $j = json_decode($resp, true);
                    $items = is_array($j['items'] ?? null) ? $j['items'] : [];
                    foreach ($items as $it) {
                        $cid = $it['id'] ?? null;
                        $bg = $it['backgroundColor'] ?? null;
                        if ($cid && $bg) {
                            $map[$cid] = $bg;
                            if (!empty($it['primary'])) {
                                $map['primary'] = $bg;
                            }
                        }
                    }
                    if (count($map) > 0) saveSetting($pdo, 'google_calendar_colors', json_encode($map));
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $map;
    }
    function getCalendarBgColor($pdo, $googleConfig, $calendarId) {
        $map = getCalendarColorsMap($pdo, $googleConfig);
        if (!$calendarId) return null;
        return $map[$calendarId] ?? null;
    }
    function getEventColorsMap($pdo, $googleConfig) {
        $map = [];
        try {
            $json = getSetting($pdo, 'google_event_colors');
            if ($json) {
                $tmp = json_decode($json, true);
                if (is_array($tmp)) $map = $tmp;
            }
            if (!$map || !is_array($map)) $map = [];
            $token = getSetting($pdo, 'google_calendar_token');
            if (!$token) return $map;
            if (count($map) === 0) {
                $url = 'https://www.googleapis.com/calendar/v3/colors';
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Authorization: Bearer " . $token . "\r\n",
                        'timeout' => 20,
                        'ignore_errors' => true
                    ],
                    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                ]);
                $resp = @file_get_contents($url, false, $ctx);
                $code = 0;
                if (isset($http_response_header) && is_array($http_response_header)) {
                    foreach ($http_response_header as $hdr) {
                        if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
                    }
                }
                if ($resp === false || $code === 401) {
                    $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
                    if ($newTok) {
                        $ctx = stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => "Authorization: Bearer " . $newTok . "\r\n",
                                'timeout' => 20,
                                'ignore_errors' => true
                            ],
                            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                        ]);
                        $resp = @file_get_contents($url, false, $ctx);
                    }
                }
                if ($resp !== false) {
                    $j = json_decode($resp, true);
                    $evColors = is_array($j['event'] ?? null) ? $j['event'] : [];
                    foreach ($evColors as $id => $def) {
                        $bg = $def['background'] ?? null;
                        if ($bg) $map[$id] = $bg;
                    }
                    if (count($map) > 0) saveSetting($pdo, 'google_event_colors', json_encode($map));
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $map;
    }
    function fetchGoogleEvents($pdo, $googleConfig, $start, $end, $unitCode = null) {
        $out = [];
        try {
            $token = getSetting($pdo, 'google_calendar_token');
            if (!$token) return [];
            $calendarId = getCalendarIdForUnit($pdo, $unitCode);
            $calendarColor = getCalendarBgColor($pdo, $googleConfig, $calendarId);
            $eventColors = getEventColorsMap($pdo, $googleConfig);
            $timeMin = date('c', strtotime($start . ' 00:00:00'));
            $timeMax = date('c', strtotime($end . ' 23:59:59'));
            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events?' . http_build_query([
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => 'true',
                'orderBy' => 'startTime'
            ]);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer " . $token . "\r\n",
                    'timeout' => 20,
                    'ignore_errors' => true
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $code = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $hdr) {
                    if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
                }
            }
            if ($resp === false || $code === 401) {
                // Try refresh token then retry once
                $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
                if ($newTok) {
                    $ctx = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "Authorization: Bearer " . $newTok . "\r\n",
                            'timeout' => 20,
                            'ignore_errors' => true
                        ],
                        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                    ]);
                    $resp = @file_get_contents($url, false, $ctx);
                }
            }
            if ($resp === false) return [];
            $json = json_decode($resp, true);
            $items = is_array($json['items'] ?? null) ? $json['items'] : [];
            $GLOBALS['__GOOGLE_DEBUG'] = [
                'token_present' => !!$token,
                'calendar_id' => $calendarId,
                'http_code' => $code,
                'items_count' => count($items),
                'time_min' => $timeMin,
                'time_max' => $timeMax,
                'unit' => $unitCode,
                'calendar_color' => $calendarColor
            ];
            foreach ($items as $ev) {
                $id = $ev['id'] ?? null;
                $summary = $ev['summary'] ?? '(Tanpa Judul)';
                $desc = $ev['description'] ?? '';
                $loc = $ev['location'] ?? '';
                $st = $ev['start'] ?? [];
                $en = $ev['end'] ?? [];
                $startDt = $st['dateTime'] ?? (($st['date'] ?? null) ? ($st['date'] . ' 00:00:00') : null);
                if (isset($en['dateTime'])) {
                    $endDt = $en['dateTime'];
                } elseif (isset($en['date'])) {
                    $endDt = date('Y-m-d 23:59:59', strtotime($en['date'] . ' -1 day'));
                } else {
                    $endDt = null;
                }
                $colorId = $ev['colorId'] ?? null;
                $evColor = $calendarColor;
                if ($colorId && isset($eventColors[$colorId])) $evColor = $eventColors[$colorId];
                if (!$startDt) continue;
                if (!$endDt) $endDt = $startDt;
                $out[] = [
                    'id' => 'google:' . $id,
                    'title' => $summary,
                    'description' => $desc,
                    'start_date' => str_replace('T', ' ', substr($startDt, 0, 19)),
                    'end_date' => str_replace('T', ' ', substr($endDt, 0, 19)),
                    'location' => $loc,
                    'type' => 'EVENT',
                    'color' => $evColor
                ];
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $out;
    }
    function findGoogleEventIdByTitleAndTime($pdo, $googleConfig, $title, $startDate, $endDate, $unitCode = null) {
        $token = getSetting($pdo, 'google_calendar_token');
        if (!$token) return null;
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $timeMin = date('c', strtotime($startDate));
        $timeMax = date('c', strtotime($endDate));
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events?' . http_build_query([
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'q' => $title
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $token . "\r\n",
                'timeout' => 20,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
            }
        }
        if ($resp === false || $code === 401) {
            $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
            if (!$newTok) return null;
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer " . $newTok . "\r\n",
                    'timeout' => 20,
                    'ignore_errors' => true
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp === false) return null;
        }
        $json = json_decode($resp, true);
        $items = is_array($json['items'] ?? null) ? $json['items'] : [];
        foreach ($items as $ev) {
            $summary = $ev['summary'] ?? '';
            $st = $ev['start'] ?? [];
            $en = $ev['end'] ?? [];
            $startDt = $st['dateTime'] ?? (($st['date'] ?? null) ? ($st['date'] . ' 00:00:00') : null);
            if (isset($en['dateTime'])) {
                $endDt = $en['dateTime'];
            } elseif (isset($en['date'])) {
                $endDt = date('Y-m-d 23:59:59', strtotime($en['date'] . ' -1 day'));
            } else {
                $endDt = null;
            }
            if ($startDt) $startDt = str_replace('T', ' ', substr($startDt, 0, 19));
            if ($endDt) $endDt = str_replace('T', ' ', substr($endDt, 0, 19));
            if ($summary === $title && $startDt === $startDate && $endDt === $endDate) {
                return $ev['id'] ?? null;
            }
        }
        return null;
    }
    function createGoogleEvent($pdo, $googleConfig, $data, $unitCode = null) {
        $token = getSetting($pdo, 'google_calendar_token');
        if (!$token) return ['success' => false, 'error' => 'No Google token'];
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $payload = [
            'summary' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? '',
            'start' => ['dateTime' => date('c', strtotime($data['start_date']))],
            'end' => ['dateTime' => date('c', strtotime($data['end_date']))]
        ];
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events';
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $token . "\r\nContent-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 20,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
            }
        }
        if ($resp === false || $code === 401) {
            $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
            if (!$newTok) return ['success' => false, 'error' => 'Unauthorized'];
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Authorization: Bearer " . $newTok . "\r\nContent-Type: application/json\r\n",
                    'content' => json_encode($payload),
                    'timeout' => 20,
                    'ignore_errors' => true
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
        }
        if ($resp === false) return ['success' => false, 'error' => 'Request failed'];
        $json = json_decode($resp, true);
        if (!empty($json['id'])) return ['success' => true, 'id' => $json['id']];
        return ['success' => false, 'error' => $resp];
    }
    function updateGoogleEvent($pdo, $googleConfig, $eventId, $data, $unitCode = null) {
        $token = getSetting($pdo, 'google_calendar_token');
        if (!$token) return ['success' => false, 'error' => 'No Google token'];
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $payload = [
            'summary' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? '',
            'start' => ['dateTime' => date('c', strtotime($data['start_date']))],
            'end' => ['dateTime' => date('c', strtotime($data['end_date']))]
        ];
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events/' . urlencode($eventId);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => "Authorization: Bearer " . $token . "\r\nContent-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 20,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
            }
        }
        if ($resp === false || $code >= 400) return ['success' => false, 'error' => 'Request failed'];
        $json = json_decode($resp, true);
        if (!empty($json['id'])) return ['success' => true, 'id' => $json['id']];
        return ['success' => false, 'error' => $resp];
    }
    function deleteGoogleEvent($pdo, $googleConfig, $eventId, $unitCode = null) {
        $token = getSetting($pdo, 'google_calendar_token');
        if (!$token) return ['success' => false, 'error' => 'No Google token'];
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events/' . urlencode($eventId);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => "Authorization: Bearer " . $token . "\r\n",
                'timeout' => 20,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
            }
        }
        if ($resp === false || ($code >= 400 && $code !== 404)) return ['success' => false, 'error' => 'Request failed'];
        return ['success' => true];
    }
    // 1. GET AGENDA LIST
    if ($action == 'get_agenda' && $method == 'GET') {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $startFull = $start . ' 00:00:00';
        $endFull = $end . ' 23:59:59';
        $unitCode = $_GET['unit'] ?? 'all';
        
        $events = [];
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM acad_school_agenda 
                WHERE 
                    DATE(start_date) <= ?
                AND DATE(end_date)   >= ?
                ORDER BY start_date ASC
            ");
            $stmt->execute([$end, $start]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $events = [];
            $GLOBALS['__LOCAL_ERR'] = $e->getMessage();
        }
        // Merge Google events from local cache (no live fetch to avoid delay)
        $googleEvents = [];
        try {
            $sqlG = "
                SELECT google_event_id, title, description, location, start_date, end_date, color 
                FROM acad_school_agenda_google_cache
                WHERE ";
            $params = [];
            if (strtolower($unitCode) === 'all') {
                $sqlG .= "1=1";
            } else {
                $sqlG .= "unit_code = ?";
                $params[] = $unitCode;
            }
            $sqlG .= " AND DATE(start_date) <= ? AND DATE(end_date) >= ? ORDER BY start_date ASC";
            $params = array_merge($params, [$end, $start]);
            $stmtG = $pdo->prepare($sqlG);
            $stmtG->execute($params);
            $rows = $stmtG->fetchAll(PDO::FETCH_ASSOC);
            $uMap = [];
            foreach ($rows as $r) {
                $gid = $r['google_event_id'];
                if (!isset($uMap[$gid])) {
                    $uMap[$gid] = $r;
                } else {
                    if ((!$uMap[$gid]['color'] || $uMap[$gid]['color'] === '') && ($r['color'] ?? '') !== '') {
                        $uMap[$gid] = $r;
                    }
                }
            }
            foreach (array_values($uMap) as $r) {
                $googleEvents[] = [
                    'id' => 'google:' . $r['google_event_id'],
                    'title' => $r['title'],
                    'description' => $r['description'],
                    'start_date' => $r['start_date'],
                    'end_date' => $r['end_date'],
                    'location' => $r['location'],
                    'type' => 'EVENT',
                    'color' => $r['color'],
                    'calendar_id' => $r['calendar_id']
                ];
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Inject color to local events from cache by google_event_id
        try {
            $localGeids = [];
            foreach ($events as $e) {
                if (!empty($e['google_event_id'])) {
                    $localGeids[$e['google_event_id']] = true;
                }
            }
            if (!empty($localGeids)) {
                $placeholders = implode(',', array_fill(0, count($localGeids), '?'));
                $stmtC = $pdo->prepare("SELECT google_event_id, color FROM acad_school_agenda_google_cache WHERE google_event_id IN ($placeholders)");
                $stmtC->execute(array_keys($localGeids));
                $colorRows = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                $colorMap = [];
                foreach ($colorRows as $cr) {
                    $colorMap[$cr['google_event_id']] = $cr['color'] ?? null;
                }
                foreach ($events as &$e) {
                    if (!empty($e['google_event_id']) && isset($colorMap[$e['google_event_id']]) && $colorMap[$e['google_event_id']]) {
                        $e['color'] = $colorMap[$e['google_event_id']];
                    }
                }
                unset($e);
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Deduplicate: remove Google items that already exist as local linked events
        try {
            if (!empty($localGeids)) {
                $filtered = [];
                foreach ($googleEvents as $g) {
                    $gid = $g['id'] ?? '';
                    if (strpos($gid, 'google:') === 0) $gid = substr($gid, 7);
                    if (empty($localGeids[$gid])) {
                        $filtered[] = $g;
                    }
                }
                $googleEvents = $filtered;
            }
        } catch (\Throwable $e) { /* ignore */ }
        $merged = array_merge($events ?: [], $googleEvents ?: []);
        $debug = [];
        if (isset($_GET['debug'])) {
            $dbName = '';
            try { $dbName = $pdo->query('select database()')->fetchColumn(); } catch (\Throwable $e) {}
            $debug = [
                'db' => $dbName,
                'range' => ['start' => $startFull, 'end' => $endFull],
                'local_count' => is_array($events) ? count($events) : 0,
                'google_cache_count' => is_array($googleEvents) ? count($googleEvents) : 0,
                'local_error' => $GLOBALS['__LOCAL_ERR'] ?? null,
                'unit' => $unitCode
            ];
        }
        echo json_encode(['success' => true, 'data' => $merged, 'debug' => $debug]);
    }

    // 2. SAVE AGENDA (Create/Update)
    elseif ($action == 'save_agenda' && $method == 'POST') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $location = $data['location'] ?? '';
        $type = $data['type'] ?? 'EVENT';
        $id = $data['id'] ?? null;
        $unitCode = $data['unit'] ?? ($_GET['unit'] ?? 'all');
        $googleEventId = $data['google_event_id'] ?? null;
        
        $push_to_google = !empty($data['push_to_google']);
            // Dedup guard: prevent duplicate create with same title & exact time
            try {
                if (!$id) {
                    $stmtD = $pdo->prepare("SELECT id, google_event_id FROM acad_school_agenda WHERE title=? AND start_date=? AND end_date=? LIMIT 1");
                    $stmtD->execute([$title, $start_date, $end_date]);
                    $dup = $stmtD->fetch(PDO::FETCH_ASSOC);
                    if ($dup && isset($dup['id'])) {
                        $existingId = (int)$dup['id'];
                        $googleRes = null;
                        if ($push_to_google && $googleConfig && empty($dup['google_event_id'])) {
                            $foundId = findGoogleEventIdByTitleAndTime($pdo, $googleConfig, $title, $start_date, $end_date, $unitCode);
                            if ($foundId) {
                                $stmtU = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                                $stmtU->execute([$foundId, $existingId]);
                                $googleRes = ['success' => true, 'id' => $foundId, 'adopted' => true];
                            } else {
                                $googleRes = createGoogleEvent($pdo, $googleConfig, $data, $unitCode);
                                if (!empty($googleRes['id'])) {
                                    $stmtU = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                                    $stmtU->execute([$googleRes['id'], $existingId]);
                                }
                            }
                        }
                        echo json_encode(['success' => true, 'message' => 'Agenda sudah ada', 'id' => $existingId, 'duplicate_guard' => true, 'google' => $googleRes]);
                        exit;
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        if ($id) {
            try {
                $stmtCur = $pdo->prepare("SELECT google_event_id FROM acad_school_agenda WHERE id=?");
                $stmtCur->execute([$id]);
                $curGeid = $stmtCur->fetchColumn();
                if ($curGeid) $googleEventId = $curGeid;
            } catch (\Throwable $e) {}
            $stmt = $pdo->prepare("UPDATE acad_school_agenda SET title=?, description=?, start_date=?, end_date=?, location=?, type=? WHERE id=?");
            $stmt->execute([$title, $description, $start_date, $end_date, $location, $type, $id]);
            $googleRes = null;
            if ($push_to_google && $googleConfig) {
                if ($googleEventId) {
                    $googleRes = updateGoogleEvent($pdo, $googleConfig, $googleEventId, $data, $unitCode);
                } else {
                    $foundId = findGoogleEventIdByTitleAndTime($pdo, $googleConfig, $title, $start_date, $end_date, $unitCode);
                    if ($foundId) {
                        $stmt = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                        $stmt->execute([$foundId, $id]);
                        $googleRes = updateGoogleEvent($pdo, $googleConfig, $foundId, $data, $unitCode);
                    } else {
                        $googleRes = createGoogleEvent($pdo, $googleConfig, $data, $unitCode);
                        if (!empty($googleRes['id'])) {
                            $stmt = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                            $stmt->execute([$googleRes['id'], $id]);
                        }
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Agenda berhasil diperbarui', 'google' => $googleRes]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO acad_school_agenda (title, description, start_date, end_date, location, type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $start_date, $end_date, $location, $type]);
            $newId = $pdo->lastInsertId();
            $googleRes = null;
            if ($push_to_google && $googleConfig) {
                $foundId = findGoogleEventIdByTitleAndTime($pdo, $googleConfig, $title, $start_date, $end_date, $unitCode);
                if ($foundId) {
                    $stmt = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                    $stmt->execute([$foundId, $newId]);
                    $googleRes = updateGoogleEvent($pdo, $googleConfig, $foundId, $data, $unitCode);
                } else {
                    $googleRes = createGoogleEvent($pdo, $googleConfig, $data, $unitCode);
                    if (!empty($googleRes['id'])) {
                        $stmt = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id=? WHERE id=?");
                        $stmt->execute([$googleRes['id'], $newId]);
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Agenda berhasil ditambahkan', 'google' => $googleRes, 'id' => $newId]);
        }
    }


    // 3. DELETE AGENDA
    elseif ($action == 'delete_agenda' && $method == 'POST') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $unitCode = $data['unit'] ?? 'all';
        $googleRes = null;
        try {
            $stmt = $pdo->prepare("SELECT google_event_id FROM acad_school_agenda WHERE id=?");
            $stmt->execute([$id]);
            $geid = $stmt->fetchColumn();
            if ($geid && $googleConfig) {
                $googleRes = deleteGoogleEvent($pdo, $googleConfig, $geid, $unitCode);
            }
        } catch (\Throwable $e) {}
        
        $stmt = $pdo->prepare("DELETE FROM acad_school_agenda WHERE id=?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Agenda berhasil dihapus', 'google' => $googleRes]);
    }
    
    // 4. SYNC TO GOOGLE (Initiate)
    elseif ($action == 'sync_google' && $method == 'POST') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        if (!$googleConfig) {
            throw new Exception('Konfigurasi Google Calendar belum ada. Hubungi Admin.');
        }

        $token = getSetting($pdo, 'google_calendar_token');
        
        if (!$token) {
            // Generate Auth URL
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $googleConfig['client_id'],
                'redirect_uri' => $googleConfig['redirect_uri'],
                'response_type' => 'code',
                'scope' => $googleConfig['scopes'],
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'state' => 'school_agenda'
            ]);
            
            echo json_encode([
                'success' => true, 
                'auth_required' => true,
                'auth_url' => $authUrl,
                'message' => 'Redirecting to Google...'
            ]);
            exit;
        }

        // TODO: Implement actual sync logic here if token exists
        // For now, we assume token check is enough to say "Connected"
        echo json_encode([
            'success' => true, 
            'auth_required' => false,
            'message' => 'Akun Google sudah terhubung. Sync otomatis berjalan saat menyimpan agenda.'
        ]);
    }

    // 5. GOOGLE CALLBACK (Handle Redirect)
    elseif ($action == 'google_callback') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            die('Unauthorized');
        }
        if (!$googleConfig) die('Config missing');
        
        $code = $_GET['code'] ?? '';
        if (!$code) die('No code provided');

        // Exchange code for token
        $postFields = http_build_query([
            'code' => $code,
            'client_id' => $googleConfig['client_id'],
            'client_secret' => $googleConfig['client_secret'],
            'redirect_uri' => $googleConfig['redirect_uri'],
            'grant_type' => 'authorization_code'
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        $response = @file_get_contents('https://oauth2.googleapis.com/token', false, $context);
        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) {
                    $httpCode = (int)$m[1];
                }
            }
        }
        if ($response === false || $httpCode >= 400) {
            die('Error fetching token: HTTP ' . $httpCode);
        }

        $tokenData = json_decode($response, true);
        
        if (isset($tokenData['access_token'])) {
            saveSetting($pdo, 'google_calendar_token', $tokenData['access_token']);
            if (isset($tokenData['refresh_token'])) {
                saveSetting($pdo, 'google_calendar_refresh_token', $tokenData['refresh_token']);
            }
            
            // Redirect back to agenda
            header('Location: ../modules/academic/school_agenda.php?status=synced');
            exit;
        } else {
            die('Error fetching token: ' . $response);
        }
    }
    elseif ($action == 'get_google_settings' && $method == 'GET') {
        $token = getSetting($pdo, 'google_calendar_token');
        $unitCode = $_GET['unit'] ?? 'all';
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        // Also return full map for advanced UI if needed
        $map = getSetting($pdo, 'google_calendar_map');
        echo json_encode(['success' => true, 'data' => ['token_present' => !!$token, 'calendar_id' => $calendarId ?: '', 'map' => $map ? json_decode($map, true) : []]]);
    }
    elseif ($action == 'save_google_settings' && $method == 'POST') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $cid = trim($data['calendar_id'] ?? '');
        $unitCode = strtoupper(trim($data['unit'] ?? ''));
        if ($unitCode === '') {
            // fallback: save global
            saveSetting($pdo, 'google_calendar_id', $cid);
            echo json_encode(['success' => true, 'message' => 'Google Calendar ID global disimpan']);
        } else {
            $mapJson = getSetting($pdo, 'google_calendar_map');
            $map = [];
            if ($mapJson) { $tmp = json_decode($mapJson, true); if (is_array($tmp)) $map = $tmp; }
            $map[$unitCode] = $cid;
            saveSetting($pdo, 'google_calendar_map', json_encode($map));
            echo json_encode(['success' => true, 'message' => 'Google Calendar ID untuk unit ' . $unitCode . ' disimpan', 'map' => $map]);
        }
    }
    // 7. SYNC GOOGLE TO CACHE (background-friendly)
    elseif ($action == 'sync_google_cache') {
        set_time_limit(120); // Extend execution time to 2 minutes
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $start = $_GET['start'] ?? ($_POST['start'] ?? date('Y-m-01'));
        $end = $_GET['end'] ?? ($_POST['end'] ?? date('Y-m-t'));
        $unitCode = $_GET['unit'] ?? ($_POST['unit'] ?? 'all');
        $force = isset($_GET['force']) ? ($_GET['force'] == '1') : (isset($_POST['force']) ? ($_POST['force'] == '1') : false);
        if (!$googleConfig) {
            echo json_encode(['success' => false, 'message' => 'Konfigurasi Google tidak ada']);
            exit;
        }
        // Rate limit: skip if last sync < 60s ago unless force
        try {
            $mapJson = getSetting($pdo, 'google_sync_last');
            $map = $mapJson ? json_decode($mapJson, true) : [];
            $last = is_array($map) ? ($map[strtoupper($unitCode)] ?? null) : null;
            if (!$force && $last) {
                $lastTs = strtotime($last);
                if ($lastTs && (time() - $lastTs) < 60) {
                    echo json_encode(['success' => true, 'message' => 'Skip: recently synced', 'skipped' => true, 'unit' => $unitCode]);
                    return;
                }
            }
        } catch (\Throwable $e) {}
        $events = fetchGoogleEvents($pdo, $googleConfig, $start, $end, $unitCode);
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $ins = $pdo->prepare("
            INSERT INTO acad_school_agenda_google_cache 
            (google_event_id, calendar_id, unit_code, title, description, location, start_date, end_date, color)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                title=VALUES(title),
                description=VALUES(description),
                location=VALUES(location),
                start_date=VALUES(start_date),
                end_date=VALUES(end_date),
                color=VALUES(color),
                updated_at=CURRENT_TIMESTAMP
        ");
        $count = 0;
        foreach ($events as $ev) {
            $gid = $ev['id'] ?? '';
            if (strpos($gid, 'google:') === 0) $gid = substr($gid, 7);
            $ins->execute([
                $gid,
                $calendarId,
                $unitCode,
                $ev['title'] ?? '',
                $ev['description'] ?? '',
                $ev['location'] ?? '',
                $ev['start_date'] ?? '',
                $ev['end_date'] ?? '',
                $ev['color'] ?? null
            ]);
            $count++;
        }
        // Record last sync time per unit
        try {
            $mapJson = getSetting($pdo, 'google_sync_last');
            $map = $mapJson ? json_decode($mapJson, true) : [];
            if (!is_array($map)) $map = [];
            $map[strtoupper($unitCode)] = date('c');
            saveSetting($pdo, 'google_sync_last', json_encode($map));
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Sync cache selesai', 'updated' => $count, 'unit' => $unitCode]);
    }
    elseif ($action == 'dedup_google') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        if (!$googleConfig) {
            echo json_encode(['success' => false, 'message' => 'Konfigurasi Google tidak ada']);
            exit;
        }
        $start = $_GET['start'] ?? ($_POST['start'] ?? date('Y-m-01'));
        $end = $_GET['end'] ?? ($_POST['end'] ?? date('Y-m-t'));
        $unitCode = $_GET['unit'] ?? ($_POST['unit'] ?? 'all');
        $doDelete = isset($_GET['delete']) ? ($_GET['delete'] == '1') : (isset($_POST['delete']) ? ($_POST['delete'] == '1') : false);
        $token = getSetting($pdo, 'google_calendar_token');
        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Token Google tidak ada']);
            exit;
        }
        $calendarId = getCalendarIdForUnit($pdo, $unitCode);
        $timeMin = date('c', strtotime($start . ' 00:00:00'));
        $timeMax = date('c', strtotime($end . ' 23:59:59'));
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events?' . http_build_query([
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $token . "\r\n",
                'timeout' => 20,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $hdr, $m)) { $code = (int)$m[1]; }
            }
        }
        if ($resp === false || $code === 401) {
            $newTok = refreshGoogleAccessToken($pdo, $googleConfig);
            if ($newTok) {
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "Authorization: Bearer " . $newTok . "\r\n",
                        'timeout' => 20,
                        'ignore_errors' => true
                    ],
                    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
                ]);
                $resp = @file_get_contents($url, false, $ctx);
            }
        }
        if ($resp === false) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil event dari Google']);
            exit;
        }
        $json = json_decode($resp, true);
        $items = is_array($json['items'] ?? null) ? $json['items'] : [];
        $groups = [];
        foreach ($items as $ev) {
            $summary = $ev['summary'] ?? '';
            $st = $ev['start'] ?? [];
            $en = $ev['end'] ?? [];
            $startDt = $st['dateTime'] ?? (($st['date'] ?? null) ? ($st['date'] . ' 00:00:00') : null);
            if (isset($en['dateTime'])) {
                $endDt = $en['dateTime'];
            } elseif (isset($en['date'])) {
                $endDt = date('Y-m-d 23:59:59', strtotime($en['date'] . ' -1 day'));
            } else {
                $endDt = null;
            }
            if ($startDt) $startDt = str_replace('T', ' ', substr($startDt, 0, 19));
            if ($endDt) $endDt = str_replace('T', ' ', substr($endDt, 0, 19));
            if (!$startDt || !$endDt) continue;
            $key = $summary . '|' . $startDt . '|' . $endDt;
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][] = ['id' => $ev['id'] ?? null, 'summary' => $summary, 'start' => $startDt, 'end' => $endDt];
        }
        $dups = [];
        foreach ($groups as $k => $arr) {
            if (count($arr) > 1) $dups[$k] = $arr;
        }
        $deleted = [];
        if ($doDelete) {
            foreach ($dups as $k => $arr) {
                for ($i = 1; $i < count($arr); $i++) {
                    $eid = $arr[$i]['id'];
                    if ($eid) {
                        $res = deleteGoogleEvent($pdo, $googleConfig, $eid, $unitCode);
                        $deleted[] = ['id' => $eid, 'key' => $k, 'success' => $res['success'] ?? false];
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'unit' => $unitCode, 'range' => ['start' => $start, 'end' => $end], 'duplicates' => $dups, 'deleted' => $deleted]);
    }
    // 6. UNSYNC GOOGLE EVENT (remove local mapping only)
    elseif ($action == 'unsync_google' && $method == 'POST') {
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), ['SUPERADMIN','ADMIN','ACADEMIC'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID agenda tidak valid']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE acad_school_agenda SET google_event_id = NULL WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Sinkronisasi dengan Google diputus (event Google tidak dihapus)']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memutus sinkronisasi']);
        }
    }
    // 8. ANNOUNCEMENTS: LIST
    elseif ($action == 'get_announcements' && $method == 'GET') {
        try {
            $json = getSetting($pdo, 'dashboard_announcements');
            $arr = $json ? json_decode($json, true) : [];
            if (!is_array($arr)) $arr = [];
            $includeExpired = isset($_GET['include_expired']) && ($_GET['include_expired'] == '1');
            $today = date('Y-m-d');
            $arr = array_values(array_filter($arr, function($a) use ($includeExpired, $today) {
                if ($includeExpired) return true;
                $exp = trim($a['expires_at'] ?? '');
                if ($exp === '') return true;
                return $exp >= $today;
            }));
            usort($arr, function($a, $b) {
                $ta = strtotime($a['created_at'] ?? '') ?: 0;
                $tb = strtotime($b['created_at'] ?? '') ?: 0;
                return $tb <=> $ta;
            });
            echo json_encode(['success' => true, 'data' => $arr]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil pengumuman']);
        }
    }
    // 9. ANNOUNCEMENTS: SAVE (create or update)
    elseif ($action == 'save_announcement' && $method == 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!isset($_SESSION['user_id']) || !in_array($role, ['SUPERADMIN','ADMIN','MANAGERIAL','EXECUTIVE','PRINCIPAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = trim($data['id'] ?? '');
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $audience = trim($data['audience'] ?? 'ALL');
        $expiresAt = trim($data['expires_at'] ?? '');
        $overrideBy = trim($data['created_by'] ?? '');
        if ($title === '' || $content === '') {
            echo json_encode(['success' => false, 'message' => 'Judul dan isi pengumuman wajib']);
            exit;
        }
        try {
            $listJson = getSetting($pdo, 'dashboard_announcements');
            $list = $listJson ? json_decode($listJson, true) : [];
            if (!is_array($list)) $list = [];
            $createdBy = $overrideBy !== '' ? $overrideBy : ($_SESSION['username'] ?? 'SYSTEM');
            try {
                if ($overrideBy === '') {
                    $pid = $_SESSION['person_id'] ?? null;
                    if ($pid) {
                        $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
                        $st->execute([$pid]);
                        $nm = $st->fetchColumn();
                        if ($nm) $createdBy = $nm;
                    }
                }
            } catch (\Throwable $e) {}
            $now = date('Y-m-d H:i:s');
            $saved = null;
            if ($id !== '') {
                foreach ($list as &$it) {
                    if (($it['id'] ?? '') === $id) {
                        $it['title'] = $title;
                        $it['content'] = $content;
                        $it['audience'] = $audience;
                        $it['expires_at'] = $expiresAt;
                        $it['created_by'] = $createdBy;
                        $it['updated_at'] = $now;
                        $it['updated_by_user_id'] = $_SESSION['user_id'] ?? null;
                        $saved = $it;
                        break;
                    }
                }
                unset($it);
            } 
            if ($saved === null) {
                $item = [
                    'id' => ($id !== '' ? $id : ('ANN-' . time())),
                    'title' => $title,
                    'content' => $content,
                    'audience' => $audience,
                    'expires_at' => $expiresAt,
                    'created_by' => $createdBy,
                    'created_by_user_id' => $_SESSION['user_id'] ?? null,
                    'created_at' => $now
                ];
                $list[] = $item;
                $saved = $item;
            }
            saveSetting($pdo, 'dashboard_announcements', json_encode(array_values($list)));
            echo json_encode(['success' => true, 'message' => 'Pengumuman disimpan', 'data' => $saved]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pengumuman']);
        }
    }
    // 10. ANNOUNCEMENTS: DELETE
    elseif ($action == 'delete_announcement' && $method == 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!isset($_SESSION['user_id']) || !in_array($role, ['SUPERADMIN','ADMIN','MANAGERIAL','EXECUTIVE','PRINCIPAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = trim($data['id'] ?? '');
        if ($id === '') { echo json_encode(['success' => false, 'message' => 'ID wajib']); exit; }
        try {
            $listJson = getSetting($pdo, 'dashboard_announcements');
            $list = $listJson ? json_decode($listJson, true) : [];
            if (!is_array($list)) $list = [];
            $list = array_values(array_filter($list, function($it) use ($id) { return ($it['id'] ?? '') !== $id; }));
            saveSetting($pdo, 'dashboard_announcements', json_encode($list));
            echo json_encode(['success' => true, 'message' => 'Pengumuman dihapus']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengumuman']);
        }
    }

} catch (Exception $e) {
    if ($action == 'google_callback') {
        die('Error: ' . $e->getMessage());
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
