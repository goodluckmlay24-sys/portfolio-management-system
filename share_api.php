<?php
// api/share.php - Generate share links
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once '../config/database.php';
require_once '../config/jwt.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- GENERATE SHARE LINK ---
if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        sendJsonResponse(['error' => 'Authentication required'], 401);
    }
    
    $payload = JWT::decode($token);
    if (!$payload) {
        sendJsonResponse(['error' => 'Invalid token'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $entryId = (int)($data['entry_id'] ?? 0);
    
    if (!$entryId) {
        sendJsonResponse(['error' => 'Entry ID required'], 400);
    }
    
    // Check ownership
    $stmt = $db->prepare("SELECT user_id FROM portfolio_entries WHERE id = :id");
    $stmt->execute([':id' => $entryId]);
    $entry = $stmt->fetch();
    
    if (!$entry || $entry['user_id'] != $payload['user_id']) {
        sendJsonResponse(['error' => 'You can only share your own entries'], 403);
    }
    
    // Generate share token
    $shareToken = generateShareToken();
    
    $stmt = $db->prepare("UPDATE portfolio_entries SET share_token = :token WHERE id = :id");
    $stmt->execute([':token' => $shareToken, ':id' => $entryId]);
    
    $shareUrl = "https://" . $_SERVER['HTTP_HOST'] . "/portfolio/view.php?token=" . $shareToken;
    
    sendJsonResponse([
        'success' => true,
        'share_url' => $shareUrl,
        'share_token' => $shareToken
    ]);
}

// --- GET SHARED ENTRY ---
if ($action === 'view' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : '';
    
    if (empty($token)) {
        sendJsonResponse(['error' => 'Share token required'], 400);
    }
    
    $stmt = $db->prepare("
        SELECT e.*, u.username, u.full_name 
        FROM portfolio_entries e
        LEFT JOIN users u ON e.user_id = u.id
        WHERE e.share_token = :token AND e.is_public = 1
    ");
    $stmt->execute([':token' => $token]);
    $entry = $stmt->fetch();
    
    if (!$entry) {
        sendJsonResponse(['error' => 'Entry not found or not public'], 404);
    }
    
    // Increment views
    $stmt = $db->prepare("UPDATE portfolio_entries SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => $entry['id']]);
    
    sendJsonResponse(['success' => true, 'entry' => $entry]);
}

sendJsonResponse(['error' => 'Invalid action'], 400);
?>