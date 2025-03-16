<?php
/**
 * Functions for handling review submissions and retrievals
 */
/**
 * Reindex all reviews to ensure they appear consecutively
 *
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function reindexReviews(mysqli $conn): bool
{
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Get all reviews ordered by created_at
        $query = "SET @row_number = 0";
        $conn->query($query);

        // Update index_order for all reviews
        $query = "UPDATE reviews r
                 JOIN (
                     SELECT id, 
                            @row_number:=@row_number + 1 AS new_index
                     FROM reviews, 
                          (SELECT @row_number:=0) AS init
                     ORDER BY created_at
                 ) AS numbered
                 ON r.id = numbered.id
                 SET r.index_order = numbered.new_index";

        $result = $conn->query($query);

        if (!$result) {
            throw new Exception("Failed to reindex reviews: " . $conn->error);
        }

        // Commit transaction
        $conn->commit();
        return true;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Review Reindexing Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Handle review submission
 *
 * @param mysqli $conn Database connection
 * @param array $post_data POST data from form
 * @return array Result with success or error message
 */
function handleReviewSubmission(mysqli $conn, array $post_data): array
{
    // Verify CSRF token
    if (!isset($post_data['csrf_token']) || !validateCsrfToken($post_data['csrf_token'])) {
        return ['error' => "Invalid form submission. Please try again."];
    }

    // Determine game name (from dropdown or new input)
    $game_name = !empty($post_data['new_game'])
        ? sanitizeInput($post_data['new_game'], 'game_name')
        : sanitizeInput($post_data['game_name'], 'game_name');

    $review = sanitizeInput($post_data['review']);
    $reviewer = $_SESSION['username'];
    $rating = sanitizeInput($post_data['rating'], 'int');

    // Validate input data
    $validation = validateReviewData([
        'game_name' => $game_name,
        'new_game' => $post_data['new_game'] ?? '',
        'review' => $review,
        'rating' => $rating
    ]);

    if (!$validation['valid']) {
        return ['error' => implode("<br>", $validation['errors'])];
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

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

            if (!$insert_game->execute()) {
                throw new Exception("Failed to insert new game: " . $insert_game->error);
            }

            $game_id = $conn->insert_id;
            $insert_game->close();
        }
        $check_game->close();
        $next_index_query = "SELECT COALESCE(MAX(index_order), 0) + 1 AS next_index FROM reviews";
        $next_index_result = $conn->query($next_index_query);
        $next_index = $next_index_result->fetch_assoc()['next_index'];
        // Insert review
        $stmt = $conn->prepare("INSERT INTO reviews (game_id, game_name, review, reviewer, rating, index_order) VALUES (?, ?, ?, ?, ?,?)");
        $stmt->bind_param("isssi", $game_id, $game_name, $review, $reviewer, $rating);

        if (!$stmt->execute()) {
            throw new Exception("Review insertion failed: " . $stmt->error);
        }

        $stmt->close();

        // Commit transaction
        $conn->commit();
        reindexReviews($conn);
        return ['success' => true];

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        // Log the error
        error_log("Review Submission Error: " . $e->getMessage());

        return ['error' => "An error occurred while submitting your review. Please try again."];
    }
}

/**
 * Fetch all games for dropdown
 *
 * @param mysqli $conn Database connection
 * @return array List of game names
 */
function fetchAllGames($conn) {
    $games = [];

    try {
        $games_result = $conn->query("SELECT DISTINCT name FROM games ORDER BY name");
        if ($games_result) {
            while ($game_row = $games_result->fetch_assoc()) {
                $games[] = $game_row['name'];
            }
            $games_result->free_result();
        }
    } catch (Exception $e) {
        error_log('Error fetching games: ' . $e->getMessage());
    }

    return $games;
}

/**
 * Fetch filtered reviews with pagination
 *
 * @param mysqli $conn Database connection
 * @param string $game_filter Game name filter
 * @param int $rating_filter Rating filter
 * @param int $page Current page number
 * @return array Reviews data with pagination info
 */
function fetchFilteredReviews($conn, $game_filter, $rating_filter, $page) {
    $limit = 5; // Reviews per page
    $offset = ($page - 1) * $limit;
    $where_clauses = [];
    $params = [];
    $types = "";

    // Build WHERE clause for filters
    if (!empty($game_filter)) {
        $where_clauses[] = "r.game_name LIKE ?";
        $params[] = "%$game_filter%";
        $types .= "s";
    }

    if ($rating_filter > 0) {
        $where_clauses[] = "r.rating = ?";
        $params[] = $rating_filter;
        $types .= "i";
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Main query for reviews
    $sql = "SELECT SQL_CALC_FOUND_ROWS r.* 
            FROM reviews r
            $where_sql
            ORDER BY r.index_order ASC 
            LIMIT ? OFFSET ?";

    // Add pagination parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    try {
        // Prepare and execute the query
        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];

        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        $stmt->close();

        // Count total reviews for pagination
        $count_sql = "SELECT COUNT(*) as count FROM reviews r LEFT JOIN games g ON r.game_id = g.id $where_sql";
        $count_stmt = $conn->prepare($count_sql);

        if (!empty($where_clauses)) {
            // Remove pagination parameters
            array_pop($params);
            array_pop($params);
            $count_types = substr($types, 0, -2);

            if (!empty($params)) {
                $count_stmt->bind_param($count_types, ...$params);
            }
        }

        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_reviews = $count_result->fetch_assoc()['count'];
        $count_stmt->close();

        $total_pages = ceil($total_reviews / $limit);

        return [
            'reviews' => $reviews,
            'total' => $total_reviews,
            'pages' => $total_pages
        ];

    } catch (Exception $e) {
        error_log('Error fetching reviews: ' . $e->getMessage());
        return [
            'reviews' => [],
            'total' => 0,
            'pages' => 0
        ];
    }
}