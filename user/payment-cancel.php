<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$reason = isset($_GET['reason']) ? sanitize($_GET['reason']) : 'cancelled';

$page_title = "Payment Cancelled";
include '../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 60px auto; text-align: center;">
    <div class="cancel-card" style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 50px; border-top: 5px solid var(--danger-color);">
        <div style="font-size: 4rem; color: var(--danger-color); margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i>
        </div>

        <h1 style="color: var(--danger-color); margin-bottom: 20px;">
            <?php echo $reason === 'failed' ? 'Payment Failed' : 'Payment Cancelled'; ?>
        </h1>

        <p style="color: var(--gray-color); font-size: 1.1rem; margin-bottom: 30px;">
            <?php if ($reason === 'failed'): ?>
                We're sorry, but your payment could not be processed. This might be due to insufficient funds, card declined, or other payment issues.
            <?php else: ?>
                Your payment was cancelled. No charges have been made to your account.
            <?php endif; ?>
        </p>

        <div class="error-details" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: left;">
            <strong>What you can do:</strong>
            <ul style="margin-top: 10px; margin-bottom: 0;">
                <li>Check your card details and try again</li>
                <li>Ensure sufficient funds are available</li>
                <li>Contact your bank if the issue persists</li>
                <li>Try a different payment method</li>
            </ul>
        </div>

        <div class="cancel-actions" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?php if ($course_id): ?>
            <a href="payment.php?course=<?php echo $course_id; ?>"
               class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-credit-card"></i> Try Again
            </a>
            <?php endif; ?>

            <a href="courses.php" class="btn btn-outline-primary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-search"></i> Browse Courses
            </a>

            <a href="my-courses.php" class="btn btn-outline-secondary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>

        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="color: var(--primary-color); margin-bottom: 10px;">Need Help?</h4>
            <p style="color: var(--gray-color); margin-bottom: 15px;">
                If you're experiencing issues with payment, our support team is here to help.
            </p>
            <a href="mailto:support@tmmacademy.com" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
        </div>
    </div>
</div>

<style>
    .cancel-card {
        animation: cancelAnimation 0.5s ease-out;
    }

    @keyframes cancelAnimation {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn {
        display: inline-block;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
    }

    .btn-outline-primary {
        background: white;
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background: var(--primary-color);
        color: white;
    }

    .btn-outline-secondary {
        background: white;
        color: var(--gray-color);
        border-color: var(--gray-color);
    }

    .btn-outline-secondary:hover {
        background: var(--gray-color);
        color: white;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
</style>

<?php include '../includes/footer.php'; ?>
