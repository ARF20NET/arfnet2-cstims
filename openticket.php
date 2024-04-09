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
$sql = "SELECT id, name, service FROM orders WHERE client = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $param_client);
$param_client = $clientid;
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get services
$sql = "SELECT id, name, type, billing, description FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

// Get users
$sql = "SELECT id, username, type, email FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // add entry
    $sql = "INSERT INTO tickets (`order`, subject, body, status, asignee) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $param_order, $param_subject, $param_body, $param_status, $param_asignee);
    $param_order = $_POST["order"];
    $param_subject = $_POST["subject"];
    $param_body = $_POST["body"];
    $param_status = "open";
    // choose asignee automatically
    $helpdesk = array_filter($users, function ($t) { return $t["type"] == "helpdesk"; });
    $admins = array_filter($users, function ($t) { return $t["type"] == "admin"; });
    $asignee = null;
    if (!empty($helpdesk))
        $asignee = $helpdesk[array_rand($helpdesk)];
    else
        $asignee = $admins[array_rand($admins)];
    $param_asignee = $asignee["id"];

    if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
        echo "SQL error.";
    } else {
        // send ticket notification
        // get id
        // Get users
        $sql = "SELECT id FROM tickets ORDER BY id DESC LIMIT 0, 1";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $id = $result->fetch_all(MYSQLI_ASSOC);

        $lines = explode("\n", $_POST["body"]);
        $body = "";
        foreach ($lines as $line) $body .= ">".$line;

        $mailer->addAddress($asignee["email"]);
        $mailer->addReplyTo(getuserbyid($clientid)["email"]);
        $mailer->Subject = "[Ticket ID: ".$id[0]["id"]."] ".$_POST["subject"];
        $mailer->Body = "Helpdesk,\n\nUser $username opened new ticket for ".getorderbyid($_POST["order"])["name"]." (".getservicebyid(getorderbyid($_POST["order"])["service"])["name"]."):\n"
            .$body
            ."\n\n--\nARFNET Client, Service, Ticket and Invoice Management System\nhttps://arf20.com";

        if (!$mailer->send()) {
            echo 'Mailer Error [ask arf20]: ' . $mailer->ErrorInfo;
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
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
                    <h2>ARFNET Client Service Ticket and Invoice Management System</h2>
                    <h3><?php echo strtoupper($type[0]).substr($type, 1); ?> panel</h3>
                    <div class="form">
                        <h3>Open ticket</h3>
                        <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
                            <label><b>Service</b></label><br>
                            <select name="order">
                                <?php
                                foreach ($orders as $order) {
                                    echo "<option value=\"".$order["id"]."\">".$order["name"]." (".getservicebyid($order["service"])["name"].")</option>\n";
                                }
                                ?>
                            </select><br>
                            <br><label><b>Subject</b></label><br>
                            <input type="text" name="subject"><br>
                            <br><label><b>Body</b></label><br>
                            <textarea name="body" rows="10" cols="80"></textarea><br>
                            <br><input type="submit" value="Open ticket">
                        </form>
                    </div>
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</a></h2>
                    <h3><a href="/client.php">Back to dashboard</a></h2>
                </div>
            </div>
        </main>
    </body>
</html>
