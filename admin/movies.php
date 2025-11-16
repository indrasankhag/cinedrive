<?php
require_once 'config.php';
check_login();

// Connect to videos database
$videos_conn = new mysqli('localhost', 'sinhbtve_smovies_user', 'Gaiya#@!$@!', 'sinhbtve_sinhalamovies');

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT cover_image FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && !empty($result['cover_image'])) {
        $filename = basename($result['cover_image']);
        if (file_exists('../uploads/' . $filename)) {
            unlink('../uploads/' . $filename);
        }
    }
    $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: movies.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    $genre = $_POST['genre'];
    $video_id = intval($_POST['video_id']); // Now using video ID from videos table
    $collection_id = !empty($_POST['collection_id']) ? intval($_POST['collection_id']) : NULL;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $cover_image_path = $_POST['existing_cover_image'] ?? '';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_name = uniqid() . '_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_path = BASE_URL . '/uploads/' . $image_name; 
        }
    }

    if ($id > 0) { // Update
        $stmt = $conn->prepare("UPDATE movies SET title = ?, release_date = ?, genre = ?, cover_image = ?, video_url = ?, collection_id = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $title, $release_date, $genre, $cover_image_path, $video_id, $collection_id, $id);
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO movies (title, release_date, genre, cover_image, video_url, collection_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $release_date, $genre, $cover_image_path, $video_id, $collection_id);
    }
    $stmt->execute();
    header('Location: movies.php');
    exit;
}

$edit_movie = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_movie = $stmt->get_result()->fetch_assoc();
}

$movies = $conn->query("SELECT m.*, c.title as collection_title FROM movies m LEFT JOIN collections c ON m.collection_id = c.id ORDER BY m.created_at DESC");
$collections = $conn->query("SELECT id, title FROM collections ORDER BY title ASC");

// Get available videos from videos table
$available_videos = $videos_conn->query("SELECT id, tg_message_id, caption, duration FROM videos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - CineDrive</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Manage Movies</h1>
        </header>

        <section class="form-container">
            <h2><?php echo $edit_movie ? 'Edit Movie' : 'Add New Movie'; ?></h2>
            <form method="POST" action="movies.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_movie['id'] ?? ''; ?>">
                <input type="hidden" name="existing_cover_image" value="<?php echo htmlspecialchars($edit_movie['cover_image'] ?? ''); ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_movie['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="text" name="release_date" value="<?php echo htmlspecialchars($edit_movie['release_date'] ?? ''); ?>" placeholder="e.g. Nov. 16, 2001" required>
                </div>
                <div class="form-group">
                    <label for="genre">Genre (comma-separated)</label>
                    <input type="text" name="genre" value="<?php echo htmlspecialchars($edit_movie['genre'] ?? ''); ?>" placeholder="e.g. Action,Adventure,Sci-Fi" required>
                </div>
                <div class="form-group">
                    <label for="video_id">Select Video from Telegram</label>
                    <select name="video_id" required>
                        <option value="">-- Choose Video --</option>
                        <?php 
                        mysqli_data_seek($available_videos, 0);
                        while($v = $available_videos->fetch_assoc()): 
                            $selected = (isset($edit_movie['video_url']) && $edit_movie['video_url'] == $v['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $selected; ?>>
                                Video ID: <?php echo $v['id']; ?> | 
                                Msg: <?php echo $v['tg_message_id']; ?> | 
                                Duration: <?php echo gmdate("i:s", $v['duration']); ?>
                                <?php echo $v['caption'] ? ' | ' . substr($v['caption'], 0, 30) : ''; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <p style="font-size: 0.85rem; color: #7f8c8d; margin-top: 5px;">
                        Videos are automatically saved when you upload to your Telegram channel
                    </p>
                </div>
                <div class="form-group">
                    <label for="collection_id">Collection</label>
                    <select name="collection_id">
                        <option value="">None</option>
                        <?php mysqli_data_seek($collections, 0); while($c = $collections->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($edit_movie['collection_id']) && $edit_movie['collection_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*">
                    <?php if (!empty($edit_movie['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($edit_movie['cover_image']); ?>" alt="Cover" width="100" style="margin-top: 10px;">
                    <?php endif; ?>
                </div>

                <button type="submit"><?php echo $edit_movie ? 'Update Movie' : 'Add Movie'; ?></button>
                <?php if ($edit_movie): ?>
                    <a href="movies.php" class="cancel-btn">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-container">
            <h2>Existing Movies</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Release Date</th>
                        <th>Genre</th>
                        <th>Video ID</th>
                        <th>Collection</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($movie = $movies->fetch_assoc()): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($movie['cover_image']); ?>" alt="Cover" width="50"></td>
                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                        <td><?php echo htmlspecialchars($movie['release_date']); ?></td>
                        <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                        <td><?php echo htmlspecialchars($movie['video_url']); ?></td>
                        <td><?php echo htmlspecialchars($movie['collection_title'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="movies.php?edit=<?php echo $movie['id']; ?>">Edit</a>
                            <a href="movies.php?delete=<?php echo $movie['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>