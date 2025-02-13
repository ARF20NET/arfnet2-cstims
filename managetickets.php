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
$sql = "SELECT id, username, type FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

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

// Get tickets
$sql = "SELECT id, `order`, subject, body, date, status, closecomment, asignee FROM tickets";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tickets = $result->fetch_all(MYSQLI_ASSOC);

// GET actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM tickets WHERE id = ?";
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
        $sql = "INSERT INTO tickets (`order`, subject, body, status, closecomment, asignee) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $param_order, $param_subject, $param_body, $param_status, $param_closecomment, $param_asignee);
        $param_order = $_POST["order"];
        $param_subject = $_POST["subject"];
        $param_body = $_POST["body"];
        $param_status = $_POST["status"];
        $param_closecomment = $_POST["closecomment"];
        $param_asignee = $_POST["asignee"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
    }

    // edit entry
    if (isset($_POST["save"])) {
        $sql = "UPDATE tickets SET status = ?, closecomment = ?, asignee = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $param_status, $param_closecomment, $param_asignee, $param_id);
        $param_status = $_POST["status"];
        $param_closecomment = $_POST["closecomment"];
        $param_asignee = $_POST["asignee"];
        $param_id = $_POST["id"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
    }
}

function getticketbyid($id) {
    global $tickets;
    foreach ($tickets as $ticket) {
        if ($ticket["id"] == $id) {
            return $ticket;
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

function getservicebyid($id) {
    global $services;
    foreach ($services as $service) {
        if ($service["id"] == $id) {
            return $service;
        }
    }
}

function getuserbyid($id) {
    global $users;
    foreach ($users as $user) {
        if ($user["id"] == $id) {
            return $user;
        }
    }
}

?>

<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" href="/style.css">
        <link rel="stylesheet" type="text/css" href="https://arf20.com/style.css">
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
                    <h3>Tickets</h3>
                    <?php
                    if (isset($_GET["edit"])) {
                        $ticket = getticketbyid($_GET["edit"]);
                        $asignee_options = "";
                        foreach ($users as $user)
                            if ($user["type"] == "admin" || $user["type"] == "helpdesk")
                                $asignee_options .= "<option value=\"".$user["id"]."\" ".($ticket["asignee"] == $user["id"] ? "selected" : "").">".$user["username"]."</option>";
                        echo "<div class=\"form\"><h3>Edit ticket ".$ticket["id"]."</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label><b>Instance</b></label><br><label>".getorderbyid($ticket["order"])["name"]."</label><br>\n"
                            ."<label><b>Service</b></label><br><label>".getservicebyid(getorderbyid($ticket["order"])["service"])["name"]."</label><br>\n"
                            ."<label><b>Client</b></label><br><label>".getuserbyid(getorderbyid($ticket["order"])["client"])["username"]."</label><br>\n"
                            ."<label><b>Subject</b></label><br><label>".$ticket["subject"]."</label><br>\n"
                            ."<label><b>Body</b></label><br><pre>".$ticket["body"]."</pre><br>\n"
                            ."<label><b>Status</b></label><br><select name=\"status\"><option value=\"open\" ".($ticket["status"] == "open" ? "selected" : "").">open</option><option value=\"closed\" ".($order["status"] == "closed" ? "selected" : "").">closed</option></select><br>\n"
                            ."<label><b>Close comment</b><br><textarea name=\"closecomment\" rows=\"10\" cols=\"80\">".$ticket["closecomment"]."</textarea><br>\n"
                            ."<label><b>Asignee</b></label><br><select name=\"asignee\">$asignee_options</select><br>\n"
                            ."<input type=\"hidden\" name=\"id\" value=\"".$ticket["id"]."\">\n"
                            ."<br><input type=\"submit\" name=\"save\" value=\"Save\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }

                    if (isset($_GET["add"])) {
                        $order_options = $asignee_options = "";
                        foreach ($orders as $order)
                            $order_options .= "<option value=\"".$order["id"]."\">".$order["name"]." (".getservicebyid($order["service"])["name"].")</option>";
                        foreach ($users as $user)
                            if ($user["type"] == "admin" || $user["type"] == "helpdesk")
                                $asignee_options .= "<option value=\"".$user["id"]."\">".$user["username"]."</option>";
                        echo "<div class=\"form\"><h3>Add ticket</h3><form action=\"".$_SERVER["SCRIPT_NAME"]."\" method=\"post\">\n"
                            ."<label>Order</label><br><select name=\"order\">".$order_options."</select><br>"
                            ."<label>Subject</label><br><input type=\"text\" name=\"subject\"><br>\n"
                            ."<label>Body</label><br><textarea name=\"body\" rows=\"10\" cols=\"80\"></textarea><br>\n"
                            ."<label>Status</label><br><select name=\"status\"><option value=\"open\">open</option><option value=\"closed\">closed</option></select><br>\n"
                            ."<label>Close comment<br><textarea name=\"closecomment\" rows=\"10\" cols=\"80\"></textarea><br>\n"
                            ."<label>Asignee</label><br><select name=\"asignee\">$asignee_options</select><br>\n"
                            ."<br><input type=\"submit\" name=\"add\" value=\"Add\"><a href=\"".$_SERVER["SCRIPT_NAME"]."\">cancel</a>"
                            ."</form></div>";
                    }
                    ?>

                    <a href="?add">add</a>
                    <table>
                        <tr><th>id</th><th>order</th><th>service</th><th>client</th><th>subject</th><th>body</th><th>date</th><th>status</th><th>close comment</th><th>asignee</th><th>action</th></tr>
                        <?php
                        foreach ($tickets as $ticket) {
                            $order = getorderbyid($ticket["order"]);
                            echo "<tr><td>".$ticket["id"]."</td>"
                            ."<td>".$order["name"]."</td>"
                            ."<td>".getservicebyid($order["service"])["name"]."</td>"
                            ."<td>".getuserbyid(getorderbyid($ticket["order"])["client"])["username"]."</td>"
                            ."<td>".$ticket["subject"]."</td>"
                            ."<td><details><summary></summary><pre>".$ticket["body"]."</pre></details></td>"
                            ."<td>".$ticket["date"]."</td>"
                            ."<td>".$ticket["status"]."</td>"
                            ."<td><details><summary></summary><pre>".$ticket["closecomment"]."</pre></details></td>"
                            ."<td>".getuserbyid($ticket["asignee"])["username"]."</td>"
                            ."<td><a href=\"?del=".$ticket["id"]."\">del</a> <a href=\"?edit=".$ticket["id"]."\">edit</a></td></tr>\n";
                        }
                        ?>
                    </table>
                        
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</a></h2>
                    <h3><a href="/admin.php">Back to admin panel</a></h2>
                </div>
            </div>
        </main>
    </body>
</html>

