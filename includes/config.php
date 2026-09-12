<?php
/**
 * Database Configuration for Serenity Stay
 */

// Database credentials for Oracle XE
define('DB_HOST', 'localhost');
define('DB_PORT', '1521');
define('DB_SERVICE', 'XEPDB1');
define('DB_USER', 'hostel');
define('DB_PASS', 'hostel123');
define('DB_CHARSET', 'AL32UTF8');

// Application settings
define('APP_NAME', 'Serenity Stay');
define('BASE_URL', 'http://localhost/Hostel/');
define('SITE_NAME', 'Hostel Booking System');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Security
define('PASSWORD_COST', 12);

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get Oracle Database Connection
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $tns = "(DESCRIPTION =
                (ADDRESS = (PROTOCOL = TCP)(HOST = " . DB_HOST . ")(PORT = " . DB_PORT . "))
                (CONNECT_DATA =
                    (SERVICE_NAME = " . DB_SERVICE . ")
                )
            )";
        
        $conn = oci_connect(DB_USER, DB_PASS, $tns, DB_CHARSET);
        
        if (!$conn) {
            $e = oci_error();
            error_log("Oracle Connection Error: " . $e['message']);
            
            // User-friendly error
            if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode']) {
                die("Database connection error: " . $e['message']);
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
    }
    
    return $conn;
}
?>