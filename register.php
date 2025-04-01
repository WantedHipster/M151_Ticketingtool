<?php
// Include database connection using relative path
require_once __DIR__ . '/db_connect.php';

// Initialisierung
$error = $message = '';
$firstname = $lastname = $email = $username = '';

// Security function to sanitize input
function sanitizeInput($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

// Wurden Daten mit "POST" gesendet?
if($_SERVER['REQUEST_METHOD'] == "POST"){
  /** Ausgabe des gesamten $_POST Arrays zum debuggen
   * muss vor der Verwendung auskommentiert werden! */ 
  /**
  echo "<pre>";
  print_r($_POST);
  echo "</pre>";
  */

  // Validate all inputs
  if(isset($_POST["vorname"])){
    $firstname = sanitizeInput($_POST["vorname"]);
    if(empty($firstname) || strlen($firstname) > 30){
      $error .= "Vorname entspricht nicht den Vorgaben<br>";
    }
  } else {
    $error .= "Vorname fehlt<br>";
  }

  if (isset($_POST["lastname"])){
    $lastname = sanitizeInput($_POST["lastname"]);
    if(empty($lastname) || strlen($lastname) > 30){
      $error .= "Nachname entspricht nicht den Vorgaben<br>";
    }
  } else {
    $error .= "Nachname fehlt<br>";
  }

  if (isset($_POST["email"])){
    $email = sanitizeInput($_POST["email"]);
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
      $error .= "E-Mail entspricht nicht den Vorgaben<br>";
    }
  } else {
    $error .= "E-Mail fehlt<br>";
  }

  if (isset($_POST["username"])){
    $username = sanitizeInput($_POST["username"]);
    if(empty($username) || strlen($username) < 6){
      $error .= "Benutzername entspricht nicht den Vorgaben<br>";
    } else {
      // Check if username already exists
      $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();
      if($result->num_rows > 0){
        $error .= "Benutzername ist bereits vergeben<br>";
      }
      $stmt->close();
    }
  } else {
    $error .= "Benutzername fehlt<br>";
  }

  // Password validation
  if (isset($_POST["password"])){
    $password = $_POST["password"]; // Don't sanitize password before hashing
    
    // Password pattern: at least one uppercase, one lowercase, one number, one special character, no umlauts, min 8 chars
    $passwordPattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?])(?!.*[äöüÄÖÜß]).{8,}$/';
    
    if(empty($password) || !preg_match($passwordPattern, $password)){
      $error .= "Passwort entspricht nicht den Vorgaben<br>";
    }
  } else {
    $error .= "Passwort fehlt<br>";
  }

  // If no errors, insert into database
  if(empty($error)){
    try {
      // Create users table if it doesn't exist
      $createTableQuery = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(30) NOT NULL,
        lastname VARCHAR(30) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )";
      $mysqli->query($createTableQuery);
      
      // Hash the password
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      
      // Prepare the SQL statement
      $stmt = $mysqli->prepare("INSERT INTO users (firstname, lastname, email, username, password) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $firstname, $lastname, $email, $username, $hashed_password);
      
      // Execute the statement
      if($stmt->execute()){
        $message = "Registrierung erfolgreich! Sie können sich jetzt anmelden.";
        // Clear form fields after successful registration
        $firstname = $lastname = $email = $username = '';
      } else {
        $error = "Bei der Registrierung ist ein Fehler aufgetreten: " . $stmt->error;
      }
      
      $stmt->close();
    } catch (Exception $e) {
      $error = "Datenbankfehler: " . $e->getMessage();
    }
  }

  // keine Fehler vorhanden
  if(empty($error)){
    $message = "Keine Fehler vorhanden";
  }
}



