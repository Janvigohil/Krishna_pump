<?php
session_start();
if (!isset($_SESSION['admin'])) { echo json_encode(['status'=>'unauthorized']); exit; }
require 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) { echo json_encode(['status'=>'invalid']); exit; }

$id = isset($data['id']) && $data['id'] ? (int)$data['id'] : null;
$date = $conn->real_escape_string($data['entry_date']);
$details = $conn->real_escape_string($data['details']);
$amount = (float)$data['amount'];

if ($id) {
  $q = "UPDATE journal SET entry_date='$date', details='$details', amount='$amount' WHERE id=$id";
} else {
  $q = "INSERT INTO journal (entry_date, details, amount) VALUES ('$date', '$details', '$amount')";
}

if ($conn->query($q)) echo json_encode(['status'=>'success']);
else echo json_encode(['status'=>'error', 'error'=>$conn->error]);
?>
