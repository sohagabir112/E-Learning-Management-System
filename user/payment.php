<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    $_SESSION['error'] = 'No course selected for payment';
    redirect('my-courses.php');
}

// Get course details
$conn = getDBConnection();
$course_sql = "SELECT * FROM courses WHERE id = ? AND is_active = 1";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows === 0) {
    $_SESSION['error'] = 'Course not found';
    redirect('my-courses.php');
}

$course = $course_result->fetch_assoc();

// Check if already enrolled
$check_sql = "SELECT id, payment_status FROM enrollments WHERE user_id = ? AND course_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $course_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

$already_enrolled = false;
$enrollment_id = null;
if ($check_result->num_rows > 0) {
    $enrollment = $check_result->fetch_assoc();
    if ($enrollment['payment_status'] === 'completed') {
        $_SESSION['success'] = 'You are already enrolled in this course';
        redirect('learning.php?course=' . $course_id);
    }
    $already_enrolled = true;
    $enrollment_id = $enrollment['id'];
}

$course_price = $course['discount_price'] ?: $course['price'];
$discount_amount = $course['discount_price'] ? $course['price'] - $course['discount_price'] : 0;

// Handle demo payment initialization
$showPaymentForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment_intent'])) {
    // Create or update enrollment record with pending status
    if (!$already_enrolled) {
        // Create new enrollment with pending status
        $insert_sql = "INSERT INTO enrollments (user_id, course_id, payment_status, amount_paid, payment_method) VALUES (?, ?, 'pending', ?, 'card')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iid", $user_id, $course_id, $course_price);
        $insert_stmt->execute();
        $enrollment_id = $conn->insert_id;
    }

    // Show payment form
    $showPaymentForm = true;

    // Generate a mock client secret for demo purposes
    $clientSecret = 'demo_client_secret_' . time() . '_' . $enrollment_id;
    $_SESSION['demo_payment'] = [
        'enrollment_id' => $enrollment_id,
        'course_id' => $course_id,
        'amount' => $course_price,
        'user_id' => $user_id
    ];
}

// Handle demo payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $cardNumber = sanitize($_POST['card_number'] ?? '');
    $expiryDate = sanitize($_POST['expiry_date'] ?? '');
    $cvv = sanitize($_POST['cvv'] ?? '');
    $cardholderName = sanitize($_POST['cardholder_name'] ?? '');

    $errors = [];

    // Basic validation
    if (empty($cardNumber) || strlen(preg_replace('/\s+/', '', $cardNumber)) < 13) {
        $errors[] = 'Please enter a valid card number';
    }

    if (empty($expiryDate) || !preg_match('/^\d{2}\/\d{2}$/', $expiryDate)) {
        $errors[] = 'Please enter a valid expiry date (MM/YY)';
    }

    if (empty($cvv) || strlen($cvv) < 3) {
        $errors[] = 'Please enter a valid CVV';
    }

    if (empty($cardholderName)) {
        $errors[] = 'Please enter the cardholder name';
    }

    // Demo payment logic - always succeed for valid inputs
    if (empty($errors)) {
        $demoPaymentData = $_SESSION['demo_payment'] ?? null;
        if ($demoPaymentData) {
            $enrollmentId = $demoPaymentData['enrollment_id'];
            $amountPaid = $demoPaymentData['amount'];
            $transactionId = 'DEMO_' . time() . '_' . rand(1000, 9999);

            // Update enrollment to completed
            $update_sql = "UPDATE enrollments SET payment_status = 'completed', amount_paid = ?, payment_method = 'card', transaction_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("dsi", $amountPaid, $transactionId, $enrollmentId);
            $update_stmt->execute();

            // Clear demo payment session
            unset($_SESSION['demo_payment']);

            $_SESSION['success'] = 'Payment successful!';
            redirect('learning.php?course=' . $course_id);
        } else {
            $errors[] = 'Payment session expired. Please try again.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['payment_errors'] = $errors;
        $showPaymentForm = true; // Keep form visible to show errors
    }
}

