<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin login
if (!isLoggedIn() || !isAdmin()) {
    setFlash('error', 'Access denied. Admin privileges required.');
    redirect('../login.php');
}

$current_user = currentUser();
$action = $_GET['action'] ?? 'dashboard';
$id = $_GET['id'] ?? 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleAdminAction();
}

// Get statistics
$stats = getAdminStats();

// Get recent activities
$activities = fetchAll("SELECT * FROM audit_log ORDER BY created_at DESC FETCH FIRST 10 ROWS ONLY");

// Get pending bookings
$pending_bookings = fetchAll("SELECT b.*, u.full_name, r.room_number 
                              FROM bookings b
                              JOIN users u ON b.user_id = u.user_id
                              JOIN rooms r ON b.room_id = r.room_id
                              WHERE b.booking_status = 'pending'
                              ORDER BY b.created_at DESC");

// Get all users for management
$all_users = fetchAll("SELECT user_id, full_name, email, user_type, created_at, is_active 
                       FROM users ORDER BY created_at DESC");

// Get all rooms
$all_rooms = fetchAll("SELECT r.*, u.full_name as created_by_name 
                       FROM rooms r 
                       LEFT JOIN users u ON r.created_by = u.user_id 
                       ORDER BY r.room_number");

// Get all bookings
$all_bookings = fetchAll("SELECT b.*, u.full_name, r.room_number, r.room_type
                          FROM bookings b
                          JOIN users u ON b.user_id = u.user_id
                          JOIN rooms r ON b.room_id = r.room_id
                          ORDER BY b.created_at DESC");

/**
 * Handle admin actions
 */
function handleAdminAction() {
    global $conn;
    
    // Debug logging
    error_log("Admin Action Received: " . ($_POST['action'] ?? 'none'));
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    $result = false;
    $message = '';
    
    try {
        switch ($action) {
            case 'update_room_status':
                $room_id = $_POST['room_id'];
                $status = $_POST['status'];
                $sql = "UPDATE rooms SET status = :status, updated_at = SYSTIMESTAMP WHERE room_id = :room_id";
                $result = execute($sql, [':status' => $status, ':room_id' => $room_id]);
                $message = 'Room status updated successfully';
                logActivity('ROOM_STATUS_UPDATE', 'ROOMS', $room_id, "Status changed to: $status");
                break;
                
            case 'confirm_booking':
                $booking_id = $_POST['booking_id'];
                $sql = "UPDATE bookings SET booking_status = 'confirmed', updated_at = SYSTIMESTAMP 
                        WHERE booking_id = :booking_id";
                $result = execute($sql, [':booking_id' => $booking_id]);
                
                // Update room status to occupied
                $room_sql = "UPDATE rooms SET status = 'occupied', updated_at = SYSTIMESTAMP 
                            WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = :booking_id)";
                execute($room_sql, [':booking_id' => $booking_id]);
                
                $message = 'Booking confirmed successfully';
                logActivity('BOOKING_CONFIRMED', 'BOOKINGS', $booking_id, 'Booking confirmed');
                break;
                
            case 'cancel_booking':
                $booking_id = $_POST['booking_id'];
                $sql = "UPDATE bookings SET booking_status = 'cancelled', updated_at = SYSTIMESTAMP 
                        WHERE booking_id = :booking_id";
                $result = execute($sql, [':booking_id' => $booking_id]);
                
                // Update room status back to available
                $room_sql = "UPDATE rooms SET status = 'available', updated_at = SYSTIMESTAMP 
                            WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = :booking_id)";
                execute($room_sql, [':booking_id' => $booking_id]);
                
                $message = 'Booking cancelled successfully';
                logActivity('BOOKING_CANCELLED', 'BOOKINGS', $booking_id, 'Booking cancelled');
                break;
                
            case 'update_booking_status':
                $booking_id = $_POST['booking_id'];
                $booking_status = $_POST['booking_status'];
                $payment_status = $_POST['payment_status'] ?? null;
                
                // Update booking status
                $sql = "UPDATE bookings SET booking_status = :booking_status, updated_at = SYSTIMESTAMP ";
                
                if ($payment_status) {
                    $sql .= ", payment_status = :payment_status ";
                }
                
                $sql .= " WHERE booking_id = :booking_id";
                
                $params = [':booking_id' => $booking_id, ':booking_status' => $booking_status];
                if ($payment_status) {
                    $params[':payment_status'] = $payment_status;
                }
                
                $result = execute($sql, $params);
                
                // Update room status based on booking status
                if ($result) {
                    if ($booking_status === 'confirmed') {
                        // Mark room as occupied
                        $room_sql = "UPDATE rooms SET status = 'occupied' 
                                    WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = :booking_id)";
                        execute($room_sql, [':booking_id' => $booking_id]);
                    } elseif ($booking_status === 'cancelled' || $booking_status === 'completed') {
                        // Mark room as available
                        $room_sql = "UPDATE rooms SET status = 'available' 
                                    WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = :booking_id)";
                        execute($room_sql, [':booking_id' => $booking_id]);
                    }
                }
                
                $message = 'Booking status updated successfully';
                logActivity('BOOKING_UPDATED', 'BOOKINGS', $booking_id, 
                           "Status: $booking_status, Payment: $payment_status");
                break;
                
            case 'update_payment_status':
                $booking_id = $_POST['booking_id'];
                $payment_status = $_POST['payment_status'];
                
                $sql = "UPDATE bookings SET payment_status = :payment_status, updated_at = SYSTIMESTAMP 
                        WHERE booking_id = :booking_id";
                $result = execute($sql, [':payment_status' => $payment_status, ':booking_id' => $booking_id]);
                
                // If payment status is 'paid', create a payment record if it doesn't exist
                if ($result && $payment_status === 'paid') {
                    $booking = fetchOne("SELECT total_price FROM bookings WHERE booking_id = :booking_id", 
                                       [':booking_id' => $booking_id]);
                    
                    if ($booking) {
                        // Check if payment already exists
                        $existing_payment = fetchOne("SELECT COUNT(*) as count FROM payments WHERE booking_id = :booking_id", 
                                                   [':booking_id' => $booking_id]);
                        
                        if (!$existing_payment || $existing_payment['COUNT'] == 0) {
                            $payment_sql = "INSERT INTO payments (booking_id, amount_paid, payment_method, status, payment_type) 
                                           VALUES (:booking_id, :amount, 'admin_manual', 'success', 'booking')";
                            execute($payment_sql, [':booking_id' => $booking_id, ':amount' => $booking['TOTAL_PRICE']]);
                        }
                    }
                }
                
                $message = 'Payment status updated successfully';
                logActivity('PAYMENT_STATUS_UPDATED', 'BOOKINGS', $booking_id, "Payment status: $payment_status");
                break;
                
            case 'add_room':
                $room_number = sanitize($_POST['room_number']);
                $room_type = sanitize($_POST['room_type']);
                $description = sanitize($_POST['description']);
                $capacity = $_POST['capacity'];
                $price = $_POST['price_per_night'];
                $amenities = sanitize($_POST['amenities']);
                $status = $_POST['status'];
                
                $sql = "INSERT INTO rooms (room_number, room_type, description, capacity, 
                                          price_per_night, amenities, status, created_by) 
                        VALUES (:room_number, :room_type, :description, :capacity, 
                                :price, :amenities, :status, :created_by)";
                
                $params = [
                    ':room_number' => $room_number,
                    ':room_type' => $room_type,
                    ':description' => $description,
                    ':capacity' => $capacity,
                    ':price' => $price,
                    ':amenities' => $amenities,
                    ':status' => $status,
                    ':created_by' => $_SESSION['user_id']
                ];
                
                $result = execute($sql, $params);
                $message = 'Room added successfully';
                logActivity('ROOM_CREATED', 'ROOMS', lastInsertId(), "Room $room_number created");
                break;
                
            case 'update_room':
                $room_id = $_POST['room_id'];
                $room_number = sanitize($_POST['room_number']);
                $room_type = sanitize($_POST['room_type']);
                $description = sanitize($_POST['description']);
                $capacity = $_POST['capacity'];
                $price = $_POST['price_per_night'];
                $amenities = sanitize($_POST['amenities']);
                $status = $_POST['status'];
                
                $sql = "UPDATE rooms 
                        SET room_number = :room_number,
                            room_type = :room_type,
                            description = :description,
                            capacity = :capacity,
                            price_per_night = :price,
                            amenities = :amenities,
                            status = :status,
                            updated_at = SYSTIMESTAMP
                        WHERE room_id = :room_id";
                
                $params = [
                    ':room_id' => $room_id,
                    ':room_number' => $room_number,
                    ':room_type' => $room_type,
                    ':description' => $description,
                    ':capacity' => $capacity,
                    ':price' => $price,
                    ':amenities' => $amenities,
                    ':status' => $status
                ];
                
                $result = execute($sql, $params);
                $message = 'Room updated successfully';
                logActivity('ROOM_UPDATED', 'ROOMS', $room_id, "Room $room_number updated");
                break;
                
            case 'delete_room':
                $room_id = $_POST['room_id'];
                
                // Check if room has bookings
                $booking_check = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE room_id = :room_id", 
                                         [':room_id' => $room_id]);
                
                if ($booking_check && $booking_check['COUNT'] > 0) {
                    setFlash('error', 'Cannot delete room with existing bookings.');
                    redirect('admin_dashboard.php?action=rooms');
                }
                
                $sql = "DELETE FROM rooms WHERE room_id = :room_id";
                $result = execute($sql, [':room_id' => $room_id]);
                $message = 'Room deleted successfully';
                logActivity('ROOM_DELETED', 'ROOMS', $room_id, 'Room deleted');
                break;
                
            case 'update_user_status':
                $user_id = $_POST['user_id'];
                $is_active = $_POST['is_active'];
                $sql = "UPDATE users SET is_active = :is_active WHERE user_id = :user_id";
                $result = execute($sql, [':is_active' => $is_active, ':user_id' => $user_id]);
                $message = 'User status updated successfully';
                logActivity('USER_STATUS_UPDATED', 'USERS', $user_id, "Active: $is_active");
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                
                // Check if user has bookings
                $booking_check = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE user_id = :user_id", 
                                         [':user_id' => $user_id]);
                
                if ($booking_check && $booking_check['COUNT'] > 0) {
                    setFlash('error', 'Cannot delete user with existing bookings.');
                    redirect('admin_dashboard.php?action=users');
                }
                
                $sql = "DELETE FROM users WHERE user_id = :user_id AND user_type != 'admin'";
                $result = execute($sql, [':user_id' => $user_id]);
                $message = 'User deleted successfully';
                logActivity('USER_DELETED', 'USERS', $user_id, 'User deleted');
                break;
                
            case 'add_payment':
                error_log("=== ADD PAYMENT DEBUG ===");
                error_log("Booking ID: " . ($_POST['booking_id'] ?? 'empty'));
                error_log("Amount: " . ($_POST['amount'] ?? 'empty'));
                
                $booking_id = $_POST['booking_id'];
                $amount = $_POST['amount'];
                $payment_method = $_POST['payment_method'];
                $payment_type = $_POST['payment_type'];
                $notes = $_POST['notes'] ?? '';
                $transaction_id = $_POST['transaction_id'] ?? '';
                
                // Validate amount
                if ($amount <= 0) {
                    error_log("Amount validation failed: $amount");
                    setFlash('error', "Amount must be greater than 0");
                    $redirect_action = $_POST['redirect'] ?? 'payments';
                    redirect("admin_dashboard.php?action=" . $redirect_action);
                    return;
                }
                
                // Check if booking exists
                $booking_check = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booking_id = :booking_id", 
                                         [':booking_id' => $booking_id]);
                
                if (!$booking_check || $booking_check['COUNT'] == 0) {
                    error_log("Booking not found: $booking_id");
                    setFlash('error', "Booking not found");
                    $redirect_action = $_POST['redirect'] ?? 'payments';
                    redirect("admin_dashboard.php?action=" . $redirect_action);
                    return;
                }
                
                // Get booking details for logging
                $booking = fetchOne("SELECT total_price, payment_status FROM bookings WHERE booking_id = :booking_id", 
                                   [':booking_id' => $booking_id]);
                
                error_log("Booking found: Total = " . $booking['TOTAL_PRICE'] . ", Current status = " . $booking['PAYMENT_STATUS']);
                
                $sql = "INSERT INTO payments (booking_id, amount_paid, payment_method, payment_type, notes, transaction_id, status, payment_date) 
                        VALUES (:booking_id, :amount, :method, :type, :notes, :transaction_id, 'success', SYSTIMESTAMP)";
                
                $params = [
                    ':booking_id' => $booking_id,
                    ':amount' => $amount,
                    ':method' => $payment_method,
                    ':type' => $payment_type,
                    ':notes' => $notes,
                    ':transaction_id' => $transaction_id
                ];
                
                error_log("Inserting payment...");
                $result = execute($sql, $params);
                
                if ($result) {
                    error_log("Payment inserted successfully");
                    
                    // Get total paid for this booking
                    $paid_result = fetchOne("SELECT SUM(amount_paid) as total FROM payments WHERE booking_id = :booking_id", 
                                          [':booking_id' => $booking_id]);
                    
                    $total_paid = $paid_result['TOTAL'] ?? 0;
                    $booking_total = $booking['TOTAL_PRICE'];
                    
                    error_log("Total Paid: $total_paid, Booking Total: $booking_total");
                    
                    // Determine new payment status
                    if ($total_paid >= $booking_total) {
                        $new_status = 'paid';
                    } elseif ($total_paid > 0) {
                        $new_status = 'partial';
                    } else {
                        $new_status = 'unpaid';
                    }
                    
                    error_log("Updating booking payment status to: $new_status");
                    
                    // Update booking payment status
                    $update_sql = "UPDATE bookings SET payment_status = :status WHERE booking_id = :booking_id";
                    $update_result = execute($update_sql, [':status' => $new_status, ':booking_id' => $booking_id]);
                    
                    error_log("Update result: " . ($update_result ? 'Success' : 'Failed'));
                    
                    $message = 'Payment recorded successfully';
                    $payment_id = lastInsertId();
                    error_log("Payment ID: $payment_id");
                    logActivity('PAYMENT_ADDED', 'PAYMENTS', $payment_id, "Amount: $$amount, Type: $payment_type");
                    setFlash('success', $message);
                } else {
                    error_log("Payment insertion failed");
                    setFlash('error', 'Failed to record payment. Please try again.');
                }
                
                $redirect_action = $_POST['redirect'] ?? 'payments';
                redirect("admin_dashboard.php?action=" . $redirect_action);
                break;
        }
        
        if ($result) {
            setFlash('success', $message);
        } else {
            setFlash('error', 'Action failed. Please try again.');
        }
        
        $redirect_action = $_POST['redirect'] ?? 'dashboard';
        redirect("admin_dashboard.php?action=" . $redirect_action);
        
    } catch (Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
        $redirect_action = $_POST['redirect'] ?? 'dashboard';
        redirect("admin_dashboard.php?action=" . $redirect_action);
    }
}

