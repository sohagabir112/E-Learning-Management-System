<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Search Results - TMM Academy";

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all'; // all, courses, instructors

$results = [];
$total_results = 0;

if (!empty($query) && strlen($query) >= 2) {
    $conn = getDBConnection();

    // Search courses
    if ($type == 'all' || $type == 'courses') {
        $course_sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name,
                              (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.payment_status = 'completed') as enrolled_count
                       FROM courses c
                       LEFT JOIN categories cat ON c.category_id = cat.id
                       LEFT JOIN instructors i ON c.instructor_id = i.id
                       WHERE c.is_active = 1 AND (
                           c.title LIKE ? OR
                           c.short_description LIKE ? OR
                           c.full_description LIKE ? OR
                           i.name LIKE ?
                       )";

        $params = ["%$query%", "%$query%", "%$query%", "%$query%"];
        $types = "ssss";

        if ($category) {
            $course_sql .= " AND cat.slug = ?";
            $params[] = $category;
            $types .= "s";
        }

        $course_sql .= " ORDER BY c.created_at DESC LIMIT 20";

        $course_stmt = $conn->prepare($course_sql);
        $course_stmt->bind_param($types, ...$params);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();

        while ($course = $course_result->fetch_assoc()) {
            $results[] = [
                'type' => 'course',
                'id' => $course['id'],
                'title' => $course['title'],
                'description' => $course['short_description'],
                'url' => 'course-detail.php?slug=' . $course['slug'],
                'thumbnail' => $course['thumbnail'],
                'category' => $course['category_name'],
                'instructor' => $course['instructor_name'],
                'price' => $course['price'],
                'discount_price' => $course['discount_price'],
                'rating' => getCourseRating($course['id']),
                'enrolled_count' => $course['enrolled_count']
            ];
        }
    }

    // Search instructors
    if ($type == 'all' || $type == 'instructors') {
        $instructor_sql = "SELECT i.*, COUNT(c.id) as course_count
                          FROM instructors i
                          LEFT JOIN courses c ON i.id = c.instructor_id AND c.is_active = 1
                          WHERE i.name LIKE ? OR i.specialization LIKE ? OR i.bio LIKE ?
                          GROUP BY i.id
                          ORDER BY i.name LIMIT 10";

        $instructor_stmt = $conn->prepare($instructor_sql);
        $search_term = "%$query%";
        $instructor_stmt->bind_param("sss", $search_term, $search_term, $search_term);
        $instructor_stmt->execute();
        $instructor_result = $instructor_stmt->get_result();

        while ($instructor = $instructor_result->fetch_assoc()) {
            $results[] = [
                'type' => 'instructor',
                'id' => $instructor['id'],
                'title' => $instructor['name'],
                'description' => $instructor['specialization'] . ' - ' . substr($instructor['bio'], 0, 100) . '...',
                'url' => '#', // Could link to instructor profile page
                'thumbnail' => $instructor['photo'],
                'course_count' => $instructor['course_count']
            ];
        }
    }

    $total_results = count($results);
}

// Get categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}
?>

<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<section style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; padding: 60px 0;">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 20px;">Search Results</h1>
        <p style="text-align: center; font-size: 1.2rem; opacity: 0.9;">
            <?php if (!empty($query)): ?>
                Found <?php echo $total_results; ?> result<?php echo $total_results !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($query); ?>"
            <?php else: ?>
                Enter a search term to find courses and instructors
            <?php endif; ?>
        </p>
    </div>
</section>

