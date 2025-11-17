<?php
// Include database connection
require_once 'admin/config.php';

// Get filter parameters
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$tag = isset($_GET['tag']) ? $_GET['tag'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build SQL query with filters
$sql = "SELECT id, title, description, thumbnail, release_year, genre FROM movies WHERE 1=1";

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql .= " AND (title LIKE '%$search_safe%' OR description LIKE '%$search_safe%')";
}

if (!empty($genre)) {
    $genre_safe = $conn->real_escape_string($genre);
    $sql .= " AND genre LIKE '%$genre_safe%'";
}

if (!empty($year)) {
    $year_safe = $conn->real_escape_string($year);
    $sql .= " AND release_year = '$year_safe'";
}

if (!empty($tag)) {
    $tag_safe = $conn->real_escape_string($tag);
    $sql .= " AND tags LIKE '%$tag_safe%'";
}

$sql .= " ORDER BY id DESC";

// Execute query
$movies = [];
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
}

// Fetch unique years for filter
$years_sql = "SELECT DISTINCT release_year FROM movies WHERE release_year IS NOT NULL ORDER BY release_year DESC";
$years_result = $conn->query($years_sql);
$years = [];
if ($years_result && $years_result->num_rows > 0) {
    while ($row = $years_result->fetch_assoc()) {
        $years[] = $row['release_year'];
    }
}

// Common genres
$genres = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Thriller', 'Romance', 'Animation', 'Adventure', 'Crime'];

// Common tags
$tags = ['Popular', 'Trending', 'New Release', 'Classic', 'Award Winner', 'Blockbuster'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CineDrive - Browse and watch your favorite movies">
    <title>Movies - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Filter Bar Styling */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 0;
            margin-bottom: 10px;
        }

        .filter-btn {
            position: relative;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--muted);
            text-transform: uppercase;
            padding: 10px 18px;
            border-radius: 10px;
            background: rgba(20, 22, 35, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.25s;
            cursor: pointer;
        }

        .filter-btn:hover {
            color: var(--text);
            background: color-mix(in oklab, var(--primary) 10%, transparent);
            border-color: rgba(42, 108, 255, 0.3);
        }

        .filter-btn.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(42, 108, 255, 0.25), rgba(255, 51, 102, 0.2));
            border: 1px solid rgba(42, 108, 255, 0.3);
            box-shadow: 0 0 12px rgba(42, 108, 255, 0.25);
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 200px;
            background: rgba(20, 22, 35, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.25s;
            z-index: 200;
            max-height: 300px;
            overflow-y: auto;
        }

        .filter-dropdown-menu.open {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .filter-dropdown-menu a {
            display: block;
            padding: 10px 16px;
            color: var(--text);
            font-size: 0.9rem;
            transition: background 0.2s;
            border-radius: 8px;
            margin: 4px;
        }

        .filter-dropdown-menu a:hover {
            background: color-mix(in oklab, var(--primary) 12%, transparent);
        }

        /* Movie Grid */
        .movie-grid {
            margin-top: 18px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }

        /* No results message */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            font-size: 1.1rem;
        }

        /* Active filter indicator */
        .active-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(42, 108, 255, 0.2);
            border: 1px solid rgba(42, 108, 255, 0.3);
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text);
        }

        .active-filter-tag .remove {
            cursor: pointer;
            color: var(--secondary);
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .filter-bar {
                gap: 8px;
            }

            .filter-btn {
                font-size: 0.85rem;
                padding: 8px 14px;
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

    <!-- Main Container -->
    <div class="container">
        <!-- Navbar with Search -->
        <nav class="navbar">
            <ul class="menu">
                <li><a href="index.php">Shots</a></li>
                <li><a href="movies.php" class="active">Movies</a></li>
                <li><a href="tv-series.php">TV Series</a></li>
                <li><a href="collections.php">Collections</a></li>
            </ul>
            
            <!-- Search Bar -->
            <form class="search" method="GET" action="movies.php">
                <span>🔍</span>
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search movies by title..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </form>
        </nav>

        <!-- Active Filters Display -->
        <?php if (!empty($genre) || !empty($year) || !empty($tag)): ?>
        <div class="active-filters">
            <?php if (!empty($genre)): ?>
                <div class="active-filter-tag">
                    Genre: <?php echo htmlspecialchars($genre); ?>
                    <span class="remove" onclick="removeFilter('genre')">×</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($year)): ?>
                <div class="active-filter-tag">
                    Year: <?php echo htmlspecialchars($year); ?>
                    <span class="remove" onclick="removeFilter('year')">×</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($tag)): ?>
                <div class="active-filter-tag">
                    Tag: <?php echo htmlspecialchars($tag); ?>
                    <span class="remove" onclick="removeFilter('tag')">×</span>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <!-- Genres Filter -->
            <div class="filter-dropdown">
                <button class="filter-btn <?php echo !empty($genre) ? 'active' : ''; ?>" onclick="toggleDropdown('genresDropdown')">
                    📁 Genres
                </button>
                <div class="filter-dropdown-menu" id="genresDropdown">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['genre' => ''])); ?>">All Genres</a>
                    <?php foreach ($genres as $g): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['genre' => $g])); ?>">
                            <?php echo htmlspecialchars($g); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Years Filter -->
            <div class="filter-dropdown">
                <button class="filter-btn <?php echo !empty($year) ? 'active' : ''; ?>" onclick="toggleDropdown('yearsDropdown')">
                    📅 Years
                </button>
                <div class="filter-dropdown-menu" id="yearsDropdown">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['year' => ''])); ?>">All Years</a>
                    <?php foreach ($years as $y): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['year' => $y])); ?>">
                            <?php echo htmlspecialchars($y); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tags Filter -->
            <div class="filter-dropdown">
                <button class="filter-btn <?php echo !empty($tag) ? 'active' : ''; ?>" onclick="toggleDropdown('tagsDropdown')">
                    🏷️ Tags
                </button>
                <div class="filter-dropdown-menu" id="tagsDropdown">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['tag' => ''])); ?>">All Tags</a>
                    <?php foreach ($tags as $t): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['tag' => $t])); ?>">
                            <?php echo htmlspecialchars($t); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Movies Grid -->
        <?php if (!empty($movies)): ?>
            <div class="movie-grid grid">
                <?php foreach ($movies as $movie): ?>
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
            <div class="no-results">
                <p>No movies found matching your criteria. Try adjusting your filters! 🎬</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>

    <script>
        // Toggle dropdown menus
        function toggleDropdown(id) {
            event.stopPropagation();
            const dropdown = document.getElementById(id);
            const isOpen = dropdown.classList.contains('open');
            
            // Close all dropdowns
            document.querySelectorAll('.filter-dropdown-menu').forEach(menu => {
                menu.classList.remove('open');
            });
            
            // Toggle current dropdown
            if (!isOpen) {
                dropdown.classList.add('open');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.filter-dropdown-menu').forEach(menu => {
                menu.classList.remove('open');
            });
        });

        // Prevent dropdown from closing when clicking inside
        document.querySelectorAll('.filter-dropdown-menu').forEach(menu => {
            menu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Remove filter function
        function removeFilter(filterType) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterType);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
