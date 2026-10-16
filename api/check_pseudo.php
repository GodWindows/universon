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
$pseudo = isset($input['pseudo']) ? trim($input['pseudo']) : '';

// Validate pseudo
if (empty($pseudo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pseudo cannot be empty']);
    exit;
}

if (strlen($pseudo) < 3 || strlen($pseudo) > 45) {
    http_response_code(400);
    echo json_encode(['error' => 'Pseudo must be between 3 and 45 characters']);
    exit;
}

// Same characters as the /@pseudo route: letters, digits, _ . -
if (!preg_match('/^[A-Za-z0-9_.-]+$/', $pseudo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pseudo can only contain letters, digits, _ . -']);
    exit;
}

// Check pseudo availability
$available = check_pseudo_availability($pseudo);

echo json_encode([
    'success' => true,
    'available' => $available,
    'pseudo' => $pseudo
]);
?>
