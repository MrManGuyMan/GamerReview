<?php
// Include the database configuration file
global $conn;
include('config.php');

// Start session for CSRF protection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Processing form submission for new reviews
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_review') {
// Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission.";
    } else {
        $game_name = trim($_POST['game_name']);
        $review = trim($_POST['review']);
        $reviewer = trim($_POST['reviewer']);
        $rating = intval($_POST['rating']);
    }

    // Validation
    if (empty($game_name) || empty($review) || empty($reviewer)) {
        $error_message = "All fields are required.";
    } elseif (strlen($game_name) > 100) {
        $error_message = "Game name must be less than 100 characters.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s]+$/", $game_name)) {
        $error_message = "Game name contains invalid characters.";
    } elseif (strlen($reviewer) > 50) {
        $error_message = "Reviewer name must be less than 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $reviewer)) {
        $error_message = "Reviewer name should contain only letters and spaces.";
    } elseif (strlen($review) > 1000) {
        $error_message = "Review must be less than 1000 characters.";
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5.";
    } else {
        // Check if game exists in games table
        $game_id = null;
        $check_game = $conn->prepare("SELECT id FROM games WHERE name = ?");
        $check_game->bind_param("s", $game_name);
        $check_game->execute();
        $game_result = $check_game->get_result();

        if ($game_result->num_rows > 0) {
            $game_id = $game_result->fetch_assoc()['id'];
        } else {
            // Insert new game
            $insert_game = $conn->prepare("INSERT INTO games (name) VALUES (?)");
            $insert_game->bind_param("s", $game_name);
            $insert_game->execute();
            $game_id = $conn->insert_id;
            $insert_game->close();
        }
        $check_game->close();

        // Insert review with game_id
        $stmt = $conn->prepare("INSERT INTO reviews (game_id, game_name, review, reviewer, rating) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $game_id, $game_name, $review, $reviewer, $rating);
        if ($stmt->execute()) {
            $message = "Review added successfully!";
        } else {
            $error_message = "Error: Could not add review. Please try again.";
        }
        $stmt->close();
    }
}

// Filter setup
$where_clauses = [];
$params = [];
$types = "";

// Game filter
$game_filter = isset($_GET['game']) ? trim($_GET['game']) : '';
if (!empty($game_filter)) {
    $where_clauses[] = "r.game_name LIKE ?";
    $params[] = "%$game_filter%";
    $types .= "s";
}

// Rating filter
$rating_filter = isset($_GET['rating']) && is_numeric($_GET['rating']) ? intval($_GET['rating']) : 0;
if ($rating_filter > 0) {
    $where_clauses[] = "r.rating = ?";
    $params[] = $rating_filter;
    $types .= "i";
}

// Build the WHERE clause
$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Pagination
$limit = 5; // Number of reviews per page
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch games for dropdown
$games_result = $conn->query("SELECT DISTINCT name FROM games ORDER BY name");
$games = [];
while ($game_row = $games_result->fetch_assoc()) {
    $games[] = $game_row['name'];
}

// Prepare query for filtered reviews
$sql = "SELECT r.*, g.genre, g.release_year 
        FROM reviews r
        LEFT JOIN games g ON r.game_id = g.id
        $where_sql
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?";

