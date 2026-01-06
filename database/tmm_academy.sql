-- Create Database
CREATE DATABASE IF NOT EXISTS tmm_academy;
USE tmm_academy;

-- 1. Users Table (Students)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 2. Admin Users Table (Separate for admin panel)
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Instructors Table
CREATE TABLE instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    specialization VARCHAR(100),
    bio TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50)
);

-- 5. Courses Table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    category_id INT,
    instructor_id INT,
    short_description TEXT,
    full_description LONGTEXT,
    price DECIMAL(10,2) DEFAULT 0,
    discount_price DECIMAL(10,2),
    thumbnail VARCHAR(255),
    duration_hours INT,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (instructor_id) REFERENCES instructors(id)
);

-- 6. Course Modules
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    module_order INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- 7. Course Videos
CREATE TABLE videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    video_url VARCHAR(255),
    duration_minutes INT,
    video_order INT,
    is_preview BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- 8. Enrollments Table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    amount_paid DECIMAL(10,2) DEFAULT 0,
    transaction_id VARCHAR(100),
    payment_method ENUM('card', 'paypal', 'bank_transfer', 'free', 'demo') DEFAULT 'free',
    payment_gateway VARCHAR(50) DEFAULT 'demo', -- stripe, paypal, demo, etc.
    payment_date TIMESTAMP NULL,
    refund_amount DECIMAL(10,2) DEFAULT 0,
    refund_date TIMESTAMP NULL,
    refund_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_enrolled_at (enrolled_at)
);

-- 9. User Wishlist
CREATE TABLE wishlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    UNIQUE KEY unique_wishlist (user_id, course_id)
);

-- 10. User Progress
CREATE TABLE user_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    video_id INT,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_percentage INT DEFAULT 0 CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
    watch_duration_seconds INT DEFAULT 0,
    last_position_seconds INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, video_id),
    INDEX idx_user_course (user_id, course_id),
    INDEX idx_completed (is_completed),
    INDEX idx_watched_at (watched_at)
);

-- 11. Payment Logs (for audit and debugging)
CREATE TABLE payment_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    course_id INT,
    enrollment_id INT,
    payment_method VARCHAR(50),
    payment_gateway VARCHAR(50) DEFAULT 'demo',
    transaction_id VARCHAR(100),
    amount DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(50), -- initiated, processing, completed, failed, cancelled, refunded
    gateway_response TEXT,
    error_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 12. FAQ Table
CREATE TABLE faqs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_display_order (display_order),
    INDEX idx_active (is_active)
);

