<?php
require_once 'config.php';
check_login();
$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Placeholder function for Doodstream upload (to be implemented with API key later)
function uploadToDoodstream($file) {
    // TODO: Implement actual Doodstream API upload
    // This is a placeholder that returns a test ID
    // You will replace this with real Doodstream API code later
    
    // Simulate file processing
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Generate a test Doodstream ID
        $test_id = 'dood_test_' . uniqid();
        return $test_id;
    }
    
    return false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_shot'])) {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content_type = $_POST['content_type'];
    $linked_content_id = intval($_POST['linked_content_id']);
    
    // Validate basic inputs
    if (empty($title) || empty($content_type) || $linked_content_id <= 0) {
        $error = 'Please fill in all required fields and select a movie/series.';
    } else {
        // Handle file upload
        if (isset($_FILES['shot_video']) && $_FILES['shot_video']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded_file = $_FILES['shot_video'];
            
            // Validate file upload
            if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
                $error = 'File upload error. Please try again.';
            } else {
                // Validate file type (video only)
                $allowed_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/mkv'];
                $file_type = mime_content_type($uploaded_file['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = 'Invalid file type. Please upload a video file.';
                } else {
                    // Upload to Doodstream (placeholder function)
                    $doodstream_video_id = uploadToDoodstream($uploaded_file);
                    
                    if ($doodstream_video_id === false) {
                        $error = 'Failed to upload video to Doodstream.';
                    } else {
                        // Insert shot into database with description
                        $sql = "INSERT INTO shots (title, description, doodstream_video_id, linked_content_type, linked_content_id, admin_id) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssii", $title, $description, $doodstream_video_id, $content_type, $linked_content_id, $admin_id);
                        
                        if ($stmt->execute()) {
                            $message = "✅ Success! Shot added with Doodstream ID: {$doodstream_video_id}";
                        } else {
                            $error = 'Error adding shot to database: ' . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            }
        } else {
            $error = 'Please upload a video file.';
        }
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM shots WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $message = 'Shot deleted successfully!';
    } else {
        $error = 'Error deleting shot.';
    }
    $delete_stmt->close();
}

// Fetch all existing shots
$shots = [];
$shots_sql = "SELECT s.*, 
              CASE 
                  WHEN s.linked_content_type = 'movie' THEN m.title
                  WHEN s.linked_content_type = 'series' THEN ser.title
              END as content_title
              FROM shots s
              LEFT JOIN movies m ON s.linked_content_type = 'movie' AND s.linked_content_id = m.id
              LEFT JOIN series ser ON s.linked_content_type = 'series' AND s.linked_content_id = ser.id
              ORDER BY s.created_at DESC";
$shots_result = $conn->query($shots_sql);

if ($shots_result && $shots_result->num_rows > 0) {
    while ($row = $shots_result->fetch_assoc()) {
        $shots[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shots - Admin Panel</title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
        }
        
        .search-results div {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .search-results div:hover {
            background: #f5f5f5;
        }
        
        .selected-content {
            margin-top: 10px;
            padding: 10px;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 4px;
            display: none;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>🎬 Manage Shots</h1>
            <p>Create and manage TikTok-style movie/series shots</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add New Shot Form -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add New Shot</h2>
            </div>
            <div class="card-body">
                <form action="manage_shots.php" method="post" enctype="multipart/form-data">
                    <!-- Shot Title -->
                    <div class="form-group">
                        <label for="title">Shot Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="Enter shot title...">
                    </div>

                    <!-- Shot Description -->
                    <div class="form-group">
                        <label for="description">Shot Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter shot description/caption..."></textarea>
                    </div>

                    <!-- Doodstream Video ID -->
                    <div class="form-group">
                        <label for="doodstream_video_id">Doodstream Video ID *</label>
                        <input type="text" name="doodstream_video_id" id="doodstream_video_id" class="form-control" required placeholder="Enter Doodstream video ID (e.g., abc123xyz)">
                        <small>Enter the Doodstream video ID for this shot</small>
                    </div>

                    <!-- Content Type Radio Buttons -->
                    <div class="form-group">
                        <label>Content Type *</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="content_type" value="movie" checked onchange="clearSearch()">
                                <span>🎥 Movie</span>
                            </label>
                            <label>
                                <input type="radio" name="content_type" value="series" onchange="clearSearch()">
                                <span>📺 TV Series</span>
                            </label>
                        </div>
                    </div>

                    <!-- Search Movie/Series -->
                    <div class="form-group" style="position: relative;">
                        <label for="content-search">Search Movie/Series *</label>
                        <input type="text" id="content-search" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <div id="search-results" class="search-results"></div>
                        
                        <!-- Selected Content Display -->
                        <div id="selected-content" class="selected-content">
                            <strong>Selected:</strong> <span id="selected-title"></span>
                        </div>
                        
                        <!-- Hidden Input for Linked Content ID -->
                        <input type="hidden" name="linked_content_id" id="linked-content-id" required>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" name="submit_shot" class="btn btn-primary">
                            ➕ Add Shot
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Shots Table -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2>📋 Existing Shots</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($shots)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Video ID</th>
                                <th>Type</th>
                                <th>Linked Content</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shots as $shot): ?>
                                <tr>
                                    <td><?php echo $shot['id']; ?></td>
                                    <td><?php echo htmlspecialchars($shot['title']); ?></td>
                                    <td><code><?php echo htmlspecialchars($shot['doodstream_video_id']); ?></code></td>
                                    <td>
                                        <span class="badge badge-<?php echo $shot['linked_content_type'] === 'movie' ? 'blue' : 'purple'; ?>">
                                            <?php echo ucfirst($shot['linked_content_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($shot['content_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($shot['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $shot['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this shot?')">
                                            🗑️ Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No shots found. Add your first shot above!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('content-search');
        const searchResults = document.getElementById('search-results');
        const linkedContentId = document.getElementById('linked-content-id');
        const selectedContent = document.getElementById('selected-content');
        const selectedTitle = document.getElementById('selected-title');

        // Search functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            const contentType = document.querySelector('input[name="content_type"]:checked').value;

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Fetch search results
            fetch(`../api/search_content.php?q=${encodeURIComponent(query)}&type=${contentType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.results.length > 0) {
                        displayResults(data.results);
                    } else {
                        searchResults.innerHTML = '<div style="padding: 10px; color: #999;">No results found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        });

        // Display search results
        function displayResults(results) {
            searchResults.innerHTML = '';
            results.forEach(item => {
                const div = document.createElement('div');
                div.textContent = item.title + (item.release_year ? ` (${item.release_year})` : '');
                div.onclick = function() {
                    selectContent(item.id, item.title);
                };
                searchResults.appendChild(div);
            });
            searchResults.style.display = 'block';
        }

        // Select content
        function selectContent(id, title) {
            linkedContentId.value = id;
            selectedTitle.textContent = title;
            selectedContent.style.display = 'block';
            searchInput.value = '';
            searchResults.style.display = 'none';
        }

        // Clear search when content type changes
        function clearSearch() {
            searchInput.value = '';
            linkedContentId.value = '';
            selectedContent.style.display = 'none';
            searchResults.style.display = 'none';
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    </script>
</body>
</html>
