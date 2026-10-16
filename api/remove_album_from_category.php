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
$albumId = isset($input['album_id']) ? (int)$input['album_id'] : 0;
$categoryName = isset($input['category']) ? trim($input['category']) : '';

// Validate inputs
if ($albumId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid album ID']);
    exit;
}

// Validate category exists in database
if (!validate_category_exists($categoryName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

// Remove album from category
$success = remove_album_from_category($user['id'], $albumId, $categoryName);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Album removed from category successfully',
        'category' => $categoryName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to remove album from category']);
}
?>