-- Insert Default Admin (password: admin123)
INSERT INTO admins (username, password, email, full_name, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tmmacademy.com', 'System Administrator', 'super_admin');

-- Insert Sample Categories
INSERT INTO categories (name, slug, description, icon) VALUES
('Python Programming', 'python-programming', 'Learn Python programming language', 'fas fa-python'),
('Web Development', 'web-development', 'Full stack web development courses', 'fas fa-code'),
('JavaScript', 'javascript', 'Master JavaScript and modern frameworks', 'fab fa-js'),
('Data Science', 'data-science', 'Data analysis and machine learning', 'fas fa-chart-line'),
('Mobile Development', 'mobile-development', 'Android and iOS app development', 'fas fa-mobile-alt'),
('UI/UX Design', 'ui-ux-design', 'User interface and experience design', 'fas fa-paint-brush');

-- Insert Sample Instructors
INSERT INTO instructors (name, email, specialization, bio) VALUES
('Mahade Hasan', 'mahade@tmmacademy.com', 'Python Expert', 'Senior Python Developer with 10+ years of experience'),
('Mohibul Hasan', 'mohibul@tmmacademy.com', 'Full Stack Developer', 'Expert in MERN stack and cloud technologies'),
('Tamim Akondo', 'tamim@tmmacademy.com', 'Web Development', 'Frontend specialist with expertise in modern frameworks'),
('Md Sohag Abir', 'sohag@tmmacademy.com', 'JavaScript', 'JavaScript architect and framework specialist'),
('Masum Parvez', 'masum@tmmacademy.com', 'UI/UX Design', 'Award-winning designer with 8+ years experience');

-- Insert Sample Courses
INSERT INTO courses (title, slug, category_id, instructor_id, short_description, full_description, price, discount_price, thumbnail, duration_hours, difficulty, is_featured, is_active) VALUES
-- Python Courses
('Python for Beginners', 'python-for-beginners', 1, 1, 'Learn Python programming from scratch with hands-on projects', 'Start your programming journey with Python! This comprehensive course covers Python fundamentals, data structures, object-oriented programming, and real-world projects. Perfect for complete beginners who want to become proficient Python developers.', 49.99, 29.99, 'python-beginners.jpg', 20, 'beginner', TRUE, TRUE),

('Advanced Python Programming', 'advanced-python-programming', 1, 1, 'Master advanced Python concepts including decorators, generators, and concurrency', 'Take your Python skills to the next level with advanced concepts like decorators, generators, async programming, multiprocessing, and advanced data structures. Build complex applications and optimize your code for performance.', 79.99, 59.99, 'python-advanced.jpg', 35, 'advanced', TRUE, TRUE),

('Python for Data Science', 'python-data-science', 4, 1, 'Learn data analysis and visualization with Python, pandas, and matplotlib', 'Dive into the world of data science with Python! Learn to analyze data, create visualizations, and build predictive models using popular libraries like pandas, NumPy, matplotlib, and scikit-learn.', 89.99, 69.99, 'python-data-science.jpg', 40, 'intermediate', TRUE, TRUE),

-- Web Development Courses
('Complete Web Development Bootcamp', 'complete-web-development-bootcamp', 2, 2, 'Full-stack web development with HTML, CSS, JavaScript, Node.js, and React', 'Become a full-stack web developer! Learn frontend technologies (HTML, CSS, JavaScript) and backend development (Node.js, Express, MongoDB). Build real-world projects including e-commerce sites and social media platforms.', 99.99, 79.99, 'web-development-bootcamp.jpg', 60, 'beginner', TRUE, TRUE),

('Frontend Development with React', 'frontend-development-react', 2, 3, 'Build modern web applications with React, Redux, and modern JavaScript', 'Master React and modern frontend development! Learn component-based architecture, state management with Redux, hooks, context API, and build responsive, interactive web applications.', 69.99, 49.99, 'react-frontend.jpg', 30, 'intermediate', FALSE, TRUE),

('Backend Development with Node.js', 'backend-development-nodejs', 2, 2, 'Build scalable backend applications with Node.js, Express, and MongoDB', 'Learn server-side development with Node.js! Build RESTful APIs, work with databases, implement authentication, and deploy scalable applications. Includes real-world projects and best practices.', 79.99, 59.99, 'nodejs-backend.jpg', 35, 'intermediate', FALSE, TRUE),

-- JavaScript Courses
('JavaScript Fundamentals', 'javascript-fundamentals', 3, 4, 'Master JavaScript from basics to advanced concepts including ES6+', 'Comprehensive JavaScript course covering everything from basic syntax to advanced concepts like closures, prototypes, async/await, and ES6+ features. Build a strong foundation for modern web development.', 59.99, 39.99, 'javascript-fundamentals.jpg', 25, 'beginner', FALSE, TRUE),

('Advanced JavaScript & Frameworks', 'advanced-javascript-frameworks', 3, 4, 'Deep dive into advanced JavaScript patterns and popular frameworks', 'Advanced JavaScript course covering design patterns, performance optimization, testing, and modern frameworks. Learn to build complex applications with Vue.js, Angular, and advanced React patterns.', 89.99, 69.99, 'javascript-advanced.jpg', 45, 'advanced', TRUE, TRUE),

-- Mobile Development Courses
('React Native Mobile App Development', 'react-native-mobile-development', 5, 2, 'Build cross-platform mobile apps with React Native', 'Create native mobile applications for iOS and Android using React Native! Learn to build, test, and deploy mobile apps. Includes UI components, navigation, API integration, and app store deployment.', 79.99, 59.99, 'react-native-mobile.jpg', 35, 'intermediate', FALSE, TRUE),

('Flutter & Dart Mobile Development', 'flutter-dart-mobile-development', 5, 3, 'Build beautiful native apps with Flutter and Dart', 'Learn Flutter and Dart to create stunning cross-platform mobile applications! Master widgets, state management, animations, and deploy to both iOS and Android app stores.', 69.99, 49.99, 'flutter-mobile.jpg', 30, 'beginner', FALSE, TRUE),

-- UI/UX Design Courses
('UI/UX Design Fundamentals', 'ui-ux-design-fundamentals', 6, 5, 'Learn the principles of user interface and user experience design', 'Master the fundamentals of UI/UX design! Learn design thinking, user research, wireframing, prototyping, and design systems. Create beautiful and functional user interfaces.', 59.99, 39.99, 'ui-ux-fundamentals.jpg', 25, 'beginner', FALSE, TRUE),

('Advanced UI/UX Design & Prototyping', 'advanced-ui-ux-design-prototyping', 6, 5, 'Create professional designs with Figma, Adobe XD, and prototyping tools', 'Take your design skills to the next level! Learn advanced prototyping, design systems, accessibility, and user testing. Master professional tools like Figma and Adobe XD.', 79.99, 59.99, 'ui-ux-advanced.jpg', 35, 'advanced', FALSE, TRUE);

-- Insert Sample Enrollments (to demonstrate payment system)
INSERT INTO enrollments (user_id, course_id, enrolled_at, payment_status, amount_paid, payment_method, payment_gateway, transaction_id, payment_date) VALUES
(1, 1, '2024-01-15 10:30:00', 'completed', 29.99, 'card', 'demo', 'DEMO_20240115_001', '2024-01-15 10:30:00'),
(1, 4, '2024-01-20 14:45:00', 'completed', 79.99, 'card', 'demo', 'DEMO_20240120_002', '2024-01-20 14:45:00'),
(1, 5, '2024-01-25 09:15:00', 'completed', 69.99, 'card', 'demo', 'DEMO_20240125_003', '2024-01-25 09:15:00');

-- Insert Sample User Progress
INSERT INTO user_progress (user_id, course_id, video_id, progress_percentage, watch_duration_seconds, last_position_seconds, is_completed, completed_at) VALUES
(1, 1, 1, 100, 300, 300, TRUE, '2024-01-16 11:00:00'),
(1, 1, 2, 85, 510, 510, FALSE, NULL),
(1, 4, 8, 100, 480, 480, TRUE, '2024-01-21 15:30:00'),
(1, 4, 9, 60, 720, 720, FALSE, NULL);

-- Insert Sample Payment Logs
INSERT INTO payment_logs (user_id, course_id, enrollment_id, payment_method, payment_gateway, transaction_id, amount, status, ip_address, created_at) VALUES
(1, 1, 1, 'card', 'demo', 'DEMO_20240115_001', 29.99, 'completed', '127.0.0.1', '2024-01-15 10:30:00'),
(1, 4, 2, 'card', 'demo', 'DEMO_20240120_002', 79.99, 'completed', '127.0.0.1', '2024-01-20 14:45:00'),
(1, 5, 3, 'card', 'demo', 'DEMO_20240125_003', 69.99, 'completed', '127.0.0.1', '2024-01-25 09:15:00');

-- Insert Sample FAQs
-- 13. Reviews Table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    review_text TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_course_review (user_id, course_id),
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_rating (rating),
    INDEX idx_is_approved (is_approved),
    INDEX idx_is_featured (is_featured),
    INDEX idx_created_at (created_at)
);

