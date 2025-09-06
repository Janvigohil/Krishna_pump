<?php
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(401); echo json_encode(['status'=>'unauthorized']); exit(); }
require 'db_config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$worker_id = isset($payload['worker_id']) ? (int)$payload['worker_id'] : 0;
$parts = isset($payload['parts']) && is_array($payload['parts']) ? $payload['parts'] : [];
$bill = isset($payload['bill']) && $payload['bill'] !== '' ? (float)$payload['bill'] : null;
$manual_cost = isset($payload['cost']) ? (float)$payload['cost'] : null;

if ($worker_id<=0 || empty($parts)) {
  echo json_encode(['status'=>'error','message'=>'Invalid worker or parts']); exit();
}

// recalc cost from parts if admin didn't override
if ($manual_cost !== null) {
  $total_cost = $manual_cost;
} else {
  $counts = [];
  foreach ($parts as $pid) {
    $pid = (int)$pid;
    if ($pid>0) $counts[$pid] = ($counts[$pid] ?? 0) + 1;
  }
  $ids = implode(',', array_keys($counts));
  $q = $conn->query("SELECT id, cost FROM motor_parts WHERE id IN ($ids)");
  $total_cost = 0.00;
  $costMap = [];
  while ($row = $q->fetch_assoc()) {
    $costMap[(int)$row['id']] = (float)$row['cost'];
  }
  foreach ($counts as $pid=>$qty) {
    $total_cost += ($costMap[$pid] ?? 0) * $qty;
  }
}

// create work_no
$work_no = 'WK'.date('Ymd').'-'.rand(1000,9999);

// insert into worker_work
$stmt = $conn->prepare("INSERT INTO worker_work (work_no, work_date, cost, bill, worker_id) VALUES (?, CURDATE(), ?, ?, ?)");
$stmt->bind_param('sddi', $work_no, $total_cost, $bill, $worker_id);
if (!$stmt->execute()) { echo json_encode(['status'=>'error','message'=>'insert failed']); exit(); }
$work_id = $stmt->insert_id;

// insert parts rows
$ins = $conn->prepare("INSERT INTO worker_motor_work (work_id, part_id) VALUES (?, ?)");
foreach ($parts as $pid) {
  $pid = (int)$pid;
  if ($pid>0) { $ins->bind_param('ii', $work_id, $pid); $ins->execute(); }
}

echo json_encode(['status'=>'success','work_id'=>$work_id,'is_pending'=> is_null($bill) || $bill==0 ]);
