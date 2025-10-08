
<?php
// Database configuration
$host = 'localhost';
$dbname = 'home4paws_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Database connection failed: " . $e->getMessage()]);
    exit;
}

// Fetch new images and all current image IDs for rescued and adoptable animals
$lastRescuedId = isset($_GET['last_rescued_id']) ? (int)$_GET['last_rescued_id'] : 0;
$lastAdoptId = isset($_GET['last_adopt_id']) ? (int)$_GET['last_adopt_id'] : 0;
try {
    // Fetch new rescued images
    $stmt = $pdo->prepare("SELECT id, image_data FROM rescued_images WHERE id > ? ORDER BY created_at DESC");
    $stmt->execute([$lastRescuedId]);
    $rescuedImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all current rescued image IDs
    $stmt = $pdo->query("SELECT id FROM rescued_images ORDER BY created_at DESC");
    $rescuedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch new adoptable animals
    $stmt = $pdo->prepare("SELECT id, image_data FROM adoptable_animals WHERE id > ? ORDER BY created_at DESC");
    $stmt->execute([$lastAdoptId]);
    $adoptImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all current adoptable animal IDs
    $stmt = $pdo->query("SELECT id FROM adoptable_animals ORDER BY created_at DESC");
    $adoptIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode([
        'rescued_images' => $rescuedImages,
        'rescued_ids' => $rescuedIds,
        'adopt_images' => $adoptImages,
        'adopt_ids' => $adoptIds
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Query failed: " . $e->getMessage()]);
}
?>
