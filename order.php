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
                    <div class="form">
                        <h3>Order a new service</h3>
                        <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
                            <div class="border">
                                <label><b>Service</b></label><br>
                                
                                    <label>Premium</dev><br>
                                    <?php
                                    foreach ($services as $service) {
                                        if ($service["type"] != "premium") continue;
                                        echo "<input type=\"radio\" name=\"service\" value=\"".$service["id"]."\">"
                                            ."<label>".$service["name"]."</label><br>\n";
                                    }
                                    ?>
                                
                                
                                    <label>Standard</dev><br>
                                    <?php
                                    foreach ($services as $service) {
                                        if ($service["type"] != "standard") continue;
                                        echo "<input type=\"radio\" name=\"service\" value=\"".$service["id"]."\">"
                                            ."<label>".$service["name"]."</label><br>\n";
                                    }
                                    ?>
                                
                                    <label>Free</dev><br>
                                    <?php
                                    foreach ($services as $service) {
                                        if ($service["type"] != "free") continue;
                                        echo "<input type=\"radio\" name=\"service\" value=\"".$service["id"]."\">"
                                            ."<label>".$service["name"]."</label><br>\n";
                                    }
                                    ?>
                                
                            </div>
                            <br><input type="submit" value="Place order">
                        </form>
                    </div>
                </div>
                <div class="col2">
                    <h3>Logged as <?php echo $username; ?></h3>
                    <h3><a href="/logout.php">Logout</h2>
                    <h3><a href="/client.php">Back to dashboard</h2>
                </div>
            </div>
        </main>
    </body>
</html>
