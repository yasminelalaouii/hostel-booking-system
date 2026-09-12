<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password";
    } else {
        $result = login($email, $password);
        
        if ($result['success']) {
            setFlash('success', 'Login successful!');
            
            if ($result['user_type'] === 'admin') {
                redirect('admin/admin_dashboard.php');
            } else {
                redirect('dashboard.php');
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-hotel"></i> <?php echo APP_NAME; ?></h1>
                <p>Welcome back! Please login to your account</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-1"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php echo displayFlash(); ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo $_POST['email'] ?? ''; ?>" required 
                               placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required placeholder="Enter your password">
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="auth-links">
                    <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                    <p><a href="forgot-password.php">Forgot Password?</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
                
                <!-- Demo Credentials -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="loader"></span> Logging in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>