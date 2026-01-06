<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "All Courses - TMM Academy";
include 'includes/header.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? sanitize($_GET['difficulty']) : '';

// Get all categories for filter
$conn = getDBConnection();
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

// Build course query
$sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name 
        FROM courses c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        WHERE c.is_active = 1";
$params = [];
$types = "";

if ($category_filter) {
    $sql .= " AND cat.slug = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if ($difficulty_filter && in_array($difficulty_filter, ['beginner', 'intermediate', 'advanced'])) {
    $sql .= " AND c.difficulty = ?";
    $params[] = $difficulty_filter;
    $types .= "s";
}

if ($search_query) {
    $sql .= " AND (c.title LIKE ? OR c.short_description LIKE ? OR i.name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($course = $result->fetch_assoc()) {
    $courses[] = $course;
}
?>
<style>
    .page-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }

    .page-header h1 {
        color: white;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .filters-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: var(--shadow-md);
        margin-bottom: 30px;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark-color);
    }

    .filter-select, .filter-input {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        font-size: 1rem;
    }

    .filter-select:focus, .filter-input:focus {
        outline: none;
        border-color: var(--secondary-color);
    }

    .btn-filter {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-filter:hover {
        background: #1a252f;
    }

    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .no-courses {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-color);
    }

    .no-courses i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #ddd;
    }

    .course-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .course-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .course-footer {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }

    .course-stats {
        display: flex;
        justify-content: space-between;
        color: var(--gray-color);
        font-size: 0.9rem;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }

    .page-link {
        padding: 8px 15px;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        color: var(--dark-color);
        text-decoration: none;
        transition: all 0.3s;
    }

    .page-link:hover {
        background: var(--light-color);
    }

    .page-link.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
</style>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Browse Our Courses</h1>
            <p style="font-size: 1.2rem; opacity: 0.9;">
                Learn from industry experts with hands-on projects and real-world applications
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search"><i class="fas fa-search"></i> Search Courses</label>
                        <input type="text" id="search" name="search" class="filter-input" 
                               placeholder="Search by title, instructor, or keyword"
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category"><i class="fas fa-folder"></i> Category</label>
                        <select id="category" name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['slug']; ?>" 
                                    <?php echo ($category_filter == $category['slug']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="difficulty"><i class="fas fa-signal"></i> Difficulty</label>
                        <select id="difficulty" name="difficulty" class="filter-select">
                            <option value="">All Levels</option>
                            <option value="beginner" <?php echo ($difficulty_filter == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo ($difficulty_filter == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo ($difficulty_filter == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="courses.php" class="btn-filter" style="background: var(--gray-color);">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Course Count -->
        <div style="margin-bottom: 20px; color: var(--gray-color);">
            <p><strong><?php echo count($courses); ?></strong> courses found</p>
        </div>

        <!-- Courses Grid -->
        <div class="course-grid">
            <?php if (empty($courses)): ?>
                <div class="no-courses">
                    <i class="fas fa-search"></i>
                    <h3>No courses found</h3>
                    <p>Try adjusting your search filters or check back later for new courses.</p>
                    <a href="courses.php" class="btn btn-primary" style="margin-top: 20px;">
                        View All Courses
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="card course-card">
                        <!-- Course Thumbnail -->
                        <img src="assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>" 
                             alt="<?php echo $course['title']; ?>" 
                             style="width: 100%; height: 200px; object-fit: cover;">
                        
                        <div class="card-body course-content">
                            <!-- Course Category -->
                            <span class="course-category"><?php echo $course['category_name']; ?></span>
                            
                            <!-- Course Title -->
                            <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                            
                            <!-- Course Description -->
                            <p class="card-text"><?php echo htmlspecialchars($course['short_description']); ?></p>
                            
                            <!-- Instructor -->
                            <p style="color: var(--gray-color); margin-bottom: 15px;">
                                <i class="fas fa-user-circle"></i> 
                                <strong>Instructor:</strong> <?php echo $course['instructor_name']; ?>
                            </p>
                            
                            <!-- Difficulty -->
                            <p style="margin-bottom: 15px;">
                                <span class="badge" style="
                                    background: <?php 
                                        echo $course['difficulty'] == 'beginner' ? '#27AE60' : 
                                               ($course['difficulty'] == 'intermediate' ? '#E67E22' : '#E74C3C'); 
                                    ?>;
                                    color: white;
                                    padding: 5px 10px;
                                    border-radius: 4px;
                                    font-size: 0.8rem;
                                ">
                                    <?php echo ucfirst($course['difficulty']); ?> Level
                                </span>
                            </p>
                            
                            <!-- Price -->
                            <div class="course-price">
                                <span class="current-price">
                                    $<?php echo number_format($course['discount_price'] ?: $course['price'], 2); ?>
                                </span>
                                <?php if ($course['discount_price']): ?>
                                    <span class="original-price">
                                        $<?php echo number_format($course['price'], 2); ?>
                                    </span>
                                    <span class="discount-badge">
                                        Save <?php echo calculateDiscount($course['price'], $course['discount_price']); ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="course-footer">
                            <div class="course-stats">
                                <span><i class="fas fa-clock"></i> <?php echo $course['duration_hours'] ?: '0'; ?> hours</span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($course['created_at'])); ?></span>
                            </div>
                            <a href="course-detail.php?slug=<?php echo $course['slug']; ?>" 
                               class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Categories Section -->
        <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--border-color);">
            <h2 style="text-align: center; margin-bottom: 30px;">Browse by Category</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="courses.php?category=<?php echo $category['slug']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon'] ?: 'fas fa-book'; ?>"></i>
                        </div>
                        <h4><?php echo $category['name']; ?></h4>
                        <p>
                            <?php
                            $count_sql = "SELECT COUNT(*) as count FROM courses WHERE category_id = ? AND is_active = 1";
                            $count_stmt = $conn->prepare($count_sql);
                            $count_stmt->bind_param("i", $category['id']);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $course_count = $count_result->fetch_assoc()['count'];
                            echo $course_count . ' courses';
                            ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


<script>
    // Auto-submit form when select changes (optional)
    document.getElementById('category').addEventListener('change', function() {
        if (this.value) {
            this.form.submit();
        }
    });

    document.getElementById('difficulty').addEventListener('change', function() {
        if (this.value) {
            this.form.submit();
        }
    });
</script>
<?php include 'includes/footer.php'; ?>