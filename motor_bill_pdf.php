<?php
require 'dompdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Collect form data
$date            = $_POST['date'] ?? date('Y-m-d');
$name            = $_POST['name'] ?? '';
$address         = $_POST['address'] ?? '';
$mobile          = $_POST['mobile'] ?? '';
$discountPercent = floatval($_POST['discount'] ?? 0);
$payment_method  = $_POST['payment_method'] ?? 'Cash';

// Arrays from form
$descriptions = $_POST['description'] ?? [];
$qtys         = $_POST['qty'] ?? [];
$rates        = $_POST['rate'] ?? [];

// Calculate totals
$total = 0;
$rows = '';
foreach ($descriptions as $i => $desc) {
    $desc  = htmlspecialchars($desc);
    $qty   = floatval($qtys[$i] ?? 0);
    $rate  = floatval($rates[$i] ?? 0);
    $amount = $qty * $rate;
    $total += $amount;

    $rows .= "
        <tr>
            <td style='text-align:center;'>".($i+1)."</td>
            <td>$desc</td>
            <td style='text-align:center;'>$qty</td>
            <td style='text-align:right;'>".number_format($rate,2)."</td>
            <td style='text-align:right;'>".number_format($amount,2)."</td>
        </tr>
    ";
}

// Apply discount
$discountAmount = ($total * $discountPercent) / 100;
$finalAmount = $total - $discountAmount;

// Ensure consistent table height (8 rows minimum)
$max_rows = 8;
$current_rows = count($descriptions);
if ($current_rows < $max_rows) {
    for ($j = $current_rows; $j < $max_rows; $j++) {
        $rows .= "
            <tr>
                <td style='text-align:center;'>&nbsp;</td>
                <td>&nbsp;</td>
                <td style='text-align:center;'>&nbsp;</td>
                <td style='text-align:right;'>&nbsp;</td>
                <td style='text-align:right;'>&nbsp;</td>
            </tr>
        ";
    }
}

// Build HTML
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .container { border: 3px solid #000; padding: 8px; border-radius: 12px; }
        .header { display: flex; justify-content: space-between; border: 2px solid #000; padding: 5px; }
        .company { text-align: center; font-size: 20px; font-weight: bold; margin: auto; }
        .contact { font-size: 12px; }
        .address-bar { text-align: center; font-size: 11px; margin: 5px 0; border: 2px solid #000; padding: 3px; }
        .details { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 5px; }
        .details td { padding: 4px; font-size: 12px; border: 1px solid #000; }
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
                        <td><div class="contact">+91 99797 34485</div></td>
                        <td style="text-align: right;"><div class="contact">+91 96627 74770</div></td>
                    </tr>
                </table>
            </div>
            <div class="company">Krishna Pump</div>
        </div>

        <!-- Address -->
        <div class="address-bar">
            No-1, next to Adarshnagar Gate (in the basement), Kapodra Crossroads, Surat.
        </div>

        <!-- Customer Details -->
        <table class="details">
            <tr>
                <td width="60%"><b>Name:</b> <?= $name ?></td>
                <td><b>Date:</b> <?= date('d-m-Y', strtotime($date)) ?></td>
            </tr>
            <tr>
                <td><b>Address:</b> <?= $address ?></td>
                <td><b>Mobile:</b> <?= $mobile ?></td>
            </tr>
            <tr>
                <td><b>Payment:</b> <?= $payment_method ?></td>
                <td><b>Discount:</b> <?= $discountPercent ?>%</td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items">
            <tr>
                <th style="width:5%;">No.</th>
                <th style="width:50%;">Description</th>
                <th style="width:10%;">Qty</th>
                <th style="width:15%;">Rate (₹)</th>
                <th style="width:20%;">Amount (₹)</th>
            </tr>
            <?= $rows ?>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total</td>
                <td style="text-align:right;"><?= number_format($total,2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Discount (<?= $discountPercent ?>%)</td>
                <td style="text-align:right;">-<?= number_format($discountAmount,2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Final Amount</td>
                <td style="text-align:right; font-size:14px;"><?= number_format($finalAmount,2) ?></td>
            </tr>
        </table>

        <!-- Notes -->
        <div class="note">
            <b>Note:</b><br>
            1. No warranty applies if water gets into the motor.<br>
            2. Please retain this bill for warranty claims.
        </div>

        <div class="footer">
            For, Krishna Pump
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output
$filename = "motor_bill_" . time() . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]); // open in browser
?>
