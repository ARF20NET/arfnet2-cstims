<?php 

// Run first day of every month

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
$sql = "SELECT id, username FROM users WHERE type = 'client'";
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

$fdom = new DateTime('first day of this month');
$ldom = new DateTime('last day of this month');

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
    } else echo $client["id"]." ok ".$ret[1]."\n";
}


function getservicebyid($id) {
    global $services;
    foreach ($services as $service) {
        if ($service["id"] == $id) {
            return $service;
        }
    }
}


function generate_pdf($client, $dueorders) {
    global $link, $fdom, $ldom;
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
        ."Invoice date: ".date("l, F j\t\h, Y\n")
        ."Due date: ".date("l, F j\t\h, Y\n");
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
        
        $dueorderdate = new DateTime($dueorder["date"]);
        $billingperiodstart = $dueorderdate > $fdom ? $dueorderdate : $fdom;
        $billingperiod = $billingperiodstart->format("d-m-Y")." to ".$ldom->format("d-m-Y");
        $billinginterval = date_diff($billingperiodstart, $ldom);
        $qty = ($billinginterval->d * 24) + $billinginterval->h;
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

    return array($pdf->Output('invoice.pdf', 'S'), $subtotal);
}

?>