?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrierung</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  </head>
  <body>

    <div class="container">
      <h1>Registrierung</h1>
      <p>
        Bitte registrieren Sie sich, damit Sie diesen Dienst benutzen können.
      </p>
      <?php
        // Ausgabe der Fehlermeldungen
        if(strlen($error)){
          echo "<div class=\"alert alert-danger\" role=\"alert\">" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>";
        } elseif (strlen($message)){
          echo "<div class=\"alert alert-success\" role=\"alert\">" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
        }
      ?>
      <div id="validation-error" class="alert alert-danger" role="alert"></div>
      <form action="" method="post">
        <!-- Clientseitige Validierung: vorname -->
        <div class="form-group">
          <label for="firstname">Vorname *</label>
          <input type="text" name="vorname" class="form-control" id="firstname"
                  value="<?php echo htmlspecialchars($firstname) ?>"
                  placeholder="Geben Sie Ihren Vornamen an."
                  required
                  maxlength="30"
                  pattern="^.{1,30}$"
                  title="Vorname darf nicht leer sein und maximal 30 Zeichen haben">
        </div>
        <!-- Clientseitige Validierung: nachname -->
        <div class="form-group">
          <label for="lastname">Nachname *</label>
          <input type="text" name="lastname" class="form-control" id="lastname"
                  value="<?php echo htmlspecialchars($lastname) ?>"
                  placeholder="Geben Sie Ihren Nachnamen an"
                  required
                  maxlength="30"
                  pattern="^.{1,30}$"
                  title="Nachname darf nicht leer sein und maximal 30 Zeichen haben">
        </div>
        <!-- Clientseitige Validierung: email -->
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" name="email" class="form-control" id="email"
                  value="<?php echo htmlspecialchars($email) ?>"
                  placeholder="Geben Sie Ihre Email-Adresse an."
                  required
                  pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                  title="Bitte geben Sie eine gültige E-Mail-Adresse ein">
        </div>
        <!-- Clientseitige Validierung: benutzername -->
        <div class="form-group">
          <label for="username">Benutzername *</label>
          <input type="text" name="username" class="form-control" id="username"
                  value="<?php echo htmlspecialchars($username) ?>"
                  placeholder="Gross- und Kleinbuchstaben, min 6 Zeichen."
                  required
                  pattern="^[A-Za-z0-9]{6,}$"
                  title="Benutzername muss mindestens 6 Zeichen lang sein und darf nur aus Buchstaben und Zahlen bestehen">
        </div>
        <!-- Clientseitige Validierung: password -->
        <div class="form-group">
          <label for="password">Password *</label>
          <input type="password" name="password" class="form-control" id="password"
                  placeholder="Gross- und Kleinbuchstaben, Zahlen, Sonderzeichen, min. 8 Zeichen, keine Umlaute"
                  required
                  pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?])(?!.*[äöüÄÖÜß]).{8,}$"
                  title="Passwort muss mindestens 8 Zeichen lang sein und Gross- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten. Keine Umlaute erlaubt.">
        </div>
        <button type="submit" name="button" value="submit" class="btn btn-info">Senden</button>
        <button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
        <a href="index.php" class="btn btn-default">Zurück zur Startseite</a>
      </form>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    
    <!-- Client-side validation script using regex -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        // Define regex patterns
        const patterns = {
          firstname: /^.{1,30}$/, // 1-30 characters
          lastname: /^.{1,30}$/, // 1-30 characters
          email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/, // Standard email regex
          username: /^[A-Za-z0-9]{6,}$/, // At least 6 alphanumeric characters
          password: /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?!.*[äöüÄÖÜß]).{8,}$/ // Complex password without umlauts
        };
        
        // Error messages
        const errorMessages = {
          firstname: 'Vorname darf nicht leer sein und maximal 30 Zeichen haben.',
          lastname: 'Nachname darf nicht leer sein und maximal 30 Zeichen haben.',
          email: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
          username: 'Benutzername muss mindestens 6 Zeichen lang sein und darf nur aus Buchstaben und Zahlen bestehen.',
          password: 'Passwort muss mindestens 8 Zeichen lang sein und Gross- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten. Keine Umlaute erlaubt.'
        };
        
        // Validate function using regex
        function validateInput(input, pattern, errorMsg) {
          const value = input.value.trim();
          if (!value || !pattern.test(value)) {
            input.classList.add('is-invalid');
            return errorMsg;
          } else {
            input.classList.remove('is-invalid');
            return '';
          }
        }
        
        form.addEventListener('submit', function(event) {
          let hasError = false;
          let errorMessage = '';
          
          // Get all form elements to validate
          const fields = {
            firstname: document.getElementById('firstname'),
            lastname: document.getElementById('lastname'),
            email: document.getElementById('email'),
            username: document.getElementById('username'),
            password: document.getElementById('password')
          };
          
          // Validate each field
          for (const [key, field] of Object.entries(fields)) {
            const error = validateInput(field, patterns[key], errorMessages[key]);
            if (error) {
              errorMessage += error + '<br>';
              hasError = true;
            }
          }
          
          if (hasError) {
            event.preventDefault();
            document.getElementById('validation-error').innerHTML = errorMessage;
            document.getElementById('validation-error').style.display = 'block';
          }
        });
        
        // Live validation as user types
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
          input.addEventListener('input', function() {
            const fieldName = this.id;
            if (patterns[fieldName]) {
              if (patterns[fieldName].test(this.value.trim())) {
                this.classList.remove('is-invalid');
              } else {
                this.classList.add('is-invalid');
              }
            }
          });
        });
      });
    </script>
    
    <style>
      .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
      }
      #validation-error {
        display: none;
      }
    </style>
  </body>
</html>