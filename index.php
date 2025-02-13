<?php
require_once "config.php";
// Get services
$sql = "SELECT id, name, type, billing FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);
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
                    <p>State of the art hosting solution with ultra personalised service and 24/7 support (fucked up sleep schedule)</p>
                    <div class="row">
                        <div class="col5">
                            <h3>Our cutting edge datacenter</h3>
                            <img class="img" src="/rack.jpg"><br>
                        </div>
                        <div class="col5">
                            <h3>Services and plans</h3>
                            <table>
                                <tr><th>name</th><th>type</th><th>billing</th></tr>
                                <?php
                                foreach ($services as $service) {
                                    echo "<tr><td>".$service["name"]."</td><td>".$service["type"]."</td><td>".$service["billing"]."</tr>\n";
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col2">
                    <h3><a href="/login.php">Login</h2>
                    <h3><a href="/register.php">Sign up today!</h2>
                    <h3><a href="/pay.html">Payment methods</h2>
                    <h3><a href="/privacy.html">Privacy Policy</h2>
                    <h3><a href="/tos.html">Terms of Service</h2>
                </div>
            </div>
        </main>
    </body>
</html>
