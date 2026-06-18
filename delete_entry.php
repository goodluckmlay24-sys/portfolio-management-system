<?php
session_start();
require_once 'config/database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($entryId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if entry belongs to user
        $stmt = $db->prepare("SELECT user_id FROM portfolio_entries WHERE id = :id");
        $stmt->execute([':id' => $entryId]);
        $entry = $stmt->fetch();
        
        if ($entry && $entry['user_id'] == $_SESSION['user_id']) {
            // Delete entry
            $stmt = $db->prepare("DELETE FROM portfolio_entries WHERE id = :id");
            $stmt->execute([':id' => $entryId]);
            $_SESSION['success'] = 'Entry deleted successfully';
        } else {
            $_SESSION['error'] = 'You do not have permission to delete this entry';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete entry: ' . $e->getMessage();
    }
}

header('Location: index.php');
exit;
?>