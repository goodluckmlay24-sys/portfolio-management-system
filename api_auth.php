<?php
// api/auth.php - Login, Register, Logout
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/jwt.php';

$db = Database::getInstance()->getConnection();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- REGISTER ---
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = sanitizeInput($data['username'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $fullName = sanitizeInput($data['full_name'] ?? '');
    
    // Validate
    if (empty($username) || empty($email) || empty($password)) {
        sendJsonResponse(['error' => 'Username, email, and password are required'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['error' => 'Invalid email format'], 400);
    }
    
    if (strlen($password) < 6) {
        sendJsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->execute([':username' => $username, ':email' => $email]);
    if ($stmt->fetch()) {
        sendJsonResponse(['error' => 'Username or email already exists'], 409);
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name) VALUES (:username, :email, :password, :full_name)");
    $result = $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':full_name' => $fullName
    ]);
    
    if ($result) {
        sendJsonResponse(['success' => true, 'message' => 'Registration successful! Please login.']);
    } else {
        sendJsonResponse(['error' => 'Registration failed'], 500);
    }
}

// --- LOGIN ---
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = sanitizeInput($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendJsonResponse(['error' => 'Username and password are required'], 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        sendJsonResponse(['error' => 'Invalid username or password'], 401);
    }
    
    // Generate JWT token
    $payload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ];
    $token = JWT::encode($payload);
    
    sendJsonResponse([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'bio' => $user['bio'],
            'avatar' => $user['avatar']
        ]
    ]);
}

// --- VERIFY TOKEN ---
if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        sendJsonResponse(['error' => 'Token required'], 401);
    }
    
    $payload = JWT::decode($token);
    if (!$payload) {
        sendJsonResponse(['error' => 'Invalid or expired token'], 401);
    }
    
    sendJsonResponse(['success' => true, 'user' => $payload]);
}

sendJsonResponse(['error' => 'Invalid action'], 400);
?>