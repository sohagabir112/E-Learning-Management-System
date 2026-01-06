<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "Payment Successful";
include '../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 60px auto; text-align: center;">
    <div class="success-card" style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 50px; border-top: 5px solid var(--success-color);">
        <div style="font-size: 4rem; color: var(--success-color); margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
        </div>

        <h1 style="color: var(--success-color); margin-bottom: 20px;">Payment Successful!</h1>

        <p style="color: var(--gray-color); font-size: 1.1rem; margin-bottom: 30px;">
            Congratulations! Your payment has been processed successfully. You now have full access to your course.
        </p>

        <div class="success-actions" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="learning.php?course=<?php echo isset($_GET['course']) ? (int)$_GET['course'] : ''; ?>"
               class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-play-circle"></i> Start Learning
            </a>

            <a href="my-courses.php" class="btn btn-outline-primary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-book"></i> My Courses
            </a>

            <a href="my-courses.php" class="btn btn-outline-secondary" style="padding: 12px 25px; font-size: 1rem;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>

        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="color: var(--primary-color); margin-bottom: 10px;">What happens next?</h4>
            <ul style="text-align: left; color: var(--gray-color); line-height: 1.6;">
                <li><i class="fas fa-check" style="color: var(--success-color); margin-right: 8px;"></i> Lifetime access to your course</li>
                <li><i class="fas fa-check" style="color: var(--success-color); margin-right: 8px;"></i> Downloadable resources and materials</li>
                <li><i class="fas fa-check" style="color: var(--success-color); margin-right: 8px;"></i> Certificate of completion upon finishing</li>
                <li><i class="fas fa-check" style="color: var(--success-color); margin-right: 8px;"></i> 24/7 support access</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .success-card {
        animation: successAnimation 0.5s ease-out;
    }

    @keyframes successAnimation {
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
</style>

<?php include '../includes/footer.php'; ?>
