<?php
/**
 * Database connection file
 * Create this file as db_connect.php
 */

// Database connection parameters
$host = 'localhost'; // host
$username = 'rob'; // username
$password = 'Test1234'; // password
$database = '151_users'; // database

// Connect to the database
try {
    $mysqli = new mysqli($host, $username, $password, $database);

    // Check for connection errors
    if ($mysqli->connect_error) {
        die('Database Connection Error (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
    }

    // Set charset to ensure proper handling of special characters
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    die('Database Error: ' . $e->getMessage());
}