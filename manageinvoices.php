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

// Get clients
$sql = "SELECT id, username FROM users WHERE type = 'client'";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$clients = $result->fetch_all(MYSQLI_ASSOC);

// Get invoices
$sql = "SELECT id, client, `desc`, amount, date, status FROM invoices";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoices = $result->fetch_all(MYSQLI_ASSOC);

// GET actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param_id);
    $param_id = $_GET["del"];
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) != 1) {
        echo "SQL error.";
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
}

if (isset($_GET["pdf"])) {
    // Get invoice
    $sql = "SELECT pdf FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param_id);
    $param_id = $_GET["pdf"];
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pdf = $result->fetch_all(MYSQLI_ASSOC)[0]["pdf"];
    header("Content-type: application/pdf");
    header("Content-Disposition: inline;filename=\"invoice.pdf\"");
    echo $pdf;
}

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // edit entry
    if (isset($_POST["save"])) {
        $sql = "UPDATE invoices SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_status, $param_id);
        $param_status = $_POST["status"];
        $param_id = $_POST["id"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
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

function getservicebyid($id) {
    global $services;
    foreach ($services as $service) {
        if ($service["id"] == $id) {
            return $service;
        }
    }
}

function getclientbyid($id) {
    global $clients;
    foreach ($clients as $client) {
        if ($client["id"] == $id) {
            return $client;
        }
    }
}

function getinvoicebyid($id) {
    global $invoices;
    foreach ($invoices as $invoice) {
        if ($invoice["id"] == $id) {
            return $invoice;
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
                    <h3>Orders</h3>

                    <?php
                    if (isset($_GET["edit"])) {
                        $invoice = getinvoicebyid($_GET["edit"]);
                        $client_options = $service_options = "";
                        echo "<div class=\"form\"><h3>Edit invoice ".$invoice["id"]."</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label><b>Client</b></label><br><label>".getclientbyid($invoice["client"])["username"]."</label><br>\n"
                            ."<label><b>Description</b></label><br><label>".$invoice["desc"]."</label><br>\n"
                            ."<label><b>Amount</b></label><br><label>".$invoice["amount"]."</label><br>\n"
                            ."<label><b>Date</b></label><br><label>".$invoice["date"]."</label><br>\n"
                            ."<label><b>Status</b></label><br><select name=\"status\"><option value=\"paid\" ".($invoice["status"] == "paid" ? "selected" : "").">paid</option><option value=\"unpaid\" ".($invoice["status"] == "unpaid" ? "selected" : "").">unpaid</option></select><br>\n"
                            ."<input type=\"hidden\" name=\"id\" value=\"".$invoice["id"]."\">"
                            ."<br><input type=\"submit\" name=\"save\" value=\"Save\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }
                    ?>

                    <a href="?add">add</a>
                    <table>
                        <tr><th>id</th><th>client</th><th>description</th><th>amount</th><th>date</th><th>pdf</th><th>status</th><th>action</th></tr>
                        <?php
                        foreach ($invoices as $invoice) {
                            echo "<tr><td>".$invoice["id"]."</td>"
                            ."<td>".getclientbyid($invoice["client"])["username"]."</td>"
                            ."<td>".$invoice["desc"]."</td>"
                            ."<td>".$invoice["amount"]." €</td>"
                            ."<td>".$invoice["date"]."</td>"
                            ."<td><a href=\"?pdf=".$invoice["id"]."\">pdf</a></td>"
                            ."<td>".$invoice["status"]."</td>"
                            ."<td><a href=\"?del=".$invoice["id"]."\">del</a> <a href=\"?edit=".$invoice["id"]."\">edit</a></td></tr>\n";
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

