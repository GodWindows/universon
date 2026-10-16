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

// Get all categories from database
function get_all_categories() {
    $conn = connect_database();
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, name, description FROM album_categories ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (env_type() == "dev") {
            error_log("Error fetching categories: " . $e->getMessage());
        }
        return [];
    }
}

$categories = get_all_categories();

echo json_encode([
    'success' => true,
    'categories' => $categories
]);
?>
