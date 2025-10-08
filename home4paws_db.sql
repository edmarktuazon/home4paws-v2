CREATE TABLE IF NOT EXISTS rescued_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_data LONGTEXT NOT NULL, -- Store base64-encoded image
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS adoptable_animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_data LONGTEXT NOT NULL, -- Store base64-encoded image
    created_at DATETIME NOT NULL
);