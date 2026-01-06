<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "Write a Review - TMM Academy";
$user = getCurrentUser();

$success = '';
$error = '';

// Get user's enrolled courses (completed or with significant progress)
$conn = getDBConnection();
$sql = "SELECT c.id, c.title, c.slug, c.thumbnail,
               e.enrolled_at, e.payment_status,
               COALESCE(up.total_progress, 0) as progress_percentage,
               CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_review
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN (
            SELECT course_id, user_id, AVG(progress_percentage) as total_progress
            FROM user_progress
            GROUP BY course_id, user_id
        ) up ON up.course_id = c.id AND up.user_id = e.user_id
        LEFT JOIN reviews r ON r.course_id = c.id AND r.user_id = e.user_id
        WHERE e.user_id = ? AND e.payment_status = 'completed'
        AND (up.total_progress >= 30 OR up.total_progress IS NULL)
        ORDER BY e.enrolled_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$enrolled_courses = [];
while ($row = $result->fetch_assoc()) {
    $enrolled_courses[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = sanitize($_POST['course_id']);
    $rating = (int)$_POST['rating'];
    $title = sanitize($_POST['title']);
    $review_text = sanitize($_POST['review_text']);

    // Validation
    if (empty($course_id) || empty($rating) || empty($review_text)) {
        $error = 'All fields are required';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5 stars';
    } elseif (strlen($review_text) < 10) {
        $error = 'Review must be at least 10 characters long';
    } else {
        // Check if user is enrolled in this course
        $enrolled_check = false;
        foreach ($enrolled_courses as $course) {
            if ($course['id'] == $course_id) {
                $enrolled_check = true;
                break;
            }
        }

        if (!$enrolled_check) {
            $error = 'You can only review courses you are enrolled in';
        } else {
            // Check if user already reviewed this course
            $check_sql = "SELECT id FROM reviews WHERE user_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user['id'], $course_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error = 'You have already reviewed this course';
            } else {
                // Insert the review
                $is_approved = TRUE;
                $insert_sql = "INSERT INTO reviews (user_id, course_id, rating, title, review_text, is_approved)
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiisss", $user['id'], $course_id, $rating, $title, $review_text, $is_approved);

                if ($insert_stmt->execute()) {
                    $success = 'Thank you for your review! It has been submitted successfully.';
                    // Clear form data and redirect after showing success message
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                        }, 2000);
                    </script>";
                } else {
                    $error = 'Failed to submit review. Please try again.';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
    .review-form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 15px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }

    .review-form-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .review-form-header h2 {
        margin: 0 0 10px 0;
        font-size: 2rem;
    }

    .review-form-header p {
        margin: 0;
        opacity: 0.9;
    }

    .review-form-body {
        padding: 40px;
    }

    .course-selection {
        margin-bottom: 30px;
    }

    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .course-option {
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }

    .course-option:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .course-option.selected {
        border-color: var(--primary-color);
        background: rgba(44, 62, 80, 0.05);
    }

    .course-option img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .course-option h4 {
        margin: 0 0 5px 0;
        font-size: 1rem;
        color: var(--primary-color);
    }

    .course-option p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--gray-color);
    }

    .course-option .progress {
        margin-top: 10px;
        font-size: 0.8rem;
        color: var(--accent-color);
    }

    .course-radio {
        display: none;
    }

    .rating-section {
        margin-bottom: 30px;
    }

    .rating-stars {
        display: flex;
        gap: 5px;
        margin-bottom: 10px;
    }

    .star-rating {
        font-size: 2rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .star-rating:hover,
    .star-rating.active {
        color: #ffc107;
    }

    .rating-text {
        font-size: 0.9rem;
        color: var(--gray-color);
        margin-top: 5px;
    }

    .review-form-group {
        margin-bottom: 25px;
    }

    .review-form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--dark-color);
    }

    .review-input,
    .review-textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        font-family: inherit;
    }

    .review-input:focus,
    .review-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
    }

    .review-textarea {
        min-height: 120px;
        resize: vertical;
        line-height: 1.5;
    }

    .review-submit {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 20px;
    }

    .review-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(44, 62, 80, 0.3);
    }

    .review-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .no-courses {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-color);
    }

    .no-courses i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #ddd;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
    }

    .alert-success {
        background: rgba(39, 174, 96, 0.1);
        color: var(--accent-color);
        border: 1px solid rgba(39, 174, 96, 0.2);
    }

    .alert-error {
        background: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
        border: 1px solid rgba(231, 76, 60, 0.2);
    }

    @media (max-width: 768px) {
        .review-form-body {
            padding: 30px 20px;
        }

        .review-form-header {
            padding: 25px 20px;
        }

        .review-form-header h2 {
            font-size: 1.6rem;
        }

        .course-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .rating-stars {
            justify-content: center;
        }
    }
