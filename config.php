<?php
$servername = "localhost";
$username = "root";      // Default for XAMPP
$password = "";          // Default password for XAMPP
$dbname = "gamer_reviews";

// Create connection
global $conn;
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8");
?>