<?php
// MADELINE PROTO INSTALLER - Run once via browser
set_time_limit(300);
header('Content-Type: text/plain');

$targetDir = __DIR__;
$pharFile = $targetDir . '/madeline.phar';
$url = 'https://phar.madelineproto.xyz/madeline.phar';

echo "=== MadelineProto Installer ===\n\n";

// Method 1: Try with cURL
echo "[1/3] Attempting download via cURL...\n";
$ch = curl_init($url);
$fp = fopen($pharFile, 'wb');
curl_setopt_array($ch, [
    CURLOPT_FILE => $fp,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if ($result && $httpCode === 200 && file_exists($pharFile)) {
    $size = filesize($pharFile);
    echo "✓ Downloaded successfully! Size: " . number_format($size) . " bytes\n";
    
    if ($size < 100000) {
        echo "✗ ERROR: File too small (likely HTML error page). Trying alternative method...\n";
        unlink($pharFile);
    } else {
        echo "\n✓ SUCCESS! madeline.phar is ready.\n";
        echo "Next: Visit /tg/mp_setup.php to configure.\n";
        exit;
    }
}

// Method 2: Try with file_get_contents
echo "\n[2/3] Attempting download via file_get_contents...\n";
$content = @file_get_contents($url);
if ($content && strlen($content) > 100000) {
    file_put_contents($pharFile, $content);
    echo "✓ Downloaded successfully! Size: " . number_format(strlen($content)) . " bytes\n";
    echo "\n✓ SUCCESS! madeline.phar is ready.\n";
    echo "Next: Visit /tg/mp_setup.php to configure.\n";
    exit;
}

// Method 3: Manual upload instructions
echo "\n[3/3] Automatic download failed.\n";
echo "=== MANUAL UPLOAD REQUIRED ===\n\n";
echo "1. Download from: https://phar.madelineproto.xyz/madeline.phar\n";
echo "2. Upload to: /public_html/tg/madeline.phar via cPanel File Manager\n";
echo "3. Verify file size is 3MB+ (not a few KB)\n";
echo "4. Then visit /tg/mp_setup.php\n";
?>