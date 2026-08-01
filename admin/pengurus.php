<?php
// admin/pengurus.php - Halaman Manajemen Pengurus
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

$title = 'Manajemen Pengurus';
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// ============================================
// CEK DAN PERBAIKI TABEL PENGURUS - AMAN
// ============================================

// 1. Cek apakah tabel ada
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pengurus'");
$table_exists = mysqli_num_rows($check_table) > 0;

if (!$table_exists) {
    // Buat tabel dari awal
    $create_table = "CREATE TABLE `pengurus` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nama` VARCHAR(255) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `foto` VARCHAR(255) NULL,
        `bio` TEXT NULL,
        `email` VARCHAR(100) NULL,
        `no_telp` VARCHAR(20) NULL,
        `alamat` TEXT NULL,
        `kabupaten_id` INT(11) NULL,
        `kecamatan_id` INT(11) NULL,
        `desa_id` INT(11) NULL,
        `urutan` INT(11) DEFAULT 0,
        `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_jabatan` (`jabatan`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
} else {
    // 2. Cek kolom yang ada
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pengurus");
    $existing_columns = [];
    while ($col = mysqli_fetch_assoc($check_columns)) {
        $existing_columns[] = $col['Field'];
    }
    
    // 3. Tambahkan kolom yang hilang dengan aman (gunakan IF NOT EXISTS)
    $columns_to_add = [
        'desa_id' => "INT(11) NULL",
        'urutan' => "INT(11) DEFAULT 0",
        'updated_at' => "DATETIME NULL"
    ];
    
    foreach ($columns_to_add as $col_name => $col_definition) {
        if (!in_array($col_name, $existing_columns)) {
            // Gunakan cara yang lebih aman: cek dulu apakah kolom ada
            $check_col = mysqli_query($conn, "SELECT COUNT(*) as total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengurus' AND COLUMN_NAME = '$col_name'");
            $col_exists = false;
            if ($check_col) {
                $row = mysqli_fetch_assoc($check_col);
                $col_exists = ($row['total'] > 0);
            }
            
            if (!$col_exists) {
                $alter_query = "ALTER TABLE `pengurus` ADD COLUMN `$col_name` $col_definition";
                mysqli_query($conn, $alter_query);
            }
        }
    }
    
    // 4. Tambahkan index jika belum ada
    $check_index = mysqli_query($conn, "SHOW INDEX FROM pengurus WHERE Key_name = 'idx_status'");
    if (mysqli_num_rows($check_index) == 0) {
        mysqli_query($conn, "ALTER TABLE `pengurus` ADD INDEX `idx_status` (`status`)");
    }
    
    $check_index = mysqli_query($conn, "SHOW INDEX FROM pengurus WHERE Key_name = 'idx_jabatan'");
    if (mysqli_num_rows($check_index) == 0) {
        mysqli_query($conn, "ALTER TABLE `pengurus` ADD INDEX `idx_jabatan` (`jabatan`)");
    }
}

// ============================================
// CEK JIKA ADA DATA PENGURUS
// ============================================
$check_data = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengurus");
$total_pengurus = $check_data ? mysqli_fetch_assoc($check_data)['total'] : 0;

// ============================================
// AMBIL KOLOM YANG ADA UNTUK QUERY
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pengurus");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

$has_desa = in_array('desa_id', $existing_columns);
$has_kecamatan = in_array('kecamatan_id', $existing_columns);
$has_kabupaten = in_array('kabupaten_id', $existing_columns);
$has_urutan = in_array('urutan', $existing_columns);

// ============================================
// KONFIGURASI PAGINATION
// ============================================
$per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$jabatan_filter = isset($_GET['jabatan']) ? trim($_GET['jabatan']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// ============================================
// QUERY UNTUK MENGHITUNG TOTAL
// ============================================
$count_query = "SELECT COUNT(*) as total FROM pengurus WHERE 1=1";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $count_query .= " AND (nama LIKE '%$search_escaped%' OR jabatan LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')";
}
if (!empty($jabatan_filter)) {
    $jabatan_escaped = mysqli_real_escape_string($conn, $jabatan_filter);
    $count_query .= " AND jabatan = '$jabatan_escaped'";
}
if (!empty($status_filter)) {
    $status_escaped = mysqli_real_escape_string($conn, $status_filter);
    $count_query .= " AND status = '$status_escaped'";
}

$count_result = mysqli_query($conn, $count_query);
$total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_data / $per_page);

