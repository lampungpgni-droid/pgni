<?php
// include/header.php
?>
<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- ============================================ -->
    <!-- TITLE & DESCRIPTION -->
    <!-- ============================================ -->
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?>PGNI Lampung</title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : 'Persatuan Guru Ngaji Indonesia Provinsi Lampung - Wadah para guru ngaji, ustadz/ustadzah, dan pengajar Al-Qur\'an di Lampung.'; ?>">
    
    <!-- ============================================ -->
    <!-- OPEN GRAPH TAGS UNTUK SHARE (WhatsApp, FB, dll) -->
    <!-- ============================================ -->
    <?php
    // ============================================
    // AMBIL DATA DARI VARIABEL YANG DISET DI HALAMAN
    // ============================================
    $og_title_final = isset($og_title) ? $og_title : (isset($title) ? $title . ' - PGNI Lampung' : 'PGNI Lampung');
    $og_description_final = isset($og_description) ? $og_description : (isset($meta_description) ? $meta_description : 'Persatuan Guru Ngaji Indonesia Provinsi Lampung');
    
    // ============================================
    // GENERATE BASE URL YANG BENAR (TANPA HARDCORE PATH)
    // ============================================
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Gunakan BASE_URL jika sudah didefinisikan, atau generate otomatis
    if (defined('BASE_URL') && !empty(BASE_URL)) {
        $base_url = rtrim(BASE_URL, '/');
    } else {
        // Generate dari server
        $script_path = dirname($_SERVER['SCRIPT_NAME']);
        if (strpos($script_path, '/admin') !== false) {
            $script_path = dirname($script_path);
        }
        $base_url = $protocol . $host . rtrim($script_path, '/');
    }
    
    // ============================================
    // GENERATE OG IMAGE URL
    // ============================================
    if (isset($og_image) && !empty($og_image)) {
        // Jika $og_image adalah URL lengkap
        if (filter_var($og_image, FILTER_VALIDATE_URL)) {
            $og_image_final = $og_image;
        } 
        // Jika $og_image adalah path relatif
        else {
            $og_image_final = $base_url . '/' . ltrim($og_image, '/');
        }
    } else {
        // Cari logo default (tanpa hardcode /pgnil/)
        $logo_files = [
            'logo-pgni-share.jpg',
            'logo-pgni.png',
            'logo.png'
        ];
        
        $found_logo = false;
        foreach ($logo_files as $logo_file) {
            $logo_full_path = dirname(__DIR__) . '/assets/images/logo/' . $logo_file;
            if (file_exists($logo_full_path)) {
                $og_image_final = $base_url . '/assets/images/logo/' . $logo_file;
                $found_logo = true;
                break;
            }
        }
        
        // Jika tidak ada logo, gunakan placeholder (bukan hardcode)
        if (!$found_logo) {
            // Gunakan BASE_URL untuk fallback
            $og_image_final = $base_url . '/assets/images/logo/default.jpg';
        }
    }
    
    // Pastikan URL absolute (jika masih relatif)
    if (strpos($og_image_final, 'http') !== 0) {
        $og_image_final = $base_url . '/' . ltrim($og_image_final, '/');
    }
    
   // ============================================
    // GENERATE OG URL
    // ============================================
    $og_url_final = $protocol . $host . $_SERVER['REQUEST_URI'];
    ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($og_title_final, ENT_NOQUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($og_description_final, ENT_NOQUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_final); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo htmlspecialchars($og_url_final); ?>">
    <meta property="og:site_name" content="PGNI Lampung">
    <meta property="og:locale" content="id_ID">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title_final, ENT_NOQUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($og_description_final, ENT_NOQUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_final); ?>">
    
    <!-- WhatsApp specific -->
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($og_title_final, ENT_NOQUOTES, 'UTF-8'); ?>">
    <!-- ============================================ -->
    <!-- FAVICON -->
    <!-- ============================================ -->
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/logo/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/logo/favicon.ico" type="image/x-icon">
    <!-- ============================================ -->
    <!-- FONTS & CSS -->
    <!-- ============================================ -->
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Utama -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    
    <!-- ============================================ -->
    <!-- CSS INTERNAL -->
    <!-- ============================================ -->
    <style>
        /* ============================================
           CSS VARIABLES
           ============================================ */
        :root {
            --primary: #1a6e3a;
            --primary-dark: #0e4a26;
            --primary-light: #2d8f52;
            --gold: #d4a847;
            --gold-light: #f0dba8;
            --dark: #1a1a2e;
            --text: #333;
            --text-light: #666;
            --bg-light: #f8f6f1;
            --bg-white: #ffffff;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 40px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s ease;
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
            color: var(--text);
            background: var(--bg-light);
            line-height: 1.7;
            direction: ltr;
            text-align: left;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        a {
            text-decoration: none;
            color: var(--primary);
            transition: var(--transition);
        }
        a:hover {
            color: var(--primary-dark);
        }
        
        ul {
            list-style: none;
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* ============================================
           TOP HEADER
           ============================================ */
        .top-header {
            background: var(--dark);
            color: #fff;
            padding: 8px 0;
            font-size: 0.85rem;
        }
        .top-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-header-left {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .top-header-left span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .top-header-left i {
            color: var(--gold);
        }
        .top-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .social-icons {
            display: flex;
            gap: 8px;
        }
        .social-icons a {
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
            font-size: 0.8rem;
        }
        .social-icons a:hover {
            background: var(--gold);
            color: var(--dark);
            transform: translateY(-2px);
        }
        .btn-admin {
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            background: var(--gold);
            font-size: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-admin:hover {
            background: var(--gold-light);
            color: var(--dark);
        }
        .btn-logout {
            color: #e74c3c;
            font-size: 0.8rem;
            transition: var(--transition);
        }
        .btn-logout:hover {
            color: #ff6b6b;
        }
        .btn-login {
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            font-size: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-login:hover {
            background: rgba(255,255,255,0.25);
        }
        
        /* ============================================
           MAIN HEADER
           ============================================ */
        .main-header {
            background: var(--bg-white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--gold);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }
        .logo a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--dark);
        }
        .logo-img {
            height: 50px;
            width: auto;
        }
        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .logo-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .logo-subtitle {
            font-size: 0.7rem;
            color: var(--text-light);
            font-weight: 400;
            letter-spacing: 1px;
        }
        
        /* ============================================
           MENU TOGGLE (Mobile)
           ============================================ */
        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            z-index: 1001;
        }
        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: #e74c3c;
            border-radius: 3px;
            transition: var(--transition);
        }
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }
        
        /* ============================================
           MAIN NAVIGATION
           ============================================ */
        .main-nav ul {
            display: flex;
            gap: 5px;
        }
        .main-nav ul li {
            position: relative;
        }
        .main-nav ul li a {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 500;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            white-space: nowrap;
        }
        .main-nav ul li a i {
            font-size: 0.9rem;
        }
        .main-nav ul li a:hover,
        .main-nav ul li.active a {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 15px rgba(26,110,58,0.3);
        }
        
        /* ============================================
       DROPDOWN MENU - NASIONAL (DIPERBAIKI)
       ============================================ */
    .dropdown-menu {
        display: none !important;
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 220px;
        max-height: 400px;
        overflow-y: auto;
        background: var(--bg-white);
        border-radius: var(--radius);
        box-shadow: var(--shadow-hover);
        border: 1px solid #eee;
        padding: 8px 0;
        z-index: 1001;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }
    
    .dropdown-menu.show {
        display: block !important;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        animation: dropdownFade 0.2s ease;
    }
    
    @keyframes dropdownFade {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* ============================================
       RESPONSIVE DROPDOWN (MOBILE)
       ============================================ */
    @media (max-width: 768px) {
        .dropdown-menu {
            position: static;
            box-shadow: none;
            border: none;
            border-left: 3px solid var(--gold);
            margin: 5px 0 5px 15px;
            padding: 5px 0 5px 10px;
            max-height: 300px;
            border-radius: 0;
            transform: none;
        }
        
        .dropdown-menu.show {
            display: block !important;
        }
    }
        /* ============================================
           ISLAMIC DECORATION
           ============================================ */
        .islamic-decoration {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 8px 0;
            text-align: center;
            color: var(--gold-light);
            font-family: 'Amiri', serif;
            font-size: 1.1rem;
            letter-spacing: 2px;
        }
        
        /* ============================================
   RUNNING TEXT STYLES
   ============================================ */
.running-text-wrapper {
    background: linear-gradient(90deg, #1a6e3a, #0e4a26);
    padding: 8px 0;
    border-bottom: 2px solid #d4a847;
    position: relative;
    overflow: hidden;
}

.running-text-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.running-text-icon {
    color: #d4a847;
    font-size: 1.1rem;
    flex-shrink: 0;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.running-text-content {
    flex: 1;
    overflow: hidden;
    position: relative;
}

.running-text-scroll {
    overflow: hidden;
    white-space: nowrap;
}

.running-text-list {
    display: flex;
    animation: scrollText 30s linear infinite;
    margin: 0;
    padding: 0;
    list-style: none;
}

.running-text-list li {
    color: #fff;
    font-size: 0.9rem;
    padding: 0 40px;
    flex-shrink: 0;
    font-weight: 400;
    letter-spacing: 0.5px;
    position: relative;
}

.running-text-list li::after {
    content: "•";
    color: #d4a847;
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.running-text-list li:last-child::after {
    content: none;
}

@keyframes scrollText {
    0% {
        transform: translateX(100%);
    }
    100% {
        transform: translateX(-100%);
    }
}

/* Pause animation on hover */
.running-text-scroll:hover .running-text-list {
    animation-play-state: paused;
}

/* ============================================
   RUNNING TEXT RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .running-text-wrapper {
        padding: 6px 0;
    }
    .running-text-icon {
        font-size: 0.9rem;
        display: none;
    }
    .running-text-list li {
        font-size: 0.8rem;
        padding: 0 25px;
    }
    .running-text-list li::after {
        right: 10px;
    }
    @keyframes scrollText {
        0% { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }
}

@media (max-width: 480px) {
    .running-text-list li {
        font-size: 0.7rem;
        padding: 0 15px;
    }
    .running-text-list li::after {
        right: 5px;
    }
}
        
        
        
        /* ============================================
           BREADCRUMB
           ============================================ */
        .breadcrumb-wrapper {
            background: #fff;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .breadcrumb-wrapper .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            font-size: 0.9rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .breadcrumb-wrapper .breadcrumb-item {
            display: flex;
            align-items: center;
        }
        .breadcrumb-wrapper .breadcrumb-item a {
            color: #666;
            text-decoration: none;
            transition: var(--transition);
        }
        .breadcrumb-wrapper .breadcrumb-item a:hover {
            color: var(--primary);
        }
        .breadcrumb-wrapper .breadcrumb-item .sep {
            margin: 0 8px;
            color: #ccc;
        }
        .breadcrumb-wrapper .breadcrumb-item.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* ============================================
           SCROLL TO TOP
           ============================================ */
        #scrollTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(26,110,58,0.3);
            transition: var(--transition);
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            z-index: 999;
        }
        #scrollTop.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        #scrollTop:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(26,110,58,0.4);
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 40px 20px;
            }
            .hero-description {
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
            }
            .hero-buttons {
                justify-content: center;
            }
            .hero-stats {
                max-width: 500px;
                margin: 0 auto;
            }
            .visi-misi-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 2.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }
            
            .main-nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 320px;
                height: 100vh;
                background: var(--bg-white);
                padding: 80px 25px 30px;
                box-shadow: -4px 0 30px rgba(0,0,0,0.15);
                transition: var(--transition);
                overflow-y: auto;
                z-index: 999;
            }
            .main-nav.open {
                right: 0;
            }
            .main-nav ul {
                flex-direction: column;
                gap: 3px;
            }
            .main-nav ul li a {
                padding: 12px 16px;
                border-radius: 8px;
                white-space: normal;
            }
            
            /* Dropdown mobile */
            .dropdown-menu {
                position: static;
                box-shadow: none;
                border: none;
                border-left: 3px solid var(--gold);
                margin: 5px 0 5px 15px;
                padding: 5px 0 5px 10px;
                max-height: 300px;
                border-radius: 0;
            }
            
            .dropdown-menu li a {
                padding: 8px 16px;
                font-size: 0.85rem;
                white-space: normal;
            }
            
            .dropdown-toggle .fa-chevron-down {
                margin-left: auto;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            .hero-stats {
                grid-template-columns: repeat(3, 1fr);
                padding: 20px;
                gap: 10px;
            }
            .stat-number {
                font-size: 1.8rem;
            }
            .berita-grid {
                grid-template-columns: 1fr;
            }
            .section-title {
                font-size: 1.6rem;
            }
            .top-header .container {
                flex-direction: column;
                text-align: center;
            }
            .top-header-left {
                justify-content: center;
            }
            .top-header-right {
                justify-content: center;
            }
            .pengurus-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 20px;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .section-header.text-center {
                align-items: center;
            }
            .section-berita,
            .section-pengurus,
            .section-visi-misi,
            .section-cta {
                padding: 50px 0;
            }
            .hero-section {
                min-height: 60vh;
                padding: 30px 0;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 1.8rem;
            }
            .hero-stats {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }
            .hero-buttons .btn-primary,
            .hero-buttons .btn-secondary {
                width: 100%;
                justify-content: center;
            }
            .stat-number {
                font-size: 1.5rem;
            }
            .berita-image {
                height: 180px;
            }
            .pengurus-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .cta-content h2 {
                font-size: 1.8rem;
            }
            .cta-buttons {
                flex-direction: column;
                width: 100%;
            }
            .cta-buttons .btn-primary,
            .cta-buttons .btn-secondary {
                width: 100%;
                justify-content: center;
            }
            .hero-container {
                padding: 15px;
            }
            .visi-box,
            .misi-box {
                padding: 25px 20px;
            }
            .hero-section {
                min-height: 50vh;
            }
        }
        
       
/* Memperjelas warna teks saat pengunjung mengetik di tawk.to */
#tawkchat-container input[type="text"], 
#tawkchat-container textarea,
#tawkchat-container [contenteditable="true"] {
    color: #333333 !important; /* Warna teks ketik menjadi abu-abu gelap/hitam */
    opacity: 1 !important;
}

