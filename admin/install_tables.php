<?php
// install_simple.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install Tables</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1a6e3a; }
        .success { color: #155724; background: #d4edda; padding: 10px 15px; border-radius: 5px; margin: 5px 0; border-left: 4px solid #28a745; }
        .error { color: #721c24; background: #f8d7da; padding: 10px 15px; border-radius: 5px; margin: 5px 0; border-left: 4px solid #dc3545; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px 15px; border-radius: 5px; margin: 5px 0; border-left: 4px solid #17a2b8; }
        .summary { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #1a6e3a; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .btn:hover { background: #0e4a26; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Install Tables (Tanpa Foreign Key)</h1>
    <hr>";

// ============================================
// 1. HAPUS TABEL LAMA
// ============================================
echo "<h3>1. Hapus Tabel Lama</h3>";
$tables = ['user_permissions', 'role_permissions', 'user_wilayah_akses'];
foreach ($tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        if (mysqli_query($conn, "DROP TABLE IF EXISTS $table")) {
            echo "<div class='success'>✅ Tabel '$table' berhasil dihapus</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Tabel '$table' tidak ditemukan</div>";
    }
}

// ============================================
// 2. BUAT TABEL USER_PERMISSIONS
// ============================================
echo "<h3>2. Buat Tabel User Permissions</h3>";
$query = "CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_permission (user_id, permission_id),
    KEY idx_user_id (user_id),
    KEY idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $query)) {
    echo "<div class='success'>✅ Tabel 'user_permissions' berhasil dibuat</div>";
} else {
    echo "<div class='error'>❌ Error: " . mysqli_error($conn) . "</div>";
}

// ============================================
// 3. BUAT TABEL ROLE_PERMISSIONS
// ============================================
echo "<h3>3. Buat Tabel Role Permissions</h3>";
$query = "CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role, permission_id),
    KEY idx_role (role),
    KEY idx_permission_id_role (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $query)) {
    echo "<div class='success'>✅ Tabel 'role_permissions' berhasil dibuat</div>";
} else {
    echo "<div class='error'>❌ Error: " . mysqli_error($conn) . "</div>";
}

// ============================================
// 4. BUAT TABEL USER_WILAYAH_AKSES
// ============================================
echo "<h3>4. Buat Tabel User Wilayah Akses</h3>";
$query = "CREATE TABLE user_wilayah_akses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kabupaten_id INT DEFAULT NULL,
    kecamatan_id INT DEFAULT NULL,
    desa_id INT DEFAULT NULL,
    akses_semua TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_wilayah (user_id, kabupaten_id, kecamatan_id, desa_id),
    KEY idx_user_id (user_id),
    KEY idx_kabupaten_id (kabupaten_id),
    KEY idx_kecamatan_id (kecamatan_id),
    KEY idx_desa_id (desa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $query)) {
    echo "<div class='success'>✅ Tabel 'user_wilayah_akses' berhasil dibuat</div>";
} else {
    echo "<div class='error'>❌ Error: " . mysqli_error($conn) . "</div>";
}

// ============================================
// 5. INSERT PERMISSIONS
// ============================================
echo "<h3>5. Insert Data Permissions</h3>";

// Cek apakah ada data
$check = mysqli_query($conn, "SELECT COUNT(*) as total FROM permissions");
$row = mysqli_fetch_assoc($check);

if ($row['total'] == 0) {
    $permissions = [
        ['manage_users', 'Mengelola user (tambah, edit, hapus)', 'user'],
        ['manage_teachers', 'Mengelola data guru', 'teacher'],
        ['manage_news', 'Mengelola berita', 'news'],
        ['manage_officials', 'Mengelola pengurus', 'official'],
        ['manage_yayasan', 'Mengelola data yayasan', 'yayasan'],
        ['view_reports', 'Melihat laporan', 'report'],
        ['manage_permissions', 'Mengelola hak akses', 'permission'],
        ['manage_wilayah', 'Mengelola data wilayah', 'wilayah']
    ];
    
    foreach ($permissions as $perm) {
        $query = "INSERT INTO permissions (name, description, module) 
                  VALUES ('" . mysqli_real_escape_string($conn, $perm[0]) . "', 
                          '" . mysqli_real_escape_string($conn, $perm[1]) . "', 
                          '" . mysqli_real_escape_string($conn, $perm[2]) . "')";
        if (mysqli_query($conn, $query)) {
            echo "<div class='success'>✅ Permission '{$perm[0]}' ditambahkan</div>";
        }
    }
} else {
    echo "<div class='info'>ℹ️ Data permissions sudah ada (" . $row['total'] . " record)</div>";
}

// ============================================
// 6. INSERT ROLE PERMISSIONS
// ============================================
echo "<h3>6. Insert Role Permissions</h3>";

$role_perms = [
    'admin' => ['manage_teachers', 'manage_news', 'manage_officials', 'manage_yayasan', 'view_reports'],
    'petugas_kecamatan' => ['manage_teachers', 'view_reports']
];

foreach ($role_perms as $role => $perms) {
    foreach ($perms as $perm_name) {
        $query = "INSERT IGNORE INTO role_permissions (role, permission_id) 
                  SELECT '$role', id FROM permissions WHERE name = '$perm_name'";
        if (mysqli_query($conn, $query)) {
            echo "<div class='success'>✅ Role '$role' -> '$perm_name'</div>";
        }
    }
}

// ============================================
// 7. VERIFIKASI
// ============================================
echo "<h3>7. Verifikasi</h3>";

$tables = ['permissions', 'user_permissions', 'role_permissions', 'user_wilayah_akses'];
$all_ok = true;

foreach ($tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM $table");
        $row = mysqli_fetch_assoc($count);
        echo "<div class='success'>✅ Tabel '$table' - " . $row['total'] . " record</div>";
    } else {
        echo "<div class='error'>❌ Tabel '$table' TIDAK terdeteksi</div>";
        $all_ok = false;
    }
}

echo "<div class='summary'>";
if ($all_ok) {
    echo "<div class='success'>✅ Semua tabel berhasil dibuat tanpa foreign key!</div>";
    echo "<p>Sistem sekarang siap digunakan. Foreign key tidak digunakan untuk menghindari error constraint.</p>";
} else {
    echo "<div class='error'>❌ Ada tabel yang gagal dibuat. Periksa error di atas.</div>";
}
echo "<a href='user.php' class='btn'>Kembali ke Manajemen User</a>";
echo "</div>";

echo "</div></body></html>";
?>