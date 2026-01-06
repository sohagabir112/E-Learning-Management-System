<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check admin login
if (!isAdminLoggedIn()) {
    redirect('index.php');
}

// Handle form submissions
$message = '';
$error = '';

// Handle different admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        $conn = getDBConnection();

        switch ($action) {
            case 'update_course_status':
                $course_id = (int)$_POST['course_id'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $stmt = $conn->prepare("UPDATE courses SET is_active = ? WHERE id = ?");
                $stmt->bind_param("ii", $is_active, $course_id);
                $stmt->execute();
                $message = "Course status updated successfully!";
                break;

            case 'update_review_status':
                $review_id = (int)$_POST['review_id'];
                $is_approved = isset($_POST['is_approved']) ? 1 : 0;
                $stmt = $conn->prepare("UPDATE reviews SET is_approved = ? WHERE id = ?");
                $stmt->bind_param("ii", $is_approved, $review_id);
                $stmt->execute();
                $message = "Review status updated successfully!";
                break;

            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $message = "User deleted successfully!";
                break;

            case 'update_user_status':
                $user_id = (int)$_POST['user_id'];
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $user_id);
                $stmt->execute();
                $message = "User status updated successfully!";
                break;

            case 'delete_course':
                $course_id = (int)$_POST['course_id'];
                $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $message = "Course deleted successfully!";
                break;

            case 'add_category':
                $name = sanitize($_POST['name']);
                $slug = generateSlug($name);
                $description = sanitize($_POST['description']);
                $icon = sanitize($_POST['icon']);

                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $slug, $description, $icon);
                $stmt->execute();
                $message = "Category added successfully!";
                break;

            case 'delete_category':
                $category_id = (int)$_POST['category_id'];
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $message = "Category deleted successfully!";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get statistics
$conn = getDBConnection();

// Dashboard stats
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_courses' => $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'],
    'total_enrollments' => $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE payment_status = 'completed'")->fetch_assoc()['count'],
    'total_reviews' => $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 1")->fetch_assoc()['count'],
    'pending_reviews' => $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 0")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(c.price) as revenue FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.payment_status = 'completed'")->fetch_assoc()['revenue'] ?? 0
];

// Get recent data
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_enrollments = $conn->query("SELECT e.*, u.full_name, u.username, c.title as course_title FROM enrollments e JOIN users u ON e.user_id = u.id JOIN courses c ON e.course_id = c.id WHERE e.payment_status = 'completed' ORDER BY e.enrolled_at DESC LIMIT 5");
$pending_reviews = $conn->query("SELECT r.*, u.full_name, c.title as course_title FROM reviews r JOIN users u ON r.user_id = u.id JOIN courses c ON r.course_id = c.id WHERE r.is_approved = 0 ORDER BY r.created_at DESC LIMIT 10");
$courses = $conn->query("SELECT * FROM courses ORDER BY created_at DESC");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TMM Academy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-dashboard {
            min-height: 100vh;
            background: #f8f9fa;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .admin-nav {
            display: flex;
            gap: 15px;
        }

        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card .revenue {
            color: #27ae60;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .admin-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: var(--gray-bg);
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header h2 {
            margin: 0;
            color: var(--dark-color);
            font-size: 1.3rem;
        }

        .section-content {
            padding: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--gray-bg);
            font-weight: 600;
            color: var(--dark-color);
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-primary:hover {
            background: #2c3e50;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success:hover {
            background: #27ae60;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            min-height: 80px;
            resize: vertical;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .admin-header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-dashboard">
    <header class="admin-header">
        <div class="container">
            <h1><i class="fas fa-cog"></i> TMM Academy Admin Panel</h1>
            <nav class="admin-nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container" style="padding: 30px 0;">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Users</h3>
                <div class="number"><?php echo number_format($stats['total_users']); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-book"></i> Total Courses</h3>
                <div class="number"><?php echo number_format($stats['total_courses']); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-graduation-cap"></i> Total Enrollments</h3>
                <div class="number"><?php echo number_format($stats['total_enrollments']); ?></div>
            </div>
            <div class="stat-card revenue">
                <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
                <div class="number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-star"></i> Approved Reviews</h3>
                <div class="number"><?php echo number_format($stats['total_reviews']); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-clock"></i> Pending Reviews</h3>
                <div class="number"><?php echo number_format($stats['pending_reviews']); ?></div>
            </div>
        </div>

        <div class="content-grid">
            <!-- User Management -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                </div>
                <div class="section-content">
                    <h3>Recent Users</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_user_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 4px; border-radius: 4px; border: 1px solid #ddd;">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="action-btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Course Management -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-book"></i> Course Management</h2>
                </div>
                <div class="section-content">
                    <h3>All Courses</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $courses->data_seek(0); // Reset pointer
                            while ($course = $courses->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $course['id']; ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['category_id']); ?></td>
                                <td>$<?php echo number_format($course['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $course['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_course_status">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?php echo $course['is_active'] ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                        Active
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_course">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="action-btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this course?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Review Management -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-star"></i> Review Management</h2>
                </div>
                <div class="section-content">
                    <h3>Pending Reviews (<?php echo $stats['pending_reviews']; ?>)</h3>
                    <?php if ($pending_reviews->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Course</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($review = $pending_reviews->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['course_title']); ?></td>
                                <td><?php echo $review['rating']; ?> ⭐</td>
                                <td><?php echo htmlspecialchars(substr($review['review_text'], 0, 50)) . (strlen($review['review_text']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_review_status">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="is_approved" value="1" class="action-btn btn-success btn-sm">Approve</button>
                                        <button type="submit" name="is_approved" value="0" class="action-btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>No pending reviews.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enrollments -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-graduation-cap"></i> Recent Enrollments</h2>
                </div>
                <div class="section-content">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($enrollment = $recent_enrollments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                <td>
                                    <span class="status-badge status-active">
                                        <?php echo ucfirst($enrollment['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Management -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-tags"></i> Category Management</h2>
                </div>
                <div class="section-content">
                    <h3>Add New Category</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_category">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Category Name</label>
                                <input type="text" id="name" name="name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="icon">Icon Class</label>
                                <input type="text" id="icon" name="icon" class="form-input" placeholder="fas fa-code" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-textarea"></textarea>
                        </div>
                        <button type="submit" class="action-btn btn-primary">Add Category</button>
                    </form>

                    <h3 style="margin-top: 30px;">Existing Categories</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Icon</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><i class="<?php echo htmlspecialchars($category['icon']); ?>"></i></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="action-btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