// ============================================
// QUERY AMBIL DATA PENGURUS - DENGAN CEK KOLOM
// ============================================
$query = "SELECT p.*";

// Hanya JOIN jika kolom ada
if ($has_kabupaten) {
    $query .= ", k.nama as kabupaten_nama";
}
if ($has_kecamatan) {
    $query .= ", kec.nama as kecamatan_nama";
}
if ($has_desa) {
    $query .= ", d.nama as desa_nama";
}

$query .= " FROM pengurus p";

if ($has_kabupaten) {
    $query .= " LEFT JOIN kabupaten k ON p.kabupaten_id = k.id";
}
if ($has_kecamatan) {
    $query .= " LEFT JOIN kecamatan kec ON p.kecamatan_id = kec.id";
}
if ($has_desa) {
    $query .= " LEFT JOIN desa d ON p.desa_id = d.id";
}

$query .= " WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (p.nama LIKE '%$search_escaped%' OR p.jabatan LIKE '%$search_escaped%' OR p.email LIKE '%$search_escaped%')";
}
if (!empty($jabatan_filter)) {
    $query .= " AND p.jabatan = '$jabatan_escaped'";
}
if (!empty($status_filter)) {
    $query .= " AND p.status = '$status_escaped'";
}

// Order by
if ($has_urutan) {
    $query .= " ORDER BY p.urutan ASC, p.jabatan ASC, p.nama ASC";
} else {
    $query .= " ORDER BY p.jabatan ASC, p.nama ASC";
}
$query .= " LIMIT $offset, $per_page";

$pengurus_list = mysqli_query($conn, $query);

// ============================================
// AMBIL DAFTAR JABATAN UNTUK FILTER
// ============================================
$jabatan_query = "SELECT DISTINCT jabatan FROM pengurus WHERE jabatan != '' AND jabatan IS NOT NULL ORDER BY jabatan";
$jabatan_list = mysqli_query($conn, $jabatan_query);

include $root_path . '/admin/include/admin_header.php';
?>