-- Insert Sample Reviews
INSERT INTO reviews (user_id, course_id, rating, title, review_text, is_approved, is_featured, created_at) VALUES
-- Featured reviews for homepage
(1, 4, 5, 'Life-changing experience!', 'TMM Academy completely changed my career trajectory. The instructors are top-notch and the hands-on projects helped me land my dream job as a full-stack developer. Highly recommended!', TRUE, TRUE, '2024-01-15 10:30:00'),
(1, 9, 5, 'Perfect for beginners', 'As someone with no programming background, I was nervous about starting. The beginner-friendly approach and supportive community made learning enjoyable. Now I build mobile apps!', TRUE, TRUE, '2024-01-20 14:45:00'),
(1, 3, 5, 'Outstanding course quality', 'The Python data science course was exceptional. The real-world projects and industry insights gave me the confidence to transition into data analysis. Best investment in my career!', TRUE, TRUE, '2024-01-25 09:15:00'),

-- Additional featured reviews
(1, 4, 5, 'Got me hired!', 'The web development bootcamp was comprehensive and practical. I went from zero knowledge to building complex websites. The career support helped me get hired within weeks!', TRUE, TRUE, '2024-02-01 16:20:00'),
(1, 9, 5, 'Excellent teaching', 'Outstanding React Native course! The instructor\'s expertise and the step-by-step guidance helped me create my first mobile app. The community support is incredible.', TRUE, TRUE, '2024-02-05 11:30:00'),
(1, 10, 5, 'Eye-opening experience', 'The UI/UX fundamentals course opened my eyes to user-centered design. Combined with the web development skills, I now create beautiful, functional websites.', TRUE, TRUE, '2024-02-10 13:45:00'),

