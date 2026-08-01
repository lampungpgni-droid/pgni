<?php
// admin/guru.php - Halaman Daftar Guru
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

// ============================================
// CEK ROLE - REDIRECT PETUGAS KECAMATAN
// ============================================
if ($_SESSION['role'] === 'petugas_kecamatan') {
    header('Location: guru_petugas.php');
    exit;
}

$title = 'Daftar Guru Ngaji';
$user_role = $_SESSION['role'] ?? 'admin';

// ============================================
// SUPER ADMIN: FULL ACCESS
// ============================================
$is_super_admin = ($user_role === 'super_admin');
$is_admin = ($user_role === 'admin');

// ============================================
// AMBIL AKSES WILAYAH UNTUK ADMIN
// ============================================
$user_wilayah = [
    'akses_semua' => 0,
    'kabupaten' => [],
    'kecamatan' => [],
    'desa' => []
];

// SUPER ADMIN: FULL ACCESS SEMUA WILAYAH
if ($is_super_admin) {
    $user_wilayah['akses_semua'] = 1;
} 
// ADMIN: Ambil akses dari database
elseif ($is_admin) {
    $user_id = $_SESSION['user_id'];
    $query_wilayah = "SELECT * FROM user_wilayah_akses WHERE user_id = $user_id";
    $result_wilayah = mysqli_query($conn, $query_wilayah);
    if ($result_wilayah) {
        while ($row = mysqli_fetch_assoc($result_wilayah)) {
            if ($row['akses_semua'] == 1) {
                $user_wilayah['akses_semua'] = 1;
            }
            if ($row['kabupaten_id']) {
                $user_wilayah['kabupaten'][] = $row['kabupaten_id'];
            }
            if ($row['kecamatan_id']) {
                $user_wilayah['kecamatan'][] = $row['kecamatan_id'];
            }
            if ($row['desa_id']) {
                $user_wilayah['desa'][] = $row['desa_id'];
            }
        }
    }
}

// ============================================
// BUILD FILTER WILAYAH
// ============================================
function buildGuruFilter($user_wilayah) {
    // Jika akses semua, tidak ada filter
    if ($user_wilayah['akses_semua'] == 1) {
        return '';
    }
    
    $conditions = [];
    
    if (!empty($user_wilayah['kecamatan'])) {
        $kec_ids = implode(',', array_map('intval', $user_wilayah['kecamatan']));
        $conditions[] = "g.kecamatan_id IN ($kec_ids)";
    }
    
    if (!empty($user_wilayah['kabupaten'])) {
        $kab_ids = implode(',', array_map('intval', $user_wilayah['kabupaten']));
        $conditions[] = "g.kabupaten_id IN ($kab_ids)";
    }
    
    if (!empty($user_wilayah['desa'])) {
        $desa_ids = implode(',', array_map('intval', $user_wilayah['desa']));
        $conditions[] = "g.desa_id IN ($desa_ids)";
    }
    
    if (!empty($conditions)) {
        return 'WHERE (' . implode(' OR ', $conditions) . ')';
    }
    
    return 'WHERE 1=0';
}

$filter_condition = buildGuruFilter($user_wilayah);

// ============================================
// AMBIL DATA GURU DENGAN FILTER
// ============================================
if (empty($filter_condition)) {
    $query = "SELECT g.*, 
                     k.nama as kecamatan_nama,
                     kab.nama as kabupaten_nama,
                     d.nama as desa_nama
              FROM guru_ngaji g 
              LEFT JOIN kecamatan k ON g.kecamatan_id = k.id 
              LEFT JOIN kabupaten kab ON g.kabupaten_id = kab.id
              LEFT JOIN desa d ON g.desa_id = d.id
              ORDER BY g.created_at DESC";
} else {
    // Hapus WHERE untuk digabung
    $filter_clean = str_replace('WHERE ', '', $filter_condition);
    $query = "SELECT g.*, 
                     k.nama as kecamatan_nama,
                     kab.nama as kabupaten_nama,
                     d.nama as desa_nama
              FROM guru_ngaji g 
              LEFT JOIN kecamatan k ON g.kecamatan_id = k.id 
              LEFT JOIN kabupaten kab ON g.kabupaten_id = kab.id
              LEFT JOIN desa d ON g.desa_id = d.id
              WHERE $filter_clean 
              ORDER BY g.created_at DESC";
}
$result = mysqli_query($conn, $query);

