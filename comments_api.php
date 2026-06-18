<?php
// api/comments.php - Add, Get, Delete Comments
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/jwt.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$entryId = isset($_GET['entry_id']) ? (int)$_GET['entry_id'] : 0;

// --- GET COMMENTS ---
if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$entryId) {
        sendJsonResponse(['error' => 'Entry ID required'], 400);
    }
    
    $stmt = $db->prepare("
        SELECT c.*, u.username, u.full_name, u.avatar 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.entry_id = :entry_id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([':entry_id' => $entryId]);
    $comments = $stmt->fetchAll();
    
    sendJsonResponse(['success' => true, 'comments' => $comments]);
}

// --- ADD COMMENT ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $content = sanitizeInput($data['content'] ?? '');
    
    if (!$entryId || empty($content)) {
        sendJsonResponse(['error' => 'Entry ID and content are required'], 400);
    }
    
    $stmt = $db->prepare("INSERT INTO comments (entry_id, user_id, content) VALUES (:entry_id, :user_id, :content)");
    $result = $stmt->execute([
        ':entry_id' => $entryId,
        ':user_id' => $payload['user_id'],
        ':content' => $content
    ]);
    
    if ($result) {
        $commentId = $db->lastInsertId();
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.full_name, u.avatar 
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $commentId]);
        $comment = $stmt->fetch();
        
        sendJsonResponse(['success' => true, 'comment' => $comment]);
    } else {
        sendJsonResponse(['error' => 'Failed to add comment'], 500);
    }
}

// --- DELETE COMMENT ---
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        sendJsonResponse(['error' => 'Authentication required'], 401);
    }
    
    $payload = JWT::decode($token);
    if (!$payload) {
        sendJsonResponse(['error' => 'Invalid token'], 401);
    }
    
    $commentId = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
    
    if (!$commentId) {
        sendJsonResponse(['error' => 'Comment ID required'], 400);
    }
    
    // Check ownership
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = :id");
    $stmt->execute([':id' => $commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        sendJsonResponse(['error' => 'Comment not found'], 404);
    }
    
    if ($comment['user_id'] != $payload['user_id']) {
        sendJsonResponse(['error' => 'You can only delete your own comments'], 403);
    }
    
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $result = $stmt->execute([':id' => $commentId]);
    
    if ($result) {
        sendJsonResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendJsonResponse(['error' => 'Failed to delete comment'], 500);
    }
}

sendJsonResponse(['error' => 'Invalid action'], 400);
?>