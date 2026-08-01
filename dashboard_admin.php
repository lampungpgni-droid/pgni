<?php
// admin/dashboard_admin.php
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
// CEK ROLE - HANYA ADMIN YANG BISA AKSES
// ============================================
if ($_SESSION['role'] === 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SESSION['role'] === 'petugas_kecamatan') {
    header('Location: dashboard_petugas.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$title = 'Dashboard Admin';

// ============================================
// AMBIL AKSES WILAYAH USER
// ============================================
$user_id = $_SESSION['user_id'];
$user_wilayah = [
    'akses_semua' => 0,
    'kabupaten' => [],
    'kecamatan' => [],
    'desa' => []
];

$query_wilayah = "SELECT * FROM user_wilayah_akses WHERE user_id = $user_id";
$result_wilayah = mysqli_query($conn, $query_wilayah);
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

// ============================================
// BUILD WHERE CLAUSE UNTUK FILTER WILAYAH
// ============================================
function buildWilayahFilter($user_wilayah, $table_alias = 'g') {
    if ($user_wilayah['akses_semua'] == 1) {
        return '';
    }
    
    $conditions = [];
    
    // Filter berdasarkan kecamatan
    if (!empty($user_wilayah['kecamatan'])) {
        $kec_ids = implode(',', $user_wilayah['kecamatan']);
        $conditions[] = "$table_alias.kecamatan_id IN ($kec_ids)";
    }
    
    // Filter berdasarkan kabupaten
    if (!empty($user_wilayah['kabupaten'])) {
        $kab_ids = implode(',', $user_wilayah['kabupaten']);
        $conditions[] = "$table_alias.kabupaten_id IN ($kab_ids)";
    }
    
    // Filter berdasarkan desa
    if (!empty($user_wilayah['desa'])) {
        $desa_ids = implode(',', $user_wilayah['desa']);
        $conditions[] = "$table_alias.desa_id IN ($desa_ids)";
    }
    
    if (!empty($conditions)) {
        return 'WHERE (' . implode(' OR ', $conditions) . ')';
    }
    
    return 'WHERE 1=0'; // Tidak ada akses
}

// ============================================
// FUNGSI UNTUK MENDAPATKAN FILTER CLEAN (tanpa WHERE)
// ============================================
function getFilterCondition($user_wilayah, $table_alias = 'g') {
    $filter = buildWilayahFilter($user_wilayah, $table_alias);
    if (empty($filter)) {
        return '';
    }
    // Hapus kata "WHERE " dari awal string
    return str_replace('WHERE ', '', $filter);
}

$wilayah_filter = buildWilayahFilter($user_wilayah);
$filter_condition = getFilterCondition($user_wilayah);

// ============================================
// AMBIL DATA KABUPATEN YANG DIAKSES
// ============================================
$kabupaten_terakses = [];
if ($user_wilayah['akses_semua'] == 1) {
    $query_kab = "SELECT * FROM kabupaten ORDER BY nama";
    $result_kab = mysqli_query($conn, $query_kab);
    while ($row = mysqli_fetch_assoc($result_kab)) {
        $kabupaten_terakses[] = $row;
    }
} else if (!empty($user_wilayah['kabupaten'])) {
    $kab_ids = implode(',', $user_wilayah['kabupaten']);
    $query_kab = "SELECT * FROM kabupaten WHERE id IN ($kab_ids) ORDER BY nama";
    $result_kab = mysqli_query($conn, $query_kab);
    while ($row = mysqli_fetch_assoc($result_kab)) {
        $kabupaten_terakses[] = $row;
    }
} else if (!empty($user_wilayah['kecamatan'])) {
    $kec_ids = implode(',', $user_wilayah['kecamatan']);
    $query_kab = "SELECT DISTINCT kb.* FROM kabupaten kb 
                  JOIN kecamatan k ON k.kabupaten_id = kb.id 
                  WHERE k.id IN ($kec_ids) ORDER BY kb.nama";
    $result_kab = mysqli_query($conn, $query_kab);
    while ($row = mysqli_fetch_assoc($result_kab)) {
        $kabupaten_terakses[] = $row;
    }
} else if (!empty($user_wilayah['desa'])) {
    $desa_ids = implode(',', $user_wilayah['desa']);
    $query_kab = "SELECT DISTINCT kb.* FROM kabupaten kb 
                  JOIN kecamatan k ON k.kabupaten_id = kb.id 
                  JOIN desa d ON d.kecamatan_id = k.id 
                  WHERE d.id IN ($desa_ids) ORDER BY kb.nama";
    $result_kab = mysqli_query($conn, $query_kab);
    while ($row = mysqli_fetch_assoc($result_kab)) {
        $kabupaten_terakses[] = $row;
    }
}

// ============================================
// STATISTIK
// ============================================

// Total Guru Ngaji
if (empty($filter_condition)) {
    $query_guru = "SELECT COUNT(*) as total FROM guru_ngaji g";
} else {
    $query_guru = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_condition";
}
$result_guru = mysqli_query($conn, $query_guru);
$total_guru = $result_guru ? mysqli_fetch_assoc($result_guru)['total'] : 0;

// Guru Pending
if (empty($filter_condition)) {
    $query_guru_pending = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE g.status_verifikasi = 'pending'";
} else {
    $query_guru_pending = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_condition AND g.status_verifikasi = 'pending'";
}
$result_pending = mysqli_query($conn, $query_guru_pending);
$total_pending = $result_pending ? mysqli_fetch_assoc($result_pending)['total'] : 0;

// Guru Aktif
if (empty($filter_condition)) {
    $query_guru_aktif = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE g.status = 'aktif'";
} else {
    $query_guru_aktif = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_condition AND g.status = 'aktif'";
}
$result_aktif = mysqli_query($conn, $query_guru_aktif);
$total_aktif = $result_aktif ? mysqli_fetch_assoc($result_aktif)['total'] : 0;

// Guru Diverifikasi
if (empty($filter_condition)) {
    $query_guru_verif = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE g.status_verifikasi = 'disetujui'";
} else {
    $query_guru_verif = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE $filter_condition AND g.status_verifikasi = 'disetujui'";
}
$result_verif = mysqli_query($conn, $query_guru_verif);
$total_verif = $result_verif ? mysqli_fetch_assoc($result_verif)['total'] : 0;

// Total Berita (tanpa filter wilayah - berita bersifat global)
$query_berita = "SELECT COUNT(*) as total FROM berita WHERE status = 'publish'";
$result_berita = mysqli_query($conn, $query_berita);
$total_berita = $result_berita ? mysqli_fetch_assoc($result_berita)['total'] : 0;

// Total Pengurus (tanpa filter wilayah - pengurus bersifat global)
$query_pengurus = "SELECT COUNT(*) as total FROM pengurus WHERE status = 'aktif'";
$result_pengurus = mysqli_query($conn, $query_pengurus);
$total_pengurus = $result_pengurus ? mysqli_fetch_assoc($result_pengurus)['total'] : 0;

// ============================================
// DATA TERBARU
// ============================================

// Berita terbaru (global)
$query_berita_terbaru = "SELECT * FROM berita WHERE status = 'publish' ORDER BY created_at DESC LIMIT 5";
$berita_terbaru = mysqli_query($conn, $query_berita_terbaru);

// Guru terbaru (dengan filter wilayah)
if (empty($filter_condition)) {
    $query_guru_terbaru = "SELECT * FROM guru_ngaji g ORDER BY g.created_at DESC LIMIT 5";
} else {
    $query_guru_terbaru = "SELECT * FROM guru_ngaji g WHERE $filter_condition ORDER BY g.created_at DESC LIMIT 5";
}
$guru_terbaru = mysqli_query($conn, $query_guru_terbaru);

// ============================================
// DATA PER KABUPATEN
// ============================================
$stat_per_kabupaten = [];
foreach ($kabupaten_terakses as $kab) {
    $kab_id = $kab['id'];
    
    // Hitung guru di kabupaten ini
    $query = "SELECT COUNT(*) as total FROM guru_ngaji g WHERE g.kabupaten_id = $kab_id";
    $result = mysqli_query($conn, $query);
    $total = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Hitung kecamatan di kabupaten ini
    $query_kec = "SELECT COUNT(*) as total FROM kecamatan WHERE kabupaten_id = $kab_id";
    $result_kec = mysqli_query($conn, $query_kec);
    $total_kec = mysqli_fetch_assoc($result_kec)['total'] ?? 0;
    
    // Hitung desa di kabupaten ini
    $query_desa = "SELECT COUNT(*) as total FROM desa d 
                   JOIN kecamatan k ON d.kecamatan_id = k.id 
                   WHERE k.kabupaten_id = $kab_id";
    $result_desa = mysqli_query($conn, $query_desa);
    $total_desa = mysqli_fetch_assoc($result_desa)['total'] ?? 0;
    
    $stat_per_kabupaten[] = [
        'id' => $kab_id,
        'nama' => $kab['nama'],
        'total_guru' => $total,
        'total_kecamatan' => $total_kec,
        'total_desa' => $total_desa
    ];
}

include 'include/admin_header.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-text">
            <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
            <p>Selamat datang di dashboard manajemen PGNI Lampung. Kelola data dengan mudah dan efisien.</p>
        </div>
        <div class="welcome-date">
            <i class="fas fa-calendar-alt"></i>
            <?php echo tanggal_indonesia(date('Y-m-d')); ?>
        </div>
    </div>
    
    <!-- Informasi Akses Wilayah -->
    <div class="wilayah-info-card">
        <div class="wilayah-info-header">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Wilayah yang Anda Akses</h3>
        </div>
        <div class="wilayah-info-body">
            <?php if ($user_wilayah['akses_semua'] == 1): ?>
                <span class="badge-akses-semua">
                    <i class="fas fa-globe"></i> Akses Seluruh Wilayah
                </span>
            <?php elseif (!empty($kabupaten_terakses)): ?>
                <div class="wilayah-badges">
                    <?php foreach ($kabupaten_terakses as $kab): ?>
                        <span class="badge-kabupaten">
                            <i class="fas fa-city"></i> <?php echo htmlspecialchars($kab['nama']); ?>
                            <?php if (!empty($user_wilayah['kecamatan'])): ?>
                                <?php 
                                $kec_count = 0;
                                foreach ($user_wilayah['kecamatan'] as $kec_id) {
                                    $query_check = "SELECT id FROM kecamatan WHERE id = $kec_id AND kabupaten_id = " . $kab['id'];
                                    $check = mysqli_query($conn, $query_check);
                                    if (mysqli_num_rows($check) > 0) $kec_count++;
                                }
                                if ($kec_count > 0): ?>
                                    <span class="sub-badge"><?php echo $kec_count; ?> kecamatan</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($user_wilayah['desa'])): ?>
                                <?php 
                                $desa_count = 0;
                                foreach ($user_wilayah['desa'] as $desa_id) {
                                    $query_check = "SELECT d.id FROM desa d 
                                                   JOIN kecamatan k ON d.kecamatan_id = k.id 
                                                   WHERE d.id = $desa_id AND k.kabupaten_id = " . $kab['id'];
                                    $check = mysqli_query($conn, $query_check);
                                    if (mysqli_num_rows($check) > 0) $desa_count++;
                                }
                                if ($desa_count > 0): ?>
                                    <span class="sub-badge"><?php echo $desa_count; ?> desa</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <span class="badge-akses-terbatas">
                    <i class="fas fa-info-circle"></i> Tidak ada akses wilayah yang diberikan
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="border-right: 4px solid #1a6e3a;">
            <div class="stat-icon" style="background: rgba(26, 110, 58, 0.1); color: #1a6e3a;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_guru); ?></h3>
                <p>Total Guru Ngaji</p>
                <?php if (!$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #2ecc71;">
            <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_aktif); ?></h3>
                <p>Guru Aktif</p>
                <?php if (!$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #f39c12;">
            <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_pending); ?></h3>
                <p>Menunggu Verifikasi</p>
                <?php if (!$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #3498db;">
            <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_verif); ?></h3>
                <p>Sudah Verifikasi</p>
                <?php if (!$user_wilayah['akses_semua']): ?>
                    <small class="stat-note">(wilayah akses)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #e74c3c;">
            <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_berita); ?></h3>
                <p>Total Berita</p>
                <small class="stat-note">(global)</small>
            </div>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #9b59b6;">
            <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_pengurus); ?></h3>
                <p>Total Pengurus</p>
                <small class="stat-note">(global)</small>
            </div>
        </div>
    </div>
    
    <!-- Statistik Per Kabupaten -->
    <?php if (!empty($stat_per_kabupaten) && count($stat_per_kabupaten) >= 1): ?>
    <div class="stat-kabupaten-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-bar"></i> Statistik Per Kabupaten</h3>
        </div>
        <div class="stat-kabupaten-grid">
            <?php foreach ($stat_per_kabupaten as $kab): ?>
                <div class="stat-kabupaten-card">
                    <div class="kab-name"><?php echo htmlspecialchars($kab['nama']); ?></div>
                    <div class="kab-stats">
                        <div class="kab-stat-item">
                            <span class="kab-stat-value"><?php echo number_format($kab['total_guru']); ?></span>
                            <span class="kab-stat-label">Guru</span>
                        </div>
                        <div class="kab-stat-item">
                            <span class="kab-stat-value"><?php echo number_format($kab['total_kecamatan']); ?></span>
                            <span class="kab-stat-label">Kecamatan</span>
                        </div>
                        <div class="kab-stat-item">
                            <span class="kab-stat-value"><?php echo number_format($kab['total_desa']); ?></span>
                            <span class="kab-stat-label">Desa</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Data -->
    <div class="recent-grid">
        <!-- Berita Terbaru -->
        <div class="recent-card">
            <div class="card-header">
                <h3><i class="fas fa-newspaper"></i> Berita Terbaru</h3>
                <a href="berita.php" class="btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if ($berita_terbaru && mysqli_num_rows($berita_terbaru) > 0): ?>
                    <ul class="recent-list">
                        <?php while ($berita = mysqli_fetch_assoc($berita_terbaru)): ?>
                            <li>
                                <a href="berita_edit.php?id=<?php echo $berita['id']; ?>">
                                    <span class="recent-title"><?php echo htmlspecialchars($berita['judul'] ?: 'Tanpa Judul'); ?></span>
                                    <span class="recent-date"><?php echo tanggal_indonesia($berita['created_at']); ?></span>
                                    <span class="status-badge <?php echo $berita['status']; ?>"><?php echo $berita['status']; ?></span>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-message">Belum ada berita</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Guru Terbaru -->
        <div class="recent-card">
            <div class="card-header">
                <h3><i class="fas fa-user-graduate"></i> Guru Ngaji Terbaru</h3>
                <a href="guru.php" class="btn-sm">Lihat Semua</a>
                <?php if (!$user_wilayah['akses_semua']): ?>
                    <span class="badge-filter">(filter wilayah)</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($guru_terbaru && mysqli_num_rows($guru_terbaru) > 0): ?>
                    <ul class="recent-list">
                        <?php while ($guru = mysqli_fetch_assoc($guru_terbaru)): ?>
                            <li>
                                <a href="guru_edit.php?id=<?php echo $guru['id']; ?>">
                                    <span class="recent-title"><?php echo htmlspecialchars($guru['nama']); ?></span>
                                    <span class="recent-date"><?php echo htmlspecialchars($guru['tempat_mengajar']); ?></span>
                                    <span class="status-badge <?php echo $guru['status_verifikasi']; ?>"><?php echo $guru['status_verifikasi']; ?></span>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-message">Belum ada guru ngaji di wilayah akses Anda</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-content { padding: 0; }
    
    /* ===== WELCOME SECTION ===== */
    .welcome-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .welcome-text h1 {
        font-size: 1.5rem;
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    .welcome-text p {
        color: #666;
        font-size: 0.95rem;
    }
    .welcome-date {
        color: #666;
        font-size: 0.95rem;
    }
    .welcome-date i {
        color: #d4a847;
        margin-right: 8px;
    }
    
    /* ===== WILAYAH INFO CARD ===== */
    .wilayah-info-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
        border-left: 4px solid #d4a847;
    }
    .wilayah-info-header {
        padding: 15px 25px;
        background: #fafafa;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .wilayah-info-header i {
        color: #d4a847;
        font-size: 1.1rem;
    }
    .wilayah-info-header h3 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
    }
    .wilayah-info-body {
        padding: 15px 25px;
    }
    .wilayah-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .badge-kabupaten {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #e8f5e9;
        color: #1a6e3a;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .badge-kabupaten i {
        font-size: 0.8rem;
    }
    .sub-badge {
        background: rgba(26, 110, 58, 0.15);
        padding: 1px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 400;
        color: #1a6e3a;
    }
    .badge-akses-semua {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #d4edda;
        color: #155724;
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .badge-akses-terbatas {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff3cd;
        color: #856404;
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* ===== STATS GRID ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: #fff;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 2px;
    }
    .stat-info p {
        font-size: 0.85rem;
        color: #666;
        margin: 0;
    }
    .stat-note {
        font-size: 0.65rem;
        color: #999;
        display: block;
        margin-top: 2px;
    }
    
    /* ===== STAT PER KABUPATEN ===== */
    .stat-kabupaten-section {
        margin-bottom: 25px;
    }
    .section-header {
        margin-bottom: 15px;
    }
    .section-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-header h3 i {
        color: #d4a847;
    }
    .stat-kabupaten-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 15px;
    }
    .stat-kabupaten-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-top: 3px solid #1a6e3a;
        transition: all 0.3s ease;
    }
    .stat-kabupaten-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }
    .kab-name {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.95rem;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    .kab-stats {
        display: flex;
        justify-content: space-around;
        gap: 10px;
    }
    .kab-stat-item {
        text-align: center;
    }
    .kab-stat-value {
        display: block;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a6e3a;
    }
    .kab-stat-label {
        font-size: 0.7rem;
        color: #999;
    }
    
    /* ===== RECENT GRID ===== */
    .recent-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    .recent-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 25px;
        border-bottom: 1px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 8px;
    }
    .card-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-header h3 i { color: #d4a847; }
    .badge-filter {
        font-size: 0.65rem;
        color: #999;
        background: #f5f5f5;
        padding: 2px 10px;
        border-radius: 12px;
    }
    .btn-sm {
        padding: 5px 14px;
        background: #f0f0f0;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #666;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .btn-sm:hover {
        background: #1a6e3a;
        color: #fff;
    }
    .card-body { padding: 10px 0; }
    .recent-list { list-style: none; margin: 0; padding: 0; }
    .recent-list li { border-bottom: 1px solid #f5f5f5; }
    .recent-list li:last-child { border-bottom: none; }
    .recent-list li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 25px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }
    .recent-list li a:hover { background: #f8f9fa; }
    .recent-title { flex: 1; font-size: 0.9rem; font-weight: 500; }
    .recent-date { font-size: 0.8rem; color: #999; }
    
    .status-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    .status-badge.publish,
    .status-badge.disetujui,
    .status-badge.aktif {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.draft,
    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.ditolak,
    .status-badge.nonaktif {
        background: #f8d7da;
        color: #721c24;
    }
    
    .empty-message {
        padding: 20px 25px;
        color: #999;
        font-size: 0.9rem;
        text-align: center;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .recent-grid { grid-template-columns: 1fr; }
        .welcome-section { flex-direction: column; text-align: center; }
        .stat-kabupaten-grid { grid-template-columns: 1fr 1fr; }
        .wilayah-badges { justify-content: center; }
    }
    
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .stat-kabupaten-grid { grid-template-columns: 1fr; }
        .recent-list li a { flex-wrap: wrap; }
    }
</style>

<?php include 'include/admin_footer.php'; ?>