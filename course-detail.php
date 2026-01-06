<?php
require_once 'includes/config.php';

// Check if slug is provided
if (!isset($_GET['slug'])) {
    redirect('courses.php');
}

$slug = sanitize($_GET['slug']);
$page_title = "Course Details";

// Get course details
$conn = getDBConnection();
$sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name, i.bio as instructor_bio, i.photo as instructor_photo
        FROM courses c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        WHERE c.slug = ? AND c.is_active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    redirect('courses.php?error=Course not found');
}

$page_title = $course['title'] . " - TMM Academy";

// Check if user is enrolled
$is_enrolled = false;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $check_sql = "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $course['id']);
    $check_stmt->execute();
    $check_stmt->store_result();
    $is_enrolled = $check_stmt->num_rows > 0;
}

// Get course modules/videos
$modules_sql = "SELECT m.*, 
               (SELECT COUNT(*) FROM videos v WHERE v.module_id = m.id) as video_count,
               (SELECT SUM(duration_minutes) FROM videos v WHERE v.module_id = m.id) as total_minutes
               FROM modules m WHERE m.course_id = ? ORDER BY m.module_order";
$modules_stmt = $conn->prepare($modules_sql);
$modules_stmt->bind_param("i", $course['id']);
$modules_stmt->execute();
$modules_result = $modules_stmt->get_result();
$modules = [];
while ($module = $modules_result->fetch_assoc()) {
    $modules[] = $module;
}

