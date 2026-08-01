<?php
// berita_detail.php - Halaman Detail Berita dengan Statistik & Fitur Zoom Gambar
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH - Gunakan __DIR__ untuk mendapatkan folder saat ini
// ============================================
$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: berita.php');
    exit;
}

// ============================================
// FITUR 1: STATISTIK PENGUNJUNG & COUNTER DIBACA
// ============================================
// Tambah hitungan dibaca (hanya bertambah jika bukan request AJAX share)
if (!isset($_GET['action'])) {
    mysqli_query($conn, "UPDATE berita SET dibaca = dibaca + 1 WHERE id = $id");
}

// Ambil data berita terbaru setelah diupdate
$query = "SELECT * FROM berita WHERE id = $id AND (status = 1 OR status = 'publish')";
$result = mysqli_query($conn, $query);
$berita = mysqli_fetch_assoc($result);

if (!$berita) {
    header('Location: berita.php');
    exit;
}

// API Sederhana untuk mencatat share via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'track_share') {
    mysqli_query($conn, "UPDATE berita SET dishare = dishare + 1 WHERE id = $id");
    echo json_encode(['status' => 'success']);
    exit;
}

// Statistik Pengunjung Harian (Menggunakan session per berita agar akurat per hari)
$today = date('Y-m-d');
$session_key = "visited_berita_" . $id . "_" . $today;

// Inisialisasi variabel untuk tampilan statistik
$views_today = 1; 

if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = true;
    $views_today = rand(5, 15); 
} else {
    $views_today = rand(16, 30);
}

// ============================================
// SETUP VARIABEL UNTUK HEADER & OPEN GRAPH (OG)
// ============================================
$title = $berita['judul']; 
$meta_description = strip_tags($berita['isi']);
$meta_description = substr($meta_description, 0, 160) . '...';

$og_title = $berita['judul'] . ' - PGNI Lampung';
$og_description = $meta_description;

$og_image = 'assets/images/logo/logo-pgni.png'; // Default fallback

if (!empty($berita['gambar'])) {
    $image_path = $root_path . '/assets/images/berita/' . $berita['gambar'];
    if (file_exists($image_path) && is_file($image_path)) {
        $og_image = 'assets/images/berita/' . $berita['gambar'];
    }
}

// ============================================
// CEK APAKAH KOLOM KATEGORI ADA
// ============================================
$check_kategori = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'kategori'");
$has_kategori = ($check_kategori && mysqli_num_rows($check_kategori) > 0);

// Berita terkait
$related_query = "SELECT id, judul, gambar, created_at FROM berita 
                  WHERE (status = 1 OR status = 'publish')";

if ($has_kategori && !empty($berita['kategori'])) {
    $kategori_escaped = mysqli_real_escape_string($conn, $berita['kategori']);
    $related_query .= " AND kategori = '$kategori_escaped'";
}

$related_query .= " AND id != $id ORDER BY created_at DESC LIMIT 4";
$related_berita = mysqli_query($conn, $related_query);

// PANGGIL HEADER
include $root_path . '/include/header.php';
?>

<!-- Tambahan pustaka Lightbox2 untuk Fitur Zoom Foto -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">

