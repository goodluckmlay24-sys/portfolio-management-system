<?php
// api/analytics.php - Track and get analytics
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

// --- TRACK VIEW ---
if ($action === 'track' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $entryId = (int)($data['entry_id'] ?? 0);
    
    if (!$entryId) {
        sendJsonResponse(['error' => 'Entry ID required'], 400);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO analytics (entry_id, ip_address, user_agent, referer) 
        VALUES (:entry_id, :ip, :user_agent, :referer)
    ");
    $stmt->execute([
        ':entry_id' => $entryId,
        ':ip' => $ip,
        ':user_agent' => $userAgent,
        ':referer' => $referer
    ]);
    
    // Increment view count
    $stmt = $db->prepare("UPDATE portfolio_entries SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => $entryId]);
    
    sendJsonResponse(['success' => true]);
}

// --- GET STATS ---
if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        sendJsonResponse(['error' => 'Authentication required'], 401);
    }
    
    $payload = JWT::decode($token);
    if (!$payload) {
        sendJsonResponse(['error' => 'Invalid token'], 401);
    }
    
    $userId = $payload['user_id'];
    $period = isset($_GET['period']) ? $_GET['period'] : '30d';
    
    // Total views, likes, entries
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(views) as total_views,
            SUM(likes) as total_likes
        FROM portfolio_entries
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $stats = $stmt->fetch();
    
    // Views by month (last 12 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(viewed_at, '%Y-%m') as month,
            COUNT(*) as views_count
        FROM analytics a
        JOIN portfolio_entries e ON a.entry_id = e.id
        WHERE e.user_id = :user_id
        AND viewed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $monthlyViews = $stmt->fetchAll();
    
    // Top performing entries
    $stmt = $db->prepare("
        SELECT title, views, likes, created_at
        FROM portfolio_entries
        WHERE user_id = :user_id
        ORDER BY views DESC
        LIMIT 5
    ");
    $stmt->execute([':user_id' => $userId]);
    $topEntries = $stmt->fetchAll();
    
    sendJsonResponse([
        'success' => true,
        'stats' => $stats,
        'monthly_views' => $monthlyViews,
        'top_entries' => $topEntries
    ]);
}

sendJsonResponse(['error' => 'Invalid action'], 400);
?>