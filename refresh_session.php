<?php
// Resume session
session_start();

// Only refresh if user is actually logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'remaining' => 900]);
} else {
    // Return failure response
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
}
?>