<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect('user/my-courses.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $conn = getDBConnection();
        
        // Check if email or username already exists
        $check_sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error = 'Email or username already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
            
            if ($insert_stmt->execute()) {
                // Get the newly created user ID
                $user_id = $conn->insert_id;

                // Set session variables (auto-login)
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['full_name'] = $full_name;

                // Update last login timestamp
                $update_sql = "UPDATE users SET updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();

                // Redirect to dashboard
                redirect('user/my-courses.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TMM Academy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-color);
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: var(--gray-color);
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
            color: var(--gray-color);
        }
        
        .terms-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .terms-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2>Create Your Account</h2>
                <p>Join thousands of learners at TMM Academy</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <br><a href="login.php" style="color: #155724; font-weight: bold;">Click here to login</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="John Doe">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="johndoe">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="john@example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="At least 6 characters">
                        <div class="password-strength">
                            <i class="fas fa-info-circle"></i> Minimum 6 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               placeholder="Re-enter your password">
                    </div>
                </div>
                
                <div class="terms-check">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms" style="color: var(--gray-color); font-size: 0.9rem;">
                        I agree to the <a href="#" style="color: var(--secondary-color);">Terms & Conditions</a> 
                        and <a href="#" style="color: var(--secondary-color);">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 25px; color: var(--gray-color);">
                <p>Already have an account? <a href="login.php" style="color: var(--secondary-color);">Login here</a></p>
                <p><a href="index.php" style="color: var(--gray-color);"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation check
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.style.borderColor = '#E74C3C';
            } else {
                confirmPassword.style.borderColor = '#27AE60';
            }
        }
        
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    </script>
</body>
</html>