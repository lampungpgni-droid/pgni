<?php
// admin/include/admin_header.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path absolut dari root project
$root_path = dirname(__DIR__, 2); // Naik 2 level dari admin/include ke root

require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$admin_base = dirname($_SERVER['SCRIPT_NAME']);
$admin_base = rtrim($admin_base, '/') . '/';

$user_id = $_SESSION['user_id'];
$query_user = "SELECT * FROM users WHERE id = $user_id";
$result_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($result_user);

if (!$user_data) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Ambil role user
$user_role = $_SESSION['role'] ?? 'admin';
$user_kecamatan_id = $_SESSION['kecamatan_id'] ?? 0;

// Tentukan class body berdasarkan role
$body_class = 'role-' . $user_role;

// ============================================
// DETEKSI HALAMAN AKTIF
// ============================================
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($pages) {
    global $current_page;
    if (!is_array($pages)) {
        $pages = [$pages];
    }
    return in_array($current_page, $pages) ? 'active' : '';
}

// ============================================
// PERBAIKAN: TENTUKAN DASHBOARD SECARA DINAMIS
// ============================================
$dashboard_link = 'dashboard.php';
if ($user_role === 'admin') {
    $dashboard_link = 'dashboard_admin.php';
} elseif ($user_role === 'petugas_kecamatan') {
    $dashboard_link = 'dashboard_petugas.php';
}

