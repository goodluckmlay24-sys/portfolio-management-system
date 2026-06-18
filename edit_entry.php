<?php
session_start();
require_once 'config/database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get entry
    $stmt = $db->prepare("SELECT * FROM portfolio_entries WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $entryId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $entry = $stmt->fetch();
    
    if (!$entry) {
        header('Location: index.php');
        exit;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $tags = sanitizeInput($_POST['tags'] ?? '');
        
        if (empty($title)) {
            $error = 'Title is required';
        } else {
            $stmt = $db->prepare("
                UPDATE portfolio_entries 
                SET title = :title, category = :category, description = :description, tags = :tags 
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':title' => $title,
                ':category' => $category,
                ':description' => $description,
                ':tags' => $tags,
                ':id' => $entryId,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = 'Entry updated successfully';
            header('Location: index.php');
            exit;
        }
    }
    
} catch (Exception $e) {
    $error = 'Failed to load entry: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Entry | Portfolio Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f6f3f0; padding: 2rem; display: flex; justify-content: center; }
        .container { max-width: 600px; width: 100%; background: white; border-radius: 28px; padding: 2.5rem; box-shadow: 0 20px 60px rgba(0,0,0,0.06); }
        h1 { font-size: 1.8rem; color: #1f1c17; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.6rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 500; color: #2d2a24; margin-bottom: 0.3rem; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 0.7rem 1rem; border: 2px solid #ede8e0; border-radius: 14px; font-size: 0.95rem; background: #fcfaf8;
        }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #2d2a24; background: white; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .btn { padding: 0.8rem; width: 100%; border: none; border-radius: 14px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: #2d2a24; color: white; }
        .btn-primary:hover { background: #1f1c17; }
        .btn-secondary { background: #ede8e0; color: #1f1c17; margin-top: 0.5rem; }
        .btn-secondary:hover { background: #d6cec2; }
        .alert { padding: 0.8rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-error { background: #fde8e5; color: #c0392b; border: 1px solid #f5d0cc; }
        .back-link { display: inline-block; margin-top: 1rem; color: #7b756b; text-decoration: none; }
        .back-link:hover { color: #2d2a24; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-edit"></i> Edit Entry</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($entry['category']); ?>">
            </div>
            <div class="form-group">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($entry['tags']); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($entry['description']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Entry</button>
        </form>
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Portfolio</a>
    </div>
</body>
</html>