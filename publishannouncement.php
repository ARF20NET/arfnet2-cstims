<?php

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /login.php");
    exit;
}

$username = $_SESSION["username"];
$type = $_SESSION["type"];
$id = $_SESSION["id"];

if ($type != "admin") die("Permission denied.");

require_once "config.php";

// Get users
$sql = "SELECT id, username, type, email FROM users";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = $result->fetch_all(MYSQLI_ASSOC);

/*
 * Announce to
 *  - mailing list (hereby the announcement archive at lists.arf20.com)
 *  - discord webhook
 *  - irc (bridged) announcement notice
 *  - NNTP?
 *  - phpBB?
 *  - another, custom, archive ARFNET-ly
 */

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /* Send email */
    $mailer->addAddress(MAIL_ANNOUNCE_ADDRESS);
    $mailer->addReplyTo(getuserbyid($id)["email"]);
    $mailer->Subject = "[ARFNET Announcement] ".$_POST["subject"];
    $mailer->Body = $_POST["body"];

    if (!$mailer->send()) {
        echo 'Mailer Error [ask arf20]: ' . $mailer->ErrorInfo;
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
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
                    <h3>Publish announcement</h3>
                    <form action="<?php echo $_SERVER["SCRIPT_NAME"]; ?>" method="post">
                        <label><b>Subject</b></label><br>
                        <input type="text" name="subject"><br>
                        <br><label><b>Body</b></label><br>
                        <textarea name="body" rows="10" cols="80"></textarea><br>
                        <br><input type="submit" value="Publish">
                    </form>
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

