<?php
require_once __DIR__ . '/../env_data.php';
require_once __DIR__ . '/../util/functions.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is from your app URL
$allowed_origins = [
    'http://localhost:8000',
    $site_url,
    'http://127.0.0.1:8000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

$is_allowed = false;

// Check origin
if (in_array($origin, $allowed_origins)) {
    $is_allowed = true;
}

// Check referer as fallback
if (!$is_allowed && $referer) {
    foreach ($allowed_origins as $allowed_origin) {
        if (strpos($referer, $allowed_origin) === 0) {
            $is_allowed = true;
            break;
        }
    }
}

// If not allowed, return error
if (!$is_allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get search parameters
$query = $_GET['q'] ?? $_POST['q'] ?? '';
$type = $_GET['type'] ?? $_POST['type'] ?? 'album';
$limit = $_GET['limit'] ?? $_POST['limit'] ?? '8';

// Validate parameters
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term is required']);
    exit;
}

// Search Deezer API
function searchSpotify($query, $type, $limit) {
    $searchQuery = "\"" . $query . "\"";
    $url = 'https://api.deezer.com/search/album?' . http_build_query([
        'q' => $searchQuery,
        'limit' => $limit,
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

$deezer_data = searchSpotify($query, $type, $limit);

if (!$deezer_data) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to search Deezer']);
    exit;
}

// Convert Deezer response to iTunes-like format
$results = [];
if (isset($deezer_data['data'])) {
    foreach ($deezer_data['data'] as $album) {
        $artwork_url = '';
        if (isset($album['cover_medium'])) {
            $artwork_url = $album['cover_medium'];
        }
        
        $artist_name = '';
        if (isset($album['artist'])) {
            $artist_name = $album['artist']['name'];
        }
        
        $results[] = [
            'collectionName' => $album['title'] ?? '',
            'artistName' => $artist_name,
            'artworkUrl100' => $artwork_url,
            'collectionId' => $album['id'] ?? '',
            'artistId' => isset($album['artist']['id']) ? $album['artist']['id'] : '',
        ];
    }
}

$response = [
    'resultCount' => count($results),
    'results' => $results
];

echo json_encode($response);
?>