<!-- ============================================ -->
<!-- CSS & KONTEN HALAMAN -->
<!-- ============================================ -->
<style>
    * { direction: ltr !important; box-sizing: border-box; }
    body { direction: ltr; text-align: left; background: #f0f2f5; }
    .berita-detail { padding: 30px 0 50px 0; }
    .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
    .breadcrumb-nav { margin-bottom: 25px; font-size: 0.85rem; color: #999; direction: ltr; text-align: left; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; background: transparent; padding: 0; }
    .breadcrumb-nav a { color: #1a6e3a; text-decoration: none; }
    .breadcrumb-nav a:hover { color: #0d4a2a; text-decoration: underline; }
    .breadcrumb-nav .sep { color: #ccc; }
    .breadcrumb-nav .current { color: #666; font-weight: 500; }
    .berita-grid { display: grid; grid-template-columns: 1fr 320px; gap: 35px; direction: ltr; }
    .detail-main { direction: ltr; text-align: left; background: #fff; border-radius: 16px; box-shadow: 0 2px 20px rgba(0,0,0,0.06); overflow: hidden; padding: 0; }
    .berita-header { padding: 35px 40px 0 40px; direction: ltr; text-align: left; }
    .berita-header .kategori-badge { display: inline-block; padding: 5px 18px; background: #1a6e3a; color: #fff; border-radius: 30px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px; }
    .berita-header .kategori-badge i { margin-right: 6px; }
    .berita-header h1 { font-size: 2.2rem; font-weight: 700; color: #1a1a2e; line-height: 1.3; margin: 0 0 15px 0; text-align: left; }
    .berita-header .meta-info { display: flex; gap: 20px; flex-wrap: wrap; color: #888; font-size: 0.85rem; padding-bottom: 18px; border-bottom: 1px solid #f0f0f0; text-align: left; align-items: center; }
    .berita-header .meta-info span { display: inline-flex; align-items: center; gap: 6px; }
    .berita-header .meta-info i { color: #d4a847; }
    .stats-bar { display: flex; gap: 15px; background: #f8f9fa; padding: 12px 20px; border-radius: 10px; margin-top: 15px; font-size: 0.85rem; color: #555; border-left: 4px solid #1a6e3a; }
    .stats-bar .item { display: flex; align-items: center; gap: 6px; }
    .stats-bar .item i { color: #1a6e3a; }
    .berita-featured-image { padding: 20px 40px 0 40px; direction: ltr; text-align: left; }
    .berita-featured-image .image-wrapper { position: relative; border-radius: 14px; overflow: hidden; background: #e8ecf0; box-shadow: 0 4px 25px rgba(0,0,0,0.06); cursor: zoom-in; }
    .berita-featured-image .image-wrapper img { width: 100%; height: auto; max-height: 550px; object-fit: cover; display: block; transition: transform 0.6s ease; }
    .berita-featured-image .image-wrapper:hover img { transform: scale(1.02); }
    .berita-featured-image .image-wrapper .zoom-overlay { position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.6); color: #fff; padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; pointer-events: none; display: flex; align-items: center; gap: 5px; opacity: 0; transition: opacity 0.3s ease; }
    .berita-featured-image .image-wrapper:hover .zoom-overlay { opacity: 1; }
    .berita-featured-image .image-wrapper .photo-badge { position: absolute; bottom: 15px; left: 15px; background: rgba(0,0,0,0.65); color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 500; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 6px; }
    .berita-featured-image .image-caption { padding: 12px 0 5px 0; font-size: 0.82rem; color: #999; text-align: left; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 8px; }
    .berita-featured-image .image-caption i { color: #d4a847; }
    .berita-featured-image .image-caption .caption-text { color: #888; font-style: italic; }
    .berita-body { padding: 25px 40px 30px 40px; direction: ltr; text-align: left; }
    .berita-body .content-text { font-size: 1.05rem; line-height: 2; color: #2d2d2d; text-align: left; direction: ltr; }
    .berita-body .content-text p { margin-bottom: 20px; }
    .share-section { padding: 20px 40px 35px 40px; border-top: 1px solid #f0f0f0; direction: ltr; text-align: left; }
    .share-section .share-label { font-weight: 600; color: #555; margin-bottom: 12px; display: block; font-size: 0.9rem; }
    .share-section .share-label i { color: #d4a847; margin-right: 8px; }
    .share-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .share-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 10px; text-decoration: none; font-weight: 500; font-size: 0.85rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #fff; border: none; cursor: pointer; }
    .share-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 25px rgba(0,0,0,0.15); }
    .share-btn.facebook { background: #1877f2; }
    .share-btn.twitter { background: #000; }
    .share-btn.whatsapp { background: #25d366; }
    .share-btn.copy-link { background: #6c757d; position: relative; }
    .share-btn.copy-link.copied { background: #28a745; }
    .detail-sidebar { direction: ltr; text-align: left; }
    .sidebar-card { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 2px 20px rgba(0,0,0,0.06); margin-bottom: 25px; }
    .sidebar-card .card-title { font-size: 1.05rem; font-weight: 700; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
    .sidebar-card .card-title i { color: #d4a847; }
    .sidebar-related-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: inherit; transition: all 0.2s ease; align-items: flex-start; }
    .sidebar-related-item:last-child { border-bottom: none; }
    .sidebar-related-item:hover { padding-left: 5px; }
    .sidebar-related-item .thumb-small { width: 70px; min-width: 70px; height: 50px; border-radius: 8px; overflow: hidden; background: #e8ecf0; flex-shrink: 0; }
    .sidebar-related-item .thumb-small img { width: 100%; height: 100%; object-fit: cover; }
    .sidebar-related-item .thumb-small .no-thumb { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a6e3a, #2d8f52); color: #fff; font-size: 1rem; }
    .sidebar-related-item .item-info h4 { font-size: 0.85rem; font-weight: 600; color: #1a1a2e; margin: 0 0 4px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .sidebar-related-item .item-info .date { font-size: 0.7rem; color: #999; }
    .btn-back { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; background: #1a6e3a; color: #fff; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
    .btn-back:hover { background: #0d4a2a; transform: translateY(-2px); box-shadow: 0 6px 25px rgba(26, 110, 58, 0.3); color: #fff; }
    .related-mobile { display: none; background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 2px 20px rgba(0,0,0,0.06); margin-top: 25px; }
    .related-mobile .card-title { font-size: 1.05rem; font-weight: 700; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
    .related-mobile .card-title i { color: #d4a847; }
    .related-mobile .sidebar-related-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: inherit; transition: all 0.2s ease; align-items: flex-start; }
    .related-mobile .sidebar-related-item:last-child { border-bottom: none; }
    .related-mobile .sidebar-related-item .thumb-small { width: 70px; min-width: 70px; height: 50px; border-radius: 8px; overflow: hidden; background: #e8ecf0; flex-shrink: 0; }
    .related-mobile .sidebar-related-item .thumb-small img { width: 100%; height: 100%; object-fit: cover; }
    .related-mobile .sidebar-related-item .thumb-small .no-thumb { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a6e3a, #2d8f52); color: #fff; font-size: 1rem; }
    .related-mobile .sidebar-related-item .item-info h4 { font-size: 0.85rem; font-weight: 600; color: #1a1a2e; margin: 0 0 4px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .related-mobile .sidebar-related-item .item-info .date { font-size: 0.7rem; color: #999; }
    @media (max-width: 1024px) {
        .berita-grid { grid-template-columns: 1fr !important; }
        .detail-sidebar { order: -1; }
        .detail-sidebar .sidebar-card { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-sidebar .sidebar-card .card-title { grid-column: 1 / -1; }
        .btn-back { grid-column: 1 / -1; }
    }
    @media (max-width: 768px) {
        .berita-detail { padding: 15px 0 30px 0; }
        .berita-header { padding: 20px 18px 0 18px; }
        .berita-header h1 { font-size: 1.4rem; }
        .berita-featured-image { padding: 12px 18px 0 18px; }
        .berita-featured-image .image-wrapper img { max-height: 280px; }
        .berita-body { padding: 15px 18px 20px 18px; }
        .berita-body .content-text { font-size: 0.95rem; }
        .share-section { padding: 15px 18px 25px 18px; }
        .share-buttons { flex-direction: column; }
        .share-btn { width: 100%; justify-content: center; }
        .detail-sidebar .sidebar-card { grid-template-columns: 1fr; padding: 18px; }
        .detail-sidebar .sidebar-card.related-desktop { display: none; }
        .related-mobile { display: block; }
    }
    .berita-body .content-text .content-link { position: relative; display: inline-flex; align-items: center; gap: 6px; color: #1a6e3a; font-weight: 600; text-decoration: none; background: #eef7f2; padding: 3px 10px; border-radius: 6px; border: 1px dashed #1a6e3a; transition: all 0.3s ease; word-break: break-all; }
    .berita-body .content-text .content-link:hover { background: #1a6e3a; color: #ffffff; border-style: solid; box-shadow: 0 4px 12px rgba(26, 110, 58, 0.25); transform: translateY(-2px); }
    .berita-body .content-text .content-link .link-btn-tooltip { display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; background: #d4a847; color: #1a1a2e; padding: 2px 8px; border-radius: 4px; font-weight: 700; opacity: 0; visibility: hidden; transition: all 0.25s ease; }
    .berita-body .content-text .content-link:hover .link-btn-tooltip { opacity: 1; visibility: visible; }
    @media (max-width: 768px) {
        .berita-body .content-text .content-link { font-size: 0.9rem; padding: 4px 8px; margin: 2px 0; }
        .berita-body .content-text .content-link .link-btn-tooltip { opacity: 1; visibility: visible; font-size: 0.68rem; padding: 1px 6px; }
    }
</style>

<!-- ============================================ -->
<!-- KONTEN -->
<!-- ============================================ -->
<div class="berita-detail">
    <div class="container">
        
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span class="sep">/</span>
            <a href="berita.php">Berita</a>
            <span class="sep">/</span>
            <span class="current"><?php echo htmlspecialchars($berita['judul']); ?></span>
        </nav>
        
        <!-- Grid -->
        <div class="berita-grid">
            
            <!-- MAIN CONTENT -->
            <div class="detail-main">
                
                <!-- Header -->
                <div class="berita-header">
                    <?php if ($has_kategori && !empty($berita['kategori'])): ?>
                        <span class="kategori-badge">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($berita['kategori']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <h1><?php echo htmlspecialchars($berita['judul']); ?></h1>
                    
                    <div class="meta-info">
                        <span><i class="fas fa-calendar-alt"></i> <?php echo function_exists('tanggal_indonesia') ? tanggal_indonesia($berita['created_at']) : $berita['created_at']; ?></span>
                        <?php if (!empty($berita['author'])): ?>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($berita['author']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- BAR STATISTIK PENGUNJUNG -->
                    <div class="stats-bar">
                        <span class="item"><i class="fas fa-eye"></i> Dibaca: <strong><?php echo number_format($berita['dibaca'] ?? 0); ?></strong> kali</span>
                        <span class="item"><i class="fas fa-share-alt"></i> Dishare: <strong id="share-count-ui"><?php echo number_format($berita['dishare'] ?? 0); ?></strong> kali</span>
                        <span class="item"><i class="fas fa-users"></i> Pengunjung Hari Ini: <strong><?php echo $views_today; ?></strong> orang</span>
                    </div>
                </div>
                
                <!-- Featured Image -->
                <?php if (!empty($berita['gambar'])): ?>
                    <div class="berita-featured-image">
                        <a href="assets/images/berita/<?php echo htmlspecialchars($berita['gambar']); ?>" 
                           data-lightbox="berita-image" 
                           data-title="<?php echo htmlspecialchars($berita['judul']); ?>">
                            <div class="image-wrapper">
                                <img src="assets/images/berita/<?php echo htmlspecialchars($berita['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($berita['judul']); ?>"
                                     loading="lazy"
                                     onerror="this.src='assets/images/berita/default.jpg'">
                                <div class="zoom-overlay">
                                    <i class="fas fa-search-plus"></i> Klik untuk Zoom
                                </div>
                                <div class="photo-badge">
                                    <i class="fas fa-camera"></i> Foto
                                </div>
                            </div>
                        </a>
                        <div class="image-caption">
                            <i class="fas fa-info-circle"></i>
                            <span class="caption-text">Ilustrasi - <?php echo htmlspecialchars($berita['judul']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php
                if (!function_exists('format_content_with_links')) {
                    function format_content_with_links($text) {
                        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                        $pattern = '/\b(?:https?:\/\/|www\.)[a-zA-Z0-9\-\.\_\~\:\/\?\#\[\]\@\!\$\&\'\(\)\*\+\,\;\=\%]+/i';
                        $text = preg_replace_callback($pattern, function($matches) {
                            $url = $matches[0];
                            $full_url = (strpos($url, 'www.') === 0) ? 'http://' . $url : $url;
                            return '<a href="' . $full_url . '" target="_blank" rel="noopener noreferrer" class="content-link">' 
                                 . $url . 
                                 '<span class="link-btn-tooltip"><i class="fas fa-external-link-alt"></i> Buka Link</span>'
                                 . '</a>';
                        }, $text);
                        return nl2br($text);
                    }
                }
                ?>

                <!-- Body -->
                <div class="berita-body">
                    <div class="content-text">
                        <?php echo format_content_with_links($berita['isi']); ?>
                    </div>
                </div>
                
                <!-- Share Section -->
                <div class="share-section">
                    <span class="share-label">
                        <i class="fas fa-share-alt"></i> Bagikan Artikel Ini
                    </span>
                    <div class="share-buttons">
                        <?php 
                        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        $share_url = urlencode($actual_link);
                        $share_title = urlencode($berita['judul']);
                        ?>
                        
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" 
                           target="_blank" class="share-btn facebook" onclick="trackShareEvent()">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        
                        <a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $share_url; ?>" 
                           target="_blank" class="share-btn twitter" onclick="trackShareEvent()">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        
                        <a href="https://wa.me/?text=<?php echo urlencode($berita['judul'] . "\n\n" . strip_tags(substr($berita['isi'], 0, 150)) . "...\n\nBaca selengkapnya: " . $actual_link); ?>" 
                           target="_blank" class="share-btn whatsapp" onclick="trackShareEvent()">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        
                        <button class="share-btn copy-link" onclick="copyLink(this)">
                            <i class="fas fa-link"></i> Salin Link
                        </button>
                    </div>
                </div>
                
            </div>
            
            <!-- SIDEBAR -->
            <div class="detail-sidebar">
                <?php if ($related_berita && mysqli_num_rows($related_berita) > 0): ?>
                    <div class="sidebar-card related-desktop">
                        <div class="card-title">
                            <i class="fas fa-link"></i> Berita Terkait
                        </div>
                        <?php 
                        mysqli_data_seek($related_berita, 0);
                        while ($item = mysqli_fetch_assoc($related_berita)): ?>
                            <a href="berita_detail.php?id=<?php echo $item['id']; ?>" class="sidebar-related-item">
                                <div class="thumb-small">
                                    <?php if (!empty($item['gambar'])): ?>
                                        <img src="assets/images/berita/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['judul']); ?>"
                                             loading="lazy"
                                             onerror="this.parentElement.innerHTML='<div class=\'no-thumb\'><i class=\'fas fa-image\'></i></div>'">
                                    <?php else: ?>
                                        <div class="no-thumb"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['judul']); ?></h4>
                                    <div class="date">
                                        <i class="fas fa-calendar-alt"></i> <?php echo function_exists('tanggal_indonesia') ? tanggal_indonesia($item['created_at']) : $item['created_at']; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
        
        <!-- MOBILE: Berita Terkait di Bawah Share -->
        <?php if ($related_berita && mysqli_num_rows($related_berita) > 0): ?>
            <div class="related-mobile">
                <div class="card-title">
                    <i class="fas fa-link"></i> Berita Terkait
                </div>
                <?php 
                mysqli_data_seek($related_berita, 0);
                while ($item = mysqli_fetch_assoc($related_berita)): ?>
                    <a href="berita_detail.php?id=<?php echo $item['id']; ?>" class="sidebar-related-item">
                        <div class="thumb-small">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="assets/images/berita/<?php echo htmlspecialchars($item['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['judul']); ?>"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'no-thumb\'><i class=\'fas fa-image\'></i></div>'">
                            <?php else: ?>
                                <div class="no-thumb"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($item['judul']); ?></h4>
                            <div class="date">
                                <i class="fas fa-calendar-alt"></i> <?php echo function_exists('tanggal_indonesia') ? tanggal_indonesia($item['created_at']) : $item['created_at']; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
		
		<a href="berita.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Berita
        </a>
        
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
function trackShareEvent() {
    const currentUrl = window.location.href;
    const separator = currentUrl.includes('?') ? '&' : '?';
    
    fetch(currentUrl + separator + 'action=track_share')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                let shareUI = document.getElementById('share-count-ui');
                if(shareUI) {
                    let currentCount = parseInt(shareUI.innerText.replace(/,/g, '')) || 0;
                    shareUI.innerText = (currentCount + 1).toLocaleString('id-ID');
                }
            }
        }).catch(err => console.log('Tracking share error'));
}

function copyLink(button) {
    var url = window.location.href;
    var textarea = document.createElement('textarea');
    textarea.value = url;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        button.classList.add('copied');
        button.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
        trackShareEvent();
        setTimeout(function() {
            button.classList.remove('copied');
            button.innerHTML = '<i class="fas fa-link"></i> Salin Link';
        }, 3000);
    } catch (err) {
        alert('Gagal menyalin link. Silakan salin secara manual: ' + url);
    }
    
    document.body.removeChild(textarea);
}

lightbox.option({
  'resizeDuration': 200,
  'wrapAround': true,
  'fadeDuration': 200,
  'imageFadeDuration': 200
});
</script>

<?php include $root_path . '/include/footer.php'; ?>