<?php
require_once '../includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    // Check admin credentials
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_logged_in'] = true;
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password';
    }
}

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TMM Academy</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .admin-login-header {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .admin-login-header h2 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }

        .admin-login-body {
            padding: 40px;
        }

        .admin-form-group {
            margin-bottom: 25px;
        }

        .admin-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .admin-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .admin-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .admin-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .admin-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="admin-login-container">
        <div class="admin-login-header">
            <h2><i class="fas fa-cog"></i> Admin Panel</h2>
            <p>Login to manage TMM Academy</p>
        </div>

        <div class="admin-login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="admin-form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" class="admin-input" required
                           placeholder="Enter admin username">
                </div>

                <div class="admin-form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="admin-input" required
                           placeholder="Enter admin password">
                </div>

                <button type="submit" class="admin-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
                </button>
            </form>

            <div style="text-align: center; margin-top: 30px;">
                <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to TMM Academy
                </a>
            </div>
        </div>
    </div>
</body>
</html>
