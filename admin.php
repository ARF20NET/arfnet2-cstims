<?php

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /login.php");
    exit;
}

$username = $_SESSION["username"];
$type = $_SESSION["type"];

if ($type != "admin") die("Permission denied.");

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

// Get services
$sql = "SELECT id, service, name, client FROM orders";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get tickets
$sql = "SELECT id, `order`, subject FROM tickets";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tickets = $result->fetch_all(MYSQLI_ASSOC);

// Get invoices
$sql = "SELECT id, client, `desc`, amount, date, status FROM invoices";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoices = $result->fetch_all(MYSQLI_ASSOC);

function getservicebyid($id) {
    global $services;
    foreach ($services as $service) {
        if ($service["id"] == $id) {
            return $service;
        }
    }
}

function getclientbyid($id) {
    global $users;
    foreach ($users as $client) {
        if ($client["id"] == $id) {
            return $client;
        }
    }
}

function getorderbyid($id) {
    global $orders;
    foreach ($orders as $order) {
        if ($order["id"] == $id) {
            return $order;
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
                    <h2 class="center">ARFNET Client Service Ticket and Invoice Management System</h2>
                    <h3><?php echo strtoupper($type[0]).substr($type, 1); ?> panel</h3>
                    <div class="row">
                        <div class="col2">
                            <h3>Users</h3>
                            <table>
                                <tr><th>user</th><th>type</th><th>status</th></tr>
                                <?php
                                foreach ($users as $user) {
                                    echo "<tr><td>".$user["username"]."</td><td>".$user["type"]."</td><td>".$user["status"]."</tr>\n";
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
                                    echo "<tr><td>".$service["name"]."</td><td>".$service["type"]."</td><td>".$service["billing"]."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col2">
                            <h3>Orders</h3>
                            <table>
                                <tr><th>service</th><th>instance</th><th>client</th></tr>
                                <?php
                                foreach ($orders as $order) {
                                    echo "<tr><td>".getservicebyid($order["service"])["name"]."</td><td>".$order["name"]."</td><td>".getclientbyid($order["client"])["username"]."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col2">
                            <h3>Tickets</h3>
                            <table>
                                <tr><th>order</th><th>client</th><th>subject</th></tr>
                                <?php
                                foreach ($tickets as $ticket) {
                                    echo "<tr><td>".getorderbyid($ticket["order"])["name"]."</td><td>".getclientbyid(getorderbyid($ticket["order"])["client"])["username"]."</td><td>".$ticket["subject"]."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                        <div class="col2">
                            <h3>Invoices</h3>
                            <table>
                                <tr><th>client</th><th>amount</th></tr>
                                <?php
                                foreach ($invoices as $invoice) {
                                    echo "<tr><td>".getclientbyid($invoice["client"])["username"]."</td><td>".number_format($invoice["amount"], 2, '.', '')."€</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</a></h2>
                    <h3><a href="/manageusers.php">Manage users</a></h2>
                    <h3><a href="/manageservices.php">Manage services</a></h2>
                    <h3><a href="/manageorders.php">Manage orders</a></h2>
                    <h3><a href="/managetickets.php">Manage tickets</a></h2>
                    <h3><a href="/manageinvoices.php">Manage invoices</a></h2>
                    <h3><a href="/publishannouncement.php">Publish announcement</a></h2>
                </div>
            </div>
        </main>
    </body>
</html>
