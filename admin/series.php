<?php
require_once 'config.php';
check_login();

// Connect to videos database
$videos_conn = new mysqli('localhost', 'sinhbtve_smovies_user', 'Gaiya#@!$@!', 'sinhbtve_sinhalamovies');

// Page logic (keeping your existing logic)
$managing_episodes = false;
$editing_episode = false;
$current_series = null;
$episodes = [];
$all_series = null;
$edit_episode_data = null;
$edit_series = null;

if (isset($_GET['manage_episodes'])) {
    $managing_episodes = true;
    $series_id = intval($_GET['manage_episodes']);
    
    $stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->bind_param("i", $series_id);
    $stmt->execute();
    $current_series = $stmt->get_result()->fetch_assoc();

    if (isset($_GET['edit_episode'])) {
        $editing_episode = true;
        $episode_id = intval($_GET['edit_episode']);
        $stmt_ep = $conn->prepare("SELECT * FROM episodes WHERE id = ? AND series_id = ?");
        $stmt_ep->bind_param("ii", $episode_id, $series_id);
        $stmt_ep->execute();
        $edit_episode_data = $stmt_ep->get_result()->fetch_assoc();
    }

    $stmt = $conn->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY season_number ASC, episode_number ASC");
    $stmt->bind_param("i", $series_id);
    $stmt->execute();
    $episodes_result = $stmt->get_result();
    while ($row = $episodes_result->fetch_assoc()) {
        $episodes[] = $row;
    }
} else {
    if (isset($_GET['edit_series'])) {
        $id = intval($_GET['edit_series']);
        $stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $edit_series = $stmt->get_result()->fetch_assoc();
    }
    $all_series = $conn->query("SELECT * FROM series ORDER BY created_at DESC");
}

// Handle Add/Edit Series
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['series_action'])) {
    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    $genre = $_POST['genre'];
    $description = $_POST['description'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $cover_image_path = $_POST['existing_cover_image'] ?? '';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_name = uniqid() . '_series_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_path = BASE_URL . '/uploads/' . $image_name;
        }
    }
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE series SET title = ?, release_date = ?, genre = ?, description = ?, cover_image = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $release_date, $genre, $description, $cover_image_path, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO series (title, release_date, genre, description, cover_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $release_date, $genre, $description, $cover_image_path);
    }
    $stmt->execute();
    header('Location: series.php');
    exit;
}

