<?php include "sidebar.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Motor Bill Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { margin-left: 250px; }
        .card { max-width: 900px; margin: auto; }
        table input { width: 100%; }
    </style>
    <script>
        function calculateRow(row) {
            let qty = parseFloat(row.querySelector(".qty").value) || 0;
            let rate = parseFloat(row.querySelector(".rate").value) || 0;
            let amount = qty * rate;
            row.querySelector(".amount").value = amount.toFixed(2);
            calculateFinal();
        }

        function calculateFinal() {
            let total = 0;
            document.querySelectorAll(".amount").forEach(el => {
                total += parseFloat(el.value) || 0;
            });

            let discount = parseFloat(document.getElementById("discount").value) || 0;
            let discountAmt = (total * discount) / 100;
            let finalAmt = total - discountAmt;

            document.getElementById("total_amount").value = total.toFixed(2);
            document.getElementById("final_amount").value = finalAmt.toFixed(2);
        }

        function addRow() {
            let table = document.getElementById("motorTable").getElementsByTagName("tbody")[0];
            let newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td><input type="text" name="description[]" class="form-control" required></td>
                <td><input type="number" name="qty[]" class="form-control qty" value="1" oninput="calculateRow(this.closest('tr'))" required></td>
                <td><input type="number" step="0.01" name="rate[]" class="form-control rate" oninput="calculateRow(this.closest('tr'))" required></td>
                <td><input type="text" class="form-control amount" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
            `;
            table.appendChild(newRow);
        }

        function removeRow(btn) {
            btn.closest("tr").remove();
            calculateFinal();
        }
    </script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg p-4">
        <h3 class="mb-4">Motor Bill Form</h3>
        <form method="post" action="motor_bill_pdf.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Date</label>
                    <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Address</label>
                <input type="text" class="form-control" name="address" required>
            </div>
            <div class="mb-3">
                <label>Mobile</label>
                <input type="text" class="form-control" name="mobile" required>
            </div>

            <h5>Motor Details</h5>
            <table class="table table-bordered" id="motorTable">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Rate (₹)</th>
                        <th>Amount (₹)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="description[]" class="form-control" required></td>
                        <td><input type="number" name="qty[]" class="form-control qty" value="1" oninput="calculateRow(this.closest('tr'))" required></td>
                        <td><input type="number" step="0.01" name="rate[]" class="form-control rate" oninput="calculateRow(this.closest('tr'))" required></td>
                        <td><input type="text" class="form-control amount" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-primary mb-3" onclick="addRow()">+ Add Motor</button>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Total Amount (₹)</label>
                    <input type="text" class="form-control" id="total_amount" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Discount (%)</label>
                    <input type="number" step="0.01" class="form-control" id="discount" name="discount" oninput="calculateFinal()" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Final Amount (₹)</label>
                    <input type="text" class="form-control" id="final_amount" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label>Payment Method</label>
                <select class="form-control" name="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Card">Card</option>
                </select>
            </div>
            <button type="submit" name="generate_pdf" class="btn btn-success">Generate PDF</button>
        </form>
    </div>
</div>
</body>
</html>
