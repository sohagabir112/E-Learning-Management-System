<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    $_SESSION['error'] = 'Invalid course selected';
    redirect('../courses.php');
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
    redirect('../courses.php');
}

$course = $course_result->fetch_assoc();

// Check if already enrolled
$check_sql = "SELECT id, payment_status FROM enrollments WHERE user_id = ? AND course_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $course_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $enrollment = $check_result->fetch_assoc();
    if ($enrollment['payment_status'] === 'completed') {
        $_SESSION['success'] = 'You are already enrolled in this course';
        redirect('learning.php?course=' . $course_id);
    }
    // If payment was pending, redirect to payment
    redirect('payment.php?course=' . $course_id);
}

// Check if course is free or has a price
$course_price = $course['discount_price'] ?: $course['price'];

if ($course_price <= 0) {
    // Free course - enroll directly
    $enroll_sql = "INSERT INTO enrollments (user_id, course_id, payment_status, amount_paid, payment_method)
                   VALUES (?, ?, 'completed', 0, 'free')
                   ON DUPLICATE KEY UPDATE payment_status = 'completed'";

    $enroll_stmt = $conn->prepare($enroll_sql);
    $enroll_stmt->bind_param("ii", $user_id, $course_id);

    if ($enroll_stmt->execute()) {
        $_SESSION['success'] = 'Successfully enrolled in "' . $course['title'] . '"! You can now start learning.';
        redirect('learning.php?course=' . $course_id);
    } else {
        $_SESSION['error'] = 'Failed to enroll in course. Please try again.';
        redirect('../course-detail.php?slug=' . $course['slug']);
    }
} else {
    // Paid course - redirect to payment (now shows card form)
    redirect('payment.php?course=' . $course_id);
}
?>
