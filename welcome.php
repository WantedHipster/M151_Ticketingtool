<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Willkommen</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <div class="jumbotron">
            <h1>Willkommen, <?php echo htmlspecialchars($_SESSION["firstname"], ENT_QUOTES, 'UTF-8'); ?>!</h1>
            <p class="lead">Sie haben sich erfolgreich angemeldet.</p>
            <hr class="my-4">
            <p>Hier ist Ihr persönlicher Bereich.</p>
            <p class="lead">
              <a class="btn btn-primary btn-lg" href="logout.php" role="button">Abmelden</a>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  </body>
</html>