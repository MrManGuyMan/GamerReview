<?php
$servername = "localhost";
$username = "root";      // Default for XAMPP
$password = "";          // Default password for XAMPP
$dbname = "gamer_reviews";

// Create connection
global $conn;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set character set to UTF-8
    $conn->set_charset("utf8");

    // Check database schema
    checkDatabaseSchema($conn);

} catch (Exception $e) {
    // Log error and display user-friendly message
    error_log($e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

function checkDatabaseSchema($conn) {
    // Check if tables exist
    $tables = [
        'games' => "CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            genre VARCHAR(50),
            release_year INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        )",
        'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT,
            game_name VARCHAR(100) NOT NULL,
            review TEXT NOT NULL,
            reviewer VARCHAR(50) NOT NULL,
            rating INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_game_id (game_id),
            INDEX idx_rating (rating),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
        )"
    ];

    foreach ($tables as $table => $query) {
        if (!$conn->query($query)) {
            error_log("Error creating table $table: " . $conn->error);
        }
    }
}
?>