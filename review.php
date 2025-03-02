<?php
// Include the database configuration file
global $conn;
include('config.php');

// Processing form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $game_name = trim($_POST['game_name']);
    $review = trim($_POST['review']);
    $reviewer = trim($_POST['reviewer']);
    $rating = intval($_POST['rating']);

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
        // Prepared statement
        $stmt = $conn->prepare("INSERT INTO reviews (game_name, review, reviewer, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $game_name, $review, $reviewer, $rating);

        if ($stmt->execute()) {
            $message = "Review added successfully!";
        } else {
            $error_message = "Error: Could not add review. Please try again.";
        }
        $stmt->close();
    }
}

$limit = 10; // Number of reviews per page
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch paginated reviews
$stmt = $conn->prepare("SELECT * FROM reviews ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Get total reviews count for pagination
$total_reviews_query = $conn->query("SELECT COUNT(*) AS count FROM reviews");
$total_reviews = $total_reviews_query->fetch_assoc()['count'];
$total_pages = ceil($total_reviews / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Reviews - Add & View Reviews</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <!-- Title Section -->
    <h1>Game Reviews</h1>
    <!-- Navigation: Back to Index Button -->
    <a href="index.php" class="button">Back to Home</a>
</header>

<!-- Error/Success Message -->
<?php if (isset($error_message)): ?>
    <p class="error"><?php echo $error_message; ?></p>
<?php elseif (isset($message)): ?>
    <p class="success"><?php echo $message; ?></p>
<?php endif; ?>

<!-- Review Submission Form -->
<form method="POST" action="">
    <label for="game_name">Game Name:</label><br>
    <input type="text" id="game_name" name="game_name" required><br><br>

    <label for="review">Review:</label><br>
    <textarea id="review" name="review" rows="5" required></textarea><br><br>

    <label for="reviewer">Your Name:</label><br>
    <input type="text" id="reviewer" name="reviewer" required><br><br>

    <label for="rating">Rating (1 to 5):</label><br>
    <input type="number" id="rating" name="rating" min="1" max="5" required><br><br>

    <button type="submit">Submit Review</button>
</form>

<hr>

<!-- Display Reviews -->
<h2>All Reviews</h2>
<?php
function renderReview($row)
{
    echo "<div class='review-card'>";
    echo "<h3>" . htmlspecialchars($row['game_name']) . " (" . $row['rating'] . "/5)</h3>";
    echo "<p>" . nl2br(htmlspecialchars($row['review'])) . "</p>";
    echo "<small>Reviewed by " . htmlspecialchars($row['reviewer']) . " on " . htmlspecialchars($row['created_at']) . "</small>";
    echo "</div>";
}

?>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php renderReview($row); ?>
    <?php endwhile; ?>
<?php else: ?>
    <p>No reviews found! Be the first to add one.</p>
<?php endif; ?>

<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
</body>
</html>