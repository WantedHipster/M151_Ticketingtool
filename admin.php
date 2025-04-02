<?php
// Initialize the session
include 'session_config.php';

// Check if the user is logged in and is an admin, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Initialize variables
$error = $success = "";

// Process user role update
if(isset($_POST["action"]) && $_POST["action"] == "update_role"){
    $userId = intval($_POST["user_id"]);
    $newRole = $_POST["role"];
    
    // Validate role
    if($newRole != "user" && $newRole != "admin"){
        $error = "Invalid role selected.";
    } else {
        // Verify user exists
        $userCheck = $mysqli->prepare("SELECT id, role FROM users WHERE id = ?");
        $userCheck->bind_param("i", $userId);
        $userCheck->execute();
        $userResult = $userCheck->get_result();
        
        if($userResult->num_rows == 0){
            $error = "User not found.";
        } else {
            $userInfo = $userResult->fetch_assoc();
            $oldRole = $userInfo["role"];
            
            // Don't allow changing your own role
            if($userId == $_SESSION["id"]){
                $error = "You cannot change your own role.";
            } else {
                // Update user role
                $updateRole = $mysqli->prepare("UPDATE users SET role = ? WHERE id = ?");
                $updateRole->bind_param("si", $newRole, $userId);
                
                if($updateRole->execute()){
                    $success = "User role updated successfully.";
                } else {
                    $error = "Failed to update user role.";
                }
                $updateRole->close();
            }
        }
        $userCheck->close();
    }
}

// Get all users
$usersQuery = "SELECT id, username, firstname, lastname, email, role, created_at FROM users ORDER BY username";
$users = $mysqli->query($usersQuery);

// Get ticket statistics
$statsQuery = "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
                SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_tickets,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_tickets,
                SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned_tickets
              FROM tickets";
$statsResult = $mysqli->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Get tickets per user
$userTicketsQuery = "SELECT 
                        u.username, 
                        COUNT(t.id) as ticket_count 
                    FROM users u
                    LEFT JOIN tickets t ON u.id = t.created_by
                    GROUP BY u.id
                    ORDER BY ticket_count DESC
                    LIMIT 10";
$userTickets = $mysqli->query($userTicketsQuery);

// Get recent tickets
$recentTicketsQuery = "SELECT t.id, t.title, t.status, t.priority, t.created_at, u.username
                      FROM tickets t
                      JOIN users u ON t.created_by = u.id
                      ORDER BY t.created_at DESC
                      LIMIT 10";
$recentTickets = $mysqli->query($recentTicketsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Ticketing System</title>

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
        .stat-box {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-label {
            color: #777;
            text-transform: uppercase;
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
                    <li class="active"><a href="admin.php"><i class="fa fa-cogs"></i> Admin Panel</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#">
                            <i class="fa fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                            <span class="user-role-badge admin-role-badge">Admin</span>
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
        
        <div class="page-header">
            <h1><i class="fa fa-cogs"></i> Admin Panel</h1>
        </div>
        
        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-box text-center">
                    <div class="stat-number"><?php echo $stats['total_tickets']; ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box text-center">
                    <div class="stat-number"><?php echo $stats['open_tickets']; ?></div>
                    <div class="stat-label">Open Tickets</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box text-center">
                    <div class="stat-number"><?php echo $stats['in_progress_tickets']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box text-center">
                    <div class="stat-number"><?php echo $stats['unassigned_tickets']; ?></div>
                    <div class="stat-label">Unassigned</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- User Management -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-users"></i> User Management</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if($user['role'] == 'admin'): ?>
                                                    <span class="label label-danger">Admin</span>
                                                <?php else: ?>
                                                    <span class="label label-default">User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($user['id'] != $_SESSION['id']): ?>
                                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <select name="role" class="form-control input-sm">
                                                            <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                                            <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                                    </form>
                                                <?php else: ?>
                                                    <em>Current User</em>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Tickets -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-ticket"></i> Recent Tickets</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Created By</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($ticket = $recentTickets->fetch_assoc()): ?>
                                        <tr onclick="window.location='view_ticket.php?id=<?php echo $ticket['id']; ?>'" style="cursor: pointer;">
                                            <td><?php echo $ticket['id']; ?></td>
                                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                            <td>
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
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ticket Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Tickets by User</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Tickets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($userTicket = $userTickets->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($userTicket['username']); ?></td>
                                            <td><?php echo $userTicket['ticket_count']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-pie-chart"></i> Ticket Statistics</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Status Breakdown</h4>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <span class="badge"><?php echo $stats['open_tickets']; ?></span>
                                        Open
                                    </li>
                                    <li class="list-group-item">
                                        <span class="badge"><?php echo $stats['in_progress_tickets']; ?></span>
                                        In Progress
                                    </li>
                                    <li class="list-group-item">
                                        <span class="badge"><?php echo $stats['resolved_tickets']; ?></span>
                                        Resolved
                                    </li>
                                    <li class="list-group-item">
                                        <span class="badge"><?php echo $stats['closed_tickets']; ?></span>
                                        Closed
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h4>Priority Breakdown</h4>
                                <ul class="list-group">
                                    <li class="list-group-item list-group-item-danger">
                                        <span class="badge"><?php echo $stats['critical_tickets']; ?></span>
                                        Critical
                                    </li>
                                    <li class="list-group-item list-group-item-warning">
                                        <span class="badge"><?php echo $stats['high_tickets']; ?></span>
                                        High
                                    </li>
                                    <li class="list-group-item">
                                        <span class="badge"><?php echo $stats['unassigned_tickets']; ?></span>
                                        Unassigned
                                    </li>
                                </ul>
                            </div>
                        </div>
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