-- Create database
CREATE DATABASE IF NOT EXISTS portfolio_db;
USE portfolio_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    bio TEXT,
    avatar VARCHAR(255),
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Portfolio entries table
CREATE TABLE portfolio_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    image_path VARCHAR(255),
    tags VARCHAR(255), -- Comma-separated tags
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    share_token VARCHAR(64) UNIQUE,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_share_token (share_token),
    INDEX idx_user_public (user_id, is_public)
);

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES portfolio_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_entry (entry_id)
);

-- Analytics table (for tracking views)
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES portfolio_entries(id) ON DELETE CASCADE,
    INDEX idx_entry_date (entry_id, viewed_at)
);

-- Insert sample user (password: student123)
INSERT INTO users (username, email, password, full_name, bio) VALUES 
('student', 'student@example.com', '$2y$10$Wq.x5aNOVKQZVZvFnFzDvefGdFqVtA1xJqMv3JLW.XZSgMkF/Xm8C', 'John Doe', 'Multimedia & Animation student passionate about digital art.');

-- Insert sample entries
INSERT INTO portfolio_entries (user_id, title, category, description, tags, share_token) VALUES
(1, 'Neon Dreams', 'Digital Art', 'Cyberpunk illustration with vibrant neon lights and rain-soaked streets.', 'cyberpunk, digital, neon', 'share_' . md5(RAND())),
(1, 'Forest Spirit', 'Animation', '2D animated short featuring a mystical forest guardian. 30s loop.', '2d, animation, fantasy', 'share_' . md5(RAND())),
(1, 'Sculpture Study', '3D', 'ZBrush sculpt of a fantasy creature, detailed textures and lighting.', '3d, zbrush, sculpting', 'share_' . md5(RAND()));

-- Insert sample comments
INSERT INTO comments (entry_id, user_id, content) VALUES
(1, 1, 'Amazing work! The lighting is incredible.'),
(2, 1, 'Love the fluid animation style.'),
(3, 1, 'Great sculpting details.');