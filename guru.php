<?php
// guru.php - Halaman Daftar Guru Ngaji Frontend
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH - Gunakan __DIR__ untuk mendapatkan folder saat ini
// ============================================
$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Daftar Guru Ngaji - PGNI Lampung';
$per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kabupaten_id = isset($_GET['kabupaten']) ? (int)$_GET['kabupaten'] : 0;
$tempat_mengajar = isset($_GET['tempat']) ? trim($_GET['tempat']) : '';

// ============================================
// QUERY UNTUK MENGHITUNG TOTAL GURU
// ============================================
$count_query = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status = 'aktif' AND status_verifikasi = 'disetujui'";

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $count_query .= " AND (nama LIKE '%$search_escaped%' OR nik LIKE '%$search_escaped%' OR tempat_mengajar LIKE '%$search_escaped%')";
}
if ($kabupaten_id > 0) {
    $count_query .= " AND kabupaten_id = $kabupaten_id";
}
if (!empty($tempat_mengajar)) {
    $tempat_escaped = mysqli_real_escape_string($conn, $tempat_mengajar);
    $count_query .= " AND tempat_mengajar LIKE '%$tempat_escaped%'";
}

$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    $total_data = 0;
} else {
    $total_data = mysqli_fetch_assoc($count_result)['total'];
}
$total_pages = ceil($total_data / $per_page);

// ============================================
// QUERY AMBIL DATA GURU
// ============================================
$query = "SELECT g.*, 
          k.nama as kabupaten_nama, 
          kec.nama as kecamatan_nama, 
          d.nama as desa_nama 
          FROM guru_ngaji g 
          LEFT JOIN kabupaten k ON g.kabupaten_id = k.id 
          LEFT JOIN kecamatan kec ON g.kecamatan_id = kec.id 
          LEFT JOIN desa d ON g.desa_id = d.id 
          WHERE g.status = 'aktif' AND g.status_verifikasi = 'disetujui'";

if (!empty($search)) {
    $query .= " AND (g.nama LIKE '%$search_escaped%' OR g.nik LIKE '%$search_escaped%' OR g.tempat_mengajar LIKE '%$search_escaped%')";
}
if ($kabupaten_id > 0) {
    $query .= " AND g.kabupaten_id = $kabupaten_id";
}
if (!empty($tempat_mengajar)) {
    $query .= " AND g.tempat_mengajar LIKE '%$tempat_escaped%'";
}
$query .= " ORDER BY g.created_at DESC LIMIT $offset, $per_page";

$guru_list = mysqli_query($conn, $query);

// ============================================
// AMBIL DAFTAR KABUPATEN UNTUK FILTER
// ============================================
$kabupaten_query = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $kabupaten_query);

// ============================================
// AMBIL DAFTAR TEMPAT MENGAJAR UNTUK FILTER
// ============================================
$tempat_query = "SELECT DISTINCT tempat_mengajar FROM guru_ngaji WHERE status = 'aktif' AND status_verifikasi = 'disetujui' AND tempat_mengajar != '' ORDER BY tempat_mengajar";
$tempat_list = mysqli_query($conn, $tempat_query);

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- PAGE BANNER -->
<!-- ============================================ -->
<div class="page-banner" style="background: linear-gradient(135deg, #1a6e3a, #2d8f52); padding: 50px 0; color: #fff; text-align: center; direction: ltr;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;">👨‍🏫 Daftar Guru Ngaji</h1>
        <p style="font-size: 1rem; opacity: 0.9;">Daftar guru ngaji yang terverifikasi di Provinsi Lampung</p>
        <p style="font-size: 0.9rem; opacity: 0.7; margin-top: 5px;">
            Total <?php echo number_format($total_data); ?> Guru Ngaji Terdaftar
        </p>
    </div>
</div>

