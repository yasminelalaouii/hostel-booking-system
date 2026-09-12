<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    
    // Get total paid amount for this booking
    $sql = "SELECT COALESCE(SUM(amount_paid), 0) as total_paid 
            FROM payments 
            WHERE booking_id = :booking_id 
            AND status = 'success'";
    
    $result = fetchOne($sql, [':booking_id' => $booking_id]);
    
    echo json_encode([
        'paid' => $result['TOTAL_PAID'] ?? 0,
        'success' => true
    ]);
} else {
    echo json_encode([
        'paid' => 0,
        'success' => false,
        'error' => 'Invalid request'
    ]);
}
?>