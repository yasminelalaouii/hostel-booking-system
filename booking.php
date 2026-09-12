<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

$errors = [];
$success = false;

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get room details if room_id is provided
$room = null;
if (isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
    $room_id = $_GET['room_id'];
    $sql = "SELECT room_id, room_number, room_type, description, capacity, price_per_night, 
                   DBMS_LOB.SUBSTR(amenities, 2000, 1) as amenities_text,
                   status
            FROM rooms WHERE room_id = :room_id";
    $room = fetchOne($sql, [':room_id' => $room_id]);
    
    if (!$room) {
        setFlash('error', 'Room not found.');
        redirect('dashboard.php');
    }
    
    if ($room['STATUS'] !== 'available') {
        setFlash('error', 'This room is not available for booking.');
        redirect('index.php#rooms');
    }
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? '';
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Debug
    error_log("Booking attempt - User: $user_id, Room: $room_id, Check-in: $check_in, Check-out: $check_out");
    
    // Validation
    if (empty($room_id)) {
        $errors[] = "Please select a room";
    }
    
    if (empty($check_in)) {
        $errors[] = "Check-in date is required";
    } elseif (strtotime($check_in) < strtotime('today')) {
        $errors[] = "Check-in date cannot be in the past";
    }
    
    if (empty($check_out)) {
        $errors[] = "Check-out date is required";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $errors[] = "Check-out date must be after check-in date";
    }
    
    // Calculate total nights and price
    if (empty($errors)) {
        // Get room price and check availability
        $room_sql = "SELECT price_per_night, status FROM rooms WHERE room_id = :room_id";
        $room_data = fetchOne($room_sql, [':room_id' => $room_id]);
        
        if (!$room_data) {
            $errors[] = "Invalid room selected";
        } elseif ($room_data['STATUS'] !== 'available') {
            $errors[] = "This room is no longer available";
        } else {
            $price_per_night = $room_data['PRICE_PER_NIGHT'];
            $check_in_date = new DateTime($check_in);
            $check_out_date = new DateTime($check_out);
            $interval = $check_in_date->diff($check_out_date);
            $total_nights = $interval->days;
            $total_price = $price_per_night * $total_nights;
            
            if ($total_nights < 1) {
                $errors[] = "Minimum stay is 1 night";
            }
            
            // Check if room is available for selected dates
            $availability_sql = "SELECT COUNT(*) as count FROM bookings 
                                WHERE room_id = :room_id 
                                AND booking_status IN ('confirmed', 'pending')
                                AND NOT (check_out_date <= TO_DATE(:check_in, 'YYYY-MM-DD') 
                                        OR check_in_date >= TO_DATE(:check_out, 'YYYY-MM-DD'))";
            $availability = fetchOne($availability_sql, [
                ':room_id' => $room_id,
                ':check_in' => $check_in,
                ':check_out' => $check_out
            ]);
            
            if ($availability && $availability['COUNT'] > 0) {
                $errors[] = "This room is not available for the selected dates";
            }
        }
    }
    
    // Create booking if no errors
    if (empty($errors)) {
        $conn = getDBConnection();
        
        try {
            // Start transaction
            $begin_sql = "BEGIN";
            $stid = oci_parse($conn, $begin_sql);
            oci_execute($stid);
            
            // Insert booking
            $sql = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, 
                                           total_nights, total_price, booking_status, notes)
                    VALUES (:user_id, :room_id, TO_DATE(:check_in, 'YYYY-MM-DD'), 
                            TO_DATE(:check_out, 'YYYY-MM-DD'), :total_nights, :total_price, 
                            'pending', :notes)";
            
            $stid = oci_parse($conn, $sql);
            
            // Bind parameters
            oci_bind_by_name($stid, ':user_id', $user_id);
            oci_bind_by_name($stid, ':room_id', $room_id);
            oci_bind_by_name($stid, ':check_in', $check_in);
            oci_bind_by_name($stid, ':check_out', $check_out);
            oci_bind_by_name($stid, ':total_nights', $total_nights);
            oci_bind_by_name($stid, ':total_price', $total_price);
            oci_bind_by_name($stid, ':notes', $notes);
            
            if (oci_execute($stid)) {
                // Get the booking ID
                $booking_id_sql = "SELECT booking_id_seq.CURRVAL FROM dual";
                $stid2 = oci_parse($conn, $booking_id_sql);
                oci_execute($stid2);
                $row = oci_fetch_array($stid2, OCI_ASSOC);
                $booking_id = $row['CURRVAL'];
                
                // Update room status to occupied
                $update_sql = "UPDATE rooms SET status = 'occupied', updated_at = SYSTIMESTAMP 
                               WHERE room_id = :room_id";
                $stid3 = oci_parse($conn, $update_sql);
                oci_bind_by_name($stid3, ':room_id', $room_id);
                oci_execute($stid3);
                
                // Commit transaction
                $commit_sql = "COMMIT";
                $stid4 = oci_parse($conn, $commit_sql);
                oci_execute($stid4);
                
                // Log activity
                logActivity('BOOKING_CREATED', 'BOOKINGS', $booking_id, "Booking created for room $room_id");
                
                $success = true;
                setFlash('success', '🎉 Booking request submitted successfully! We will confirm your booking shortly.');
                redirect('dashboard.php');
            } else {
                $e = oci_error($stid);
                $errors[] = "Failed to create booking: " . $e['message'];
                
                // Rollback on error
                $rollback_sql = "ROLLBACK";
                $stid5 = oci_parse($conn, $rollback_sql);
                oci_execute($stid5);
            }
            
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get all available rooms for dropdown
$available_rooms = fetchAll("SELECT room_id, room_number, room_type, price_per_night FROM rooms 
                             WHERE status = 'available' ORDER BY room_number");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Add custom alert styling */
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border: none;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert-dismissible .btn-close {
            padding: 1rem;
        }
        
        .booking-hero {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(52, 152, 219, 0.9));
            color: white;
            padding: 80px 0 40px;
            position: relative;
            overflow: hidden;
        }
        
        .booking-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .booking-container {
            background: white;
            border-radius: 15px;
            margin-top: -40px;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .room-selection {
            background: #f8f9fa;
            padding: 40px;
            border-right: 1px solid #e1e5eb;
        }
        
        .room-option {
            background: white;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .room-option:hover {
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-option.selected {
            border-color: var(--secondary-color);
            background: rgba(52, 152, 219, 0.05);
        }
        
        .booking-form {
            padding: 40px;
        }
        
        .price-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .room-selection {
                border-right: none;
                border-bottom: 1px solid #e1e5eb;
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
            <a href="booking.php" class="nav-link active">Book a Room</a>
            <a href="profile.php" class="nav-link">Profile</a>
            <?php if (isAdmin()): ?>
            <a href="admin/dashboard.php" class="nav-link">
                <i class="fas fa-cog"></i> Admin
            </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-link btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="booking-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-3">Book Your Stay 🏨</h1>
                    <p class="lead mb-0">Choose your perfect room and select your dates</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php#rooms" class="btn btn-outline-light">
                        <i class="fas fa-bed"></i> Browse All Rooms
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Display Flash Messages -->
    <?php 
    $flash = getFlash();
    if ($flash): ?>
    <div class="custom-alert alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Main Booking -->
    <div class="container">
        <div class="booking-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger m-4">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="bookingForm">
                <div class="row">
                    <!-- Room Selection -->
                    <div class="col-lg-5">
                        <div class="room-selection">
                            <h3 class="mb-4">Choose Your Room</h3>
                            
                            <?php if ($room): ?>
                                <!-- Pre-selected room -->
                                <div class="room-option selected" data-room-id="<?php echo $room['ROOM_ID']; ?>" data-room-price="<?php echo $room['PRICE_PER_NIGHT']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">Room <?php echo htmlspecialchars($room['ROOM_NUMBER']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($room['ROOM_TYPE']); ?></p>
                                        </div>
                                        <span class="badge bg-success">Available</span>
                                    </div>
                                    <p class="mb-2">
                                        <i class="fas fa-users"></i> Capacity: <?php echo $room['CAPACITY']; ?> persons
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-tag"></i> $<?php echo number_format($room['PRICE_PER_NIGHT'], 2); ?>/night
                                    </p>
                                    <input type="hidden" name="room_id" value="<?php echo $room['ROOM_ID']; ?>">
                                </div>
                            <?php elseif (!empty($available_rooms)): ?>
                                <!-- Room selection -->
                                <?php foreach ($available_rooms as $index => $room_opt): ?>
                                <div class="room-option <?php echo $index === 0 ? 'selected' : ''; ?>" 
                                     data-room-id="<?php echo $room_opt['ROOM_ID']; ?>"
                                     data-room-price="<?php echo $room_opt['PRICE_PER_NIGHT']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">Room <?php echo htmlspecialchars($room_opt['ROOM_NUMBER']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($room_opt['ROOM_TYPE']); ?></p>
                                        </div>
                                        <span class="badge bg-success">Available</span>
                                    </div>
                                    <p class="mb-2">
                                        <i class="fas fa-users"></i> Capacity: <?php echo $room_opt['CAPACITY'] ?? 2; ?> persons
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-tag"></i> $<?php echo number_format($room_opt['PRICE_PER_NIGHT'], 2); ?>/night
                                    </p>
                                </div>
                                <?php endforeach; ?>
                                <input type="hidden" name="room_id" id="selectedRoomId" value="<?php echo $available_rooms[0]['ROOM_ID'] ?? ''; ?>">
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-bed fa-lg mb-3"></i>
                                    <h5>No rooms available</h5>
                                    <p class="mb-0">Please check back later or contact us for availability.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Booking Form -->
                    <div class="col-lg-7">
                        <div class="booking-form">
                            <h3 class="mb-4">Select Dates</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label"><i class="fas fa-calendar-check"></i> Check-in Date *</label>
                                    <input type="date" name="check_in" id="checkIn" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label class="form-label"><i class="fas fa-calendar-times"></i> Check-out Date *</label>
                                    <input type="date" name="check_out" id="checkOut" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-sticky-note"></i> Special Requests</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Any special requests or requirements..."></textarea>
                            </div>
                            
                            <!-- Price Summary -->
                            <div class="price-summary">
                                <h4 class="mb-3">Price Summary</h4>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Price per night:</span>
                                    <span id="pricePerNight">$0.00</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Number of nights:</span>
                                    <span id="nightsCount">0</span>
                                </div>
                                
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total Price:</strong>
                                    <strong id="totalPrice" class="text-primary">$0.00</strong>
                                </div>
                                
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    Prices are in USD. Your booking will be confirmed within 24 hours.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-lock"></i> Confirm Booking
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('checkIn').min = today;
        
        // Room selection
        document.querySelectorAll('.room-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.room-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input
                const roomId = this.getAttribute('data-room-id');
                const roomPrice = this.getAttribute('data-room-price');
                document.getElementById('selectedRoomId').value = roomId;
                document.querySelector('input[name="room_id"]').value = roomId;
                
                // Update price calculation
                calculatePrice();
            });
        });
        
        // Update check-out minimum based on check-in
        document.getElementById('checkIn').addEventListener('change', function() {
            const checkInDate = this.value;
            const checkOutField = document.getElementById('checkOut');
            checkOutField.min = checkInDate;
            
            if (checkOutField.value && checkOutField.value < checkInDate) {
                checkOutField.value = '';
            }
            
            calculatePrice();
        });
        
        document.getElementById('checkOut').addEventListener('change', calculatePrice);
        
        // Calculate price function
        function calculatePrice() {
            const checkIn = document.getElementById('checkIn').value;
            const checkOut = document.getElementById('checkOut').value;
            const selectedRoom = document.querySelector('.room-option.selected');
            
            if (!selectedRoom) return;
            
            const roomPrice = parseFloat(selectedRoom.getAttribute('data-room-price') || 0);
            
            if (checkIn && checkOut && roomPrice > 0) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);
                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (nights > 0) {
                    const totalPrice = roomPrice * nights;
                    
                    // Update price display
                    document.getElementById('pricePerNight').textContent = `$${roomPrice.toFixed(2)}`;
                    document.getElementById('nightsCount').textContent = nights;
                    document.getElementById('totalPrice').textContent = `$${totalPrice.toFixed(2)}`;
                    
                    // Update submit button
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.innerHTML = `<i class="fas fa-lock"></i> Book Now - $${totalPrice.toFixed(2)}`;
                    
                    return;
                }
            }
            
            // Reset if invalid
            document.getElementById('pricePerNight').textContent = '$0.00';
            document.getElementById('nightsCount').textContent = '0';
            document.getElementById('totalPrice').textContent = '$0.00';
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = `<i class="fas fa-lock"></i> Confirm Booking`;
        }
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const checkIn = document.getElementById('checkIn').value;
            const checkOut = document.getElementById('checkOut').value;
            const roomId = document.querySelector('input[name="room_id"]').value;
            
            if (!roomId) {
                e.preventDefault();
                alert('Please select a room');
                return;
            }
            
            if (!checkIn || !checkOut) {
                e.preventDefault();
                alert('Please select both check-in and check-out dates');
                return;
            }
            
            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (checkInDate < today) {
                e.preventDefault();
                alert('Check-in date cannot be in the past');
                return;
            }
            
            if (checkOutDate <= checkInDate) {
                e.preventDefault();
                alert('Check-out date must be after check-in date');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('#submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
        });
        
        // Initialize price calculation
        calculatePrice();
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.custom-alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>