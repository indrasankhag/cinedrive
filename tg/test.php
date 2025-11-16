<!DOCTYPE html>
<html>
<head>
    <title>Video Streaming Test</title>
    <style>
        body {
            font-family: monospace;
            background: #0d1117;
            color: #c9d1d9;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        h1 { color: #58a6ff; }
        .video-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        video {
            width: 100%;
            max-width: 800px;
            background: #000;
            border-radius: 6px;
        }
        .info {
            background: #1c2128;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .success { color: #3fb950; }
        .error { color: #f85149; }
        .links { margin: 20px 0; }
        .links a {
            display: inline-block;
            background: #238636;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin: 5px;
        }
        .links a:hover { background: #2ea043; }
    </style>
</head>
<body>
    <h1>🎬 Telegram Video Streaming Test</h1>
    
    <div class="info">
        <strong>Testing Video ID: 1</strong><br>
        This directly streams from Telegram using MadelineProto.<br>
        No file_path needed in database!
    </div>
    
    <div class="video-container">
        <video id="player" controls preload="metadata">
            <source src="/tg/stream.php?id=1&exp=<?php echo time() + 14400; ?>&sig=<?php 
                $id = 1;
                $exp = time() + 14400;
                $secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
                echo hash_hmac('sha256', $id . '.' . $exp, $secret);
            ?>" type="video/mp4">
            Your browser doesn't support video playback.
        </video>
    </div>
    
    <div class="links">
        <a href="?id=1">Video 1</a>
        <a href="?id=2">Video 2</a>
        <a href="?id=3">Video 3</a>
        <a href="?id=4">Video 4</a>
        <a href="?id=5">Video 5</a>
        <a href="?id=6">Video 6</a>
        <a href="?id=7">Video 7</a>
    </div>
    
    <div class="info">
        <strong>Status:</strong>
        <div id="status">Loading...</div>
    </div>
    
    <script>
        const video = document.getElementById('player');
        const status = document.getElementById('status');
        
        video.addEventListener('loadedmetadata', () => {
            status.innerHTML = '<span class="success">✓ Video loaded successfully!</span><br>' +
                              'Duration: ' + Math.floor(video.duration) + ' seconds<br>' +
                              'Dimensions: ' + video.videoWidth + 'x' + video.videoHeight;
        });
        
        video.addEventListener('error', (e) => {
            status.innerHTML = '<span class="error">✗ Failed to load video</span><br>' +
                              'Error: ' + video.error.message;
        });
        
        video.addEventListener('play', () => {
            console.log('Video playing');
        });
        
        video.addEventListener('seeking', () => {
            console.log('Seeking to:', video.currentTime);
        });
    </script>
</body>
</html>