-- Regular approved reviews (not featured)
(1, 1, 5, 'Great start to programming', 'Python for Beginners was an excellent introduction to programming. The pace was perfect for someone with no experience, and the exercises were challenging but achievable.', TRUE, FALSE, '2024-02-15 10:00:00'),
(1, 2, 4, 'Challenging but rewarding', 'Advanced Python really pushed my limits. Some concepts were difficult to grasp initially, but the instructor\'s explanations and practice exercises made it all click eventually.', TRUE, FALSE, '2024-02-20 15:30:00'),
(1, 5, 5, 'Frontend development mastery', 'The React course was fantastic! I learned modern React patterns, hooks, and state management. The project-based approach really solidified my understanding.', TRUE, FALSE, '2024-03-01 12:15:00'),
(1, 6, 4, 'Solid backend skills', 'Node.js backend development course provided a comprehensive understanding of server-side programming. The API building exercises were particularly valuable.', TRUE, FALSE, '2024-03-05 14:20:00'),
(1, 7, 5, 'JavaScript mastery achieved', 'JavaScript Fundamentals course was thorough and well-structured. The ES6+ coverage was excellent, and the practical examples helped reinforce concepts.', TRUE, FALSE, '2024-03-10 09:45:00'),
(1, 8, 4, 'Valuable advanced concepts', 'Advanced JavaScript covered complex topics like design patterns and performance optimization. Challenging but extremely valuable for professional development.', TRUE, FALSE, '2024-03-15 16:30:00'),
(1, 11, 5, 'Flutter is amazing!', 'Flutter & Dart course was excellent for cross-platform development. The hot reload feature and widget system made development so much faster and more enjoyable.', TRUE, FALSE, '2024-03-20 11:00:00'),
(1, 12, 5, 'Design thinking revolutionized', 'UI/UX Advanced course taught me how to think like a designer. The prototyping tools and user research methodologies were game-changing for my development work.', TRUE, FALSE, '2024-03-25 13:45:00'),

-- Mixed rating reviews for realism
(1, 1, 4, 'Good foundation course', 'Python for Beginners provided a solid foundation. Some sections could be more detailed, but overall it\'s a great starting point for programming.', TRUE, FALSE, '2024-04-01 10:30:00'),
(1, 5, 4, 'Strong React foundation', 'Frontend with React course was comprehensive. The Redux section could use more examples, but the component architecture teaching was excellent.', TRUE, FALSE, '2024-04-05 15:20:00'),
(1, 7, 3, 'Decent JS course', 'JavaScript Fundamentals covered the basics well, but some advanced concepts were rushed. Still, it provided a good foundation for further learning.', TRUE, FALSE, '2024-04-10 12:00:00'),

-- Recent reviews (last 30 days)
(1, 4, 5, 'Incredible learning platform', 'TMM Academy has the best learning experience I\'ve ever had. The video quality, interactive exercises, and supportive community make learning programming enjoyable.', TRUE, FALSE, '2024-12-01 09:15:00'),
(1, 3, 5, 'Data science made simple', 'Python for Data Science course made complex data analysis concepts accessible. The pandas and matplotlib tutorials were particularly well-explained.', TRUE, FALSE, '2024-12-05 14:30:00'),
(1, 9, 5, 'Mobile dev success', 'React Native course exceeded my expectations. Built three apps during the course and published one to the App Store. The instructor\'s guidance was invaluable.', TRUE, FALSE, '2024-12-10 16:45:00'),
(1, 10, 5, 'Design and development synergy', 'UI/UX Fundamentals perfectly complemented my web development skills. Now I can create user-centered applications that look great and work beautifully.', TRUE, FALSE, '2024-12-15 11:20:00');

