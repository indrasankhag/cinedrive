<?php
// === MADELINEPROTO SETUP & LOGIN ===
// Run this ONCE to create your session
set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/bootstrap.php';

$pharPath = __DIR__ . '/madeline.phar';
$sessionPath = __DIR__ . '/session.madeline';

// Check if PHAR exists
if (!file_exists($pharPath)) {
    die("❌ ERROR: madeline.phar not found!\nRun /tg/install_madeline.php first.\n");
}

// Load MadelineProto
require_once $pharPath;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;

echo "<pre style='font-family:monospace;background:#000;color:#0f0;padding:20px;'>";
echo "=== MadelineProto Session Setup ===\n\n";

try {
    // Create settings
    $settings = new Settings;
    
    // App info
    $appInfo = new AppInfo;
    $appInfo->setApiId(cfg('api_id'));
    $appInfo->setApiHash(cfg('api_hash'));
    $settings->setAppInfo($appInfo);

    echo "[1/3] Initializing MadelineProto...\n";
    $MadelineProto = new API($sessionPath, $settings);
    
    echo "[2/3] Starting Telegram login...\n\n";
    $MadelineProto->start();
    
    echo "\n✅ SUCCESS! Session created: session.madeline\n";
    echo "You are now logged in.\n\n";
    echo "Next steps:\n";
    echo "1. Run /tg/backfill_file_paths.php to fill missing file_path values\n";
    echo "2. Run /tg/test_link.php to test streaming\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>