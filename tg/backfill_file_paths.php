<?php
// === ULTRA-FIXED BACKFILL FOR TELEGRAM VIDEOS ===
set_time_limit(0);
header('Content-Type: text/plain');

require __DIR__ . '/bootstrap.php';

$pharPath = __DIR__ . '/madeline.phar';
$sessionPath = __DIR__ . '/session.madeline';

if (!file_exists($pharPath) || !file_exists($sessionPath)) {
    die("❌ ERROR: MadelineProto not set up.\nRun /tg/mp_setup.php first.\n");
}

require_once $pharPath;

echo "=== ULTRA-FIXED Backfill Tool ===\n\n";

$pdo = db();
$MadelineProto = new \danog\MadelineProto\API($sessionPath);

// Get all videos
$stmt = $pdo->query("SELECT id, file_id, tg_chat_id, tg_message_id FROM videos WHERE file_path IS NULL OR file_path = ''");
$videos = $stmt->fetchAll();

echo "Found " . count($videos) . " videos needing file_path.\n\n";

$success = 0;
$failed = 0;

foreach ($videos as $video) {
    $id = $video['id'];
    $chat_id = $video['tg_chat_id'];
    $message_id = $video['tg_message_id'];
    
    echo "[Video ID: $id] Fetching from Telegram...\n";
    
    if (empty($chat_id) || empty($message_id)) {
        echo "  ✗ Missing chat_id or message_id in database\n\n";
        $failed++;
        continue;
    }
    
    try {
        // ✅ FIX: Use channels.getMessages instead
        $updates = $MadelineProto->channels->getMessages([
            'channel' => $chat_id,
            'id' => [(int)$message_id]
        ]);
        
        // Navigate to the actual message
        if (empty($updates['messages'][0])) {
            echo "  ✗ Message not found\n\n";
            $failed++;
            continue;
        }
        
        $message = $updates['messages'][0];
        
        // Check if message has video document
        if (empty($message['media']) || $message['media']['_'] !== 'messageMediaDocument') {
            echo "  ✗ No video document in message\n\n";
            $failed++;
            continue;
        }
        
        $document = $message['media']['document'];
        
        // Now get download info with the full document object
        $info = $MadelineProto->getDownloadInfo($document);
        
        if (!empty($info['url'])) {
            $url = $info['url'];
            
            // Update database
            $upd = $pdo->prepare("UPDATE videos SET file_path = ? WHERE id = ?");
            $upd->execute([$url, $id]);
            
            echo "  ✓ Updated with CDN URL\n";
            echo "  → " . substr($url, 0, 60) . "...\n";
            $success++;
        } else {
            echo "  ✗ No URL in download info\n";
            $failed++;
        }
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    echo "\n";
    usleep(200000); // 0.2 second delay
}

echo "\n=== COMPLETE ===\n";
echo "✓ Success: $success\n";
echo "✗ Failed: $failed\n";

if ($failed > 0) {
    echo "\n⚠️ TROUBLESHOOTING:\n";
    echo "1. Make sure you're logged into MadelineProto\n";
    echo "2. Check if the bot/user has access to the channel\n";
    echo "3. Verify chat_id is correct (should be negative for channels)\n";
}