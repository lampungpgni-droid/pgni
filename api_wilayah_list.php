<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$kabupaten_id = isset($_GET['kabupaten_id']) ? (int)$_GET['kabupaten_id'] : 0;

if ($kabupaten_id > 0) {
    // Get Kecamatan list
    $query = "SELECT id, nama FROM kecamatan WHERE kabupaten_id = $kabupaten_id ORDER BY nama ASC";
} else {
    // Get Kabupaten list
    $query = "SELECT id, nama FROM kabupaten ORDER BY nama ASC";
}

$result = mysqli_query($conn, $query);
$list = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = [
            'id' => (int)$row['id'],
            'nama' => $row['nama']
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $list
]);
?>
