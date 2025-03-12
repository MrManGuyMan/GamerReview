<?php
require_once 'functions/auth.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <h1>Game Reviews</h1>
    <nav>
        <a href="index.php" class="button <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="reviews.php" class="button <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i> View Reviews
        </a>
        <?php if (Auth::isLoggedIn()): ?>
            <a href="add-review.php" class="button <?php echo $current_page === 'add-review.php' ? 'active' : ''; ?>">
                <i class="fas fa-pen"></i> Add Review
            </a>
            <a href="logout.php" class="button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="button <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="register.php" class="button <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Register
            </a>
        <?php endif; ?>
    </nav>
</header>
