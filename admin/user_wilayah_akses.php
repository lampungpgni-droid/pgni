<?php
// admin/user_wilayah_akses.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hanya super_admin yang bisa mengelola akses wilayah
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: dashboard.php?error=akses_ditolak');
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($user_id <= 0) {
    header('Location: user.php?error=notfound');
    exit;
}

// Cek user exists
$check_user = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($check_user);
if (!$user) {
    header('Location: user.php?error=notfound');
    exit;
}

// Proses simpan akses wilayah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wilayah'])) {
    // Hapus semua akses lama
    mysqli_query($conn, "DELETE FROM user_wilayah_akses WHERE user_id = $user_id");
    
    // Cek apakah memilih "Semua Wilayah"
    if (isset($_POST['akses_semua']) && $_POST['akses_semua'] == '1') {
        $insert = "INSERT INTO user_wilayah_akses (user_id, akses_semua) VALUES ($user_id, 1)";
        mysqli_query($conn, $insert);
    } else {
        // Simpan pilihan kabupaten
        if (isset($_POST['kabupaten']) && is_array($_POST['kabupaten'])) {
            foreach ($_POST['kabupaten'] as $kab_id) {
                $kab_id = (int)$kab_id;
                $insert = "INSERT INTO user_wilayah_akses (user_id, kabupaten_id) VALUES ($user_id, $kab_id)";
                mysqli_query($conn, $insert);
            }
        }
        
        // Simpan pilihan kecamatan
        if (isset($_POST['kecamatan']) && is_array($_POST['kecamatan'])) {
            foreach ($_POST['kecamatan'] as $kec_id) {
                $kec_id = (int)$kec_id;
                $insert = "INSERT INTO user_wilayah_akses (user_id, kecamatan_id) VALUES ($user_id, $kec_id)";
                mysqli_query($conn, $insert);
            }
        }
        
        // Simpan pilihan desa
        if (isset($_POST['desa']) && is_array($_POST['desa'])) {
            foreach ($_POST['desa'] as $desa_id) {
                $desa_id = (int)$desa_id;
                $insert = "INSERT INTO user_wilayah_akses (user_id, desa_id) VALUES ($user_id, $desa_id)";
                mysqli_query($conn, $insert);
            }
        }
    }
    
    header('Location: user_edit.php?id=' . $user_id . '&msg=wilayah');
    exit;
}

// Ambil akses yang sudah ada
$akses_kabupaten = [];
$akses_kecamatan = [];
$akses_desa = [];
$akses_semua = false;

$query_akses = "SELECT * FROM user_wilayah_akses WHERE user_id = $user_id";
$result_akses = mysqli_query($conn, $query_akses);
while ($row = mysqli_fetch_assoc($result_akses)) {
    if ($row['akses_semua']) {
        $akses_semua = true;
    }
    if ($row['kabupaten_id']) {
        $akses_kabupaten[] = $row['kabupaten_id'];
    }
    if ($row['kecamatan_id']) {
        $akses_kecamatan[] = $row['kecamatan_id'];
    }
    if ($row['desa_id']) {
        $akses_desa[] = $row['desa_id'];
    }
}
?>