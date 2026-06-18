<?php
session_start();
require_once 'config/database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$fullName = $_SESSION['full_name'] ?? '';

$stats = [];
$monthlyViews = [];
$topEntries = [];
$recentActivity = [];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get total stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(views) as total_views,
            SUM(likes) as total_likes,
            AVG(views) as avg_views
        FROM portfolio_entries
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $stats = $stmt->fetch();
    
    // Get monthly views (last 12 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as entries_count,
            SUM(views) as views_count
        FROM portfolio_entries
        WHERE user_id = :user_id
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $monthlyViews = $stmt->fetchAll();
    
    // Get top performing entries
    $stmt = $db->prepare("
        SELECT id, title, category, views, likes, created_at
        FROM portfolio_entries
        WHERE user_id = :user_id
        ORDER BY views DESC
        LIMIT 5
    ");
    $stmt->execute([':user_id' => $userId]);
    $topEntries = $stmt->fetchAll();
    
    // Get recent activity (last 10 entries)
    $stmt = $db->prepare("
        SELECT id, title, created_at, views
        FROM portfolio_entries
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $userId]);
    $recentActivity = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load analytics: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Portfolio Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f6f3f0; padding: 2rem 1.5rem; }
        .app { max-width: 1200px; margin: 0 auto; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ede8e0;
        }
        .header h1 {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #1f1c17;
        }
        .header h1 i { background: #2d7d46; color: white; padding: 0.4rem 0.7rem; border-radius: 40px; }
        
        .btn {
            padding: 0.7rem 1.6rem;
            border-radius: 40px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }
        .btn-secondary { background: #ede8e0; color: #1f1c17; }
        .btn-secondary:hover { background: #d6cec2; }
        .btn-primary { background: #2d2a24; color: white; }
        .btn-primary:hover { background: #1f1c17; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            border: 1px solid #ede8e0;
            text-align: center;
            transition: 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d2a24;
        }
        .stat-label {
            color: #7b756b;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }
        .stat-icon {
            font-size: 2rem;
            color: #2d7d46;
            margin-bottom: 0.5rem;
        }
        
        .section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ede8e0;
        }
        .section h2 {
            font-size: 1.3rem;
            color: #1f1c17;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section h2 i { color: #2d7d46; }
        
        .chart-container {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            height: 200px;
            padding: 1rem 0;
            overflow-x: auto;
        }
        .chart-bar {
            flex: 1;
            min-width: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
        }
        .chart-bar .bar {
            width: 100%;
            max-width: 50px;
            background: #2d7d46;
            border-radius: 4px 4px 0 0;
            transition: 0.3s;
            min-height: 4px;
        }
        .chart-bar .bar:hover { opacity: 0.7; }
        .chart-bar .label {
            font-size: 0.7rem;
            color: #7b756b;
            text-align: center;
        }
        .chart-bar .value {
            font-size: 0.7rem;
            font-weight: 600;
            color: #2d2a24;
        }
        
        .entry-list {
            list-style: none;
        }
        .entry-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f6f3f0;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .entry-list li:last-child { border-bottom: none; }
        .entry-list .entry-title { font-weight: 500; color: #1f1c17; }
        .entry-list .entry-meta {
            display: flex;
            gap: 1rem;
            color: #7b756b;
            font-size: 0.85rem;
        }
        .entry-list .entry-meta span { display: flex; align-items: center; gap: 0.3rem; }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #7b756b;
        }
        .empty-state i { font-size: 3rem; color: #ede8e0; display: block; margin-bottom: 1rem; }
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header { flex-direction: column; align-items: stretch; }
            .header h1 { font-size: 1.5rem; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .chart-container { height: 150px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .entry-list li { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Analytics</h1>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Portfolio</a>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background: #fde8e5; color: #c0392b; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="stat-number"><?php echo $stats['total_entries'] ?? 0; ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-eye"></i></div>
                <div class="stat-number"><?php echo $stats['total_views'] ?? 0; ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-heart"></i></div>
                <div class="stat-number"><?php echo $stats['total_likes'] ?? 0; ?></div>
                <div class="stat-label">Total Likes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-number"><?php echo round($stats['avg_views'] ?? 0); ?></div>
                <div class="stat-label">Avg Views per Entry</div>
            </div>
        </div>
        
        <!-- Monthly Views Chart -->
        <div class="section">
            <h2><i class="fas fa-calendar-alt"></i> Monthly Views (Last 12 Months)</h2>
            <?php if (!empty($monthlyViews)): ?>
                <div class="chart-container">
                    <?php 
                    $maxViews = max(array_column($monthlyViews, 'views_count'));
                    $maxViews = $maxViews > 0 ? $maxViews : 1;
                    foreach (array_reverse($monthlyViews) as $month): 
                    ?>
                        <div class="chart-bar">
                            <div class="value"><?php echo $month['views_count']; ?></div>
                            <div class="bar" style="height: <?php echo ($month['views_count'] / $maxViews) * 150; ?>px;"></div>
                            <div class="label"><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No data available yet. Add some entries to see analytics!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Performing Entries -->
        <div class="section">
            <h2><i class="fas fa-trophy"></i> Top Performing Entries</h2>
            <?php if (!empty($topEntries)): ?>
                <ul class="entry-list">
                    <?php foreach ($topEntries as $entry): ?>
                        <li>
                            <span class="entry-title"><?php echo htmlspecialchars($entry['title']); ?></span>
                            <div class="entry-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($entry['category'] ?? 'Uncategorized'); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo $entry['views']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $entry['likes']; ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($entry['created_at'])); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-trophy"></i>
                    <p>No entries yet. Create your first entry to start tracking!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Activity -->
        <div class="section">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <?php if (!empty($recentActivity)): ?>
                <ul class="entry-list">
                    <?php foreach ($recentActivity as $entry): ?>
                        <li>
                            <span class="entry-title"><?php echo htmlspecialchars($entry['title']); ?></span>
                            <div class="entry-meta">
                                <span><i class="fas fa-eye"></i> <?php echo $entry['views']; ?> views</span>
                                <span><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($entry['created_at'])); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No activity yet. Start adding entries!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>