INSERT INTO faqs (question, answer, category, display_order, is_active) VALUES
('How do I enroll in a course?', 'Click the "Enroll Now" button on any course page and follow the payment process. We accept all major credit cards.', 'General', 1, TRUE),
('Do I need any prior experience?', 'No! We have courses for all levels - from complete beginners to advanced programmers.', 'General', 2, TRUE),
('How long do I have access to a course?', 'You get lifetime access to all enrolled courses. Learn at your own pace!', 'Payment', 3, TRUE),
('Will I get a certificate?', 'Yes, you will receive a certificate of completion for every finished course.', 'Certification', 4, TRUE),
('What if I need help during the course?', 'We provide 24/7 support through our discussion forums and email support.', 'Support', 5, TRUE),
('Is my payment information secure?', 'Yes! We use industry-standard encryption and never store your card details on our servers.', 'Payment', 6, TRUE),
('Can I download course materials?', 'Yes, all enrolled students can download course materials, code samples, and resources.', 'General', 7, TRUE),
('Do you offer refunds?', 'We offer a 30-day money-back guarantee if you are not satisfied with your course.', 'Payment', 8, TRUE);

-- Insert Sample Course Modules and Videos for Python for Beginners (Course ID: 1)
INSERT INTO modules (course_id, title, description, module_order) VALUES
(1, 'Getting Started with Python', 'Introduction to Python programming and development environment setup', 1),
(1, 'Python Basics', 'Variables, data types, operators, and basic input/output', 2),
(1, 'Control Structures', 'Conditional statements and loops in Python', 3),
(1, 'Functions and Modules', 'Creating and using functions, working with modules', 4),
(1, 'Data Structures', 'Lists, tuples, dictionaries, and sets', 5),
(1, 'File Handling', 'Reading from and writing to files', 6),
(1, 'Final Project', 'Build a complete Python application', 7);

INSERT INTO videos (module_id, title, description, video_url, duration_minutes, video_order, is_preview) VALUES
(1, 'Welcome to Python Programming', 'Course introduction and what you will learn', 'python-intro.mp4', 5, 1, TRUE),
(1, 'Installing Python and IDE Setup', 'How to install Python and set up your development environment', 'python-setup.mp4', 10, 2, FALSE),
(2, 'Variables and Data Types', 'Understanding variables and different data types in Python', 'python-variables.mp4', 15, 1, FALSE),
(2, 'Basic Operations and Expressions', 'Mathematical and logical operations', 'python-operations.mp4', 12, 2, FALSE),
(3, 'If Statements and Conditional Logic', 'Making decisions in your code', 'python-conditionals.mp4', 18, 1, FALSE),
(3, 'Loops and Iteration', 'For loops and while loops', 'python-loops.mp4', 20, 2, FALSE);

-- Insert Sample Course Modules and Videos for Web Development Bootcamp (Course ID: 4)
INSERT INTO modules (course_id, title, description, module_order) VALUES
(4, 'HTML Fundamentals', 'Learn the building blocks of web pages', 1),
(4, 'CSS Styling', 'Make your websites beautiful with CSS', 2),
(4, 'JavaScript Basics', 'Add interactivity to your websites', 3),
(4, 'Backend with Node.js', 'Server-side programming with Node.js', 4),
(4, 'Database Integration', 'Working with MongoDB and data persistence', 5),
(4, 'Full-Stack Project', 'Build a complete web application', 6);

INSERT INTO videos (module_id, title, description, video_url, duration_minutes, video_order, is_preview) VALUES
(8, 'Introduction to HTML', 'What is HTML and how the web works', 'html-intro.mp4', 8, 1, TRUE),
(8, 'HTML Elements and Structure', 'Basic HTML tags and page structure', 'html-elements.mp4', 15, 2, FALSE),
(9, 'CSS Basics and Selectors', 'How to style HTML elements', 'css-basics.mp4', 20, 1, FALSE),
(9, 'CSS Layout and Flexbox', 'Creating responsive layouts', 'css-flexbox.mp4', 25, 2, FALSE),
(10, 'JavaScript Fundamentals', 'Programming concepts and syntax', 'js-fundamentals.mp4', 22, 1, FALSE),
(10, 'DOM Manipulation', 'Interacting with web page elements', 'js-dom.mp4', 18, 2, FALSE);

