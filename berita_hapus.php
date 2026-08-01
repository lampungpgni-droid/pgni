<?php
// admin/berita_hapus.php
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: berita.php?error=notfound');
    exit;
}

// Ambil data berita untuk mendapatkan nama file gambar
$query = "SELECT gambar FROM berita WHERE id = $id";
$result = mysqli_query($conn, $query);
$berita = mysqli_fetch_assoc($result);

if (!$berita) {
    header('Location: berita.php?error=notfound');
    exit;
}

// Hapus file gambar jika ada
if ($berita['gambar']) {
    $file_path = $root_path . '/assets/images/berita/' . $berita['gambar'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Hapus data dari database
$query_delete = "DELETE FROM berita WHERE id = $id";
if (mysqli_query($conn, $query_delete)) {
    header('Location: berita.php?msg=hapus');
    exit;
} else {
    header('Location: berita.php?error=hapus_gagal');
    exit;
}
?>