// Handle payment completion (legacy Stripe handling)
if (isset($_GET['payment_intent']) && isset($_GET['payment_intent_client_secret'])) {
    $paymentIntentId = $_GET['payment_intent'];
    $paymentIntentData = retrieveStripePaymentIntent($paymentIntentId);

    if ($paymentIntentData && $paymentIntentData['status'] === 'succeeded') {
        // Payment was successful
        $amountPaid = $paymentIntentData['amount_received'] / 100; // Convert from cents

        if ($already_enrolled) {
            // Update existing enrollment
            $update_sql = "UPDATE enrollments SET payment_status = 'completed', amount_paid = ?, payment_method = 'card', transaction_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("dsi", $amountPaid, $paymentIntentId, $enrollment_id);
            $update_stmt->execute();
        } else {
            // Create new enrollment
            $insert_sql = "INSERT INTO enrollments (user_id, course_id, payment_status, amount_paid, payment_method, transaction_id) VALUES (?, ?, 'completed', ?, 'card', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iids", $user_id, $course_id, $amountPaid, $paymentIntentId);
            $insert_stmt->execute();
        }

        $_SESSION['success'] = 'Payment successful! You are now enrolled in "' . $course['title'] . '"';
        redirect('learning.php?course=' . $course_id);
    } elseif ($paymentIntentData && $paymentIntentData['status'] === 'requires_payment_method') {
        $_SESSION['error'] = 'Payment was cancelled. Please try again.';
    } else {
        $_SESSION['error'] = 'Payment failed. Please try again or contact support.';
    }
}

