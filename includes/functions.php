<?php
/**
 * Helper Functions for Serenity Stay
 */

require_once 'config.php';

/**
 * Execute SQL Query with parameters
 */
function executeQuery($sql, $params = []) {
    $conn = getDBConnection();
    $stid = oci_parse($conn, $sql);
    
    if (!$stid) {
        $e = oci_error($conn);
        error_log("Query Parse Error: " . $e['message']);
        return false;
    }
    
    // Bind parameters
    foreach ($params as $key => &$value) {
        oci_bind_by_name($stid, $key, $value);
    }
    
    if (!@oci_execute($stid)) {
        $e = oci_error($stid);
        error_log("Query Execution Error: " . $e['message']);
        oci_free_statement($stid);
        return false;
    }
    
    return $stid;
}

/**
 * Fetch all rows from query
 */
function fetchAll($sql, $params = []) {
    $stid = executeQuery($sql, $params);
    if (!$stid) return [];
    
    $results = [];
    while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $results[] = $row;
    }
    
    oci_free_statement($stid);
    return processOracleResults($results);
}

/**
 * Fetch single row
 */
function fetchOne($sql, $params = []) {
    $stid = executeQuery($sql, $params);
    if (!$stid) return null;
    
    $row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);
    oci_free_statement($stid);
    
    if (!$row) return null;
    
    $processedRow = [];
    foreach ($row as $key => $value) {
        // Handle CLOB fields
        if (is_object($value) && get_class($value) === 'OCILob') {
            $processedRow[$key] = $value->load() ?: '';
            $value->free();
        } else {
            $processedRow[$key] = $value;
        }
    }
    return $processedRow;
}

/**
 * Convert Oracle results to readable format
 */
function processOracleResults($results) {
    if (!is_array($results)) return $results;
    
    $processed = [];
    foreach ($results as $row) {
        $processedRow = [];
        foreach ($row as $key => $value) {
            // Handle CLOB fields (OCILob objects)
            if (is_object($value) && get_class($value) === 'OCILob') {
                $processedRow[$key] = $value->load() ?: '';
                $value->free();
            } else {
                $processedRow[$key] = $value;
            }
        }
        $processed[] = $processedRow;
    }
    return $processed;
}

/**
 * Execute insert/update/delete
 */
function execute($sql, $params = []) {
    $stid = executeQuery($sql, $params);
    if (!$stid) return false;
    
    $affected = oci_num_rows($stid);
    oci_free_statement($stid);
    return $affected;
}

/**
 * Get last insert ID
 */
function lastInsertId() {
    $sql = "SELECT last_insert_id FROM dual";
    $result = fetchOne($sql);
    return $result ? $result['LAST_INSERT_ID'] : null;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}


/**
 * Display flash message with better styling - IMPROVED
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
        
        // Map message types to Bootstrap classes and icons
        $typeMap = [
            'success' => ['class' => 'success', 'icon' => 'check-circle'],
            'error'   => ['class' => 'danger', 'icon' => 'exclamation-triangle'],
            'warning' => ['class' => 'warning', 'icon' => 'exclamation-circle'],
            'info'    => ['class' => 'info', 'icon' => 'info-circle']
        ];
        
        $config = $typeMap[$type] ?? $typeMap['info'];
        $alertClass = $config['class'];
        $icon = $config['icon'];
        
        // Generate unique ID for the alert
        $alertId = 'flash-alert-' . uniqid();
        
        return <<<HTML
        <div id="{$alertId}" class="alert alert-{$alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-{$icon} me-2"></i>
            {$message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            // Auto-dismiss this specific alert
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.getElementById('{$alertId}');
                if (alert) {
                    // Auto-dismiss after 5 seconds
                    setTimeout(() => {
                        if (alert.classList.contains('show')) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 5000);
                    
                    // Also dismiss on click anywhere
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.alert')) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, { once: true });
                }
            });
        </script>
HTML;
    }
    return '';
}


/**
 * Log activity (for admin actions) - Only keep this one
 */
function logActivity($action, $table, $record_id = null, $details = '') {
    if (!isLoggedIn()) return;
    
    $sql = "INSERT INTO audit_log (admin_id, action_type, table_name, record_id, new_values, ip_address) 
            VALUES (:admin_id, :action, :table_name, :record_id, :details, :ip)";
    
    $params = [
        ':admin_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':table_name' => $table,
        ':record_id' => $record_id,
        ':details' => $details,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ];
    
    return execute($sql, $params);
}

/**
 * Check if room is available for given dates
 */
function isRoomAvailable($room_id, $check_in, $check_out) {
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE room_id = :room_id 
            AND booking_status IN ('confirmed', 'pending')
            AND NOT (check_out_date <= TO_DATE(:check_in, 'YYYY-MM-DD') 
                    OR check_in_date >= TO_DATE(:check_out, 'YYYY-MM-DD'))";
    
    $result = fetchOne($sql, [
        ':room_id' => $room_id,
        ':check_in' => $check_in,
        ':check_out' => $check_out
    ]);
    
    return ($result && $result['COUNT'] == 0);
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Get current user info
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    
    $sql = "SELECT user_id, full_name, email, user_type, phone_number, created_at 
            FROM users WHERE user_id = :user_id";
    return fetchOne($sql, [':user_id' => $_SESSION['user_id']]);
}

/**
 * Password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>