<?php
// delete_journal.php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$allowed = ['journal','journal_hirani','journal_shivdhara'];
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status'=>'error','message'=>'No input']); exit; }

$id = isset($input['id']) ? (int)$input['id'] : 0;
$table = $input['table'] ?? '';
if (!$id || !in_array($table, $allowed, true)) { echo json_encode(['status'=>'error','message'=>'Invalid request']); exit; }

$stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? '' : $stmt->error]);