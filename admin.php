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
$sql = "SELECT id, username, status, type FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get services
$sql = "SELECT id, name, type, billing FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

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
                    <div class="row">
                        <div class="col2">
                            <h3>Users</h3>
                            <table>
                                <tr><th>user</th><th>type</th><th>status</th></tr>
                                <?php
                                foreach ($users as $user) {
                                    echo "<tr><td>".$user['username']."</td><td>".$user['type']."</td><td>".$user['status']."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col2">
                            <h3>Service offerings</h3>
                            <table>
                                <tr><th>name</th><th>type</th><th>billing</th></tr>
                                <?php
                                foreach ($services as $service) {
                                    echo "<tr><td>".$service['name']."</td><td>".$service['type']."</td><td>".$service['billing']."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col2">
                            <h3>Orders</h3>
                            <!-- TODO PHP list of services -->
                        </div>
                        <div class="col2">
                            <h3>Tickets</h3>
                            <!-- TODO PHP list of services -->
                        </div>
                        <div class="col2">
                            <h3>Invoices</h3>
                            <!-- TODO PHP list of services -->
                        </div>
                    </div>
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</h2>
                    <h3><a href="/manageusers.php">Manage users</h2>
                    <h3><a href="/manageservices.php">Manage services</h2>
                    <h3><a href="/manageorders.php">Manage orders</h2>
                    <h3><a href="/managetickets.php">Manage tickets</h2>
                    <h3><a href="/manageinvoices.php">Manage invoices</h2>
                </div>
            </div>
        </main>
    </body>
</html>