$page_title = "Payment - " . $course['title'];
include '../includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
    <div class="payment-container">
        <!-- Course Summary -->
        <div class="course-summary-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Course Payment</h2>

            <div style="display: flex; gap: 30px; align-items: flex-start;">
                <img src="../assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>"
                    alt="<?php echo $course['title']; ?>"
                    style="width: 200px; height: 150px; object-fit: cover; border-radius: 8px;">

                <div style="flex: 1;">
                    <h3 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p style="color: var(--gray-color); margin-bottom: 20px;"><?php echo htmlspecialchars($course['short_description']); ?></p>

                    <div style="display: flex; gap: 20px; color: var(--gray-color); font-size: 0.9rem;">
                        <span><i class="fas fa-clock"></i> <?php echo $course['duration_hours']; ?> hours</span>
                        <span><i class="fas fa-signal"></i> <?php echo ucfirst($course['difficulty']); ?> Level</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="payment-form-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px;">
            <h3 style="color: var(--primary-color); margin-bottom: 25px;">Payment Details</h3>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>

                    <?php if (strpos($_SESSION['error'] ?? '', 'not configured') !== false): ?>
                        <br><br>
                        <a href="../setup-stripe.php" style="color: #721c24; text-decoration: underline;">
                            <i class="fas fa-cog"></i> Configure Stripe API Keys
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['payment_errors'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <strong>Please correct the following errors:</strong>
                    <ul style="margin-top: 10px; margin-bottom: 0;">
                        <?php foreach ($_SESSION['payment_errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['payment_errors']); ?>
            <?php endif; ?>

            <!-- Order Summary -->
            <div class="order-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h4 style="margin-bottom: 15px; color: var(--primary-color);">Order Summary</h4>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Course Price:</span>
                    <span><?php echo $course['discount_price'] ? '$' . number_format($course['price'], 2) : '$' . number_format($course_price, 2); ?></span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--danger-color);">
                    <span>Discount:</span>
                    <span>-$<?php echo number_format($discount_amount, 2); ?></span>
                </div>
                <?php endif; ?>

                <hr style="margin: 15px 0; border: none; border-top: 1px solid #dee2e6;">

                <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem;">
                    <span>Total:</span>
                    <span style="color: var(--primary-color);">$<?php echo number_format($course_price, 2); ?></span>
                </div>
            </div>

            <!-- Payment Form -->
            <div id="payment-form-container">
                <div class="payment-methods" style="margin-bottom: 30px;">
                    <h4 style="margin-bottom: 20px; color: var(--primary-color);">Secure Payment</h4>
                    <p style="color: var(--gray-color); margin-bottom: 20px;">
                        <i class="fas fa-shield-alt" style="color: var(--success-color);"></i>
                        Your payment is secured with industry-standard encryption
                    </p>
                </div>

                <?php if (!$showPaymentForm): ?>
                <!-- Initial payment button to create payment intent -->
                <form method="POST" action="">
                    <input type="hidden" name="create_payment_intent" value="1">
                    <button type="submit" class="btn-payment" style="width: 100%; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s;">
                        <i class="fas fa-credit-card"></i> Proceed to Payment - $<?php echo number_format($course_price, 2); ?>
                    </button>
                </form>
                <?php else: ?>
                <!-- Payment form -->
                <form id="payment-form" method="POST" action="">
                    <input type="hidden" name="process_payment" value="1">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="card_number" style="display: block; margin-bottom: 8px; font-weight: 500;">Card Number</label>
                        <input type="text" id="card_number" name="card_number" class="form-control"
                               placeholder="1234 5678 9012 3456" maxlength="19" required
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="expiry_date" style="display: block; margin-bottom: 8px; font-weight: 500;">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" class="form-control"
                                   placeholder="MM/YY" maxlength="5" required
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="cvv" style="display: block; margin-bottom: 8px; font-weight: 500;">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="form-control"
                                   placeholder="123" maxlength="4" required
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="cardholder_name" style="display: block; margin-bottom: 8px; font-weight: 500;">Cardholder Name</label>
                        <input type="text" id="cardholder_name" name="cardholder_name" class="form-control"
                               placeholder="John Doe" required
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>

                    <div id="card-errors" role="alert" style="color: var(--danger-color); font-size: 0.9rem; margin-bottom: 20px; display: none;"></div>

                    <button type="submit" id="submit-payment" class="btn-payment" style="width: 100%; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s;">
                        <i class="fas fa-credit-card"></i> Complete Payment - $<?php echo number_format($course_price, 2); ?>
                    </button>

                    <div id="payment-message" style="margin-top: 15px; text-align: center;"></div>
                </form>
                <?php endif; ?>

                <p style="text-align: center; margin-top: 20px; color: var(--gray-color); font-size: 0.9rem;">
                    <i class="fas fa-lock"></i> SSL Encrypted | Secure Payment Processing
                </p>
            </div>
        </div>

        <!-- Back to Course -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="../course-detail.php?slug=<?php echo $course['slug']; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Course Details
            </a>
        </div>
    </div>
</div>

<style>
    .payment-option:hover {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-payment:hover {
        transform: translateY(-2px);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    /* Payment Form Styling */
    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .form-control:invalid {
        border-color: var(--danger-color);
    }
</style>

<?php if ($showPaymentForm): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-payment');
    const errorElement = document.getElementById('card-errors');
    const messageElement = document.getElementById('payment-message');

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

    form.addEventListener('submit', function(event) {
        // Clear previous errors
        errorElement.style.display = 'none';
        errorElement.textContent = '';

        // Basic client-side validation
        const cardNumber = cardNumberInput.value.replace(/\s+/g, '');
        const expiry = expiryInput.value;
        const cvv = cvvInput.value;
        const cardholderName = document.getElementById('cardholder_name').value;

        let errors = [];

        if (cardNumber.length < 13) {
            errors.push('Card number must be at least 13 digits');
        }

        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            errors.push('Expiry date must be in MM/YY format');
        }

        if (cvv.length < 3) {
            errors.push('CVV must be at least 3 digits');
        }

        if (!cardholderName.trim()) {
            errors.push('Cardholder name is required');
        }

        if (errors.length > 0) {
            event.preventDefault();
            errorElement.innerHTML = errors.join('<br>');
            errorElement.style.display = 'block';
            return;
        }

        // Show processing state
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';

        // For demo purposes, add a small delay to simulate processing
        setTimeout(() => {
            messageElement.innerHTML = '<div style="color: var(--success-color);"><i class="fas fa-check-circle"></i> Payment processed successfully!</div>';
        }, 1000);
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
