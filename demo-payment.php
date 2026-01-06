<?php
require_once 'includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = $_POST['card_number'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $cardholderName = $_POST['cardholder_name'] ?? '';

    // Demo validation - always accept
    if (!empty($cardNumber) && !empty($expiryDate) && !empty($cvv) && !empty($cardholderName)) {
        $message = '✅ Payment processed successfully! This is a demo - no real transaction occurred.';
        $success = true;
    } else {
        $message = '❌ Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Payment Form - TMM Academy</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .card-inputs {
            display: flex;
            gap: 15px;
        }

        .card-inputs .form-group {
            flex: 1;
        }

        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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

        .demo-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }

        .test-cards {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .test-cards h3 {
            margin-top: 0;
            color: #333;
        }

        .test-cards ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .test-cards li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Demo Payment Form</h1>

        <div class="demo-notice">
            <strong>🎭 Demo Mode:</strong> This form accepts any valid-looking card details for testing purposes. No real payments are processed.
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number"
                       placeholder="1234 5678 9012 3456" maxlength="19" required>
            </div>

            <div class="card-inputs">
                <div class="form-group">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="text" id="expiry_date" name="expiry_date"
                           placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv"
                           placeholder="123" maxlength="4" required>
                </div>
            </div>

            <div class="form-group">
                <label for="cardholder_name">Cardholder Name</label>
                <input type="text" id="cardholder_name" name="cardholder_name"
                       placeholder="John Doe" required>
            </div>

            <button type="submit" class="btn" id="submit-btn">
                💳 Complete Payment - $99.99
            </button>
        </form>

        <div class="test-cards">
            <h3>🧪 Test Card Details</h3>
            <p>Use any of these for testing:</p>
            <ul>
                <li><strong>Card Number:</strong> 4242 4242 4242 4242</li>
                <li><strong>Expiry:</strong> 12/25 (any future date)</li>
                <li><strong>CVV:</strong> 123 (any 3 digits)</li>
                <li><strong>Name:</strong> Any name</li>
            </ul>
            <p><em>All test cards will be accepted in demo mode.</em></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submit-btn');

            // Card number formatting
            const cardNumberInput = document.getElementById('card_number');
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                e.target.value = formattedValue;
            });

            // Expiry date formatting
            const expiryInput = document.getElementById('expiry_date');
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });

            // CVV formatting (numbers only)
            const cvvInput = document.getElementById('cvv');
            cvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.textContent = '🔄 Processing...';
            });
        });
    </script>
</body>
</html>
