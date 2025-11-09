<?php
// save_journal.php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$allowed = ['journal','journal_hirani','journal_shivdhara'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status'=>'error','message'=>'No input']); exit; }

$table = $input['table'] ?? '';
if (!in_array($table, $allowed, true)) { echo json_encode(['status'=>'error','message'=>'Invalid table']); exit; }

$id = isset($input['id']) && $input['id'] !== '' ? (int)$input['id'] : 0;
$entry_date = $input['entry_date'] ?? '';
$details = $input['details'] ?? '';
$amount = isset($input['amount']) ? (float)$input['amount'] : 0;

if (!$entry_date || !$details || $amount <= 0) {
    echo json_encode(['status'=>'error','message'=>'Missing or invalid fields']);
    exit;
}

if ($id > 0) {
    // Update
    $sql = "UPDATE $table SET entry_date=?, details=?, amount=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['status'=>'error','message'=>$conn->error]); exit; }
    $stmt->bind_param('ssdi', $entry_date, $details, $amount, $id);
    $ok = $stmt->execute();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? '' : $stmt->error ]);
} else {
    // Insert
    $sql = "INSERT INTO $table (entry_date, details, amount) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['status'=>'error','message'=>$conn->error]); exit; }
    $stmt->bind_param('ssd', $entry_date, $details, $amount);
    $ok = $stmt->execute();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? '' : $stmt->error ]);
}
