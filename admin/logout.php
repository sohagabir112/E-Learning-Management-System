<?php
require_once '../includes/config.php';

// Clear all admin session data
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_logged_in']);

// Redirect to admin login page
redirect('index.php');
?>
