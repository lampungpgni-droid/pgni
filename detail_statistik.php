<?php
// detail_statistik.php - Halaman Statistik Lengkap Guru Ngaji Per Wilayah
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH
// ============================================
$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Statistik Guru Ngaji Per Wilayah - PGNI Lampung';

// ============================================
// PARAMETER FILTER
// ============================================
$kabupaten_id = isset($_GET['kabupaten_id']) ? (int)$_GET['kabupaten_id'] : 0;
$kecamatan_id = isset($_GET['kecamatan_id']) ? (int)$_GET['kecamatan_id'] : 0;
$filter_level = isset($_GET['level']) ? $_GET['level'] : 'kabupaten';

// ============================================
// AMBIL DATA KABUPATEN
// ============================================
$kabupaten_query = "
    SELECT 
        k.id,
        k.nama,
        COUNT(DISTINCT g.id) as total_guru,
        COUNT(DISTINCT kec.id) as total_kecamatan,
        COUNT(DISTINCT d.id) as total_desa
    FROM kabupaten k
    LEFT JOIN kecamatan kec ON kec.kabupaten_id = k.id
    LEFT JOIN desa d ON d.kecamatan_id = kec.id
    LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
    WHERE k.id BETWEEN 1801 AND 1813 OR k.id IN (1871, 1872)
    GROUP BY k.id, k.nama
    ORDER BY total_guru DESC, k.nama ASC
";
$kabupaten_result = mysqli_query($conn, $kabupaten_query);

// ============================================
// DATA KABUPATEN TERPILIH
// ============================================
$selected_kabupaten = null;
if ($kabupaten_id > 0) {
    $query = "
        SELECT 
            k.id,
            k.nama,
            COUNT(DISTINCT g.id) as total_guru,
            COUNT(DISTINCT kec.id) as total_kecamatan,
            COUNT(DISTINCT d.id) as total_desa
        FROM kabupaten k
        LEFT JOIN kecamatan kec ON kec.kabupaten_id = k.id
        LEFT JOIN desa d ON d.kecamatan_id = kec.id
        LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
        WHERE k.id = $kabupaten_id
        GROUP BY k.id, k.nama
    ";
    $result = mysqli_query($conn, $query);
    $selected_kabupaten = mysqli_fetch_assoc($result);
}

// ============================================
// DATA KECAMATAN PER KABUPATEN
// ============================================
$kecamatan_list = [];
if ($kabupaten_id > 0) {
    $query = "
        SELECT 
            kec.id,
            kec.nama,
            COUNT(DISTINCT g.id) as total_guru,
            COUNT(DISTINCT d.id) as total_desa
        FROM kecamatan kec
        LEFT JOIN desa d ON d.kecamatan_id = kec.id
        LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
        WHERE kec.kabupaten_id = $kabupaten_id
        GROUP BY kec.id, kec.nama
        ORDER BY total_guru DESC, kec.nama ASC
    ";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $kecamatan_list[] = $row;
    }
}

// ============================================
// DATA DESA PER KECAMATAN
// ============================================
$desa_list = [];
if ($kecamatan_id > 0) {
    $query = "
        SELECT 
            d.id,
            d.nama,
            COUNT(DISTINCT g.id) as total_guru
        FROM desa d
        LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
        WHERE d.kecamatan_id = $kecamatan_id
        GROUP BY d.id, d.nama
        ORDER BY total_guru DESC, d.nama ASC
    ";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $desa_list[] = $row;
    }
}

// ============================================
// STATISTIK GLOBAL
// ============================================
$stat_global = [
    'total_guru' => 0,
    'total_kabupaten' => 0,
    'total_kecamatan' => 0,
    'total_desa' => 0
];

$query = "
    SELECT 
        COUNT(DISTINCT g.id) as total_guru,
        COUNT(DISTINCT k.id) as total_kabupaten,
        COUNT(DISTINCT kec.id) as total_kecamatan,
        COUNT(DISTINCT d.id) as total_desa
    FROM kabupaten k
    LEFT JOIN kecamatan kec ON kec.kabupaten_id = k.id
    LEFT JOIN desa d ON d.kecamatan_id = kec.id
    LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
    WHERE k.id BETWEEN 1801 AND 1813 OR k.id IN (1871, 1872)
