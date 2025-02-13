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
    // Send email
    if (defined("ANNOUNCE_MAIL")) {
        echo "email ";
        $mailer->addAddress(ANNOUNCE_MAIL);
        $mailer->addReplyTo(getuserbyid($id)["email"]);
        $mailer->Subject = "[ARFNET Announcement] ".$_POST["subject"];
        $mailer->Body = $_POST["body"];

        if (!$mailer->send()) {
            echo 'Mailer Error [ask arf20]: ' . $mailer->ErrorInfo;
        } else echo "ok<br>";
    }

    // Send discord message
    if (defined("ANNOUNCE_DISCORD")) {
        echo "discord ";
        $json_data = json_encode([
            "content" => "@everyone \nSubject: ".$_POST["subject"]."\n".$_POST["body"]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ch = curl_init(ANNOUNCE_DISCORD);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response == false) {
            echo 'Curl Error [ask arf20]: ' . $response;
        } else echo "ok<br>";
    }

    // Send IRC notification
    if (defined("ANNOUNCE_IRCSERVER")) {
        echo "irc ";
        $fd = fsockopen(ANNOUNCE_IRCSERVER, 6667, $errno, $errstr, 5);
        $read = fread($fd, 1024); // flush

        // send login
        fwrite($fd, "NICK system\r\n");
        fwrite($fd, "USER system 0 * ARFNET CSTIMS UAS\r\n");

        // read ping
        do {
            $read = fread($fd, 1024);
        } while (!str_contains($read, "PING"));
        $rndstr = substr($read, strpos($read, ":", 1) + 1);
        // answer ping
        fwrite($fd, "PONG :".$rndstr."\r\n");

        // join channel
        fwrite($fd, "JOIN ".ANNOUNCE_IRCCHANNEL."\r\n");
        
        $read = fread($fd, 1024); // flush

        // send message
        fwrite($fd, "PRIVMSG ".ANNOUNCE_IRCCHANNEL." :New announcement: ".$_POST["subject"]."\r\n");

        fwrite($fd, "QUIT\r\n");

        fclose($fd);

        echo "ok";
    }

    if (defined("ANNOUNCE_NNTPSERVER")) {
        echo "nntp ";

        $fd = fsockopen(ANNOUNCE_NNTPSERVER, 119, $errno, $errstr, 5);

        fwrite($fd, "AUTHINFO USER ".ANNOUNCE_NNTPUSER."\r\n");
        fwrite($fd, "AUTHINFO PASS ".ANNOUNCE_NNTPPASS."\r\n");
        fread($fd, 1024); // flush

        fwrite($fd, "POST\r\n");
        do {
            $read = fread($fd, 1024);
        } while (!str_contains($read, "Message-ID"));
        /* Assumes CRLF */
        $msgidheader = substr($read, strpos($read, "Message-ID"), -2);
        $msgidheader = str_replace("Message-ID", "Message-ID:", $msgidheader);

        $message = 
            "From: System <system@arf20.com>\r\n".
            "Reply-To: ".getuserbyid($id)["email"]."\r\n".
            "Newsgroups: ".ANNOUNCE_NNTPGROUP."\r\n".
            "Subject: ".$_POST["subject"]."\r\n".
            "Date: ".date("r")."\r\n".
            $msgidheader."\r\n".
            "Organization: ARFNET\r\n".
            "User-Agent: ARFNET CSTIMS UAS\r\n\r\n".
            $_POST["body"]."\r\n".
            ".\r\n";

        $response = fread($fd, 1024);
    
        fwrite($fd, "QUIT\r\n");

        fclose($fd);

        if (str_contains($response, "240 Article received"))
            echo "ok";
        else echo "error $response<br>";
    }

    die();
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