<!-- Search Form -->
<div class="container" style="margin-top: -30px; margin-bottom: 40px;">
    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: var(--shadow-lg);">
        <form method="GET" action="search.php">
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark-color);">
                        <i class="fas fa-search"></i> Search Query
                    </label>
                    <input type="text" name="q" class="form-control" placeholder="Search courses, instructors..."
                           value="<?php echo htmlspecialchars($query); ?>" required style="padding: 12px;">
                </div>

                <div style="min-width: 150px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark-color);">
                        <i class="fas fa-folder"></i> Category
                    </label>
                    <select name="category" class="form-control" style="padding: 12px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo ($category == $cat['slug']) ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width: 150px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark-color);">
                        <i class="fas fa-filter"></i> Search In
                    </label>
                    <select name="type" class="form-control" style="padding: 12px;">
                        <option value="all" <?php echo ($type == 'all') ? 'selected' : ''; ?>>All</option>
                        <option value="courses" <?php echo ($type == 'courses') ? 'selected' : ''; ?>>Courses Only</option>
                        <option value="instructors" <?php echo ($type == 'instructors') ? 'selected' : ''; ?>>Instructors Only</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<div class="container">
    <?php if (empty($query)): ?>
        <!-- Empty search state -->
        <div style="text-align: center; padding: 80px 20px; color: var(--gray-color);">
            <i class="fas fa-search fa-4x" style="margin-bottom: 30px; opacity: 0.5;"></i>
            <h3>Start Your Search</h3>
            <p>Enter keywords to find courses and instructors that match your interests.</p>
        </div>
    <?php elseif (empty($results)): ?>
        <!-- No results found -->
        <div style="text-align: center; padding: 80px 20px; color: var(--gray-color);">
            <i class="fas fa-search-minus fa-4x" style="margin-bottom: 30px; opacity: 0.5;"></i>
            <h3>No Results Found</h3>
            <p>We couldn't find any courses or instructors matching "<?php echo htmlspecialchars($query); ?>"</p>
            <p style="margin-top: 20px;">
                <strong>Try:</strong>
                <br>• Using different keywords
                <br>• Checking spelling
                <br>• Searching in a different category
                <br>• Broadening your search terms
            </p>
            <a href="courses.php" class="btn btn-primary" style="margin-top: 30px;">
                <i class="fas fa-book"></i> Browse All Courses
            </a>
        </div>
    <?php else: ?>
        <!-- Results found -->
        <div style="display: grid; gap: 30px;">
            <?php foreach ($results as $result): ?>
                <div style="background: white; border-radius: 10px; box-shadow: var(--shadow-md); overflow: hidden;">
                    <?php if ($result['type'] == 'course'): ?>
                        <!-- Course Result -->
                        <div style="display: flex; flex-wrap: wrap;">
                            <div style="flex: 0 0 300px;">
                                <img src="assets/images/courses/<?php echo $result['thumbnail'] ?: 'default-course.jpg'; ?>"
                                     alt="<?php echo htmlspecialchars($result['title']); ?>"
                                     style="width: 100%; height: 200px; object-fit: cover;">
                            </div>
                            <div style="flex: 1; padding: 25px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <span class="badge" style="background: var(--secondary-color); color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($result['category']); ?>
                                        </span>
                                        <h3 style="margin: 10px 0; color: var(--primary-color);">
                                            <a href="<?php echo $result['url']; ?>" style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($result['title']); ?>
                                            </a>
                                        </h3>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php if ($result['discount_price']): ?>
                                            <div style="color: var(--gray-color); text-decoration: line-through; font-size: 0.9rem;">
                                                $<?php echo number_format($result['price'], 2); ?>
                                            </div>
                                            <div style="color: var(--primary-color); font-size: 1.2rem; font-weight: 600;">
                                                $<?php echo number_format($result['discount_price'], 2); ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="color: var(--primary-color); font-size: 1.2rem; font-weight: 600;">
                                                $<?php echo number_format($result['price'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <p style="color: var(--gray-color); margin-bottom: 15px; line-height: 1.6;">
                                    <?php echo htmlspecialchars($result['description']); ?>
                                </p>

                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                    <div style="display: flex; align-items: center; gap: 15px; color: var(--gray-color);">
                                        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($result['instructor']); ?></span>
                                        <span><i class="fas fa-users"></i> <?php echo $result['enrolled_count']; ?> students</span>
                                        <span>
                                            <?php echo generateStarRating($result['rating']); ?>
                                            <span style="margin-left: 5px;"><?php echo number_format($result['rating'], 1); ?></span>
                                        </span>
                                    </div>
                                    <a href="<?php echo $result['url']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Course
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Instructor Result -->
                        <div style="padding: 25px;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <img src="assets/images/instructors/<?php echo $result['thumbnail'] ?: 'default-instructor.jpg'; ?>"
                                     alt="<?php echo htmlspecialchars($result['title']); ?>"
                                     style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 10px 0; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($result['title']); ?>
                                    </h3>
                                    <p style="color: var(--gray-color); margin: 0 0 10px 0;">
                                        <?php echo htmlspecialchars($result['description']); ?>
                                    </p>
                                    <span style="color: var(--secondary-color); font-weight: 500;">
                                        <i class="fas fa-book"></i> <?php echo $result['course_count']; ?> courses
                                    </span>
                                </div>
                                <div>
                                    <a href="#" class="btn btn-outline-primary">
                                        <i class="fas fa-user"></i> View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
