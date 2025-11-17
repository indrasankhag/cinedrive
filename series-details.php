<?php
// Include database connection
require_once 'admin/config.php';

// Get series ID from URL
$series_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($series_id <= 0) {
    header('Location: tv-series.php');
    exit();
}

// First Query: Fetch series details
$sql_series = "SELECT * FROM series WHERE id = ?";
$stmt = $conn->prepare($sql_series);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if series exists
if ($result->num_rows === 0) {
    header('Location: tv-series.php');
    exit();
}

$series = $result->fetch_assoc();
$stmt->close();

// Second Query: Fetch all episodes for this series (ordered by season and episode number)
$episodes = [];
$sql_episodes = "SELECT * FROM episodes WHERE series_id = ? ORDER BY season_number ASC, episode_number ASC";
$stmt_episodes = $conn->prepare($sql_episodes);
$stmt_episodes->bind_param("i", $series_id);
$stmt_episodes->execute();
$result_episodes = $stmt_episodes->get_result();

if ($result_episodes && $result_episodes->num_rows > 0) {
    while ($row = $result_episodes->fetch_assoc()) {
        $episodes[] = $row;
    }
}
$stmt_episodes->close();

// Group episodes by season
$seasons = [];
foreach ($episodes as $episode) {
    $season_num = $episode['season_number'];
    if (!isset($seasons[$season_num])) {
        $seasons[$season_num] = [];
    }
    $seasons[$season_num][] = $episode;
}
ksort($seasons); // Sort seasons by number
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($series['title']); ?> - Watch on CineDrive">
    <title><?php echo htmlspecialchars($series['title']); ?> - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Series Details Page Styling */
        .series-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Series Header */
        .series-header {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 40px;
            padding: 28px;
            background: linear-gradient(145deg, rgba(20, 22, 35, 0.9), rgba(10, 11, 18, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.35);
        }

        .series-cover {
            flex-shrink: 0;
            width: 240px;
            height: auto;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s;
        }

        .series-cover:hover {
            transform: scale(1.03);
        }

        .series-info {
            flex: 1;
        }

        .series-title {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 12px 0;
            color: var(--text);
        }

        .series-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .series-description {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.8;
            text-align: justify;
        }

        /* Seasons Section */
        .seasons-section {
            margin-top: 30px;
        }

        .season-block {
            margin-bottom: 30px;
            background: linear-gradient(180deg, rgba(20, 22, 35, 0.9), rgba(10, 11, 18, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.35);
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .season-block:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(42, 108, 255, 0.25);
        }

        .season-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .season-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .season-count {
            font-size: 0.9rem;
            color: var(--muted);
        }

        /* Episodes Grid */
        .episodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }

        .episode-card {
            background: rgba(25, 27, 38, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.25s;
            cursor: pointer;
        }

        .episode-card:hover {
            transform: translateY(-3px);
            border-color: rgba(42, 108, 255, 0.3);
            box-shadow: 0 6px 18px rgba(42, 108, 255, 0.25);
        }

        .episode-thumbnail {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background-size: cover;
            background-position: center;
            background-color: var(--surface);
        }

        .episode-thumbnail::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
        }

        .episode-play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(42, 108, 255, 0.9);
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            transition: 0.3s;
            z-index: 2;
        }

        .episode-play-icon span {
            color: #fff;
            font-size: 20px;
        }

        .episode-card:hover .episode-play-icon {
            transform: translate(-50%, -50%) scale(1.15);
            background: var(--primary);
        }

        .episode-info {
            padding: 12px 14px;
        }

        .episode-code {
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .episode-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 4px 0;
            line-height: 1.3;
        }

        .episode-date {
            font-size: 0.8rem;
            color: var(--muted);
        }

        /* Video Player Popup */
        .video-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
        }

        .video-popup.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-popup-content {
            position: relative;
            width: 100%;
            max-width: 1200px;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        }

        .video-popup-header {
            background: var(--surface);
            padding: 16px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-popup-title {
            margin: 0;
            font-size: 1.2rem;
            color: var(--text);
        }

        .close-popup {
            background: rgba(255, 51, 102, 0.2);
            border: 1px solid rgba(255, 51, 102, 0.3);
            color: var(--secondary);
            font-size: 1.5rem;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-popup:hover {
            background: var(--secondary);
            color: #fff;
            transform: scale(1.1);
        }

        .video-popup-player {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
        }

        .video-popup-player iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* No Episodes State */
        .no-episodes {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
        }

        .no-episodes h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--text);
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            margin-bottom: 20px;
            background: rgba(20, 22, 35, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.25s;
        }

        .back-button:hover {
            color: var(--text);
            background: color-mix(in oklab, var(--primary) 10%, transparent);
            border-color: rgba(42, 108, 255, 0.3);
            transform: translateX(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .series-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }

            .series-cover {
                width: 200px;
            }

            .series-title {
                font-size: 1.5rem;
            }

            .series-meta {
                justify-content: center;
            }

            .series-description {
                text-align: left;
            }

            .episodes-grid {
                grid-template-columns: 1fr;
            }

            .video-popup {
                padding: 10px;
            }

            .season-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Logo -->
    <div class="top-logo">
        <img src="assets/images/logo.png" alt="CineDrive Logo">
    </div>

    <!-- Series Details Container -->
    <div class="series-details-container">
        <!-- Back Button -->
        <a href="tv-series.php" class="back-button">
            <span>←</span>
            Back to TV Series
        </a>

        <!-- Series Header -->
        <div class="series-header">
            <?php if (!empty($series['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($series['cover_image']); ?>" alt="<?php echo htmlspecialchars($series['title']); ?>" class="series-cover">
            <?php endif; ?>
            
            <div class="series-info">
                <h1 class="series-title"><?php echo htmlspecialchars($series['title']); ?></h1>
                
                <div class="series-meta">
                    <span class="meta-badge">📺 TV Series</span>
                    
                    <?php if (!empty($series['release_year'])): ?>
                        <span class="meta-badge">📅 <?php echo htmlspecialchars($series['release_year']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($series['genre'])): ?>
                        <span class="meta-badge">🎭 <?php echo htmlspecialchars($series['genre']); ?></span>
                    <?php endif; ?>

                    <span class="meta-badge">🎬 <?php echo count($episodes); ?> Episode<?php echo count($episodes) !== 1 ? 's' : ''; ?></span>
                </div>
                
                <?php if (!empty($series['description'])): ?>
                    <p class="series-description"><?php echo nl2br(htmlspecialchars($series['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Seasons Section -->
        <div class="seasons-section">
            <?php if (!empty($seasons)): ?>
                <?php foreach ($seasons as $season_number => $season_episodes): ?>
                    <div class="season-block">
                        <div class="season-header">
                            <h2 class="season-title">Season <?php echo $season_number; ?></h2>
                            <span class="season-count"><?php echo count($season_episodes); ?> Episode<?php echo count($season_episodes) !== 1 ? 's' : ''; ?></span>
                        </div>

                        <div class="episodes-grid">
                            <?php foreach ($season_episodes as $episode): ?>
                                <div class="episode-card" onclick="openVideoPopup(
                                    '<?php echo htmlspecialchars($episode['video_url']); ?>',
                                    '<?php echo htmlspecialchars($series['title']); ?>',
                                    'S<?php echo str_pad($episode['season_number'], 2, '0', STR_PAD_LEFT); ?>E<?php echo str_pad($episode['episode_number'], 2, '0', STR_PAD_LEFT); ?>',
                                    '<?php echo htmlspecialchars($episode['title']); ?>'
                                )">
                                    <div class="episode-thumbnail" style="background-image: url('<?php echo htmlspecialchars($episode['thumbnail'] ?? $series['cover_image']); ?>')">
                                        <div class="episode-play-icon">
                                            <span>▶</span>
                                        </div>
                                    </div>
                                    <div class="episode-info">
                                        <div class="episode-code">
                                            S<?php echo str_pad($episode['season_number'], 2, '0', STR_PAD_LEFT); ?>E<?php echo str_pad($episode['episode_number'], 2, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        <h3 class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></h3>
                                        <?php if (!empty($episode['air_date'])): ?>
                                            <div class="episode-date"><?php echo date('M d, Y', strtotime($episode['air_date'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-episodes">
                    <h3>No Episodes Available</h3>
                    <p>Episodes for this series are coming soon! 📺</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Player Popup -->
    <div class="video-popup" id="videoPopup">
        <div class="video-popup-content">
            <div class="video-popup-header">
                <h3 class="video-popup-title" id="popupTitle">Episode Player</h3>
                <button class="close-popup" onclick="closeVideoPopup()">×</button>
            </div>
            <div class="video-popup-player">
                <iframe id="videoFrame" src="" allowfullscreen allow="autoplay; fullscreen"></iframe>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>

    <script>
        // Open video popup
        function openVideoPopup(videoUrl, seriesTitle, episodeCode, episodeTitle) {
            const popup = document.getElementById('videoPopup');
            const frame = document.getElementById('videoFrame');
            const title = document.getElementById('popupTitle');
            
            // Set video source (Doodstream embed)
            frame.src = 'https://dood.li/e/' + videoUrl;
            
            // Set popup title
            title.textContent = seriesTitle + ' - ' + episodeCode + ': ' + episodeTitle;
            
            // Show popup
            popup.classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        // Close video popup
        function closeVideoPopup() {
            const popup = document.getElementById('videoPopup');
            const frame = document.getElementById('videoFrame');
            
            // Hide popup
            popup.classList.remove('active');
            
            // Stop video
            frame.src = '';
            
            // Restore body scroll
            document.body.style.overflow = '';
        }

        // Close popup when clicking outside
        document.getElementById('videoPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoPopup();
            }
        });

        // Close popup with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoPopup();
            }
        });
    </script>
</body>
</html>
