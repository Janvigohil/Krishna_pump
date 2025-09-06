<?php
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(401); exit("Unauthorized"); }
require 'db_config.php';

$data=json_decode(file_get_contents('php://input'),true);
$id=(int)($data['id']??0);
$cost=(float)($data['cost']??0);
$bill=$data['bill']!=='' ? (float)$data['bill'] : null;

if($id>0){
  $stmt=$conn->prepare("UPDATE worker_work SET cost=?,bill=? WHERE id=?");
  $stmt->bind_param("ddi",$cost,$bill,$id);
  $stmt->execute();
  echo "ok";
}else http_response_code(400);