/* Memperjelas teks petunjuk (placeholder) */
#tawkchat-container input::placeholder, 
#tawkchat-container textarea::placeholder {
    color: #888888 !important;
    opacity: 1 !important;
}


    </style>
</head>
<body>

<?php
// ============================================
// AMBIL DATA PROVINSI UNTUK DROPDOWN NASIONAL
// ============================================
// Include database
require_once __DIR__ . '/../config/database.php';

$provinsi_list = [];
$query = "SELECT id, nama FROM provinsi ORDER BY nama";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $provinsi_list[] = $row;
    }
}
?>

<!-- ============================================ -->
<!-- TOP HEADER -->
<!-- ============================================ -->
<div class="top-header">
    <div class="container">
        <div class="top-header-right">
            <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                <a href="<?php echo ADMIN_URL; ?>" class="btn-admin">
                    <i class="fas fa-user-shield"></i> Admin
                </a>
                <a href="<?php echo ADMIN_URL; ?>logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?php echo ADMIN_URL; ?>login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MAIN HEADER -->
<!-- ============================================ -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo/logo-pgni.png" alt="PGNI Lampung" class="logo-img" onerror="this.style.display='none'">
                    <div class="logo-text">
                        <span class="logo-title">PGNI NASIONAL</span>
                        <span class="logo-subtitle">Persatuan Guru Ngaji Indonesia</span>
                    </div>
                </a>
            </div>
            
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == '' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>"><i class="fas fa-home"></i> Beranda</a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'berita.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>berita.php"><i class="fas fa-newspaper"></i> Berita</a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tentang.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>tentang.php"><i class="fas fa-info-circle"></i> Tentang</a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'pengurus.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>pengurus.php"><i class="fas fa-users"></i> Pengurus</a>
                    </li>
                    
                    <!-- ============================================ -->
                    <!-- MENU NASIONAL DENGAN DROPDOWN PROVINSI -->
                    <!-- ============================================ -->
                    <li class="dropdown-toggle" id="nasionalDropdown">
                            <i class="fas fa-map-marked-alt"></i> Nasional
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <?php if (count($provinsi_list) > 0): ?>
                        <ul class="dropdown-menu" id="nasionalMenu">
                            <?php foreach ($provinsi_list as $provinsi): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>nasional.php?provinsi=<?php echo $provinsi['id']; ?>">
                                    <i class="fas fa-flag"></i> <?php echo htmlspecialchars($provinsi['nama']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    
                    
                    <li>
                        <a href="<?php echo BASE_URL; ?>registrasi.php"><i class="fas fa-user-plus"></i> Daftar</a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>cek_status.php"><i class="fas fa-search"></i> Cek Status</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!-- ============================================ -->
<!-- ISLAMIC DECORATION -->
<!-- ============================================ -->
<div class="islamic-decoration">
    <div class="container">
        <span class="arabic-text">بسم الله الرحمن الرحيم</span>
    </div>
</div>

<!-- ============================================ -->
<!-- RUNNING TEXT - TAMPILAN FRONTEND -->
<!-- ============================================ -->

<?php
// Ambil running text yang aktif
$running_texts = [];
$query_rt = "SELECT teks, urutan FROM running_text WHERE status = 'aktif' ORDER BY urutan ASC, id ASC";
$result_rt = mysqli_query($conn, $query_rt);
if ($result_rt) {
    while ($row = mysqli_fetch_assoc($result_rt)) {
        $running_texts[] = $row['teks'];
    }
}
?>

<?php if (!empty($running_texts)): ?>
<div class="running-text-wrapper">
    <div class="container">
        <div class="running-text-container">
            <div class="running-text-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="running-text-content">
                <div class="running-text-scroll">
                    <ul class="running-text-list">
                        <?php foreach ($running_texts as $text): ?>
                            <li><?php echo htmlspecialchars($text); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- BREADCRUMB -->
<!-- ============================================ -->
<?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
<div class="breadcrumb-wrapper">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>"><i class="fas fa-home"></i> Beranda</a>
                </li>
                <?php foreach ($breadcrumb as $item): ?>
                    <?php if (isset($item['url']) && $item['url']): ?>
                        <li class="breadcrumb-item">
                            <span class="sep">/</span>
                            <a href="<?php echo $item['url']; ?>"><?php echo $item['label']; ?></a>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">
                            <span class="sep">/</span>
                            <span><?php echo $item['label']; ?></span>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
</div>
<?php endif; ?>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/6a650061ae8c151d44811d05/1jud8iv78';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->



<!-- ============================================ -->
<!-- SCROLL TO TOP BUTTON -->
<!-- ============================================ -->
<button id="scrollTop" title="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ============================================ -->
<!-- JAVASCRIPT UNTUK DROPDOWN & MOBILE MENU -->
<!-- ============================================ -->
<script>
// ============================================
// DROPDOWN NASIONAL - Toggle
// ============================================
function toggleDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    var dropdown = document.getElementById('nasionalDropdown');
    var menu = document.getElementById('nasionalMenu');
    
    if (dropdown && menu) {
        menu.classList.toggle('show');
        dropdown.classList.toggle('active');
    }
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('nasionalDropdown');
    var menu = document.getElementById('nasionalMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.remove('show');
        dropdown.classList.remove('active');
    }
});

