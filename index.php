<?php
// Include database connection using relative path
require_once __DIR__ . '/db_connect.php';

// Initialize variables
$error = $message = '';
$login_username = '';

// Security function to sanitize input
function sanitizeInput($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == "POST"){
  // Process login attempt if the login form was submitted
  if(isset($_POST["login_submit"])){
    // Sanitize user inputs
    $login_username = isset($_POST["login_username"]) ? sanitizeInput($_POST["login_username"]) : '';
    $login_password = isset($_POST["login_password"]) ? $_POST["login_password"] : ''; // Don't sanitize password before verification
    
    // Validate that fields are not empty
    if(empty($login_username)) {
      $error = "Bitte geben Sie Ihren Benutzernamen ein.";
    } elseif(empty($login_password)) {
      $error = "Bitte geben Sie Ihr Passwort ein.";
    } else {
      // Verify credentials against database
      try {
        // Prepare the SQL statement
        $stmt = $mysqli->prepare("SELECT id, username, password, firstname FROM users WHERE username = ?");
        $stmt->bind_param("s", $login_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
          // User found
          $user = $result->fetch_assoc();
          
          // Verify password
          if(password_verify($login_password, $user['password'])) {
            // Password is correct, start a session
            session_start();
            
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["firstname"] = $user["firstname"];
            
            // Redirect to welcome page
            header("location: welcome.php");
            exit;
          } else {
            // Password is not valid
            $error = "Ungültiger Benutzername oder Passwort.";
          }
        } else {
          // Username doesn't exist
          $error = "Ungültiger Benutzername oder Passwort.";
        }
        
        $stmt->close();
      } catch (Exception $e) {
        $error = "Datenbankfehler: " . $e->getMessage();
      }
    }
  }
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
    <style>
      .card {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 20px;
        margin: 20px 0;
        background-color: #f9f9f9;
      }
      .option-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
      }
      .welcome-text {
        margin-bottom: 30px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <h1 class="text-center">Willkommen</h1>
          
          <?php
            // Display messages if any
            if(strlen($error)){
              echo "<div class=\"alert alert-danger\" role=\"alert\">" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>";
            } elseif (strlen($message)){
              echo "<div class=\"alert alert-success\" role=\"alert\">" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
            }
          ?>
          
          <div class="welcome-text">
            <p class="lead text-center">
              Bitte wählen Sie, ob Sie sich registrieren oder anmelden möchten.
            </p>
          </div>
          
          <div class="row">
            <!-- Registration Card -->
            <div class="col-md-6">
              <div class="card">
                <h3><i class="fa fa-user-plus"></i> Registrieren</h3>
                <p>Erstellen Sie ein neues Konto, um diesen Dienst zu nutzen.</p>
                <div class="text-center">
                  <a href="register.php" class="btn btn-primary btn-lg">Registrieren</a>
                </div>
              </div>
            </div>
            
            <!-- Login Card -->
            <div class="col-md-6">
              <div class="card">
                <h3><i class="fa fa-sign-in"></i> Anmelden</h3>
                <p>Melden Sie sich mit Ihrem bestehenden Konto an.</p>
                <form action="" method="post">
                  <div class="form-group">
                    <label for="login_username">Benutzername</label>
                    <input type="text" name="login_username" class="form-control" id="login_username" 
                           placeholder="Benutzername eingeben" 
                           required
                           value="<?php echo htmlspecialchars($login_username ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           pattern="^[A-Za-z0-9]{6,}$"
                           title="Benutzername muss mindestens 6 Zeichen lang sein (nur Buchstaben und Zahlen)">
                  </div>
                  <div class="form-group">
                    <label for="login_password">Passwort</label>
                    <input type="password" name="login_password" class="form-control" id="login_password" 
                           placeholder="Passwort eingeben" 
                           required
                           pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?])(?!.*[äöüÄÖÜß]).{8,}$"
                           title="Passwort muss mindestens 8 Zeichen lang sein und Gross- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten. Keine Umlaute erlaubt.">
                  </div>
                  <div class="text-center">
                    <button type="submit" name="login_submit" value="login" class="btn btn-success btn-lg">Anmelden</button>
                  </div>
                </form>
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
    
    <!-- Login form validation script using regex -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.querySelector('form[action=""]');
        if (!loginForm) return;
        
        // Define regex patterns
        const patterns = {
          login_username: /^[A-Za-z0-9]{6,}$/,
          login_password: /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?!.*[äöüÄÖÜß]).{8,}$/
        };
        
        // Error messages
        const errorMessages = {
          login_username: 'Benutzername muss mindestens 6 Zeichen lang sein und darf nur aus Buchstaben und Zahlen bestehen.',
          login_password: 'Passwort muss mindestens 8 Zeichen lang sein und Gross- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten. Keine Umlaute erlaubt.'
        };
        
        // Create validation message container
        const validationDiv = document.createElement('div');
        validationDiv.id = 'login-validation-error';
        validationDiv.className = 'alert alert-danger';
        validationDiv.style.display = 'none';
        
        // Insert it before the form
        loginForm.parentNode.insertBefore(validationDiv, loginForm);
        
        loginForm.addEventListener('submit', function(event) {
          let hasError = false;
          let errorMessage = '';
          
          // Get all form inputs to validate
          for (const inputId of Object.keys(patterns)) {
            const input = document.getElementById(inputId);
            if (!input) continue;
            
            const value = input.value.trim();
            if (!value || !patterns[inputId].test(value)) {
              input.classList.add('is-invalid');
              errorMessage += errorMessages[inputId] + '<br>';
              hasError = true;
            } else {
              input.classList.remove('is-invalid');
            }
          }
          
          if (hasError) {
            event.preventDefault();
            document.getElementById('login-validation-error').innerHTML = errorMessage;
            document.getElementById('login-validation-error').style.display = 'block';
          }
        });
      });
    </script>
    
    <style>
      .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
      }
    </style>
  </body>
</html>