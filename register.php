<?php
session_start();
require_once 'functions/auth.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $result = Auth::register(
            $_POST['username'],
            $_POST['email'],
            $_POST['password']
        );

        if (isset($result['error'])) {
            $error_message = $result['error'];
        } else {
            $success_message = 'Registration successful! You can now login.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Game Reviews</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <div class="form-container">
        <h2><i class="fas fa-user-plus"></i> Register</h2>

        <?php if ($error_message): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="button">Register</button>

            <p class="form-footer">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>