<?php
/**
 * update_payment_status.php - Update status donasi via AJAX
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$order_id = $input['order_id'];
$status = $input['status'] ?? 'pending';
$result = json_encode($input['result'] ?? []);

$query = "UPDATE donasi SET status = ?, response_raw = ? WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'sss', $status, $result, $order_id);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'affected' => $affected]);