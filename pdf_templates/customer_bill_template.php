<?php
$date = date('d-m-Y');
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
    .header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 10px; }
    .info-table, .bill-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .info-table td { padding: 6px; }
    .bill-table th, .bill-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .bill-table th { background: #f2f2f2; }
    .footer { text-align: center; margin-top: 30px; font-style: italic; }
</style>

<div class="header">KRISHNA ENGINEERING</div>
<div style="text-align:center;">Main Road, Surat - 395006 | +91 98765 43210</div>

<table class="info-table">
    <tr>
        <td><strong>Invoice No:</strong> <?= $work_id ?></td>
        <td><strong>Date:</strong> <?= $date ?></td>
    </tr>
    <tr>
        <td><strong>Customer Name:</strong> <?= htmlspecialchars($customer_name) ?></td>
        <td><strong>Contact:</strong> <?= htmlspecialchars($customer_contact) ?></td>
    </tr>
</table>

<h4 style="margin-top:20px;">Motor Work Details</h4>
<table class="bill-table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Motor Description</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($part_names as $part): ?>
        <tr>
            <td><?= $part['sr'] ?></td>
            <td><?= htmlspecialchars($part['name']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" style="text-align:right; font-weight:bold;">Total Bill: ₹<?= number_format($bill_amount, 2) ?></td>
        </tr>
    </tbody>
</table>

<div class="footer">
    Thank you for choosing Krishna Engineering 
</div>
