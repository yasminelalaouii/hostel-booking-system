<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

$user = currentUser();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // If changing password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif ($current_password !== $user['PASSWORD_HASH'] && !verifyPassword($current_password, $user['PASSWORD_HASH'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        // Update user
        if (!empty($new_password)) {
            $sql = "UPDATE users SET full_name = :full_name, phone_number = :phone, 
                    password_hash = :password_hash WHERE user_id = :user_id";
            $params = [
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':password_hash' => hashPassword($new_password),
                ':user_id' => $_SESSION['user_id']
            ];
        } else {
            $sql = "UPDATE users SET full_name = :full_name, phone_number = :phone 
                    WHERE user_id = :user_id";
            $params = [
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':user_id' => $_SESSION['user_id']
            ];
        }
        
        if (execute($sql, $params)) {
            // Update session
            $_SESSION['user_name'] = $full_name;
            
            $success = true;
            setFlash('success', 'Profile updated successfully! 🎉');
            redirect('dashboard.php');
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Get user statistics
$stats_sql = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(total_price) as total_spent
    FROM bookings WHERE user_id = :user_id";
$user_stats = fetchOne($stats_sql, [':user_id' => $_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(52, 152, 219, 0.9));
            color: white;
            padding: 80px 0 40px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .profile-container {
            background: white;
            border-radius: 15px;
            margin-top: -40px;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            height: 100%;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 5px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .profile-avatar-text {
            font-size: 60px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .profile-main {
            padding: 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 30px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: white;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 10;
        }
        
        .input-group-custom input {
            padding-left: 45px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            height: 50px;
            transition: all 0.3s;
        }
        
        .input-group-custom input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .tag {
            display: inline-block;
            padding: 5px 15px;
            background: #e7f7e7;
            color: #27ae60;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 5px;
        }
        
        @media (max-width: 768px) {
            .profile-sidebar {
                padding: 30px;
            }
            
            .profile-main {
                padding: 30px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="home-navbar">
        <a href="index.php" class="nav-brand">
            <i class="fas fa-hotel"></i> <?php echo APP_NAME; ?>
        </a>
        
        <div class="nav-links">
            <a href="index.php" class="nav-link">Home</a>
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="booking.php" class="nav-link">Book a Room</a>
            <a href="profile.php" class="nav-link active">Profile</a>
            <?php if (isAdmin()): ?>
            <a href="admin/admin_dashboard.php" class="nav-link">
                <i class="fas fa-cog"></i> Admin
            </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-link btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-3">My Profile </h1>
                    <p class="lead mb-0">Manage your account settings and view your statistics</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Profile -->
    <div class="container">
        <div class="profile-container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="profile-sidebar">
                        <div class="profile-avatar">
                            <div class="profile-avatar-text">
                                <?php echo strtoupper(substr($user['FULL_NAME'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <h3 class="text-center mb-3"><?php echo htmlspecialchars($user['FULL_NAME']); ?></h3>
                        <p class="text-center text-light mb-4">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($user['EMAIL']); ?>
                        </p>
                        
                        <?php if ($user['PHONE_NUMBER']): ?>
                        <p class="text-center text-light mb-4">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($user['PHONE_NUMBER']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <span class="tag">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo ucfirst($user['USER_TYPE']); ?> Account
                            </span>
                            <span class="tag">
                                                                <i class="fas fa-calendar me-1"></i>
                                <?php 
                                    // Handle Oracle date format
                                    $created_at = $user['CREATED_AT'];
                                    if (is_object($created_at)) {
                                        $created_at = $created_at->load();
                                    }
                                    if ($created_at && strtotime($created_at) > 0) {
                                        echo 'Member since ' . date('M Y', strtotime($created_at));
                                    } else {
                                        echo 'Active member';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="profile-main">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-1"><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php echo displayFlash(); ?>
                        
                        <!-- User Stats -->
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $user_stats['TOTAL_BOOKINGS'] ?? 0; ?></div>
                                <div class="stat-label">Total Bookings</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $user_stats['CONFIRMED_BOOKINGS'] ?? 0; ?></div>
                                <div class="stat-label">Confirmed</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">$<?php echo number_format($user_stats['TOTAL_SPENT'] ?? 0, 0); ?></div>
                                <div class="stat-label">Total Spent</div>
                            </div>
                        </div>
                        
                        <!-- Profile Form -->
                        <form method="POST" action="">
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user-edit"></i> Personal Information
                                </h3>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['FULL_NAME']); ?>" 
                                           placeholder="Full Name" required>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['EMAIL']); ?>" 
                                           placeholder="Email Address" disabled>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['PHONE_NUMBER'] ?? ''); ?>" 
                                           placeholder="Phone Number">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-lock"></i> Password Settings
                                </h3>
                                <p class="text-muted mb-3">Leave password fields blank if you don't want to change</p>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="current_password" class="form-control" 
                                           placeholder="Current Password">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group-custom">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="new_password" class="form-control" 
                                                   placeholder="New Password">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-custom">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="confirm_password" class="form-control" 
                                                   placeholder="Confirm New Password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="footer-content">
            <h4><?php echo APP_NAME; ?></h4>
            <p>Your comfort is our priority</p>
            
            <div class="footer-links">
                  <a href="index.php#contact">Contact</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
            </div>
            
            <div class="social-links mt-3">
                <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-light me-3"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-lg"></i></a>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength check
        document.querySelector('input[name="new_password"]').addEventListener('input', function() {
            const password = this.value;
            const confirmField = document.querySelector('input[name="confirm_password"]');
            
            if (password.length > 0 && password.length < 6) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (password.length >= 6) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
            
            // Check password match
            if (confirmField.value && password !== confirmField.value) {
                confirmField.classList.add('is-invalid');
                confirmField.classList.remove('is-valid');
            } else if (confirmField.value && password === confirmField.value) {
                confirmField.classList.add('is-valid');
                confirmField.classList.remove('is-invalid');
            }
        });
        
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const password = document.querySelector('input[name="new_password"]').value;
            
            if (this.value && password !== this.value) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value && password === this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    </script>
</body>
</html>