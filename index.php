<?php
// Start session early for CSRF protection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'config.php';  // Database configuration
require_once 'functions/validation.php';  // Input validation functions
require_once 'functions/review_handler.php';  // Review submission logic
require_once 'functions/display_helper.php';  // Display helper functions

// Initialize variables
$error_message = '';
$success_message = '';
$game_filter = '';
$rating_filter = 0;
$page = 1;
$total_reviews = 0;
$total_pages = 0;
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

    // Get filter parameters
    $game_filter = isset($_GET['game']) ? sanitizeInput($_GET['game']) : '';
    $rating_filter = isset($_GET['rating']) && is_numeric($_GET['rating']) ? intval($_GET['rating']) : 0;
    $page = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? intval($_GET['page']) : 1;

    // Fetch all games for the dropdown
    $games = fetchAllGames($conn);

    // Fetch filtered reviews with pagination
    $reviewsData = fetchFilteredReviews($conn, $game_filter, $rating_filter, $page);
    $reviews = $reviewsData['reviews'];
    $total_reviews = $reviewsData['total'];
    $total_pages = $reviewsData['pages'];

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
    <title>Game Reviews - Add & View Reviews</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script defer src="js/form-validation.js"></script>
</head>
<body>
<header>
    <h1>Game Reviews</h1>
    <nav>
        <a href="index.php" class="button"><i class="fas fa-home"></i> Home</a>
    </nav>
</header>

<div class="container">
    <!-- Error/Success Message -->
    <?php if (!empty($error_message)): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php elseif (!empty($success_message)): ?>
        <div class="success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Review Submission Form -->
    <div class="form-container">
        <h2><i class="fas fa-pen"></i> Add Your Review</h2>
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

            <button type="submit" class="submit-btn">Submit Review</button>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <h2><i class="fas fa-filter"></i> Filter Reviews</h2>
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="game-filter">Game:</label>
                <input type="text" id="game-filter" name="game"
                       value="<?php echo htmlspecialchars($game_filter); ?>"
                       list="game-filter-suggestions" autocomplete="off">
                <datalist id="game-filter-suggestions">
                    <?php foreach($games as $game): ?>
                    <option value="<?php echo htmlspecialchars($game); ?>">
                        <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="rating-filter">Rating:</label>
                <select id="rating-filter" name="rating">
                    <option value="0">All Ratings</option>
                    <?php for($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($rating_filter == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> Star<?php echo ($i != 1) ? 's' : ''; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-buttons">
                <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Apply Filters</button>
                <a href="index.php" class="reset-btn"><i class="fas fa-undo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Display Reviews -->
    <div class="reviews-section">
        <h2><i class="fas fa-comments"></i> Game Reviews
            <?php if ($total_reviews > 0): ?>
                (<?php echo htmlspecialchars($total_reviews); ?>)
            <?php endif; ?>
        </h2>

        <?php if (!empty($reviews)): ?>
            <div class="reviews-container">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <h3><?php echo htmlspecialchars($review['game_name']); ?></h3>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo ($i <= $review['rating']) ? 'fas fa-star' : 'far fa-star'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <?php if (!empty($review['genre']) || !empty($review['release_year'])): ?>
                            <div class="game-details">
                                <?php if (!empty($review['genre'])): ?>
                                    <span class="genre"><i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($review['genre']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($review['release_year'])): ?>
                                    <span class="year"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($review['release_year']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                        </div>

                        <div class="review-footer">
                            <span class="reviewer"><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['reviewer']); ?></span>
                            <span class="date"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars(date('M j, Y', strtotime($review['created_at']))); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <?php echo generatePagination($page, $total_pages, $game_filter, $rating_filter); ?>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-reviews">
                <i class="fas fa-search"></i>
                <p>No reviews found! <?php echo (!empty($game_filter) || $rating_filter > 0) ? 'Try changing your filters or ' : ''; ?>be the first to add one.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>Gamer Reviews &copy; <?php echo date('Y'); ?> - Your ultimate source for gaming opinions!</p>
</footer>
</body>
</html>