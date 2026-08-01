<?php
// admin/kegiatan_tambah.php - Tambah Kegiatan
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

$title = 'Tambah Kegiatan';
$error = '';
$success = '';

// ============================================
// PASTIKAN TABEL KEGIATAN ADA
// ============================================
$create_table = "CREATE TABLE IF NOT EXISTS `kegiatan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `judul` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `jenis` VARCHAR(50) NULL DEFAULT 'pelatihan',
    `tanggal_mulai` DATETIME NULL,
    `tanggal_selesai` DATETIME NULL,
    `lokasi` VARCHAR(255) NULL,
    `alamat` TEXT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `radius` INT(11) DEFAULT 100,
    `kuota` INT(11) DEFAULT 0,
    `qr_code` VARCHAR(255) NULL,
    `status` VARCHAR(20) NULL DEFAULT 'draft',
    `created_by` INT(11) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_tanggal_mulai` (`tanggal_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $create_table)) {
    die('Gagal membuat tabel kegiatan: ' . mysqli_error($conn));
}

// ============================================
// CEK KOLOM YANG ADA DI TABEL KEGIATAN
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM kegiatan");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

// ============================================
// DAFTAR JENIS KEGIATAN
// ============================================
$jenis_list = [
    'pelatihan' => 'Pelatihan',
    'rapat' => 'Rapat',
    'sosialisasi' => 'Sosialisasi',
    'workshop' => 'Workshop',
    'lainnya' => 'Lainnya'
];

// ============================================
// PROSES FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $jenis = isset($_POST['jenis']) ? $_POST['jenis'] : 'pelatihan';
    $tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : '';
    $tanggal_selesai = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : '';
    $lokasi = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    $latitude = isset($_POST['latitude']) && !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $radius = isset($_POST['radius']) ? (int)$_POST['radius'] : 100;
    $kuota = isset($_POST['kuota']) ? (int)$_POST['kuota'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'draft';

    // Validasi
    if (empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($lokasi)) {
        $error = 'Judul, Tanggal Mulai, Tanggal Selesai, dan Lokasi wajib diisi!';
    } else {
        // Escape semua data
        $judul_esc = mysqli_real_escape_string($conn, $judul);
        $deskripsi_esc = mysqli_real_escape_string($conn, $deskripsi);
        $jenis_esc = mysqli_real_escape_string($conn, $jenis);
        $tanggal_mulai_esc = mysqli_real_escape_string($conn, $tanggal_mulai);
        $tanggal_selesai_esc = mysqli_real_escape_string($conn, $tanggal_selesai);
        $lokasi_esc = mysqli_real_escape_string($conn, $lokasi);
        $alamat_esc = mysqli_real_escape_string($conn, $alamat);
        $status_esc = mysqli_real_escape_string($conn, $status);
        
        // Generate QR Code filename
        $qr_filename = 'qr_' . time() . '_' . rand(1000, 9999) . '.png';
        
        // ==========================================
        // BUILD QUERY
        // ==========================================
        $query = "INSERT INTO kegiatan (
            judul, 
            deskripsi, 
            jenis, 
            tanggal_mulai, 
            tanggal_selesai, 
            lokasi, 
            alamat, 
            latitude, 
            longitude, 
            radius, 
            kuota, 
            qr_code, 
            status, 
            created_by, 
            created_at
        ) VALUES (
            '$judul_esc',
            " . (empty($deskripsi_esc) ? "NULL" : "'$deskripsi_esc'") . ",
            '$jenis_esc',
            '$tanggal_mulai_esc',
            '$tanggal_selesai_esc',
            '$lokasi_esc',
            " . (empty($alamat_esc) ? "NULL" : "'$alamat_esc'") . ",
            " . ($latitude !== null ? $latitude : "NULL") . ",
            " . ($longitude !== null ? $longitude : "NULL") . ",
            $radius,
            $kuota,
            '$qr_filename',
            '$status_esc',
            " . $_SESSION['user_id'] . ",
            NOW()
        )";

        if (mysqli_query($conn, $query)) {
            $kegiatan_id = mysqli_insert_id($conn);
            
            // Kirim notifikasi ke semua member
            if (function_exists('send_notification_to_all')) {
                send_notification_to_all(
                    'Kegiatan Baru: ' . $judul,
                    'Kegiatan "' . $judul . '" akan dilaksanakan pada ' . date('d/m/Y H:i', strtotime($tanggal_mulai)) . ' di ' . $lokasi,
                    '/pgnil/member/absensi_scan.php?kegiatan_id=' . $kegiatan_id,
                    'kegiatan',
                    'member'
                );
            }
            
            $_SESSION['success'] = 'Kegiatan berhasil ditambahkan!';
            header('Location: kegiatan.php?msg=tambah');
            exit;
        } else {
            $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
        }
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f2f5;
}
.page-header-left h2 {
    font-size: 1.4rem;
    color: #1a1a2e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.page-header-left h2 i { color: #d4a847; }
.page-header-left .text-muted {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin: 0;
}
.page-header-right .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}
.btn-secondary {
    background: #95a5a6;
    color: #fff;
}
.btn-secondary:hover {
    background: #7f8c8d;
    color: #fff;
}

