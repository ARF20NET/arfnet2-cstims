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
$sql = "SELECT id, name, type, billing, description FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // add entry
    $sql = "INSERT INTO orders (service, name, client, billing, comments) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $param_service, $param_name, $param_client, $param_billing, $param_comments);
    $param_service = $_POST["service"];
    $param_name = $_POST["name"];
    $param_client = $clientid;
    $param_billing = $_POST["billing"];
    $param_comments = $_POST["comments"];

    if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
        echo "SQL error.";
    } else {
        // send admin mail
        // Get admin mails
        $sql = "SELECT email FROM users WHERE type = 'admin'";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admins = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($admins as $admin) {
            $mailer->addAddress($admin["email"]);
        }
        
        $mailer->Subject = "New service order request";
        $mailer->Body = "Admins,\n\nUser $username requested service ".getservicebyid($_POST["service"])["name"]."\n\n"
            ."Instance name: ".$_POST["name"]."\n"
            ."Calculated billing: ".$_POST["billing"]."\n"
            ."Comments:\n"
            .$_POST["comments"]
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

function genoption($id, $name) {
    return "<input type=\"radio\" name=\"service\" id=\"$id\" onclick=\"selectservice($id)\" value=\"$id\">"
        ."<label for=\"$id\">$name</label><br>\n";
}

?>

<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" href="/style.css">
        <title>ARFNET CSTIMS</title>
        <script type="text/javascript">
            var services = <?php echo json_encode($services); ?>;
            var service;
            function selectservice(id) {
                service = services.find((element) => element["id"] == id);
                document.getElementById("pricelabel").innerHTML = "Price: " + service["billing"];
                document.getElementById("description").innerHTML = service["description"];
                if (service["name"] == "vps") {
                    document.getElementById("extraform").innerHTML
                        = `<label><b>Options</b></label><br><label>Cores</label><br><select id=\"cpus\" onclick=\"update()\"><option value=\"1\">1</option><option value=\"2\">2</option><option value=\"3\">3</option><option value=\"4\">4</option></select><br>
                        <label>Memory</label><br><select id=\"mem\" onclick=\"update()\"><option value=\"1\">1GB</option><option value=\"2\">2GB</option><option value=\"4\">4GB</option><option value=\"8\">8GB</option></select><br>
                        <label>SSD</label><br><select id=\"ssd\" onclick=\"update()\"><option value=\"5\">5GB</option><option value=\"10\">10GB</option><option value=\"15\">15GB</option><option value=\"20\">20GB</option><option value=\"30\">30GB</option></select><br>
                        <br><label id=\"calculated\">Calculated price: </label>`;
                } else document.getElementById("extraform").innerHTML = "";
                update();
            }

            function update() {
                var comment = document.getElementById("commentbox").value;
                if (service["name"] == "vps") {
                    var cpus = document.getElementById("cpus").value;
                    var mem = document.getElementById("mem").value;
                    var ssd = document.getElementById("ssd").value;
                    document.getElementById("comments").value = "Options:\ncpus: " + cpus + "\nmem: " + mem + "GB\nssd: " + ssd + "GB\n\nClient comment:\n" + comment;
                    var price = (1*Number(cpus)**2) + (0.5*Number(mem)**2) + (0.02*Number(ssd)**2);
                    document.getElementById("calculated").innerHTML = "Calculated price: " + price + " €/mo";
                    document.getElementById("billing").value = price + " €/mo";
                } else {
                    document.getElementById("comments").value = "Client comment:\n" + comment;
                    document.getElementById("billing").value = service["billing"];
                }
            }
        </script>
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
                        <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
                            <div class="border">
                                <label><b>Service</b></label><br>
                                <div class="row">
                                    <div class="col">
                                        <label>Premium</dev><br>
                                        <?php
                                        foreach ($services as $service) {
                                            if ($service["type"] != "premium") continue;
                                            echo genoption($service["id"], $service["name"]);
                                        }
                                        ?>
                                    </div>
                                    <div class="col">
                                        <label>Standard</dev><br>
                                        <?php
                                        foreach ($services as $service) {
                                            if ($service["type"] != "standard") continue;
                                            echo genoption($service["id"], $service["name"]);
                                        }
                                        ?>
                                    </div>
                                    <div class="col">
                                        <label>Free</dev><br>
                                        <?php
                                        foreach ($services as $service) {
                                            if ($service["type"] != "free") continue;
                                            echo genoption($service["id"], $service["name"]);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <br><label>Description</label><pre id="description"></pre>
                            <label id="pricelabel">Price: </label><br>
                            <br><div class="border" id="extraform"></div>
                            <br><label>Instance name</label><br>
                            <input type=text name="name"><br>
                            <div id="commentcontainer">
                                <br><label>Comments (describe use case and requirements)</label><br>
                                <textarea id="commentbox" rows="10" cols="80" onchange="update()"></textarea><br>
                            </div>
                            <input type="hidden" name="billing" id="billing">
                            <input type="hidden" name="comments" id="comments">
                            <br><input type="submit" value="Place order">
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
