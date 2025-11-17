<?php
// Include database connection
require_once 'admin/config.php';

// Fetch all shots from the shots table with like and comment counts (latest first)
$shots_feed = [];
$sql = "SELECT 
    s.id, 
    s.title, 
    s.doodstream_video_id,
    s.linked_content_type,
    s.linked_content_id,
    s.created_at,
    CASE 
        WHEN s.linked_content_type = 'movie' THEN m.title
        WHEN s.linked_content_type = 'series' THEN ser.title
    END as linked_content_title,
    CASE 
        WHEN s.linked_content_type = 'movie' THEN m.description
        WHEN s.linked_content_type = 'series' THEN ser.description
    END as description,
    (SELECT COUNT(*) FROM shot_likes WHERE shot_id = s.id) as like_count,
    (SELECT COUNT(*) FROM shot_comments WHERE shot_id = s.id) as comment_count
FROM shots s
LEFT JOIN movies m ON s.linked_content_type = 'movie' AND s.linked_content_id = m.id
LEFT JOIN series ser ON s.linked_content_type = 'series' AND s.linked_content_id = ser.id
ORDER BY s.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shots_feed[] = $row;
    }
}

// Backward compatibility: Keep $shots variable for existing code
$shots = $shots_feed;
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
        <?php if (!empty($shots_feed)): ?>
            <?php foreach ($shots_feed as $shot): ?>
                <div class="shot-item" data-shot-id="<?php echo $shot['id']; ?>">
                    <!-- Doodstream Video Embed -->
                    <iframe 
                        src="https://dood.li/e/<?php echo htmlspecialchars($shot['doodstream_video_id']); ?>" 
                        allowfullscreen
                        allow="autoplay"
                    ></iframe>
                    
                    <!-- Shot Details Overlay -->
                    <div class="shot-details">
                        <!-- Shot Title -->
                        <h1><?php echo htmlspecialchars($shot['title']); ?></h1>
                        
                        <!-- Shot Description/Caption -->
                        <p class="shot-description"><?php echo htmlspecialchars($shot['description']); ?></p>
                        
                        <!-- Dynamic Watch Button (Movie or Series) -->
                        <?php if ($shot['linked_content_type'] === 'movie'): ?>
                            <a href="movie-details.php?id=<?php echo $shot['linked_content_id']; ?>" class="watch-movie-btn">
                                <span>▶</span>
                                Watch Full Movie
                            </a>
                        <?php elseif ($shot['linked_content_type'] === 'series'): ?>
                            <a href="series-details.php?id=<?php echo $shot['linked_content_id']; ?>" class="watch-movie-btn">
                                <span>▶</span>
                                Watch Full TV Series
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- TikTok-Style Sidebar Actions -->
                    <div class="shot-sidebar-actions">
                        <!-- Like Button with data-shot-id -->
                        <button class="action-btn like-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="toggleLike(<?php echo $shot['id']; ?>, this)">
                            <span class="action-icon">❤️</span>
                            <span class="action-count like-count"><?php echo $shot['like_count']; ?></span>
                        </button>

                        <!-- Comment Button -->
                        <button class="action-btn comment-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="openComments(<?php echo $shot['id']; ?>)">
                            <span class="action-icon">💬</span>
                            <span class="action-count comment-count"><?php echo $shot['comment_count']; ?></span>
                        </button>

                        <!-- Favorite Button -->
                        <button class="action-btn favorite-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="toggleFavorite(<?php echo $shot['id']; ?>, this)">
                            <span class="action-icon">⭐</span>
                            <span class="action-label">Save</span>
                        </button>

                        <!-- Share Button -->
                        <button class="action-btn share-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="shareShot(<?php echo $shot['id']; ?>, '<?php echo htmlspecialchars($shot['title']); ?>')">
                            <span class="action-icon">📤</span>
                            <span class="action-label">Share</span>
                        </button>
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

    <script>
        // Toggle Like/Unlike
        function toggleLike(shotId, button) {
            // Prevent default action
            if (event) {
                event.preventDefault();
            }
            
            const likeCountSpan = button.querySelector('.like-count');
            const currentCount = parseInt(likeCountSpan.textContent);
            
            // Send AJAX request to like_shot.php
            fetch('api/like_shot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `shot_id=${shotId}&user_id=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update UI based on action
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                        likeCountSpan.textContent = data.like_count;
                    } else {
                        button.classList.remove('liked');
                        likeCountSpan.textContent = data.like_count;
                    }
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Event delegation for like buttons (alternative approach)
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to all like buttons
            document.querySelectorAll('.like-button, .like-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    // Get shot ID from data attribute
                    const shotId = this.getAttribute('data-shot-id') || 
                                   this.closest('.shot-item').getAttribute('data-shot-id');
                    
                    if (!shotId) {
                        console.error('Shot ID not found');
                        return;
                    }
                    
                    const likeCountSpan = this.querySelector('.like-count');
                    
                    // Send POST request to API
                    fetch('api/like_shot.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `shot_id=${shotId}&user_id=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Toggle 'liked' class for red heart effect
                            if (data.action === 'liked') {
                                this.classList.add('liked');
                            } else {
                                this.classList.remove('liked');
                            }
                            
                            // Update like count text
                            if (likeCountSpan) {
                                likeCountSpan.textContent = data.like_count;
                            }
                        } else {
                            console.error('Error:', data.message);
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Failed to update like. Please try again.');
                    });
                });
            });
        });

        // Open Comments (placeholder)
        function openComments(shotId) {
            alert('Comments feature coming soon for Shot ID: ' + shotId);
            // TODO: Implement comments modal/drawer
        }

        // Toggle Favorite
        function toggleFavorite(shotId, button) {
            // Prevent default action
            if (event) {
                event.preventDefault();
            }
            
            // Send POST request to favorite_shot.php API
            fetch('api/favorite_shot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `shot_id=${shotId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Toggle 'favorited' class for yellow star effect
                    if (data.action === 'favorited') {
                        button.classList.add('favorited');
                    } else {
                        button.classList.remove('favorited');
                    }
                } else {
                    console.error('Error:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Failed to update favorite. Please try again.');
            });
        }

        // Share Shot
        function shareShot(shotId, title) {
            const url = window.location.origin + '/index.php#shot-' + shotId;
            
            // Check if Web Share API is available
            if (navigator.share) {
                navigator.share({
                    title: title + ' - CineDrive',
                    text: 'Check out this movie on CineDrive!',
                    url: url
                })
                .then(() => console.log('Shared successfully'))
                .catch((error) => console.log('Error sharing:', error));
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    prompt('Copy this link:', url);
                });
            }
        }
    </script>
</body>
</html>