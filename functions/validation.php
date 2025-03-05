<?php
/**
 * Input validation and sanitization functions
 */

/**
 * Sanitize user input based on type
 *
 * @param mixed $input Input to sanitize
 * @param string $type Type of input for specific sanitization
 * @return mixed Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    if ($input === null) return null;

    $input = trim($input);

    switch ($type) {
        case 'string':
            // Use FILTER_SANITIZE_FULL_SPECIAL_CHARS instead of deprecated FILTER_SANITIZE_STRING
            return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);

        case 'game_name':
            // Allow alphanumeric, spaces, and some special characters
            return preg_replace("/[^a-zA-Z0-9\s\-():.]/", '', $input);

        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);

        default:
            return $input;
    }
}

/**
 * Validate review data
 *
 * @param array $data Review data to validate
 * @return array Validation result with errors if any
 */
function validateReviewData($data) {
    $errors = [];

    // Game name validation
    if (empty($data['game_name']) && empty($data['new_game'])) {
        $errors[] = "Game name is required.";
    } elseif (!empty($data['new_game']) && strlen($data['new_game']) > 100) {
        $errors[] = "Game name must be less than 100 characters.";
    }

    // Reviewer validation
    if (empty($data['reviewer'])) {
        $errors[] = "Reviewer name is required.";
    } elseif (strlen($data['reviewer']) > 50) {
        $errors[] = "Reviewer name must be less than 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $data['reviewer'])) {
        $errors[] = "Reviewer name should contain only letters and spaces.";
    }

    // Review validation
    if (empty($data['review'])) {
        $errors[] = "Review text is required.";
    } elseif (strlen($data['review']) > 1000) {
        $errors[] = "Review must be less than 1000 characters.";
    }

    // Rating validation
    if (!isset($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        $errors[] = "Rating must be between 1 and 5.";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate CSRF token
 *
 * @param string $token Token from form submission
 * @return bool Whether token is valid
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}
