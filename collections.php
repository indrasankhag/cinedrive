<?php
// Include database connection
require_once 'admin/config.php';

// Fetch all collections from database (latest first)
$collections = [];
$sql = "SELECT id, title, description, cover_image, created_at FROM collections ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $collections[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CineDrive - Browse movie collections and curated lists">
    <title>Movie Collections - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Collections Page Styling */
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

        /* Collection Grid */
        .collection-grid {
            margin-top: 18px;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            padding-bottom: 20px;
        }

        /* Collection Card - Enhanced styling */
        .collection-card {
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            cursor: pointer;
        }

        .collection-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(42, 108, 255, 0.25);
            border-color: rgba(42, 108, 255, 0.3);
        }

        .collection-thumb {
            position: relative;
            aspect-ratio: 16 / 9;
            background-size: cover;
            background-position: center;
            background-color: var(--surface);
        }

        .collection-thumb::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.15));
        }

        .collection-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            z-index: 2;
        }

        .collection-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(10, 25, 60, 0.85);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: grid;
            place-items: center;
            transition: 0.3s;
            z-index: 2;
        }

        .collection-icon span {
            color: #fff;
            font-size: 26px;
        }

        .collection-card:hover .collection-icon {
            transform: translate(-50%, -50%) scale(1.15);
            background: rgba(42, 108, 255, 0.9);
        }

        .collection-meta {
            padding: 14px 16px 18px;
        }

        .collection-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text);
            margin: 0 0 8px 0;
            line-height: 1.3;
        }

        .collection-description {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin: 0;
        }

        /* Empty state */
        .no-collections {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .no-collections h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-collections p {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .collection-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }

            .collection-icon {
                width: 50px;
                height: 50px;
            }

            .collection-icon span {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.7rem;
            }

            .collection-grid {
                grid-template-columns: 1fr;
                gap: 14px;
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
            <h1 class="page-title">📚 Movie Collections</h1>
            <p class="page-subtitle">Curated collections of the best movies organized by theme, genre, and more</p>
        </div>

        <!-- Collections Grid -->
        <?php if (!empty($collections)): ?>
            <div class="collection-grid">
                <?php foreach ($collections as $collection): ?>
                    <a href="collection-details.php?id=<?php echo $collection['id']; ?>" class="collection-card">
                        <div class="collection-thumb" style="background-image: url('<?php echo htmlspecialchars($collection['cover_image']); ?>')">
                            <div class="collection-icon">
                                <span>📚</span>
                            </div>
                        </div>
                        <div class="collection-meta">
                            <h3 class="collection-title"><?php echo htmlspecialchars($collection['title']); ?></h3>
                            <?php if (!empty($collection['description'])): ?>
                                <p class="collection-description"><?php echo htmlspecialchars($collection['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-collections">
                <h3>No Collections Available</h3>
                <p>Check back soon for curated movie collections! 🎬</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>
