<?php
// Enhanced Database Configuration with Robust Error Handling

// Database Connection Parameters
$servername = "localhost";
$username = "root";      // Default for XAMPP
$password = "";          // Default password for XAMPP
$dbname = "gamer_reviews";

// Global connection variable
global $conn;

// Enhanced error logging function
function logError($message, $context = []) {
    $logEntry = date('[Y-m-d H:i:s] ') . $message . "\n";
    if (!empty($context)) {
        $logEntry .= "Context: " . json_encode($context) . "\n";
    }
    error_log($logEntry, 3, "application_errors.log");
}

// Improved database connection
try {
    // Create connection with error reporting
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection with detailed error handling
    if ($conn->connect_error) {
        throw new Exception("MySQL Connection Error: " . $conn->connect_error, $conn->connect_errno);
    }

    // Set character set to UTF-8 with error checking
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting character set: " . $conn->error);
    }

    // Check and create database schema
    checkDatabaseSchema($conn);

} catch (Exception $e) {
    // Log detailed error information
    logError("Database Connection Failed", [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    // Display user-friendly error message
    die("We're experiencing technical difficulties. Our team has been notified.");
}

function checkDatabaseSchema($conn) {
    // Enhanced table creation with detailed error logging
    $tables = [
        'games' => "CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            genre VARCHAR(50),
            release_year INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT,
            game_name VARCHAR(100) NOT NULL,
            review TEXT NOT NULL,
            reviewer VARCHAR(50) NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_game_id (game_id),
            INDEX idx_rating (rating),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($tables as $table => $query) {
        if (!$conn->query($query)) {
            logError("Error creating table $table", [
                'error' => $conn->error,
                'query' => $query
            ]);
        }
    }
}
?>