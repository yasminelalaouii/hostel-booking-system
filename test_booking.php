<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

echo "<h1>Booking Test</h1>";

// Test 1: Check if user is logged in
if (isLoggedIn()) {
    echo "✅ User logged in: " . $_SESSION['user_email'] . "<br>";
} else {
    echo "❌ User not logged in<br>";
}

// Test 2: Check session data
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test 3: Check if bookings exist for current user
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = :user_id";
    $result = fetchOne($sql, [':user_id' => $user_id]);
    echo "✅ User has " . ($result['COUNT'] ?? 0) . " bookings<br>";
    
    // Show all bookings
    $sql = "SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC";
    $bookings = fetchAll($sql, [':user_id' => $user_id]);
    echo "<h2>User Bookings:</h2>";
    echo "<pre>";
    print_r($bookings);
    echo "</pre>";
}

// Test 4: Check rooms status
$sql = "SELECT room_id, room_number, status FROM rooms ORDER BY room_number";
$rooms = fetchAll($sql);
echo "<h2>Rooms Status:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Room ID</th><th>Room #</th><th>Status</th></tr>";
foreach ($rooms as $room) {
    echo "<tr>";
    echo "<td>" . $room['ROOM_ID'] . "</td>";
    echo "<td>" . $room['ROOM_NUMBER'] . "</td>";
    echo "<td>" . $room['STATUS'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>