-- Create content table
CREATE TABLE IF NOT EXISTS content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    video_path VARCHAR(255),
    video_url VARCHAR(255),
    duration INT,
    release_year INT,
    is_featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create content_categories table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS content_categories (
    content_id INT,
    category_id INT,
    PRIMARY KEY (content_id, category_id),
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_ratings table
CREATE TABLE IF NOT EXISTS user_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    content_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (user_id, content_id)
);

-- Create user_comments table
CREATE TABLE IF NOT EXISTS user_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    content_id INT,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
);

-- Create watchlist table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    content_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watchlist (user_id, content_id)
);

-- Create watch_history table
CREATE TABLE IF NOT EXISTS watch_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    content_id INT,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
);

-- Create site_settings table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, slug) VALUES
('Action', 'action'),
('Comedy', 'comedy'),
('Drama', 'drama'),
('Horror', 'horror'),
('Sci-Fi', 'sci-fi'),
('Documentary', 'documentary'),
('Animation', 'animation'),
('Romance', 'romance'),
('Thriller', 'thriller'),
('Family', 'family');

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Flixnate'),
('site_description', 'Your Ultimate Streaming Platform'),
('site_logo', 'assets/images/logo.png'),
('allow_registration', 'true'),
('maintenance_mode', 'false'),
('max_upload_size', '500000000'); -- 500MB in bytes

-- Create indexes for better performance
CREATE INDEX idx_content_title ON content(title);
CREATE INDEX idx_content_created_at ON content(created_at);
CREATE INDEX idx_watch_history_user ON watch_history(user_id);
CREATE INDEX idx_watch_history_content ON watch_history(content_id);
CREATE INDEX idx_user_ratings_user ON user_ratings(user_id);
CREATE INDEX idx_user_ratings_content ON user_ratings(content_id);
CREATE INDEX idx_user_comments_user ON user_comments(user_id);
CREATE INDEX idx_user_comments_content ON user_comments(content_id); 