<?php
// === TEST LINK GENERATOR ===
require __DIR__ . '/bootstrap.php';

$id = (int)($_GET['id'] ?? 1);
$secret = cfg('hmac_secret');
$base = cfg('base_url');

$exp = time() + 14400; // 4 hours
$sig = hash_hmac('sha256', $id . '.' . $exp, $secret);

$url = "{$base}/tg/stream.php?id={$id}&exp={$exp}&sig={$sig}";

// Check if video exists
$pdo = db();
$stmt = $pdo->prepare("SELECT id, file_id, file_path FROM videos WHERE id = ?");
$stmt->execute([$id]);
$video = $stmt->fetch();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Stream Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: linear-gradient(135deg, #0f0f23, #1a1a2e);
            color: #e0e0e0; 
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 { 
            color: #00ff88;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(0,255,136,0.5);
        }
        .box { 
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }
        .box h2 {
            color: #00ff88;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        .info-row {
            display: flex;
            margin: 10px 0;
            padding: 8px;
            background: rgba(0,0,0,0.3);
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #00ff88;
            width: 150px;
            flex-shrink: 0;
        }
        .info-value {
            color: #fff;
            word-break: break-all;
            font-size: 0.9rem;
        }
        a { 
            color: #00ddff;
            text-decoration: none;
            transition: color 0.3s;
        }
        a:hover { 
            color: #00ff88;
            text-decoration: underline;
        }
        video { 
            width: 100%;
            max-width: 800px;
            background: #000;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,255,136,0.2);
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .status-ok {
            background: rgba(0,255,136,0.2);
            color: #00ff88;
            border: 1px solid #00ff88;
        }
        .status-error {
            background: rgba(255,68,68,0.2);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .nav-links a {
            padding: 8px 16px;
            background: rgba(0,255,136,0.1);
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 5px;
            transition: all 0.3s;
        }
        .nav-links a:hover {
            background: rgba(0,255,136,0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Telegram Video Stream Test</h1>
        
        <div class="box">
            <h2>📊 Video Information</h2>
            
            <?php if ($video): ?>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <?php if (!empty($video['file_path'])): ?>
                            <span class="status status-ok">✓ Ready to Stream</span>
                        <?php else: ?>
                            <span class="status status-error">✗ No file_path</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Video ID:</div>
                    <div class="info-value"><?= $id ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Telegram File ID:</div>
                    <div class="info-value"><?= htmlspecialchars(substr($video['file_id'], 0, 50)) ?>...</div>
                </div>
                
                <?php if (!empty($video['file_path'])): ?>
                <div class="info-row">
                    <div class="info-label">Stream URL:</div>
                    <div class="info-value">
                        <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                            <?= htmlspecialchars(substr($url, 0, 80)) ?>...
                        </a>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Link Expires:</div>
                    <div class="info-value"><?= date('Y-m-d H:i:s', $exp) ?> (<?= round(($exp - time()) / 3600, 1) ?> hours)</div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="status status-error">✗ Video ID <?= $id ?> not found in database</div>
            <?php endif; ?>
        </div>
        
        <?php if ($video && !empty($video['file_path'])): ?>
        <div class="box">
            <h2>🎥 Live Player Test</h2>
            <video controls preload="metadata">
                <source src="<?= htmlspecialchars($url) ?>" type="video/mp4">
                Your browser doesn't support video playback.
            </video>
        </div>
        <?php elseif ($video && empty($video['file_path'])): ?>
        <div class="box">
            <h2>⚠️ Action Required</h2>
            <p style="color: #ff4444; margin-bottom: 15px;">
                This video doesn't have a file_path yet. Run the backfill script:
            </p>
            <a href="/tg/backfill_file_paths.php" style="display: inline-block; padding: 12px 24px; background: rgba(255,68,68,0.2); border: 1px solid #ff4444; border-radius: 5px; color: #ff4444; font-weight: bold;">
                → Run Backfill Script
            </a>
        </div>
        <?php endif; ?>
        
        <div class="box">
            <h2>🔗 Test Other Videos</h2>
            <div class="nav-links">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <a href="?id=<?= $i ?>" <?= $i == $id ? 'style="background: rgba(0,255,136,0.3);"' : '' ?>>
                        Video <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="box">
            <h2>🛠️ Troubleshooting</h2>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ Videos 1-6 are in your database</li>
                <li>✓ Stream links expire after 4 hours</li>
                <li>✓ Range requests enabled for seeking</li>
                <li>✓ Cloudflare-compatible streaming</li>
            </ul>
        </div>
    </div>
</body>
</html>
```

---

## STEP 3: Cloudflare Configuration

### Go to: Cloudflare Dashboard → Rules → Configuration Rules

**Create New Rule:**
```
Rule Name: Bypass TG Streaming
When incoming requests match:
  (http.request.uri.path contains "/tg/stream.php")

Then the settings are:
  ✓ Browser Cache TTL: Bypass cache
  ✓ Disable Rocket Loader
  ✓ Disable Apps
```

**Save and Deploy**

---

## STEP 4: Run the Fix (In Order)

### 1️⃣ **Test Your Setup**
Visit: `https://sinhalamovies.web.lk/tg/test_link.php?id=1`

You should see: `✗ No file_path` status

---

### 2️⃣ **Run Backfill Script**
Visit: `https://sinhalamovies.web.lk/tg/backfill_file_paths.php`

**Expected Output:**
```
=== File Path Backfill Tool ===

Found 6 videos needing file_path.

[Video ID: 1] Fetching from Telegram... ✓ Saved CDN URL
[Video ID: 2] Fetching from Telegram... ✓ Saved CDN URL
[Video ID: 3] Fetching from Telegram... ✓ Saved CDN URL
[Video ID: 4] Fetching from Telegram... ✓ Saved CDN URL
[Video ID: 5] Fetching from Telegram... ✓ Saved CDN URL
[Video ID: 6] Fetching from Telegram... ✓ Saved CDN URL

=== COMPLETE ===
✓ Success: 6
✗ Failed: 0

✓ You can now test streaming at /tg/test_link.php