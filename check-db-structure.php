<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/config.php';

// Get column names from movies table
$result = $conn->query("DESCRIBE movies");
$movies_columns = [];
while ($row = $result->fetch_assoc()) {
    $movies_columns[] = $row['Field'];
}

// Get column names from series table
$result = $conn->query("DESCRIBE series");
$series_columns = [];
while ($row = $result->fetch_assoc()) {
    $series_columns[] = $row['Field'];
}

// Get column names from collections table
$result = $conn->query("DESCRIBE collections");
$collections_columns = [];
while ($row = $result->fetch_assoc()) {
    $collections_columns[] = $row['Field'];
}

echo json_encode([
    'movies_table_columns' => $movies_columns,
    'series_table_columns' => $series_columns,
    'collections_table_columns' => $collections_columns
], JSON_PRETTY_PRINT);

$conn->close();
?>