// ============================================
// STATISTIK DENGAN FILTER
// ============================================
// Total Guru
if (empty($filter_condition)) {
    $query_total = "SELECT COUNT(*) as total FROM guru_ngaji";
} else {
    $filter_clean = str_replace('WHERE ', '', $filter_condition);
    $query_total = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_clean";
}
$result_total = mysqli_query($conn, $query_total);
$total_guru = $result_total ? mysqli_fetch_assoc($result_total)['total'] : 0;

// Guru Pending
if (empty($filter_condition)) {
    $query_pending = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status_verifikasi = 'pending'";
} else {
    $filter_clean = str_replace('WHERE ', '', $filter_condition);
    $query_pending = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_clean AND g.status_verifikasi = 'pending'";
}
$result_pending = mysqli_query($conn, $query_pending);
$total_pending = $result_pending ? mysqli_fetch_assoc($result_pending)['total'] : 0;

// Guru Aktif
if (empty($filter_condition)) {
    $query_aktif = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status = 'aktif'";
} else {
    $filter_clean = str_replace('WHERE ', '', $filter_condition);
    $query_aktif = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_clean AND g.status = 'aktif'";
}
$result_aktif = mysqli_query($conn, $query_aktif);
$total_aktif = $result_aktif ? mysqli_fetch_assoc($result_aktif)['total'] : 0;

// Guru Ditolak
if (empty($filter_condition)) {
    $query_ditolak = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status_verifikasi = 'ditolak'";
} else {
    $filter_clean = str_replace('WHERE ', '', $filter_condition);
    $query_ditolak = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_clean AND g.status_verifikasi = 'ditolak'";
}
$result_ditolak = mysqli_query($conn, $query_ditolak);
$total_ditolak = $result_ditolak ? mysqli_fetch_assoc($result_ditolak)['total'] : 0;

