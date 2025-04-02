<?php
// Include database connection
require_once __DIR__ . '/db_connect.php';

// Function to check if a column exists in a table
function columnExists($mysqli, $table, $column) {
    $sql = "SHOW COLUMNS FROM {$table} LIKE '{$column}'";
    $result = $mysqli->query($sql);
    return $result->num_rows > 0;
}

// Check if users table exists
$tableExists = $mysqli->query("SHOW TABLES LIKE 'users'")->num_rows > 0;

if ($tableExists) {
    // Check if role column exists in users table
    if (!columnExists($mysqli, 'users', 'role')) {
        // Add role column to users table
        $mysqli->query("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
        echo "Added role column to users table.<br>";
    } else {
        echo "Role column already exists in users table.<br>";
    }
} else {
    echo "Users table does not exist yet. It will be created when a user registers.<br>";
}

// Create tickets table if it doesn't exist
$ticketsTableQuery = "CREATE TABLE IF NOT EXISTS tickets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    created_by INT(11) NOT NULL,
    assigned_to INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
)";

if ($mysqli->query($ticketsTableQuery)) {
    echo "Tickets table created or already exists.<br>";
} else {
    echo "Error creating tickets table: " . $mysqli->error . "<br>";
}

// Create ticket_comments table if it doesn't exist
$commentsTableQuery = "CREATE TABLE IF NOT EXISTS ticket_comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($mysqli->query($commentsTableQuery)) {
    echo "Ticket comments table created or already exists.<br>";
} else {
    echo "Error creating ticket comments table: " . $mysqli->error . "<br>";
}

// Create ticket_history table if it doesn't exist
$historyTableQuery = "CREATE TABLE IF NOT EXISTS ticket_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    field_changed VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($mysqli->query($historyTableQuery)) {
    echo "Ticket history table created or already exists.<br>";
} else {
    echo "Error creating ticket history table: " . $mysqli->error . "<br>";
}

// Create first admin user if no admin exists
$checkAdminQuery = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$adminResult = $mysqli->query($checkAdminQuery);

if ($adminResult->num_rows == 0) {
    // Check if any users exist
    $usersExist = $mysqli->query("SELECT id FROM users LIMIT 1")->num_rows > 0;
    
    if ($usersExist) {
        // Make the first user an admin
        $mysqli->query("UPDATE users SET role = 'admin' WHERE id = (SELECT MIN(id) FROM users)");
        echo "First user promoted to admin.<br>";
    } else {
        echo "No users exist yet. The first registered user will be automatically promoted to admin.<br>";
    }
}

echo "<p>Database update completed.</p>";
echo "<p><a href='index.php'>Return to login page</a></p>";
?>