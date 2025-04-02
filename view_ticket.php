<?php
// Initialize the session
include 'session_config.php';

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Get user information
$userId = $_SESSION["id"];
$isAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";

// Check if ticket ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: dashboard.php");
    exit;
}

// Delete ticket
if(isset($_POST["action"]) && $_POST["action"] == "delete_ticket" && $isAdmin){
    $ticketId = intval($_GET["id"]);
    try {
        // Prepare delete statement
        $deleteQuery = "DELETE FROM tickets WHERE id = ?";
        $deleteStmt = $mysqli->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $ticketId);
        
        if($deleteStmt->execute()){
            // Redirect to dashboard after successful deletion
            header("location: dashboard.php?delete_success=1");
            exit();
        } else {
            $error = "Failed to delete ticket.";
        }
        
        $deleteStmt->close();
    } catch (Exception $e) {
        $error = "Error deleting ticket: " . $e->getMessage();
    }
}

$ticketId = intval($_GET["id"]);

// Get ticket information
$ticketQuery = "SELECT t.*, 
                u_creator.username AS creator_username,
                u_creator.firstname AS creator_firstname,
                u_creator.lastname AS creator_lastname,
                u_assigned.username AS assigned_username,
                u_assigned.firstname AS assigned_firstname,
                u_assigned.lastname AS assigned_lastname
            FROM tickets t
            LEFT JOIN users u_creator ON t.created_by = u_creator.id
            LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
            WHERE t.id = ?";
$stmt = $mysqli->prepare($ticketQuery);
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    // Ticket not found
    header("location: dashboard.php");
    exit;
}

$ticket = $result->fetch_assoc();
$stmt->close();

// Check if the user has permission to view this ticket
if(!$isAdmin && $ticket['created_by'] != $userId && $ticket['assigned_to'] != $userId){
    // User does not have permission to view this ticket
    header("location: dashboard.php");
    exit;
}

