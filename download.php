<?php
// download.php - Monetized download page
$videoId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$videoTitle = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'Video';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download: <?php echo $videoTitle; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .download-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .download-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .download-header h1 { font-size: 24px; margin-bottom: 10px; }
        .download-header p { opacity: 0.9; font-size: 14px; }
        .download-body { padding: 40px 30px; }
        .video-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f3f4f6;
        }
        .video-info h2 { font-size: 20px; color: #1f2937; margin-bottom: 10px; }
        .video-info .meta { color: #6b7280; font-size: 14px; }
        .timer-section { text-align: center; margin-bottom: 30px; }
        .timer-circle {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            position: relative;
        }
        .timer-circle svg { transform: rotate(-90deg); }
        .timer-circle circle { fill: none; stroke-width: 8; }
        .timer-circle .bg { stroke: #e5e7eb; }
        .timer-circle .progress {
            stroke: #667eea;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
        }
        .timer-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .timer-label { color: #6b7280; font-size: 14px; font-weight: 500; }
        .download-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0.5;
            pointer-events: none;
        }
        .download-btn.active { opacity: 1; pointer-events: auto; }
        .download-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .download-btn svg { width: 20px; height: 20px; }
        .ad-space {
            margin: 30px 0;
            min-height: 250px;
            background: #f9fafb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .download-footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        @media (max-width: 768px) {
            .download-header h1 { font-size: 20px; }
            .video-info h2 { font-size: 18px; }
            .timer-circle { width: 100px; height: 100px; }
            .timer-text { font-size: 28px; }
            .download-body { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="download-container">
        <div class="download-header">
            <h1>🎬 Download Video</h1>
            <p>Please wait while we prepare your download link</p>
        </div>
        
        <div class="download-body">
            <!-- Top Ad -->
            <div class="ad-space">
                <script type="text/javascript">
                    atOptions = {
                        'key' : '96a29f51b86217947d07668aab3919e6',
                        'format' : 'iframe',
                        'height' : 250,
                        'width' : 300,
                        'params' : {}
                    };
                </script>
                <script type="text/javascript" src="//www.highperformanceformat.com/96a29f51b86217947d07668aab3919e6/invoke.js"></script>
            </div>
            
            <div class="video-info">
                <h2><?php echo $videoTitle; ?></h2>
                <div class="meta">Preparing download...</div>
            </div>
            
            <div class="timer-section" id="timerSection">
                <div class="timer-circle">
                    <svg width="120" height="120">
                        <circle class="bg" cx="60" cy="60" r="54"></circle>
                        <circle id="progressCircle" class="progress" cx="60" cy="60" r="54" 
                                stroke-dasharray="339.292" stroke-dashoffset="339.292"></circle>
                    </svg>
                    <div class="timer-text" id="timerText">10</div>
                </div>
                <div class="timer-label">Seconds remaining</div>
            </div>
            
            <button class="download-btn" id="downloadBtn" disabled>
                <svg fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                <span id="btnText">Please wait...</span>
            </button>
            
            <!-- Bottom Ad -->
            <div class="ad-space">
                <script type="text/javascript">
                    atOptions = {
                        'key' : '96a29f51b86217947d07668aab3919e6',
                        'format' : 'iframe',
                        'height' : 250,
                        'width' : 300,
                        'params' : {}
                    };
                </script>
                <script type="text/javascript" src="//www.highperformanceformat.com/96a29f51b86217947d07668aab3919e6/invoke.js"></script>
            </div>
        </div>
        
        <div class="download-footer">
            ⚡ Powered by SinhalaMovies | Download will start automatically
        </div>
    </div>

    <script>
        let timeLeft = 10;
        const timerText = document.getElementById('timerText');
        const progressCircle = document.getElementById('progressCircle');
        const downloadBtn = document.getElementById('downloadBtn');
        const btnText = document.getElementById('btnText');
        const circumference = 339.292;
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerText.textContent = timeLeft;
            const offset = circumference - (circumference * (10 - timeLeft) / 10);
            progressCircle.style.strokeDashoffset = offset;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                enableDownload();
            }
        }, 1000);
        
        function enableDownload() {
            document.getElementById('timerSection').style.display = 'none';
            downloadBtn.classList.add('active');
            downloadBtn.disabled = false;
            btnText.textContent = 'Download Now';
            downloadBtn.onclick = initiateDownload;
        }
        
        function initiateDownload() {
            const exp = Math.floor(Date.now() / 1000) + 14400;
            const secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
            const videoId = <?php echo $videoId; ?>;
            
            // Generate signature (simplified - use proper HMAC in production)
            const sig = '<?php 
                $videoId = isset($_GET['id']) ? intval($_GET['id']) : 1;
                $exp = time() + 14400;
                $secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
                echo hash_hmac('sha256', $videoId . '.' . $exp, $secret);
            ?>';
            
            const downloadUrl = `/tg/stream.php?id=${videoId}&exp=${exp}&sig=${sig}`;
            
            btnText.textContent = 'Downloading...';
            downloadBtn.style.background = '#10b981';
            
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = '<?php echo addslashes($videoTitle); ?>.mp4';
            link.click();
            
            setTimeout(() => {
                btnText.textContent = '✓ Download Started';
            }, 1000);
        }
    </script>
</body>
</html>