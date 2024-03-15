<?php
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";
 
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if (empty($_POST["username"]))
        $username_err = "Enter a username.";
    else if (preg_match("/[a-zA-Z0-9_]+/", $_POST["username"]) != 1)
        $username_err = "Invalid username.";
    else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $_POST["username"];
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = $_POST["username"];
                }
            } else{
                echo "SQL failed. Idk, ask arf20.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if (empty($_POST["email"]))
        $email_err = "Enter a email address.";
    else if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL) === false)
        $email_err = "Invalid email address.";
    else
        $email = $_POST["email"];
    
    // Validate password
    if (empty($_POST["password"]))
        $password_err = "Enter a password.";     
    else if (strlen($_POST["password"]) < 8)
        $password_err = "Password must have at least 8 characters.";
    else if (preg_match("/[a-zA-Z0-9!@^*$%&)(=+çñÇ][}{\-.,_:;]+/", $_POST["password"]) != false)
        $password_err = "Password must be in the format [a-zA-Z0-9!@^*$%&)(=+çñÇ][}{-.,_:;].";
    else
        $password = $_POST["password"];
    
    // Validate confirm password
    if (empty($password_err) && ($password != $_POST["confirm_password"])) {
        $confirm_password_err = "Password did not match.";
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email, verifycode) VALUES (?, ?, ?, ?)";
         
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssss", $param_username, $param_password, $param_email, $param_verifycode);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            $param_verifycode = base64_encode(random_bytes(12)); // code of size 16
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Send verification email
                
                // Redirect to login page
                header("location: login.php");
            } else {
                echo "SQL failed. Idk ask arf20.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>CSTIMS Register</title>
        <link rel="stylesheet" type="text/css" href="/style.css">
    </head>
    <body>
        <header><a href="https://arf20.com/">
            <img src="arfnet_logo.png" width="64"><span class="title"><strong>ARFNET</strong></span>
        </a></header>
        <hr>
        <main>
            <div class="wrapper">
                <h2>CSTIMS Register</h2>
                <p>For those who don't want their data sold</p>
                <form action="/register.php" method="post">
                    <div class="form-group row <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                        <div class="column"><label>Username</label></div>
                        <div class="column"><input type="text" name="username" class="form-control" pattern="[a-zA-Z0-9_]+" value="<?php echo $username; ?>"></div>
                        <span class="help-block"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group row <?php echo (!empty($mail_err)) ? 'has-error' : ''; ?>">
                        <div class="column"><label>Email address</label></div>
                        <div class="column"><input type="email" name="email" class="form-control" value="<?php echo $email; ?>"></div>
                        <span class="help-block"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group row <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                        <div class="column"><label>Password</label></div>
                        <div class="column"><input type="password" name="password" class="form-control" pattern="[a-zA-Z0-9!@^*$%&)(=+çñÇ[]{}-.,_:;]+" value="<?php echo $password; ?>"></div>
                        <span class="help-block"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group row <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                        <div class="column"><label>Confirm Password</label></div>
                        <div class="column"><input type="password" name="confirm_password" class="form-control" pattern="[a-zA-Z0-9!@^*$%&)(=+çñÇ[]{}-.,_:;]+" value="<?php echo $confirm_password; ?>"></div>
                        <span class="help-block"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                    </div>
                    <p><a href="login.php">Login</a>.</p>
                </form>
            </div>
        </main>
    </body>
</html>
