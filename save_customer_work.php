<?php
require 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

$customer_name = $conn->real_escape_string($data['customer_name']);
$customer_contact = $conn->real_escape_string($data['customer_contact']);
$motor_parts = $data['parts'];
$bill_amount = floatval($data['bill_amount']);
$work_date = date('Y-m-d');

// Insert bill
$conn->query("INSERT INTO customer_bill (customer_name, contact_no, cost, bill_amount, date)
              VALUES ('$customer_name', '$customer_contact', 0, '$bill_amount', '$work_date')");
$bill_id = $conn->insert_id;

// Insert motor parts
foreach ($motor_parts as $part) {
    $pid = intval($part['id']);
    $conn->query("INSERT INTO customer_motor_work (bill_id, part_id) VALUES ($bill_id, $pid)");
}

// Return success
echo json_encode(["status" => "success", "id" => $bill_id]);
