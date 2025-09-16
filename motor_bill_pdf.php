<?php
require 'dompdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Input Data (replace with $_POST in production)
$invoice_no   = $_POST['invoice_no'] ?? '001';
$invoice_date = $_POST['date'] ?? date('d-m-Y');
$name         = $_POST['name'] ?? 'Customer Name';
$address      = $_POST['address'] ?? 'Customer Address';
$bho          = $_POST['bho'] ?? '';
$items        = $_POST['items'] ?? [
    ['desc' => 'Motor Repairing Work', 'price' => 1500],
    ['desc' => 'New Motor Installed', 'price' => 3500]
];

$total = 0;
$rows = '';
foreach ($items as $i => $item) {
    $desc  = htmlspecialchars($item['desc']);
    $price = floatval($item['price']);
    $total += $price;
    $rows .= "
        <tr>
            <td style='text-align:center;'>".($i+1)."</td>
            <td>$desc</td>
            <td style='text-align:right;'>".number_format($price,2)."</td>
        </tr>
    ";
}

// Always show 12 rows (add empty ones if fewer)
$max_rows = 8;
$current_rows = count($items);
if ($current_rows < $max_rows) {
    for ($j = $current_rows; $j < $max_rows; $j++) {
        $rows .= "
            <tr>
                <td style='text-align:center;'>&nbsp;</td>
                <td>&nbsp;</td>
                <td style='text-align:right;'>&nbsp;</td>
            </tr>
        ";
    }
}

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .container { border: 3px solid #000; padding: 8px; border-radius: 12px; }
        .header, .address-bar, .details, .items, .note {
            border-radius: 6px;
        }

        .header { display: flex; justify-content: space-between; border: 2px solid #000; padding: 5px; }
        .company { text-align: center; font-size: 20px; font-weight: bold; margin: auto; }
        .contact { font-size: 12px; }
        .address-bar { text-align: center; font-size: 11px; margin: 5px 0; border: 2px solid #000; padding: 3px; }
        .details { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 5px; }
        .details td { padding: 4px; font-size: 12px; border: 1px solid #000; }

        /* Items table */
        .items { width: 100%; border: 2px solid #000; border-collapse: collapse; table-layout: fixed; }
        .items th, .items td { border: 1px solid #000; font-size: 12px; word-wrap: break-word; }
        .items th { background: #000; color: #fff; text-align: center; padding: 6px; }
        .items td { height: 28px; padding: 4px; vertical-align: top; }

        .total-row td { border-top: 2px solid #000; font-weight: bold; }
        .note { border: 2px solid #000; padding: 5px; font-size: 11px; margin-top: 5px; }
        .footer { text-align: right; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Header -->
        <div class="header">
            <div>
                <table>
                    <tr>
                        <td> <div class="contact">+91 99797 34485</div></td>
                        <td style="text-align: right;"> <div class="contact" >+91 96627 74770</div></td>
                    </tr>
                </table>
            </div>
            <div class="company">Krishna Pump</div>
        </div>

        <!-- Address -->
        <div class="address-bar">
            No-1, next to Adarshnagar Gate (in the basement), Kapodra Crossroads, Surat.
        </div>

        <!-- Customer / Invoice Details -->
        <table class="details">
            <tr>
                <td width="60%"><b>Name:</b> <?= $name ?></td>
                <td><b>Bill No.:</b> <?= $invoice_no ?></td>
            </tr>
            <tr>
                <td><b>Address:</b> <?= $address ?></td>
                <td><b>Date:</b> <?= $invoice_date ?></td>
            </tr>
            <tr>
                <td><b>MO :</b> <?= $bho ?></td>
                <td></td>
            </tr>
        </table>

        <!-- Items -->
        <table class="items" >
            <tr style="margine: 10px;">
                <th style="width:10%;">No.</th>
                <th style="width:70%;">Description</th>
                <th style="width:20%;">price</th>
            </tr>
            <?= $rows ?>
            <tr class="total-row">
                <td colspan="2" style="text-align:right;">Total</td>
                <td style="text-align:right; font-size: 15px;"><?= number_format($total,2) ?></td>
            </tr>
        </table>

        <!-- Notes -->
        <div class="note">
            <b>Note:</b><br>
            1. No warranty applies if water gets into the motor.<br>
            2. It is necessary to keep the bill as long as the motor is under warranty.
        </div>

        <!-- Footer -->
        <div class="footer">
            Four, Krishna Pump
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$filename = "invoice_" . time() . ".pdf";
$dompdf->stream($filename, ["Attachment" => 1]);
?>
