<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Total Guru Ngaji
$total_guru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji"))['total'] ?? 0;

// Guru Pending
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji WHERE status_verifikasi = 'pending'"))['total'] ?? 0;

// Guru Aktif
$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji WHERE status = 'aktif'"))['total'] ?? 0;

// Guru Terverifikasi
$total_verif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji WHERE status_verifikasi = 'disetujui'"))['total'] ?? 0;

// Total Berita
$total_berita = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM berita WHERE status = 'publish'"))['total'] ?? 0;

// Total Pengurus
$total_pengurus = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pengurus WHERE status = 'aktif'"))['total'] ?? 0;

// Total User
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_guru' => (int)$total_guru,
        'total_pending' => (int)$total_pending,
        'total_aktif' => (int)$total_aktif,
        'total_verifikasi' => (int)$total_verif,
        'total_berita' => (int)$total_berita,
        'total_pengurus' => (int)$total_pengurus,
        'total_user' => (int)$total_user
    ]
]);
?>
