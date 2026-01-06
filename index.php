<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$page_title = "TMM Academy - Learn Programming Online";
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Join Our Online Programming Courses</h1>
                    <p class="hero-description">
                        We help you learn programming through expert-led lessons and hands-on projects.
                        Start your coding journey today with our comprehensive courses.
                    </p>
                    <div class="hero-buttons">
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-play-circle"></i> Start Learning
                        </a>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Join Free
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/hero-image.png?v=<?php echo time(); ?>" alt="Students Learning">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section style="padding: 80px 0; background: var(--light-color);">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.5rem;">Why Choose TMM Academy?</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <!-- Feature 1 -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fas fa-chalkboard-teacher fa-2x" style="color: white;"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Expert Instructors</h3>
                        <p>Learn from industry professionals with years of real-world experience.</p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--accent-color) 0%, #2ecc71 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fas fa-laptop-code fa-2x" style="color: white;"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Hands-on Projects</h3>
                        <p>Build real-world projects to enhance your portfolio and skills.</p>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--warning-color) 0%, #e67e22 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fas fa-certificate fa-2x" style="color: white;"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Certification</h3>
                        <p>Get recognized certificates upon course completion.</p>
                    </div>
                </div>
                
                <!-- Feature 4 -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--danger-color) 0%, #e74c3c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fas fa-users fa-2x" style="color: white;"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Community Support</h3>
                        <p>Join our active community of learners and mentors.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Courses Section -->
    <section style="padding: 80px 0;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Popular Courses</h2>
                <a href="courses.php" class="btn btn-outline-primary">View All Courses</a>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <?php
                // Get popular courses from database
                $conn = getDBConnection();
                $sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name 
                        FROM courses c 
                        LEFT JOIN categories cat ON c.category_id = cat.id 
                        LEFT JOIN instructors i ON c.instructor_id = i.id 
                        WHERE c.is_active = 1 
                        ORDER BY c.created_at DESC 
                        LIMIT 3";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0):
                    while($course = $result->fetch_assoc()):
                ?>
                <div class="card course-card">
                    <img src="assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>" 
                        alt="<?php echo $course['title']; ?>" class="course-thumbnail">
                    <div class="card-body">
                        <span class="course-category"><?php echo $course['category_name']; ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($course['short_description']); ?></p>
                        <p style="color: var(--gray-color); margin-bottom: 10px;">
                            <i class="fas fa-user-circle"></i> <?php echo $course['instructor_name']; ?>
                        </p>
                        <div class="course-price">
                            <span class="current-price">$<?php echo $course['discount_price'] ?: $course['price']; ?></span>
                            <?php if ($course['discount_price']): ?>
                            <span class="original-price">$<?php echo $course['price']; ?></span>
                            <span class="discount-badge"><?php echo calculateDiscount($course['price'], $course['discount_price']); ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <a href="course-detail.php?slug=<?php echo $course['slug']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            View Course
                        </a>
                    </div>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--gray-color);">
                    No courses available yet. Check back soon!
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; padding: 60px 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; text-align: center;">
                <div>
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 10px;">50+</div>
                    <div style="opacity: 0.9;">Courses Available</div>
                </div>
                <div>
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 10px;">10,000+</div>
                    <div style="opacity: 0.9;">Students Enrolled</div>
                </div>
                <div>
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 10px;">98%</div>
                    <div style="opacity: 0.9;">Success Rate</div>
                </div>
                <div>
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 10px;">24/7</div>
                    <div style="opacity: 0.9;">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="padding: 80px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Ready to Start Your Coding Journey?</h2>
            <p style="font-size: 1.2rem; color: var(--gray-color); margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                Join thousands of successful students who have transformed their careers with TMM Academy.
            </p>
            <div class="btn-group" style="justify-content: center;">
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Get Started Free
                </a>
                <a href="courses.php" class="btn btn-outline-primary">
                    <i class="fas fa-play-circle"></i> Browse Courses
                </a>
            </div>
        </div>
    </section>

    <!-- Student Reviews Section -->
    <section style="padding: 80px 0; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 60px;">
                <h2 style="font-size: 2.5rem; margin-bottom: 15px;">What Our Students Say</h2>
                <p style="font-size: 1.1rem; color: var(--gray-color); max-width: 600px; margin: 0 auto;">
                    Hear from students who have transformed their careers through our comprehensive programming courses.
                </p>
                <a href="courses.php" class="btn btn-outline-primary" style="margin-top: 20px;">
                    <i class="fas fa-star"></i> Leave a Review
                </a>
            </div>

            <div class="reviews-grid">
                <?php
                $reviews = getRecentReviews(6);

                // If no reviews in database, show sample reviews
                if (empty($reviews)) {
                    $sample_reviews = [
                        [
                            'full_name' => 'Sarah Johnson',
                            'review_text' => 'TMM Academy completely changed my career trajectory. The instructors are top-notch and the hands-on projects helped me land my dream job as a full-stack developer. Highly recommended!',
                            'rating' => 5,
                            'course_title' => 'Complete Web Development Bootcamp',
                            'title' => 'Life-changing experience!'
                        ],
                        [
                            'full_name' => 'Michael Chen',
                            'review_text' => 'As someone with no programming background, I was nervous about starting. The beginner-friendly approach and supportive community made learning enjoyable. Now I build mobile apps!',
                            'rating' => 5,
                            'course_title' => 'React Native Mobile Development',
                            'title' => 'Perfect for beginners'
                        ],
                        [
                            'full_name' => 'Emily Rodriguez',
                            'review_text' => 'The Python data science course was exceptional. The real-world projects and industry insights gave me the confidence to transition into data analysis. Best investment in my career!',
                            'rating' => 5,
                            'course_title' => 'Python for Data Science',
                            'title' => 'Outstanding course quality'
                        ],
                        [
                            'full_name' => 'David Kim',
                            'review_text' => 'The web development bootcamp was comprehensive and practical. I went from zero knowledge to building complex websites. The career support helped me get hired within weeks!',
                            'rating' => 5,
                            'course_title' => 'Complete Web Development Bootcamp',
                            'title' => 'Got me hired!'
                        ],
                        [
                            'full_name' => 'Jessica Martinez',
                            'review_text' => 'Outstanding React Native course! The instructor\'s expertise and the step-by-step guidance helped me create my first mobile app. The community support is incredible.',
                            'rating' => 5,
                            'course_title' => 'React Native Mobile Development',
                            'title' => 'Excellent teaching'
                        ],
                        [
                            'full_name' => 'Alex Thompson',
                            'review_text' => 'The UI/UX fundamentals course opened my eyes to user-centered design. Combined with the web development skills, I now create beautiful, functional websites.',
                            'rating' => 5,
                            'course_title' => 'UI/UX Design Fundamentals',
                            'title' => 'Eye-opening experience'
                        ]
                    ];
                    $reviews = $sample_reviews;
                }

                foreach ($reviews as $review):
                    // Truncate review text for display
                    $full_text = htmlspecialchars($review['review_text']);
                    $short_text = strlen($full_text) > 80 ? substr($full_text, 0, 80) . '...' : $full_text;
                    $rating = isset($review['rating']) ? $review['rating'] : 5;
                ?>
                <div class="review-card">
                    <div class="review-content">
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $rating ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text">
                            <span class="review-text-short">"<?php echo $short_text; ?>"</span>
                            <span class="review-text-full" style="display: none;">
                                "<?php echo $full_text; ?>"
                            </span>
                            <?php if (strlen($full_text) > 80): ?>
                                <a href="#" class="review-toggle" data-expanded="false">View More</a>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="reviewer-details">
                            <div class="reviewer-name"><?php echo htmlspecialchars($review['full_name']); ?></div>
                            <div class="reviewer-title">
                                <?php echo htmlspecialchars($review['course_title'] ?? 'Student'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>