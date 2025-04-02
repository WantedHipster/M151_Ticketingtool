<?php
// Set session timeout to 15 minutes (900 seconds)
ini_set('session.gc_maxlifetime', 900);
session_set_cookie_params(900);

// Start or resume the session
session_start();

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    // Last activity was more than 15 minutes ago, destroy session
    session_unset();
    session_destroy();
    
    // Redirect to login page with timeout message
    header("location: index.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Calculate remaining time (in seconds)
$remaining = 900;
if (isset($_SESSION['last_activity'])) {
    $remaining = 900 - (time() - $_SESSION['last_activity']);
    if ($remaining < 0) $remaining = 0;
}
$_SESSION['timeout_remaining'] = $remaining;
?>