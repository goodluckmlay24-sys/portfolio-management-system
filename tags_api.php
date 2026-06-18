<?php
// api/tags.php - Get all tags, get entries by tag
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once '../config/database.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- GET ALL TAGS ---
if ($action === 'all' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("
        SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', numbers.n), ',', -1)) as tag
        FROM portfolio_entries
        CROSS JOIN (
            SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
            UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
        ) numbers
        WHERE tags IS NOT NULL AND tags != ''
        HAVING tag IS NOT NULL AND tag != ''
        ORDER BY tag
    ");
    $tags = $stmt->fetchAll();
    $tagList = array_column($tags, 'tag');
    
    sendJsonResponse(['success' => true, 'tags' => $tagList]);
}

// --- GET ENTRIES BY TAG ---
if ($action === 'by_tag' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $tag = isset($_GET['tag']) ? sanitizeInput($_GET['tag']) : '';
    
    if (empty($tag)) {
        sendJsonResponse(['error' => 'Tag parameter required'], 400);
    }
    
    $stmt = $db->prepare("
        SELECT * FROM portfolio_entries 
        WHERE tags LIKE :tag1 OR tags LIKE :tag2 OR tags LIKE :tag3
        ORDER BY created_at DESC
    ");
    $stmt->execute([
        ':tag1' => $tag . ',%',
        ':tag2' => '%,' . $tag . ',%',
        ':tag3' => '%,' . $tag
    ]);
    $entries = $stmt->fetchAll();
    
    sendJsonResponse(['success' => true, 'entries' => $entries]);
}

sendJsonResponse(['error' => 'Invalid action'], 400);
?>