// Handle Add/Edit Episode (UPDATED to use video ID)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['episode_action'])) {
    $series_id = intval($_POST['series_id']);
    $season_number = intval($_POST['season_number']);
    $episode_number = intval($_POST['episode_number']);
    $title = $_POST['title'];
    $video_id = intval($_POST['video_id']); // Changed from video_url to video_id
    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    
    $thumb_image_path = $_POST['existing_thumb_image'] ?? '';

    if (isset($_FILES['thumb_image']) && $_FILES['thumb_image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_name = uniqid() . '_thumb_' . basename($_FILES["thumb_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["thumb_image"]["tmp_name"], $target_file)) {
            $thumb_image_path = BASE_URL . '/uploads/' . $image_name;
        }
    }

    if ($episode_id > 0) {
        $stmt = $conn->prepare("UPDATE episodes SET season_number = ?, episode_number = ?, title = ?, video_url = ?, thumb_image = ? WHERE id = ? AND series_id = ?");
        $stmt->bind_param("iisisii", $season_number, $episode_number, $title, $video_id, $thumb_image_path, $episode_id, $series_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO episodes (series_id, season_number, episode_number, title, video_url, thumb_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisis", $series_id, $season_number, $episode_number, $title, $video_id, $thumb_image_path);
    }
    $stmt->execute();
    header('Location: series.php?manage_episodes=' . $series_id);
    exit;
}

// Handle Delete Series
if (isset($_GET['delete_series'])) {
    $id = intval($_GET['delete_series']);
    $stmt = $conn->prepare("DELETE FROM series WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: series.php');
    exit;
}

// Handle Delete Episode
if (isset($_GET['delete_episode'])) {
    $id = intval($_GET['delete_episode']);
    $series_id = intval($_GET['series_id']);
    $stmt = $conn->prepare("DELETE FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: series.php?manage_episodes=' . $series_id);
    exit;
}

// Get available videos
$available_videos = $videos_conn->query("SELECT id, tg_message_id, caption, duration FROM videos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage TV Series - CineDrive</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1><?php echo $managing_episodes ? 'Manage Episodes for "' . htmlspecialchars($current_series['title']) . '"' : 'Manage TV Series'; ?></h1>
            <?php if ($managing_episodes): ?>
                <a href="series.php" class="back-link">&larr; Back to All Series</a>
            <?php endif; ?>
        </header>

        <?php if ($managing_episodes): ?>
            <section class="form-container">
                <h2><?php echo $editing_episode ? 'Edit Episode' : 'Add New Episode'; ?></h2>
                <form method="POST" action="series.php?manage_episodes=<?php echo $current_series['id']; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="episode_action" value="1">
                    <input type="hidden" name="series_id" value="<?php echo $current_series['id']; ?>">
                    <input type="hidden" name="episode_id" value="<?php echo $edit_episode_data['id'] ?? ''; ?>">
                    <input type="hidden" name="existing_thumb_image" value="<?php echo htmlspecialchars($edit_episode_data['thumb_image'] ?? ''); ?>">
                    
                    <div class="form-group">
                        <label for="season_number">Season Number</label>
                        <input type="number" name="season_number" value="<?php echo htmlspecialchars($edit_episode_data['season_number'] ?? '1'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="episode_number">Episode Number</label>
                        <input type="number" name="episode_number" value="<?php echo htmlspecialchars($edit_episode_data['episode_number'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title">Episode Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_episode_data['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="video_id">Select Video from Telegram</label>
                        <select name="video_id" required>
                            <option value="">-- Choose Video --</option>
                            <?php 
                            mysqli_data_seek($available_videos, 0);
                            while($v = $available_videos->fetch_assoc()): 
                                $selected = (isset($edit_episode_data['video_url']) && $edit_episode_data['video_url'] == $v['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo $selected; ?>>
                                    Video ID: <?php echo $v['id']; ?> | 
                                    Msg: <?php echo $v['tg_message_id']; ?> | 
                                    Duration: <?php echo gmdate("i:s", $v['duration']); ?>
                                    <?php echo $v['caption'] ? ' | ' . substr($v['caption'], 0, 30) : ''; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="thumb_image">Thumbnail Image</label>
                        <input type="file" name="thumb_image" accept="image/*">
                        <?php if (!empty($edit_episode_data['thumb_image'])): ?>
                            <img src="<?php echo htmlspecialchars($edit_episode_data['thumb_image']); ?>" width="100" style="margin-top:10px;">
                        <?php endif; ?>
                    </div>
                    <button type="submit"><?php echo $editing_episode ? 'Update Episode' : 'Add Episode'; ?></button>
                    <?php if ($editing_episode): ?>
                        <a href="series.php?manage_episodes=<?php echo $current_series['id']; ?>" class="cancel-btn">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </section>
            
            <section class="table-container">
                <h2>Existing Episodes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>S#</th>
                            <th>E#</th>
                            <th>Title</th>
                            <th>Thumbnail</th>
                            <th>Video ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($episodes as $ep): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ep['season_number']); ?></td>
                            <td><?php echo htmlspecialchars($ep['episode_number']); ?></td>
                            <td><?php echo htmlspecialchars($ep['title']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($ep['thumb_image']); ?>" width="100"></td>
                            <td><?php echo htmlspecialchars($ep['video_url']); ?></td>
                            <td>
                                <a href="series.php?manage_episodes=<?php echo $current_series['id']; ?>&edit_episode=<?php echo $ep['id']; ?>">Edit</a>
                                <a href="series.php?delete_episode=<?php echo $ep['id']; ?>&series_id=<?php echo $current_series['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

        <?php else: ?>
            <!-- Series Management Form (keeping your existing code) -->
            <section class="form-container">
                <h2><?php echo $edit_series ? 'Edit Series' : 'Add New Series'; ?></h2>
                <form method="POST" action="series.php" enctype="multipart/form-data">
                    <input type="hidden" name="series_action" value="1">
                    <input type="hidden" name="id" value="<?php echo $edit_series['id'] ?? ''; ?>">
                    <input type="hidden" name="existing_cover_image" value="<?php echo htmlspecialchars($edit_series['cover_image'] ?? ''); ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_series['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Release Date</label>
                        <input type="text" name="release_date" value="<?php echo htmlspecialchars($edit_series['release_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Genre (comma-separated)</label>
                        <input type="text" name="genre" value="<?php echo htmlspecialchars($edit_series['genre'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($edit_series['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Cover Image</label>
                        <input type="file" name="cover_image" accept="image/*">
                        <?php if (!empty($edit_series['cover_image'])): ?>
                            <img src="<?php echo htmlspecialchars($edit_series['cover_image']); ?>" width="100" style="margin-top:10px;">
                        <?php endif; ?>
                    </div>
                    <button type="submit"><?php echo $edit_series ? 'Update Series' : 'Add Series'; ?></button>
                    <?php if ($edit_series): ?> <a href="series.php" class="cancel-btn">Cancel Edit</a> <?php endif; ?>
                </form>
            </section>
            
            <section class="table-container">
                <h2>Existing Series</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Release Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_series) : while($s = $all_series->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($s['cover_image']); ?>" width="50"></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars($s['release_date']); ?></td>
                            <td>
                                <a href="series.php?manage_episodes=<?php echo $s['id']; ?>">Episodes</a>
                                <a href="series.php?edit_series=<?php echo $s['id']; ?>">Edit</a>
                                <a href="series.php?delete_series=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>