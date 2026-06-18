<?php
session_start();
require_once 'config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$fullName = $_SESSION['full_name'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get entries based on filter
$entries = [];
try {
    $db = Database::getInstance()->getConnection();
    
    if ($filter === 'my' && $isLoggedIn) {
        // Show only user's entries
        $stmt = $db->prepare("
            SELECT e.*, u.username, u.full_name 
            FROM portfolio_entries e 
            LEFT JOIN users u ON e.user_id = u.id 
            WHERE e.user_id = :user_id 
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
    } else {
        // Show all entries
        $stmt = $db->query("
            SELECT e.*, u.username, u.full_name 
            FROM portfolio_entries e 
            LEFT JOIN users u ON e.user_id = u.id 
            ORDER BY e.created_at DESC
        ");
    }
    $entries = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f6f3f0; padding: 2rem 1.5rem; }
        .app { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
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
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #1f1c17;
        }
        .header h1 i { background: #2d2a24; color: white; padding: 0.4rem 0.7rem; border-radius: 40px; }
        
        /* Buttons */
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
        .btn-primary { background: #2d2a24; color: white; }
        .btn-primary:hover { background: #1f1c17; transform: translateY(-2px); }
        .btn-secondary { background: #ede8e0; color: #1f1c17; }
        .btn-secondary:hover { background: #d6cec2; }
        .btn-success { background: #2d7d46; color: white; }
        .btn-success:hover { background: #1f5c33; transform: translateY(-2px); }
        .btn-sm { padding: 0.4rem 1rem; font-size: 0.85rem; }
        
        /* User Menu */
        .user-menu { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #2d2a24;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
            flex-wrap: wrap;
        }
        .filter-bar .btn {
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
        }
        .filter-bar .btn.active {
            background: #2d2a24;
            color: white;
        }
        
        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.8rem;
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #ede8e0;
            transition: 0.25s;
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.06); }
        .card-icon {
            font-size: 2rem;
            color: #2d2a24;
            background: #f6f3f0;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            margin-bottom: 1rem;
        }
        .card-title { font-size: 1.2rem; font-weight: 600; color: #1f1c17; }
        .card-category {
            font-size: 0.75rem;
            color: #7b756b;
            background: #f6f3f0;
            padding: 0.1rem 0.8rem;
            border-radius: 20px;
            display: inline-block;
            margin: 0.3rem 0;
        }
        .card-desc { color: #4a443c; font-size: 0.9rem; line-height: 1.5; margin: 0.5rem 0; }
        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin: 0.5rem 0;
        }
        .card-tags .tag {
            background: #f6f3f0;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            color: #7b756b;
        }
        .card-meta {
            display: flex;
            justify-content: space-between;
            padding-top: 0.6rem;
            margin-top: 0.6rem;
            border-top: 1px solid #ede8e0;
            font-size: 0.75rem;
            color: #7b756b;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
        .card-meta .author { color: #4a443c; font-weight: 500; }
        .card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px solid #ede8e0;
        }
        .card-actions .action-btn {
            background: none;
            border: none;
            color: #7b756b;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0.2rem 0.6rem;
            border-radius: 16px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .card-actions .action-btn:hover { background: #f6f3f0; color: #1f1c17; }
        .card-actions .action-btn.delete:hover { background: #fde8e5; color: #c0392b; }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: #7b756b;
        }
        .empty-state i { font-size: 3.5rem; color: #ede8e0; display: block; margin-bottom: 1rem; }
        .empty-state p { font-size: 1.1rem; max-width: 400px; margin: 0 auto; }
        
        /* Alert messages */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success { background: #e3f0e8; color: #2d7d46; border: 1px solid #c5e0d3; }
        .alert-error { background: #fde8e5; color: #c0392b; border: 1px solid #f5d0cc; }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header { flex-direction: column; align-items: stretch; }
            .header h1 { font-size: 1.8rem; justify-content: center; }
            .user-menu { justify-content: center; }
            .filter-bar { justify-content: center; }
            .grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.4rem; }
            .btn { padding: 0.5rem 1rem; font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-palette"></i> Portfolio</h1>
            <div style="display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;">
                <?php if ($isLoggedIn): ?>
                    <a href="add_entry.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Entry</a>
                    <div class="user-menu">
                        <div class="avatar"><?php echo strtoupper(substr($fullName ?: $username, 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($fullName ?: $username); ?></span>
                        <a href="log_out.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Display messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary active' : 'btn-secondary'; ?>">
                <i class="fas fa-globe"></i> All Entries
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="?filter=my" class="btn <?php echo $filter === 'my' ? 'btn-primary active' : 'btn-secondary'; ?>">
                    <i class="fas fa-user"></i> My Entries
                </a>
                <a href="analytics.php" class="btn btn-success">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Portfolio Grid -->
        <div class="grid">
            <?php if (!empty($entries)): ?>
                <?php foreach ($entries as $entry): ?>
                    <div class="card">
                        <div class="card-icon"><i class="fas fa-image"></i></div>
                        <div class="card-title"><?php echo htmlspecialchars($entry['title']); ?></div>
                        <?php if (!empty($entry['category'])): ?>
                            <div class="card-category"><?php echo htmlspecialchars($entry['category']); ?></div>
                        <?php endif; ?>
                        <div class="card-desc"><?php echo htmlspecialchars($entry['description'] ?? 'No description'); ?></div>
                        <?php if (!empty($entry['tags'])): ?>
                            <div class="card-tags">
                                <?php foreach (array_map('trim', explode(',', $entry['tags'])) as $tag): ?>
                                    <span class="tag">#<?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($entry['full_name'] ?? $entry['username'] ?? 'Anonymous'); ?></span>
                            <span><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($entry['created_at'])); ?></span>
                            <span><i class="far fa-eye"></i> <?php echo $entry['views'] ?? 0; ?></span>
                        </div>
                        <?php if ($isLoggedIn && ($entry['user_id'] ?? 0) == $userId): ?>
                            <div class="card-actions">
                                <a href="edit_entry.php?id=<?php echo $entry['id']; ?>" class="action-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_entry.php?id=<?php echo $entry['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this entry?')">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas <?php echo $filter === 'my' ? 'fa-user' : 'fa-image'; ?>"></i>
                    <p>
                        <?php if ($filter === 'my'): ?>
                            You haven't added any entries yet. Click "Add Entry" to get started!
                        <?php else: ?>
                            No portfolio entries yet. <?php if ($isLoggedIn): ?>Click "Add Entry" to get started!<?php endif; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>