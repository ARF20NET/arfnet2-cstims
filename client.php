<?php

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /login.php");
    exit;
}

$clientid = $_SESSION["id"];
$username = $_SESSION["username"];
$type = $_SESSION["type"];

require_once "config.php";

// Get orders
$sql = "SELECT id, service, name, billing, comments FROM orders WHERE client = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $param_client);
$param_client = $clientid;
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get services
$sql = "SELECT id, name, type, billing FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

function getservicebyid($id) {
    global $services;
    foreach ($services as $service) {
        if ($service["id"] == $id) {
            return $service;
        }
    }
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
                    <h2>ARFNET Client Service Ticket and Invoice Management System</h2>
                    <h3><?php echo strtoupper($type[0]).substr($type, 1); ?> panel</h3>
                    <div class="row">
                        <div class="col5">
                            <h3>Active services</h3>
                            <table>
                                <tr><th>service</th><th>instance</th><th>billing</th><th>comments</th></tr>
                                <?php
                                foreach ($orders as $order) {
                                    echo "<tr><td>".getservicebyid($order["service"])["name"]."</td><td>".$order["name"]."</td><td>".$order["billing"]."</td><td><pre>".$order["comments"]."</pre></td></tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col5">
                            <h3>Tickets</h3>
                            <!-- TODO PHP list of services -->
                        </div>
                    </div>
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</h2>
                    <h3><a href="/order.php">Order a new service</h2>
                    <h3><a href="/openticket.php">Open ticket</h2>
                </div>
            </div>
        </main>
    </body>
</html>