include 'include/admin_header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- CSS Mobile Responsive & Elegant -->
<style>
    /* ============================================
       RESET & BASE
       ============================================ */
    * {
        box-sizing: border-box;
    }
    
    body {
        background: #f8fafc;
    }

    /* ============================================
       PAGE HEADER
       ============================================ */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eef2f5;
    }
    .page-header-left h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 5px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-header-left h2 i {
        color: #27ae60;
    }
    .page-header-left .text-muted {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
    }
    .page-header-right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* ============================================
       FILTER BADGE
       ============================================ */
    .filter-badge {
        display: inline-block;
        padding: 4px 12px;
        background: #e8f5e9;
        color: #1a6e3a;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 5px;
    }
    .filter-badge i {
        margin-right: 5px;
    }
    .filter-badge.super-admin {
        background: #fff3cd;
        color: #856404;
    }
    .filter-badge.super-admin i {
        color: #f39c12;
    }

    /* ============================================
       ALERT
       ============================================ */
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
        border: 1px solid transparent;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    .alert i {
        font-size: 1.2rem;
    }

    /* ============================================
       STATS ROW (Premium Gradient)
       ============================================ */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-box {
        background: #fff;
        padding: 20px;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        gap: 18px;
        border: 1px solid #f0f2f5;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .stat-box-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-box-info h3 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        line-height: 1.2;
    }
    .stat-box-info p {
        font-size: 0.8rem;
        color: #7f8c8d;
        margin: 4px 0 0 0;
        font-weight: 500;
    }
    .stat-note {
        font-size: 0.65rem;
        color: #999;
        display: block;
        margin-top: 2px;
    }

    /* ============================================
       TABLE WRAPPER
       ============================================ */
    .table-wrapper {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        overflow: hidden;
        border: 1px solid #f0f2f5;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* ============================================
       TABLE STYLE
       ============================================ */
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table thead {
        background: #fafbfc;
    }
    .table th {
        padding: 16px 20px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
        border-bottom: 2px solid #edf2f7;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #495057;
    }
    .table tbody tr {
        transition: background-color 0.2s ease;
    }
    .table tbody tr:hover {
        background: #fafbfc;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    .text-center {
        text-align: center;
    }

    /* ============================================
       STATUS BADGE
       ============================================ */
    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .status-badge.aktif {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-badge.nonaktif {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    .status-badge.disetujui {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-badge.ditolak {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* ============================================
       ACTION BUTTONS
       ============================================ */
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        font-family: inherit;
        border: none;
        white-space: nowrap;
    }
    .btn:hover {
        transform: translateY(-2px);
    }
    .btn:active {
        transform: translateY(0);
    }

    .btn-primary {
        background: #1e7e34;
        color: #fff;
        box-shadow: 0 4px 10px rgba(30, 126, 52, 0.15);
    }
    .btn-primary:hover {
        background: #155d24;
        box-shadow: 0 6px 15px rgba(30, 126, 52, 0.25);
    }
    .btn-warning {
        background: #f39c12;
        color: #fff;
        box-shadow: 0 4px 10px rgba(243, 156, 18, 0.15);
    }
    .btn-warning:hover {
        background: #d35400;
        box-shadow: 0 6px 15px rgba(243, 156, 18, 0.25);
    }
    .btn-info {
        background: #17a2b8;
        color: #fff;
        box-shadow: 0 4px 10px rgba(23, 162, 184, 0.15);
    }
    .btn-info:hover {
        background: #117a8b;
        box-shadow: 0 6px 15px rgba(23, 162, 184, 0.25);
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
        box-shadow: 0 4px 10px rgba(231, 76, 60, 0.15);
    }
    .btn-danger:hover {
        background: #c0392b;
        box-shadow: 0 6px 15px rgba(231, 76, 60, 0.25);
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
        box-shadow: 0 4px 10px rgba(149, 165, 166, 0.1);
    }
    .btn-secondary:hover {
        background: #7f8c8d;
    }
    .btn-sm {
        padding: 6px 14px;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    .btn-success {
        background: #28a745;
        color: #fff;
    }
    .btn-success:hover {
        background: #218838;
    }

    /* ============================================
       EMPTY STATE
       ============================================ */
    .empty-state {
        text-align: center;
        padding: 80px 40px;
        background: #fff;
        border-radius: 14px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #27ae60;
        margin-bottom: 20px;
    }
    .empty-state h3 {
        margin: 0 0 10px 0;
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .empty-state p {
        color: #7f8c8d;
        margin: 0 0 20px 0;
    }

    /* ============================================
       MOBILE RESPONSIVE - PREMIUM (CARD-VIEW)
       ============================================ */
    @media (max-width: 991px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        .page-header-right {
            display: flex;
            width: 100%;
        }
        .page-header-right .btn {
            flex: 1;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        /* Adjust Font Size */
        .page-header-left h2 {
            font-size: 1.2rem;
        }
        
        /* Stats Mobile Layout */
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .stat-box {
            padding: 15px;
            gap: 12px;
        }
        .stat-box-icon {
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
        }
        .stat-box-info h3 {
            font-size: 1.2rem;
        }
        .stat-box-info p {
            font-size: 0.75rem;
        }

        /* Transform Table to Cards */
        .table-wrapper {
            background: transparent;
            box-shadow: none;
            border: none;
        }
        .table thead {
            display: none;
        }
        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }
        .table tr {
            margin-bottom: 16px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            padding: 16px 20px;
            border: 1px solid #f0f2f5;
        }
        .table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.85rem;
            gap: 15px;
        }
        .table td:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .table td:first-child {
            padding-top: 0;
        }
        .table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table td > * {
            text-align: right;
            word-break: break-word;
        }

        /* Action Column Layout for Mobile */
        .table td:last-child {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px dashed #eef2f5;
        }
        .table td:last-child::before {
            content: 'Aksi Menu';
            align-self: center;
            margin-bottom: 2px;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            width: 100%;
        }
        .action-buttons .btn {
            width: 100%;
            padding: 8px 4px;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
        .action-buttons {
            grid-template-columns: 1fr 1fr;
        }
        .action-buttons .btn-danger {
            grid-column: span 2;
        }
    }

    /* ============================================
       SEARCH & FILTER
       ============================================ */
    .search-box {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .search-box input {
        flex: 1;
        min-width: 200px;
        padding: 10px 15px;
        border: 1px solid #e0e4e8;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    .search-box input:focus {
        outline: none;
        border-color: #27ae60;
        box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
    }
    .search-box .btn {
        padding: 10px 25px;
    }
</style>

<!-- ============================================
     PAGE CONTENT
     ============================================ -->
<div class="container-fluid" style="padding: 20px 30px;">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="fas fa-users"></i> Daftar Guru Ngaji</h2>
            <p class="text-muted">Kelola data guru ngaji yang terdaftar</p>
            <?php if ($is_super_admin): ?>
                <span class="filter-badge super-admin">
                    <i class="fas fa-crown"></i> Super Admin - Akses Penuh Seluruh Wilayah
                </span>
            <?php elseif ($is_admin && !$user_wilayah['akses_semua']): ?>
                <span class="filter-badge">
                    <i class="fas fa-filter"></i> Filter wilayah akses
                </span>
            <?php elseif ($is_admin && $user_wilayah['akses_semua']): ?>
                <span class="filter-badge">
                    <i class="fas fa-globe"></i> Akses Semua Wilayah
                </span>
            <?php endif; ?>
        </div>
        <div class="page-header-right">
            <!-- Tombol Tambah Guru - Tampil untuk semua role -->
            <a href="guru_tambah.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Tambah Guru
            </a>
            <!-- Tombol Verifikasi - Tampil untuk admin dan super_admin -->
            <?php if ($is_admin || $is_super_admin): ?>
                <a href="guru_verifikasi.php" class="btn btn-warning">
                    <i class="fas fa-check-double"></i> Verifikasi
                    <?php if ($total_pending > 0): ?>
                        <span class="badge" style="background: #fff; color: #f39c12; border-radius: 50%; padding: 2px 8px; margin-left: 5px; font-size: 0.8rem;">
                            <?php echo $total_pending; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-box-icon" style="background: linear-gradient(135deg, #1e7e34, #2d8f52);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-box-info">
                <h3><?php echo $total_guru; ?></h3>
                <p>Total Guru</p>
                <?php if ($is_admin && !$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-box-info">
                <h3><?php echo $total_aktif; ?></h3>
                <p>Guru Aktif</p>
                <?php if ($is_admin && !$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-box-info">
                <h3><?php echo $total_pending; ?></h3>
                <p>Menunggu Verifikasi</p>
                <?php if ($is_admin && !$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-box-info">
                <h3><?php echo $total_ditolak; ?></h3>
                <p>Ditolak</p>
                <?php if ($is_admin && !$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            <?php 
                switch($_GET['msg']) {
                    case 'tambah': echo 'Data guru berhasil ditambahkan!'; break;
                    case 'edit': echo 'Data guru berhasil diperbarui!'; break;
                    case 'hapus': echo 'Data guru berhasil dihapus!'; break;
                    case 'verifikasi': echo 'Status verifikasi berhasil diperbarui!'; break;
                    default: echo 'Operasi berhasil!';
                }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> 
            <?php 
                switch($_GET['error']) {
                    case 'notfound': echo 'Data guru tidak ditemukan!'; break;
                    case 'verifikasi': echo 'Gagal memperbarui status verifikasi!'; break;
                    case 'akses': echo 'Anda tidak memiliki akses ke data ini!'; break;
                    default: echo 'Terjadi kesalahan!';
                }
            ?>
        </div>
    <?php endif; ?>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Cari guru berdasarkan nama, NIK, atau tempat mengajar..." onkeyup="filterTable()">
        <button class="btn btn-secondary" onclick="resetSearch()">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table" id="guruTable">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Tempat Mengajar</th>
                        <th>Wilayah</th>
                        <th>Status</th>
                        <th>Verifikasi</th>
                        <th style="text-align: center; width: 240px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php 
                        mysqli_data_seek($result, 0);
                        $no = 1; 
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td data-label="No" style="font-weight: 600; color: #7f8c8d;"><?php echo $no++; ?></td>
                                <td data-label="NIK">
                                    <span style="background: #f1f3f5; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-weight: 600;">
                                        <?php echo htmlspecialchars($row['nik']); ?>
                                    </span>
                                </td>
                                <td data-label="Nama">
                                    <strong style="color: #2c3e50;"><?php echo htmlspecialchars($row['nama']); ?></strong>
                                </td>
                                <td data-label="Tempat Mengajar">
                                    <?php echo htmlspecialchars($row['tempat_mengajar']); ?>
                                </td>
                                <td data-label="Wilayah">
                                    <div style="font-size: 0.85rem;">
                                        <?php if ($row['kabupaten_nama']): ?>
                                            <div><i class="fas fa-city" style="color: #95a5a6; width: 16px;"></i> <?php echo htmlspecialchars($row['kabupaten_nama']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['kecamatan_nama']): ?>
                                            <div><i class="fas fa-map-marker-alt" style="color: #95a5a6; width: 16px;"></i> <?php echo htmlspecialchars($row['kecamatan_nama']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['desa_nama']): ?>
                                            <div><i class="fas fa-location-dot" style="color: #95a5a6; width: 16px;"></i> <?php echo htmlspecialchars($row['desa_nama']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge <?php echo $row['status']; ?>">
                                        <?php 
                                            switch($row['status']) {
                                                case 'aktif': echo '✅ Aktif'; break;
                                                case 'nonaktif': echo '❌ Nonaktif'; break;
                                                default: echo ucfirst($row['status']);
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td data-label="Verifikasi">
                                    <span class="status-badge <?php echo $row['status_verifikasi']; ?>">
                                        <?php 
                                            switch($row['status_verifikasi']) {
                                                case 'pending': echo '⏳ Pending'; break;
                                                case 'disetujui': echo '✅ Disetujui'; break;
                                                case 'ditolak': echo '❌ Ditolak'; break;
                                                default: echo $row['status_verifikasi'];
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="action-buttons">
                                        <a href="guru_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($is_admin || $is_super_admin): ?>
                                            <a href="guru_verifikasi.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Verifikasi">
                                                <i class="fas fa-check-double"></i> Verif
                                            </a>
                                        <?php endif; ?>
                                        <a href="guru_hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 40px 20px;">
                                <div class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <h3>Belum Ada Data Guru</h3>
                                    <p>Silakan tambahkan guru ngaji baru</p>
                                    <a href="guru_tambah.php" class="btn btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-user-plus"></i> Tambah Guru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Info -->
    <div style="margin-top: 20px; text-align: center; color: #95a5a6; font-size: 0.85rem; border-top: 1px solid #eef2f5; padding-top: 20px;">
        <p>
            <i class="fas fa-database"></i> Total: <?php echo $total_guru; ?> data guru 
            <?php if ($is_super_admin): ?>
                <span style="margin-left: 10px;">👑 <strong>Super Admin</strong> - Akses Penuh</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT - SEARCH FILTER
     ============================================ -->
<script>
    function filterTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toLowerCase();
        var table = document.getElementById("guruTable");
        var rows = table.getElementsByTagName("tr");

        for (var i = 1; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName("td");
            var found = false;
            
            for (var j = 0; j < cells.length - 1; j++) {
                var cellText = cells[j].textContent || cells[j].innerText;
                if (cellText.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            
            if (found) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }

    function resetSearch() {
        document.getElementById("searchInput").value = "";
        filterTable();
    }

    // Auto filter on page load if search has value
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('searchInput');
        if (searchInput && searchInput.value) {
            filterTable();
        }
    });
</script>

<?php include 'include/admin_footer.php'; ?>

</body>
</html>