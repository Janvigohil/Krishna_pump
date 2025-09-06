<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
    .header { text-align: center; font-size: 20px; font-weight: bold; }
    .info-table, .salary-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .info-table td { padding: 6px; }
    .salary-table th, .salary-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .salary-table th { background: #f2f2f2; }
    .footer { text-align: center; margin-top: 30px; font-style: italic; }
</style>

<div class="header">KRISHNA ENGINEERING - Worker Salary Report</div>
<div style="text-align:center;">Main Road, Surat - 395006 | +91 98765 43210</div>

<table class="info-table">
    <tr>
        <td><strong>Worker Name:</strong> <?= htmlspecialchars($worker_name) ?></td>
        <td><strong>Period:</strong> <?= $from ?> to <?= $to ?></td>
    </tr>
</table>

<h4 style="margin-top:20px;">Work Entries</h4>
<table class="salary-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Parts</th>
            <th>Bill</th>
            <th>Cost</th>
            <th>Margin</th>
            <th>Salary (50%)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td><?= $e['work_date'] ?></td>
            <td><?= htmlspecialchars($e['parts']) ?></td>
            <td>₹<?= number_format($e['bill'], 2) ?></td>
            <td>₹<?= number_format($e['cost'], 2) ?></td>
            <td>₹<?= number_format($e['margin'], 2) ?></td>
            <td>₹<?= number_format($e['salary'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="5" style="text-align:right;">Total Salary</th>
            <th>₹<?= number_format($total_salary, 2) ?></th>
        </tr>
        <tr>
            <th colspan="5" style="text-align:right;">Advance</th>
            <th>₹<?= number_format($advance, 2) ?></th>
        </tr>
        <tr>
            <th colspan="5" style="text-align:right;">Remaining</th>
            <th>₹<?= number_format($total_salary - $advance, 2) ?></th>
        </tr>
    </tbody>
</table>

<div class="footer">
    Thank you for your hard work!
</div>
