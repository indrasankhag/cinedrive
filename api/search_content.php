<?php
// Set JSON header
header('Content-Type: application/json');

// Include database connection
require_once '../admin/config.php';

// Get search parameters from URL
$term = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'movie';

// Validate inputs
if (empty($term)) {
    echo json_encode(['status' => 'error', 'message' => 'Search term is required', 'results' => []]);
    exit();
}

// Validate type
if (!in_array($type, ['movie', 'series'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid content type', 'results' => []]);
    exit();
}

$results = [];

try {
    // Escape search term for LIKE query
    $search_term = '%' . $conn->real_escape_string($term) . '%';
    
    if ($type === 'movie') {
        // Search movies table
        $sql = "SELECT id, title, release_year FROM movies WHERE title LIKE ? ORDER BY title ASC LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_term);
    } else {
        // Search series table
        $sql = "SELECT id, title, release_year FROM series WHERE title LIKE ? ORDER BY title ASC LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_term);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'release_year' => $row['release_year']
            ];
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'results' => []
    ]);
}

$conn->close();
?>
