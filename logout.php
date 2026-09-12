<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Logout user
logout();

setFlash('success', 'You have been logged out successfully.');
redirect('index.php');
?>