<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../util/functions.php';
require_once __DIR__ . '/../env_data.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSON only: a cross-site form cannot send this Content-Type (CSRF)
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
    http_response_code(415);
    echo json_encode(['error' => 'Content-Type must be application/json']);
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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$albumName = isset($input['album_name']) ? trim($input['album_name']) : '';
$categoryName = isset($input['category']) ? trim($input['category']) : '';
$albumData = isset($input['album_data']) ? $input['album_data'] : [];

// Validate inputs
if (empty($albumName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Album name is required']);
    exit;
}

// Validate category exists in database
if (!validate_category_exists($categoryName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

// Add album to category (this will handle album creation if needed)
$success = add_album_to_category($user['id'], $albumData, $categoryName);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Album added to category successfully',
        'category' => $categoryName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add album to category']);
}
?>

