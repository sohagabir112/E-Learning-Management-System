<?php
// Ensure config is loaded
if (!defined('SITE_NAME')) {
    require_once 'config.php';
}

// Helper functions for navbar active states
function isActivePage($pageName) {
    return basename($_SERVER['PHP_SELF']) === $pageName;
}

function isActivePath($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js" defer></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">TMM ACADEMY</a>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" aria-label="Toggle navigation menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>

                <ul class="navbar-nav">

                    <!-- Public Navigation -->
                    <li><a class="nav-link <?php echo isActivePage('index.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/index.php">
                        <i class="fas fa-home"></i> Home
                    </a></li>
                    <li><a class="nav-link <?php echo isActivePage('courses.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/courses.php">
                        <i class="fas fa-book-open"></i> Courses
                    </a></li>
                    <li><a class="nav-link <?php echo isActivePage('faq.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/faq.php">
                        <i class="fas fa-question-circle"></i> FAQ
                    </a></li>

                    <?php if (isLoggedIn()): ?>
                        <!-- Learning Section -->
                        <li class="nav-divider"></li>
                        <li><a class="nav-link <?php echo isActivePage('my-courses.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/user/my-courses.php">
                            <i class="fas fa-graduation-cap"></i> My Courses
                        </a></li>
                        <li><a class="nav-link <?php echo isActivePage('wishlist.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/user/wishlist.php">
                            <i class="far fa-heart"></i> Wishlist
                        </a></li>
                        <li><a class="nav-link <?php echo isActivePage('review.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/user/review.php">
                            <i class="fas fa-star"></i> Write Review
                        </a></li>

                        <!-- Account Section -->
                        <li class="nav-divider"></li>
                        <li><a class="nav-link <?php echo isActivePage('profile.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/user/profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a></li>
                        <li><a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    <?php else: ?>
                        <!-- Authentication -->
                        <li class="nav-divider"></li>
                        <li><a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a></li>
                        <li><a class="nav-link nav-cta" href="<?php echo SITE_URL; ?>/register.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