-- Create Indexes for Performance
-- Courses table indexes
CREATE INDEX idx_courses_category ON courses(category_id);
CREATE INDEX idx_courses_instructor ON courses(instructor_id);
CREATE INDEX idx_courses_featured ON courses(is_featured, is_active);
CREATE INDEX idx_courses_active ON courses(is_active);
CREATE INDEX idx_courses_price ON courses(price, discount_price);
CREATE INDEX idx_courses_created ON courses(created_at);

-- Enrollments table indexes (additional to existing)
CREATE INDEX idx_enrollments_user ON enrollments(user_id);
CREATE INDEX idx_enrollments_course ON enrollments(course_id);
CREATE INDEX idx_enrollments_status ON enrollments(payment_status);
CREATE INDEX idx_enrollments_user_status ON enrollments(user_id, payment_status);
CREATE INDEX idx_enrollments_course_status ON enrollments(course_id, payment_status);

-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created ON users(created_at);

-- Videos and modules indexes
CREATE INDEX idx_videos_module ON videos(module_id, video_order);
CREATE INDEX idx_modules_course ON modules(course_id, module_order);

-- Wishlist indexes
CREATE INDEX idx_wishlists_user ON wishlists(user_id);
CREATE INDEX idx_wishlists_course ON wishlists(course_id);

-- Progress tracking indexes (additional to existing)
CREATE INDEX idx_progress_user ON user_progress(user_id);
CREATE INDEX idx_progress_course ON user_progress(course_id);
CREATE INDEX idx_progress_video ON user_progress(video_id);
CREATE INDEX idx_progress_completion ON user_progress(is_completed, completed_at);

-- User Activity Logs Table (for tracking user actions)
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_activity_user (user_id),
    INDEX idx_user_activity_action (action),
    INDEX idx_user_activity_created (created_at)
);

