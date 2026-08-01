<?php
// ajax/get_running_text.php
header('Content-Type: application/json');

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';

$query = "SELECT teks, urutan FROM running_text WHERE status = 'aktif' ORDER BY urutan ASC, id ASC";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);