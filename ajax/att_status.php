<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$emp_id = $_GET['emp_id'] ?? '';
$today  = date('Y-m-d');

if (!$emp_id) { echo json_encode([]); exit; }

$stmt = getDB()->prepare("SELECT check_in, check_out FROM attendance WHERE emp_id = ? AND att_date = ?");
$stmt->bind_param('ss', $emp_id, $today);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode($row ?: []);
