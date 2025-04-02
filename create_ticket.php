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

// Initialize variables
$title = $description = $priority = "";
$title_err = $description_err = $priority_err = "";
$success_message = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } elseif(strlen(trim($_POST["title"])) > 100){
        $title_err = "Title cannot exceed 100 characters.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate priority
    $valid_priorities = array("low", "medium", "high", "critical");
    if(empty($_POST["priority"])){
        $priority_err = "Please select a priority.";
    } elseif(!in_array($_POST["priority"], $valid_priorities)){
        $priority_err = "Invalid priority selected.";
    } else{
        $priority = $_POST["priority"];
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($description_err) && empty($priority_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO tickets (title, description, priority, created_by) VALUES (?, ?, ?, ?)";
         
        if($stmt = $mysqli->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssi", $param_title, $param_description, $param_priority, $param_created_by);
            
            // Set parameters
            $param_title = $title;
            $param_description = $description;
            $param_priority = $priority;
            $param_created_by = $userId;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Get the ticket ID
                $ticketId = $mysqli->insert_id;
                
                // Add ticket creation to history
                $historySQL = "INSERT INTO ticket_history (ticket_id, user_id, field_changed, new_value) VALUES (?, ?, ?, ?)";
                $historyStmt = $mysqli->prepare($historySQL);
                $field = "created";
                $value = "Ticket created";
                $historyStmt->bind_param("iiss", $ticketId, $userId, $field, $value);
                $historyStmt->execute();
                $historyStmt->close();
                
                // Redirect to the ticket view
                header("location: view_ticket.php?id=".$ticketId);
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
}

// Get users for assignment (admins only)
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
    <title>Create New Ticket - Ticketing System</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
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
                    <li class="active"><a href="create_ticket.php"><i class="fa fa-plus-circle"></i> Create New Ticket</a></li>
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
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="page-header">
                    <h1><i class="fa fa-plus-circle"></i> Create New Ticket</h1>
                </div>
                
                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Ticket Information</h3>
                    </div>
                    <div class="panel-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group <?php echo (!empty($title_err)) ? 'has-error' : ''; ?>">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                                <span class="help-block"><?php echo $title_err; ?></span>
                            </div>    
                            <div class="form-group <?php echo (!empty($description_err)) ? 'has-error' : ''; ?>">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                                <span class="help-block"><?php echo $description_err; ?></span>
                            </div>
                            <div class="form-group <?php echo (!empty($priority_err)) ? 'has-error' : ''; ?>">
                                <label>Priority</label>
                                <select name="priority" class="form-control" required>
                                    <option value="" <?php echo empty($priority) ? 'selected' : ''; ?>>-- Select Priority --</option>
                                    <option value="low" <?php echo ($priority == "low") ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo ($priority == "medium") ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo ($priority == "high") ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo ($priority == "critical") ? 'selected' : ''; ?>>Critical</option>
                                </select>
                                <span class="help-block"><?php echo $priority_err; ?></span>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="dashboard.php" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
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