<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = "Valid email address is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $data = [
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone
        ];
        
        $result = registerUser($data);
        
        if ($result['success']) {
            setFlash('success', $result['message']);
            redirect('login.php');
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
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-hotel"></i> <?php echo APP_NAME; ?></h1>
                <p>Create your account</p>
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
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label for="full_name"><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo $_POST['full_name'] ?? ''; ?>" required 
                               placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo $_POST['email'] ?? ''; ?>" required 
                               placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo $_POST['phone'] ?? ''; ?>" 
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required placeholder="Enter password (min. 6 characters)">
                        <small class="text-muted">Must be at least 6 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required placeholder="Confirm your password">
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By registering for an account, you agree to these Terms and Conditions.</p>
                    
                    <h6>2. User Account</h6>
                    <p>You are responsible for maintaining the confidentiality of your account and password.</p>
                    
                    <h6>3. Booking Policy</h6>
                    <p>Room bookings are subject to availability and confirmation by the hostel administration.</p>
                    
                    <h6>4. Cancellation Policy</h6>
                    <p>Cancellations must be made at least 24 hours before check-in time.</p>
                    
                    <h6>5. Privacy</h6>
                    <p>Your personal information will be protected and not shared with third parties.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            
            let isValid = true;
            
            // Password validation
            if (password.length < 6) {
                showError('password', 'Password must be at least 6 characters');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                showError('confirm_password', 'Passwords do not match');
                isValid = false;
            }
            
            // Terms validation
            if (!terms) {
                showError('terms', 'You must accept the Terms & Conditions');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.classList.add('is-invalid');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            
            field.parentNode.appendChild(errorDiv);
        }
        
        // Real-time password match validation
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        function validatePasswordMatch() {
            if (passwordField.value && confirmPasswordField.value) {
                if (passwordField.value !== confirmPasswordField.value) {
                    confirmPasswordField.classList.add('is-invalid');
                } else {
                    confirmPasswordField.classList.remove('is-invalid');
                }
            }
        }
        
        passwordField.addEventListener('input', validatePasswordMatch);
        confirmPasswordField.addEventListener('input', validatePasswordMatch);
    </script>
</body>
</html>