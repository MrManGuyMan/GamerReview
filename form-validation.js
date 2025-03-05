/**
 * Game Reviews - Client-side JavaScript functionality
 * Handles form validation, UI enhancements, and user interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    initFormValidation();

    // Character counter for review textarea
    initCharCounter();

    // Star rating hover effects
    initStarRating();

    // Game selection logic
    initGameSelection();

    // Filter form enhancements
    initFilterForm();
});

/**
 * Initialize form validation
 */
function initFormValidation() {
    const reviewForm = document.getElementById('review-form');

    if (!reviewForm) return;

    reviewForm.addEventListener('submit', function(e) {
        let hasErrors = false;
        const errorMessages = [];

        // Get form elements
        const gameSelect = document.getElementById('game_name');
        const newGame = document.getElementById('new_game');
        const reviewer = document.getElementById('reviewer');
        const review = document.getElementById('review');
        const rating = document.querySelector('input[name="rating"]:checked');

        // Remove any existing error messages
        removeAllErrors();

        // Validate game selection - either selected or new game name is required
        if (gameSelect.value === '' && newGame.value.trim() === '') {
            addError(gameSelect, 'Please select a game or enter a new game name');
            hasErrors = true;
            errorMessages.push('Game name is required');
        } else if (newGame.value.trim() !== '' && newGame.value.length > 100) {
            addError(newGame, 'Game name must be less than 100 characters');
            hasErrors = true;
            errorMessages.push('Game name is too long');
        }

        // Validate reviewer name
        if (reviewer.value.trim() === '') {
            addError(reviewer, 'Please enter your name');
            hasErrors = true;
            errorMessages.push('Reviewer name is required');
        } else if (reviewer.value.length > 50) {
            addError(reviewer, 'Name must be less than 50 characters');
            hasErrors = true;
            errorMessages.push('Reviewer name is too long');
        } else if (!/^[a-zA-Z\s]+$/.test(reviewer.value)) {
            addError(reviewer, 'Name should contain only letters and spaces');
            hasErrors = true;
            errorMessages.push('Reviewer name contains invalid characters');
        }

        // Validate review text
        if (review.value.trim() === '') {
            addError(review, 'Please enter your review');
            hasErrors = true;
            errorMessages.push('Review text is required');
        } else if (review.value.length > 1000) {
            addError(review, 'Review must be less than 1000 characters');
            hasErrors = true;
            errorMessages.push('Review is too long');
        }

        // Check if rating is selected (though we have a default)
        if (!rating) {
            addError(document.querySelector('.star-rating'), 'Please select a rating');
            hasErrors = true;
            errorMessages.push('Rating is required');
        }

        // Prevent submission if there are errors
        if (hasErrors) {
            e.preventDefault();

            // Show error summary at the top of the form
            showErrorSummary(errorMessages);

            // Scroll to the first error
            const firstError = document.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

/**
 * Add error message to a form field
 *
 * @param {HTMLElement} element Form element with error
 * @param {string} message Error message to display
 */
function addError(element, message) {
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;

    // Add error class to the input
    element.classList.add('error-input');

    // Insert error message after the element
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
}

/**
 * Remove all error messages from the form
 */
function removeAllErrors() {
    // Remove error message elements
    const errorElements = document.querySelectorAll('.field-error');
    errorElements.forEach(el => el.remove());

    // Remove error class from inputs
    const errorInputs = document.querySelectorAll('.error-input');
    errorInputs.forEach(input => input.classList.remove('error-input'));

    // Remove error summary if exists
    const errorSummary = document.querySelector('.error-summary');
    if (errorSummary) {
        errorSummary.remove();
    }
}

/**
 * Show summary of errors at the top of the form
 *
 * @param {string[]} messages Array of error messages
 */
function showErrorSummary(messages) {
    if (messages.length === 0) return;

    const form = document.getElementById('review-form');

    // Create error summary element
    const errorSummary = document.createElement('div');
    errorSummary.className = 'error-summary';

    // Create error list
    const errorList = document.createElement('ul');
    messages.forEach(message => {
        const listItem = document.createElement('li');
        listItem.textContent = message;
        errorList.appendChild(listItem);
    });

    // Add heading and list to summary
    const heading = document.createElement('h3');
    heading.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fix the following errors:';
    errorSummary.appendChild(heading);
    errorSummary.appendChild(errorList);

    // Insert at the top of the form
    form.prepend(errorSummary);
}

/**
 * Initialize character counter for review textarea
 */
function initCharCounter() {
    const reviewTextarea = document.getElementById('review');
    const charCount = document.getElementById('char-count');

    if (!reviewTextarea || !charCount) return;

    // Update character count on input
    reviewTextarea.addEventListener('input', function() {
        const currentLength = this.value.length;
        charCount.textContent = currentLength;

        // Add warning class if approaching limit
        if (currentLength > 900 && currentLength <= 1000) {
            charCount.className = 'approaching-limit';
        }
        // Add over limit class if exceeding
        else if (currentLength > 1000) {
            charCount.className = 'over-limit';
        }
        // Normal otherwise
        else {
            charCount.className = '';
        }
    });

    // Initial count
    charCount.textContent = reviewTextarea.value.length;
}

/**
 * Initialize star rating hover effects
 */
function initStarRating() {
    const starLabels = document.querySelectorAll('.star-rating label');

    if (starLabels.length === 0) return;

    starLabels.forEach(label => {
        // Add hover class on mouseover
        label.addEventListener('mouseover', function() {
            // Reset all stars
            starLabels.forEach(l => l.classList.remove('hover'));

            // Add hover class to current and previous stars
            let current = this;
            while (current) {
                current.classList.add('hover');
                current = current.previousElementSibling?.previousElementSibling;
            }
        });

        // Remove hover class on mouseout from rating container
        document.querySelector('.star-rating').addEventListener('mouseout', function() {
            starLabels.forEach(l => l.classList.remove('hover'));
        });
    });
}

/**
 * Initialize game selection logic
 */
function initGameSelection() {
    const gameSelect = document.getElementById('game_name');
    const newGameInput = document.getElementById('new_game');

    if (!gameSelect || !newGameInput) return;

    // Clear new game input when selecting from dropdown
    gameSelect.addEventListener('change', function() {
        if (this.value !== '') {
            newGameInput.value = '';
        }
    });

    // Clear dropdown selection when entering new game
    newGameInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            gameSelect.value = '';
        }
    });
}

/**
 * Initialize filter form enhancements
 */
function initFilterForm() {
    const filterForm = document.querySelector('.filter-form');
    const gameFilter = document.getElementById('game-filter');

    if (!filterForm || !gameFilter) return;

    // Auto-submit form when changing rating filter
    const ratingFilter = document.getElementById('rating-filter');
    if (ratingFilter) {
        ratingFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    // Add autocomplete behavior to game filter
    if (gameFilter) {
        const gameOptions = Array.from(document.querySelectorAll('#game-filter-suggestions option'))
            .map(option => option.value);

        gameFilter.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            const datalist = document.getElementById('game-filter-suggestions');

            // Clear existing options
            while (datalist.firstChild) {
                datalist.removeChild(datalist.firstChild);
            }

            // Filter options based on input
            const filteredOptions = gameOptions.filter(option =>
                option.toLowerCase().includes(value)
            );

            // Add filtered options
            filteredOptions.forEach(option => {
                const optionEl = document.createElement('option');
                optionEl.value = option;
                datalist.appendChild(optionEl);
            });
        });
    }
}

