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

// Get POST data: an existing album (album_id) or one picked in the search (album_data)
$input = json_decode(file_get_contents('php://input'), true);
$albumId = isset($input['album_id']) ? (int) $input['album_id'] : 0;
$albumData = isset($input['album_data']) && is_array($input['album_data']) ? $input['album_data'] : [];
$rating = isset($input['rating']) && $input['rating'] !== '' ? $input['rating'] : null;
$review = isset($input['review']) && is_string($input['review']) ? trim($input['review']) : '';

// Validate inputs: rating in half stars (1–10) or null, review ≤ 2000 characters
if ($albumId <= 0 && empty($albumData['album_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Album is required']);
    exit;
}
if ($rating !== null && (filter_var($rating, FILTER_VALIDATE_INT) === false || $rating < 1 || $rating > 10)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rating must be between 1 and 10']);
    exit;
}
$rating = $rating === null ? null : (int) $rating;
if (mb_strlen($review) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Review too long (max 2000 characters)']);
    exit;
}
if ($albumId <= 0 && $rating === null && $review === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Rating or review is required']);
    exit;
}

// Save, or delete when both are empty
$savedAlbumId = save_album_review($user['id'], $albumId, $albumData, $rating, $review);

if ($savedAlbumId) {
    echo json_encode([
        'success' => true,
        'album_id' => (int) $savedAlbumId,
        'rating' => $rating,
        'review' => $review
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to save review']);
}
?>
