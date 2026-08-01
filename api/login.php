<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$nik = $_POST['nik'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($nik) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'NIK dan Password harus diisi']);
    exit;
}

$nik = mysqli_real_escape_string($conn, $nik);
// Query ke tabel guru (asumsi tabel member/guru sesuai database.php)
$query = "SELECT * FROM guru WHERE nik = '$nik' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    // Verifikasi password (sesuaikan jika menggunakan password_hash atau MD5)
    // Jika di web pgnil menggunakan plain text atau enkripsi tertentu, sesuaikan di sini.
    // Contoh sederhana:
    if ($password === $row['password'] || password_verify($password, $row['password'])) {
        unset($row['password']); // Jangan kirim password balik
        echo json_encode([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => $row
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password salah']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'NIK tidak terdaftar']);
}
?>