/**
 * Get admin statistics
 */
function getAdminStats() {
    $stats = [];
    
    // Total revenue from ALL successful payments
    $revenue = fetchOne("SELECT SUM(amount_paid) as total FROM payments WHERE status = 'success'");
    $stats['revenue'] = $revenue ? $revenue['TOTAL'] : 0;
    
    // Total bookings
    $bookings = fetchOne("SELECT COUNT(*) as count FROM bookings");
    $stats['total_bookings'] = $bookings ? $bookings['COUNT'] : 0;
    
    // Active bookings (confirmed and not completed)
    $active = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'");
    $stats['active_bookings'] = $active ? $active['COUNT'] : 0;
    
    // Total users
    $users = fetchOne("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $users ? $users['COUNT'] : 0;
    
    // Available rooms
    $rooms = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = $rooms ? $rooms['COUNT'] : 0;
    
    // Occupied rooms
    $occupied = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'");
    $stats['occupied_rooms'] = $occupied ? $occupied['COUNT'] : 0;
    
    // Pending bookings
    $pending = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'");
    $stats['pending_bookings'] = $pending ? $pending['COUNT'] : 0;
    
    // Total payments count
    $payments = fetchOne("SELECT COUNT(*) as count FROM payments");
    $stats['total_payments'] = $payments ? $payments['COUNT'] : 0;
    
    // Today's payments
    $today_payments = fetchOne("SELECT SUM(amount_paid) as total FROM payments WHERE TRUNC(payment_date) = TRUNC(SYSDATE) AND status = 'success'");
    $stats['today_revenue'] = $today_payments ? $today_payments['TOTAL'] : 0;
    
    return $stats;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 70px;
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--secondary-color);
            color: white;
            z-index: 100;
            transition: all 0.3s;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            margin: 2px 0;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: var(--primary-color);
        }
        
        .nav-link i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .header {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: var(--secondary-color);
            font-weight: 600;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.revenue { border-left-color: #2ecc71; }
        .stat-card.bookings { border-left-color: #3498db; }
        .stat-card.users { border-left-color: #9b59b6; }
        .stat-card.rooms { border-left-color: #e74c3c; }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 25px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            opacity: 0.2;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--secondary-color);
            line-height: 1;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Tables */
        .data-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        /* Status Badges */
        .badge {
            padding: 5px 12px;
            font-weight: 500;
            border-radius: 20px;
        }
        
        .bg-pending { background-color: #fff3cd; color: #856404; }
        .bg-confirmed { background-color: #d4edda; color: #155724; }
        .bg-cancelled { background-color: #f8d7da; color: #721c24; }
        .bg-completed { background-color: #d1ecf1; color: #0c5460; }
        
        .bg-available { background-color: #d4edda; color: #155724; }
        .bg-occupied { background-color: #fff3cd; color: #856404; }
        .bg-maintenance { background-color: #f8d7da; color: #721c24; }
        
        .bg-paid { background-color: #d4edda; color: #155724; }
        .bg-unpaid { background-color: #f8d7da; color: #721c24; }
        .bg-partial { background-color: #fff3cd; color: #856404; }
        
        /* Action Buttons */
        .btn-action {
            width: 35px;
            height: 35px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
        
        /* Form Styles */
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><i class="fas fa-hotel me-2"></i> <?php echo APP_NAME; ?> Admin</h4>
            <small class="text-light">Welcome, <?php echo $current_user['FULL_NAME']; ?></small>
        </div>
        
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'rooms' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=rooms">
                        <i class="fas fa-bed"></i> Rooms Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'bookings' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=bookings">
                        <i class="fas fa-calendar-alt"></i> Bookings
                        <?php if ($stats['pending_bookings'] > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $stats['pending_bookings']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'users' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=users">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'payments' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=payments">
                        <i class="fas fa-credit-card"></i> Payments
                        <?php if ($stats['total_payments'] > 0): ?>
                        <span class="badge bg-success float-end"><?php echo $stats['total_payments']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'reports' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php?action=reports">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
            
                <li class="nav-item mt-4">
                    <a class="nav-link" href="../dashboard.php">
                        <i class="fas fa-arrow-left"></i> Back to User View
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
                <h2 class="page-title mb-0">
                    <?php 
                    $titles = [
                        'dashboard' => 'Dashboard Overview',
                        'rooms' => 'Rooms Management',
                        'bookings' => 'Bookings Management',
                        'users' => 'Users Management',
                        'payments' => 'Payments',
                    ];
                    echo $titles[$action] ?? 'Admin Panel';
                    ?>
                </h2>
                <small class="text-muted">Last updated: <?php echo date('F j, Y, g:i a'); ?></small>
            </div>
            <div>
                <button class="btn btn-outline-primary d-md-none" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php echo displayFlash(); ?>
        
        <!-- Content based on action -->
        <?php 
        switch ($action) {
        case 'dashboard':
          include 'sections/dashboard.php';
        break;
        case 'rooms':
           include 'sections/rooms.php';
        break;
         case 'bookings':
          include 'sections/bookings.php';
        break;
        case 'users':
           include 'sections/users.php';
        break;
         case 'payments':
          // Pass $all_bookings to the payments section
           include 'sections/payments.php';
        break;
        case 'reports':
          include 'sections/reports.php';
        break;
        default:
          include 'sections/dashboard.php';
        }
        ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Toggle sidebar on mobile
        $('#menuToggle').click(function() {
            $('.sidebar').toggleClass('active');
        });
        
        // Initialize DataTables
        $(document).ready(function() {
            $('.data-table table').DataTable({
                "pageLength": 10,
                "order": [[0, 'desc']],
                "responsive": true
            });
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
        
        // Confirm actions
        function confirmAction(action, message) {
            return confirm(message || 'Are you sure you want to perform this action?');
        }
        
        // Show modal for adding room
        function showAddRoomModal() {
            $('#addRoomModal').modal('show');
        }
        
        // Show modal for editing room
        function showEditRoomModal(roomId) {
            $('#editRoomId').val(roomId);
            $('#editRoomModal').modal('show');
        }
        
        // Show modal for managing booking
        function showManageBookingModal(bookingId) {
            $('#manageBookingId').val(bookingId);
            $('#manageBookingModal').modal('show');
        }
        
        // Show modal for adding payment
        function showAddPaymentModal(bookingId) {
            $('#addPaymentBookingId').val(bookingId);
            $('#addPaymentModal').modal('show');
        }
        
        // Calculate booking total
        function calculateBookingTotal() {
            const checkIn = new Date($('#check_in').val());
            const checkOut = new Date($('#check_out').val());
            const price = parseFloat($('#room_price').val());
            
            if (checkIn && checkOut && price && checkOut > checkIn) {
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const total = nights * price;
                $('#total_nights').val(nights);
                $('#total_price').val(total.toFixed(2));
                $('#total_price_display').text('$' + total.toFixed(2));
            }
        }
    </script>
</body>
</html>