<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = "My Profile - TMM Academy";
$user = getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    
    $conn = getDBConnection();
    $sql = "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $full_name, $phone, $user['id']);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $success = 'Profile updated successfully!';
    } else {
        $error = 'Failed to update profile. Please try again.';
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error. Please try again.';
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            $error = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
        } else {
            // Validate file size (max 5MB)
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $max_size) {
                $error = 'File size too large. Maximum size is 5MB.';
            } else {
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $user['id'] . '_' . time() . '.' . $file_extension;

                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $new_filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile image if it exists and is not the default
                    if (!empty($user['profile_image']) && $user['profile_image'] !== 'default.jpg' && file_exists('../uploads/profiles/' . $user['profile_image'])) {
                        unlink('../uploads/profiles/' . $user['profile_image']);
                    }

                    // Update database
                    $conn = getDBConnection();
                    $update_sql = "UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $new_filename, $user['id']);

                    if ($update_stmt->execute()) {
                        $user['profile_image'] = $new_filename;
                        $_SESSION['profile_image'] = $new_filename;
                        $success = 'Profile picture updated successfully!';
                    } else {
                        // If database update fails, delete the uploaded file
                        unlink($upload_path);
                        $error = 'Failed to update profile picture in database.';
                    }
                } else {
                    $error = 'Failed to save uploaded file. Please try again.';
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        // Verify current password
        $conn = getDBConnection();
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($current_password, $hashed_password)) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_hashed_password, $user['id']);

            if ($update_stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 50px;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            padding: 30px;
            height: fit-content;
        }
        
        .profile-picture {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light-color);
            margin: 0 auto 15px;
        }
        
        .profile-info {
            text-align: center;
        }
        
        .profile-name {
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: var(--gray-color);
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }
        
        .profile-stat {
            text-align: center;
            padding: 15px;
            background: var(--light-color);
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .profile-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .profile-menu li {
            margin-bottom: 10px;
        }
        
        .profile-menu a {
            display: block;
            padding: 12px 15px;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .profile-menu a:hover,
        .profile-menu a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .profile-content {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            padding: 30px;
        }
        
        .profile-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
            color: var(--gray-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
        }
        
        .password-input {
            position: relative;
        }

        /* ===== COMPREHENSIVE RESPONSIVE DESIGN ===== */

        /* Tablets and smaller laptops (1024px and below) */
        @media (max-width: 1024px) {
            .profile-header {
                padding: 50px 0;
            }

            .profile-header h1 {
                font-size: 2.2rem;
            }

            .profile-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .profile-sidebar {
                order: 2;
            }

            .profile-main {
                order: 1;
            }
        }

        /* Small tablets and large phones (768px and below) */
        @media (max-width: 768px) {
            .profile-header {
                padding: 40px 0;
            }

            .profile-header h1 {
                font-size: 1.8rem;
            }

            .profile-header p {
                font-size: 1rem;
            }

            .profile-container {
                gap: 25px;
            }

            .profile-card {
                padding: 25px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-label {
                font-size: 0.95rem;
                margin-bottom: 6px;
            }

            .form-control {
                padding: 12px;
                font-size: 1rem;
            }

            .btn-primary {
                padding: 12px 24px;
                font-size: 1rem;
            }

            .profile-avatar-section {
                text-align: center;
                margin-bottom: 30px;
            }

            .current-avatar {
                width: 100px;
                height: 100px;
                margin: 0 auto 15px;
            }

            .avatar-upload {
                margin-bottom: 20px;
            }

            .profile-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .profile-info-label {
                font-weight: 600;
                color: var(--primary-color);
            }

            .profile-info-value {
                width: 100%;
                padding: 8px 12px;
                background: #f8f9fa;
                border-radius: 6px;
                border: 1px solid var(--border-color);
            }
        }

        /* Phones (480px and below) */
        @media (max-width: 480px) {
            .profile-header {
                padding: 30px 0;
            }

            .profile-header h1 {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .profile-header p {
                font-size: 0.95rem;
            }

            .profile-container {
                gap: 20px;
            }

            .profile-card {
                padding: 20px;
            }

            .card-title {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-control {
                padding: 10px;
                font-size: 0.95rem;
            }

            .btn-primary {
                width: 100%;
                padding: 14px;
                font-size: 1rem;
            }

            .profile-sidebar {
                padding: 20px;
            }

            .current-avatar {
                width: 80px;
                height: 80px;
            }

            .avatar-upload input[type="file"] {
                font-size: 0.9rem;
            }

            .profile-info-item {
                margin-bottom: 15px;
            }

            .profile-info-value {
                font-size: 0.9rem;
                padding: 10px 12px;
            }

            /* Stack form buttons vertically */
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .form-actions .btn-primary {
                margin-top: 10px;
            }

            /* Adjust grid layouts for mobile */
            .password-input {
                position: relative;
            }

            .password-toggle {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: var(--gray-color);
                cursor: pointer;
                padding: 5px;
            }
        }

        /* Extra small phones (360px and below) */
        @media (max-width: 360px) {
            .profile-header {
                padding: 25px 0;
            }

            .profile-header h1 {
                font-size: 1.3rem;
            }

            .profile-card {
                padding: 18px;
            }

            .card-title {
                font-size: 1.2rem;
                margin-bottom: 18px;
            }

            .form-control {
                padding: 10px 8px;
                font-size: 0.9rem;
            }

            .current-avatar {
                width: 70px;
                height: 70px;
            }

            .profile-info-value {
                font-size: 0.85rem;
                padding: 8px 10px;
            }

            .btn-primary {
                padding: 12px;
                font-size: 0.95rem;
            }
        }

        /* Large desktop screens (1440px and above) */
        @media (min-width: 1440px) {
            .profile-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .profile-card {
                padding: 35px;
            }

            .card-title {
                font-size: 1.8rem;
            }
        }

        /* Ultra-wide screens (1920px and above) */
        @media (min-width: 1920px) {
            .profile-container {
                max-width: 1400px;
            }
        }

        /* Touch devices optimization */
        @media (hover: none) and (pointer: coarse) {
            .btn-primary:hover {
                transform: none;
            }

            .profile-card:hover {
                box-shadow: var(--shadow-md);
            }

            .form-control:focus {
                border-color: var(--secondary-color);
                box-shadow: 0 0 0 0.2rem rgba(142, 68, 173, 0.25);
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .current-avatar,
            .profile-info-value {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Landscape orientation for small screens */
        @media (max-width: 768px) and (orientation: landscape) {
            .profile-header {
                padding: 20px 0;
            }

            .profile-container {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .profile-sidebar {
                order: 1;
            }

            .profile-main {
                order: 2;
            }
        }

        /* Print styles */
        @media print {
            .profile-header,
            .avatar-upload,
            .password-input,
            .form-actions {
                display: none !important;
            }

            .profile-card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }

            .profile-info-item {
                break-inside: avoid;
            }
        }
    </style>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <h1 style="color: white;">My Profile</h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">Manage your account settings and preferences</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-picture">
                    <?php if ($user['profile_image'] && $user['profile_image'] !== 'default.jpg'): ?>
                        <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>"
                             alt="<?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>"
                             class="profile-img"
                             onerror="this.src='../default-avatar.php?initials=<?php echo urlencode(substr($user['full_name'] ?: $user['username'], 0, 2)); ?>'">
                    <?php else: ?>
                        <img src="../default-avatar.php?initials=<?php echo urlencode(substr($user['full_name'] ?: $user['username'], 0, 2)); ?>"
                             alt="<?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>"
                             class="profile-img">
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h2>
                    <p class="profile-email"><?php echo $user['email']; ?></p>
                    <p style="color: var(--gray-color); font-size: 0.9rem;">
                        <i class="fas fa-calendar-alt"></i> Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
                
                <div class="profile-stats">
                    <?php
                    $conn = getDBConnection();
                    
                    // Get enrolled courses count
                    $enrolled_sql = "SELECT COUNT(*) as count FROM enrollments WHERE user_id = ? AND payment_status = 'completed'";
                    $enrolled_stmt = $conn->prepare($enrolled_sql);
                    $enrolled_stmt->bind_param("i", $user['id']);
                    $enrolled_stmt->execute();
                    $enrolled_result = $enrolled_stmt->get_result();
                    $enrolled_count = $enrolled_result->fetch_assoc()['count'];
                    
                    // Get completed courses count
                    $completed_sql = "SELECT COUNT(DISTINCT course_id) as count FROM user_progress WHERE user_id = ? AND progress_percentage >= 100";
                    $completed_stmt = $conn->prepare($completed_sql);
                    $completed_stmt->bind_param("i", $user['id']);
                    $completed_stmt->execute();
                    $completed_result = $completed_stmt->get_result();
                    $completed_count = $completed_result->fetch_assoc()['count'];
                    ?>
                    
                    <div class="profile-stat">
                        <div class="stat-value"><?php echo $enrolled_count; ?></div>
                        <div class="stat-label">Enrolled</div>
                    </div>
                    
                    <div class="profile-stat">
                        <div class="stat-value"><?php echo $completed_count; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                
                <ul class="profile-menu">
                    <li><a href="my-courses.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile.php" class="active"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="my-courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                    <li><a href="../logout.php" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Profile Picture Section -->
                <div class="profile-section">
                    <h2 class="section-title">Profile Picture</h2>

                    <div style="display: flex; gap: 30px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <?php if ($user['profile_image'] && $user['profile_image'] !== 'default.jpg'): ?>
                                <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>"
                                     alt="Profile Picture"
                                     class="profile-img"
                                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid var(--light-color);"
                                     onerror="this.src='../default-avatar.php?initials=<?php echo urlencode(substr($user['full_name'] ?: $user['username'], 0, 2)); ?>'">
                            <?php else: ?>
                                <img src="../default-avatar.php?initials=<?php echo urlencode(substr($user['full_name'] ?: $user['username'], 0, 2)); ?>"
                                     alt="Profile Picture"
                                     class="profile-img"
                                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid var(--light-color);">
                            <?php endif; ?>
                        </div>

                        <div style="flex: 1;">
                            <p style="color: var(--gray-color); margin-bottom: 20px;">
                                Upload a new profile picture. Recommended size: 300x300 pixels or larger.
                                Supported formats: JPG, PNG, GIF. Maximum size: 5MB.
                            </p>

                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="profile_image" class="form-label">Choose Image</label>
                                    <input type="file" id="profile_image" name="profile_image" class="form-control"
                                           accept="image/jpeg,image/jpg,image/png,image/gif" required>
                                    <small style="color: var(--gray-color); font-size: 0.9rem; display: block; margin-top: 5px;">
                                        <i class="fas fa-info-circle"></i> Only image files are allowed
                                    </small>
                                </div>

                                <div style="margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Picture
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('profile_image').value=''">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="profile-section">
                    <h2 class="section-title">Personal Information</h2>

                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($user['full_name'] ?: ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" class="form-control"
                                       value="<?php echo $user['username']; ?>" disabled>
                                <small style="color: var(--gray-color); font-size: 0.9rem;">Username cannot be changed</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" class="form-control"
                                       value="<?php echo $user['email']; ?>" disabled>
                                <small style="color: var(--gray-color); font-size: 0.9rem;">Contact support to change email</small>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
                            </div>
                        </div>

                        <div style="text-align: right; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password Section -->
                <div class="profile-section">
                    <h2 class="section-title">Change Password</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group password-input">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group password-input">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="password-strength">
                                    <i class="fas fa-info-circle"></i> Minimum 6 characters
                                </div>
                            </div>
                            
                            <div class="form-group password-input">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 25px;">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Account Settings (Placeholder) -->
                <div class="profile-section">
                    <h2 class="section-title">Account Settings</h2>
                    
                    <div style="display: grid; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: var(--light-color); border-radius: 5px;">
                            <div>
                                <div style="font-weight: 500; margin-bottom: 5px;">Email Notifications</div>
                                <div style="font-size: 0.9rem; color: var(--gray-color);">Receive course updates and announcements</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: var(--light-color); border-radius: 5px;">
                            <div>
                                <div style="font-weight: 500; margin-bottom: 5px;">Newsletter Subscription</div>
                                <div style="font-size: 0.9rem; color: var(--gray-color);">Get weekly learning tips and resources</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <button class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                        <small style="display: block; color: var(--gray-color); margin-top: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. All your data will be permanently deleted.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }
        
        // Password confirmation check
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.style.borderColor = '#E74C3C';
                confirmPassword.parentElement.querySelector('.password-toggle').style.color = '#E74C3C';
            } else {
                confirmPassword.style.borderColor = '#27AE60';
                confirmPassword.parentElement.querySelector('.password-toggle').style.color = '#27AE60';
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        // Photo upload preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF).');
                    this.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size too large. Please select an image smaller than 5MB.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update both the main profile image and sidebar image
                    document.querySelectorAll('.profile-img').forEach(img => {
                        img.src = e.target.result;
                    });
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Confirm account deletion
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                alert('Account deletion feature will be available soon.');
            }
        }
        
        // Toggle switch styling
        const style = document.createElement('style');
        style.innerHTML = `
            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }
            
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
            }
            
            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }
            
            input:checked + .slider {
                background-color: var(--accent-color);
            }
            
            input:checked + .slider:before {
                transform: translateX(26px);
            }
            
            .slider.round {
                border-radius: 34px;
            }
            
            .slider.round:before {
                border-radius: 50%;
            }
        `;
        document.head.appendChild(style);
    </script>