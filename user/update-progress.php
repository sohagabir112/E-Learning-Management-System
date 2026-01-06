<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
$progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
$user_id = $_SESSION['user_id'];

if (!$video_id || $progress < 0 || $progress > 100) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get course_id from video
$conn = getDBConnection();
$video_sql = "SELECT v.id, m.course_id FROM videos v JOIN modules m ON v.module_id = m.id WHERE v.id = ?";
$video_stmt = $conn->prepare($video_sql);
$video_stmt->bind_param("i", $video_id);
$video_stmt->execute();
$video_result = $video_stmt->get_result();

if ($video_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Video not found']);
    exit;
}

$video_data = $video_result->fetch_assoc();
$course_id = $video_data['course_id'];

// Check if user is enrolled in this course
$enrollment_sql = "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'";
$enrollment_stmt = $conn->prepare($enrollment_sql);
$enrollment_stmt->bind_param("ii", $user_id, $course_id);
$enrollment_stmt->execute();

if ($enrollment_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Not enrolled in this course']);
    exit;
}

// Update or insert progress
$progress_sql = "INSERT INTO user_progress (user_id, course_id, video_id, progress_percentage, watched_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE progress_percentage = VALUES(progress_percentage), watched_at = NOW()";

$progress_stmt = $conn->prepare($progress_sql);
$progress_stmt->bind_param("iiii", $user_id, $course_id, $video_id, $progress);

if ($progress_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Progress updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
}

$conn->close();
?>
