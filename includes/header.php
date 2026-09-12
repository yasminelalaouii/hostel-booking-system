<?php
if (!isset($no_header)): // Allow pages to disable header
?>
<?php if (isAdmin()): ?>
<li class="nav-item">
    <a class="nav-link" href="admin/admin_dashboard.php">
        <i class="fas fa-cog"></i> Admin Panel
    </a>
</li>
<?php endif; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-hotel"></i> <?php echo APP_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="booking.php">Book a Room</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                       data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a></li>
                        <li><a class="dropdown-item" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a></li>
                        <?php if (isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="admin/dashboard.php">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light" href="register.php">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>