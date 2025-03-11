<?php
// Start session early for CSRF protection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'config.php';  // Database configuration
require_once 'functions/validation.php';  // Input validation functions
require_once 'functions/review_handler.php';  // Review submission logic

// Initialize variables
$error_message = '';
$success_message = '';
$games = [];

// Generate CSRF token if it doesn't exist
try {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
} catch (Exception $e) {
    error_log('CSRF Token Generation Error: ' . $e->getMessage());
    $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
}

// Get database connection
try {
    $conn = DatabaseConfig::getConnection();

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_review') {
        $result = handleReviewSubmission($conn, $_POST);
        if (isset($result['error'])) {
            $error_message = $result['error'];
        } else {
            $success_message = "Review added successfully!";
        }
    }

    // Fetch all games for the dropdown
    $games = fetchAllGames($conn);

} catch (Exception $e) {
    $error_message = "An error occurred while processing your request. Please try again later.";
    error_log('Application Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Reviews - Add a Review</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script defer src="form-validation.js"></script>
</head>
<body>
<header>
    <h1>Game Reviews</h1>
    <nav>
        <a href="index.php" class="button"><i class="fas fa-home"></i> Home</a>
        <a href="add-review.php" class="button active"><i class="fas fa-pen"></i> Add Review</a>
        <a href="reviews.php" class="button"><i class="fas fa-comments"></i> View Reviews</a>
    </nav>
</header>

<div class="container">
    <!-- Page Title -->
    <div class="page-title">
        <h2><i class="fas fa-pen"></i> Add Your Review</h2>
        <p>Share your gaming experience with the community. Your opinion matters!</p>
    </div>

    <!-- Error/Success Message -->
    <?php if (!empty($error_message)): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php elseif (!empty($success_message)): ?>
        <div class="success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Review Submission Form -->
    <div class="form-container">
        <form method="POST" action="" id="review-form">
            <input type="hidden" name="action" value="add_review">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="game_name">Game Name:</label>
                <select name="game_name" id="game_name">
                    <option value="">Select a game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo htmlspecialchars($game); ?>"><?php echo htmlspecialchars($game); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="new_game">New Game Name (if not in list)</label>
                <input type="text" id="new_game" name="new_game" placeholder="Or enter a new game name" maxlength="100">
            </div>

            <div class="form-group">
                <label for="rating">Rating:</label>
                <div class="star-rating">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="reviewer">Your Name:</label>
                <input type="text" name="reviewer" id="reviewer" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="review">Your Review:</label>
                <textarea name="review" id="review" rows="4" maxlength="1000" required></textarea>
                <div class="char-counter"><span id="char-count">0</span>/1000 characters</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Review</button>
                <a href="reviews.php" class="button"><i class="fas fa-comments"></i> View All Reviews</a>
            </div>
        </form>
    </div>

    <!-- Review Guidelines -->
    <div class="review-guidelines">
        <h3><i class="fas fa-info-circle"></i> Review Guidelines</h3>
        <ul>
            <li>Be honest and respectful in your reviews.</li>
            <li>Avoid spoilers or warn readers if your review contains them.</li>
            <li>Focus on your experience with the game, including gameplay, graphics, story, etc.</li>
            <li>Keep your review focused and concise (max 1000 characters).</li>
            <li>Use appropriate language - reviews with offensive content may be removed.</li>
        </ul>
    </div>
</div>

<footer>
    <p>Gamer Reviews &copy; <?php echo date('Y'); ?> - Your ultimate source for gaming opinions!</p>
</footer>
</body>
</html>