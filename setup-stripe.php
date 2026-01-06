<?php
require_once 'includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $publishableKey = trim($_POST['publishable_key'] ?? '');
    $secretKey = trim($_POST['secret_key'] ?? '');
    $webhookSecret = trim($_POST['webhook_secret'] ?? '');

    if (empty($publishableKey) || empty($secretKey)) {
        $message = 'Both publishable key and secret key are required.';
    } else {
        // Update the config file
        $configFile = 'includes/config.php';
        $configContent = file_get_contents($configFile);

        $configContent = preg_replace(
            "/define\('STRIPE_PUBLISHABLE_KEY',\s*'[^']*'\);/",
            "define('STRIPE_PUBLISHABLE_KEY', '$publishableKey');",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('STRIPE_SECRET_KEY',\s*'[^']*'\);/",
            "define('STRIPE_SECRET_KEY', '$secretKey');",
            $configContent
        );

        if (!empty($webhookSecret)) {
            $configContent = preg_replace(
                "/define\('STRIPE_WEBHOOK_SECRET',\s*'[^']*'\);/",
                "define('STRIPE_WEBHOOK_SECRET', '$webhookSecret');",
                $configContent
            );
        }

        if (file_put_contents($configFile, $configContent)) {
            $message = 'Stripe API keys updated successfully!';
            $success = true;
        } else {
            $message = 'Failed to update configuration file. Please check file permissions.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Setup - TMM Academy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background: #6772e5;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5469d4;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Stripe Payment Setup</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="message info">
                <strong>How to get your Stripe API keys:</strong>
                <ol>
                    <li>Go to <a href="https://dashboard.stripe.com/" target="_blank">Stripe Dashboard</a></li>
                    <li>Navigate to <strong>Developers → API keys</strong></li>
                    <li>Copy your <strong>Publishable key</strong> (starts with pk_test_)</li>
                    <li>Copy your <strong>Secret key</strong> (starts with sk_test_)</li>
                </ol>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="publishable_key">Publishable Key (pk_test_...)</label>
                    <input type="text" id="publishable_key" name="publishable_key"
                           placeholder="pk_test_your_publishable_key_here" required>
                </div>

                <div class="form-group">
                    <label for="secret_key">Secret Key (sk_test_...)</label>
                    <input type="password" id="secret_key" name="secret_key"
                           placeholder="sk_test_your_secret_key_here" required>
                </div>

                <div class="form-group">
                    <label for="webhook_secret">Webhook Secret (Optional - whsec_...)</label>
                    <input type="text" id="webhook_secret" name="webhook_secret"
                           placeholder="whsec_your_webhook_secret_here">
                </div>

                <button type="submit" class="btn">Save Stripe Keys</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <p><strong>After setup:</strong></p>
            <ul>
                <li><a href="test-stripe.php">Run the connection test</a></li>
                <li><a href="courses.php">Try enrolling in a course</a></li>
                <li>Delete this setup file for security</li>
            </ul>
        </div>
    </div>
</body>
</html>
