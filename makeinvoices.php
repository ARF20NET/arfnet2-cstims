<?php 

// Run first day of every month

require_once("/usr/share/doc/php-tcpdf/examples/tcpdf_include.php");

require_once "config.php";

// Get due orders
$sql = "SELECT id, service, name, client, billing FROM orders";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dueorders = $result->fetch_all(MYSQLI_ASSOC);

/*foreach ($dueorders as $dueorder) {
    generate_pdf($dueorder);
}*/
generate_pdf($dueorders[0]);

function generate_pdf($dueorder) {
    // Extend the TCPDF class to create custom Header and Footer
    class InvoicePDF extends TCPDF {
        //Page header
        public function Header() {
            // Logo
            $image_file = "arfnet_logo.png";
            $this->Image($image_file, 10, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            // Set font
            $this->SetFont('helvetica', 'B', 20);
            // Title
            $this->Cell(0, 15, 'ARFNET', 0, false, 'C', 0, '', 0, false, 'M', 'M');
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
    }

    $pdf = new InvoicePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor("ARFNET Client Service Ticket and Invoice Management System");
    $pdf->SetTitle("Invoice");

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
    $pdf->SetFont('times', 'B', 12);

    $pdf->AddPage();

    $txt =
        "Client ID: ".$dueorder["client"]."\n"
        ."Order ID: ".$dueorder["id"]."\n\n"
        ."Service: ".$dueorder["name"]."\n"
        ."Amount: ".$dueorder["billing"]."\n"
        ;

    $pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

    $pdf->Output('invoice.pdf', 'I');
}

?>