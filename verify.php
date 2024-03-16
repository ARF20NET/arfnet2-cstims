<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: /".$_SESSION["type"].".php");
    exit;
}
 
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$code = "";
$code_err = ""; 
$verification_success = false;
 
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $code_err = "Invalid code.";
    if (isset($_GET["code"]) && (strlen($_GET["code"]) == 16)) {
        $code_err = "";
        $code = $_GET["code"];
    }
    
    // Validate credentials
    if (empty($code_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, status, type FROM users WHERE verifycode = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_code);
            
            // Set parameters
            $param_code = $code;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $status, $type);
                    if (mysqli_stmt_fetch($stmt)){
                        if ($status == "unverified") {
                            // set verified
                            $sql = "UPDATE users SET status = 'verified' WHERE id = ?";
                            if ($stmt = mysqli_prepare($link, $sql)) {
                                mysqli_stmt_bind_param($stmt, "s", $param_id);
                                $param_id = $id;
                                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) == 1) {
                                    $verification_success = true;
                                } else {
                                    echo "SQL error, ask arf20.";
                                }
                            }
                        } else {
                            $code_err = "Already verified.";
                        }
                    }
                } else {
                    // Display an error message if username doesn't exist
                    $code_err = "Code does not exist.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
        <title>CSTIMS Login</title>
        <link rel="stylesheet" type="text/css" href="/style.css">
    </head>
    <body>
        <header><a href="https://arf20.com/">
            <img src="arfnet_logo.png" width="64"><span class="title"><strong>ARFNET</strong></span>
        </a></header>
        <hr>
        <main>
            <div class="wrapper">
                <h2>CSTIMS Verification</h2>
                <?php
                if ($verification_success) echo "Verification successful, welcome to ARFNET $username.";
                else echo "Verification failed: ".$code_err;
                ?>
            </div>
        </main>
    </body>
</html>
