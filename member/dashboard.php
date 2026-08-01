<?php
// member/dashboard.php - Dashboard Member
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PERBAIKAN PATH - Include dari root
// ============================================
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$member_id = $_SESSION['member_id'];
$member_nama = $_SESSION['member_nama'];
$member_nik = $_SESSION['member_nik'];

// Ambil data guru dengan join ke tabel kabupaten, kecamatan, desa
$query = "SELECT g.*, 
          k.nama as kabupaten_nama, 
          kec.nama as kecamatan_nama, 
          d.nama as desa_nama
          FROM guru_ngaji g 
          LEFT JOIN kabupaten k ON g.kabupaten_id = k.id
          LEFT JOIN kecamatan kec ON g.kecamatan_id = kec.id 
          LEFT JOIN desa d ON g.desa_id = d.id
          WHERE g.id = $member_id";
$result = mysqli_query($conn, $query);
$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Statistik - Total berita publish
$total_berita = 0;
$query_berita = "SELECT COUNT(*) as total FROM berita WHERE status = 0"; // status 0 = publish
$result_berita = mysqli_query($conn, $query_berita);
if ($result_berita) {
    $total_berita = mysqli_fetch_assoc($result_berita)['total'];
}

// Total guru terverifikasi
$total_guru = 0;
$query_guru = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status_verifikasi = 'disetujui'";
$result_guru = mysqli_query($conn, $query_guru);
if ($result_guru) {
    $total_guru = mysqli_fetch_assoc($result_guru)['total'];
}

