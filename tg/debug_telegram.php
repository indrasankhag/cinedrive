<?php
// === TELEGRAM MESSAGE INSPECTOR ===
set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/bootstrap.php';

$pharPath = __DIR__ . '/madeline.phar';
$sessionPath = __DIR__ . '/session.madeline';

if (!file_exists($pharPath) || !file_exists($sessionPath)) {
    die("❌ ERROR: MadelineProto not set up.");
}

require_once $pharPath;

echo "<pre style='background:#000;color:#0f0;padding:20px;font-family:monospace;'>";
echo "=== 🔍 Telegram Message Inspector ===\n\n";

$pdo = db();
$MadelineProto = new \danog\MadelineProto\API($sessionPath);

// Get first video
$stmt = $pdo->query("SELECT * FROM videos LIMIT 1");
$video = $stmt->fetch();

if (!$video) {
    die("❌ No videos in database\n");
}

echo "📊 Testing with Video ID: {$video['id']}\n";
echo "📱 Chat ID: {$video['tg_chat_id']}\n";
echo "💬 Message ID: {$video['tg_message_id']}\n";
echo "🆔 File ID: {$video['file_id']}\n\n";

echo "--- Testing Method 1: channels.getMessages ---\n";
try {
    $updates = $MadelineProto->channels->getMessages([
        'channel' => (int)$video['tg_chat_id'],
        'id' => [(int)$video['tg_message_id']]
    ]);
    
    echo "✅ Response received!\n\n";
    echo "📦 Full Response Structure:\n";
    echo json_encode($updates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if (!empty($updates['messages'][0])) {
        $msg = $updates['messages'][0];
        echo "✅ Message found!\n";
        echo "   Type: {$msg['_']}\n";
        
        if (isset($msg['media'])) {
            echo "   Media Type: {$msg['media']['_']}\n";
            
            if ($msg['media']['_'] === 'messageMediaDocument') {
                echo "   ✅ Has document!\n";
                $doc = $msg['media']['document'];
                echo "   Document Type: {$doc['_']}\n";
                echo "   Document ID: {$doc['id']}\n";
                
                // Try to get download info
                echo "\n--- Testing getDownloadInfo ---\n";
                try {
                    $info = $MadelineProto->getDownloadInfo($doc);
                    echo "✅ Download info retrieved!\n";
                    echo "   URL: " . ($info['url'] ?? 'NOT SET') . "\n";
                    echo "   Size: " . ($info['size'] ?? 'NOT SET') . "\n";
                } catch (Exception $e) {
                    echo "❌ getDownloadInfo failed: " . $e->getMessage() . "\n";
                }
            } else {
                echo "   ❌ Media is not a document, it's: {$msg['media']['_']}\n";
            }
        } else {
            echo "   ❌ No media in message\n";
        }
    } else {
        echo "❌ No messages in response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Method 1 failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n--- Testing Method 2: messages.getMessages ---\n";
try {
    $updates2 = $MadelineProto->messages->getMessages([
        'id' => [(int)$video['tg_message_id']]
    ]);
    
    echo "✅ Response received!\n";
    echo "📦 Response:\n";
    echo json_encode($updates2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Method 2 failed: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Method 3: Direct File Access ---\n";
try {
    // Try to get file info directly using file_id
    echo "Attempting to use file_id directly...\n";
    
    $fileInfo = $MadelineProto->getDownloadInfo($video['file_id']);
    echo "✅ Direct file access works!\n";
    echo "   URL: " . ($fileInfo['url'] ?? 'NOT SET') . "\n";
    
} catch (Exception $e) {
    echo "❌ Direct file access failed: " . $e->getMessage() . "\n";
}

echo "\n--- User/Bot Info ---\n";
try {
    $me = $MadelineProto->getSelf();
    echo "Logged in as:\n";
    echo "   Type: " . ($me['bot'] ?? false ? 'BOT' : 'USER') . "\n";
    echo "   ID: {$me['id']}\n";
    echo "   Username: " . ($me['username'] ?? 'N/A') . "\n";
    echo "   Phone: " . ($me['phone'] ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "❌ Could not get account info: " . $e->getMessage() . "\n";
}

echo "\n=== INSPECTION COMPLETE ===\n";
echo "</pre>";