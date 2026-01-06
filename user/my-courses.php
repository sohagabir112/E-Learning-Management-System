<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "Dashboard - TMM Academy";
$user = getCurrentUser();

// Get user enrollments
$conn = getDBConnection();
$sql = "SELECT e.*, c.title, c.slug, c.thumbnail, c.duration_hours, c.instructor_id,
               i.name as instructor_name,
               (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as module_count
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE e.user_id = ? AND e.payment_status = 'completed'
        ORDER BY e.enrolled_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$enrollments = [];
while ($row = $result->fetch_assoc()) {
    $enrollments[] = $row;
}

// Get user progress
$progress_sql = "SELECT course_id, MAX(progress_percentage) as progress 
                 FROM user_progress 
                 WHERE user_id = ? 
                 GROUP BY course_id";
$progress_stmt = $conn->prepare($progress_sql);
$progress_stmt->bind_param("i", $user['id']);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$progress_data = [];
while ($row = $progress_result->fetch_assoc()) {
    $progress_data[$row['course_id']] = $row['progress'];
}

// Calculate statistics
$total_courses = count($enrollments);
$completed_courses = 0;
$in_progress_courses = 0;
$not_started_courses = 0;
$total_learning_hours = 0;

foreach ($enrollments as $enrollment) {
    $progress = $progress_data[$enrollment['course_id']] ?? 0;
    $total_learning_hours += $enrollment['duration_hours'];

    if ($progress >= 100) {
        $completed_courses++;
    } elseif ($progress > 0) {
        $in_progress_courses++;
    } else {
        $not_started_courses++;
    }
}
?>
<?php include '../includes/header.php'; ?>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .welcome-message h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .welcome-message p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .stat-icon i {
            color: white;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .dashboard-section {
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin: 0;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .course-card-dashboard {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s;
            border: 1px solid var(--border-color);
        }

        .course-card-dashboard:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .course-thumbnail-small {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .course-card-body {
            padding: 20px;
        }

        .course-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .progress-container {
            margin-bottom: 15px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-color) 0%, #2ecc71 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 0.8rem;
            color: var(--gray-color);
            text-align: right;
            margin-bottom: 15px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .btn-continue {
            flex: 1;
            padding: 10px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-continue:hover {
            background: #1a252f;
            transform: translateY(-2px);
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .quick-link-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: var(--light-color);
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }

        .quick-link-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .quick-link-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .quick-link-item:hover .quick-link-icon {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .courses-table {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 40px;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 20px;
            background: var(--light-color);
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        
        .table-row:hover {
            background: #f9f9f9;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .course-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .course-thumbnail-table {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .course-title-table {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .course-instructor {
            font-size: 0.9rem;
            color: var(--gray-color);
        }
        
        .progress-cell {
            text-align: center;
        }
        
        .progress-circle {
            width: 60px;
            height: 60px;
            margin: 0 auto;
            position: relative;
        }
        
        .progress-circle svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .progress-circle-bg {
            fill: none;
            stroke: var(--light-color);
            stroke-width: 6;
        }
        
        .progress-circle-fill {
            fill: none;
            stroke: var(--accent-color);
            stroke-width: 6;
            stroke-linecap: round;
            stroke-dasharray: 157;
            stroke-dashoffset: calc(157 - (157 * var(--progress)) / 100);
            transition: stroke-dashoffset 0.3s;
        }
        
        .progress-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .course-actions-table {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-table {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-continue-table {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-view-table {
            background: var(--light-color);
            color: var(--dark-color);
        }
        
        .empty-state-table {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-color);
        }
        
        .empty-state-table i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-progress {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-not-started {
            background: #f8f9fa;
            color: #6c757d;
        }

        /* ===== COMPREHENSIVE RESPONSIVE DESIGN ===== */

        /* Tablets and smaller laptops (1024px and below) */
        @media (max-width: 1024px) {
            .dashboard-header {
                padding: 50px 0;
            }

            .welcome-message h1 {
                font-size: 2.2rem;
            }

            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
            }

            .courses-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }

            .course-card-dashboard {
                min-height: 160px;
                height: 160px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        /* Small tablets and large phones (768px and below) */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 40px 0;
                margin-bottom: 30px;
            }

            .welcome-message h1 {
                font-size: 1.8rem;
                margin-bottom: 8px;
            }

            .welcome-message p {
                font-size: 1rem;
            }

            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 12px;
            }

            .stat-icon i {
                font-size: 1.3rem;
            }

            .stat-number {
                font-size: 2.2rem;
            }

            .section-title {
                font-size: 1.6rem;
            }

            .courses-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 18px;
            }

            .course-card-dashboard {
                min-height: 150px;
                height: 150px;
                padding: 18px;
            }

            .course-thumbnail-small {
                width: 100%;
                height: 140px;
            }

            .course-card-title {
                font-size: 1rem;
            }

            .progress-container {
                margin-bottom: 12px;
            }

            .course-actions {
                gap: 8px;
            }

            .btn-continue {
                padding: 8px 14px;
                font-size: 0.85rem;
            }

            .quick-links {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }

            .quick-link-item {
                padding: 16px;
            }

            .quick-link-icon {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }

        /* Phones (480px and below) */
        @media (max-width: 480px) {
            .dashboard-header {
                padding: 30px 0;
                margin-bottom: 25px;
            }

            .welcome-message h1 {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .welcome-message p {
                font-size: 0.95rem;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 25px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-icon {
                width: 45px;
                height: 45px;
                margin-bottom: 10px;
            }

            .stat-icon i {
                font-size: 1.2rem;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .stat-label {
                font-size: 0.85rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .courses-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .course-card-dashboard {
                min-height: 140px;
                height: 140px;
                padding: 16px;
                flex-direction: row;
                align-items: flex-start;
            }

            .course-thumbnail-small {
                width: 100px;
                height: 80px;
                flex-shrink: 0;
                margin-bottom: 0;
            }

            .course-card-body {
                flex: 1;
                padding-left: 12px;
                text-align: left;
            }

            .course-card-title {
                font-size: 0.95rem;
                margin-bottom: 8px;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                display: -webkit-box;
            }

            .course-meta {
                margin-bottom: 10px;
            }

            .course-meta span {
                font-size: 0.8rem;
            }

            .progress-container {
                margin-bottom: 10px;
            }

            .progress-text {
                font-size: 0.75rem;
            }

            .course-actions {
                flex-direction: column;
                gap: 6px;
                width: 100%;
            }

            .btn-continue {
                width: 100%;
                padding: 10px;
                font-size: 0.9rem;
            }

            .quick-links {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .quick-link-item {
                padding: 14px;
                justify-content: flex-start;
            }

            .quick-link-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
                margin-right: 12px;
            }

            .quick-link-item h4 {
                font-size: 0.95rem;
                margin-bottom: 4px;
            }

            .quick-link-item p {
                font-size: 0.8rem;
            }

            /* Table view on mobile */
            .courses-table {
                font-size: 0.85rem;
            }

            .table-header,
            .table-row {
                grid-template-columns: 2fr 1fr 1fr;
                padding: 12px;
            }

            .course-thumbnail-table {
                width: 60px;
                height: 45px;
            }

            .course-title-table {
                font-size: 0.85rem;
            }

            .status-badge {
                padding: 3px 8px;
                font-size: 0.75rem;
            }
        }

        /* Extra small phones (360px and below) */
        @media (max-width: 360px) {
            .dashboard-header {
                padding: 25px 0;
            }

            .welcome-message h1 {
                font-size: 1.3rem;
            }

            .stats-cards {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .stat-card {
                padding: 14px;
                display: flex;
                align-items: center;
                text-align: left;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                margin: 0 12px 0 0;
                flex-shrink: 0;
            }

            .stat-number {
                font-size: 1.6rem;
                margin-bottom: 0;
            }

            .course-card-dashboard {
                padding: 14px;
                min-height: 120px;
                height: 120px;
            }

            .course-thumbnail-small {
                width: 80px;
                height: 60px;
            }

            .course-card-title {
                font-size: 0.9rem;
            }

            .btn-continue {
                padding: 8px;
                font-size: 0.85rem;
            }
        }

        /* Large desktop screens (1440px and above) */
        @media (min-width: 1440px) {
            .dashboard-header {
                padding: 80px 0;
            }

            .welcome-message h1 {
                font-size: 3rem;
            }

            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 30px;
            }

            .courses-grid {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 30px;
            }

            .course-card-dashboard {
                min-height: 190px;
                height: 190px;
                padding: 25px;
            }
        }

        /* Ultra-wide screens (1920px and above) */
        @media (min-width: 1920px) {
            .container {
                max-width: 1600px;
            }

            .courses-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }

        /* Touch devices optimization */
        @media (hover: none) and (pointer: coarse) {
            .course-card-dashboard:hover {
                transform: none;
            }

            .quick-link-item:hover {
                transform: none;
            }

            .nav-link:hover,
            .nav-link.active {
                background-color: rgba(255,255,255,0.15);
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .stat-icon,
            .course-thumbnail-small,
            .quick-link-icon {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Print styles */
        @media print {
            .dashboard-header,
            .quick-links,
            .course-actions {
                display: none !important;
            }

            .courses-grid {
                display: block !important;
            }

            .course-card-dashboard {
                break-inside: avoid;
                margin-bottom: 20px;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="welcome-message">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h1>
                <p>Ready to continue your learning journey?</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-label">Enrolled Courses</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $completed_courses; ?></div>
                <div class="stat-label">Completed Courses</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo $in_progress_courses; ?></div>
                <div class="stat-label">In Progress</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $total_learning_hours; ?></div>
                <div class="stat-label">Learning Hours</div>
            </div>
        </div>

        <!-- My Courses Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">My Courses</h2>
                <a href="#" class="btn btn-outline-primary" onclick="toggleView()" id="viewToggle">Table View</a>
            </div>

            <?php if (empty($enrollments)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No courses enrolled yet</h3>
                    <p>Start your learning journey by enrolling in a course.</p>
                    <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                </div>
            <?php else: ?>
                <!-- Course Cards View (Default) -->
                <div id="cardsView" class="courses-grid">
                    <?php foreach ($enrollments as $enrollment):
                        $progress = $progress_data[$enrollment['course_id']] ?? 0;
                    ?>
                    <div class="course-card-dashboard">
                        <img src="../assets/images/courses/<?php echo $enrollment['thumbnail']; ?>"
                             alt="<?php echo $enrollment['title']; ?>"
                             class="course-thumbnail-small">

                        <div class="course-card-body">
                            <h3 class="course-card-title"><?php echo htmlspecialchars($enrollment['title']); ?></h3>

                            <div class="course-meta" style="display: flex; justify-content: space-between; color: var(--gray-color); font-size: 0.9rem; margin-bottom: 15px;">
                                <span><i class="fas fa-play-circle"></i> <?php echo $enrollment['module_count']; ?> modules</span>
                                <span><i class="fas fa-clock"></i> <?php echo $enrollment['duration_hours']; ?>h</span>
                            </div>

                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                                <div class="progress-text"><?php echo round($progress); ?>% Complete</div>
                            </div>

                            <div class="course-actions">
                                <a href="learning.php?course=<?php echo $enrollment['course_id']; ?>" class="btn-continue">
                                    <?php echo $progress > 0 ? 'Continue Learning' : 'Start Course'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Courses Table View (Hidden by default) -->
                <div id="tableView" class="courses-table" style="display: none;">
                    <div class="table-header">
                        <div>Course</div>
                    <div style="text-align: center;">Progress</div>
                    <div style="text-align: center;">Status</div>
                    <div style="text-align: center;">Actions</div>
                </div>
                
                <?php foreach ($enrollments as $enrollment): 
                    $progress = $progress_data[$enrollment['course_id']] ?? 0;
                    $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in-progress' : 'not-started');
                ?>
                <div class="table-row">
                    <div class="course-info">
                        <img src="../assets/images/courses/<?php echo $enrollment['thumbnail']; ?>" 
                             alt="<?php echo $enrollment['title']; ?>" 
                             class="course-thumbnail-table">
                        <div>
                            <div class="course-title-table"><?php echo htmlspecialchars($enrollment['title']); ?></div>
                            <div class="course-instructor">
                                <i class="fas fa-user-circle"></i> <?php echo $enrollment['instructor_name']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress-cell">
                        <div class="progress-circle">
                            <svg>
                                <circle class="progress-circle-bg" cx="30" cy="30" r="25"></circle>
                                <circle class="progress-circle-fill" cx="30" cy="30" r="25" 
                                        style="--progress: <?php echo $progress; ?>"></circle>
                            </svg>
                            <div class="progress-percentage"><?php echo $progress; ?>%</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <?php if ($status == 'completed'): ?>
                            <span class="status-badge status-completed">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                        <?php elseif ($status == 'in-progress'): ?>
                            <span class="status-badge status-progress">
                                <i class="fas fa-spinner"></i> In Progress
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-not-started">
                                <i class="fas fa-clock"></i> Not Started
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="course-actions-table">
                        <a href="learning.php?course=<?php echo $enrollment['course_id']; ?>" class="btn-table btn-continue-table">
                            <i class="fas fa-play-circle"></i> Continue
                        </a>
                        <a href="../course-detail.php?slug=<?php echo $enrollment['slug']; ?>" class="btn-table btn-view-table">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="../courses.php" class="quick-link-item">
                <div class="quick-link-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 1rem;">Enroll in New Course</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Expand your skills with new courses</p>
                </div>
            </a>

            <a href="wishlist.php" class="quick-link-item">
                <div class="quick-link-icon">
                    <i class="far fa-heart"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 1rem;">My Wishlist</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Courses you're interested in</p>
                </div>
            </a>

            <a href="profile.php" class="quick-link-item">
                <div class="quick-link-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 1rem;">Update Profile</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Manage your account settings</p>
                </div>
            </a>

            <a href="../faq.php" class="quick-link-item">
                <div class="quick-link-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 1rem;">Get Help</h4>
                    <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Find answers to common questions</p>
                </div>
            </a>
        </div>

        <!-- Course Progress Tips -->
        <div style="background: linear-gradient(135deg, var(--accent-color) 0%, #2ecc71 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 40px;">
            <h3 style="color: white; margin-bottom: 15px;"><i class="fas fa-lightbulb"></i> Learning Tips</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: white; font-size: 1.1rem; margin-bottom: 10px;">Set Daily Goals</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem;">Commit to at least 30 minutes of learning every day.</p>
                </div>
                <div>
                    <h4 style="color: white; font-size: 1.1rem; margin-bottom: 10px;">Practice Regularly</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem;">Apply what you learn through hands-on exercises.</p>
                </div>
                <div>
                    <h4 style="color: white; font-size: 1.1rem; margin-bottom: 10px;">Take Notes</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem;">Document key concepts for future reference.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
<?php include '../includes/footer.php'; ?>

<script>
        // View toggle functionality
        function toggleView() {
            const cardsView = document.getElementById('cardsView');
            const tableView = document.getElementById('tableView');
            const viewToggle = document.getElementById('viewToggle');

            if (cardsView.style.display === 'none') {
                cardsView.style.display = 'grid';
                tableView.style.display = 'none';
                viewToggle.textContent = 'Table View';
            } else {
                cardsView.style.display = 'none';
                tableView.style.display = 'block';
                viewToggle.textContent = 'Card View';
            }
        }

        // Update progress circle animations
        document.addEventListener('DOMContentLoaded', function() {
            const progressCircles = document.querySelectorAll('.progress-circle-fill');
            progressCircles.forEach(circle => {
                const progress = circle.style.getPropertyValue('--progress');
                const circumference = 2 * Math.PI * 25;
                const offset = circumference - (progress / 100) * circumference;
                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                circle.style.strokeDashoffset = offset;
            });
        });

        // Filter courses by status (for table view)
        function filterCourses(status) {
            const rows = document.querySelectorAll('.table-row');
            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                if (status === 'all' || statusBadge.classList.contains('status-' + status)) {
                    row.style.display = 'grid';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>