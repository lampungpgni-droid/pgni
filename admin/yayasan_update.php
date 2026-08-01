<?php
// admin/yayasan_update.php - Proses Update Data Yayasan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH
// ============================================
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

// ============================================
// AMBIL DATA DARI FORM
// ============================================
$id = isset($_POST['id']) ? (int)$_POST['id'] : 1;
$nama_yayasan = isset($_POST['nama_yayasan']) ? mysqli_real_escape_string($conn, trim($_POST['nama_yayasan'])) : '';
$nama_pimpinan = isset($_POST['nama_pimpinan']) ? mysqli_real_escape_string($conn, trim($_POST['nama_pimpinan'])) : '';
$alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, trim($_POST['alamat'])) : '';
$kabupaten_id = isset($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : 0;
$kecamatan_id = isset($_POST['kecamatan_id']) ? (int)$_POST['kecamatan_id'] : 0;
$desa_id = isset($_POST['desa_id']) ? (int)$_POST['desa_id'] : 0;
$no_telp = isset($_POST['no_telp']) ? mysqli_real_escape_string($conn, trim($_POST['no_telp'])) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
$website = isset($_POST['website']) ? mysqli_real_escape_string($conn, trim($_POST['website'])) : '';
$deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($conn, trim($_POST['deskripsi'])) : '';
$visi = isset($_POST['visi']) ? mysqli_real_escape_string($conn, trim($_POST['visi'])) : '';
$misi = isset($_POST['misi']) ? mysqli_real_escape_string($conn, trim($_POST['misi'])) : '';
$tahun_berdiri = isset($_POST['tahun_berdiri']) ? (int)$_POST['tahun_berdiri'] : 0;
$status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'aktif';
$hapus_logo = isset($_POST['hapus_logo']) ? true : false;

// ============================================
// CEK APAKAH TABEL YAYASAN ADA
// ============================================
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'yayasan'");
$table_exists = mysqli_num_rows($check_table) > 0;

if (!$table_exists) {
    // Buat tabel yayasan
    $create_table = "CREATE TABLE IF NOT EXISTS `yayasan` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nama_yayasan` VARCHAR(255) NOT NULL,
        `nama_pimpinan` VARCHAR(255) NULL,
        `alamat` TEXT NULL,
        `kabupaten_id` INT(11) NULL,
        `kecamatan_id` INT(11) NULL,
        `desa_id` INT(11) NULL,
        `no_telp` VARCHAR(20) NULL,
        `email` VARCHAR(100) NULL,
        `website` VARCHAR(100) NULL,
        `logo` VARCHAR(255) NULL,
        `deskripsi` TEXT NULL,
        `visi` TEXT NULL,
        `misi` TEXT NULL,
        `tahun_berdiri` YEAR NULL,
        `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
    
    // Insert data default
    $insert_default = "INSERT INTO `yayasan` SET 
        `nama_yayasan` = 'PGNI Lampung',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
}

// ============================================
// CEK KOLOM YANG ADA DI TABEL
// ============================================
$columns = [];
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM yayasan");
while ($col = mysqli_fetch_assoc($check_columns)) {
    $columns[] = $col['Field'];
}

// ============================================
// VALIDASI
// ============================================
if (empty($nama_yayasan)) {
    header('Location: yayasan.php?error=empty_name');
    exit;
}

// ============================================
// AMBIL DATA LAMA UNTUK LOGO
// ============================================
$query_old = "SELECT logo FROM yayasan WHERE id = $id";
$result_old = mysqli_query($conn, $query_old);
$old_data = mysqli_fetch_assoc($result_old);
$logo = $old_data['logo'] ?? '';

// ============================================
// PROSES LOGO
// ============================================
if ($hapus_logo && $logo) {
    // Hapus file logo lama
    $old_path = $root_path . '/assets/images/logo/' . $logo;
    if (file_exists($old_path)) {
        unlink($old_path);
    }
    $logo = '';
}

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    // Hapus logo lama jika ada
    if ($logo) {
        $old_path = $root_path . '/assets/images/logo/' . $logo;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }
    
    $upload = upload_file($_FILES['logo'], 'logo', ['jpg','jpeg','png','gif','webp'], 5242880);
    if ($upload['status']) {
        $logo = $upload['nama_file'];
    }
}

// ============================================
// BUILD QUERY DINAMIS (HANYA KOLOM YANG ADA)
// ============================================
$set_parts = [];

if (in_array('nama_yayasan', $columns)) {
    $set_parts[] = "nama_yayasan = '$nama_yayasan'";
}
if (in_array('nama_pimpinan', $columns)) {
    $set_parts[] = "nama_pimpinan = '$nama_pimpinan'";
}
if (in_array('alamat', $columns)) {
    $set_parts[] = "alamat = '$alamat'";
}
if (in_array('kabupaten_id', $columns)) {
    $set_parts[] = "kabupaten_id = $kabupaten_id";
}
if (in_array('kecamatan_id', $columns)) {
    $set_parts[] = "kecamatan_id = $kecamatan_id";
}
if (in_array('desa_id', $columns)) {
    $set_parts[] = "desa_id = $desa_id";
}
if (in_array('no_telp', $columns)) {
    $set_parts[] = "no_telp = '$no_telp'";
}
if (in_array('email', $columns)) {
    $set_parts[] = "email = '$email'";
}
if (in_array('website', $columns)) {
    $set_parts[] = "website = '$website'";
}
if (in_array('logo', $columns)) {
    $set_parts[] = "logo = '$logo'";
}
if (in_array('deskripsi', $columns)) {
    $set_parts[] = "deskripsi = '$deskripsi'";
}
if (in_array('visi', $columns)) {
    $set_parts[] = "visi = '$visi'";
}
if (in_array('misi', $columns)) {
    $set_parts[] = "misi = '$misi'";
}
if (in_array('tahun_berdiri', $columns)) {
    $set_parts[] = "tahun_berdiri = $tahun_berdiri";
}
if (in_array('status', $columns)) {
    $set_parts[] = "status = '$status'";
}
if (in_array('updated_at', $columns)) {
    $set_parts[] = "updated_at = NOW()";
}

// ============================================
// EKSEKUSI UPDATE
// ============================================
if (empty($set_parts)) {
    header('Location: yayasan.php?error=no_fields');
    exit;
}

$query = "UPDATE yayasan SET " . implode(', ', $set_parts) . " WHERE id = $id";

// Debug - log query jika error
if (!mysqli_query($conn, $query)) {
    // Simpan error untuk debugging
    $error_message = mysqli_error($conn);
    error_log("Yayasan Update Error: " . $error_message);
    error_log("Query: " . $query);
    
    header('Location: yayasan.php?error=update_failed');
    exit;
} else {
    header('Location: yayasan.php?msg=update');
    exit;
}
?>