// Add the limit and offset parameters
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Create and execute the prepared statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total matching reviews for pagination
$count_sql = "SELECT COUNT(*) as count FROM reviews r LEFT JOIN games g ON r.game_id = g.id $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($where_clauses)) {
    // Remove the limit and offset from params and types
    array_pop($params);
    array_pop($params);
    $count_types = substr($types, 0, -2);
    $count_stmt->bind_param($count_types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_reviews = $count_result->fetch_assoc()['count'];
$total_pages = ceil($total_reviews / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Reviews - Add & View Reviews</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
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
    <?php if (isset($error_message)): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
    <?php elseif (isset($message)): ?>
        <div class="success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Review Submission Form -->
    <div class="form-container">
        <h2><i class="fas fa-pen"></i> Add Your Review</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_review">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="game_name">Game Name:</label>
                <input type="text" id="game_name" name="game_name" required
                       list="game-suggestions" autocomplete="off">
                <datalist id="game-suggestions">
                    <?php foreach($games as $game): ?>
                    <option value="<?php echo htmlspecialchars($game); ?>">
                        <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="rating">Rating:</label>
                <div class="star-rating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                        <label for="rating-<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="reviewer">Your Name:</label>
                <input type="text" id="reviewer" name="reviewer" required>
            </div>

            <div class="form-group">
                <label for="review">Your Review:</label>
                <textarea id="review" name="review" rows="5" required></textarea>
                <div class="char-counter"><span id="char-count">0</span>/1000</div>
            </div>

            <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Review</button>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <h2><i class="fas fa-filter"></i> Filter Reviews</h2>
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="game-filter">Game:</label>
                <input type="text" id="game-filter" name="game" value="<?php echo htmlspecialchars($game_filter); ?>"
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
                        <option value="<?php echo $i; ?>" <?php echo ($rating_filter == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo ($i != 1) ? 's' : ''; ?></option>
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
        <h2><i class="fas fa-comments"></i> Game Reviews <?php if($total_reviews > 0): ?>(<?php echo $total_reviews; ?>)<?php endif; ?></h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="reviews-container">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <h3><?php echo htmlspecialchars($row['game_name']); ?></h3>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $row['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if(!empty($row['genre']) || !empty($row['release_year'])): ?>
                            <div class="game-details">
                                <?php if(!empty($row['genre'])): ?>
                                    <span class="genre"><i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($row['genre']); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($row['release_year'])): ?>
                                    <span class="year"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($row['release_year']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($row['review'])); ?></p>
                        </div>
                        <div class="review-footer">
                            <span class="reviewer"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['reviewer']); ?></span>
                            <span class="date"><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($game_filter) ? '&game='.urlencode($game_filter) : ''; ?><?php echo ($rating_filter > 0) ? '&rating='.$rating_filter : ''; ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i> Previous</a>
                    <?php endif; ?>

                    <?php
                    // Define how many page numbers to show
                    $max_visible_pages = 5;
                    $start_page = max(1, min($page - floor($max_visible_pages / 2), $total_pages - $max_visible_pages + 1));
                    $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
                    $start_page = max(1, $end_page - $max_visible_pages + 1);

                    // First page link if not visible in the range
                    if($start_page > 1): ?>
                        <a href="?page=1<?php echo !empty($game_filter) ? '&game='.urlencode($game_filter) : ''; ?><?php echo ($rating_filter > 0) ? '&rating='.$rating_filter : ''; ?>" class="pagination-btn">1</a>
                        <?php if($start_page > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($game_filter) ? '&game='.urlencode($game_filter) : ''; ?><?php echo ($rating_filter > 0) ? '&rating='.$rating_filter : ''; ?>"
                           class="pagination-btn <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php
                    // Last page link if not visible in the range
                    if($end_page < $total_pages): ?>
                        <?php if($end_page < $total_pages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($game_filter) ? '&game='.urlencode($game_filter) : ''; ?><?php echo ($rating_filter > 0) ? '&rating='.$rating_filter : ''; ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($game_filter) ? '&game='.urlencode($game_filter) : ''; ?><?php echo ($rating_filter > 0) ? '&rating='.$rating_filter : ''; ?>" class="pagination-btn">Next <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter for review textarea
        const reviewTextarea = document.getElementById('review');
        const charCount = document.getElementById('char-count');

        if(reviewTextarea && charCount) {
            reviewTextarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                charCount.textContent = currentLength;

                if(currentLength > 1000) {
                    charCount.classList.add('over-limit');
                } else {
                    charCount.classList.remove('over-limit');
                }
            });
        }

        // Star rating functionality
        const starLabels = document.querySelectorAll('.star-rating label');
        if(starLabels.length > 0) {
            starLabels.forEach(function(label) {
                label.addEventListener('mouseover', function() {
                    const currentId = this.getAttribute('for');
                    const currentRating = parseInt(currentId.split('-')[1]);

                    starLabels.forEach(function(starLabel, index) {
                        if(index < currentRating) {
                            starLabel.classList.add('hover');
                        } else {
                            starLabel.classList.remove('hover');
                        }
                    });
                });

                label.addEventListener('mouseout', function() {
                    starLabels.forEach(function(starLabel) {
                        starLabel.classList.remove('hover');
                    });
                });
            });
        }

        // Form validation
        const reviewForm = document.querySelector('form[action=""]');
        if(reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                let hasError = false;
                const gameNameInput = document.getElementById('game_name');
                const reviewerInput = document.getElementById('reviewer');
                const reviewTextarea = document.getElementById('review');

                // Reset previous errors
                document.querySelectorAll('.field-error').forEach(el => el.remove());

                // Validate game name
                if(gameNameInput.value.trim() === '') {
                    addErrorTo(gameNameInput, 'Game name is required');
                    hasError = true;
                } else if(gameNameInput.value.length > 100) {
                    addErrorTo(gameNameInput, 'Game name must be less than 100 characters');
                    hasError = true;
                }

                // Validate reviewer name
                if(reviewerInput.value.trim() === '') {
                    addErrorTo(reviewerInput, 'Your name is required');
                    hasError = true;
                } else if(reviewerInput.value.length > 50) {
                    addErrorTo(reviewerInput, 'Name must be less than 50 characters');
                    hasError = true;
                }

                // Validate review text
                if(reviewTextarea.value.trim() === '') {
                    addErrorTo(reviewTextarea, 'Review text is required');
                    hasError = true;
                } else if(reviewTextarea.value.length > 1000) {
                    addErrorTo(reviewTextarea, 'Review must be less than 1000 characters');
                    hasError = true;
                }

                if(hasError) {
                    e.preventDefault();
                }
            });
        }

        function addErrorTo(element, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            errorDiv.style.color = 'var(--error)';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            element.parentNode.appendChild(errorDiv);
            element.style.borderColor = 'var(--error)';
        }
    });
</script>
</body>
</html>