.form-wrapper {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.form-group {
    margin-bottom: 18px;
}
.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
    font-size: 0.9rem;
}
.form-label .required {
    color: #e74c3c;
    font-weight: 700;
    margin-left: 3px;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 0.9rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: #fff;
    color: #333;
}
.form-control:focus {
    border-color: #1a6e3a !important;
    outline: none;
    box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
}
textarea.form-control {
    resize: vertical;
    font-family: inherit;
}
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-text {
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
    display: block;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}
.btn-primary {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}
.btn-danger {
    background: #e74c3c;
    color: #fff;
}
.btn-danger:hover {
    background: #c0392b;
    color: #fff;
}
.btn-lg {
    padding: 12px 32px;
    font-size: 1rem;
    border-radius: 10px;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
    margin-top: 10px;
    flex-wrap: wrap;
}
.form-actions .btn {
    min-width: 140px;
    justify-content: center;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    .page-header-right .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- ============================================ -->
<!-- CONTENT -->
// admin/kegiatan_tambah.php - Tambah Kegiatan
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

$title = 'Tambah Kegiatan';
$error = '';
$success = '';

// ============================================
// PASTIKAN TABEL KEGIATAN ADA
// ============================================
$create_table = "CREATE TABLE IF NOT EXISTS `kegiatan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `judul` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `jenis` VARCHAR(50) NULL DEFAULT 'pelatihan',
    `tanggal_mulai` DATETIME NULL,
    `tanggal_selesai` DATETIME NULL,
    `lokasi` VARCHAR(255) NULL,
    `alamat` TEXT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `radius` INT(11) DEFAULT 100,
    `kuota` INT(11) DEFAULT 0,
    `qr_code` VARCHAR(255) NULL,
    `status` VARCHAR(20) NULL DEFAULT 'draft',
    `created_by` INT(11) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_tanggal_mulai` (`tanggal_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $create_table)) {
    die('Gagal membuat tabel kegiatan: ' . mysqli_error($conn));
}

// ============================================
// CEK KOLOM YANG ADA DI TABEL KEGIATAN
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM kegiatan");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

// ============================================
// DAFTAR JENIS KEGIATAN
// ============================================
$jenis_list = [
    'pelatihan' => 'Pelatihan',
    'rapat' => 'Rapat',
    'sosialisasi' => 'Sosialisasi',
    'workshop' => 'Workshop',
    'lainnya' => 'Lainnya'
];

// ============================================
// PROSES FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $jenis = isset($_POST['jenis']) ? $_POST['jenis'] : 'pelatihan';
    $tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : '';
    $tanggal_selesai = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : '';
    $lokasi = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    $latitude = isset($_POST['latitude']) && !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $radius = isset($_POST['radius']) ? (int)$_POST['radius'] : 100;
    $kuota = isset($_POST['kuota']) ? (int)$_POST['kuota'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'draft';

    // Validasi
    if (empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($lokasi)) {
        $error = 'Judul, Tanggal Mulai, Tanggal Selesai, dan Lokasi wajib diisi!';
    } else {
        // Escape semua data
        $judul_esc = mysqli_real_escape_string($conn, $judul);
        $deskripsi_esc = mysqli_real_escape_string($conn, $deskripsi);
        $jenis_esc = mysqli_real_escape_string($conn, $jenis);
        $tanggal_mulai_esc = mysqli_real_escape_string($conn, $tanggal_mulai);
        $tanggal_selesai_esc = mysqli_real_escape_string($conn, $tanggal_selesai);
        $lokasi_esc = mysqli_real_escape_string($conn, $lokasi);
        $alamat_esc = mysqli_real_escape_string($conn, $alamat);
        $status_esc = mysqli_real_escape_string($conn, $status);
        
        // Generate QR Code filename
        $qr_filename = 'qr_' . time() . '_' . rand(1000, 9999) . '.png';
        
        // ==========================================
        // BUILD QUERY
        // ==========================================
        $query = "INSERT INTO kegiatan (
            judul, 
            deskripsi, 
            jenis, 
            tanggal_mulai, 
            tanggal_selesai, 
            lokasi, 
            alamat, 
            latitude, 
            longitude, 
            radius, 
            kuota, 
            qr_code, 
            status, 
            created_by, 
            created_at
        ) VALUES (
            '$judul_esc',
            " . (empty($deskripsi_esc) ? "NULL" : "'$deskripsi_esc'") . ",
            '$jenis_esc',
            '$tanggal_mulai_esc',
            '$tanggal_selesai_esc',
            '$lokasi_esc',
            " . (empty($alamat_esc) ? "NULL" : "'$alamat_esc'") . ",
            " . ($latitude !== null ? $latitude : "NULL") . ",
            " . ($longitude !== null ? $longitude : "NULL") . ",
            $radius,
            $kuota,
            '$qr_filename',
            '$status_esc',
            " . $_SESSION['user_id'] . ",
            NOW()
        )";

        if (mysqli_query($conn, $query)) {
            $kegiatan_id = mysqli_insert_id($conn);
            
            // Kirim notifikasi ke semua member
            if (function_exists('send_notification_to_all')) {
                send_notification_to_all(
                    'Kegiatan Baru: ' . $judul,
                    'Kegiatan "' . $judul . '" akan dilaksanakan pada ' . date('d/m/Y H:i', strtotime($tanggal_mulai)) . ' di ' . $lokasi,
                    '/pgnil/member/absensi_scan.php?kegiatan_id=' . $kegiatan_id,
                    'kegiatan',
                    'member'
                );
            }
            
            $_SESSION['success'] = 'Kegiatan berhasil ditambahkan!';
            header('Location: kegiatan.php?msg=tambah');
            exit;
        } else {
            $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
        }
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f2f5;
}
.page-header-left h2 {
    font-size: 1.4rem;
    color: #1a1a2e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.page-header-left h2 i { color: #d4a847; }
.page-header-left .text-muted {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin: 0;
}
.page-header-right .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}
.btn-secondary {
    background: #95a5a6;
    color: #fff;
}
.btn-secondary:hover {
    background: #7f8c8d;
    color: #fff;
}

