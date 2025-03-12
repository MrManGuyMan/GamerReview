<?php
require_once 'config.php';

class Auth {
    public static function register($username, $email, $password) {
        try {
            $conn = DatabaseConfig::getConnection();

            // Validate input
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['error' => 'Invalid email format'];
            }

            if (strlen($password) < 8) {
                return ['error' => 'Password must be at least 8 characters'];
            }

            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return ['error' => 'Username or email already exists'];
            }

            // Hash password and insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Registration successful'];
            }

            return ['error' => 'Registration failed'];
        } catch (Exception $e) {
            error_log('Registration Error: ' . $e->getMessage());
            return ['error' => 'An error occurred during registration'];
        }
    }

    public static function login($username, $password) {
        try {
            $conn = DatabaseConfig::getConnection();

            $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? AND is_active = TRUE");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password_hash'])) {
                    // Update last login time
                    $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    return ['success' => true, 'message' => 'Login successful'];
                }
            }

            return ['error' => 'Invalid username or password'];
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return ['error' => 'An error occurred during login'];
        }
    }

    public static function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}