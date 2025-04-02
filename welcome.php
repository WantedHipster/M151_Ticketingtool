<?php
// Initialize the session
include 'session_config.php';

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Redirect to dashboard
header("location: dashboard.php");
exit;
?>

// Redirect to dashboard
header("location: dashboard.php");
exit;
?>