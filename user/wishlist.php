<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "My Wishlist - TMM Academy";
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Get user's wishlist with course details
$sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name, w.added_at
        FROM wishlists w
        JOIN courses c ON w.course_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE w.user_id = ? AND c.is_active = 1
        ORDER BY w.added_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_items = [];
while ($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row;
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 40px auto;">
    <div class="wishlist-header" style="margin-bottom: 40px;">
        <h1 style="color: var(--primary-color); margin-bottom: 10px;">My Wishlist</h1>
        <p style="color: var(--gray-color); font-size: 1.1rem;">
            Courses you've saved for later - <?php echo count($wishlist_items); ?> item<?php echo count($wishlist_items) !== 1 ? 's' : ''; ?>
        </p>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <!-- Empty wishlist state -->
        <div class="empty-wishlist" style="text-align: center; padding: 80px 20px; background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <div style="font-size: 5rem; color: #e1e8ed; margin-bottom: 30px;">
                <i class="far fa-heart"></i>
            </div>
            <h2 style="color: var(--primary-color); margin-bottom: 15px;">Your wishlist is empty</h2>
            <p style="color: var(--gray-color); font-size: 1.1rem; margin-bottom: 30px;">
                Start exploring courses and save the ones you're interested in for later!
            </p>
            <a href="../courses.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem;">
                <i class="fas fa-search"></i> Browse Courses
            </a>
        </div>
    <?php else: ?>
        <!-- Wishlist items -->
        <div class="wishlist-grid" style="display: grid; gap: 25px;">
            <?php foreach ($wishlist_items as $item): ?>
            <div class="wishlist-item" style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.3s;">
                <div style="display: flex; flex-wrap: wrap;">
                    <!-- Course Image -->
                    <div style="flex: 0 0 300px; position: relative;">
                        <img src="../assets/images/courses/<?php echo $item['thumbnail'] ?: 'default-course.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             style="width: 100%; height: 200px; object-fit: cover;">

                        <!-- Remove button -->
                        <button onclick="removeFromWishlist(<?php echo $item['id']; ?>, this)"
                                class="remove-btn"
                                style="position: absolute; top: 15px; right: 15px; background: rgba(220, 53, 69, 0.9); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.3s;">
                            <i class="fas fa-times"></i>
                        </button>

                        <!-- Price badge -->
                        <div style="position: absolute; bottom: 15px; left: 15px;">
                            <?php if ($item['discount_price']): ?>
                                <div style="background: var(--danger-color); color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                                    $<?php echo number_format($item['discount_price'], 2); ?>
                                    <span style="text-decoration: line-through; opacity: 0.7; margin-left: 5px;">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div style="background: var(--primary-color); color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                                    $<?php echo number_format($item['price'], 2); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Course Details -->
                    <div style="flex: 1; padding: 25px; display: flex; flex-direction: column;">
                        <div style="flex: 1;">
                            <!-- Category and Added date -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <span class="course-category" style="background: var(--secondary-color); color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 500;">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                                <span style="color: var(--gray-color); font-size: 0.9rem;">
                                    Added <?php echo date('M j, Y', strtotime($item['added_at'])); ?>
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 style="color: var(--primary-color); margin-bottom: 10px; font-size: 1.4rem;">
                                <a href="../course-detail.php?slug=<?php echo $item['slug']; ?>"
                                   style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h3>

                            <!-- Instructor -->
                            <p style="color: var(--gray-color); margin-bottom: 15px;">
                                <i class="fas fa-user-circle"></i>
                                <strong>Instructor:</strong> <?php echo htmlspecialchars($item['instructor_name']); ?>
                            </p>

                            <!-- Description -->
                            <p style="color: var(--dark-color); line-height: 1.6; margin-bottom: 20px;">
                                <?php echo htmlspecialchars(substr($item['short_description'], 0, 200)); ?>...
                            </p>

                            <!-- Course stats -->
                            <div style="display: flex; gap: 20px; color: var(--gray-color); font-size: 0.9rem;">
                                <span><i class="fas fa-clock"></i> <?php echo $item['duration_hours']; ?> hours</span>
                                <span><i class="fas fa-signal"></i> <?php echo ucfirst($item['difficulty']); ?> Level</span>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div style="display: flex; gap: 15px; margin-top: 25px;">
                            <a href="../course-detail.php?slug=<?php echo $item['slug']; ?>"
                               class="btn btn-primary" style="padding: 12px 25px;">
                                <i class="fas fa-eye"></i> View Details
                            </a>

                            <?php
                            // Check if already enrolled
                            $enrollment_sql = "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'";
                            $enrollment_stmt = $conn->prepare($enrollment_sql);
                            $enrollment_stmt->bind_param("ii", $user_id, $item['id']);
                            $enrollment_stmt->execute();
                            $is_enrolled = $enrollment_stmt->get_result()->num_rows > 0;
                            ?>

                            <?php if ($is_enrolled): ?>
                                <a href="learning.php?course=<?php echo $item['id']; ?>"
                                   class="btn btn-success" style="padding: 12px 25px;">
                                    <i class="fas fa-play-circle"></i> Continue Learning
                                </a>
                            <?php else: ?>
                                <form action="enroll.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-accent" style="padding: 12px 25px;">
                                        <i class="fas fa-shopping-cart"></i> Enroll Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(courseId, buttonElement) {
    if (!confirm('Are you sure you want to remove this course from your wishlist?')) {
        return;
    }

    const itemElement = buttonElement.closest('.wishlist-item');

    fetch('wishlist-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&course_id=${courseId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the page with animation
            itemElement.style.transition = 'all 0.3s';
            itemElement.style.opacity = '0';
            itemElement.style.transform = 'translateX(-100%)';

            setTimeout(() => {
                itemElement.remove();
                // Check if wishlist is now empty
                const remainingItems = document.querySelectorAll('.wishlist-item');
                if (remainingItems.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
        } else {
            alert(data.message || 'Failed to remove from wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing from wishlist');
    });
}

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const wishlistItems = document.querySelectorAll('.wishlist-item');
    wishlistItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
    .remove-btn:hover {
        background: var(--danger-color) !important;
        transform: scale(1.1);
    }

    .wishlist-item:hover {
        transform: translateY(-5px);
    }

    .course-category {
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .empty-wishlist i {
        animation: heartbeat 2s infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
</style>

<?php include '../includes/footer.php'; ?>
