<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Get current user
$user = currentUser();

// Get user bookings 
$sql = "SELECT b.booking_id, b.check_in_date, b.check_out_date, b.total_nights, 
               b.total_price, b.booking_status, b.payment_status, b.created_at,
               r.room_number, r.room_type, r.price_per_night
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC
        FETCH FIRST 3 ROWS ONLY";
$user_bookings = fetchAll($sql, [':user_id' => $_SESSION['user_id']]);

// Get available rooms count
$available_rooms = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'");

// Get upcoming booking
$upcoming_booking = fetchOne("SELECT * FROM bookings 
                              WHERE user_id = :user_id AND booking_status = 'confirmed'
                              AND check_in_date >= SYSDATE
                              ORDER BY check_in_date FETCH FIRST 1 ROWS ONLY", 
                              [':user_id' => $_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-hero {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(52, 152, 219, 0.9));
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        
        .user-avatar-lg {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            color: white;
            margin-right: 20px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid #f0f0f0;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-icon i {
            font-size: 25px;
            color: white;
        }
        
        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .booking-card:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .booking-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-confirmed { background: #e7f7e7; color: #27ae60; }
        .status-pending { background: #fff7e7; color: #f39c12; }
        .status-cancelled { background: #f7e7e7; color: #e74c3c; }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .room-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .room-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
        }
        
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        
        .quick-link {
            padding: 10px 20px;
            background: white;
            border-radius: 50px;
            text-decoration: none;
            color: var(--primary-color);
            border: 2px solid #f0f0f0;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .quick-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
/* Fix flash message display - IMPROVED */
.alert {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 99999 !important;
    min-width: 350px;
    max-width: 500px;
    margin: 0 !important;
    animation: slideInRight 0.3s ease-out;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    border: none;
    border-radius: 10px !important;
    padding: 15px 20px !important;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Auto fade out animation */
.alert.fade.show {
    animation: slideInRight 0.3s ease-out, fadeOut 5s ease-in 3s forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Make sure alerts are visible */
.alert-success {
    background-color: #d1e7dd !important;
    border-left: 4px solid #0f5132 !important;
    color: #0f5132 !important;
}

.alert-danger {
    background-color: #f8d7da !important;
    border-left: 4px solid #842029 !important;
    color: #842029 !important;
}

.alert-warning {
    background-color: #fff3cd !important;
    border-left: 4px solid #856404 !important;
    color: #856404 !important;
}

.alert-info {
    background-color: #d1ecf1 !important;
    border-left: 4px solid #0c5460 !important;
    color: #0c5460 !important;
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
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="booking.php" class="nav-link">Book a Room</a>
            <a href="profile.php" class="nav-link">Profile</a>
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
    <section class="dashboard-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-3">Welcome back, <?php echo htmlspecialchars($user['FULL_NAME']); ?>! </h1>
                    <p class="lead mb-0">Manage your bookings, find available rooms, and enjoy your stay with us.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="user-avatar-lg">
                        <?php echo strtoupper(substr($user['FULL_NAME'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">Hello, <?php echo htmlspecialchars(explode(' ', $user['FULL_NAME'])[0]); ?>!</h3>
                    <p class="text-muted mb-0">
                        <?php if ($user['USER_TYPE'] === 'admin'): ?>
                            <i class="fas fa-crown text-warning"></i> Administrator Account
                        <?php else: ?>
                            <i class="fas fa-user text-primary"></i> Guest Account
                        <?php endif; ?>
                                                <?php if (!empty($user['CREATED_AT']) && $user['CREATED_AT'] != '1970-01-01'): ?>
                        • Member since <?php 
                            // Handle Oracle date format
                            $created_at = $user['CREATED_AT'];
                            if (is_object($created_at)) {
                                $created_at = $created_at->load();
                            }
                            if ($created_at && strtotime($created_at) > 0) {
                                echo date('M Y', strtotime($created_at));
                            } else {
                                echo 'Recently';
                            }
                        ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links mt-4">
                <a href="index.php#rooms" class="quick-link">
                    <i class="fas fa-bed"></i> Browse Rooms
                </a>
                <a href="booking.php" class="quick-link">
                    <i class="fas fa-plus"></i> New Booking
                </a>
                <a href="#my-bookings" class="quick-link">
                    <i class="fas fa-calendar"></i> My Bookings
                </a>
                <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                <?php endif; ?>
                <a href="logout.php" class="quick-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3><?php echo count($user_bookings); ?></h3>
                <p class="text-muted mb-0">Total Bookings</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <h3><?php echo $available_rooms['COUNT'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Rooms Available</p>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                 <i class="fas fa-money-bill-wave"></i>
              </div>
            <h3>
           <?php
            // Only count confirmed bookings with paid status
             $sql = "SELECT COALESCE(SUM(total_price), 0) as total_spent 
                FROM bookings 
                WHERE user_id = :user_id 
                AND booking_status = 'confirmed' 
                AND payment_status = 'paid'";
        $result = fetchOne($sql, [':user_id' => $_SESSION['user_id']]);
        echo '$' . number_format($result['TOTAL_SPENT'] ?? 0, 2);
        ?>
        </h3>
        <p class="text-muted mb-0">Total Spent</p>
         </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>
                    <?php 
                    $active_bookings = array_filter($user_bookings, function($b) {
                        return $b['BOOKING_STATUS'] === 'confirmed';
                    });
                    echo count($active_bookings);
                    ?>
                </h3>
                <p class="text-muted mb-0">Active Stays</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="booking.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus"></i> Book New Room
            </a>
            <a href="index.php#rooms" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-search"></i> Browse Available Rooms
            </a>
            <a href="#my-bookings" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-history"></i> View Booking History
            </a>
            <a href="profile.php" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-user-cog"></i> Account Settings
            </a>
        </div>
        
        <!-- Upcoming Booking -->
        <?php if ($upcoming_booking): 
            $room_info = fetchOne("SELECT room_number, room_type FROM rooms WHERE room_id = :room_id", 
                                  [':room_id' => $upcoming_booking['ROOM_ID']]);
        ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-calendar-alt fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Upcoming Stay! 🎉</h5>
                    <p class="mb-0">
                        You have a booking for <strong>Room <?php echo $room_info['ROOM_NUMBER']; ?></strong> 
                        from <?php echo date('M d, Y', strtotime($upcoming_booking['CHECK_IN_DATE'])); ?> 
                        to <?php echo date('M d, Y', strtotime($upcoming_booking['CHECK_OUT_DATE'])); ?>
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Recent Bookings -->
        <div id="my-bookings" class="mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar me-2"></i>Recent Bookings</h2>
                <?php if (!empty($user_bookings)): ?>
                <a href="my-bookings.php" class="btn btn-outline-primary">View All</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($user_bookings)): ?>
                <div class="row">
                    <?php foreach ($user_bookings as $booking): ?>
                    <div class="col-md-4 mb-4">
                        <div class="booking-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">Room <?php echo $booking['ROOM_NUMBER']; ?></h5>
                                    <p class="text-muted mb-0"><?php echo $booking['ROOM_TYPE']; ?></p>
                                </div>
                                <span class="booking-status status-<?php echo strtolower($booking['BOOKING_STATUS']); ?>">
                                    <?php echo ucfirst($booking['BOOKING_STATUS']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="far fa-calendar-check text-primary me-2"></i>
                                    <?php echo date('M d, Y', strtotime($booking['CHECK_IN_DATE'])); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="far fa-calendar-times text-primary me-2"></i>
                                    <?php echo date('M d, Y', strtotime($booking['CHECK_OUT_DATE'])); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-moon text-primary me-2"></i>
                                    <?php echo $booking['TOTAL_NIGHTS']; ?> nights
                                </p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">
                                    $<?php echo number_format($booking['TOTAL_PRICE'], 2); ?>
                                </span>
                                <span class="badge bg-<?php echo $booking['PAYMENT_STATUS'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($booking['PAYMENT_STATUS']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No bookings yet</h4>
                    <p class="mb-4">Start your journey with us by booking your first room!</p>
                    <a href="booking.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Make Your First Booking
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="room-card">
                    <div class="room-image">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="p-3">
                        <h5>Find Your Perfect Room</h5>
                        <p class="text-muted mb-3">Browse our selection of comfortable rooms</p>
                        <a href="index.php#rooms" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search"></i> Explore Rooms
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="room-card">
                    <div class="room-image">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="p-3">
                        <h5>Need Help?</h5>
                        <p class="text-muted mb-3">Check our FAQ or contact support</p>
                        <a href="index.php#contact" class="btn btn-outline-primary w-100">
                            <i class="fas fa-headset"></i> Get Help
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="room-card">
                    <div class="room-image">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="p-3">
                        <h5>Leave a Review</h5>
                        <p class="text-muted mb-3">Share your experience with us</p>
                        <a href="index.php#contact" class="btn btn-outline-primary w-100">
                            <i class="fas fa-edit"></i> Write Review
                        </a>
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
                <a href="index.php">Home</a>
                <a href="index.php#rooms">Rooms</a>
                <a href="index.php#about">About</a>
                <a href="index.php#contact">Contact</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Admin</a>
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
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                // Set timeout to dismiss after 5 seconds
                setTimeout(() => {
                    if (alert.classList.contains('show')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
                
                // Also dismiss when clicking anywhere on page (except the alert itself)
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.alert')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>