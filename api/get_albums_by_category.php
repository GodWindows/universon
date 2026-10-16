<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../util/functions.php';
require_once __DIR__ . '/../env_data.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is authenticated
if (!isset($_COOKIE['session_token']) || empty($_COOKIE['session_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user from session
$user = getUserFromSessionToken($_COOKIE['session_token']);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

// Get category from query parameters
$categoryName = isset($_GET['category']) ? trim($_GET['category']) : '';

// Validate category exists in database
if (!validate_category_exists($categoryName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

// Get albums by category
$albums = get_user_albums_by_category($user['id'], $categoryName);

echo json_encode([
    'success' => true,
    'category' => $categoryName,
    'albums' => $albums
]);
?>

