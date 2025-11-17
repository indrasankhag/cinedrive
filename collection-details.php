<?php
// Include database connection
require_once 'admin/config.php';

// Get collection ID from URL
$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($collection_id <= 0) {
    header('Location: collections.php');
    exit();
}

// First Query: Fetch collection details
$sql_collection = "SELECT * FROM collections WHERE id = ?";
$stmt = $conn->prepare($sql_collection);
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if collection exists
if ($result->num_rows === 0) {
    header('Location: collections.php');
    exit();
}

$collection = $result->fetch_assoc();
$stmt->close();

// Second Query: Fetch all movies in this collection
$movies_in_collection = [];
$sql_movies = "SELECT id, title, description, thumbnail, release_year, genre FROM movies WHERE collection_id = ? ORDER BY id DESC";
$stmt_movies = $conn->prepare($sql_movies);
$stmt_movies->bind_param("i", $collection_id);
$stmt_movies->execute();
$result_movies = $stmt_movies->get_result();

if ($result_movies && $result_movies->num_rows > 0) {
    while ($row = $result_movies->fetch_assoc()) {
        $movies_in_collection[] = $row;
    }
}
$stmt_movies->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($collection['title']); ?> - CineDrive Collection">
    <title><?php echo htmlspecialchars($collection['title']); ?> - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Collection Details Page Styling */
        .collection-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Collection Header */
        .collection-header {
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

        .collection-cover {
            flex-shrink: 0;
            width: 280px;
            height: auto;
            aspect-ratio: 16 / 9;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.5);
            object-fit: cover;
            transition: transform 0.3s;
        }

        .collection-cover:hover {
            transform: scale(1.03);
        }

        .collection-info {
            flex: 1;
        }

        .collection-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 16px 0;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .collection-icon {
            font-size: 2rem;
        }

        .collection-description {
            font-size: 1.05rem;
            color: var(--muted);
            line-height: 1.8;
            text-align: justify;
            margin: 0;
        }

        .collection-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Movies Section */
        .movies-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 20px 0;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Movie Grid */
        .movie-grid {
            margin-top: 18px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }

        /* Empty state */
        .no-movies {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
        }

        .no-movies h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--text);
        }

        .no-movies p {
            font-size: 1rem;
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
            .collection-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }

            .collection-cover {
                width: 100%;
                max-width: 320px;
            }

            .collection-title {
                font-size: 1.8rem;
                justify-content: center;
            }

            .collection-stats {
                justify-content: center;
            }

            .collection-description {
                text-align: left;
            }

            .movie-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Logo -->
    <div class="top-logo">
        <img src="assets/images/logo.png" alt="CineDrive Logo">
    </div>

    <!-- Collection Details Container -->
    <div class="collection-details-container">
        <!-- Back Button -->
        <a href="collections.php" class="back-button">
            <span>←</span>
            Back to Collections
        </a>

        <!-- Collection Header -->
        <div class="collection-header">
            <?php if (!empty($collection['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($collection['cover_image']); ?>" alt="<?php echo htmlspecialchars($collection['title']); ?>" class="collection-cover">
            <?php endif; ?>
            
            <div class="collection-info">
                <h1 class="collection-title">
                    <span class="collection-icon">📚</span>
                    <?php echo htmlspecialchars($collection['title']); ?>
                </h1>
                
                <?php if (!empty($collection['description'])): ?>
                    <p class="collection-description"><?php echo nl2br(htmlspecialchars($collection['description'])); ?></p>
                <?php endif; ?>

                <div class="collection-stats">
                    <span class="stat-badge">
                        <span>🎬</span>
                        <?php echo count($movies_in_collection); ?> Movie<?php echo count($movies_in_collection) !== 1 ? 's' : ''; ?>
                    </span>
                    
                    <?php if (!empty($collection['created_at'])): ?>
                        <span class="stat-badge">
                            <span>📅</span>
                            Created <?php echo date('M Y', strtotime($collection['created_at'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Movies Section -->
        <div class="movies-section">
            <h2 class="section-title">
                <span>🎥</span>
                Movies in this Collection
            </h2>

            <?php if (!empty($movies_in_collection)): ?>
                <div class="movie-grid grid">
                    <?php foreach ($movies_in_collection as $movie): ?>
                        <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="card">
                            <div class="thumb" style="background-image: url('<?php echo htmlspecialchars($movie['thumbnail']); ?>')">
                                <div class="play-overlay">
                                    <span>▶</span>
                                </div>
                            </div>
                            <div class="meta">
                                <div class="title"><?php echo htmlspecialchars($movie['title']); ?></div>
                                <div class="sub">
                                    <?php echo htmlspecialchars($movie['release_year'] ?? 'N/A'); ?> • 
                                    <?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-movies">
                    <h3>No Movies Found</h3>
                    <p>This collection is currently empty. Check back soon! 🎬</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>
