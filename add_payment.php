<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company_id = intval($_POST['company_id']);
    $type       = $_POST['type']; // credit or debit
    $coupon_no  = trim($_POST['coupon_no'] ?? '');
    $entry_date = trim($_POST['entry_date'] ?? '');
    $descriptions = $_POST['description'] ?? [];
    $amounts      = $_POST['amount'] ?? [];

    // Ensure arrays
    if (!is_array($descriptions)) $descriptions = [$descriptions];
    if (!is_array($amounts)) $amounts = [$amounts];

    // Validation
    if ($company_id && $type && $coupon_no && $entry_date && !empty($descriptions)) {

        $coupon_no_safe = $conn->real_escape_string($coupon_no);

        // Check for existing coupon
        $check = $conn->query("SELECT id FROM coupons 
                               WHERE company_id=$company_id 
                               AND coupon_no='$coupon_no_safe' 
                               AND entry_date='$entry_date' 
                               LIMIT 1");

        if ($check && $check->num_rows > 0) {
            $coupon_id = $check->fetch_assoc()['id'];
        } else {
            // Insert new coupon
            $sql = "INSERT INTO coupons (company_id, entry_date, coupon_no) 
                    VALUES ($company_id, '$entry_date', '$coupon_no_safe')";
            if ($conn->query($sql) === TRUE) {
                $coupon_id = $conn->insert_id;
            } else {
                die("❌ Error inserting coupon: " . $conn->error);
            }
        }

        // Insert payment items (each description + amount)
        foreach ($descriptions as $i => $desc) {
            $desc_safe = $conn->real_escape_string($desc);
            $amt       = isset($amounts[$i]) ? floatval($amounts[$i]) : 0;

            if ($amt > 0) {
                $qty = 1;
                $price = $amt;
                $line_total = $amt;

                $sql2 = "INSERT INTO coupon_items 
                         (coupon_id, description, qty, price, line_total, type) 
                         VALUES ($coupon_id, '$desc_safe', $qty, $price, $line_total, '$type')";

                if (!$conn->query($sql2)) {
                    die("❌ Error inserting payment item: " . $conn->error);
                }
            }
        }

        // Redirect back to balance sheet
        header("Location: balance_sheet.php?company_id=$company_id");
        exit;

    } else {
        echo "⚠ Missing required fields or invalid data.";
    }

} else {
    echo "Invalid request.";
}