<!-- ============================================ -->
<!-- FILTER SECTION -->
<!-- ============================================ -->
<section class="filter-section" style="padding: 25px 0; background: #f8f9fa; border-bottom: 1px solid #e8e8e8;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <form action="" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; direction: ltr;">
            <!-- Search -->
            <div style="flex: 2; min-width: 200px; text-align: left;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px; text-align: left;">Cari Guru</label>
                <input type="text" name="search" placeholder="Cari nama, NIK, atau tempat mengajar..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       style="width: 100%; padding: 10px 15px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s ease; direction: ltr; text-align: left;">
            </div>
            
            <!-- Kabupaten -->
            <div style="flex: 1; min-width: 150px; text-align: left;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px; text-align: left;">Kabupaten</label>
                <select name="kabupaten" style="width: 100%; padding: 10px 15px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 0.95rem; background: #fff; appearance: none; direction: ltr; text-align: left;">
                    <option value="">Semua Kabupaten</option>
                    <?php while ($kab = mysqli_fetch_assoc($kabupaten_list)): ?>
                        <option value="<?php echo $kab['id']; ?>" <?php echo $kabupaten_id == $kab['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kab['nama']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Tempat Mengajar -->
            <div style="flex: 1; min-width: 150px; text-align: left;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px; text-align: left;">Tempat Mengajar</label>
                <select name="tempat" style="width: 100%; padding: 10px 15px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 0.95rem; background: #fff; appearance: none; direction: ltr; text-align: left;">
                    <option value="">Semua Tempat</option>
                    <?php while ($t = mysqli_fetch_assoc($tempat_list)): ?>
                        <option value="<?php echo htmlspecialchars($t['tempat_mengajar']); ?>" <?php echo $tempat_mengajar == $t['tempat_mengajar'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['tempat_mengajar']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" style="padding: 10px 25px; background: #1a6e3a; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if (!empty($search) || $kabupaten_id > 0 || !empty($tempat_mengajar)): ?>
                    <a href="guru.php" style="padding: 10px 20px; background: #e74c3c; color: #fff; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s ease;">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- GURU LIST SECTION -->
<!-- ============================================ -->
<section class="guru-list-section" style="padding: 40px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Hasil Pencarian -->
        <?php if (!empty($search) || $kabupaten_id > 0 || !empty($tempat_mengajar)): ?>
            <div style="margin-bottom: 20px; color: #666; font-size: 0.95rem; direction: ltr; text-align: left;">
                Menampilkan <strong><?php echo number_format($total_data); ?></strong> guru 
                <?php if (!empty($search)): ?>untuk pencarian "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                <?php if ($kabupaten_id > 0): ?>
                    <?php 
                    $kab_nama = '';
                    $kab_query = "SELECT nama FROM kabupaten WHERE id = $kabupaten_id";
                    $kab_result = mysqli_query($conn, $kab_query);
                    if ($kab_result) {
                        $kab_row = mysqli_fetch_assoc($kab_result);
                        $kab_nama = $kab_row['nama'] ?? '';
                    }
                    ?>
                    di Kabupaten <?php echo $kab_nama; ?>
                <?php endif; ?>
                <?php if (!empty($tempat_mengajar)): ?>di <?php echo htmlspecialchars($tempat_mengajar); ?><?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Grid Guru -->
        <?php if ($guru_list && mysqli_num_rows($guru_list) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; direction: ltr;">
                <?php while ($guru = mysqli_fetch_assoc($guru_list)): ?>
                    <div class="guru-card" style="background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); transition: all 0.3s ease; border-top: 4px solid #1a6e3a; direction: ltr; text-align: left;">
                        <!-- Avatar -->
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; text-align: left;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #1a6e3a, #2d8f52); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; flex-shrink: 0;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div style="flex: 1; min-width: 0; text-align: left;">
                                <h3 style="font-size: 1.05rem; color: #1a1a2e; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: left;">
                                    <?php echo htmlspecialchars($guru['nama']); ?>
                                </h3>
                                <div style="font-size: 0.8rem; color: #999; text-align: left;">
                                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($guru['nik']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informasi -->
                        <div style="display: grid; gap: 8px; font-size: 0.9rem; color: #555; text-align: left;">
                            <?php if (!empty($guru['jenis_profesi'])): ?>
                                <div style="text-align: left;">
                                    <i class="fas fa-briefcase" style="color: #d4a847; width: 20px;"></i>
                                    <?php echo htmlspecialchars($guru['jenis_profesi']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="text-align: left;">
                                <i class="fas fa-school" style="color: #d4a847; width: 20px;"></i>
                                <?php echo htmlspecialchars($guru['tempat_mengajar']); ?>
                                <?php if (!empty($guru['tempat_mengajar_detail'])): ?>
                                    <span style="color: #999; font-size: 0.8rem;">(<?php echo htmlspecialchars($guru['tempat_mengajar_detail']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($guru['kabupaten_nama'])): ?>
                                <div style="text-align: left;">
                                    <i class="fas fa-map-marker-alt" style="color: #d4a847; width: 20px;"></i>
                                    <?php 
                                    $lokasi = $guru['kabupaten_nama'];
                                    if (!empty($guru['kecamatan_nama'])) {
                                        $lokasi .= ', ' . $guru['kecamatan_nama'];
                                    }
                                    if (!empty($guru['desa_nama'])) {
                                        $lokasi .= ', ' . $guru['desa_nama'];
                                    }
                                    echo htmlspecialchars($lokasi);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            
                        </div>
                        
                        <!-- Detail Link -->
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; text-align: left;">
                            <span style="font-size: 0.75rem; color: #999; text-align: left;">
                                <i class="fas fa-calendar-alt"></i> Terdaftar: <?php echo tanggal_indonesia($guru['created_at']); ?>
                            </span>
                            <a href="guru_detail.php?id=<?php echo $guru['id']; ?>" 
                               style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 16px; background: #1a6e3a; color: #fff; border-radius: 6px; text-decoration: none; font-size: 0.85rem; transition: all 0.3s ease;">
                                Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 40px; flex-wrap: wrap; direction: ltr;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $kabupaten_id > 0 ? '&kabupaten=' . $kabupaten_id : ''; ?><?php echo !empty($tempat_mengajar) ? '&tempat=' . urlencode($tempat_mengajar) : ''; ?>" 
                           style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; transition: all 0.3s ease;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . ($kabupaten_id > 0 ? '&kabupaten=' . $kabupaten_id : '') . (!empty($tempat_mengajar) ? '&tempat=' . urlencode($tempat_mengajar) : '') . '" style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; transition: all 0.3s ease;">1</a>';
                        if ($start_page > 2) echo '<span style="padding: 8px 8px; color: #999;">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $kabupaten_id > 0 ? '&kabupaten=' . $kabupaten_id : ''; ?><?php echo !empty($tempat_mengajar) ? '&tempat=' . urlencode($tempat_mengajar) : ''; ?>" 
                           style="padding: 8px 16px; background: <?php echo $i == $page ? '#1a6e3a' : '#f0f0f0'; ?>; color: <?php echo $i == $page ? '#fff' : '#333'; ?>; border-radius: 6px; text-decoration: none; <?php echo $i == $page ? 'font-weight: 600;' : ''; ?> transition: all 0.3s ease;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php 
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span style="padding: 8px 8px; color: #999;">...</span>';
                        echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . ($kabupaten_id > 0 ? '&kabupaten=' . $kabupaten_id : '') . (!empty($tempat_mengajar) ? '&tempat=' . urlencode($tempat_mengajar) : '') . '" style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; transition: all 0.3s ease;">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $kabupaten_id > 0 ? '&kabupaten=' . $kabupaten_id : ''; ?><?php echo !empty($tempat_mengajar) ? '&tempat=' . urlencode($tempat_mengajar) : ''; ?>" 
                           style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; transition: all 0.3s ease;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 12px; direction: ltr;">
                <i class="fas fa-users" style="font-size: 3rem; color: #d4a847; margin-bottom: 15px; display: block;"></i>
                <h3 style="color: #1a1a2e; margin-bottom: 10px;">Belum Ada Guru Ngaji</h3>
                <p style="color: #666; font-size: 0.95rem;">Saat ini belum ada guru ngaji yang terdaftar dan terverifikasi.</p>
                <?php if (!empty($search) || $kabupaten_id > 0 || !empty($tempat_mengajar)): ?>
                    <a href="guru.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: #1a6e3a; color: #fff; border-radius: 8px; text-decoration: none; transition: all 0.3s ease;">
                        <i class="fas fa-times"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    * {
        direction: ltr !important;
    }
    
    body {
        direction: ltr;
        text-align: left;
    }
    
    /* Hover Effects */
    .guru-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .guru-card .btn:hover {
        background: #0e4a26 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
    }
    
    .filter-section input:focus,
    .filter-section select:focus {
        border-color: #1a6e3a !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
    }
    
    .filter-section button:hover {
        background: #0e4a26 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
    }
    
    .filter-section a:hover {
        background: #c0392b !important;
        transform: translateY(-2px);
    }
    
    .pagination a:hover:not(.active) {
        background: #1a6e3a !important;
        color: #fff !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .page-banner {
            padding: 30px 0 !important;
        }
        .page-banner h1 {
            font-size: 1.5rem !important;
        }
        .filter-section form {
            flex-direction: column;
        }
        .filter-section form > div {
            width: 100% !important;
        }
        .filter-section form .btn,
        .filter-section form a {
            width: 100%;
            justify-content: center;
        }
        .guru-list-section .container > div:last-child {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 480px) {
        .page-banner h1 {
            font-size: 1.2rem !important;
        }
        .guru-card {
            padding: 18px !important;
        }
        .guru-card .guru-card-header {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<?php include $root_path . '/include/footer.php'; ?>