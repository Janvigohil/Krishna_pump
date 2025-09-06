<?php
require 'dompdf/autoload.inc.php';
include 'db_config.php';

use Dompdf\Dompdf;

// --- Input ---
if (empty($_GET['company_id'])) {
    die("Company ID required");
}
$company_id = intval($_GET['company_id']);
$coupon_filter = isset($_GET['coupon_filter']) ? trim($_GET['coupon_filter']) : "";

// --- Fetch company ---
$company = $conn->query("SELECT name FROM companies WHERE id=$company_id")->fetch_assoc();

// --- Fetch entries (same as balance_sheet.php) ---
$sql = "SELECT c.entry_date, c.coupon_no, ci.description, ci.qty, ci.price, ci.line_total, ci.type
        FROM coupons c
        JOIN coupon_items ci ON c.id = ci.coupon_id
        WHERE c.company_id=$company_id";
if ($coupon_filter) {
    $sql .= " AND c.coupon_no='" . $conn->real_escape_string($coupon_filter) . "'";
}
$sql .= " ORDER BY c.entry_date ASC, c.id ASC, ci.id ASC";
$result = $conn->query($sql);

$debits = [];
$credits = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['entry_date']."_".$row['coupon_no'];
    if ($row['type'] === 'debit') {
        $debits[$key]['date'] = $row['entry_date'];
        $debits[$key]['cc']   = $row['coupon_no'];
        $debits[$key]['items'][] = "{$row['description']} ({$row['qty']}×{$row['price']} = {$row['line_total']})";
        $debits[$key]['total'] = ($debits[$key]['total'] ?? 0) + $row['line_total'];
    } else {
        $credits[$key]['date'] = $row['entry_date'];
        $credits[$key]['cc']   = $row['coupon_no'];
        $credits[$key]['items'][] = "{$row['description']} ({$row['qty']}×{$row['price']} = {$row['line_total']})";
        $credits[$key]['total'] = ($credits[$key]['total'] ?? 0) + $row['line_total'];
    }
}

// --- Build HTML for PDF ---
$html = "
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  h2 { text-align: center; margin-bottom: 10px; }
  table { border-collapse: collapse; width: 100%; font-size: 11px; }
  th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
  th { background: #343a40; color: #fff; }
  .warning { background: #fff3cd; }
  .info { background: #d1ecf1; }
</style>
<h2>Balance Sheet - {$company['name']}</h2>";

if ($coupon_filter) {
    $html .= "<p><b>Filtered by Coupon No:</b> {$coupon_filter}</p>";
}

$html .= "<table>
            <thead>
              <tr><th colspan='4'>Debit</th><th colspan='4'>Credit</th></tr>
              <tr>
                <th>Date</th><th>CC</th><th>Description</th><th>Amount</th>
                <th>Date</th><th>CC</th><th>Description</th><th>Amount</th>
              </tr>
            </thead><tbody>";

$debitGroups  = array_values($debits);
$creditGroups = array_values($credits);
$rows = max(count($debitGroups), count($creditGroups));
$total_debit=0; $total_credit=0;

for ($i=0; $i<$rows; $i++) {
    $html .= "<tr>";
    if (isset($debitGroups[$i])) {
        $d = $debitGroups[$i];
        $html .= "<td>{$d['date']}</td><td>{$d['cc']}</td>
                  <td>".implode("<br>",$d['items'])."</td>
                  <td>{$d['total']}</td>";
        $total_debit += $d['total'];
    } else {
        $html .= "<td></td><td></td><td></td><td></td>";
    }
    if (isset($creditGroups[$i])) {
        $c = $creditGroups[$i];
        $html .= "<td>{$c['date']}</td><td>{$c['cc']}</td>
                  <td>".implode("<br>",$c['items'])."</td>
                  <td>{$c['total']}</td>";
        $total_credit += $c['total'];
    } else {
        $html .= "<td></td><td></td><td></td><td></td>";
    }
    $html .= "</tr>";
}

// --- Remaining Payment row ---
if ($total_debit != $total_credit) {
    $diff = abs($total_debit - $total_credit);
    $html .= "<tr class='warning'>";
    if ($total_debit < $total_credit) {
        $html .= "<td></td><td></td><td>Remaining Payment</td><td>$diff</td>
                  <td></td><td></td><td></td><td></td>";
        $total_debit += $diff;
    } else {
        $html .= "<td></td><td></td><td></td><td></td>
                  <td></td><td></td><td>Remaining Payment</td><td>$diff</td>";
        $total_credit += $diff;
    }
    $html .= "</tr>";
}

// --- Totals row ---
$html .= "<tr class='info'>
            <td colspan='3'><b>Total Debit</b></td><td><b>$total_debit</b></td>
            <td colspan='3'><b>Total Credit</b></td><td><b>$total_credit</b></td>
          </tr>";

$html .= "</tbody></table>";

// --- Generate PDF ---
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("balance_sheet_{$company_id}.pdf", ["Attachment" => true]);
