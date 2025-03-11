<?php
// Start session early for CSRF protection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'config.php';  // Database configuration
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Reviews - Your Ultimate Gaming Community</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script defer src="form-validation.js"></script>
</head>
<body>
<header>
    <h1>Game Reviews</h1>
    <nav>
        <a href="index.php" class="button active"><i class="fas fa-home"></i> Home</a>
        <a href="add-review.php" class="button"><i class="fas fa-pen"></i> Add Review</a>
        <a href="reviews.php" class="button"><i class="fas fa-comments"></i> View Reviews</a>
    </nav>
</header>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <h2>Welcome to Game Reviews</h2>
        <p class="tagline">Your ultimate destination for honest gaming opinions, reviews, and discussions from real gamers.</p>

        <div class="features">
            <div class="feature">
                <i class="fas fa-gamepad fa-3x"></i>
                <h3>Share Your Opinion</h3>
                <p>Rate and review your favorite games and discover new ones through our community.</p>
            </div>

            <div class="feature">
                <i class="fas fa-search fa-3x"></i>
                <p>Search through hundreds of game reviews to find your next gaming adventure.</p>
            </div>

            <div class="feature">
                <i class="fas fa-star fa-3x"></i>
                <h3>Rate Games</h3>
                <p>Give your rating from 1 to 5 stars and help other gamers make informed decisions.</p>
            </div>
        </div>

        <div class="cta">
            <a href="add-review.php" class="button"><i class="fas fa-pen"></i> Write a Review</a>
            <a href="reviews.php" class="button"><i class="fas fa-comments"></i> Read Reviews</a>
        </div>
    </div>

    <!-- Recent Reviews Preview -->
    <div class="reviews-section">
        <h2><i class="fas fa-fire"></i> Recent Reviews</h2>
        <?php
        try {
            $conn = DatabaseConfig::getConnection();

            // Fetch the 3 most recent reviews
            $sql = "SELECT r.*, g.genre, g.release_year 
                   FROM reviews r 
                   LEFT JOIN games g ON r.game_id = g.id 
                   ORDER BY r.created_at DESC LIMIT 3";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                echo '<div class="reviews-container">';
                while($review = $result->fetch_assoc()) {
                    ?>
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
                            <p><?php
                                // Display a preview of the review (first 150 characters)
                                $preview = htmlspecialchars(substr($review['review'], 0, 150));
                                echo nl2br($preview);
                                if (strlen($review['review']) > 150) {
                                    echo '... <a href="reviews.php?game=' . urlencode($review['game_name']) . '" class="read-more">Read more</a>';
                                }
                                ?></p>
                        </div>

                        <div class="review-footer">
                            <span class="reviewer"><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['reviewer']); ?></span>
                            <span class="date"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars(date('M j, Y', strtotime($review['created_at']))); ?></span>
                        </div>
                    </div>
                    <?php
                }
                echo '</div>';
                echo '<div class="cta-center"><a href="reviews.php" class="button">View All Reviews</a></div>';
            } else {
                ?>
                <div class="no-reviews">
                    <i class="fas fa-search"></i>
                    <p>No reviews found! Be the first to add one.</p>
                    <a href="add-review.php" class="button mt-3">Add Review</a>
                </div>
                <?php
            }
        } catch (Exception $e) {
            echo '<div class="error">Error loading recent reviews.</div>';
            error_log('Error loading recent reviews: ' . $e->getMessage());
        }
        ?>
    </div>
</div>

<footer>
    <p>Gamer Reviews &copy; <?php echo date('Y'); ?> - Your ultimate source for gaming opinions!</p>
</footer>

</body>
</html>