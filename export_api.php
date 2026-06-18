<?php
// api/export.php - Export portfolio as PDF/CSV
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

$format = isset($_GET['format']) ? $_GET['format'] : 'json';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Get entries
$stmt = $db->prepare("
    SELECT * FROM portfolio_entries 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$entries = $stmt->fetchAll();

// --- JSON Export ---
if ($format === 'json') {
    header('Content-Disposition: attachment; filename="portfolio_export.json"');
    echo json_encode($entries, JSON_PRETTY_PRINT);
    exit;
}

// --- CSV Export ---
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="portfolio_export.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Category', 'Description', 'Tags', 'Views', 'Likes', 'Created At']);
    
    foreach ($entries as $entry) {
        fputcsv($output, [
            $entry['id'],
            $entry['title'],
            $entry['category'],
            $entry['description'],
            $entry['tags'],
            $entry['views'],
            $entry['likes'],
            $entry['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// --- HTML Export (simple printable version) ---
if ($format === 'html') {
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Portfolio Export</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }
        .entry { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .title { font-size: 24px; font-weight: bold; color: #2d2a24; }
        .category { background: #f0ece5; padding: 4px 12px; border-radius: 20px; display: inline-block; }
        .meta { color: #666; font-size: 14px; margin: 10px 0; }
        .tags { margin-top: 10px; }
        .tag { background: #e7e2db; padding: 2px 10px; border-radius: 12px; margin-right: 5px; display: inline-block; font-size: 12px; }
    </style>
    </head>
    <body>
    <h1>📁 Portfolio Export</h1>
    <p>Exported: <?php echo date('Y-m-d H:i:s'); ?></p>
    <?php foreach ($entries as $entry): ?>
    <div class="entry">
        <div class="title"><?php echo htmlspecialchars($entry['title']); ?></div>
        <div class="meta">
            <span class="category"><?php echo htmlspecialchars($entry['category']); ?></span>
            <span style="margin-left: 15px;">👁️ <?php echo $entry['views']; ?> views</span>
            <span style="margin-left: 15px;">❤️ <?php echo $entry['likes']; ?> likes</span>
        </div>
        <p><?php echo htmlspecialchars($entry['description']); ?></p>
        <div class="tags">
            <?php 
            if ($entry['tags']) {
                $tags = array_map('trim', explode(',', $entry['tags']));
                foreach ($tags as $tag) {
                    echo '<span class="tag">#' . htmlspecialchars($tag) . '</span>';
                }
            }
            ?>
        </div>
        <div style="color: #999; font-size: 12px; margin-top: 10px;">
            Created: <?php echo $entry['created_at']; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </body>
    </html>
    <?php
    exit;
}

sendJsonResponse(['error' => 'Invalid format. Available: json, csv, html'], 400);
?>