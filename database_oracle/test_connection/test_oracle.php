<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Oracle Test - XAMPP</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        .success { color: green; background: #e7f7e7; padding: 10px; border: 1px solid green; }
        .error { color: red; background: #f7e7e7; padding: 10px; border: 1px solid red; }
        .info { color: blue; background: #e7e7f7; padding: 10px; border: 1px solid blue; }
    </style>
</head>
<body>
    <h1>Oracle Database Connection Test</h1>";

// Test 1: Check if OCI8 is loaded
echo "<h2>1. Checking OCI8 Extension</h2>";
if (extension_loaded('oci8')) {
    echo "<div class='success'>✅ OCI8 extension is LOADED!</div>";
    echo "<p>OCI8 Version: " . phpversion('oci8') . "</p>";
} else {
    echo "<div class='error'>❌ OCI8 extension is NOT loaded</div>";
    echo "<p>Please check php.ini configuration</p>";
}

// Test 2: Check Oracle environment
echo "<h2>2. Checking Environment</h2>";
echo "<div class='info'>";
echo "<p><strong>ORACLE_HOME:</strong> " . (getenv('ORACLE_HOME') ? getenv('ORACLE_HOME') : 'Not set') . "</p>";

$path = getenv('PATH');
if (strpos($path, 'instantclient') !== false) {
    echo "<p><strong>PATH:</strong> ✅ Contains Oracle Instant Client</p>";
} else {
    echo "<p><strong>PATH:</strong> ❌ Does not contain Oracle Instant Client</p>";
}
echo "</div>";

// Test 3: Check if Oracle client files exist
echo "<h2>3. Checking Oracle Client Files</h2>";
$oracle_home = getenv('ORACLE_HOME');
$important_files = [
    'oci.dll',
    'oraocci23.dll',
    'oraociei23.dll'
];

if ($oracle_home && is_dir($oracle_home)) {
    echo "<p>ORACLE_HOME directory exists: $oracle_home</p>";
    echo "<ul>";
    foreach ($important_files as $file) {
        $file_path = $oracle_home . '\\' . $file;
        if (file_exists($file_path)) {
            echo "<li style='color: green;'>✅ $file exists</li>";
        } else {
            echo "<li style='color: red;'>❌ $file NOT found</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<div class='error'>ORACLE_HOME directory not found or not set</div>";
}

// Test 4: Try to connect
echo "<h2>4. Testing Database Connection</h2>";
if (extension_loaded('oci8')) {
    // Your database credentials
    $host = 'localhost';
    $port = '1521';
    $service_name = 'XEPDB1';
    $username = 'hostel';
    $password = 'hostel123';
    
    // TNS connection string
    $tns = "(DESCRIPTION =
            (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
            (CONNECT_DATA =
                (SERVICE_NAME = $service_name)
            )
        )";
    
    echo "<p>Connecting as: <strong>$username</strong> to <strong>$service_name</strong></p>";
    
    try {
        // Try to connect
        $conn = oci_connect($username, $password, $tns);
        
        if ($conn) {
            echo "<div class='success'>";
            echo "<h3>✅ CONNECTION SUCCESSFUL!</h3>";
            echo "<p>Connected to Oracle Database successfully.</p>";
            
            // Get Oracle version
            $sql = "SELECT * FROM v\$version WHERE banner LIKE '%Oracle%'";
            $stid = oci_parse($conn, $sql);
            oci_execute($stid);
            $row = oci_fetch_array($stid, OCI_ASSOC);
            echo "<p><strong>Oracle Version:</strong> " . $row['BANNER'] . "</p>";
            
            // Test query
            $sql2 = "SELECT SYSDATE as current_date FROM dual";
            $stid2 = oci_parse($conn, $sql2);
            oci_execute($stid2);
            $row2 = oci_fetch_array($stid2, OCI_ASSOC);
            echo "<p><strong>Current Database Date:</strong> " . $row2['CURRENT_DATE'] . "</p>";
            
            echo "</div>";
            
            oci_close($conn);
        } else {
            $e = oci_error();
            echo "<div class='error'>";
            echo "<h3>❌ CONNECTION FAILED</h3>";
            echo "<p><strong>Error Code:</strong> " . $e['code'] . "</p>";
            echo "<p><strong>Error Message:</strong> " . $e['message'] . "</p>";
            
            // Common error solutions
            echo "<h4>Common Solutions:</h4>";
            echo "<ul>";
            echo "<li>Make sure Oracle Database (XE) service is running</li>";
            echo "<li>Check if listener is running: <code>lsnrctl status</code></li>";
            echo "<li>Verify credentials: username=$username, password=$password</li>";
            echo "<li>Check service name: $service_name</li>";
            echo "<li>Test with SQL*Plus: <code>sqlplus hostel/hostel123@localhost:1521/XEPDB1</code></li>";
            echo "</ul>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ EXCEPTION</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>Cannot test connection - OCI8 extension not loaded</div>";
}

// Test 5: List all loaded extensions
echo "<h2>5. Loaded PHP Extensions</h2>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<div style='max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;'>";
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><a href='test_oracle.php'>Refresh this page after restarting Apache</a></li>";
echo "<li><a href='create_tables.php'>Create database tables</a></li>";
echo "<li><a href='register.php'>Create registration page</a></li>";
echo "</ol>";

echo "<h3>Quick Troubleshooting Commands:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "# Check Oracle service (run as administrator):\n";
echo "net start | find \"Oracle\"\n\n";
echo "# Start Oracle service if not running:\n";
echo "net start OracleServiceXE\n\n";
echo "# Check listener status:\n";
echo "lsnrctl status\n";
echo "</pre>";

echo "</body></html>";
?>