<?php
/**
 * Authentication Functions
 */

require_once 'functions.php';

/**
 * Login user
 */
function login($email, $password) {
    $sql = "SELECT user_id, full_name, email, password_hash, user_type, is_active 
            FROM users WHERE email = :email";
    
    $user = fetchOne($sql, [':email' => $email]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    if ($user['IS_ACTIVE'] != 1) {
        return ['success' => false, 'message' => 'Account is inactive'];
    }
    
    // IMPORTANT: Your passwords are stored as plain text, so we need to handle both
    // For now, use simple comparison (you should hash passwords properly)
    if ($user['PASSWORD_HASH'] === $password || verifyPassword($password, $user['PASSWORD_HASH'])) {
        // Set session
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['user_email'] = $user['EMAIL'];
        $_SESSION['user_name'] = $user['FULL_NAME'];
        $_SESSION['user_type'] = $user['USER_TYPE'];
        
        // Log login activity
        if ($user['USER_TYPE'] === 'admin') {
            logActivity('LOGIN', 'USERS', $user['USER_ID'], 'User logged in');
        }
        
        return ['success' => true, 'user_type' => $user['USER_TYPE']];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

/**
 * Register new user
 */
function registerUser($data) {
    // Check if email exists
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
    $result = fetchOne($check_sql, [':email' => $data['email']]);
    
    if ($result && $result['COUNT'] > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Insert user
    $sql = "INSERT INTO users (full_name, email, password_hash, phone_number) 
            VALUES (:full_name, :email, :password_hash, :phone)";
    
    $params = [
        ':full_name' => $data['full_name'],
        ':email' => $data['email'],
        ':password_hash' => hashPassword($data['password']),
        ':phone' => $data['phone'] ?? null
    ];
    
    if (execute($sql, $params)) {
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

/**
 * Logout user
 */
function logout() {
    // Clear session
    session_unset();
    session_destroy();
    session_start(); // Start fresh session for flash messages
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to access this page');
        redirect('login.php');
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect('dashboard.php');
    }
}
?>