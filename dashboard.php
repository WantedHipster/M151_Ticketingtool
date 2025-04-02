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

// Check if we need to update session with role information
if (!isset($_SESSION["role"])) {
    $userQuery = "SELECT role FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION["role"] = $row["role"];
        $isAdmin = $_SESSION["role"] === "admin";
    }
    $stmt->close();
}

// Get tickets based on user role
if ($isAdmin) {
    // Admins can see all tickets
    $ticketQuery = "SELECT t.*, 
                        u_creator.username AS creator_username,
                        u_assigned.username AS assigned_username
                   FROM tickets t
                   LEFT JOIN users u_creator ON t.created_by = u_creator.id
                   LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
                   ORDER BY 
                    CASE 
                        WHEN t.status = 'open' THEN 1
                        WHEN t.status = 'in_progress' THEN 2
                        WHEN t.status = 'resolved' THEN 3
                        WHEN t.status = 'closed' THEN 4
                    END,
                    CASE 
                        WHEN t.priority = 'critical' THEN 1
                        WHEN t.priority = 'high' THEN 2
                        WHEN t.priority = 'medium' THEN 3
                        WHEN t.priority = 'low' THEN 4
                    END,
                    t.created_at DESC";
    $stmt = $mysqli->prepare($ticketQuery);
} else {
    // Regular users can only see tickets they created or are assigned to
    $ticketQuery = "SELECT t.*, 
                        u_creator.username AS creator_username,
                        u_assigned.username AS assigned_username
                   FROM tickets t
                   LEFT JOIN users u_creator ON t.created_by = u_creator.id
                   LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
                   WHERE t.created_by = ? OR t.assigned_to = ?
                   ORDER BY 
                    CASE 
                        WHEN t.status = 'open' THEN 1
                        WHEN t.status = 'in_progress' THEN 2
                        WHEN t.status = 'resolved' THEN 3
                        WHEN t.status = 'closed' THEN 4
                    END,
                    CASE 
                        WHEN t.priority = 'critical' THEN 1
                        WHEN t.priority = 'high' THEN 2
                        WHEN t.priority = 'medium' THEN 3
                        WHEN t.priority = 'low' THEN 4
                    END,
                    t.created_at DESC";
    $stmt = $mysqli->prepare($ticketQuery);
    $stmt->bind_param("ii", $userId, $userId);
}

$stmt->execute();
$tickets = $stmt->get_result();
$stmt->close();

// Get all available users for assignment (admins only)
$users = [];
if ($isAdmin) {
    $userQuery = "SELECT id, username, firstname, lastname FROM users ORDER BY username";
    $userResult = $mysqli->query($userQuery);
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticketing System - Dashboard</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

     <!-- Add the timer CSS here -->
     <link rel="stylesheet" href="session_timer.css">
    <style>
        .ticket-row:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .priority-critical {
            background-color: #ffdddd;
        }
        .priority-high {
            background-color: #fff6dd;
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
                    <li class="active"><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
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
        <?php if(isset($_GET['delete_success']) && $_GET['delete_success'] == 1): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Ticket was deleted successfully.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1>
                        <i class="fa fa-ticket"></i> Your Tickets
                        <a href="create_ticket.php" class="btn btn-success pull-right">
                            <i class="fa fa-plus"></i> Create New Ticket
                        </a>
                    </h1>
                </div>
                
                <?php if ($tickets->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created By</th>
                                    <th>Assigned To</th>
                                    <th>Created</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                    <tr class="ticket-row <?php echo 'priority-' . htmlspecialchars($ticket['priority']); ?>" 
                                        onclick="window.location='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                        <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
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
                                        <td>
                                            <?php 
                                                switch($ticket['priority']) {
                                                    case 'low': echo '<span class="label label-default">Low</span>'; break;
                                                    case 'medium': echo '<span class="label label-info">Medium</span>'; break;
                                                    case 'high': echo '<span class="label label-warning">High</span>'; break;
                                                    case 'critical': echo '<span class="label label-danger">Critical</span>'; break;
                                                    default: echo htmlspecialchars($ticket['priority']);
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($ticket['creator_username']); ?></td>
                                        <td>
                                            <?php 
                                                if ($ticket['assigned_username']) {
                                                    echo htmlspecialchars($ticket['assigned_username']);
                                                } else {
                                                    echo '<em>Unassigned</em>';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($ticket['updated_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> No tickets found</h4>
                        <p>You don't have any tickets yet. Create a new ticket to get started.</p>
                        <p>
                            <a href="create_ticket.php" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Create New Ticket
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
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