// ============================================
// MOBILE MENU - Tambahan untuk kompatibilitas
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.getElementById('menuToggle');
    var mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        // Tutup menu saat klik link di dalam menu
        mainNav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    menuToggle.classList.remove('active');
                    mainNav.classList.remove('open');
                }
            });
        });
        
        // Tutup menu saat resize ke desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                menuToggle.classList.remove('active');
                mainNav.classList.remove('open');
            }
        });
    }
});

// ============================================
// DROPDOWN NASIONAL - Toggle (DIPERBAIKI)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var dropdownToggle = document.getElementById('nasionalDropdown');
    var dropdownMenu = document.getElementById('nasionalMenu');
    
    if (dropdownToggle && dropdownMenu) {
        // Pastikan dropdown tersembunyi di awal
        dropdownMenu.classList.remove('show');
        dropdownToggle.classList.remove('active');
        
        // Fungsi toggle dropdown
        function toggleDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Toggle class show
            dropdownMenu.classList.toggle('show');
            dropdownToggle.classList.toggle('active');
            
            // Debug
            console.log('Dropdown toggled. Show:', dropdownMenu.classList.contains('show'));
        }
        
        // Event listener untuk klik pada toggle
        dropdownToggle.addEventListener('click', function(e) {
            // Hanya untuk mobile atau jika diklik
            toggleDropdown(e);
        });
        
        // Untuk desktop - hover effect
        dropdownToggle.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768) {
                dropdownMenu.classList.add('show');
                dropdownToggle.classList.add('active');
            }
        });
        
        dropdownToggle.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('active');
            }
        });
        
        // Tutup dropdown saat klik di luar
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('active');
            }
        });
        
        // Tutup dropdown saat ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('active');
            }
        });
        
        // Tutup dropdown saat resize ke desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('active');
            }
        });
    }
});

// ============================================
// RUNNING TEXT - Pause on hover
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const scrollElement = document.querySelector('.running-text-scroll');
    if (scrollElement) {
        scrollElement.addEventListener('mouseenter', function() {
            this.querySelector('.running-text-list').style.animationPlayState = 'paused';
        });
        scrollElement.addEventListener('mouseleave', function() {
            this.querySelector('.running-text-list').style.animationPlayState = 'running';
        });
    }
});

console.log('PGNI Lampung - Dropdown initialized');
</script>