";
$result = mysqli_query($conn, $query);
if ($result) {
    $stat_global = mysqli_fetch_assoc($result);
}

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- CSS TAMBAHAN -->
<!-- ============================================ -->
<style>
    * { direction: ltr !important; }
    body { direction: ltr; text-align: left; }
    
    .stat-header {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        padding: 40px 0;
        color: #fff;
        margin-bottom: 25px;
    }
    .stat-header h1 {
        font-size: 2rem;
        margin-bottom: 5px;
    }
    .stat-header p {
        opacity: 0.85;
        font-size: 1rem;
    }
    
    /* ===== STATISTIK CARD - LEBIH RINGKAS ===== */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 14px 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        text-align: center;
        border-left: 4px solid #1a6e3a;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.10);
    }
    .stat-card .number {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.2;
    }
    .stat-card .label {
        font-size: 0.75rem;
        color: #999;
        margin-top: 2px;
    }
    .stat-card .icon {
        font-size: 1.2rem;
        color: #d4a847;
        margin-bottom: 4px;
    }
    
    .stat-card.green { border-left-color: #1a6e3a; }
    .stat-card.gold { border-left-color: #d4a847; }
    .stat-card.blue { border-left-color: #3498db; }
    .stat-card.purple { border-left-color: #9b59b6; }
    
    /* ===== KABUPATEN CARD ===== */
    .kabupaten-card {
        background: #fff;
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 3px solid transparent;
        position: relative;
        overflow: hidden;
    }
    .kabupaten-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(26, 110, 58, 0.03), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }
    .kabupaten-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        border-color: rgba(212, 168, 71, 0.3);
    }
    .kabupaten-card.active {
        border-color: #1a6e3a;
        background: linear-gradient(135deg, #f0faf3, #e8f5e9);
        box-shadow: 0 4px 20px rgba(26, 110, 58, 0.15);
        transform: scale(1.01);
    }
    .kabupaten-card.active .badge-active {
        display: inline-block !important;
    }
    .kabupaten-card.active .nama {
        color: #1a6e3a;
        font-weight: 700;
    }
    .kabupaten-card.active .jumlah {
        color: #1a6e3a;
        font-weight: 800;
        font-size: 1.15rem;
    }
    
    .kabupaten-card .badge-active {
        display: none;
        background: #1a6e3a;
        color: #fff;
        font-size: 0.55rem;
        padding: 1px 8px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: 0.3px;
        margin-left: 6px;
        vertical-align: middle;
    }
    .kabupaten-card .badge-active i {
        font-size: 0.4rem;
        margin-right: 2px;
    }
    
    .kabupaten-card .nama {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.95rem;
        transition: color 0.3s ease;
    }
    .kabupaten-card .jumlah {
        font-weight: 700;
        color: #1a6e3a;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    .kabupaten-card .detail {
        font-size: 0.7rem;
        color: #999;
    }
    .kabupaten-card .icon-indicator {
        font-size: 0.7rem;
        transition: all 0.3s ease;
    }
    .kabupaten-card.active .icon-indicator {
        color: #1a6e3a;
    }
    
    /* ===== KECAMATAN CARD ===== */
    .kecamatan-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 16px;
        transition: all 0.3s ease;
        border-left: 4px solid #d4a847;
        cursor: pointer;
        position: relative;
    }
    .kecamatan-card:hover {
        background: #f0faf3;
        border-left-color: #1a6e3a;
        transform: translateX(3px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .kecamatan-card.active {
        background: #e8f5e9;
        border-left-color: #1a6e3a;
        border: 2px solid #1a6e3a;
        padding: 11px 15px;
    }
    .kecamatan-card .nama {
        font-weight: 500;
        color: #1a1a2e;
        font-size: 0.9rem;
    }
    .kecamatan-card .jumlah {
        font-weight: 600;
        color: #1a6e3a;
    }
    .kecamatan-card .badge-active {
        display: none;
        background: #1a6e3a;
        color: #fff;
        font-size: 0.5rem;
        padding: 1px 8px;
        border-radius: 20px;
        font-weight: 600;
        margin-left: 6px;
    }
    .kecamatan-card.active .badge-active {
        display: inline-block;
    }
    
    /* ===== DESA ITEM ===== */
    .desa-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s ease;
    }
    .desa-item:hover {
        background: #f8f9fa;
    }
    .desa-item:last-child {
        border-bottom: none;
    }
    .desa-item .nama {
        color: #333;
        font-size: 0.85rem;
    }
    .desa-item .jumlah {
        font-weight: 600;
        color: #1a6e3a;
        font-size: 0.85rem;
        background: #e8f5e9;
        padding: 1px 10px;
        border-radius: 20px;
    }
    .desa-item .jumlah.zero {
        background: #f5f5f5;
        color: #999;
    }
    
    /* ===== PROGRESS BAR ===== */
    .progress-bar-custom {
        height: 4px;
        background: #e8e8e8;
        border-radius: 2px;
        overflow: hidden;
        margin-top: 6px;
    }
    .progress-bar-custom .fill {
        height: 100%;
        border-radius: 2px;
        transition: width 1s ease;
        background: linear-gradient(90deg, #1a6e3a, #2d8f52);
    }
    .kabupaten-card.active .progress-bar-custom .fill {
        background: linear-gradient(90deg, #d4a847, #f0c75e);
    }
    
    /* ===== BREADCRUMB ===== */
    .breadcrumb-custom {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        padding: 10px 0;
        font-size: 0.85rem;
        color: #666;
    }
    .breadcrumb-custom a {
        color: #1a6e3a;
        text-decoration: none;
        font-weight: 500;
    }
    .breadcrumb-custom a:hover {
        text-decoration: underline;
        color: #d4a847;
    }
    .breadcrumb-custom .separator {
        color: #ccc;
    }
    .breadcrumb-custom .current {
        color: #1a1a2e;
        font-weight: 600;
    }
    
    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 30px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px dashed #ddd;
    }
    .empty-state i {
        font-size: 2rem;
        color: #d4a847;
        display: block;
        margin-bottom: 8px;
    }
    .empty-state h4 {
        font-size: 1rem;
        color: #1a1a2e;
        margin: 5px 0;
    }
    .empty-state p {
        color: #999;
        font-size: 0.9rem;
    }
    
    /* ===== SCROLL AREA ===== */
    .scroll-area {
        max-height: 550px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .scroll-area::-webkit-scrollbar {
        width: 4px;
    }
    .scroll-area::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 10px;
    }
    .scroll-area::-webkit-scrollbar-thumb {
        background: #1a6e3a;
        border-radius: 10px;
    }
    
    /* ============================================ */
    /* RESPONSIVE */
    /* ============================================ */
    
    /* Tablet */
    @media (max-width: 1024px) {
        .statistik-container {
            grid-template-columns: 1fr !important;
        }
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }
    
    /* Mobile */
    @media (max-width: 768px) {
        .stat-header {
            padding: 25px 0;
        }
        .stat-header h1 {
            font-size: 1.3rem;
        }
        .stat-header p {
            font-size: 0.85rem;
        }
        
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .stat-card {
            padding: 10px 12px;
            border-radius: 8px;
        }
        .stat-card .number {
            font-size: 1.2rem;
        }
        .stat-card .label {
            font-size: 0.65rem;
        }
        .stat-card .icon {
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .kabupaten-card {
            padding: 10px 14px;
        }
        .kabupaten-card .nama {
            font-size: 0.85rem;
        }
        .kabupaten-card .jumlah {
            font-size: 0.9rem;
        }
        .kabupaten-card .detail {
            font-size: 0.65rem;
        }
        .kabupaten-card.active {
            transform: scale(1.01);
        }
        
        .kecamatan-card {
            padding: 10px 14px;
        }
        .kecamatan-card .nama {
            font-size: 0.8rem;
        }
        
        .desa-item {
            padding: 6px 12px;
        }
        .desa-item .nama {
            font-size: 0.8rem;
        }
        .desa-item .jumlah {
            font-size: 0.75rem;
            padding: 1px 8px;
        }
        
        .scroll-area {
            max-height: 400px;
        }
    }
    
    /* Mobile Small */
    @media (max-width: 480px) {
        .stat-header {
            padding: 18px 0;
        }
        .stat-header h1 {
            font-size: 1.1rem;
        }
        .stat-header p {
            font-size: 0.75rem;
        }
        
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }
        .stat-card {
            padding: 8px 10px;
            border-radius: 6px;
            border-left-width: 3px;
        }
        .stat-card .number {
            font-size: 1rem;
        }
        .stat-card .label {
            font-size: 0.6rem;
        }
        .stat-card .icon {
            font-size: 0.8rem;
            margin-bottom: 1px;
        }
        
        .kabupaten-card {
            padding: 8px 12px;
            border-radius: 8px;
        }
        .kabupaten-card .nama {
            font-size: 0.78rem;
        }
        .kabupaten-card .jumlah {
            font-size: 0.82rem;
        }
        .kabupaten-card .detail {
            font-size: 0.6rem;
        }
        .kabupaten-card .badge-active {
            font-size: 0.45rem;
            padding: 1px 6px;
        }
        .kabupaten-card .progress-bar-custom {
            height: 3px;
            margin-top: 4px;
        }
        
        .kecamatan-card {
            padding: 8px 12px;
        }
        .kecamatan-card .nama {
            font-size: 0.75rem;
        }
        .kecamatan-card .jumlah {
            font-size: 0.8rem;
        }
        .kecamatan-card .progress-bar-custom {
            height: 3px;
            margin-top: 4px;
        }
        
        .desa-item {
            padding: 5px 10px;
        }
        .desa-item .nama {
            font-size: 0.75rem;
        }
        .desa-item .jumlah {
            font-size: 0.7rem;
            padding: 1px 6px;
        }
        
        .breadcrumb-custom {
            font-size: 0.75rem;
            padding: 6px 0;
        }
        
        .scroll-area {
            max-height: 300px;
        }
        
        .empty-state {
            padding: 20px 15px;
        }
        .empty-state i {
            font-size: 1.5rem;
        }
        .empty-state h4 {
            font-size: 0.9rem;
        }
        .empty-state p {
            font-size: 0.8rem;
        }
    }
</style>

<!-- ============================================ -->
<!-- HEADER STATISTIK -->
<!-- ============================================ -->
<div class="stat-header">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">
        <h1><i class="fas fa-chart-pie"></i> Statistik Guru Ngaji Per Wilayah</h1>
        <p>Data sebaran guru ngaji aktif berdasarkan Kabupaten/Kota, Kecamatan, dan Desa di Provinsi Lampung</p>
    </div>
</div>

<!-- ============================================ -->
<!-- STATISTIK GLOBAL - LEBIH RINGKAS -->
<!-- ============================================ -->
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">
    <div class="stat-grid">
        <div class="stat-card green">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="number"><?php echo number_format($stat_global['total_guru'] ?? 0); ?></div>
            <div class="label">Total Guru Aktif</div>
        </div>
        <div class="stat-card gold">
            <div class="icon"><i class="fas fa-city"></i></div>
            <div class="number"><?php echo number_format($stat_global['total_kabupaten'] ?? 0); ?></div>
            <div class="label">Kabupaten/Kota</div>
        </div>
        <div class="stat-card blue">
            <div class="icon"><i class="fas fa-map"></i></div>
            <div class="number"><?php echo number_format($stat_global['total_kecamatan'] ?? 0); ?></div>
            <div class="label">Kecamatan</div>
        </div>
        <div class="stat-card purple">
            <div class="icon"><i class="fas fa-location-dot"></i></div>
            <div class="number"><?php echo number_format($stat_global['total_desa'] ?? 0); ?></div>
            <div class="label">Desa</div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- BREADCRUMB NAVIGASI -->
<!-- ============================================ -->
<?php if ($kabupaten_id > 0 || $kecamatan_id > 0): ?>
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">
    <div class="breadcrumb-custom">
        <a href="detail_statistik.php"><i class="fas fa-home"></i> Semua Wilayah</a>
        <?php if ($kabupaten_id > 0 && $selected_kabupaten): ?>
            <span class="separator">/</span>
            <a href="detail_statistik.php?kabupaten_id=<?php echo $kabupaten_id; ?>">
                <?php echo htmlspecialchars($selected_kabupaten['nama']); ?>
            </a>
        <?php endif; ?>
        <?php if ($kecamatan_id > 0): ?>
            <span class="separator">/</span>
            <span class="current">Detail Kecamatan</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- KONTEN UTAMA -->
<!-- ============================================ -->
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 16px 40px;">
    <div class="statistik-container" style="display: grid; grid-template-columns: <?php echo ($kabupaten_id > 0 || $kecamatan_id > 0) ? '1fr 1fr' : '1fr'; ?>; gap: 25px;">
        
        <!-- ============================================ -->
        <!-- KOLOM KIRI: DAFTAR KABUPATEN -->
        <!-- ============================================ -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <h3 style="font-size: 1rem; color: #1a1a2e; margin: 0;">
                    <i class="fas fa-city" style="color: #d4a847;"></i> 
                    <?php echo ($kabupaten_id > 0) ? 'Daftar Kabupaten/Kota' : 'Statistik Per Kabupaten/Kota'; ?>
                </h3>
                <?php if ($kabupaten_id > 0): ?>
                    <a href="detail_statistik.php" style="color: #1a6e3a; font-size: 0.8rem; text-decoration: none; font-weight: 500;">
                        <i class="fas fa-times-circle"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="scroll-area">
                <?php if ($kabupaten_result && mysqli_num_rows($kabupaten_result) > 0): ?>
                    <?php 
                    $max_guru = 0;
                    $temp_data = [];
                    mysqli_data_seek($kabupaten_result, 0);
                    while ($row = mysqli_fetch_assoc($kabupaten_result)) {
                        $temp_data[] = $row;
                        if ($row['total_guru'] > $max_guru) $max_guru = $row['total_guru'];
                    }
                    $max_guru = max($max_guru, 1);
                    ?>
                    
                    <?php foreach ($temp_data as $kab): ?>
                        <?php 
                        $persentase = round(($kab['total_guru'] / $max_guru) * 100);
                        $is_active = ($kabupaten_id == $kab['id']);
                        ?>
                        <a href="detail_statistik.php?kabupaten_id=<?php echo $kab['id']; ?>" 
                           style="text-decoration: none; display: block; margin-bottom: 8px;">
                            <div class="kabupaten-card <?php echo $is_active ? 'active' : ''; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                                    <div>
                                        <span class="nama">
                                            <span class="icon-indicator">
                                                <i class="fas fa-<?php echo $is_active ? 'chevron-right' : 'circle'; ?>" 
                                                   style="color: <?php echo $is_active ? '#1a6e3a' : '#ddd'; ?>; font-size: 0.6rem; margin-right: 5px;"></i>
                                            </span>
                                            <?php echo htmlspecialchars($kab['nama']); ?>
                                            <span class="badge-active">
                                                <i class="fas fa-check-circle"></i> Dipilih
                                            </span>
                                        </span>
                                        <div class="detail">
                                            <i class="fas fa-map" style="color: #d4a847;"></i> 
                                            <?php echo number_format($kab['total_kecamatan'] ?? 0); ?> Kec · 
                                            <i class="fas fa-location-dot" style="color: #d4a847;"></i> 
                                            <?php echo number_format($kab['total_desa'] ?? 0); ?> Desa
                                        </div>
                                    </div>
                                    <div class="jumlah">
                                        <?php if ($is_active): ?>
                                            <i class="fas fa-arrow-right" style="font-size: 0.7rem; margin-right: 3px;"></i>
                                        <?php endif; ?>
                                        <?php echo number_format($kab['total_guru']); ?>
                                    </div>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="fill" style="width: <?php echo $persentase; ?>%;"></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-city"></i>
                        <p>Belum ada data kabupaten.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- KOLOM KANAN: DETAIL -->
        <!-- ============================================ -->
        <div>
            <?php if ($kecamatan_id > 0 && !empty($desa_list)): ?>
                <!-- ===== DETAIL DESA PER KECAMATAN ===== -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <h3 style="font-size: 1rem; color: #1a1a2e; margin: 0;">
                        <i class="fas fa-location-dot" style="color: #d4a847;"></i> 
                        Daftar Desa
                        <?php 
                        $kec_nama = '';
                        $query = "SELECT nama FROM kecamatan WHERE id = $kecamatan_id";
                        $result = mysqli_query($conn, $query);
                        if ($result && $row = mysqli_fetch_assoc($result)) {
                            $kec_nama = $row['nama'];
                        }
                        if ($kec_nama): 
                        ?>
                            <span style="font-weight: 400; color: #666; font-size: 0.8rem;">- <?php echo htmlspecialchars($kec_nama); ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                
                <div style="background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                    <?php 
                    $total_guru_desa = 0;
                    foreach ($desa_list as $desa) {
                        $total_guru_desa += $desa['total_guru'];
                    }
                    ?>
                    <div style="padding: 8px 14px; background: #f8f9fa; border-bottom: 1px solid #e8e8e8; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 500; color: #555; font-size: 0.8rem;">
                            <i class="fas fa-users" style="color: #d4a847;"></i> 
                            Total: <strong style="color: #1a6e3a;"><?php echo number_format($total_guru_desa); ?></strong>
                        </span>
                        <span style="color: #999; font-size: 0.75rem;">
                            <i class="fas fa-location-dot"></i> <?php echo count($desa_list); ?> Desa
                        </span>
                    </div>
                    <div style="max-height: 350px; overflow-y: auto;">
                        <?php foreach ($desa_list as $desa): ?>
                            <div class="desa-item">
                                <span class="nama">
                                    <i class="fas fa-circle" style="color: <?php echo $desa['total_guru'] > 0 ? '#1a6e3a' : '#ddd'; ?>; font-size: 0.4rem; vertical-align: middle; margin-right: 6px;"></i>
                                    <?php echo htmlspecialchars($desa['nama']); ?>
                                </span>
                                <span class="jumlah <?php echo $desa['total_guru'] == 0 ? 'zero' : ''; ?>">
                                    <?php echo number_format($desa['total_guru']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="margin-top: 12px;">
                    <a href="detail_statistik.php?kabupaten_id=<?php echo $kabupaten_id; ?>" 
                       style="color: #1a6e3a; text-decoration: none; font-weight: 500; font-size: 0.85rem; padding: 6px 14px; background: #e8f5e9; border-radius: 6px; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Kecamatan
                    </a>
                </div>
                
            <?php elseif ($kabupaten_id > 0 && !empty($kecamatan_list)): ?>
                <!-- ===== DAFTAR KECAMATAN ===== -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <h3 style="font-size: 1rem; color: #1a1a2e; margin: 0;">
                        <i class="fas fa-map" style="color: #d4a847;"></i> 
                        Kecamatan di <?php echo htmlspecialchars($selected_kabupaten['nama'] ?? ''); ?>
                    </h3>
                    <span style="background: #e8f5e9; color: #1a6e3a; padding: 2px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">
                        <i class="fas fa-users"></i> <?php echo number_format($selected_kabupaten['total_guru'] ?? 0); ?>
                    </span>
                </div>
                
                <div class="scroll-area" style="max-height: 450px;">
                    <?php 
                    $max_kec_guru = 0;
                    foreach ($kecamatan_list as $kec) {
                        if ($kec['total_guru'] > $max_kec_guru) $max_kec_guru = $kec['total_guru'];
                    }
                    $max_kec_guru = max($max_kec_guru, 1);
                    ?>
                    
                    <?php foreach ($kecamatan_list as $kec): ?>
                        <?php 
                        $persentase = round(($kec['total_guru'] / $max_kec_guru) * 100);
                        $is_active = ($kecamatan_id == $kec['id']);
                        ?>
                        <a href="detail_statistik.php?kabupaten_id=<?php echo $kabupaten_id; ?>&kecamatan_id=<?php echo $kec['id']; ?>" 
                           style="text-decoration: none; display: block; margin-bottom: 6px;">
                            <div class="kecamatan-card <?php echo $is_active ? 'active' : ''; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                                    <div>
                                        <span class="nama">
                                            <?php if ($is_active): ?>
                                                <i class="fas fa-chevron-right" style="color: #1a6e3a; font-size: 0.6rem; margin-right: 5px;"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($kec['nama']); ?>
                                            <span class="badge-active">
                                                <i class="fas fa-check-circle"></i> Dipilih
                                            </span>
                                        </span>
                                        <span style="font-size: 0.65rem; color: #999; margin-left: 4px;">
                                            <i class="fas fa-location-dot"></i> <?php echo number_format($kec['total_desa'] ?? 0); ?> desa
                                        </span>
                                    </div>
                                    <span class="jumlah"><?php echo number_format($kec['total_guru']); ?></span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="fill" style="width: <?php echo $persentase; ?>%;"></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 12px; text-align: center; padding: 10px; background: #f0faf3; border-radius: 8px; border: 1px solid #c8e6c9;">
                    <span style="color: #1a6e3a; font-size: 0.8rem;">
                        <i class="fas fa-city"></i> <strong><?php echo htmlspecialchars($selected_kabupaten['nama'] ?? ''); ?></strong>
                        <span style="margin: 0 6px; color: #ccc;">|</span>
                        <i class="fas fa-users"></i> <strong><?php echo number_format($selected_kabupaten['total_guru'] ?? 0); ?></strong> Guru
                        <span style="margin: 0 6px; color: #ccc;">|</span>
                        <i class="fas fa-map"></i> <?php echo number_format($selected_kabupaten['total_kecamatan'] ?? 0); ?> Kec
                    </span>
                </div>
                
            <?php else: ?>
                <!-- ===== INSTRUKSI PILIH KABUPATEN ===== -->
                <div class="empty-state" style="height: 100%; min-height: 250px;">
                    <i class="fas fa-hand-pointer"></i>
                    <h4>Pilih Kabupaten/Kota</h4>
                    <p>Klik salah satu kabupaten di sebelah kiri untuk melihat detail kecamatan dan guru ngaji di wilayah tersebut.</p>
                    <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                        <span style="padding: 4px 12px; background: #e8f5e9; color: #1a6e3a; border-radius: 20px; font-size: 0.7rem;">
                            <i class="fas fa-users"></i> <?php echo number_format($stat_global['total_guru'] ?? 0); ?> Guru
                        </span>
                        <span style="padding: 4px 12px; background: #fff3e0; color: #d4a847; border-radius: 20px; font-size: 0.7rem;">
                            <i class="fas fa-city"></i> <?php echo number_format($stat_global['total_kabupaten'] ?? 0); ?> Kab
                        </span>
                        <span style="padding: 4px 12px; background: #e3f2fd; color: #3498db; border-radius: 20px; font-size: 0.7rem;">
                            <i class="fas fa-map"></i> <?php echo number_format($stat_global['total_kecamatan'] ?? 0); ?> Kec
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPT UNTUK ANIMASI -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animasi progress bar
    const progressBars = document.querySelectorAll('.progress-bar-custom .fill');
    progressBars.forEach(function(bar) {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function() {
            bar.style.width = width;
        }, 200);
    });
    
    // Scroll ke card aktif di mobile
    const activeCard = document.querySelector('.kabupaten-card.active');
    if (activeCard && window.innerWidth <= 768) {
        setTimeout(function() {
            activeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 400);
    }
});
</script>

<?php include $root_path . '/include/footer.php'; ?>