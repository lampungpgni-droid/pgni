<?php
// config/database.php

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fadlanbe_pgnil');

// Koneksi Database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set karakter
mysqli_set_charset($conn, "utf8mb4");

// ============================================
// BASE URL - DETECT OTOMATIS
// ============================================
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = dirname($script_name);

// Jika berada di root, path kosong
if ($path == '/' || $path == '\\') {
    $path = '';
}

// Hapus trailing slash
$path = rtrim($path, '/');

// Base URL dengan trailing slash
define('BASE_URL', $protocol . $host . $path . '/');
define('ADMIN_URL', BASE_URL . 'admin/');

// Folder Upload
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . $path . '/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>