<?php
// Include database connection
require_once 'admin/config.php';

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($movie_id <= 0) {
    header('Location: movies.php');
    exit();
}

// Fetch movie details from database
$sql = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if movie exists
if ($result->num_rows === 0) {
    header('Location: movies.php');
    exit();
}

$movie = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($movie['title']); ?> - Watch on CineDrive">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Movie Details Page Styling */
        .movie-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .movie-header {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
            padding: 24px;
            background: linear-gradient(145deg, rgba(20, 22, 35, 0.9), rgba(10, 11, 18, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.35);
        }

        .movie-poster {
            flex-shrink: 0;
            width: 240px;
            height: auto;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s;
        }

        .movie-poster:hover {
            transform: scale(1.03);
        }

        .movie-info {
            flex: 1;
        }

        .movie-title {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 12px 0;
            color: var(--text);
        }

        .movie-meta {
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

        .movie-description {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.8;
            text-align: justify;
        }

        /* Video Player Section */
        .video-player-section {
            margin: 30px 0;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .video-player-header {
            background: var(--surface);
            padding: 14px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-player-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text);
        }

        .video-player {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
        }

        .video-player iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Download Section */
        .download-section {
            margin: 30px 0;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .download-section h3 {
            margin: 0 0 20px 0;
            font-size: 1.3rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .download-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .download-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, rgba(42, 108, 255, 0.15), rgba(255, 51, 102, 0.1));
            border: 1px solid rgba(42, 108, 255, 0.3);
            border-radius: 12px;
            color: var(--text);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(42, 108, 255, 0.3);
            background: linear-gradient(135deg, rgba(42, 108, 255, 0.25), rgba(255, 51, 102, 0.2));
        }

        .download-btn-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .download-quality {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .download-size {
            font-size: 0.85rem;
            color: var(--muted);
        }

        .download-icon {
            font-size: 1.5rem;
        }

        /* Direct Download Button */
        .direct-download-section {
            margin: 20px 0;
            text-align: center;
        }

        .direct-download-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 36px;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(42, 108, 255, 0.4);
        }

        .direct-download-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(42, 108, 255, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .movie-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }

            .movie-poster {
                width: 200px;
            }

            .movie-title {
                font-size: 1.5rem;
            }

            .movie-meta {
                justify-content: center;
            }

            .download-buttons {
                grid-template-columns: 1fr;
            }

            .movie-description {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <!-- Logo -->
    <div class="top-logo">
        <img src="assets/images/logo.png" alt="CineDrive Logo">
    </div>

    <!-- Movie Details Container -->
    <div class="movie-details-container">
        <!-- Movie Header with Poster and Info -->
        <div class="movie-header">
            <?php if (!empty($movie['thumbnail'])): ?>
                <img src="<?php echo htmlspecialchars($movie['thumbnail']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
            <?php endif; ?>
            
            <div class="movie-info">
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <div class="movie-meta">
                    <?php if (!empty($movie['release_year'])): ?>
                        <span class="meta-badge">📅 <?php echo htmlspecialchars($movie['release_year']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie['genre'])): ?>
                        <span class="meta-badge">🎭 <?php echo htmlspecialchars($movie['genre']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie['tags'])): ?>
                        <span class="meta-badge">🏷️ <?php echo htmlspecialchars($movie['tags']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($movie['description'])): ?>
                    <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Video Player Section -->
        <?php if (!empty($movie['video_url'])): ?>
        <div class="video-player-section">
            <div class="video-player-header">
                <h3>🎬 Watch Now</h3>
            </div>
            <div class="video-player">
                <iframe 
                    src="https://dood.li/e/<?php echo htmlspecialchars($movie['video_url']); ?>" 
                    allowfullscreen
                    allow="autoplay; fullscreen"
                ></iframe>
            </div>
        </div>
        <?php endif; ?>

        <!-- Direct Download Button -->
        <div class="direct-download-section">
            <a href="#downloads" class="direct-download-btn">
                <span>⬇</span>
                Direct Download
            </a>
        </div>

        <!-- Download Section -->
        <div class="download-section" id="downloads">
            <h3>
                <span>💾</span>
                Download Options
            </h3>
            
            <div class="download-buttons">
                <!-- 480p Download -->
                <a href="#" class="download-btn" onclick="alert('Download link coming soon!'); return false;">
                    <div class="download-btn-left">
                        <div>
                            <div class="download-quality">480p</div>
                            <div class="download-size">~800MB</div>
                        </div>
                    </div>
                    <span class="download-icon">⬇</span>
                </a>

                <!-- 720p Download -->
                <a href="#" class="download-btn" onclick="alert('Download link coming soon!'); return false;">
                    <div class="download-btn-left">
                        <div>
                            <div class="download-quality">720p</div>
                            <div class="download-size">~1.6GB</div>
                        </div>
                    </div>
                    <span class="download-icon">⬇</span>
                </a>

                <!-- 1080p Download -->
                <a href="#" class="download-btn" onclick="alert('Download link coming soon!'); return false;">
                    <div class="download-btn-left">
                        <div>
                            <div class="download-quality">1080p</div>
                            <div class="download-size">~3.2GB</div>
                        </div>
                    </div>
                    <span class="download-icon">⬇</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>
