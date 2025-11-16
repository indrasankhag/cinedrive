<?php
// === COMPREHENSIVE DIAGNOSTIC TOOL ===
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/bootstrap.php';

$checks = [];

// 1. Check database connection
try {
    $pdo = db();
    $checks['database'] = ['status' => '✅', 'message' => 'Connected'];
} catch (Exception $e) {
    $checks['database'] = ['status' => '❌', 'message' => $e->getMessage()];
}

// 2. Check videos table structure
try {
    $stmt = $pdo->query("DESCRIBE videos");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    
    $required = ['id', 'file_id', 'file_path', 'tg_chat_id', 'tg_message_id'];
    $missing = array_diff($required, $columns);
    
    if (empty($missing)) {
        $checks['table_structure'] = ['status' => '✅', 'message' => 'All required columns present'];
    } else {
        $checks['table_structure'] = ['status' => '❌', 'message' => 'Missing columns: ' . implode(', ', $missing)];
    }
    
    $checks['table_columns'] = ['status' => 'ℹ️', 'message' => implode(', ', $columns)];
} catch (Exception $e) {
    $checks['table_structure'] = ['status' => '❌', 'message' => $e->getMessage()];
}

// 3. Check sample videos
try {
    $stmt = $pdo->query("SELECT id, file_id, file_path, tg_chat_id, tg_message_id FROM videos LIMIT 3");
    $videos = $stmt->fetchAll();
    
    if (count($videos) > 0) {
        $checks['sample_videos'] = ['status' => '✅', 'message' => 'Found ' . count($videos) . ' sample videos'];
        
        foreach ($videos as $i => $v) {
            $has_file_path = !empty($v['file_path']);
            $has_chat_info = !empty($v['tg_chat_id']) && !empty($v['tg_message_id']);
            
            $checks["video_$i"] = [
                'status' => ($has_file_path ? '✅' : ($has_chat_info ? '⚠️' : '❌')),
                'message' => sprintf(
                    "ID: %d | file_path: %s | chat_id: %s | msg_id: %s",
                    $v['id'],
                    $has_file_path ? 'YES' : 'NO',
                    $v['tg_chat_id'] ?? 'NULL',
                    $v['tg_message_id'] ?? 'NULL'
                )
            ];
        }
    } else {
        $checks['sample_videos'] = ['status' => '⚠️', 'message' => 'No videos in database'];
    }
} catch (Exception $e) {
    $checks['sample_videos'] = ['status' => '❌', 'message' => $e->getMessage()];
}

// 4. Check MadelineProto
$pharPath = __DIR__ . '/madeline.phar';
$sessionPath = __DIR__ . '/session.madeline';

if (file_exists($pharPath)) {
    $pharSize = filesize($pharPath);
    if ($pharSize > 1000000) { // > 1MB
        $checks['madeline_phar'] = ['status' => '✅', 'message' => 'Present (' . number_format($pharSize) . ' bytes)'];
    } else {
        $checks['madeline_phar'] = ['status' => '❌', 'message' => 'File too small (likely corrupted)'];
    }
} else {
    $checks['madeline_phar'] = ['status' => '❌', 'message' => 'Not found'];
}

if (file_exists($sessionPath)) {
    $checks['madeline_session'] = ['status' => '✅', 'message' => 'Session exists'];
} else {
    $checks['madeline_session'] = ['status' => '❌', 'message' => 'Not configured (run mp_setup.php)'];
}

// 5. Check configuration
$api_id = cfg('api_id');
$api_hash = cfg('api_hash');
$bot_token = cfg('bot_token');

$checks['api_credentials'] = [
    'status' => ($api_id && $api_hash ? '✅' : '❌'),
    'message' => ($api_id && $api_hash ? 'Configured' : 'Missing in config.php')
];

$checks['bot_token'] = [
    'status' => ($bot_token ? '✅' : '⚠️'),
    'message' => ($bot_token ? 'Configured' : 'Not set (optional)')
];

// 6. Test signed URL generation
try {
    $secret = cfg('hmac_secret');
    $base = cfg('base_url');
    $test_id = 1;
    $exp = time() + 3600;
    $sig = hash_hmac('sha256', $test_id . '.' . $exp, $secret);
    $test_url = "{$base}/tg/stream.php?id={$test_id}&exp={$exp}&sig={$sig}";
    
    $checks['url_generation'] = [
        'status' => '✅',
        'message' => 'Working'
    ];
    $checks['sample_url'] = [
        'status' => 'ℹ️',
        'message' => '<a href="' . htmlspecialchars($test_url) . '" target="_blank">Test Link</a>'
    ];
} catch (Exception $e) {
    $checks['url_generation'] = ['status' => '❌', 'message' => $e->getMessage()];
}

// Output
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostics</title>
    <style>
        body { font-family: monospace; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #58a6ff; border-bottom: 2px solid #21262d; padding-bottom: 10px; }
        .check { background: #161b22; border: 1px solid #30363d; padding: 12px; margin: 8px 0; border-radius: 6px; }
        .check-name { font-weight: bold; color: #58a6ff; margin-bottom: 4px; }
        .status { font-size: 20px; margin-right: 8px; }
        .message { color: #8b949e; }
        .success { color: #3fb950; }
        .warning { color: #d29922; }
        .error { color: #f85149; }
        a { color: #58a6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 CineDrive System Diagnostics</h1>
        
        <?php foreach ($checks as $name => $check): ?>
            <div class="check">
                <div class="check-name">
                    <span class="status"><?= $check['status'] ?></span>
                    <?= ucwords(str_replace('_', ' ', $name)) ?>
                </div>
                <div class="message"><?= $check['message'] ?></div>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #1c2128; border-radius: 6px;">
            <h3 style="margin-top: 0; color: #58a6ff;">🚀 Next Steps:</h3>
            <ol style="line-height: 1.8;">
                <li>If MadelineProto is not configured, run: <code>/tg/mp_setup.php</code></li>
                <li>If videos are missing file_path, run: <code>/tg/backfill_file_paths.php</code></li>
                <li>Upload fixed files: <code>stream.php</code> and <code>backfill_file_paths.php</code></li>
                <li>Test streaming: <code>/tg/test_link.php?id=1</code></li>
            </ol>
        </div>
    </div>
</body>
</html>