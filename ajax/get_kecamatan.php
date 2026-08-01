<?php
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';

$kabupaten_id = isset($_GET['kabupaten_id']) ? (int)$_GET['kabupaten_id'] : 0;

if ($kabupaten_id > 0) {
    $query = "SELECT id, nama FROM kecamatan WHERE kabupaten_id = $kabupaten_id ORDER BY nama";
    $result = mysqli_query($conn, $query);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>