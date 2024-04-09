<?php 

// Run first day of every month or

$computedate = null;
$computedateunix = null;
if (isset($_GET["computedate"])) {
    $computedate = $_GET["computedate"];
    $computedateunix = strtotime($_GET["computedate"]);
} else {
    $computedate = new DateTime("now");
    $computedateunix = time();
}

require_once("/usr/share/doc/php-tcpdf/examples/tcpdf_include.php");

// Extend the TCPDF class to create custom Header and Footer
class InvoicePDF extends TCPDF {
    //Page header
    public function Header() {

        $image_file = "arfnet_logo.png";
        $this->Image($image_file, 15, 30, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

        $this->SetFont('helvetica', 'B', 35);
        $this->SetXY(50, 43);
        // Title
        $this->Cell(0, 30, 'ARFNET', 0, false, 'L', 0, '', 0, false, 'C', 'C');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    // Table
    public function ColoredTable($header, $colw, $colalg, $data, $lastbold = false) {
        // Colors, line width and bold font
        $this->SetFillColor(59, 142, 234);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B');
        // Header
        $w = $colw;
        $num_headers = count($header);
        for($i = 0; $i < $num_headers; $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = 0;
        foreach($data as $key => $row) {
            if ($lastbold && $key === array_key_last($data)) $this->SetFont('', 'B');
            for ($i = 0; $i < $num_headers; $i++) {
                $this->Cell($w[$i], 6, $row[$i], 'LR', 0, $colalg[$i], $fill);
            }
            $this->Ln();
            $fill=!$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

require_once "config.php";

// Get clients
$sql = "SELECT id, username, email FROM users WHERE type = 'client'";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$clients = $result->fetch_all(MYSQLI_ASSOC);

// Get due orders
$sql = "SELECT id, service, name, client, billing, date FROM orders";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dueorders = $result->fetch_all(MYSQLI_ASSOC);

// Get services
$sql = "SELECT id, name, type, billing, description FROM services";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = $result->fetch_all(MYSQLI_ASSOC);

$fdom = new DateTime(date("Y-m-01", $computedateunix));
$ldom = new DateTime(date("Y-m-t", $computedateunix)); 

function send_invoice_mail($ret, $email, $desc) {
    global $mailer;
    new_mail();
    $mailer->addAddress($email);
    $mailer->Subject = "Invoice #".$ret[2];
    $mailer->Body = "Customer,\n\nThis is a notice that a invoice has been generated on ".date("l, F j, Y")."\n"
        ."for the amount of ".number_format($ret[1], 2, '.', '')." € with description\n\n"
        .$desc."\n\n"
        ."You may pay it in any of the payments methods listed on our site,\nalways include the invoice ID in the payment concept."
        ."\n\n--\nARFNET Client, Service, Ticket and Invoice Management System\nhttps://arf20.com";

    $mailer->AddStringAttachment($ret[0], "invoice_".$ret[2].".pdf");

    if (!$mailer->send()) {
        echo 'Mailer Error [ask arf20]: ' . $mailer->ErrorInfo;
    };
}

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // add entry
    if (isset($_POST["generate"])) {
        $ret = generate_pdf(getclientbyid($_POST["client"]), array(getorderbyid($_POST["order"])), $_POST["desc"], $_POST["qty"]);

        $sql = "INSERT INTO invoices (client, `desc`, amount, pdf) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $param_client, $param_desc, $param_amount, $param_pdf);
        $param_client = $_POST["client"];
        $param_desc = $_POST["desc"];
        $param_amount = $ret[1];
        $param_pdf = $ret[0];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else {
            echo $_POST["client"]." ok ".$ret[1]."\n";
        }

        send_invoice_mail($ret, getclientbyid($_POST["client"])["email"], $_POST["desc"]);

        //header("location: /manageinvoices.php");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    foreach ($clients as $client) {
        $ret = generate_pdf($client, array_filter($dueorders, function($e) { global $client; return $e["client"] == $client["id"]; }));

        $sql = "INSERT INTO invoices (client, `desc`, amount, pdf) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $param_client, $param_desc, $param_amount, $param_pdf);
        $param_client = $client["id"];
        $param_desc = "Monthly invoice";
        $param_amount = $ret[1];
        $param_pdf = $ret[0];

        if (!mysqli_stmt_execute($stmt) || (mysqli_stmt_affected_rows($stmt) != 1)) {
            echo "SQL error.";
        } else {
            echo $client["id"]." ok ".$ret[1]."\n";
        }

        send_invoice_mail($ret, $client["email"], "Monthly invoice");
    }
}


function getorderbyid($id) {
    global $dueorders;
    foreach ($dueorders as $order) {
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

function getclientbyid($id) {
    global $clients;
    foreach ($clients as $client) {
        if ($client["id"] == $id) {
            return $client;
        }
    }
}


function generate_pdf($client, $dueorders, $desc = null, $manualqty = null) {
    global $link, $fdom, $ldom, $computedateunix;
    // get next invoice id
    $sql = "SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name = 'invoices'";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $nextid = $result->fetch_all(MYSQLI_ASSOC)[0]["AUTO_INCREMENT"];


    $pdf = new InvoicePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor("ARFNET Client Service Ticket and Invoice Management System");
    $pdf->SetTitle("Invoice #$nextid");

    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ------------------------------
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetXY(15, 70);
    $pdf->Write(0, "Invoice", '', 0, 'L', true, 0, false, false, 0);

    $pdf->SetFont('helvetica', '', 12);

    $txt =
        "Invoice ID: $nextid\n"
        ."Invoice date: ".date("l, F j, Y\n", $computedateunix)
        ."Due date: ".date("l, F j, Y\n\n", $computedateunix);
    $pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Write(0, "Bill to", '', 0, 'L', true, 0, false, false, 0);

    $pdf->SetFont('helvetica', '', 12);
    $txt =
        $client["username"]."\n"
        ."Client ID: ".$client["id"]."\n\n";
    $pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

    $theader = array("Order ID", "Instance name", "Service", "Unit Price", "Quantity", "Amount");
    $columnsal = array("L", "L", "L", "R", "R", "R");
    $columnsw = array(18, 70, 16, 40, 16, 16);
    $tdata = array();
    $subtotal = 0;
    foreach ($dueorders as $dueorder) {
        $price = (float)trim(substr($dueorder["billing"], 0, strpos($dueorder["billing"], "€"))) / (float)(30*24);
        $pricestr = number_format($price, 4, '.', '')." €/h";
        
        if (!isset($manualqty)) {
            $dueorderdate = new DateTime($dueorder["date"]);
            $billingperiodstart = $dueorderdate > $fdom ? $dueorderdate : $fdom;
            $billingperiod = $billingperiodstart->format("d-m-Y")." to ".$ldom->format("d-m-Y");
            $billinginterval = date_diff($billingperiodstart, $ldom);
            $qty = ($billinginterval->d * 24) + $billinginterval->h;
        } else {
            $billingperiodstart = date("d-m-Y");
            $billingperiodend = (new DateTime())->add(new DateInterval("PT".$manualqty."H"));
            $billingperiod = $billingperiodstart." to ".$billingperiodend->format("d-m-Y");
            $qty = $manualqty;
        }

        $amount = $price*$qty;
        $subtotal += $amount;
        $amountstr = number_format($amount, 2, '.', '')." €";
        $tdata[] = array($dueorder["id"], $dueorder["name"]." ($billingperiod)", getservicebyid($dueorder["service"])["name"], $pricestr." (".$dueorder["billing"].")", $qty, $amountstr);
    }
    $subtotalstr = number_format($subtotal, 2, '.', '')." €";

    $pdf->SetFont('helvetica', '', 10);
    $pdf->ColoredTable($theader, $columnsw, $columnsal, $tdata);
    $pdf->Ln();

    // final table
    $theader = array("", "Amount");
    $columnsal = array("L", "R");
    $columnsw = array(35, 25);
    $tdata = array();
    $tdata[] = array("Subtotal", $subtotalstr);
    $tdata[] = array("Sales Tax 0.00%", $subtotalstr);
    $tdata[] = array("TOTAL", $subtotalstr);

    $pdf->ColoredTable($theader, $columnsw, $columnsal, $tdata, true);

    return array($pdf->Output('invoice.pdf', 'S'), $subtotal, $nextid);
}

?>