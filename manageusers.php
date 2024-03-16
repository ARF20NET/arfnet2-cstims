<?php

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /login.php");
    exit;
}

$username = $_SESSION["username"];
$type = $_SESSION["type"];

require_once "config.php";

// Get users
$sql = "SELECT id, username, password, email, verifycode, status, type, regdate FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

// actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param_id);
    $param_id = $_GET["del"];
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) != 1) {
        echo "SQL error.";
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
}

?>

<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" href="/style.css">
        <title>ARFNET CSTIMS</title>
    </head>
    <body>
        <header><a href="https://arf20.com/">
            <img src="arfnet_logo.png" width="64"><span class="title"><strong>ARFNET</strong></span>
        </a></header>
        <hr>
        <main>
            <div class="row">
                <div class="col8">
                    <h2 class="center">ARFNET Client Service Ticket and Invoice Management System</h2>
                    <h3><?php echo strtoupper($type[0]).substr($type, 1); ?> panel</h3>
                    
                    <h3>Users</h3>
                    <table>
                        <tr><th>id</th><th>user</th><th>password</th><th>email</th><th>verifycode</th><th>type</th><th>regdate</th><th>status</th><th>action</th></tr>
                        <?php
                        foreach ($users as $user) {
                            echo "<tr><td>".$user['id']."</td>"
                            ."<td>".$user['username']."</td>"
                            ."<td>".$user['password']."</td>"
                            ."<td>".$user['email']."</td>"
                            ."<td>".$user['verifycode']."</td>"
                            ."<td>".$user['type']."</td>"
                            ."<td>".$user['regdate']."</td>"
                            ."<td>".$user['status']."</td>"
                            ."<td><a href=\"?del=".$user['id']."\">del</a> <a href=\"?edit=".$user['id']."\">edit</a></td></tr>\n";
                        }
                        ?>
                    </table>
                        
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</h2>
                    <h3><a href="/admin.php">Back to admin panel</h2>
                </div>
            </div>
        </main>
    </body>
</html>