// Process form submission for updates
$error = $success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Combined update for status and assignment
    if(isset($_POST["action"]) && $_POST["action"] == "update_ticket"){
        $changes = false;
        
        // Process status update
        $newStatus = $_POST["status"];
        $validStatuses = array("open", "in_progress", "resolved", "closed");
        
        if(in_array($newStatus, $validStatuses) && $newStatus != $ticket["status"]){
            $oldStatus = $ticket["status"];
            
            $updateQuery = "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("si", $newStatus, $ticketId);
            
            if($updateStmt->execute()){
                // Add to history
                $historyQuery = "INSERT INTO ticket_history (ticket_id, user_id, field_changed, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
                $historyStmt = $mysqli->prepare($historyQuery);
                $field = "status";
                $historyStmt->bind_param("iisss", $ticketId, $userId, $field, $oldStatus, $newStatus);
                $historyStmt->execute();
                $historyStmt->close();
                
                $changes = true;
            } else {
                $error = "Failed to update ticket status.";
            }
            
            $updateStmt->close();
        }
        
        // Process assignment update (for admins)
        if($isAdmin && empty($error)){
            $newAssignee = isset($_POST["assigned_to"]) ? ($_POST["assigned_to"] === "" ? null : intval($_POST["assigned_to"])) : null;
            
            // Only process if the assignment actually changed
            if($newAssignee !== $ticket["assigned_to"]){
                // Verify user exists if not null
                if($newAssignee !== null){
                    $userCheck = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
                    $userCheck->bind_param("i", $newAssignee);
                    $userCheck->execute();
                    $userExists = $userCheck->get_result()->num_rows > 0;
                    $userCheck->close();
                    
                    if(!$userExists){
                        $error = "Selected user does not exist.";
                        $newAssignee = null;
                    }
                }
                
                if(empty($error)){
                    $oldAssignee = $ticket["assigned_to"];
                    
                    $updateQuery = "UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?";
                    $updateStmt = $mysqli->prepare($updateQuery);
                    $updateStmt->bind_param("ii", $newAssignee, $ticketId);
                    
                    if($updateStmt->execute()){
                        // Add to history
                        $historyQuery = "INSERT INTO ticket_history (ticket_id, user_id, field_changed, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
                        $historyStmt = $mysqli->prepare($historyQuery);
                        $field = "assigned_to";
                        
                        // Get old and new usernames
                        $oldUsername = "";
                        $newUsername = "Unassigned";
                        
                        if($oldAssignee){
                            $userQuery = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
                            $userQuery->bind_param("i", $oldAssignee);
                            $userQuery->execute();
                            $oldUsername = $userQuery->get_result()->fetch_assoc()["username"];
                            $userQuery->close();
                        } else {
                            $oldUsername = "Unassigned";
                        }
                        
                        if($newAssignee){
                            $userQuery = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
                            $userQuery->bind_param("i", $newAssignee);
                            $userQuery->execute();
                            $newUsername = $userQuery->get_result()->fetch_assoc()["username"];
                            $userQuery->close();
                        }
                        
                        $historyStmt->bind_param("iisss", $ticketId, $userId, $field, $oldUsername, $newUsername);
                        $historyStmt->execute();
                        $historyStmt->close();
                        
                        $changes = true;
                    } else {
                        $error = "Failed to update ticket assignment.";
                    }
                    
                    $updateStmt->close();
                }
            }
        }
        
        // Set success message if changes were made
        if($changes && empty($error)){
            $success = "Ticket updated successfully.";
            
            // Refresh ticket data
            $stmt = $mysqli->prepare($ticketQuery);
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            $ticket = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
    
    // Add comment
    if(isset($_POST["action"]) && $_POST["action"] == "add_comment"){
        $comment = trim($_POST["comment"]);
        
        if(empty($comment)){
            $error = "Comment cannot be empty.";
        } else {
            $commentQuery = "INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)";
            $commentStmt = $mysqli->prepare($commentQuery);
            $commentStmt->bind_param("iis", $ticketId, $userId, $comment);
            
            if($commentStmt->execute()){
                // Update ticket timestamp
                $updateQuery = "UPDATE tickets SET updated_at = NOW() WHERE id = ?";
                $updateStmt = $mysqli->prepare($updateQuery);
                $updateStmt->bind_param("i", $ticketId);
                $updateStmt->execute();
                $updateStmt->close();
                
                $success = "Comment added successfully.";
            } else {
                $error = "Failed to add comment.";
            }
            
            $commentStmt->close();
        }
    }
}

// Get all users for assignment dropdown (admin only)
$users = [];
if($isAdmin){
    $usersQuery = "SELECT id, username, firstname, lastname FROM users ORDER BY username";
    $usersResult = $mysqli->query($usersQuery);
    while($user = $usersResult->fetch_assoc()){
        $users[] = $user;
    }
}

// Get comments for this ticket
$commentsQuery = "SELECT c.*, u.username, u.firstname, u.lastname 
                FROM ticket_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.ticket_id = ?
                ORDER BY c.created_at ASC";
$commentsStmt = $mysqli->prepare($commentsQuery);
$commentsStmt->bind_param("i", $ticketId);
$commentsStmt->execute();
$comments = $commentsStmt->get_result();
$commentsStmt->close();

// Get history for this ticket
$historyQuery = "SELECT h.*, u.username, u.firstname, u.lastname 
                FROM ticket_history h
                JOIN users u ON h.user_id = u.id
                WHERE h.ticket_id = ?
                ORDER BY h.changed_at ASC";
$historyStmt = $mysqli->prepare($historyQuery);
$historyStmt->bind_param("i", $ticketId);
$historyStmt->execute();
$history = $historyStmt->get_result();
$historyStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Ticket #<?php echo $ticketId; ?> - Ticketing System</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
     <!-- Add the timer CSS here -->
     <link rel="stylesheet" href="session_timer.css">
    <style>
        .navbar-custom {
            background-color: #3c8dbc;
            color: white;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .navbar-nav>li>a {
            color: white;
        }
        .user-role-badge {
            background-color: #3c8dbc;
            color: white;
            padding: 3px 7px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .admin-role-badge {
            background-color: #d9534f;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-open {
            background-color: #d9edf7;
            color: #31708f;
        }
        .status-in_progress {
            background-color: #fcf8e3;
            color: #8a6d3b;
        }
        .status-resolved {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .status-closed {
            background-color: #f2f2f2;
            color: #777;
        }
        .comment-panel {
            margin-bottom: 15px;
        }
        .comment-header {
            background-color: #f8f8f8;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .comment-body {
            padding: 15px;
        }
        .history-item {
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        .history-date {
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-custom">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Ticketing System</a>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="create_ticket.php"><i class="fa fa-plus-circle"></i> Create New Ticket</a></li>
                    <?php if ($isAdmin): ?>
                    <li><a href="admin.php"><i class="fa fa-cogs"></i> Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#">
                            <i class="fa fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                            <span class="user-role-badge <?php echo $isAdmin ? 'admin-role-badge' : ''; ?>">
                                <?php echo $isAdmin ? 'Admin' : 'User'; ?>
                            </span>
                        </a>
                    </li>
                    <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Ticket details -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="pull-right">
                                <span class="status-badge status-<?php echo htmlspecialchars($ticket['status']); ?>">
                                    <?php 
                                        switch($ticket['status']) {
                                            case 'open': echo 'Open'; break;
                                            case 'in_progress': echo 'In Progress'; break;
                                            case 'resolved': echo 'Resolved'; break;
                                            case 'closed': echo 'Closed'; break;
                                            default: echo htmlspecialchars($ticket['status']);
                                        }
                                    ?>
                                </span>
                            </span>
                            <i class="fa fa-ticket"></i> Ticket #<?php echo $ticketId; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Created by:</strong> <?php echo htmlspecialchars($ticket['creator_username']); ?></p>
                                <p><strong>Created on:</strong> <?php echo date('M d, Y g:i A', strtotime($ticket['created_at'])); ?></p>
                                <p>
                                    <strong>Priority:</strong> 
                                    <?php 
                                        switch($ticket['priority']) {
                                            case 'low': echo '<span class="label label-default">Low</span>'; break;
                                            case 'medium': echo '<span class="label label-info">Medium</span>'; break;
                                            case 'high': echo '<span class="label label-warning">High</span>'; break;
                                            case 'critical': echo '<span class="label label-danger">Critical</span>'; break;
                                            default: echo htmlspecialchars($ticket['priority']);
                                        }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p>
                                    <strong>Assigned to:</strong> 
                                    <?php 
                                        if ($ticket['assigned_username']) {
                                            echo htmlspecialchars($ticket['assigned_username']);
                                        } else {
                                            echo '<em>Unassigned</em>';
                                        }
                                    ?>
                                </p>
                                <p><strong>Last updated:</strong> <?php echo date('M d, Y g:i A', strtotime($ticket['updated_at'])); ?></p>
                                <p>
                                    <strong>Status:</strong> 
                                    <?php 
                                        switch($ticket['status']) {
                                            case 'open': echo 'Open'; break;
                                            case 'in_progress': echo 'In Progress'; break;
                                            case 'resolved': echo 'Resolved'; break;
                                            case 'closed': echo 'Closed'; break;
                                            default: echo htmlspecialchars($ticket['status']);
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="well">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Comments -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-comments"></i> Comments</h3>
                    </div>
                    <div class="panel-body">
                        <?php if($comments->num_rows > 0): ?>
                            <?php while($comment = $comments->fetch_assoc()): ?>
                                <div class="panel panel-default comment-panel">
                                    <div class="comment-header">
                                        <strong><?php echo htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname'] . ' (' . $comment['username'] . ')'); ?></strong>
                                        <span class="pull-right"><?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php endif; ?>
                        
                        <!-- Add comment form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $ticketId); ?>" method="post">
                            <div class="form-group">
                                <label for="comment">Add a Comment</label>
                                <textarea class="form-control" name="comment" id="comment" rows="3" required></textarea>
                            </div>
                            <input type="hidden" name="action" value="add_comment">
                            <button type="submit" class="btn btn-primary">Submit Comment</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Actions -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-cog"></i> Actions</h3>
                    </div>
                    <div class="panel-body">
                        <!-- Combined form for status update and ticket assignment -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $ticketId); ?>" method="post" class="form-horizontal">
                            <div class="form-group">
                                <label for="status" class="col-sm-4 control-label">Status</label>
                                <div class="col-sm-8">
                                    <select name="status" id="status" class="form-control">
                                        <option value="open" <?php echo ($ticket['status'] == 'open') ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo ($ticket['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo ($ticket['status'] == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo ($ticket['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <?php if($isAdmin): ?>
                            <div class="form-group">
                                <label for="assigned_to" class="col-sm-4 control-label">Assign To</label>
                                <div class="col-sm-8">
                                    <select name="assigned_to" id="assigned_to" class="form-control">
                                        <option value="">-- Unassigned --</option>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo ($ticket['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname'] . ' (' . $user['username'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <input type="hidden" name="action" value="update_ticket">
                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <button type="submit" class="btn btn-primary btn-block">Update Ticket</button>
                                </div>
                            </div>
                        </form>

                        <?php if($isAdmin): ?>
                        <hr>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $ticketId); ?>" method="post">
                            <input type="hidden" name="action" value="delete_ticket">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.');">
                                        <i class="fa fa-trash"></i> Delete Ticket
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ticket History -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-history"></i> Ticket History</h3>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <ul class="list-unstyled" style="margin-bottom: 0;">
                            <?php if($history->num_rows > 0): ?>
                                <?php while($item = $history->fetch_assoc()): ?>
                                    <li class="history-item">
                                        <div class="history-date"><?php echo date('M d, Y g:i A', strtotime($item['changed_at'])); ?></div>
                                        <div>
                                            <?php echo htmlspecialchars($item['firstname'] . ' ' . $item['lastname']); ?>
                                            <?php
                                                switch($item['field_changed']) {
                                                    case 'created':
                                                        echo 'created this ticket';
                                                        break;
                                                    case 'status':
                                                        echo 'changed status from "' . htmlspecialchars($item['old_value']) . '" to "' . htmlspecialchars($item['new_value']) . '"';
                                                        break;
                                                    case 'assigned_to':
                                                        echo 'changed assignment from ' . htmlspecialchars($item['old_value']) . ' to ' . htmlspecialchars($item['new_value']);
                                                        break;
                                                    default:
                                                        echo 'updated ' . htmlspecialchars($item['field_changed']) . ' from "' . htmlspecialchars($item['old_value']) . '" to "' . htmlspecialchars($item['new_value']) . '"';
                                                }
                                            ?>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="history-item">No history available.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <!-- Add the timer container and script here -->
    <div id="session-timer-container" data-remaining-time="<?php echo $_SESSION['timeout_remaining']; ?>">
        <span class="session-timer-label">Session expires in:</span>
        <span id="session-timer">15:00</span>
    </div>
    <script src="session_timer.js"></script>
</body>
</html>