?>
<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?>Admin PGNI Lampung</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a6e3a;
            --primary-dark: #0e4a26;
            --primary-light: #2d8f52;
            --gold: #d4a847;
            --gold-light: #f0dba8;
            --dark: #1a1a2e;
            --sidebar-width: 260px;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            direction: ltr;
            display: flex;
            min-height: 100vh;
        }
        
        /* ============================================
           SIDEBAR
        ============================================ */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--dark);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .admin-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
        }
        
        /* ============================================
           SIDEBAR HEADER
        ============================================ */
        .admin-sidebar .sidebar-header {
            padding: 18px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            text-align: center;
            flex-shrink: 0;
        }
        
        .admin-sidebar .sidebar-header .logo-text {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
        }
        .admin-sidebar .sidebar-header .logo-text span { 
            color: var(--gold); 
        }
        .admin-sidebar .sidebar-header .subtitle {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.4);
            display: block;
            margin-top: 2px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        
        /* ============================================
           SIDEBAR MENU WRAPPER
        ============================================ */
        .sidebar-menu-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 10px;
        }
        
        .admin-sidebar .sidebar-menu {
            padding: 8px 0;
            list-style: none;
            margin: 0;
        }
        
        .admin-sidebar .sidebar-menu .menu-label {
            padding: 10px 20px 4px;
            font-size: 0.6rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .admin-sidebar .sidebar-menu li {
            display: block;
        }
        
        .admin-sidebar .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-size: 0.85rem;
            position: relative;
        }
        
        .admin-sidebar .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.8);
        }
        
        .admin-sidebar .sidebar-menu li.active a {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left-color: var(--gold);
        }
        
        .admin-sidebar .sidebar-menu li a i {
            width: 22px;
            text-align: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            opacity: 0.7;
        }
        
        .admin-sidebar .sidebar-menu li.active a i,
        .admin-sidebar .sidebar-menu li a:hover i {
            opacity: 1;
        }
        
        /* Menu Badge */
        .menu-badge {
            margin-left: auto;
            background: var(--gold);
            color: var(--dark);
            font-size: 0.55rem;
            padding: 1px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* ============================================
           PERBAIKAN: MENU VISIBILITY BERDASARKAN ROLE
        ============================================ */
        /* Sembunyikan semua menu khusus secara default */
        .menu-super-admin,
        .menu-admin-only,
        .menu-petugas-only {
            display: none !important;
        }
        
        /* Tampilkan menu khusus sesuai role */
        .role-super_admin .menu-super-admin,
        .role-super_admin .menu-admin-only {
            display: block !important;
        }
        
        /* PERBAIKAN: Berikan akses menu konten (Berita) untuk Role Admin biasa */
        .role-admin .menu-super-admin {
            display: block !important;
        }
        .role-admin .menu-admin-guru {
            display: block !important;
        }
        .role-admin .menu-admin-laporan {
            display: block !important;
        }
        
        .role-petugas_kecamatan .menu-petugas-only {
            display: block !important;
        }
        
        /* Menu yang tampil untuk semua role */
        .menu-all {
            display: block !important;
        }
        
        /* ============================================
           SIDEBAR FOOTER
        ============================================ */
        .sidebar-footer {
            padding: 12px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.2);
            flex-shrink: 0;
            background: var(--dark);
        }
        .sidebar-footer .arabic {
            font-family: 'Amiri', serif;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.25);
            display: block;
            margin-bottom: 3px;
        }
        
        /* ============================================
           MAIN CONTENT
        ============================================ */
        .admin-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* ============================================
           TOP BAR
        ============================================ */
        .admin-topbar {
            height: var(--header-height);
            background: #fff;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
            flex-shrink: 0;
        }
        
        .admin-topbar .page-title h1 {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        .admin-topbar .page-title small {
            font-size: 0.75rem;
            color: #999;
            font-weight: 400;
        }
        
        .admin-topbar .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-topbar .topbar-right .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-topbar .topbar-right .user-info .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        
        .admin-topbar .topbar-right .user-info .user-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--dark);
        }
        .admin-topbar .topbar-right .user-info .user-role {
            font-size: 0.65rem;
            color: #999;
            display: block;
            margin-top: -2px;
        }
        
        .admin-topbar .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--dark);
            cursor: pointer;
            padding: 5px;
        }
        
        /* ============================================
           CONTENT
        ============================================ */
        .admin-content {
            padding: 25px 30px;
            flex: 1;
        }
        
        /* ============================================
           MOBILE OVERLAY
        ============================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.active {
            display: block;
        }
        
        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 992px) {
            .admin-content {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 2px 0 30px rgba(0,0,0,0.15);
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .admin-topbar .mobile-toggle {
                display: block;
            }
            .admin-topbar {
                padding: 0 15px;
                height: 60px;
            }
            .admin-topbar .page-title h1 {
                font-size: 0.9rem;
            }
            .admin-topbar .page-title small {
                display: none;
            }
            .admin-content {
                padding: 15px;
            }
            .admin-topbar .topbar-right .user-info .user-details {
                display: none;
            }
            .admin-topbar .topbar-right .user-info .avatar {
                width: 32px;
                height: 32px;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            .admin-sidebar {
                width: 100%;
                max-width: 300px;
            }
            .admin-content {
                padding: 10px;
            }
            .admin-topbar {
                padding: 0 10px;
                height: 55px;
            }
            .admin-topbar .page-title h1 {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="logo-text">PGNI <span>Lampung</span></div>
        <span class="subtitle">Panel Admin</span>
    </div>
    
    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-menu">
            <!-- ============================================
                 MENU UTAMA
            ============================================ -->
            <li class="menu-label">Menu Utama</li>
            
            <!-- PERBAIKAN: Dashboard Tunggal Bersifat Dinamis Mengikuti URL Role -->
            <li class="menu-all <?php echo isActive(['dashboard.php', 'dashboard_admin.php', 'dashboard_petugas.php']); ?>">
                <a href="<?php echo $dashboard_link; ?>"><i class="fas fa-home"></i> Dashboard</a>
            </li>
            
            <!-- ============================================
                 MANAJEMEN - SEMUA ROLE
            ============================================ -->
            <li class="menu-label">Manajemen</li>
            
            <!-- Guru Ngaji - Semua role -->
            <li class="menu-all <?php echo isActive(['guru.php', 'guru_tambah.php', 'guru_edit.php', 'guru_verifikasi.php', 'guru_petugas.php']); ?>">
                <a href="guru.php"><i class="fas fa-users"></i> Guru Ngaji</a>
            </li>
            
            <!-- Tambah Guru - Semua role -->
            <li class="menu-all <?php echo isActive('guru_tambah.php'); ?>">
                <a href="guru_tambah.php"><i class="fas fa-user-plus"></i> Tambah Guru</a>
            </li>
            
            <!-- ============================================
                 KONTEN - SUPER ADMIN & ADMIN BISA AKSES
            ============================================ -->
            <li class="menu-label menu-super-admin">Konten</li>
            
            <!-- Berita - Bisa Diakses Super Admin & Admin -->
            <li class="menu-super-admin <?php echo isActive(['berita.php', 'berita_tambah.php', 'berita_edit.php']); ?>">
                <a href="berita.php"><i class="fas fa-newspaper"></i> Berita</a>
            </li>
            
            <!-- Pengurus - Bisa Diakses Super Admin & Admin -->
            <li class="menu-super-admin <?php echo isActive(['pengurus.php', 'pengurus_tambah.php', 'pengurus_edit.php']); ?>">
                <a href="pengurus.php"><i class="fas fa-user-tie"></i> Pengurus</a>
            </li>
            
            <!-- Yayasan - Bisa Diakses Super Admin & Admin -->
            <li class="menu-super-admin <?php echo isActive('yayasan.php'); ?>">
                <a href="yayasan.php"><i class="fas fa-building"></i> Yayasan</a>
            </li>
            
            <!-- ============================================
                 SISTEM - HANYA KELUAR JIKA ROLE SUPER ADMIN
            ============================================ -->
            <li class="menu-label menu-admin-only">Sistem</li>
            
            <!-- User Management -->
            <li class="menu-admin-only <?php echo isActive(['user.php', 'user_tambah.php', 'user_edit.php']); ?>">
                <a href="user.php"><i class="fas fa-user-shield"></i> User</a>
            </li>
            
            <!-- Hak Akses -->
            <li class="menu-admin-only <?php echo isActive('permission.php'); ?>">
                <a href="permission.php"><i class="fas fa-key"></i> Hak Akses</a>
            </li>
            
            <!-- Wilayah -->
            <li class="menu-admin-only <?php echo isActive('wilayah.php'); ?>">
                <a href="wilayah.php"><i class="fas fa-map-marker-alt"></i> Wilayah</a>
            </li>
            
            <!-- ============================================
                 LAPORAN - HANYA KELUAR JIKA ROLE SUPER ADMIN
            ============================================ -->
            <li class="menu-label menu-admin-only">Laporan</li>
            
            <!-- Cetak Data -->
            <li class="menu-admin-only <?php echo isActive('cetak_data.php'); ?>">
                <a href="cetak_data.php"><i class="fas fa-print"></i> Cetak Data</a>
            </li>
            
            <!-- ============================================
                 LAINNYA - SEMUA ROLE
            ============================================ -->
            <li class="menu-label">Lainnya</li>
            
            <!-- Lihat Website -->
            <li class="menu-all">
                <a href="../" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            </li>
            
            <!-- Logout -->
            <li class="menu-all">
                <a href="logout.php" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <span class="arabic">اللهم صل على محمد</span>
        &copy; <?php echo date('Y'); ?> PGNI Lampung
    </div>
</aside>

<!-- Main Content -->
<div class="admin-main">
    <!-- Top Bar -->
    <header class="admin-topbar">
        <div class="page-title">
            <h1><?php echo isset($title) ? $title : 'Dashboard'; ?> <small>PGNI Lampung</small></h1>
        </div>
        <div class="topbar-right">
            <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 2)); ?></div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin'); ?></span>
                    <span class="user-role"><?php 
                        $role_display = [
                            'super_admin' => '👑 Super Admin',
                            'admin' => '⚙️ Admin',
                            'petugas_kecamatan' => '📋 Petugas Kecamatan'
                        ];
                        echo $role_display[$_SESSION['role'] ?? 'admin'] ?? ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'admin'));
                    ?></span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Content -->
    <main class="admin-content">

    <!-- PERBAIKAN: Penanganan Aksi Klik Toggle Menu Sidebar untuk Mobile -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.getElementById('mobileToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileToggle && adminSidebar && sidebarOverlay) {
            function toggleSidebar() {
                adminSidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('active');
            }

            mobileToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
    });
    </script>