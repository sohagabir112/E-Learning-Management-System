<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tmm_academy');

// Website Configuration
define('SITE_NAME', 'TMM Academy');
define('SITE_URL', 'http://localhost/tmm-academy');
define('ADMIN_URL', 'http://localhost/tmm-academy/admin');

// Stripe Payment Configuration
// Replace these with your actual Stripe API keys from https://dashboard.stripe.com/apikeys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key_here');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret_here');

// Currency settings
define('CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');

// File Paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/tmm-academy/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Sanitize input
function sanitize($data) {
    $conn = getDBConnection();
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Calculate discount percentage
function calculateDiscount($original, $discounted) {
    if ($original <= 0) return 0;
    return round((($original - $discounted) / $original) * 100);
}

// Generate star rating HTML
function generateStarRating($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $stars .= '<i class="fas fa-star"></i>';
        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }

    return $stars;
}

// Stripe Payment Functions
function createStripePaymentIntent($amount, $currency = 'usd', $metadata = []) {
    // Check if API keys are set
    if (STRIPE_SECRET_KEY === 'sk_test_your_secret_key_here' || empty(STRIPE_SECRET_KEY)) {
        error_log('Stripe API key not configured');
        return false;
    }

    $url = 'https://api.stripe.com/v1/payment_intents';

    $data = [
        'amount' => $amount * 100, // Convert to cents
        'currency' => $currency,
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => $metadata
    ];

    return makeStripeRequest('POST', $url, $data);
}

function makeStripeRequest($method, $url, $data = null) {
    // Check if curl is available
    if (!function_exists('curl_init')) {
        error_log('cURL is not available on this server');
        return false;
    }

    $ch = curl_init();

    $headers = [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Content-Type: application/x-www-form-urlencoded'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout

    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        error_log("cURL Error: " . $curlError);
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            return false;
        }
        return $decoded;
    } else {
        $errorData = json_decode($response, true);
        if ($errorData && isset($errorData['error']['message'])) {
            error_log("Stripe API Error: " . $errorData['error']['message']);
        } else {
            error_log("Stripe API Error (HTTP $httpCode): " . $response);
        }
        return false;
    }
}

function confirmStripePayment($paymentIntentId) {
    $url = "https://api.stripe.com/v1/payment_intents/{$paymentIntentId}/confirm";
    return makeStripeRequest('POST', $url);
}

function retrieveStripePaymentIntent($paymentIntentId) {
    $url = "https://api.stripe.com/v1/payment_intents/{$paymentIntentId}";
    return makeStripeRequest('GET', $url);
}
?>