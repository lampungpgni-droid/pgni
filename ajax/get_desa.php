<?php
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';

$kecamatan_id = isset($_GET['kecamatan_id']) ? (int)$_GET['kecamatan_id'] : 0;

if ($kecamatan_id > 0) {
    $query = "SELECT id, nama FROM desa WHERE kecamatan_id = $kecamatan_id ORDER BY nama";
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