<?php
// === WORKING TELEGRAM STREAMING (DIRECT PROXY) ===
require __DIR__ . '/bootstrap.php';

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('max_execution_time', '0');
set_time_limit(0);

// 1) Validate signed URL
$id  = (int)($_GET['id'] ?? 0);
$exp = (int)($_GET['exp'] ?? 0);
$sig = $_GET['sig'] ?? '';

if ($id < 1 || $exp < time()) {
    http_response_code(403);
    exit('Link expired');
}

$secret = cfg('hmac_secret');
$want = hash_hmac('sha256', $id . '.' . $exp, $secret);
if (!hash_equals($want, $sig)) {
    http_response_code(403);
    exit('Bad signature');
}

// 2) Load video from DB
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    exit('Video not found');
}

// 3) Initialize MadelineProto
$pharPath = __DIR__ . '/madeline.phar';
$sessionPath = __DIR__ . '/session.madeline';

if (!file_exists($pharPath) || !file_exists($sessionPath)) {
    http_response_code(502);
    exit('MadelineProto not configured');
}

require_once $pharPath;

try {
    $MadelineProto = new \danog\MadelineProto\API($sessionPath);
    
    $chat_id = $video['tg_chat_id'];
    $message_id = $video['tg_message_id'];
    
    if (empty($chat_id) || empty($message_id)) {
        http_response_code(502);
        exit('Missing chat/message ID');
    }
    
    // 4) Get the message with video
    $updates = $MadelineProto->channels->getMessages([
        'channel' => (int)$chat_id,
        'id' => [(int)$message_id]
    ]);
    
    if (empty($updates['messages'][0]['media']['document'])) {
        http_response_code(502);
        exit('Video not found in channel');
    }
    
    $document = $updates['messages'][0]['media']['document'];
    $filesize = $document['size'];
    
    // 5) Handle Range requests
    $range = $_SERVER['HTTP_RANGE'] ?? null;
    $start = 0;
    $end = $filesize - 1;
    
    if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
        $start = (int)$matches[1];
        if (!empty($matches[2])) {
            $end = min((int)$matches[2], $filesize - 1);
        }
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$filesize");
    } else {
        http_response_code(200);
    }
    
    // 6) Set streaming headers
    header('Content-Type: video/mp4');
    header('Content-Length: ' . ($end - $start + 1));
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=86400');
    header('X-Accel-Buffering: no');
    header('Access-Control-Allow-Origin: *');
    
    // Disable all buffering
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 7) Stream video directly from Telegram using MadelineProto
    // This downloads and streams simultaneously
    $MadelineProto->downloadToStream(
        $document,
        fopen('php://output', 'wb'),
        null,
        $start,
        $end + 1
    );
    
} catch (Exception $e) {
    http_response_code(502);
    exit('Streaming error: ' . $e->getMessage());
}

exit;