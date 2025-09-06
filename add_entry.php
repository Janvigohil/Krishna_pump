<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (empty($_POST['company_id'])) {
            throw new Exception("Company ID is required");
        }
        if (empty($_POST['coupon_no'])) {
            throw new Exception("Coupon number is required");
        }
        if (empty($_POST['entry_date'])) {
            throw new Exception("Entry date is required");
        }
        if (empty($_POST['type']) || !in_array($_POST['type'], ['debit','credit'])) {
            throw new Exception("Invalid entry type");
        }
        if (empty($_POST['description']) || !is_array($_POST['description'])) {
            throw new Exception("At least one item is required");
        }

        $company_id = intval($_POST['company_id']);
        $coupon_no  = trim($_POST['coupon_no']);
        $entry_date = $_POST['entry_date'];
        $type       = $_POST['type'];

        // Insert or update coupon
        $stmt = $conn->prepare("
            INSERT INTO coupons (company_id, coupon_no, entry_date) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE entry_date = VALUES(entry_date)
        ");
        $stmt->bind_param("iss", $company_id, $coupon_no, $entry_date);
        $stmt->execute();

        $coupon_id = $conn->insert_id;
        if ($coupon_id == 0) {
            $stmt = $conn->prepare("SELECT id FROM coupons WHERE company_id=? AND coupon_no=? AND entry_date=? LIMIT 1");
            $stmt->bind_param("iss", $company_id, $coupon_no, $entry_date);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $coupon_id = $row['id'];
            } else {
                throw new Exception("Failed to get coupon ID");
            }
        }

        // Insert items
        $stmt = $conn->prepare("
            INSERT INTO coupon_items (coupon_id, description, qty, price, line_total, type) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($_POST['description'] as $i => $desc) {
            $qty = isset($_POST['qty'][$i]) ? (int)$_POST['qty'][$i] : 0;
            $price = isset($_POST['price'][$i]) ? (float)$_POST['price'][$i] : 0;
            $line_total = $qty * $price;
            if ($qty <= 0 || $price < 0) continue;
            $stmt->bind_param("isidds", $coupon_id, $desc, $qty, $price, $line_total, $type);
            $stmt->execute();
        }

        // ✅ Redirect back to company’s balance sheet
        header("Location: balance_sheet.php?company_id=" . $company_id);
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: companies.php");
    exit;
}
