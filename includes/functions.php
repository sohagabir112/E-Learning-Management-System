<?php
require_once 'config.php';

// Utility Functions

/**
 * Check if a string starts with a given substring
 */
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if a string ends with a given substring
 */
function endsWith($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Generate a random string of specified length
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

/**
 * Get featured reviews for homepage display
 */
function getFeaturedReviews($limit = 6) {
    $conn = getDBConnection();
    $sql = "SELECT r.*, u.full_name, u.username, c.title as course_title, c.slug as course_slug
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN courses c ON r.course_id = c.id
            WHERE r.is_approved = TRUE AND r.is_featured = TRUE
            ORDER BY r.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

/**
 * Get recent approved reviews for homepage display
 */
function getRecentReviews($limit = 6) {
    $conn = getDBConnection();
    $sql = "SELECT r.*, u.full_name, u.username, c.title as course_title, c.slug as course_slug
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN courses c ON r.course_id = c.id
            WHERE r.is_approved = TRUE
            ORDER BY r.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

/**
 * Get reviews for a specific course
 */
function getCourseReviews($course_id, $limit = 10) {
    $conn = getDBConnection();
    $sql = "SELECT r.*, u.full_name, u.username
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.course_id = ? AND r.is_approved = TRUE
            ORDER BY r.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $course_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

/**
 * Get average rating for a course
 */
function getCourseAverageRating($course_id) {
    $conn = getDBConnection();
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
            FROM reviews
            WHERE course_id = ? AND is_approved = TRUE";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return [
        'average' => round($row['avg_rating'] ?? 0, 1),
        'total' => (int)($row['total_reviews'] ?? 0)
    ];
}

/**
 * Check if file extension is allowed
 */
function isAllowedFileType($filename, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowedTypes);
}

/**
 * Get course difficulty badge color
 */
function getDifficultyColor($difficulty) {
    switch (strtolower($difficulty)) {
        case 'beginner':
            return '#27AE60';
        case 'intermediate':
            return '#E67E22';
        case 'advanced':
            return '#E74C3C';
        default:
            return '#95A5A6';
    }
}

/**
 * Generate URL slug from title
 */
function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

/**
 * Get user enrollment status for a course
 */
function getUserEnrollmentStatus($userId, $courseId) {
    $conn = getDBConnection();
    $sql = "SELECT payment_status FROM enrollments WHERE user_id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['payment_status'];
    }
    return null;
}

/**
 * Check if user has completed a course
 */
function hasUserCompletedCourse($userId, $courseId) {
    $conn = getDBConnection();
    $sql = "SELECT MAX(progress_percentage) as progress FROM user_progress WHERE user_id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['progress'] >= 100;
    }
    return false;
}

/**
 * Get course progress for user
 */
function getUserCourseProgress($userId, $courseId) {
    $conn = getDBConnection();
    $sql = "SELECT AVG(progress_percentage) as progress FROM user_progress WHERE user_id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return round($row['progress'] ?: 0);
    }
    return 0;
}

/**
 * Get total enrolled students for a course
 */
function getCourseEnrollmentCount($courseId) {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ? AND payment_status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

/**
 * Get course rating (placeholder - implement when reviews are added)
 */
function getCourseRating($courseId) {
    // Placeholder - return random rating for now
    return rand(35, 50) / 10;
}

/**
 * Log user activity
 */
function logUserActivity($userId, $action, $details = '') {
    $conn = getDBConnection();
    $sql = "INSERT INTO user_activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt->bind_param("issss", $userId, $action, $details, $ip, $userAgent);
    $stmt->execute();
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get time ago string
 */
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60);
    $hours        = round($seconds / 3600);
    $days         = round($seconds / 86400);
    $weeks        = round($seconds / 604800);
    $months       = round($seconds / 2629440);
    $years        = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "1 day ago" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>
