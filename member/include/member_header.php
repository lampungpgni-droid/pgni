<?php
// member/include/member_header.php - Perbaikan menu member dengan Foto Profil
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PERBAIKAN PATH
// ============================================
$root_path = dirname(__DIR__, 2);

require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$member_id = $_SESSION['member_id'];
$member_nama = $_SESSION['member_nama'] ?? 'Member';
$member_nik = $_SESSION['member_nik'] ?? '';

// Query data lengkap guru termasuk foto_profil
$query = "SELECT * FROM guru_ngaji WHERE id = $member_id";
$result = mysqli_query($conn, $query);
$member_data = mysqli_fetch_assoc($result);

// Cek status verifikasi
$status_verifikasi = $member_data['status_verifikasi'] ?? 'pending';
$status_badge_class = 'pending';
$status_badge_text = 'Menunggu Verifikasi';
if ($status_verifikasi === 'disetujui') {
    $status_badge_class = 'disetujui';
    $status_badge_text = '✅ Terverifikasi';
} elseif ($status_verifikasi === 'ditolak') {
    $status_badge_class = 'ditolak';
    $status_badge_text = '❌ Ditolak';
}

// ============================================
// LOGIKA FOTO PROFIL (SIDEBAR & TOPBAR)
// ============================================
$foto_url = 'https://ui-avatars.com/api/?name=' . urlencode($member_nama) . '&background=1a6e3a&color=fff&size=200';
if (!empty($member_data['foto_profil'])) {
    // Path server check
    $foto_path_check = $root_path . '/uploads/foto/' . $member_data['foto_profil'];
    if (file_exists($foto_path_check)) {
        // Arahkan ke folder uploads/foto relatif dari root aplikasi
        $foto_url = '../uploads/foto/' . $member_data['foto_profil'];
    }
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$base_url = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($base_url, '/') . '/';

$title = isset($title) ? $title . ' - ' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $title; ?>Member Area - PGNI Lampung</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================
           ROOT VARIABLES
        ============================================ */
        :root {
            --primary: #1a6e3a;
            --primary-dark: #0e4a26;
            --primary-light: #2d8f52;
            --gold: #d4a847;
            --gold-light: #f0dba8;
            --dark: #1a1a2e;
            --gray: #7f8c8d;
            --light-gray: #f8f9fa;
            --sidebar-width: 260px;
            --header-height: 65px;
            --shadow: 0 2px 15px rgba(0,0,0,0.05);
            --radius: 14px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ============================================
           RESET & BASE
        ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
            color: var(--dark);
        }

        /* ============================================
           SIDEBAR MEMBER
        ============================================ */
        .member-sidebar {
            width: var(--sidebar-width);
            background: var(--dark);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            transform: translateX(0);
        }

        .member-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .member-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .member-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 20px 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-header .logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sidebar-header .logo span {
            color: var(--gold);
        }

        .sidebar-header .logo .icon {
            color: var(--gold);
            font-size: 1.5rem;
        }

        .sidebar-header .subtitle {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.4);
            display: block;
            margin-top: 2px;
            letter-spacing: 1px;
        }

        /* Sidebar User Profile */
        .sidebar-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-profile .avatar-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            position: relative;
        }

        .sidebar-profile .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.15);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            background: var(--primary);
        }

        .sidebar-profile .name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #fff;
            margin-top: 5px;
        }

        .sidebar-profile .nik {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            font-family: monospace;
            letter-spacing: 1px;
        }

        .sidebar-profile .status-badge {
            display: inline-block;
            padding: 2px 14px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-top: 6px;
        }

        .sidebar-profile .status-badge.disetujui {
            background: #d4edda;
            color: #155724;
        }

        .sidebar-profile .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .sidebar-profile .status-badge.ditolak {
            background: #f8d7da;
            color: #721c24;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 10px 0;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-menu .menu-label {
            padding: 10px 20px 5px;
            font-size: 0.65rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-size: 0.85rem;
            position: relative;
        }

        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.04);
            color: #fff;
            border-left-color: var(--gold);
        }

        .sidebar-menu li.active a {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border-left-color: var(--gold);
        }

        .sidebar-menu li a .icon {
            width: 20px;
            text-align: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .sidebar-menu li a .badge-menu {
            margin-left: auto;
            background: var(--gold);
            color: #fff;
            font-size: 0.6rem;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.2);
            flex-shrink: 0;
        }

        .sidebar-footer .arabic {
            font-family: 'Amiri', serif;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.3);
            display: block;
            margin-bottom: 3px;
        }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .member-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ============================================
           TOPBAR
        ============================================ */
        .member-topbar {
            height: var(--header-height);
            background: #fff;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 1px solid #f0f2f5;
        }

        .member-topbar .left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .member-topbar .left .toggle-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--dark);
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .member-topbar .left .toggle-btn:hover {
            background: var(--light-gray);
        }

        .member-topbar .left .toggle-btn:active {
            transform: scale(0.95);
        }

        .member-topbar .left .page-title h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .member-topbar .left .page-title small {
            font-size: 0.75rem;
            color: var(--gray);
            font-weight: 400;
        }

        .member-topbar .right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Topbar User */
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 12px 5px 5px;
            border-radius: 30px;
            transition: var(--transition);
            border: 1px solid transparent;
            background: none;
            font-family: 'Poppins', sans-serif;
        }

        .topbar-user:hover {
            background: var(--light-gray);
            border-color: #e8e8e8;
        }

        .topbar-user .topbar-avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid #e2e8f0;
            background: var(--primary);
        }

        .topbar-user .info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }

        .topbar-user .info .name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--dark);
        }

        .topbar-user .info .role {
            font-size: 0.65rem;
            color: var(--gray);
        }

        .topbar-user .chevron {
            font-size: 0.7rem;
            color: var(--gray);
            transition: var(--transition);
        }

        /* Dropdown */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            min-width: 200px;
            padding: 8px 0;
            border: 1px solid #f0f2f5;
            animation: dropdownIn 0.2s ease;
            z-index: 100;
        }

        .dropdown-menu.show {
            display: block;
        }

        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: var(--dark);
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .dropdown-menu a:hover {
            background: var(--light-gray);
        }

        .dropdown-menu a .icon {
            width: 18px;
            text-align: center;
            color: var(--gray);
        }

        .dropdown-menu .divider {
            height: 1px;
            background: #f0f2f5;
            margin: 6px 0;
        }

        .dropdown-menu .logout-btn {
            color: #e74c3c;
        }

        .dropdown-menu .logout-btn .icon {
            color: #e74c3c;
        }

        /* ============================================
           CONTENT
        ============================================ */
        .member-content {
            padding: 25px 30px;
            flex: 1;
        }

        /* ============================================
           OVERLAY
        ============================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* ============================================
           RESPONSIVE - MOBILE
        ============================================ */
        @media (max-width: 768px) {
            .member-sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 0 0 30px rgba(0,0,0,0.2);
            }

            .member-sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
                opacity: 1;
            }

            .member-main {
                margin-left: 0;
            }

            .member-topbar .left .toggle-btn {
                display: block;
            }

            .member-topbar {
                padding: 0 15px;
            }

            .member-topbar .left .page-title h1 {
                font-size: 0.95rem;
            }

            .member-topbar .left .page-title small {
                display: none;
            }

            .member-content {
                padding: 15px;
            }

            .topbar-user .info {
                display: none;
            }

            .topbar-user {
                padding: 5px;
            }
        }

        @media (max-width: 480px) {
            .member-topbar .right {
                gap: 8px;
            }

            .member-topbar .left .page-title h1 {
                font-size: 0.85rem;
            }

            .dropdown-menu {
                right: -10px;
                min-width: 180px;
            }

            .member-sidebar {
                width: 260px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="member-sidebar" id="memberSidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="icon">🕌</span>
            PGNI <span>Lampung</span>
        </div>
        <span class="subtitle">Member Area</span>
    </div>

    <!-- Poto Profil Sidebar -->
    <div class="sidebar-profile">
        <div class="avatar-wrapper">
            <img src="<?php echo $foto_url; ?>" 
                 alt="Avatar" 
                 class="avatar-img"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($member_nama); ?>&background=1a6e3a&color=fff&size=200'">
        </div>
        <div class="name"><?php echo htmlspecialchars($member_nama); ?></div>
        <div class="nik">NIK: <?php echo htmlspecialchars($member_nik); ?></div>
        <span class="status-badge <?php echo $status_badge_class; ?>">
            <?php echo $status_badge_text; ?>
        </span>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-label">Menu Utama</li>
        
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <span class="icon">🏠</span> Dashboard
            </a>
        </li>

        <li class="menu-label">Data Diri</li>
        
        <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <a href="profile.php">
                <span class="icon">👤</span> Profil Saya
            </a>
        </li>
        
        <li class="<?php echo $current_page == 'guru_edit.php' ? 'active' : ''; ?>">
            <a href="guru_edit.php?id=<?php echo $member_id; ?>">
                <span class="icon">✏️</span> Edit Data
            </a>
        </li>
		
		<li class="menu-label">Kartu Anggota</li>

		<li class="<?php echo $current_page == 'cetak_kta.php' ? 'active' : ''; ?>">
			<a href="cetak_kta.php">
			<span class="icon">🪪</span> KTA
			</a>
		</li>
		
		<li class="<?php echo $current_page == 'cetak_sertifikat.php' ? 'active' : ''; ?>">
			<a href="cetak_sertifikat.php">
			<span class="icon">🪪</span> Sertifikat
			</a>
		</li>
        
        

        <li class="menu-label">Informasi</li>
        
        <li>
            <a href="../cek_status.php?nik=<?php echo $member_nik; ?>" target="_blank">
                <span class="icon">🔍</span> Cek Status
                <span class="badge-menu">Baru</span>
            </a>
        </li>
        
        <li>
            <a href="../berita.php" target="_blank">
                <span class="icon">📰</span> Berita
            </a>
        </li>

        <li class="menu-label">Lainnya</li>
        
        <li>
            <a href="../" target="_blank">
                <span class="icon">🌐</span> Website PGNI
            </a>
        </li>
        
        <li>
            <a href="logout.php" onclick="return confirm('Yakin ingin logout?')" style="color: #e74c3c;">
                <span class="icon">🚪</span> Logout
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <span class="arabic">اللهم صل على محمد</span>
        &copy; <?php echo date('Y'); ?> PGNI Lampung
    </div>
</aside>

<!-- Main Content -->
<div class="member-main">
    <!-- Topbar -->
    <header class="member-topbar">
        <div class="left">
            <button class="toggle-btn" id="toggleSidebar" aria-label="Toggle sidebar">
                <span class="icon">☰</span>
            </button>
            <div class="page-title">
                <h1>
                    <?php echo isset($title) ? $title : 'Dashboard'; ?>
                    <small>Member Area</small>
                </h1>
            </div>
        </div>
        <div class="right">
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="topbar-user" onclick="toggleDropdown(event)">
                    <!-- Poto Profil Topbar -->
                    <img src="<?php echo $foto_url; ?>" 
                         alt="Avatar Topbar" 
                         class="topbar-avatar-img"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($member_nama); ?>&background=1a6e3a&color=fff&size=200'">
                    <div class="info">
                        <span class="name"><?php echo htmlspecialchars($member_nama); ?></span>
                        <span class="role">Member</span>
                    </div>
                    <span class="chevron">▼</span>
                </button>

                <div class="dropdown-menu" id="userDropdown">
                    <a href="profile.php">
                        <span class="icon">👤</span> Profil Saya
                    </a>
                    <a href="guru_edit.php?id=<?php echo $member_id; ?>">
                        <span class="icon">✏️</span> Edit Data
                    </a>
                    <div class="divider"></div>
                    <a href="../cek_status.php?nik=<?php echo $member_nik; ?>" target="_blank">
                        <span class="icon">🔍</span> Cek Status
                    </a>
                    <a href="../berita.php" target="_blank">
                        <span class="icon">📰</span> Berita
                    </a>
                    <div class="divider"></div>
                    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?')">
                        <span class="icon">🚪</span> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="member-content">