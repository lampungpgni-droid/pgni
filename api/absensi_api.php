<?php
// api/absensi_api.php - API Absensi
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

// Fungsi untuk menghitung jarak antar 2 titik koordinat (Haversine formula)
function hitung_jarak($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// Ambil parameter
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'scan') {
    // Scan QR Code untuk absensi
    $kegiatan_id = isset($_POST['kegiatan_id']) ? (int)$_POST['kegiatan_id'] : 0;
    $guru_id = isset($_POST['guru_id']) ? (int)$_POST['guru_id'] : 0;
    $user_lat = isset($_POST['latitude']) ? (float)$_POST['latitude'] : 0;
    $user_lon = isset($_POST['longitude']) ? (float)$_POST['longitude'] : 0;
    $kode_absensi = isset($_POST['kode_absensi']) ? mysqli_real_escape_string($conn, $_POST['kode_absensi']) : '';

    if (!$kegiatan_id || !$guru_id) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Cek kegiatan
    $kegiatan_query = "SELECT * FROM kegiatan WHERE id = $kegiatan_id AND status = 'aktif'";
    $kegiatan_result = mysqli_query($conn, $kegiatan_query);
    $kegiatan = mysqli_fetch_assoc($kegiatan_result);

    if (!$kegiatan) {
        echo json_encode(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan atau tidak aktif']);
        exit;
    }

    // Cek kuota
    if ($kegiatan['kuota'] > 0) {
        $count_query = "SELECT COUNT(*) as total FROM absensi WHERE kegiatan_id = $kegiatan_id";
        $count_result = mysqli_query($conn, $count_query);
        $total = mysqli_fetch_assoc($count_result)['total'];
        if ($total >= $kegiatan['kuota']) {
            echo json_encode(['status' => 'error', 'message' => 'Kuota peserta sudah penuh']);
            exit;
        }
    }

    // Cek apakah sudah absen
    $check_query = "SELECT * FROM absensi WHERE kegiatan_id = $kegiatan_id AND guru_id = $guru_id";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah melakukan absensi untuk kegiatan ini']);
        exit;
    }

    // Validasi GPS jika koordinat tersedia
    $jarak = null;
    if ($kegiatan['latitude'] && $kegiatan['longitude'] && $user_lat && $user_lon) {
        $jarak = hitung_jarak($kegiatan['latitude'], $kegiatan['longitude'], $user_lat, $user_lon);
        if ($jarak > $kegiatan['radius']) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Anda berada di luar radius yang diizinkan. Jarak Anda ' . round($jarak) . ' meter dari lokasi (maksimal ' . $kegiatan['radius'] . ' meter)',
                'jarak' => round($jarak)
            ]);
            exit;
        }
    }

    // Simpan absensi
    $insert_query = "INSERT INTO absensi (kegiatan_id, guru_id, kode_absensi, scan_time, latitude, longitude, jarak, status) 
                     VALUES ($kegiatan_id, $guru_id, '$kode_absensi', NOW(), $user_lat, $user_lon, " . ($jarak !== null ? round($jarak, 2) : "NULL") . ", 'hadir')";

    if (mysqli_query($conn, $insert_query)) {
        // Kirim notifikasi ke member
        $notif_query = "INSERT INTO notifikasi (user_id, tipe, judul, pesan, link, created_at) 
                        VALUES ($guru_id, 'absensi', 'Absensi Berhasil', 
                        'Anda berhasil melakukan absensi untuk kegiatan \"{$kegiatan['judul']}\"', 
                        '/pgnil/member/profile.php', NOW())";
        mysqli_query($conn, $notif_query);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Absensi berhasil! Selamat mengikuti kegiatan.',
            'data' => [
                'nama' => $guru_id,
                'jarak' => $jarak !== null ? round($jarak) . ' meter' : 'Tidak terdeteksi'
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan absensi: ' . mysqli_error($conn)]);
    }
} elseif ($action === 'cek_status') {
    // Cek status absensi untuk kegiatan
    $kegiatan_id = isset($_GET['kegiatan_id']) ? (int)$_GET['kegiatan_id'] : 0;
    $guru_id = isset($_GET['guru_id']) ? (int)$_GET['guru_id'] : 0;

    if (!$kegiatan_id || !$guru_id) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    $check_query = "SELECT * FROM absensi WHERE kegiatan_id = $kegiatan_id AND guru_id = $guru_id";
    $check_result = mysqli_query($conn, $check_query);
    $sudah_absen = mysqli_num_rows($check_result) > 0;

    echo json_encode([
        'status' => 'success',
        'sudah_absen' => $sudah_absen
    ]);
} elseif ($action === 'get_kegiatan') {
    // Ambil detail kegiatan
    $kegiatan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$kegiatan_id) {
        echo json_encode(['status' => 'error', 'message' => 'ID kegiatan tidak valid']);
        exit;
    }

    $query = "SELECT * FROM kegiatan WHERE id = $kegiatan_id AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    $kegiatan = mysqli_fetch_assoc($result);

    if ($kegiatan) {
        echo json_encode([
            'status' => 'success',
            'data' => $kegiatan
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
}