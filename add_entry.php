<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login to add entries';
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $tags = sanitizeInput($_POST['tags'] ?? '');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Handle image upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['image']['type'], $allowedTypes)) {
                    move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);
                    $imagePath = $uploadPath;
                } else {
                    $error = 'Invalid image format. Allowed: JPEG, PNG, GIF, WEBP';
                }
            }
            
            if (empty($error)) {
                // Insert entry
                $stmt = $db->prepare("
                    INSERT INTO portfolio_entries 
                    (user_id, title, category, description, tags, image_path, is_public) 
                    VALUES 
                    (:user_id, :title, :category, :description, :tags, :image_path, :is_public)
                ");
                
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':title' => $title,
                    ':category' => $category,
                    ':description' => $description,
                    ':tags' => $tags,
                    ':image_path' => $imagePath,
                    ':is_public' => $isPublic
                ]);
                
                $_SESSION['success'] = 'Entry added successfully!';
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Failed to add entry: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entry | Portfolio Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background: #f6f3f0;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        .app {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.06);
            border: 1px solid #ede8e0;
        }
        
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f6f3f0;
        }
        
        .app-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f1c17;
        }
        
        .app-header a {
            color: #7b756b;
            text-decoration: none;
            transition: 0.2s;
        }
        
        .app-header a:hover {
            color: #1f1c17;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #2d2a24;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #ede8e0;
            border-radius: 14px;
            font-size: 0.95rem;
            transition: 0.2s;
            background: #fcfaf8;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2d2a24;
            background: white;
            box-shadow: 0 0 0 4px rgba(45,42,36,0.06);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input[type="file"] {
            padding: 0.5rem;
            background: #fcfaf8;
        }
        
        .form-group .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .form-group .checkbox-group input[type="checkbox"] {
            width: auto;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            background: #2d2a24;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .btn-primary:hover {
            background: #1f1c17;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45,42,36,0.15);
        }
        
        .btn-secondary {
            width: 100%;
            padding: 0.8rem;
            background: #ede8e0;
            color: #1f1c17;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: #d6cec2;
        }
        
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fde8e5;
            color: #c0392b;
            border: 1px solid #f5d0cc;
        }
        
        .alert-success {
            background: #e3f0e8;
            color: #2d7d46;
            border: 1px solid #c5e0d3;
        }
        
        @media (max-width: 600px) {
            .app { padding: 1.5rem; border-radius: 20px; }
            .app-header h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="app-header">
            <h1><i class="fas fa-plus-circle" style="color: #2d2a24;"></i> Add Entry</h1>
            <a href="index.php"><i class="fas fa-times"></i> Cancel</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" placeholder="Enter project title" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="e.g., Digital Art, 3D, Animation">
            </div>
            
            <div class="form-group">
                <label for="tags">Tags (comma separated)</label>
                <input type="text" id="tags" name="tags" placeholder="e.g., cyberpunk, neon, digital">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe your project..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Artwork Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="is_public" name="is_public" checked>
                    <label for="is_public" style="margin-bottom: 0;">Make this entry public</label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Save Entry
            </button>
        </form>
        
        <button onclick="window.location.href='index.php'" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Portfolio
        </button>
    </div>
</body>
</html>