-- Insert Default Admin User (username: admin, password: admin123)
INSERT INTO admins (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tmmacademy.com', 'System Administrator', 'super_admin');

-- Insert Sample Users with realistic data
INSERT INTO users (username, email, password, full_name, phone, status) VALUES
('johndoe', 'john.doe@email.com', '$2y$10$hashedpassword1', 'John Doe', '+1-555-0101', 'active'),
('sarahsmith', 'sarah.smith@email.com', '$2y$10$hashedpassword2', 'Sarah Smith', '+1-555-0102', 'active'),
('mikewilson', 'mike.wilson@email.com', '$2y$10$hashedpassword3', 'Mike Wilson', '+1-555-0103', 'active'),
('emmajohnson', 'emma.johnson@email.com', '$2y$10$hashedpassword4', 'Emma Johnson', '+1-555-0104', 'active'),
('alexbrown', 'alex.brown@email.com', '$2y$10$hashedpassword5', 'Alex Brown', '+1-555-0105', 'active'),
('testuser', 'test@example.com', '$2y$10$hashedpassword6', 'Test User', '+1-555-0106', 'active');

-- Insert Sample Enrollments (matching our current data)
INSERT INTO enrollments (user_id, course_id, payment_status, enrolled_at) VALUES
(1, 1, 'completed', '2024-01-15 10:00:00'), -- John Doe enrolled in Python for Beginners
(2, 2, 'completed', '2024-01-20 14:30:00'), -- Sarah Smith enrolled in Web Development
(3, 4, 'completed', '2024-01-25 09:15:00'), -- Mike Wilson enrolled in React Native
(4, 5, 'completed', '2024-02-01 16:45:00'), -- Emma Johnson enrolled in UI/UX
(5, 6, 'completed', '2024-02-05 11:20:00'), -- Alex Brown enrolled in Advanced Python
(6, 3, 'completed', '2024-02-10 13:40:00'), -- Test User enrolled in JavaScript
(6, 4, 'completed', '2024-02-15 15:20:00'), -- Test User enrolled in React Native
(6, 5, 'completed', '2024-02-20 10:30:00'), -- Test User enrolled in UI/UX
(6, 6, 'completed', '2024-02-25 12:45:00'), -- Test User enrolled in Advanced Python
(6, 7, 'completed', '2024-03-01 14:15:00'); -- Test User enrolled in Python Data Science

-- Insert Sample User Progress (to enable reviews)
INSERT INTO user_progress (user_id, course_id, lesson_id, progress_percentage, completed_at) VALUES
(6, 3, 1, 85, '2024-02-12 14:20:00'), -- Test User - JavaScript progress
(6, 4, 1, 90, '2024-02-17 16:10:00'), -- Test User - React Native progress
(6, 5, 1, 75, '2024-02-22 11:45:00'), -- Test User - UI/UX progress
(6, 6, 1, 80, '2024-02-27 13:30:00'), -- Test User - Advanced Python progress
(6, 7, 1, 70, '2024-03-03 15:00:00'); -- Test User - Python Data Science progress

-- Insert Sample Wishlist Items
INSERT INTO wishlists (user_id, course_id, added_at) VALUES
(1, 3, '2024-01-16 11:30:00'), -- John likes JavaScript
(2, 5, '2024-01-21 15:45:00'), -- Sarah likes UI/UX
(3, 7, '2024-01-26 10:20:00'), -- Mike likes Python Data Science
(4, 1, '2024-02-02 17:10:00'), -- Emma likes Python Beginners
(5, 2, '2024-02-06 12:00:00'); -- Alex likes Web Development

-- Insert Sample Payment Logs
INSERT INTO payment_logs (user_id, course_id, amount, currency, payment_method, transaction_id, status, created_at) VALUES
(1, 1, 29.99, 'USD', 'stripe', 'txn_1234567890', 'completed', '2024-01-15 10:00:00'),
(2, 2, 79.99, 'USD', 'stripe', 'txn_1234567891', 'completed', '2024-01-20 14:30:00'),
(3, 4, 59.99, 'USD', 'stripe', 'txn_1234567892', 'completed', '2024-01-25 09:15:00'),
(4, 5, 59.99, 'USD', 'stripe', 'txn_1234567893', 'completed', '2024-02-01 16:45:00'),
(5, 6, 59.99, 'USD', 'stripe', 'txn_1234567894', 'completed', '2024-02-05 11:20:00'),
(6, 3, 39.99, 'USD', 'stripe', 'txn_1234567895', 'completed', '2024-02-10 13:40:00'),
(6, 4, 59.99, 'USD', 'stripe', 'txn_1234567896', 'completed', '2024-02-15 15:20:00'),
(6, 5, 59.99, 'USD', 'stripe', 'txn_1234567897', 'completed', '2024-02-20 10:30:00'),
(6, 6, 59.99, 'USD', 'stripe', 'txn_1234567898', 'completed', '2024-02-25 12:45:00'),
(6, 7, 69.99, 'USD', 'stripe', 'txn_1234567899', 'completed', '2024-03-01 14:15:00');

-- Update Reviews to match our current sample data
INSERT INTO reviews (user_id, course_id, rating, title, review_text, is_approved, is_featured, created_at) VALUES
-- Recent real reviews (matching what we have in the system)
(6, 5, 4, 'Great Introduction to UI/UX', 'This UI/UX Design Fundamentals course provides an excellent foundation for understanding user-centered design principles. The instructor covers important concepts like user research, wireframing, prototyping, and usability testing. The practical examples and assignments really helped me understand how to apply these concepts in real projects. Highly recommended for beginners in design!', TRUE, FALSE, '2026-01-05 23:42:00'),
(6, 4, 5, 'Excellent React Native Course', 'This React Native course was fantastic! I learned how to build cross-platform mobile applications using React Native. The instructor explained concepts clearly and the hands-on projects helped me understand the practical applications. I highly recommend this course to anyone interested in mobile app development.', TRUE, FALSE, '2026-01-05 23:37:00'),
(1, 1, 5, 'Best course for beginners', 'This is the best course for beginners. Because it breaks down all basic fundamentals in a very easy way to understand. The instructor is amazing and the projects are very practical. I would definitely recommend this course to anyone starting their programming journey.', TRUE, TRUE, '2024-01-10 09:30:00');

-- Insert Sample User Activity Logs
INSERT INTO user_activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES
(1, 'login', 'User logged in successfully', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2024-01-15 10:05:00'),
(2, 'enrollment', 'Enrolled in course: Complete Web Development Bootcamp', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', '2024-01-20 14:35:00'),
(6, 'review_submitted', 'Submitted review for course: UI/UX Design Fundamentals', '192.168.1.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2026-01-05 23:42:00'),
(6, 'review_submitted', 'Submitted review for course: React Native Mobile Development', '192.168.1.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2026-01-05 23:37:00');

-- Success message
SELECT 'Database setup completed successfully! All tables and sample data have been created.' as status;