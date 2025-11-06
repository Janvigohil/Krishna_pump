<?php
require 'dompdf/vendor/autoload.php';
include 'db_config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$worker_id = $_GET['id'] ?? 0;
$from = $_GET['start_date'] ?? '';
$to = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';

// Fetch worker
$worker = $conn->query("SELECT * FROM workers WHERE id=$worker_id")->fetch_assoc();
if (!$worker) { die("Worker not found"); }

$isPending = ($status==='pending');

// PDF options
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// ===============================
// ✅ LAUBORING SECTION (status=labour)
// ===============================
if ($status === 'labour') {
    $query = "SELECT * FROM labour_work WHERE worker_id=$worker_id ORDER BY work_date DESC";
    $result = $conn->query($query);

    $total_labour = 0;
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align:center; margin-bottom:10px; }
        table { width:100%; border-collapse: collapse; margin-bottom:15px; }
        th,td { border:1px solid #444; padding:5px; font-size:12px; }
        th { background:#f2f2f2; }
        .right { text-align:right; }
        .total { background:#f7e9d9; font-weight:bold; font-size:13px; color:blue; }
      </style>
    </head>
    <body>
      <h2>Lauboring Work Report - <?= htmlspecialchars($worker['name']) ?></h2>
      <p><b>Worker ID:</b> <?= $worker_id ?><br>
         <b>Status:</b> Lauboring</p>

      <table>
        <thead>
          <tr><th>Date</th><th>Description</th><th class="right">Price (₹)</th></tr>
        </thead>
        <tbody>
          <?php while($row=$result->fetch_assoc()): $total_labour += $row['price']; ?>
          <tr>
            <td><?= date('d-m-Y',strtotime($row['work_date'])) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td class="right"><?= number_format($row['price'],2) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
        <tfoot>
          <tr class="total"><td colspan="2" class="right">Total Lauboring Amount</td><td class="right">₹<?= number_format($total_labour,2) ?></td></tr>
        </tfoot>
      </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
}
// ===============================
// ✅ NORMAL WORK SECTION (pending/completed)
// ===============================
else {
    $filter = "WHERE w.worker_id=$worker_id";
    if ($from && $to) {
      $from_date = date('Y-m-d', strtotime($from));
      $to_date = date('Y-m-d', strtotime($to));
      $filter .= " AND w.work_date BETWEEN '$from_date' AND '$to_date'";
    }
    $filter .= $isPending ? " AND (w.bill IS NULL OR w.bill=0)" : " AND w.bill>0";

    $sql = "SELECT w.id,w.work_date,w.cost,w.bill,
                   GROUP_CONCAT(mp.name ORDER BY mp.name SEPARATOR ', ') AS parts
            FROM worker_work w
            LEFT JOIN worker_motor_work wm ON w.id=wm.work_id
            LEFT JOIN motor_parts mp ON wm.part_id=mp.id
            $filter
            GROUP BY w.id ORDER BY w.work_date DESC,w.id DESC";
    $result = $conn->query($sql);

    $total_advance = $conn->query("SELECT SUM(amount) total_advance FROM advance_salary WHERE worker_id=$worker_id")->fetch_assoc()['total_advance'] ?? 0;

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align:center; margin-bottom:10px; }
        table { width:100%; border-collapse: collapse; margin-bottom:15px; }
        th,td { border:1px solid #444; padding:5px; font-size:12px; }
        th { background:#f2f2f2; }
        .right { text-align:right; }
        .center { text-align:center; }
        .grand-total { background:#f7e9d9; font-weight:bold; color:blue; font-size:13px; }
        .salary { background:#e6ffe6; font-weight:bold; text-align:center; color:green; font-size:13px; }
        .advance { background:#ffe6e6; font-weight:bold; text-align:center; color:red; font-size:13px; }
      </style>
    </head>
    <body>
      <h2>Worker Report - <?= htmlspecialchars($worker['name']) ?></h2>
      <p><b>Worker ID:</b> <?= $worker_id ?> <br>
         <b>Filter:</b> <?= $from ? date('d-m-Y',strtotime($from)) : '---' ?> to <?= $to ? date('d-m-Y',strtotime($to)) : '---' ?><br>
         <b>Status:</b> <?= ucfirst($status) ?></p>

      <table>
        <thead>
          <tr>
            <th>Date</th><th>Sr</th><th>Parts</th>
            <th class="right">Cost</th><th class="right">Bill</th>
            <th class="right">Margin</th><th class="right">Salary (50%)</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $grand_cost=$grand_bill=$grand_margin=$grand_salary=0;
        $data_by_date=[];
        while($row=$result->fetch_assoc()){ $data_by_date[$row['work_date']][]=$row; }

        foreach($data_by_date as $date=>$entries):
          $sr=1;$day_cost=$day_bill=$day_margin=$day_salary=0;
          foreach($entries as $row):
            $cost=(float)$row['cost']; $bill=(float)$row['bill'];
            $margin=$bill-$cost; $salary=$margin/2;
            $day_cost+=$cost;$day_bill+=$bill;$day_margin+=$margin;$day_salary+=$salary;
            $grand_cost+=$cost;$grand_bill+=$bill;$grand_margin+=$margin;$grand_salary+=$salary;
        ?>
          <tr>
            <td><?= $sr===1?date('d-m-Y',strtotime($date)):"" ?></td>
            <td class="center"><?= $sr++ ?></td>
            <td><?= htmlspecialchars($row['parts']) ?></td>
            <td class="right"><?= number_format($cost,2) ?></td>
            <td class="right"><?= number_format($bill,2) ?></td>
            <td class="right"><?= number_format($margin,2) ?></td>
            <td class="right"><?= number_format($salary,2) ?></td>
          </tr>
        <?php endforeach; ?>
          <tr>
            <td colspan="3"><b>Total (<?= date('d-m-Y',strtotime($date)) ?>)</b></td>
            <td class="right"><b><?= number_format($day_cost,2) ?></b></td>
            <td class="right"><b><?= number_format($day_bill,2) ?></b></td>
            <td class="right"><b><?= number_format($day_margin,2) ?></b></td>
            <td class="right"><b><?= number_format($day_salary,2) ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="grand-total">
            <th colspan="3">Grand Total</th>
            <th class="right"><?= number_format($grand_cost,2) ?></th>
            <th class="right"><?= number_format($grand_bill,2) ?></th>
            <th class="right"><?= number_format($grand_margin,2) ?></th>
            <th class="right"><?= number_format($grand_salary,2) ?></th>
          </tr>
          <tr class="advance">
            <td colspan="6" class="right"><b>Advance Taken</b></td>
            <td class="right">- <?= number_format($total_advance,2) ?></td>
          </tr>
          <tr class="salary">
            <td colspan="6" class="right"><b>Remaining Salary</b></td>
            <td class="right"><?= number_format($grand_salary-$total_advance,2) ?></td>
          </tr>
        </tfoot>
      </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
}

// ===============================
// ✅ GENERATE AND DOWNLOAD PDF
// ===============================
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Format file name
$startLabel = $from ? date('d-m-Y', strtotime($from)) : 'start';
$endLabel   = $to   ? date('d-m-Y', strtotime($to)) : 'end';
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $worker['name']) . "_{$status}_{$startLabel}_to_{$endLabel}.pdf";

// Download
$dompdf->stream($filename, ["Attachment" => 1]);
?>