// Fungsi untuk mendapatkan foto URL
function getFotoUrlMember($foto_profil, $nama) {
    if (!empty($foto_profil)) {
        $foto_path = dirname(__DIR__) . '/uploads/foto/' . $foto_profil;
        if (file_exists($foto_path)) {
            return '../uploads/foto/' . $foto_profil;
        }
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=1a6e3a&color=fff&size=200';
}
$foto_url = getFotoUrlMember($guru['foto_profil'] ?? '', $guru['nama'] ?? 'Member');

$title = 'Dashboard';
include 'include/member_header.php';
?>

<style>
/* ============================================
   DASHBOARD MODERN STYLE
============================================ */
:root {
    --primary: #1a6e3a;
    --primary-dark: #0e4a26;
    --primary-light: #2d8f52;
    --gold: #d4a847;
    --gold-light: #f0d68a;
    --dark: #1a1a2e;
    --gray: #6b7280;
    --light-gray: #f3f4f6;
    --radius: 16px;
    --shadow: 0 10px 40px rgba(0,0,0,0.08);
    --shadow-hover: 0 20px 60px rgba(26,110,58,0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.member-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px 30px;
}

/* ===== WELCOME CARD ===== */
.welcome-card {
    background: linear-gradient(135deg, #1a6e3a 0%, #0e4a26 50%, #0a3a1e 100%);
    border-radius: var(--radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(26,110,58,0.25);
    border: 1px solid rgba(212,168,71,0.15);
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: -60%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(212,168,71,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.welcome-card::after {
    content: '📚';
    position: absolute;
    right: 30px;
    bottom: -20px;
    font-size: 10rem;
    opacity: 0.04;
    pointer-events: none;
}

.welcome-card .text {
    position: relative;
    z-index: 1;
}

.welcome-card .text h1 {
    font-size: 1.5rem;
    margin: 0 0 4px 0;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    letter-spacing: -0.5px;
}

.welcome-card .text p {
    opacity: 0.8;
    margin: 0;
    font-size: 0.95rem;
    color: rgba(255,255,255,0.85);
    font-weight: 400;
}

.welcome-card .status-badge {
    background: rgba(255,255,255,0.1);
    padding: 10px 24px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    color: #fff;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.stat-card {
    background: #fff;
    padding: 20px 24px;
    border-radius: var(--radius);
    border: 1px solid #f0f2f5;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: var(--transition);
    cursor: default;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.stat-card .stat-icon.green { background: #d4edda; color: #155724; }
.stat-card .stat-icon.blue { background: #cce5ff; color: #004085; }
.stat-card .stat-icon.yellow { background: #fff3cd; color: #856404; }
.stat-card .stat-icon.purple { background: #e8d5f5; color: #6c3483; }

.stat-card .stat-info h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0 0 2px 0;
    line-height: 1.2;
}

.stat-card .stat-info p {
    font-size: 0.75rem;
    color: var(--gray);
    margin: 0;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ===== DASHBOARD GRID ===== */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.dashboard-card {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid #f0f2f5;
    overflow: hidden;
    transition: var(--transition);
}

.dashboard-card:hover {
    box-shadow: var(--shadow-hover);
}

.dashboard-card .card-header {
    padding: 16px 22px;
    background: #fafbfc;
    border-bottom: 1px solid #f0f2f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-card .card-header h3 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.dashboard-card .card-header h3 .icon {
    color: var(--primary);
}

.dashboard-card .card-header .card-action {
    font-size: 0.75rem;
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: var(--transition);
}

.dashboard-card .card-header .card-action:hover {
    color: var(--primary-dark);
    transform: translateX(2px);
}

.dashboard-card .card-body {
    padding: 20px 22px;
}

/* Info Items */
.info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f5f6f8;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .label {
    color: var(--gray);
    font-size: 0.8rem;
    font-weight: 500;
}

.info-item .value {
    color: var(--dark);
    font-weight: 600;
    font-size: 0.85rem;
    text-align: right;
    max-width: 55%;
    word-break: break-word;
}

/* Quick Access Grid */
.quick-access-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.quick-access-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8f9fa;
    border-radius: 12px;
    text-decoration: none;
    color: var(--dark);
    transition: var(--transition);
    border: 1px solid transparent;
}

.quick-access-item:hover {
    background: #fff;
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26,110,58,0.1);
}

.quick-access-item .qa-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.quick-access-item .qa-icon.green-bg { background: #d4edda; color: var(--primary); }
.quick-access-item .qa-icon.blue-bg { background: #cce5ff; color: #004085; }
.quick-access-item .qa-icon.yellow-bg { background: #fff3cd; color: #856404; }
.quick-access-item .qa-icon.purple-bg { background: #e8d5f5; color: #6c3483; }
.quick-access-item .qa-icon.red-bg { background: #f8d7da; color: #721c24; }

.quick-access-item .qa-text h4 {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
}

.quick-access-item .qa-text p {
    margin: 0;
    font-size: 0.7rem;
    color: var(--gray);
}

.quick-access-item .qa-arrow {
    color: #ccc;
    font-size: 0.7rem;
    margin-left: auto;
    transition: var(--transition);
}

.quick-access-item:hover .qa-arrow {
    color: var(--primary);
    transform: translateX(3px);
}

/* Logout Button */
.logout-wrapper {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f0f2f5;
    display: flex;
    justify-content: center;
}

.btn-logout {
    padding: 10px 32px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
    color: #fff;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-card {
        padding: 24px 20px;
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-card .text h1 {
        font-size: 1.2rem;
        justify-content: center;
    }
    
    .welcome-card .status-badge {
        align-self: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 16px 18px;
    }
    
    .quick-access-grid {
        grid-template-columns: 1fr;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
    
    .info-item .value {
        text-align: left;
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .stat-card {
        padding: 14px 16px;
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .stat-card .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-card .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .welcome-card .text h1 {
        font-size: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<!-- ============================================
     CONTENT DASHBOARD
============================================ -->
<div class="member-dashboard">

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="text">
            <h1>
                <span>🎓</span>
                Selamat Datang, <?php echo htmlspecialchars($member_nama); ?>!
            </h1>
            <p>Selamat datang di dashboard member PGNI Lampung. Kelola data dan informasi Anda di sini.</p>
        </div>
        <div class="status-badge">
            <span class="status-dot" style="background: <?php 
                echo $guru['status_verifikasi'] === 'disetujui' ? '#2ecc71' : ($guru['status_verifikasi'] === 'ditolak' ? '#e74c3c' : '#f39c12'); 
            ?>;"></span>
            <?php 
            $status_text = 'Menunggu Verifikasi';
            if ($guru['status_verifikasi'] === 'disetujui') {
                $status_text = 'Terverifikasi ✅';
            } elseif ($guru['status_verifikasi'] === 'ditolak') {
                $status_text = 'Ditolak ❌';
            }
            echo $status_text;
            ?>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">✔️</div>
            <div class="stat-info">
                <h3><?php echo $guru['status_verifikasi'] === 'disetujui' ? '✅' : '⏳'; ?></h3>
                <p>Status Verifikasi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-info">
                <h3><?php echo isset($guru['created_at']) ? date('d/m/Y', strtotime($guru['created_at'])) : '-'; ?></h3>
                <p>Tanggal Daftar</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📰</div>
            <div class="stat-info">
                <h3><?php echo number_format($total_berita); ?></h3>
                <p>Total Berita</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">🪪</div>
            <div class="stat-info">
                <h3><?php echo htmlspecialchars(substr($member_nik, 0, 4) . '****' . substr($member_nik, -4)); ?></h3>
                <p>NIK</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Data Diri -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><span class="icon">👤</span> Data Diri</h3>
                <a href="guru_edit.php?id=<?php echo $member_id; ?>" class="card-action">
                    ✏️ Edit
                </a>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <span class="label">NIK</span>
                    <span class="value"><?php echo htmlspecialchars($guru['nik'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Nama Lengkap</span>
                    <span class="value"><?php echo htmlspecialchars($guru['nama'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Tempat Mengajar</span>
                    <span class="value"><?php echo htmlspecialchars($guru['tempat_mengajar'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Detail Tempat</span>
                    <span class="value"><?php echo htmlspecialchars($guru['tempat_mengajar_detail'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Profesi</span>
                    <span class="value"><?php echo htmlspecialchars($guru['jenis_profesi'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">No. Telepon</span>
                    <span class="value"><?php echo htmlspecialchars($guru['no_telp'] ?? '-'); ?></span>
                </div>
            </div>
        </div>

        <!-- Akses Cepat -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><span class="icon">⚡</span> Akses Cepat</h3>
                <span style="font-size:0.7rem; color:var(--gray);">Menu</span>
            </div>
            <div class="card-body">
                <div class="quick-access-grid">
                    
                    <a href="guru_edit.php?id=<?php echo $member_id; ?>" class="quick-access-item">
                        <div class="qa-icon blue-bg">✏️</div>
                        <div class="qa-text">
                            <h4>Edit Profil</h4>
                            <p>Perbarui data diri</p>
                        </div>
                        <span class="qa-arrow">›</span>
                    </a>
                    
                    </a>
                    <a href="profile.php" class="quick-access-item">
                        <div class="qa-icon purple-bg">👤</div>
                        <div class="qa-text">
                            <h4>Profil</h4>
                            <p>Lihat profil lengkap</p>
                        </div>
                        <span class="qa-arrow">›</span>
                    </a>
                    <!-- Jalur Menu Baru di dashboard.php di dalam <div class="quick-access-grid"> -->
<a href="cetak_kta.php" class="quick-access-item">
    <div class="qa-icon purple-bg">🪪</div>
    <div class="qa-text">
        <h4>Cetak KTA</h4>
        <p>Cetak Kartu Anggota</p>
    </div>
    <span class="qa-arrow">›</span>
</a>

<!-- TAMBAHKAN MENU INI -->
<a href="cetak_sertifikat.php" class="quick-access-item">
    <div class="qa-icon green-bg">📜</div>
    <div class="qa-text">
        <h4>Cetak Sertifikat</h4>
        <p>Unduh Sertifikat Resmi</p>
    </div>
    <span class="qa-arrow">›</span>
</a>
                    
                </div>
                <div class="logout-wrapper">
                    <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/member_footer.php'; ?>