.form-wrapper {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.form-group {
    margin-bottom: 18px;
}
.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
    font-size: 0.9rem;
}
.form-label .required {
    color: #e74c3c;
    font-weight: 700;
    margin-left: 3px;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 0.9rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: #fff;
    color: #333;
}
.form-control:focus {
    border-color: #1a6e3a !important;
    outline: none;
    box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
}
textarea.form-control {
    resize: vertical;
    font-family: inherit;
}
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-text {
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
    display: block;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}
.btn-primary {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}
.btn-danger {
    background: #e74c3c;
    color: #fff;
}
.btn-danger:hover {
    background: #c0392b;
    color: #fff;
}
.btn-lg {
    padding: 12px 32px;
    font-size: 1rem;
    border-radius: 10px;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
    margin-top: 10px;
    flex-wrap: wrap;
}
.form-actions .btn {
    min-width: 140px;
    justify-content: center;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    .page-header-right .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- ============================================ -->
<!-- CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="fas fa-plus-circle"></i> Tambah Kegiatan</h2>
            <p class="text-muted">Buat kegiatan pelatihan, rapat, atau event lainnya</p>
        </div>
        <div class="page-header-right">
            <a href="kegiatan.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="form-wrapper">
        <form action="" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Judul Kegiatan <span class="required">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul kegiatan" required value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis Kegiatan</label>
                    <select name="jenis" class="form-control">
                        <option value="">Pilih Jenis</option>
                        <?php foreach ($jenis_list as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['jenis']) && $_POST['jenis'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi kegiatan"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                    <input type="datetime-local" name="tanggal_mulai" class="form-control" required value="<?php echo isset($_POST['tanggal_mulai']) ? htmlspecialchars($_POST['tanggal_mulai']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai <span class="required">*</span></label>
                    <input type="datetime-local" name="tanggal_selesai" class="form-control" required value="<?php echo isset($_POST['tanggal_selesai']) ? htmlspecialchars($_POST['tanggal_selesai']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Lokasi <span class="required">*</span></label>
                <input type="text" name="lokasi" class="form-control" placeholder="Nama lokasi/tempat" required value="<?php echo isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap lokasi"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="latitude" class="form-control" placeholder="Contoh: -5.397139" value="<?php echo isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : ''; ?>">
                    <small class="form-text">Kosongkan jika tidak menggunakan GPS</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="longitude" class="form-control" placeholder="Contoh: 105.266785" value="<?php echo isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : ''; ?>">
                    <small class="form-text">Kosongkan jika tidak menggunakan GPS</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Radius (meter) <span class="required">*</span></label>
                    <input type="number" name="radius" class="form-control" placeholder="100" min="10" max="500" required value="<?php echo isset($_POST['radius']) ? (int)$_POST['radius'] : 100; ?>">
                    <small class="form-text">Jarak maksimal dari lokasi untuk validasi absensi (10-500 meter)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Kuota Peserta</label>
                    <input type="number" name="kuota" class="form-control" placeholder="0 = tidak terbatas" min="0" value="<?php echo isset($_POST['kuota']) ? (int)$_POST['kuota'] : 0; ?>">
                    <small class="form-text">0 berarti tidak terbatas</small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="aktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Simpan</button>
                <button type="reset" class="btn btn-secondary btn-lg"><i class="fas fa-undo"></i> Reset</button>
                <a href="kegiatan.php" class="btn btn-danger btn-lg"><i class="fas fa-times"></i> Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>