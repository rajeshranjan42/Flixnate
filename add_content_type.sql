-- Add content_type column to content table
ALTER TABLE content
ADD COLUMN content_type ENUM('movie', 'tv-show') NOT NULL DEFAULT 'movie';

-- Update existing content to set some as TV shows (you can adjust these titles as needed)
UPDATE content 
SET content_type = 'tv-show'
WHERE title IN ('Mystery Lane', 'Space Explorers', 'Dark Shadows'); 