/**
 * Show confirmation dialog before certain actions
 *
 * @param {string} message Confirmation message
 * @returns {boolean} True if confirmed, false otherwise
 */
function confirmAction(message) {
    return window.confirm(message);
}

/**
 * Display a toast notification
 *
 * @param {string} message Message to display
 * @param {string} type Type of notification (success, error, info)
 * @param {number} duration Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `<i class="fas ${getIconForType(type)}"></i> ${message}`;

    // Add to document
    document.body.appendChild(notification);

    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Remove after duration
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, duration);
}

/**
 * Get icon class for notification type
 *
 * @param {string} type Notification type
 * @returns {string} FontAwesome icon class
 */
function getIconForType(type) {
    switch (type) {
        case 'success':
            return 'fa-check-circle';
        case 'error':
            return 'fa-exclamation-circle';
        default:
            return 'fa-info-circle';
    }
}

// Add CSS for new elements created by JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .error-input {
            border-color: var(--error) !important;
        }
        
        .field-error {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .error-summary {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
        }
        
        .error-summary h3 {
            color: var(--error);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .error-summary ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .approaching-limit {
            color: var(--accent);
        }
        
        .over-limit {
            color: var(--error);
            font-weight: 700;
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            max-width: 300px;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .notification.success {
            border-left: 4px solid var(--success);
        }
        
        .notification.error {
            border-left: 4px solid var(--error);
        }
        
        .notification.info {
            border-left: 4px solid var(--primary);
        }
    `;
    document.head.appendChild(style);
});