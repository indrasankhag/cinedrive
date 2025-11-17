<?php
// Include database connection
require_once 'admin/config.php';

// Fetch all TV series from database (latest first)
$all_series = [];
$sql = "SELECT id, title, description, cover_image, release_year, genre FROM series ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_series[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CineDrive - Browse and watch your favorite TV series">
    <title>TV Series - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* TV Series Page Styling */
        .page-header {
            text-align: center;
            padding: 30px 20px 20px;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 1rem;
            color: var(--muted);
            margin-top: 8px;
        }

        /* Series Grid */
        .series-grid {
            margin-top: 18px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            padding-bottom: 20px;
        }

        /* Series Card - Enhanced styling */
        .series-card {
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            cursor: pointer;
        }

        .series-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(42, 108, 255, 0.25);
            border-color: rgba(42, 108, 255, 0.3);
        }

        .series-thumb {
            position: relative;
            aspect-ratio: 2 / 3;
            background-size: cover;
            background-position: center;
            background-color: var(--surface);
        }

        .series-thumb::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.15));
        }

        .series-play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(10, 25, 60, 0.8);
            border-radius: 50%;
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            transition: 0.3s;
            z-index: 2;
        }

        .series-play-overlay span {
            color: #fff;
            font-size: 22px;
        }

        .series-card:hover .series-play-overlay {
            transform: translate(-50%, -50%) scale(1.1);
        }

        .series-meta {
            padding: 10px 12px 14px;
        }

        .series-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--text);
            margin: 0 0 4px 0;
        }

        .series-sub {
            font-size: 12px;
            color: var(--muted);
        }

        /* TV Badge */
        .tv-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(42, 108, 255, 0.9);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            z-index: 3;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* Empty state */
        .no-series {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .no-series h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-series p {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .series-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.7rem;
            }

            .series-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Logo -->
    <div class="top-logo">
        <img src="assets/images/logo.png" alt="CineDrive Logo">
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">📺 TV Series</h1>
            <p class="page-subtitle">Binge-watch your favorite shows and discover new series</p>
        </div>

        <!-- TV Series Grid -->
        <?php if (!empty($all_series)): ?>
            <div class="series-grid">
                <?php foreach ($all_series as $series): ?>
                    <a href="series-details.php?id=<?php echo $series['id']; ?>" class="series-card">
                        <div class="series-thumb" style="background-image: url('<?php echo htmlspecialchars($series['cover_image']); ?>')">
                            <div class="tv-badge">TV</div>
                            <div class="series-play-overlay">
                                <span>▶</span>
                            </div>
                        </div>
                        <div class="series-meta">
                            <div class="series-title"><?php echo htmlspecialchars($series['title']); ?></div>
                            <div class="series-sub">
                                <?php echo htmlspecialchars($series['release_year'] ?? 'N/A'); ?>
                                <?php if (!empty($series['genre'])): ?>
                                    • <?php echo htmlspecialchars($series['genre']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-series">
                <h3>No TV Series Available</h3>
                <p>Check back soon for new shows! 📺</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>