</style>

<div class="container" style="padding: 40px 0;">
    <div class="review-form-container">
        <div class="review-form-header">
            <h2><i class="fas fa-star"></i> Write a Review</h2>
            <p>Share your experience with other students</p>
        </div>

        <div class="review-form-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($enrolled_courses)): ?>
                <div class="no-courses">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>No Courses Available for Review</h3>
                    <p>You need to complete at least 30% of a course before you can leave a review.</p>
                    <a href="my-courses.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> Back to My Courses
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="" id="reviewForm">
                    <!-- Course Selection -->
                    <div class="course-selection review-form-group">
                        <label><i class="fas fa-book"></i> Select Course to Review</label>
                        <div class="course-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-option <?php echo ($course['has_review']) ? 'reviewed' : ''; ?>"
                                     onclick="selectCourse(<?php echo $course['id']; ?>)">
                                    <input type="radio" name="course_id" value="<?php echo $course['id']; ?>"
                                           class="course-radio" id="course_<?php echo $course['id']; ?>" required>
                                    <img src="../assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($course['title'], 0, 50)) . (strlen($course['title']) > 50 ? '...' : ''); ?></p>
                                    <div class="progress">
                                        Progress: <?php echo round($course['progress_percentage']); ?>%
                                        <?php if ($course['has_review']): ?>
                                            <span style="color: var(--accent-color); font-weight: 600;">(Already Reviewed)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div class="rating-section review-form-group">
                        <label><i class="fas fa-star"></i> Your Rating</label>
                        <div class="rating-stars" id="ratingStars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star-rating" data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">
                                    <i class="far fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                        <div class="rating-text" id="ratingText">Click to rate this course</div>
                    </div>

                    <!-- Review Title -->
                    <div class="review-form-group">
                        <label for="title"><i class="fas fa-heading"></i> Review Title (Optional)</label>
                        <input type="text" id="title" name="title" class="review-input"
                               placeholder="Summarize your experience in a few words">
                    </div>

                    <!-- Review Text -->
                    <div class="review-form-group">
                        <label for="review_text"><i class="fas fa-comment"></i> Your Review</label>
                        <textarea id="review_text" name="review_text" class="review-textarea" required
                                  placeholder="Tell others about your experience with this course. What did you learn? What did you like or dislike? Would you recommend it?"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="review-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Course selection functionality
function selectCourse(courseId) {
    // Remove selected class from all options
    document.querySelectorAll('.course-option').forEach(option => {
        option.classList.remove('selected');
    });

    // Add selected class to clicked option
    document.querySelector(`#course_${courseId}`).closest('.course-option').classList.add('selected');

    // Check the radio button
    document.querySelector(`#course_${courseId}`).checked = true;
}

// Rating functionality
let currentRating = 0;

function setRating(rating) {
    currentRating = rating;
    document.getElementById('ratingInput').value = rating;

    // Update star display
    const stars = document.querySelectorAll('.star-rating');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
            star.innerHTML = '<i class="fas fa-star"></i>';
        } else {
            star.classList.remove('active');
            star.innerHTML = '<i class="far fa-star"></i>';
        }
    });

    // Update rating text
    const ratingTexts = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    document.getElementById('ratingText').textContent = `${rating} star${rating > 1 ? 's' : ''} - ${ratingTexts[rating - 1]}`;
}

// Form validation
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    const courseSelected = document.querySelector('input[name="course_id"]:checked');
    const ratingSelected = document.getElementById('ratingInput').value;
    const reviewText = document.getElementById('review_text').value.trim();

    if (!courseSelected) {
        e.preventDefault();
        alert('Please select a course to review.');
        return;
    }

    if (!ratingSelected) {
        e.preventDefault();
        alert('Please provide a rating.');
        return;
    }

    if (reviewText.length < 10) {
        e.preventDefault();
        alert('Please write a review with at least 10 characters.');
        return;
    }

    // Disable submit button to prevent double submission
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
});

// Highlight already reviewed courses
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.course-option.reviewed').forEach(option => {
        option.style.opacity = '0.6';
        option.style.cursor = 'not-allowed';
        option.onclick = function(e) {
            e.preventDefault();
            alert('You have already reviewed this course.');
        };
    });
});
</script>

<?php include '../includes/footer.php'; ?>
