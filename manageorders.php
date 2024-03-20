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
//mysqli_stmt_bind_param($stmt, "s", $param_type);
//$param_type = "client";
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$clients = $result->fetch_all(MYSQLI_ASSOC);

// Get services
$sql = "SELECT id, name, type, billing, description FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

// Get orders
$sql = "SELECT id, service, name, client, date, billing, status, comments FROM orders";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// GET actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param_id);
    $param_id = $_GET["del"];
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) != 1) {
        echo "SQL error.";
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
}

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // add entry
    if (isset($_POST["add"])) {
        $sql = "INSERT INTO orders (service, name, client, billing, comments) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $param_service, $param_name, $param_client, $param_billing, $param_comments);
        $param_service = $_POST["service"];
        $param_name = $_POST["name"];
        $param_client = $_POST["client"];
        $param_billing = $_POST["billing"];
        $param_comments = $_POST["comments"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
    }

    // edit entry
    if (isset($_POST["save"])) {
        $sql = "UPDATE orders SET name = ?, billing = ?, status = ?, comments = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $param_name, $param_billing, $param_status, $param_comments, $param_id);
        $param_name = $_POST["name"];
        $param_billing = $_POST["billing"];
        $param_status = $_POST["status"];
        $param_comments = $_POST["comments"];
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
                        $order = getorderbyid($_GET["edit"]);
                        $client_options = $service_options = "";
                        /*foreach ($clients as $client)
                            $client_options .= "<option value=\"".$client["id"]."\" ".($client["id"] == $order["client"] ? "selected" : "").">".$client["username"]."</option>";
                        foreach ($services as $service)
                            $service_options .= "<option value=\"".$service["id"]."\" ".($service["id"] == $order["service"] ? "selected" : "").">".$service["name"]."</option>";*/
                        echo "<div class=\"form\"><h3>Edit order ".$order["id"]."</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label>Name</label><br><input type=\"text\" name=\"name\" value=\"".$order["name"]."\"><br>\n"
                            ."<label>Billing</label><br><input type=\"text\" name=\"billing\" value=\"".$order["billing"]."\"><br>\n"
                            ."<label>Status</label><br><select name=\"status\"><option value=\"setting up\" ".($service["status"] == "setting up" ? "selected" : "").">setting up</option><option value=\"active\" ".($service["status"] == "active" ? "selected" : "").">active</option><option value=\"inactive\" ".($service["status"] == "inactive" ? "selected" : "").">inactive</option></select><br>\n"
                            ."<label>Comments</label><br><textarea name=\"comments\" rows=\"10\" cols=\"80\">".$order["comments"]."</textarea><br>\n"
                            ."<input type=\"hidden\" name=\"id\" value=\"".$order["id"]."\">"
                            ."<br><input type=\"submit\" name=\"save\" value=\"Save\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }

                    if (isset($_GET["add"])) {
                        $client_options = $service_options = "";
                        foreach ($clients as $client)
                            $client_options .= "<option value=\"".$client["id"]."\">".$client["username"]."</option>";
                        foreach ($services as $service)
                            $service_options .= "<option value=\"".$service["id"]."\">".$service["name"]."</option>";
                        echo "<div class=\"form\"><h3>Add order</h3><form action=\"".$_SERVER["SCRIPT_NAME"]."\" method=\"post\">\n"
                            ."<label>Service</label><br><select name=\"service\">".$service_options."</select><br>"
                            ."<label>Name</label><br><input type=\"text\" name=\"name\"><br>\n"
                            ."<label>Client</label><br><select name=\"client\">".$client_options."</select><br>\n"
                            ."<label>Billing</label><br><input type=\"text\" name=\"billing\"><br>\n"
                            ."<label>Status</label><br><select name=\"status\"><option value=\"active\">active</option><option value=\"inactive\">inactive</option></select><br>\n"
                            ."<label>Comments</label><br><textarea name=\"comments\" rows=\"10\" cols=\"80\"></textarea><br>\n"
                            ."<br><input type=\"submit\" name=\"add\" value=\"Add\"><a href=\"".$_SERVER["SCRIPT_NAME"]."\">cancel</a>"
                            ."</form></div>";
                    }
                    ?>

                    <a href="?add">add</a>
                    <table>
                        <tr><th>id</th><th>service</th><th>instance</th><th>client</th><th>billing</th><th>date</th><th>status</th><th>comments</th><th>action</th></tr>
                        <?php
                        foreach ($orders as $order) {
                            echo "<tr><td>".$order["id"]."</td>"
                            ."<td>".getservicebyid($order["service"])["name"]."</td>"
                            ."<td>".$order["name"]."</td>"
                            ."<td>".getclientbyid($order["client"])["username"]."</td>"
                            ."<td>".$order["billing"]."</td>"
                            ."<td>".$order["date"]."</td>"
                            ."<td>".$order["status"]."</td>"
                            ."<td><pre>".$order["comments"]."</pre></td>"
                            ."<td><a href=\"?del=".$order["id"]."\">del</a> <a href=\"?edit=".$order["id"]."\">edit</a></td></tr>\n";
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

