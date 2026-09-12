<?php
// test_payment.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Test Payment Insertion</h1>";

// Test if we can insert a payment
$sql = "INSERT INTO payments (booking_id, amount_paid, payment_method, payment_type, status) 
        VALUES (1, 100.00, 'cash', 'booking', 'success')";

echo "SQL: $sql<br>";

try {
    $result = execute($sql);
    echo "Result: " . ($result ? "✅ Success" : "❌ Failed") . "<br>";
    
    if ($result) {
        echo "Payment ID: " . lastInsertId() . "<br>";
    }
    
    // Check if payment exists
    $check = fetchOne("SELECT COUNT(*) as count FROM payments");
    echo "Total payments in database: " . ($check['COUNT'] ?? 0) . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Show all payments
echo "<h2>All Payments</h2>";
$payments = fetchAll("SELECT * FROM payments");
if ($payments) {
    echo "<pre>";
    print_r($payments);
    echo "</pre>";
} else {
    echo "No payments found<br>";
}
?>