// Calculate total course duration
$total_hours = $course['duration_hours'] ?: 0;
$total_lessons = 0;
foreach ($modules as $module) {
    $total_lessons += $module['video_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .course-detail-header {
            background: white;
            padding: 30px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .course-breadcrumb {
            margin-bottom: 20px;
            color: var(--gray-color);
        }
        
        .course-breadcrumb a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        .course-breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .course-main-title {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .course-subtitle {
            font-size: 1.2rem;
            color: var(--gray-color);
            margin-bottom: 25px;
        }
        
        .course-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-color);
        }
        
        .course-thumbnail-large {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .course-tabs {
            margin: 40px 0;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 30px;
        }
        
        .tab-link {
            padding: 15px 25px;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-color);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-link:hover {
            color: var(--primary-color);
        }
        
        .tab-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .module-accordion {
            margin-bottom: 20px;
        }
        
        .module-header {
            background: var(--light-color);
            padding: 15px 20px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .module-header:hover {
            background: #f1f1f1;
        }
        
        .module-title {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .module-duration {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .module-content {
            padding: 20px;
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 5px 5px;
            display: none;
        }
        
        .lesson-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .lesson-item:last-child {
            border-bottom: none;
        }
        
        .lesson-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .preview-badge {
            background: var(--accent-color);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .locked-badge {
            background: var(--gray-color);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .instructor-card {
            display: flex;
            gap: 20px;
            align-items: center;
            background: var(--light-color);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .instructor-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            padding: 25px;
            margin-bottom: 25px;
            position: sticky;
            top: 100px;
        }
        
        .course-price-display {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .original-price {
            font-size: 1.2rem;
            color: var(--gray-color);
            text-decoration: line-through;
            margin-bottom: 5px;
        }
        
        .discount-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--danger-color);
            margin-bottom: 10px;
        }
        
        .discount-percentage {
            background: var(--accent-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .course-features-list {
            list-style: none;
            padding: 0;
            margin: 25px 0;
        }
        
        .course-features-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .course-features-list li:last-child {
            border-bottom: none;
        }
        
        .enroll-button {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .whatsapp-share {
            background: #25D366;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .whatsapp-share:hover {
            background: #128C7E;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a class="navbar-brand" href="index.php">TMM ACADEMY</a>
                <ul class="navbar-nav">
                    <li><a class="nav-link" href="index.php">Home</a></li>
                    <li><a class="nav-link active" href="courses.php">Courses</a></li>
                    <li><a class="nav-link" href="faq.php">FAQ</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a class="nav-link" href="user/my-courses.php">Dashboard</a></li>
                        <li><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a class="nav-link" href="login.php">Login</a></li>
                        <li><a class="btn btn-accent" href="register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Course Header -->
    <div class="course-detail-header">
        <div class="container">
            <!-- Breadcrumb -->
            <div class="course-breadcrumb">
                <a href="index.php">Home</a> > 
                <a href="courses.php">Courses</a> > 
                <a href="courses.php?category=<?php echo $course['category_name']; ?>"><?php echo $course['category_name']; ?></a> > 
                <span><?php echo $course['title']; ?></span>
            </div>
            
            <h1 class="course-main-title"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="course-subtitle"><?php echo htmlspecialchars($course['short_description']); ?></p>
            
            <div class="course-meta">
                <div class="meta-item">
                    <i class="fas fa-user-circle"></i>
                    <span>Instructor: <strong><?php echo $course['instructor_name']; ?></strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>Duration: <strong><?php echo $total_hours; ?> hours</strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-play-circle"></i>
                    <span>Lessons: <strong><?php echo $total_lessons; ?></strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-signal"></i>
                    <span>Level: <strong><?php echo ucfirst($course['difficulty']); ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="margin-top: 30px;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <!-- Left Column - Course Content -->
            <div>
                <!-- Course Thumbnail -->
                <img src="assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>" 
                     alt="<?php echo $course['title']; ?>" class="course-thumbnail-large">
                
                <!-- Course Tabs -->
                <div class="course-tabs">
                    <div class="tab-nav">
                        <button class="tab-link active" onclick="openTab('overview')">Overview</button>
                        <button class="tab-link" onclick="openTab('curriculum')">Curriculum</button>
                        <button class="tab-link" onclick="openTab('instructor')">Instructor</button>
                    </div>
                    
                    <!-- Overview Tab -->
                    <div id="overview" class="tab-content active">
                        <h3 style="margin-bottom: 20px; color: var(--primary-color);">About This Course</h3>
                        <div style="line-height: 1.8; color: var(--dark-color);">
                            <?php echo nl2br(htmlspecialchars($course['full_description'])); ?>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">What You'll Learn</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--accent-color); margin-top: 3px;"></i>
                                <span>Master fundamental programming concepts</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--accent-color); margin-top: 3px;"></i>
                                <span>Build real-world applications and projects</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--accent-color); margin-top: 3px;"></i>
                                <span>Understand best practices and coding standards</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--accent-color); margin-top: 3px;"></i>
                                <span>Prepare for technical interviews and jobs</span>
                            </div>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Course Requirements</h4>
                        <ul style="color: var(--dark-color); padding-left: 20px;">
                            <li>A computer with internet connection</li>
                            <li>Basic computer skills</li>
                            <li>Dedication to learn and practice</li>
                            <?php if ($course['difficulty'] == 'beginner'): ?>
                            <li>No prior programming experience needed</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Curriculum Tab -->
                    <div id="curriculum" class="tab-content">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="color: var(--primary-color);">Course Curriculum</h3>
                            <div style="color: var(--gray-color);">
                                <?php echo count($modules); ?> modules • <?php echo $total_lessons; ?> lessons • <?php echo $total_hours; ?> hours
                            </div>
                        </div>
                        
                        <?php if (empty($modules)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--gray-color);">
                                <i class="fas fa-book fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                                <p>Curriculum coming soon. Check back later!</p>
                            </div>
                        <?php else: ?>
                            <div class="module-accordion">
                                <?php foreach ($modules as $index => $module): 
                                    $module_hours = floor($module['total_minutes'] / 60);
                                    $module_minutes = $module['total_minutes'] % 60;
                                ?>
                                <div class="module-item">
                                    <div class="module-header" onclick="toggleModule(<?php echo $index; ?>)">
                                        <div>
                                            <div class="module-title"><?php echo $module['title']; ?></div>
                                            <?php if ($module['description']): ?>
                                            <div style="font-size: 0.9rem; color: var(--gray-color); margin-top: 5px;">
                                                <?php echo $module['description']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="module-duration">
                                            <?php echo $module['video_count']; ?> lessons • 
                                            <?php echo $module_hours; ?>h <?php echo $module_minutes; ?>m
                                        </div>
                                    </div>
                                    <div id="module-content-<?php echo $index; ?>" class="module-content">
                                        <div style="color: var(--gray-color); margin-bottom: 15px; font-size: 0.9rem;">
                                            <i class="fas fa-info-circle"></i> Click lesson titles to view details
                                        </div>
                                        <?php 
                                        // Get lessons for this module
                                        $lessons_sql = "SELECT * FROM videos WHERE module_id = ? ORDER BY video_order";
                                        $lessons_stmt = $conn->prepare($lessons_sql);
                                        $lessons_stmt->bind_param("i", $module['id']);
                                        $lessons_stmt->execute();
                                        $lessons_result = $lessons_stmt->get_result();
                                        $lessons = [];
                                        while ($lesson = $lessons_result->fetch_assoc()) {
                                            $lessons[] = $lesson;
                                        }
                                        
                                        if (empty($lessons)): 
                                        ?>
                                            <div class="lesson-item">
                                                <div class="lesson-title">
                                                    <i class="far fa-play-circle" style="color: var(--secondary-color);"></i>
                                                    <span>Lessons coming soon</span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($lessons as $lesson_index => $lesson): ?>
                                            <div class="lesson-item">
                                                <div class="lesson-title">
                                                    <i class="far fa-play-circle" style="color: var(--secondary-color);"></i>
                                                    <span><?php echo $lesson['title']; ?></span>
                                                    <?php if ($lesson['is_preview']): ?>
                                                    <span class="preview-badge">Preview</span>
                                                    <?php elseif (!$is_enrolled): ?>
                                                    <span class="locked-badge">Locked</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="color: var(--gray-color); font-size: 0.9rem;">
                                                    <?php echo $lesson['duration_minutes']; ?> min
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Instructor Tab -->
                    <div id="instructor" class="tab-content">
                        <h3 style="margin-bottom: 25px; color: var(--primary-color);">About the Instructor</h3>
                        
                        <div class="instructor-card">
                            <img src="assets/images/instructors/<?php echo $course['instructor_photo'] ?: 'default-instructor.jpg'; ?>" 
                                 alt="<?php echo $course['instructor_name']; ?>" class="instructor-photo">
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 10px;"><?php echo $course['instructor_name']; ?></h4>
                                <p style="color: var(--gray-color); margin-bottom: 15px;">
                                    <i class="fas fa-graduation-cap"></i> 
                                    <?php echo $course['instructor_bio']; ?>
                                </p>
                                <div style="display: flex; gap: 20px; margin-top: 20px;">
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                            <?php 
                                            $instructor_courses_sql = "SELECT COUNT(*) as count FROM courses WHERE instructor_id = ?";
                                            $instructor_stmt = $conn->prepare($instructor_courses_sql);
                                            $instructor_stmt->bind_param("i", $course['instructor_id']);
                                            $instructor_stmt->execute();
                                            $instructor_result = $instructor_stmt->get_result();
                                            $course_count = $instructor_result->fetch_assoc()['count'];
                                            echo $course_count;
                                            ?>
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--gray-color);">Courses</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                            <?php echo rand(1000, 10000); ?>+
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--gray-color);">Students</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                            4.9
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--gray-color);">Rating</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p style="line-height: 1.8; color: var(--dark-color);">
                            <?php echo nl2br(htmlspecialchars($course['instructor_bio'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Sidebar -->
            <div>
                <!-- Course Price & Enrollment -->
                <div class="sidebar-card">
                    <div class="course-price-display">
                        <?php if ($course['discount_price']): ?>
                            <div class="original-price">$<?php echo number_format($course['price'], 2); ?></div>
                            <div class="discount-price">$<?php echo number_format($course['discount_price'], 2); ?></div>
                            <div class="discount-percentage">
                                Save <?php echo calculateDiscount($course['price'], $course['discount_price']); ?>%
                            </div>
                        <?php else: ?>
                            <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin: 20px 0;">
                                $<?php echo number_format($course['price'], 2); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_enrolled): ?>
                        <a href="user/learning.php?course=<?php echo $course['id']; ?>" class="btn btn-primary enroll-button">
                            <i class="fas fa-play-circle"></i> Continue Learning
                        </a>
                        <a href="user/my-courses.php" class="btn btn-outline-primary enroll-button">
                            <i class="fas fa-tasks"></i> View Progress
                        </a>
                    <?php else: ?>
                        <?php if (isLoggedIn()): ?>
                            <form action="user/enroll.php" method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn btn-primary enroll-button">
                                    <i class="fas fa-shopping-cart"></i> Enroll Now
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=course-<?php echo $slug; ?>" class="btn btn-primary enroll-button">
                                <i class="fas fa-sign-in-alt"></i> Login to Enroll
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-primary enroll-button" id="wishlist-btn" onclick="toggleWishlist(<?php echo $course['id']; ?>)">
                            <i class="far fa-heart"></i> Add to Wishlist
                        </button>
                    <?php endif; ?>
                    
                    <ul class="course-features-list">
                        <li><i class="fas fa-play-circle" style="color: var(--primary-color);"></i> <?php echo $total_lessons; ?> video lessons</li>
                        <li><i class="fas fa-file-alt" style="color: var(--primary-color);"></i> Downloadable resources</li>
                        <li><i class="fas fa-infinity" style="color: var(--primary-color);"></i> Full lifetime access</li>
                        <li><i class="fas fa-mobile-alt" style="color: var(--primary-color);"></i> Access on mobile and TV</li>
                        <li><i class="fas fa-certificate" style="color: var(--primary-color);"></i> Certificate of completion</li>
                        <li><i class="fas fa-headset" style="color: var(--primary-color);"></i> 24/7 support</li>
                    </ul>
                    
                    <button class="whatsapp-share" onclick="shareOnWhatsApp()">
                        <i class="fab fa-whatsapp"></i> Share on WhatsApp
                    </button>
                </div>
                
                <!-- Similar Courses -->
                <?php 
                $similar_sql = "SELECT c.*, i.name as instructor_name 
                                FROM courses c 
                                LEFT JOIN instructors i ON c.instructor_id = i.id 
                                WHERE c.category_id = ? AND c.id != ? AND c.is_active = 1 
                                ORDER BY RAND() LIMIT 3";
                $similar_stmt = $conn->prepare($similar_sql);
                $similar_stmt->bind_param("ii", $course['category_id'], $course['id']);
                $similar_stmt->execute();
                $similar_result = $similar_stmt->get_result();
                
                if ($similar_result->num_rows > 0):
                ?>
                <div class="sidebar-card">
                    <h4 style="margin-bottom: 20px; color: var(--primary-color);">Similar Courses</h4>
                    <?php while ($similar = $similar_result->fetch_assoc()): ?>
                    <a href="course-detail.php?slug=<?php echo $similar['slug']; ?>" 
                       style="text-decoration: none; color: inherit;">
                        <div style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid var(--border-color);">
                            <img src="assets/images/courses/<?php echo $similar['thumbnail'] ?: 'default-course.jpg'; ?>" 
                                 alt="<?php echo $similar['title']; ?>" 
                                 style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 500; color: var(--primary-color); margin-bottom: 5px; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($similar['title']); ?>
                                </div>
                                <div style="color: var(--gray-color); font-size: 0.8rem;">
                                    <?php echo $similar['instructor_name']; ?>
                                </div>
                                <div style="color: var(--danger-color); font-weight: 700; font-size: 0.9rem; margin-top: 5px;">
                                    $<?php echo number_format($similar['discount_price'] ?: $similar['price'], 2); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; text-align: center; color: rgba(255,255,255,0.6);">
                <p>&copy; <?php echo date('Y'); ?> TMM Academy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Remove active class from all tab links
            var tabLinks = document.getElementsByClassName('tab-link');
            for (var i = 0; i < tabLinks.length; i++) {
                tabLinks[i].classList.remove('active');
            }
            
            // Show current tab and add active class
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        // Module accordion
        function toggleModule(index) {
            var content = document.getElementById('module-content-' + index);
            if (content.style.display === 'block') {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }
        }
        
        // Toggle wishlist
        function toggleWishlist(courseId) {
            <?php if (isLoggedIn()): ?>
                const btn = document.getElementById('wishlist-btn');
                const icon = btn.querySelector('i');
                const text = btn.querySelector('span') || btn;

                // Show loading state
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                fetch('user/wishlist-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&course_id=${courseId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button appearance
                        icon.className = 'fas fa-heart';
                        btn.className = 'btn btn-danger enroll-button';
                        if (btn.querySelector('span')) {
                            btn.querySelector('span').textContent = 'Remove from Wishlist';
                        } else {
                            btn.innerHTML = '<i class="fas fa-heart"></i> Remove from Wishlist';
                        }
                        btn.onclick = function() { removeFromWishlist(courseId); };
                        alert('Course added to your wishlist!');
                    } else {
                        alert(data.message || 'Failed to add to wishlist');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding to wishlist');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            <?php else: ?>
                if (confirm('Please login to add courses to your wishlist. Would you like to login now?')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                }
            <?php endif; ?>
        }

        function removeFromWishlist(courseId) {
            const btn = document.getElementById('wishlist-btn');

            fetch('user/wishlist-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&course_id=${courseId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    const icon = btn.querySelector('i');
                    icon.className = 'far fa-heart';
                    btn.className = 'btn btn-outline-primary enroll-button';
                    if (btn.querySelector('span')) {
                        btn.querySelector('span').textContent = 'Add to Wishlist';
                    } else {
                        btn.innerHTML = '<i class="far fa-heart"></i> Add to Wishlist';
                    }
                    btn.onclick = function() { toggleWishlist(courseId); };
                    alert('Course removed from your wishlist!');
                } else {
                    alert(data.message || 'Failed to remove from wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing from wishlist');
            });
        }
        
        // Share on WhatsApp
        function shareOnWhatsApp() {
            var courseTitle = "<?php echo addslashes($course['title']); ?>";
            var courseUrl = window.location.href;
            var message = "Check out this course: " + courseTitle + " - " + courseUrl;
            var whatsappUrl = "https://api.whatsapp.com/send?text=" + encodeURIComponent(message);
            window.open(whatsappUrl, '_blank');
        }
        
        // Initialize first module as open
        document.addEventListener('DOMContentLoaded', function() {
            toggleModule(0);
        });
    </script>
</body>
</html>