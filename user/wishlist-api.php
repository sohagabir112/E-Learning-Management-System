<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Check if course exists
$conn = getDBConnection();
$course_sql = "SELECT id FROM courses WHERE id = ? AND is_active = 1";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();

if ($course_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
    exit;
}

if ($action === 'add') {
    // Check if already in wishlist
    $check_sql = "SELECT id FROM wishlists WHERE user_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $course_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Course already in wishlist']);
        exit;
    }

    // Add to wishlist
    $insert_sql = "INSERT INTO wishlists (user_id, course_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $course_id);

    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Course added to wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
    }

} elseif ($action === 'remove') {
    // Remove from wishlist
    $delete_sql = "DELETE FROM wishlists WHERE user_id = ? AND course_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $course_id);

    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Course removed from wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
    }
}

$conn->close();
?>