<!-- Premium, Elegant & Fully Responsive CSS -->
<style>
    /* Base Reset Optimization */
    * { box-sizing: border-box; }
    
    /* Container utama - batasi lebar maksimal */
    .main-content {
        max-width: 100%;
        overflow-x: hidden;
        padding: 0 15px;
    }

    /* Page Header Layout */
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
        margin: 0 0 3px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-header-left h2 i { color: #d4a847; }
    .page-header-left .text-muted { color: #7f8c8d; font-size: 0.9rem; margin: 0; }
    .page-header-right { display: flex; gap: 10px; flex-wrap: wrap; }
    
    /* Button Styles */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        white-space: nowrap;
    }
    .btn:hover { transform: translateY(-2px); }
    .btn:active { transform: translateY(0); }
    .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        box-shadow: 0 4px 12px rgba(26, 110, 58, 0.15);
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        box-shadow: 0 6px 20px rgba(26, 110, 58, 0.25);
        color: #fff;
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
        box-shadow: 0 4px 12px rgba(149, 165, 166, 0.1);
    }
    .btn-secondary:hover { background: #7f8c8d; color: #fff; }
    .btn-sm {
        padding: 6px 14px;
        font-size: 0.8rem;
    }
    
    /* Modern Form & Filter Optimization */
    .filter-section {
        background: #fff;
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        border: 1px solid #f0f2f5;
    }
    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .filter-form-group {
        flex-direction: column;
        display: flex;
        flex: 1 1 auto;
        min-width: 160px;
    }
    .filter-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .input-group { position: relative; display: flex; align-items: center; width: 100%; }
    .input-group .input-icon { position: absolute; left: 14px; color: #95a5a6; font-size: 0.95rem; z-index: 1; }
    .input-group .form-control { padding-left: 42px; }
    
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #eef2f5;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.25s ease;
        background: #fff;
        color: #2c3e50;
        min-width: 0;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2395a5a6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 14px;
        padding-right: 40px;
        cursor: pointer;
    }
    
    /* Notification Alerts */
    .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; position: relative; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-dismissible { padding-right: 45px; }
    .alert-close { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 1.3rem; cursor: pointer; color: inherit; opacity: 0.6; }
    .alert-close:hover { opacity: 1; }
    
    /* Stats Widgets Grid */
    .stats-container { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); 
        gap: 15px; 
        margin-bottom: 25px; 
    }
    .stat-box { background: #fff; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: all 0.25s ease; border: 1px solid #f0f2f5; }
    .stat-box:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .stat-box.primary { border-left: 4px solid #1a6e3a; }
    .stat-box.accent { border-left: 4px solid #d4a847; }
    .stat-val { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
    .stat-lbl { font-size: 0.8rem; color: #7f8c8d; margin-top: 2px; text-transform: capitalize; }

    /* Elegant Table Style - Dengan overflow yang terkontrol */
    .table-wrapper { 
        background: #fff; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
        border: 1px solid #f0f2f5; 
        margin-bottom: 25px;
        width: 100%;
    }
    .table-responsive { 
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
    .table { 
        width: 100%; 
        border-collapse: collapse;
        min-width: 700px; /* Minimal width untuk desktop */
    }
    .table thead tr { background: #fafbfc; border-bottom: 2px solid #edf2f7; }
    .table th { 
        padding: 15px 12px; 
        text-align: left; 
        font-weight: 600; 
        color: #495057; 
        font-size: 0.8rem; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        white-space: nowrap; 
    }
    .table td { 
        padding: 15px 12px; 
        border-bottom: 1px solid #f1f3f5; 
        vertical-align: middle; 
        font-size: 0.9rem; 
        color: #495057;
        word-break: break-word;
    }
    .table tbody tr { transition: background-color 0.2s ease; }
    .table tbody tr:hover { background: #fafbfc; }
    .table tbody tr:last-child td { border-bottom: none; }
    
    /* Table Profile Elements */
    .avatar-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #eef2f5; transition: transform 0.2s ease; }
    .avatar-img:hover { transform: scale(1.06); }
    .avatar-placeholder { width: 50px; height: 50px; background: linear-gradient(135deg, #1a6e3a, #2d8f52); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }
    .profile-name { font-weight: 600; color: #2c3e50; font-size: 0.95rem; }
    .profile-role { font-size: 0.85rem; color: #d4a847; font-weight: 500; margin-top: 1px; }
    .profile-meta { font-size: 0.75rem; color: #95a5a6; margin-top: 3px; display: flex; align-items: center; gap: 4px; }
    .contact-item { font-size: 0.85rem; color: #626d7a; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
    .contact-item:last-child { margin-bottom: 0; }
    .contact-item i { color: #d4a847; width: 14px; font-size: 0.8rem; flex-shrink: 0; }
    
    /* Badges Status */
    .badge-status { display: inline-flex; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-status.aktif { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .badge-status.nonaktif { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* Action Trigger Buttons */
    .action-buttons { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
    .btn-action { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; transition: all 0.2s ease; border: none; cursor: pointer; }
    .btn-action:hover { transform: translateY(-2px); }
    .btn-action-edit { background: #e8f0fe; color: #1a6e3a; }
    .btn-action-edit:hover { background: #1a6e3a; color: #fff; }
    .btn-action-delete { background: #fde8e8; color: #e74c3c; }
    .btn-action-delete:hover { background: #e74c3c; color: #fff; }

    /* Empty Box State */
    .empty-box { text-align: center; padding: 50px 20px; }
    .empty-box i { font-size: 3rem; color: #d4a847; margin-bottom: 12px; display: block; }
    .empty-box h3 { color: #2c3e50; margin: 0 0 4px 0; font-size: 1.15rem; }
    .empty-box p { color: #7f8c8d; margin: 0 0 16px 0; font-size: 0.9rem; }

    /* Pagination Area Layout */
    .pagination-wrapper { padding: 15px 20px; border-top: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .pagination-counter { font-size: 0.85rem; color: #7f8c8d; font-weight: 500; }
    .pagination-nav { display: flex; gap: 5px; flex-wrap: wrap; }
    .pagination-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 10px; background: #f1f3f5; border-radius: 6px; text-decoration: none; color: #2c3e50; font-size: 0.85rem; font-weight: 600; transition: all 0.2s ease; }
    .pagination-btn:hover { background: #e2e8f0; }
    .pagination-btn.active { background: #1a6e3a; color: #fff; }
    .pagination-spacer { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; color: #bdc3c7; }

    /* ============================================
       RESPONSIVE BREAKPOINTS
       ============================================ */
    
    /* Tablet & Smaller Desktop */
    @media (max-width: 1024px) {
        .filter-form-group {
            min-width: 140px;
        }
        .table th {
            padding: 12px 10px;
            font-size: 0.75rem;
        }
        .table td {
            padding: 12px 10px;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 991px) {
        .page-header { 
            flex-direction: column; 
            align-items: stretch; 
            gap: 15px; 
        }
        .page-header-right { 
            width: 100%; 
        }
        .page-header-right .btn { 
            width: 100%; 
            justify-content: center; 
        }
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-form-group {
            min-width: unset;
            width: 100%;
        }
        .filter-form > div:last-child {
            flex-direction: row;
            width: 100%;
        }
        .filter-form > div:last-child .btn {
            flex: 1;
        }
    }

    @media (max-width: 768px) {
        /* Filter Adaptation */
        .filter-section { padding: 15px; }
        .filter-form { flex-direction: column; align-items: stretch; gap: 12px; }
        .filter-form > div { width: 100% !important; min-width: unset !important; }
        .filter-form > div:last-child { 
            flex-direction: row; 
            flex-wrap: wrap;
            gap: 8px;
        }
        .filter-form > div:last-child .btn { 
            flex: 1;
            min-width: 100px;
        }

        /* Stats Micro Adaptation */
        .stats-container { 
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); 
            gap: 10px; 
        }
        .stat-box { padding: 12px 15px; }
        .stat-val { font-size: 1.2rem; }
        .stat-lbl { font-size: 0.7rem; }

        /* === KARTU VIEW MOBILE === */
        .table-wrapper { 
            background: transparent; 
            box-shadow: none; 
            border: none; 
            padding: 0;
        }
        .table thead { display: none; }
        .table, .table tbody, .table tr, .table td { 
            display: block; 
            width: 100%; 
        }
        .table { min-width: unset; }
        .table tr { 
            background: #fff; 
            border-radius: 14px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            padding: 16px 18px; 
            border: 1px solid #f0f2f5; 
            margin-bottom: 16px; 
        }
        .table td { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 8px 0; 
            border-bottom: 1px solid #f8f9fa; 
            font-size: 0.88rem; 
            gap: 10px;
            word-break: break-word;
        }
        .table td:first-child { padding-top: 0; }
        .table td:last-child { 
            border-bottom: none; 
            padding-bottom: 0; 
        }
        
        /* Mobile Field Label */
        .table td::before { 
            content: attr(data-label); 
            font-weight: 600; 
            color: #7f8c8d; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            flex-shrink: 0;
            min-width: 60px;
        }
        .table td > * { text-align: right; }

        /* Profile block - khusus nama */
        .table td[data-label="Nama & Jabatan"] { 
            flex-direction: column; 
            align-items: flex-start; 
        }
        .table td[data-label="Nama & Jabatan"]::before { 
            margin-bottom: 4px; 
        }
        .table td[data-label="Nama & Jabatan"] > * { 
            text-align: left; 
        }
        
        /* Kolom Foto di Mobile */
        .table td[data-label="Foto"] {
            justify-content: center;
            padding: 8px 0;
        }
        .table td[data-label="Foto"]::before {
            display: none;
        }
        .avatar-img, .avatar-placeholder {
            width: 60px;
            height: 60px;
        }

        /* Action buttons */
        .table td:last-child { 
            flex-direction: column; 
            align-items: stretch; 
            gap: 10px; 
            padding-top: 12px; 
            margin-top: 8px; 
            border-top: 1px dashed #eef2f5; 
        }
        .table td:last-child::before { 
            content: 'Aksi';
            align-self: flex-start;
        }
        .action-buttons { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 8px; 
            width: 100%; 
        }
        .btn-action { 
            width: 100%; 
            height: 38px; 
            border-radius: 8px; 
        }
        
        /* Pagination */
        .pagination-wrapper { 
            flex-direction: column; 
            align-items: center; 
            padding: 15px 10px; 
            gap: 10px;
        }
        .pagination-nav {
            justify-content: center;
        }
        .pagination-counter {
            text-align: center;
            font-size: 0.8rem;
        }
        .pagination-btn {
            min-width: 32px;
            height: 32px;
            font-size: 0.8rem;
            padding: 0 8px;
        }
    }

    /* Mobile Extra Small */
    @media (max-width: 480px) {
        .stats-container { 
            grid-template-columns: 1fr 1fr; 
            gap: 8px; 
        }
        .stat-box { padding: 10px 12px; }
        .stat-val { font-size: 1rem; }
        .stat-lbl { font-size: 0.65rem; }
        
        .table tr { padding: 12px 14px; }
        .table td { 
            font-size: 0.8rem; 
            padding: 6px 0;
        }
        .table td::before { 
            font-size: 0.65rem; 
            min-width: 50px;
        }
        
        .action-buttons { 
            grid-template-columns: 1fr 1fr; 
            gap: 6px; 
        }
        .btn-action { height: 36px; font-size: 0.75rem; }
        
        .page-header-left h2 { font-size: 1.1rem; }
        .page-header-left .text-muted { font-size: 0.8rem; }
        .btn { 
            padding: 8px 14px; 
            font-size: 0.8rem; 
        }
        .filter-section { padding: 12px; }
        .form-control { padding: 8px 12px; font-size: 0.85rem; }
        
        .pagination-wrapper { padding: 10px 8px; }
        .pagination-btn { 
            min-width: 28px; 
            height: 28px; 
            font-size: 0.75rem; 
            padding: 0 6px;
        }
    }
</style>

<!-- ============================================ -->
<!-- LAYOUT UTAMA VIEW -->
<!-- ============================================ -->
<div class="main-content">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="fas fa-users-cog"></i> Halaman Pengurus</h2>
            <p class="text-muted">Kelola data pengurus dan susunan struktur organisasi secara dinamis</p>
        </div>
        <div class="page-header-right">
            <a href="pengurus_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Pengurus
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Feedbacks -->
    <?php if ($success == 'tambah'): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> Anggota pengurus baru berhasil ditambahkan!
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php elseif ($success == 'edit'): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> Data profil pengurus berhasil diperbarui!
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php elseif ($success == 'hapus'): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> Data pengurus telah berhasil dihapus!
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php elseif ($error == 'notfound'): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-circle"></i> Berkas data pengurus tidak ditemukan!
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php elseif ($error == 'delete_failed'): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-circle"></i> Gagal mengeksekusi penghapusan data pengurus!
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Statistik Mini Grid Widget -->
    <div class="stats-container">
        <div class="stat-box primary">
            <div class="stat-val"><?php echo number_format($total_pengurus); ?></div>
            <div class="stat-lbl">Total Anggota</div>
        </div>
        <?php
        $stat_query = "SELECT jabatan, COUNT(*) as total FROM pengurus WHERE status = 'aktif' GROUP BY jabatan ORDER BY total DESC LIMIT 5";
        $stat_result = mysqli_query($conn, $stat_query);
        if ($stat_result):
            while ($stat = mysqli_fetch_assoc($stat_result)):
        ?>
            <div class="stat-box accent">
                <div class="stat-val"><?php echo number_format($stat['total']); ?></div>
                <div class="stat-lbl"><?php echo htmlspecialchars($stat['jabatan']); ?></div>
            </div>
        <?php 
            endwhile;
        endif; 
        ?>
    </div>

    <!-- Filter Pencarian Panel -->
    <div class="filter-section">
        <form action="" method="GET" class="filter-form">
            <div class="filter-form-group" style="flex: 2; min-width: 200px;">
                <label class="filter-label">Kata Kunci</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, jabatan, email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="filter-form-group" style="min-width: 160px;">
                <label class="filter-label">Filter Jabatan</label>
                <select name="jabatan" class="form-control">
                    <option value="">Semua Jabatan</option>
                    <?php 
                    if ($jabatan_list && mysqli_num_rows($jabatan_list) > 0):
                        while ($jab = mysqli_fetch_assoc($jabatan_list)): 
                    ?>
                        <option value="<?php echo htmlspecialchars($jab['jabatan']); ?>" 
                            <?php echo $jabatan_filter == $jab['jabatan'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($jab['jabatan']); ?>
                        </option>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </select>
            </div>
            
            <div class="filter-form-group" style="min-width: 140px;">
                <label class="filter-label">Filter Status</label>
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?php echo $status_filter == 'aktif' ? 'selected' : ''; ?>>✅ Aktif</option>
                    <option value="nonaktif" <?php echo $status_filter == 'nonaktif' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Saring
                </button>
                <?php if (!empty($search) || !empty($jabatan_filter) || !empty($status_filter)): ?>
                    <a href="pengurus.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Data Container Wrapper -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 70px; text-align: center;">Foto</th>
                        <th>Nama & Jabatan</th>
                        <th style="width: 200px;">Detail Kontak</th>
                        <th style="width: 110px;">Status</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pengurus_list && mysqli_num_rows($pengurus_list) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($pengurus = mysqli_fetch_assoc($pengurus_list)): ?>
                            <tr>
                                <td data-label="No" style="text-align: center; font-weight: 600; color: #7f8c8d;">
                                    <?php echo $no++; ?>
                                </td>
                                <td data-label="Foto" style="text-align: center;">
                                    <?php if (!empty($pengurus['foto'])): ?>
                                        <img src="../assets/images/pengurus/<?php echo htmlspecialchars($pengurus['foto']); ?>" 
                                             alt="<?php echo htmlspecialchars($pengurus['nama']); ?>"
                                             class="avatar-img"
                                             onerror="this.src='../assets/images/pengurus/default.jpg'">
                                    <?php else: ?>
                                        <div class="avatar-placeholder" style="margin: 0 auto;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Nama & Jabatan">
                                    <div class="profile-name">
                                        <?php echo htmlspecialchars($pengurus['nama']); ?>
                                    </div>
                                    <div class="profile-role">
                                        <?php echo htmlspecialchars($pengurus['jabatan']); ?>
                                    </div>
                                    <?php if (!empty($pengurus['kabupaten_nama'])): ?>
                                        <div class="profile-meta">
                                            <i class="fas fa-map-marker-alt" style="color: #d4a847;"></i> 
                                            <?php echo htmlspecialchars($pengurus['kabupaten_nama']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($pengurus['urutan']) && $pengurus['urutan'] > 0): ?>
                                        <div style="font-size: 0.72rem; color: #bdc3c7; margin-top: 2px;">
                                            Indeks Urut: <?php echo $pengurus['urutan']; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Detail Kontak">
                                    <?php if (!empty($pengurus['email'])): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-envelope"></i> 
                                            <span><?php echo htmlspecialchars($pengurus['email']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($pengurus['no_telp'])): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-phone"></i> 
                                            <span><?php echo htmlspecialchars($pengurus['no_telp']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($pengurus['email']) && empty($pengurus['no_telp'])): ?>
                                        <span style="color: #bdc3c7; font-size: 0.85rem;">Tidak ada kontak</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge-status <?php echo $pengurus['status']; ?>">
                                        <?php echo $pengurus['status'] == 'aktif' ? '✅ Aktif' : '❌ Nonaktif'; ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="action-buttons">
                                        <a href="pengurus_edit.php?id=<?php echo $pengurus['id']; ?>" 
                                           class="btn-action btn-action-edit" title="Ubah Data">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="pengurus_hapus.php?id=<?php echo $pengurus['id']; ?>" 
                                           class="btn-action btn-action-delete" title="Hapus Permanen"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data pengurus ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 0;">
                                <div class="empty-box">
                                    <i class="fas fa-users-slash"></i>
                                    <h3>Data Pengurus Kosong</h3>
                                    <p>Tidak ada record pengurus yang cocok dengan kriteria pencarian Anda.</p>
                                    <a href="pengurus_tambah.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Daftarkan Pengurus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginasi Sistem Navigasi -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <span class="pagination-counter">
                    Menampilkan <?php echo number_format(($page - 1) * $per_page + 1); ?> - 
                    <?php echo number_format(min($page * $per_page, $total_data)); ?> 
                    dari <?php echo number_format($total_data); ?> entri data
                </span>
                <div class="pagination-nav">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : '') . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '" class="pagination-btn">1</a>';
                        if ($start_page > 2) echo '<span class="pagination-spacer">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php 
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span class="pagination-spacer">...</span>';
                        echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : '') . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '" class="pagination-btn">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dim & hide alerts smoothly after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>