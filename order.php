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
    } else header("location: ".$_SERVER['SCRIPT_NAME']);
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
            function selectservice(id) {
                var service = services.find((element) => element["id"] == id);
                document.getElementById("pricelabel").innerHTML = "Price: " + service["billing"];
                document.getElementById("description").innerHTML = service["description"];
                if (service["name"] == "vps") {
                    document.getElementById("extraform").innerHTML
                        = `<label><b>Options</b></label><br><label>Cores</label><br><select id=\"cpus\" onclick=\"calcprice()\"><option value=\"1\">1</option><option value=\"2\">2</option><option value=\"3\">3</option><option value=\"4\">4</option></select><br>
                        <label>Memory</label><br><select id=\"mem\" onclick=\"calcprice()\"><option value=\"1\">1GB</option><option value=\"2\">2GB</option><option value=\"3\">3GB</option><option value=\"4\">4GB</option></select><br>
                        <label>SSD</label><br><select id=\"ssd\" onclick=\"calcprice()\"><option value=\"5\">5GB</option><option value=\"10\">10GB</option><option value=\"20\">20GB</option><option value=\"30\">30GB</option></select><br>
                        <br><label id=\"calculated\">Calculated price: </label>`;
                    document.getElementById("comments").value = comment();
                    calcprice();
                } else document.getElementById("extraform").innerHTML = "";
            }

            function comment() {
                var cpus = document.getElementById("cpus").value;
                var mem = document.getElementById("mem").value;
                var ssd = document.getElementById("ssd").value;
                return "cpus: " + cpus + "\nmem: " + mem + "GB\nssd: " + ssd + "GB";
            }

            function calcprice() {
                var cpus = Number(document.getElementById("cpus").value);
                var mem = Number(document.getElementById("mem").value);
                var ssd = Number(document.getElementById("ssd").value);
                var price = (1*cpus**2) + (0.5*mem**2) + (0.02*ssd**2);
                document.getElementById("calculated").innerHTML = "Calculated price: " + price + " €/mo";
                document.getElementById("billing").value = price + "€/mo";
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
                            <input type="hidden" name="billing" id="billing">
                            <input type="hidden" name="comments" id="comments">
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
