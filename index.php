<?php
// Include database connection
require_once 'admin/config.php';

// Connect to the database using the config from admin/config.php
// Assuming $conn is the database connection variable from config.php
// If not, you may need to create the connection like:
// $conn = new mysqli($servername, $username, $password, $dbname);

// Fetch 10 random movies for Shots
$shots = [];
$sql = "SELECT id, title, description, video_url FROM movies ORDER BY RAND() LIMIT 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shots[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="CineDrive - Watch trending movie shots TikTok style">
    <title>Shots - CineDrive</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shots-style.css">
</head>
<body>
    <div class="shots-container">
        <?php if (!empty($shots)): ?>
            <?php foreach ($shots as $shot): ?>
                <div class="shot-item">
                    <!-- Doodstream Video Embed -->
                    <iframe 
                        src="https://dood.li/e/<?php echo htmlspecialchars($shot['video_url']); ?>" 
                        allowfullscreen
                        allow="autoplay"
                    ></iframe>
                    
                    <!-- Shot Details Overlay -->
                    <div class="shot-details">
                        <h1><?php echo htmlspecialchars($shot['title']); ?></h1>
                        <p><?php echo htmlspecialchars($shot['description']); ?></p>
                        <a href="movie-details.php?id=<?php echo $shot['id']; ?>" class="watch-movie-btn">
                            <span>▶</span>
                            Watch Movie
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-shots">
                <p>No shots available at the moment. Check back soon! 🎬</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>