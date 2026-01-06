<?php
require_once 'includes/config.php';

echo "<h1>Stripe Configuration Test</h1>";

// Test 1: Check if API keys are configured
echo "<h2>Test 1: API Keys Configuration</h2>";
if (STRIPE_SECRET_KEY === 'sk_test_your_secret_key_here') {
    echo "<p style='color: red;'>❌ FAIL: Stripe Secret Key not configured</p>";
    echo "<p>Please update STRIPE_SECRET_KEY in includes/config.php with your actual Stripe secret key.</p>";
} else {
    echo "<p style='color: green;'>✅ PASS: Stripe Secret Key is configured</p>";
}

if (STRIPE_PUBLISHABLE_KEY === 'pk_test_your_publishable_key_here') {
    echo "<p style='color: red;'>❌ FAIL: Stripe Publishable Key not configured</p>";
    echo "<p>Please update STRIPE_PUBLISHABLE_KEY in includes/config.php with your actual Stripe publishable key.</p>";
} else {
    echo "<p style='color: green;'>✅ PASS: Stripe Publishable Key is configured</p>";
}

// Test 2: Check if cURL is available
echo "<h2>Test 2: cURL Availability</h2>";
if (!function_exists('curl_init')) {
    echo "<p style='color: red;'>❌ FAIL: cURL is not available on this server</p>";
    echo "<p>cURL extension is required for Stripe API calls. Please enable it in your PHP configuration.</p>";
} else {
    echo "<p style='color: green;'>✅ PASS: cURL is available</p>";
}

// Test 3: Test API connection (only if keys are configured)
echo "<h2>Test 3: Stripe API Connection</h2>";
if (STRIPE_SECRET_KEY !== 'sk_test_your_secret_key_here' && function_exists('curl_init')) {
    $testAmount = 100; // $1.00 in cents
    $testResult = createStripePaymentIntent($testAmount, 'usd', ['test' => 'connection']);

    if ($testResult && isset($testResult['id'])) {
        echo "<p style='color: green;'>✅ PASS: Successfully connected to Stripe API</p>";
        echo "<p>Payment Intent ID: " . $testResult['id'] . "</p>";
        echo "<p>Status: " . $testResult['status'] . "</p>";

        // Clean up test payment intent
        $cancelResult = makeStripeRequest('POST', "https://api.stripe.com/v1/payment_intents/{$testResult['id']}/cancel");
        if ($cancelResult) {
            echo "<p>Test payment intent cancelled successfully.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ FAIL: Could not connect to Stripe API</p>";
        echo "<p>Please check your API keys and internet connection.</p>";
        echo "<p>Make sure you're using test keys that start with 'sk_test_' and 'pk_test_'.</p>";
    }
} else {
    echo "<p style='color: orange;'>⏭️ SKIP: Configure API keys and cURL first</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Get your Stripe API keys from <a href='https://dashboard.stripe.com/apikeys' target='_blank'>Stripe Dashboard</a></li>";
echo "<li>Update includes/config.php with your actual keys</li>";
echo "<li>Run this test again to verify everything works</li>";
echo "<li>Test a real payment with a course enrollment</li>";
echo "</ol>";

echo "<p><strong>Test Card Numbers for Testing:</strong></p>";
echo "<ul>";
echo "<li><strong>Success:</strong> 4242 4242 4242 4242</li>";
echo "<li><strong>Decline:</strong> 4000 0000 0000 0002</li>";
echo "<li><strong>Any future expiry date and any 3-digit CVC</strong></li>";
echo "</ul>";
?>
