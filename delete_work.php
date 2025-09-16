<?php
include 'db_config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin'])) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

$data=json_decode(file_get_contents("php://input"),true);
$id=(int)($data['id']??0);

if($id>0){
  // delete linked motor parts first
  $conn->query("DELETE FROM worker_motor_work WHERE work_id=$id");
  // delete work
  $conn->query("DELETE FROM worker_work WHERE id=$id");
  echo json_encode(['success'=>true]);
}else{
  echo json_encode(['success'=>false]);
}
