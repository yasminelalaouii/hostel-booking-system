<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Book Your Stay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="home-container">
        <!-- Navigation -->
        <!-- Navigation -->
 <nav class="home-navbar">
    <a href="index.php" class="nav-brand">
        <i class="fas fa-hotel"></i> <?php echo APP_NAME; ?>
    </a>
    
    <div class="nav-links">
        <a href="index.php" class="nav-link">Home</a>
        <a href="#rooms" class="nav-link">Rooms</a>
        <a href="#about" class="nav-link">About</a>
        <a href="#contact" class="nav-link">Contact</a>
        
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-user"></i> Dashboard
            </a>
            <a href="logout.php" class="nav-link" style="background-color: var(--danger-color); color: white; padding: 8px 15px; border-radius: 5px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-link">Login</a>
            <a href="register.php" class="nav-link btn btn-primary">
                <i class="fas fa-user-plus"></i> Register
            </a>
        <?php endif; ?>
    </div>
</nav>
        
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Welcome to <?php echo APP_NAME; ?></h1>
                <p>Find your perfect stay with our comfortable and affordable hostel rooms. 
                   Book now and experience home away from home.</p>
                
                <div class="hero-buttons">
                    <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'register.php'; ?>" class="btn">
                        <i class="fas fa-calendar-check"></i> Book Now
                    </a>
                    <a href="#rooms" class="btn btn-outline-light">
                        <i class="fas fa-eye"></i> View Rooms
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Rooms Section -->
         <!-- Rooms Section -->
<section id="rooms" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Our Rooms</h2>
        <div class="row">
            <?php
            // Fetch available rooms - Fixed SQL query with CLOB handling
            $sql = "SELECT room_id, room_number, room_type, description, capacity, price_per_night, 
                           DBMS_LOB.SUBSTR(amenities, 2000, 1) as amenities_text
                    FROM rooms 
                    WHERE status = 'available' 
                    ORDER BY price_per_night";
            $rooms = fetchAll($sql);
            
            if ($rooms && count($rooms) > 0):
                foreach ($rooms as $room):
                    // Safe handling of CLOB data
                    $room_number = htmlspecialchars($room['ROOM_NUMBER'] ?? 'N/A');
                    $room_type = htmlspecialchars($room['ROOM_TYPE'] ?? 'Standard');
                    $description = htmlspecialchars($room['DESCRIPTION'] ?? 'Comfortable accommodation');
                    $capacity = $room['CAPACITY'] ?? 1;
                    $price = number_format($room['PRICE_PER_NIGHT'] ?? 0, 2);
                    
                    // Handle CLOB for amenities
                    $amenities_text = 'Standard amenities';
                    if (isset($room['AMENITIES_TEXT']) && $room['AMENITIES_TEXT'] !== null) {
                        if (is_object($room['AMENITIES_TEXT'])) {
                            // It's an OCILob object
                            $amenities_text = $room['AMENITIES_TEXT']->load() ?? 'Standard amenities';
                        } else {
                            $amenities_text = $room['AMENITIES_TEXT'];
                        }
                    }
                    $short_amenities = strlen($amenities_text) > 50 ? substr($amenities_text, 0, 50) . '...' : $amenities_text;
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Room <?php echo $room_number; ?></h5>
                        <h6 class="card-subtitle"><?php echo $room_type; ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo $description; ?></p>
                        <ul class="list-unstyled mb-3">
                            <li><i class="fas fa-users text-primary"></i> <strong>Capacity:</strong> <?php echo $capacity; ?> person(s)</li>
                            <li><i class="fas fa-wifi text-primary"></i> <strong>Amenities:</strong> <?php echo htmlspecialchars($short_amenities); ?></li>
                            <li><i class="fas fa-tag text-primary"></i> <strong>Price:</strong> $<?php echo $price; ?>/night</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="<?php echo isLoggedIn() ? 'dashboard.php?book=' . $room['ROOM_ID'] : 'register.php'; ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-book"></i> Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endforeach; 
            else: 
            ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>No rooms available at the moment</h4>
                    <p>Please check back later or contact us for availability.</p>
                    <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus"></i> Add Rooms in Admin Panel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (isLoggedIn() && $rooms && count($rooms) > 0): ?>
        <div class="text-center mt-4">
            <a href="dashboard.php?page=rooms" class="btn btn-outline-primary">
                <i class="fas fa-bed"></i> View All Rooms
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
        <!-- About Section -->
 <section id="about" class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                         alt="Modern Hostel Interior" class="img-fluid rounded shadow">
                    <div class="position-absolute top-0 start-0 bg-primary text-white p-3 rounded-end">
                        <h4 class="mb-0">Since 2023</h4>
                        <p class="mb-0">Trusted Accommodation</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ps-lg-5">
                    <span class="text-primary fw-bold">ABOUT US</span>
                    <h2 class="mb-4">Experience Premium Comfort at Our Hostel</h2>
                    <p class="lead mb-4">
                        We provide exceptional accommodation solutions for students, professionals, 
                        and travelers seeking comfort, security, and community.
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-shield-alt fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>24/7 Security</h5>
                                    <p class="text-muted mb-0">Advanced CCTV surveillance & security personnel</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-wifi fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>High-Speed WiFi</h5>
                                    <p class="text-muted mb-0">Fiber optic internet throughout the premises</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-utensils fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Common Kitchen</h5>
                                    <p class="text-muted mb-0">Fully equipped kitchen with modern appliances</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-tshirt fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Laundry Service</h5>
                                    <p class="text-muted mb-0">Self-service laundry facilities available</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-book-reader fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Study Rooms</h5>
                                    <p class="text-muted mb-0">Quiet study areas with individual workspaces</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="fas fa-concierge-bell fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>24/7 Reception</h5>
                                    <p class="text-muted mb-0">Round-the-clock assistance and support</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="#rooms" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-eye me-2"></i>View Our Rooms
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
        
        <!-- Contact Section -->
        <section id="contact" class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5">Contact Us</h2>
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
                        <p>123 Hostel Street, City, Country</p>
                        
                        <h4><i class="fas fa-phone"></i> Phone</h4>
                        <p>+1 (234) 567-8900</p>
                        
                        <h4><i class="fas fa-envelope"></i> Email</h4>
                        <p>info@hostel.com</p>
                    </div>
                    <div class="col-md-6">
                        <form>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Your Email" required>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <h4><?php echo APP_NAME; ?></h4>
                <p>Your comfort is our priority</p>
                
                <div class="footer-links">
                    <a href="index.php">Home</a>
                    <a href="#rooms">Rooms</a>
                    <a href="#about">About</a>
                    <a href="#contact">Contact</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/">Admin Panel</a>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>