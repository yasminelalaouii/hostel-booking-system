<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Get user bookings
$sql = "SELECT b.booking_id, b.check_in_date, b.check_out_date, b.total_nights, 
               b.total_price, b.booking_status, b.payment_status, b.created_at,
               r.room_number, r.room_type, r.price_per_night
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC";
$bookings = fetchAll($sql, [':user_id' => $_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4"><i class="fas fa-calendar-alt"></i> My Bookings</h1>
        
        <?php echo displayFlash(); ?>
        
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't made any bookings yet.
                <a href="booking.php" class="alert-link">Book your first room!</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Booking #<?php echo $booking['BOOKING_ID']; ?></h5>
                            <span class="badge bg-<?php 
                                switch($booking['BOOKING_STATUS']) {
                                    case 'confirmed': echo 'success'; break;
                                    case 'pending': echo 'warning'; break;
                                    case 'cancelled': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo ucfirst($booking['BOOKING_STATUS']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">
                                Room <?php echo $booking['ROOM_NUMBER']; ?> - <?php echo $booking['ROOM_TYPE']; ?>
                            </h6>
                            
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="far fa-calendar-check text-primary me-2"></i>
                                    Check-in: <?php echo date('M d, Y', strtotime($booking['CHECK_IN_DATE'])); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="far fa-calendar-times text-primary me-2"></i>
                                    Check-out: <?php echo date('M d, Y', strtotime($booking['CHECK_OUT_DATE'])); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-moon text-primary me-2"></i>
                                    <?php echo $booking['TOTAL_NIGHTS']; ?> nights
                                </p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="text-primary mb-0">$<?php echo number_format($booking['TOTAL_PRICE'], 2); ?></h4>
                                    <small class="text-muted">
                                        Payment: 
                                        <span class="badge bg-<?php echo $booking['PAYMENT_STATUS'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($booking['PAYMENT_STATUS']); ?>
                                        </span>
                                    </small>
                                </div>
                                <div>
                                    <small class="text-muted">
                                        Booked on: <?php echo date('M d, Y', strtotime($booking['CREATED_AT'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="booking.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Booking
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>