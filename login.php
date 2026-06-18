<?php
session_start();
require_once 'config/database.php';

// If already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$register_error = '';
$register_success = '';
$message = '';

// Check for logout message
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['register'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute([
                ':username' => $username,
                ':email' => $username
            ]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['login_time'] = time();
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $reg_username = sanitizeInput($_POST['reg_username'] ?? '');
    $reg_email = sanitizeInput($_POST['reg_email'] ?? '');
    $reg_password = $_POST['reg_password'] ?? '';
    $reg_fullname = sanitizeInput($_POST['reg_fullname'] ?? '');
    
    if (empty($reg_username) || empty($reg_email) || empty($reg_password)) {
        $register_error = 'All fields are required';
    } elseif (strlen($reg_password) < 6) {
        $register_error = 'Password must be at least 6 characters';
    } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Invalid email format';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([
                ':username' => $reg_username,
                ':email' => $reg_email
            ]);
            
            if ($stmt->fetch()) {
                $register_error = 'Username or email already exists';
            } else {
                $hashedPassword = password_hash($reg_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name) VALUES (:username, :email, :password, :full_name)");
                $stmt->execute([
                    ':username' => $reg_username,
                    ':email' => $reg_email,
                    ':password' => $hashedPassword,
                    ':full_name' => $reg_fullname
                ]);
                $register_success = 'Registration successful! Please login.';
            }
        } catch (Exception $e) {
            $register_error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login | Portfolio Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f6f3f0 0%, #e8e2d9 100%);
            padding: 1.5rem;
        }
        .login-container {
            background: white;
            border-radius: 28px;
            padding: 2.5rem;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header .logo {
            font-size: 2.8rem;
            background: #2d2a24;
            color: white;
            width: 70px;
            height: 70px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            margin-bottom: 1rem;
        }
        .login-header h1 { font-size: 1.8rem; color: #1f1c17; }
        .login-header p { color: #7b756b; margin-top: 0.3rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 500; color: #2d2a24; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .form-group .input-wrapper { position: relative; }
        .form-group .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #b1aba0;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            border: 2px solid #e2dbd2;
            border-radius: 14px;
            font-size: 0.95rem;
            background: #fcfaf8;
            transition: 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2d2a24;
            background: white;
            box-shadow: 0 0 0 4px rgba(45,42,36,0.06);
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
        .btn-primary:hover { background: #1f1c17; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(45,42,36,0.15); }
        .btn-primary:active { transform: scale(0.98); }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error { background: #fde8e5; color: #c0392b; border: 1px solid #f5d0cc; }
        .alert-success { background: #e3f0e8; color: #2d7d46; border: 1px solid #c5e0d3; }
        .alert-info { background: #e3e8f0; color: #2d5d7d; border: 1px solid #c5d0e0; }
        .register-toggle { text-align: center; margin-top: 1.2rem; font-size: 0.9rem; color: #7b756b; }
        .register-toggle a { color: #2d2a24; font-weight: 500; cursor: pointer; text-decoration: none; }
        .register-toggle a:hover { text-decoration: underline; }
        .register-form { display: none; }
        .register-form.active { display: block; }
        .login-form.hidden { display: none; }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #b1aba0;
            cursor: pointer;
            font-size: 1rem;
        }
        .password-toggle:hover { color: #2d2a24; }
        @media (max-width: 480px) {
            .login-container { padding: 1.8rem; border-radius: 20px; }
            .login-header h1 { font-size: 1.5rem; }
            .login-header .logo { width: 60px; height: 60px; font-size: 2.2rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo"><i class="fas fa-palette"></i></div>
            <h1>Portfolio Manager</h1>
            <p>Manage your creative work</p>
        </div>
        
        <!-- Display logout message -->
        <?php if ($message): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Display error messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Display registration success -->
        <?php if ($register_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $register_success; ?>
            </div>
        <?php endif; ?>
        
        <!-- Display registration error -->
        <?php if ($register_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $register_error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <div id="loginForm" class="login-form <?php echo isset($_POST['register']) ? 'hidden' : ''; ?>">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="register-toggle">
                Don't have an account? <a onclick="showRegister()">Create one</a>
            </div>
        </div>
        
        <!-- Register Form -->
        <div id="registerForm" class="register-form <?php echo isset($_POST['register']) || $register_error ? 'active' : ''; ?>">
            <form method="POST" action="">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label for="reg_username">Username *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="reg_username" name="reg_username" placeholder="Choose a username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="reg_email" name="reg_email" placeholder="Enter your email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_fullname">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="reg_fullname" name="reg_fullname" placeholder="Enter your full name">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_password">Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="reg_password" name="reg_password" placeholder="Min 6 characters" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('reg_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            <div class="register-toggle">
                Already have an account? <a onclick="showLogin()">Login</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        function showRegister() {
            document.getElementById('loginForm').className = 'login-form hidden';
            document.getElementById('registerForm').className = 'register-form active';
        }
        function showLogin() {
            document.getElementById('loginForm').className = 'login-form';
            document.getElementById('registerForm').className = 'register-form';
        }
    </script>
</body>
</html>