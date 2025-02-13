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

// Get services
$sql = "SELECT id, name, type, billing, description FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

// GET actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM services WHERE id = ?";
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
        $sql = "INSERT INTO services (name, type, billing, description) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $param_name, $param_type, $param_billing, $param_description);
        $param_name = $_POST["name"];
        $param_type= $_POST["type"];
        $param_billing = $_POST["billing"];
        $param_description = $_POST["description"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
    }

    // edit entry
    if (isset($_POST["save"])) {
        $sql = "UPDATE services SET name = ?, type = ?, billing = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $param_name, $param_type, $param_billing, $param_description, $param_id);
        $param_name = $_POST["name"];
        $param_type = $_POST["type"];
        $param_billing = $_POST["billing"];
        $param_description = $_POST["description"];
        $param_id = $_POST["id"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
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
                    <h3>Service offerings</h3>

                    <?php
                    if (isset($_GET["edit"])) {
                        $service = getservicebyid($_GET["edit"]);
                        echo "<div class=\"form\"><h3>Edit service ".$service["id"]."</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label>Name</label><br><input type=\"text\" name=\"name\" value=\"".$service["name"]."\"><br>\n"
                            ."<label>Type</label><br><select name=\"type\"><option value=\"free\" ".($service["type"] == "free" ? "selected" : "").">free</option><option value=\"standard\" ".($service["type"] == "standard" ? "selected" : "").">standard</option><option value=\"premium\" ".($service["type"] == "premium" ? "selected" : "").">premium</option></select><br>\n"
                            ."<label>Billing</label><br><input type=\"text\" name=\"billing\" value=\"".$service["billing"]."\"><br>\n"
                            ."<label>Description</label><br><textarea name=\"description\" rows=\"10\" cols=\"80\">".$service["description"]."</textarea><br>\n"
                            ."<input type=\"hidden\" name=\"id\" value=\"".$service["id"]."\">"
                            ."<br><input type=\"submit\" name=\"save\" value=\"Save\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }

                    if (isset($_GET["add"])) {
                        echo "<div class=\"form\"><h3>Add service</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label>Name</label><br><input type=\"text\" name=\"name\"><br>\n"
                            ."<label>Type</label><br><select name=\"type\"><option value=\"free\">free</option><option value=\"standard\">standard</option><option value=\"premium\">premium</option></select><br>\n"
                            ."<label>Billing</label><br><input type=\"text\" name=\"billing\"><br>\n"
                            ."<label>Description</label><br><textarea name=\"description\" rows=\"10\" cols=\"80\"></textarea><br>\n"
                            ."<br><input type=\"submit\" name=\"add\" value=\"Add\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }
                    ?>

                    <a href="?add">add</a>
                    <table>
                        <tr><th>id</th><th>name</th><th>type</th><th>billing</th><th>description</th><th>action</th></tr>
                        <?php
                        foreach ($services as $service) {
                            echo "<tr><td>".$service['id']."</td>"
                            ."<td>".$service['name']."</td>"
                            ."<td>".$service['type']."</td>"
                            ."<td>".$service['billing']."</td>"
                            ."<td><details><summary></summary><pre>".$service['description']."</pre></details></td>"
                            ."<td><a href=\"?del=".$service['id']."\">del</a> <a href=\"?edit=".$service['id']."\">edit</a></td></tr>\n";
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

