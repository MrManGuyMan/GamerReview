<?php
session_start();
require_once 'functions/auth.php';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $result = Auth::login($_POST['username'], $_POST['password']);
            if (isset($result['error'])) {
                $error_message = $result['error'];
            } else {
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Game Reviews</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2><i class="fas fa-sign-in-alt"></i> Login</h2>

        <?php if ($error_message): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="button">Login</button>

            <p class="form-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>