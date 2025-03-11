<?php
// Enhanced Database Configuration with Robust Error Handling

class DatabaseConfig {
    // Database connection parameters
    private const DB_HOST = "localhost";
    private const DB_USER = "root";      // Default for XAMPP
    private const DB_PASS = "";          // Default password for XAMPP
    private const DB_NAME = "gamer_reviews";

    // Error log file path
    private const ERROR_LOG_FILE = "application_errors.log";

    // Database connection instance
    private static $connection = null;

    /**
     * Prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Enhanced error logging function
     *
     * @param string $message Error message
     * @param array $context Additional context for the error
     */
    private static function logError(string $message, array $context = []) {
        $logEntry = date('[Y-m-d H:i:s] ') . $message . "\n";
        if (!empty($context)) {
            $logEntry .= "Context: " . json_encode($context) . "\n";
        }
        error_log($logEntry, 3, self::ERROR_LOG_FILE);
    }

    /**
     * Establish database connection
     *
     * @return mysqli Database connection object
     * @throws Exception If connection fails
     */
    public static function getConnection() {
        // Return existing connection if already established
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            // Create connection with error reporting
            $conn = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASS, self::DB_NAME);

            // Check connection with detailed error handling
            if ($conn->connect_error) {
                throw new Exception("MySQL Connection Error: " . $conn->connect_error, $conn->connect_errno);
            }

            // Set character set to UTF-8 with error checking
            if (!$conn->set_charset("utf8mb4")) {
                throw new Exception("Error setting character set: " . $conn->error);
            }

            // Create database schema
            self::checkDatabaseSchema($conn);

            // Store the connection for future use
            self::$connection = $conn;

            return $conn;

        } catch (Exception $e) {
            // Log detailed error information
            self::logError("Database Connection Failed", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Display user-friendly error message
            die("We're experiencing technical difficulties. Our team has been notified.");
        }
    }

    /**
     * Check and create database schema
     *
     * @param mysqli $conn Database connection object
     */
    private static function checkDatabaseSchema($conn) {
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
                self::logError("Error creating table $table", [
                    'error' => $conn->error,
                    'query' => $query
                ]);
            }
        }
    }

    /**
     * Close database connection
     */
    public static function closeConnection() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }

    /**
     * Prevent cloning of the class
     */
    private function __clone() {}
}

// Automatically establish connection on include
try {
    $conn = DatabaseConfig::getConnection();
} catch (Exception $e) {
    // Connection error is already handled in the getConnection method
    exit;
}
