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
$sql = "SELECT id, username, password, email, verifycode, status, type, regdate FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

// GET actions
//   delete entry
if (isset($_GET["del"])) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param_id);
    $param_id = $_GET["del"];
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) != 1) {
        echo "SQL error: ".mysqli_stmt_error($stmt);
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
}

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // add entry
    if (isset($_POST["add"])) {
        $sql = "INSERT INTO users (username, email, password, verifycode, type, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $param_username, $param_email, $param_password, $param_verifycode, $param_type, $param_status);
        $param_username = $_POST["username"];
        $param_email= $_POST["email"];
        $param_password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $param_verifycode = base64_encode(random_bytes(12));
        $param_type = $_POST["type"];
        $param_status = $_POST["status"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error: ".mysqli_stmt_error($stmt);
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
    }

    // edit entry
    if (isset($_POST["save"])) {
        $sql = "UPDATE users SET username = ?, email = ?, password = ?, type = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $param_username, $param_email, $param_password, $param_type, $param_status, $param_id);
        $param_username = $_POST["username"];
        $param_email = $_POST["email"];
        $param_password = empty($_POST["password"]) ? getuserbyid($_POST["id"])["password"] : password_hash($_POST["password"], PASSWORD_DEFAULT);
        $param_type = $_POST["type"];
        $param_status = $_POST["status"];
        $param_id = $_POST["id"];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error: ".mysqli_stmt_error($stmt);
        } else header("location: ".$_SERVER['SCRIPT_NAME']);
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

                    <?php
                    if (isset($_GET["edit"])) {
                        $user = getuserbyid($_GET["edit"]);
                        echo "<div class=\"editform\"><h3>Edit user ".$user["id"]."</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label>Username</label><br><input type=\"text\" name=\"username\" value=\"".$user["username"]."\"><br>\n"
                            ."<label>Email</label><br><input type=\"text\" name=\"email\" value=\"".$user["email"]."\"><br>\n"
                            ."<label>Password (empty is unchanged)</label><br><input type=\"text\" name=\"password\"><br>\n"
                            ."<label>Type</label><br><select name=\"type\"><option value=\"client\" ".($user["type"] == "client" ? "selected" : "").">client</option><option value=\"helpdesk\" ".($user["type"] == "helpdesk" ? "selected" : "").">helpdesk</option><option value=\"accountant\" ".($user["type"] == "accountant" ? "selected" : "").">accountant</option><option value=\"admin\" ".($user["type"] == "admin" ? "selected" : "").">admin</option></select><br>\n"
                            ."<label>Status</label><br><select name=\"status\"><option value=\"unverified\" ".($user["status"] == "unverified" ? "selected" : "").">unverified</option><option value=\"verified\" ".($user["status"] == "verified" ? "selected" : "").">verified</option></select><br>\n"
                            ."<input type=\"hidden\" name=\"id\" value=\"".$user["id"]."\">"
                            ."<br><input type=\"submit\" name=\"save\" value=\"Save\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }

                    if (isset($_GET["add"])) {
                        echo "<div class=\"editform\"><h3>Add user</h3><form action=\"".$_SERVER['SCRIPT_NAME']."\" method=\"post\">\n"
                            ."<label>Username</label><br><input type=\"text\" name=\"username\"><br>\n"
                            ."<label>Email</label><br><input type=\"text\" name=\"email\"><br>\n"
                            ."<label>Password</label><br><input type=\"text\" name=\"password\"><br>\n"
                            ."<label>Type</label><br><select name=\"type\"><option value=\"client\">client</option><option value=\"helpdesk\">helpdesk</option><option value=\"accountant\">accountant</option><option value=\"admin\">admin</option></select><br>\n"
                            ."<label>Status</label><br><select name=\"status\"><option value=\"unverified\">unverified</option><option value=\"verified\">verified</option></select><br>\n"
                            ."<br><input type=\"submit\" name=\"add\" value=\"Add\"><a href=\"".$_SERVER['SCRIPT_NAME']."\">cancel</a>"
                            ."</form></div>";
                    }
                    ?>

                    <a href="?add">add</a>
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

