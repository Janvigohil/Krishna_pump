<?php
require 'db_config.php';
require __DIR__ . '/dompdf/vendor/autoload.php';
;

use Dompdf\Dompdf;

$bill_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$result = $conn->query("SELECT * FROM customer_bill WHERE id = $bill_id");
if (!$result || $result->num_rows == 0) {
    die("Invalid Bill ID.");
}
$bill = $result->fetch_assoc();
$customer_name = $bill['customer_name'];
$customer_contact = $bill['contact_no'];
$bill_amount = $bill['bill_amount'];
$work_id = $bill['id'];

// Get parts
$parts_result = $conn->query("SELECT mp.name FROM customer_motor_work cmw 
    JOIN motor_parts mp ON cmw.part_id = mp.id 
    WHERE cmw.bill_id = $bill_id");

$part_names = [];
$sr = 1;
while ($row = $parts_result->fetch_assoc()) {
    $part_names[] = [
        'sr' => $sr++,
        'name' => $row['name'],
        'amount' => $bill_amount   // optional; same amount or split logic
    ];
}

// Load template
ob_start();
include 'pdf_templates/customer_bill_template.php';
$html = ob_get_clean();

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream("Customer_Bill_$bill_id.pdf", ["Attachment" => 1]);
exit;
