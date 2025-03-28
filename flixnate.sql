-- Create database
DROP DATABASE IF EXISTS flixnate;
CREATE DATABASE flixnate;
USE flixnate;

-- Create categories table first (since it's referenced by content_categories)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Action', 'Action-packed movies and shows'),
('Drama', 'Emotional and compelling stories'),
('Comedy', 'Funny and entertaining content'),
('Sci-Fi', 'Science fiction adventures'),
('Horror', 'Scary and thrilling content'),
('Romance', 'Love stories and romantic dramas'),
('Documentary', 'Educational and informative content'),
('Animation', 'Animated movies and shows'),
('Crime', 'Crime and mystery content');

-- Create content table
CREATE TABLE content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    video_url VARCHAR(255),
    duration INT,
    release_year INT,
    rating DECIMAL(3,1),
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create content_categories table for many-to-many relationship
CREATE TABLE content_categories (
    content_id INT,
    category_id INT,
    PRIMARY KEY (content_id, category_id),
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Insert sample content
INSERT INTO content (title, description, thumbnail, video_url, duration, release_year, rating, is_featured) VALUES
('The Adventure Begins', 'An epic tale of discovery and courage', 'uploads/thumbnails/adventure-begins.jpg', 'uploads/videos/adventure-begins.mp4', 120, 2024, 4.8, 1),
('Laugh Out Loud', 'A hilarious comedy that will keep you entertained', 'uploads/thumbnails/laugh-out-loud.jpg', 'uploads/videos/laugh-out-loud.mp4', 95, 2024, 4.5, 1),
('Mystery of the Night', 'A thrilling mystery that will keep you guessing', 'uploads/thumbnails/mystery-night.jpg', 'uploads/videos/mystery-night.mp4', 105, 2024, 4.7, 1),
('City of Dreams', 'A modern urban drama', 'uploads/thumbnails/city-of-dreams.jpg', 'uploads/videos/city-of-dreams.mp4', 90, 2024, 4.6, 0),
('Love in Paris', 'A romantic drama', 'uploads/thumbnails/love-in-paris.jpg', 'uploads/videos/love-in-paris.mp4', 100, 2024, 4.4, 0),
('The Dark Forest', 'A supernatural horror', 'uploads/thumbnails/dark-forest.jpg', 'uploads/videos/dark-forest.mp4', 110, 2024, 4.9, 0);

-- Link content to categories
INSERT INTO content_categories (content_id, category_id) VALUES
(1, (SELECT id FROM categories WHERE name = 'Action')),
(2, (SELECT id FROM categories WHERE name = 'Comedy')),
(3, (SELECT id FROM categories WHERE name = 'Crime')),
(3, (SELECT id FROM categories WHERE name = 'Horror')),
(4, (SELECT id FROM categories WHERE name = 'Drama')),
(5, (SELECT id FROM categories WHERE name = 'Romance')),
(6, (SELECT id FROM categories WHERE name = 'Horror'));

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create watchlist table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (content_id) REFERENCES content(id)
); 