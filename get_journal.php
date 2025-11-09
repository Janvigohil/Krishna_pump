<?php
// get_journal.php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$allowed = ['journal','journal_hirani','journal_shivdhara'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = isset($_GET['table']) ? $_GET['table'] : 'journal';

if (!$id || !in_array($table, $allowed, true)) {
    echo json_encode(['status'=>'error']);
    exit;
}

$stmt = $conn->prepare("SELECT id, entry_date, details, amount FROM $table WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows) {
    $entry = $res->fetch_assoc();
    echo json_encode(['status'=>'success','entry'=>$entry]);
} else {
    echo json_encode(['status'=>'error']);
}
