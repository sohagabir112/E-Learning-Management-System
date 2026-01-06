<?php
require_once 'includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get the raw POST data
$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // Verify webhook signature (uncomment when webhook secret is set)
    // $event = \Stripe\Webhook::constructEvent($payload, $sig_header, STRIPE_WEBHOOK_SECRET);

    // For development, parse the payload directly
    $event = json_decode($payload, true);

    if (!$event) {
        throw new Exception('Invalid JSON payload');
    }

    // Handle the event
    switch ($event['type']) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event['data']['object'];
            handlePaymentSuccess($paymentIntent);
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event['data']['object'];
            handlePaymentFailure($paymentIntent);
            break;

        default:
            // Unexpected event type
            error_log('Unhandled event type: ' . $event['type']);
    }

    // Return a response to acknowledge receipt of the event
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Webhook error: ' . $e->getMessage()]);
}

function handlePaymentSuccess($paymentIntent) {
    $conn = getDBConnection();

    // Extract metadata
    $metadata = $paymentIntent['metadata'];
    $courseId = $metadata['course_id'] ?? null;
    $userId = $metadata['user_id'] ?? null;
    $enrollmentId = $metadata['enrollment_id'] ?? null;

    if (!$courseId || !$userId) {
        error_log('Missing course_id or user_id in payment metadata');
        return;
    }

    $amountPaid = $paymentIntent['amount_received'] / 100; // Convert from cents
    $transactionId = $paymentIntent['id'];

    try {
        if ($enrollmentId && $enrollmentId !== 'new') {
            // Update existing enrollment
            $update_sql = "UPDATE enrollments SET payment_status = 'completed', amount_paid = ?, payment_method = 'card', transaction_id = ? WHERE id = ? AND user_id = ? AND course_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("dsiii", $amountPaid, $transactionId, $enrollmentId, $userId, $courseId);
            $update_stmt->execute();
        } else {
            // Create new enrollment
            $insert_sql = "INSERT INTO enrollments (user_id, course_id, payment_status, amount_paid, payment_method, transaction_id) VALUES (?, ?, 'completed', ?, 'card', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iids", $userId, $courseId, $amountPaid, $transactionId);
            $insert_stmt->execute();
        }

        error_log("Payment successful for course {$courseId}, user {$userId}, amount: {$amountPaid}");

    } catch (Exception $e) {
        error_log('Database error in webhook handler: ' . $e->getMessage());
    }

    $conn->close();
}

function handlePaymentFailure($paymentIntent) {
    $conn = getDBConnection();

    // Extract metadata
    $metadata = $paymentIntent['metadata'];
    $courseId = $metadata['course_id'] ?? null;
    $userId = $metadata['user_id'] ?? null;

    if (!$courseId || !$userId) {
        error_log('Missing course_id or user_id in payment metadata');
        return;
    }

    try {
        // Update enrollment status to failed
        $update_sql = "UPDATE enrollments SET payment_status = 'failed' WHERE user_id = ? AND course_id = ? AND payment_status = 'pending'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $userId, $courseId);
        $update_stmt->execute();

        error_log("Payment failed for course {$courseId}, user {$userId}");

    } catch (Exception $e) {
        error_log('Database error in webhook failure handler: ' . $e->getMessage());
    }

    $conn->close();
}
?>
