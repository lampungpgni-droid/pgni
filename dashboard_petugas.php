<?php
// admin/dashboard_petugas.php - Dashboard Khusus Petugas Kecamatan
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

if ($_SESSION['role'] !== 'petugas_kecamatan') {
    header('Location: dashboard.php');
    exit;
}

$title = 'Dashboard Petugas';

$kecamatan_id = $_SESSION['kecamatan_id'] ?? 0;
$nama_kecamatan = '';
if ($kecamatan_id > 0) {
    $query_kec = "SELECT nama FROM kecamatan WHERE id = $kecamatan_id";
    $result_kec = mysqli_query($conn, $query_kec);
    $kec = mysqli_fetch_assoc($result_kec);
    $nama_kecamatan = $kec['nama'] ?? 'Kecamatan';
}

// Statistik
$query_total = "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id";
$total_guru = mysqli_fetch_assoc(mysqli_query($conn, $query_total))['total'] ?? 0;

$query_aktif = "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id AND status = 'aktif'";
$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, $query_aktif))['total'] ?? 0;

$query_pending = "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id AND status_verifikasi = 'pending'";
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, $query_pending))['total'] ?? 0;

$query_disetujui = "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id AND status_verifikasi = 'disetujui'";
$total_disetujui = mysqli_fetch_assoc(mysqli_query($conn, $query_disetujui))['total'] ?? 0;

// Guru terbaru
$query_guru_terbaru = "SELECT * FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id ORDER BY created_at DESC LIMIT 5";
$guru_terbaru = mysqli_query($conn, $query_guru_terbaru);

include 'include/admin_header.php';
?>

<style>
    .welcome-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .welcome-section h1 {
        font-size: 1.5rem;
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    .welcome-section h1 i {
        color: #d4a847;
        margin-right: 10px;
    }
    .welcome-section p {
        color: #666;
        font-size: 0.95rem;
        margin: 0;
    }
    .welcome-section .badge-kecamatan {
        background: #1a6e3a;
        color: #fff;
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: 500;
        font-size: 0.9rem;
    }
    .welcome-section .badge-kecamatan i {
        margin-right: 8px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
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
    
    .recent-section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 25px 30px;
    }
    .recent-section h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .recent-section h3 i {
        color: #d4a847;
    }
    .recent-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .recent-list li {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .recent-list li:last-child {
        border-bottom: none;
    }
    .recent-list .nama {
        font-weight: 500;
        color: #333;
    }
    .recent-list .detail {
        font-size: 0.85rem;
        color: #999;
    }
    .status-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    .status-badge.disetujui,
    .status-badge.aktif {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.ditolak {
        background: #f8d7da;
        color: #721c24;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .welcome-section {
            flex-direction: column;
            text-align: center;
        }
        .recent-section {
            padding: 20px;
        }
    }
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-content">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div>
            <h1><i class="fas fa-user-tie"></i> Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
            <p>Anda bertugas di wilayah <?php echo htmlspecialchars($nama_kecamatan); ?></p>
        </div>
        <div class="badge-kecamatan">
            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($nama_kecamatan); ?>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="border-right: 4px solid #1a6e3a;">
            <div class="stat-icon" style="background: rgba(26, 110, 58, 0.1); color: #1a6e3a;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_guru); ?></h3>
                <p>Total Guru</p>
            </div>
        </div>
        <div class="stat-card" style="border-right: 4px solid #2ecc71;">
            <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_aktif); ?></h3>
                <p>Guru Aktif</p>
            </div>
        </div>
        <div class="stat-card" style="border-right: 4px solid #f39c12;">
            <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_pending); ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>
        </div>
        <div class="stat-card" style="border-right: 4px solid #3498db;">
            <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($total_disetujui); ?></h3>
                <p>Sudah Verifikasi</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Guru -->
    <div class="recent-section">
        <h3><i class="fas fa-user-graduate"></i> Guru Ngaji Terbaru</h3>
        <?php if ($guru_terbaru && mysqli_num_rows($guru_terbaru) > 0): ?>
            <ul class="recent-list">
                <?php while ($guru = mysqli_fetch_assoc($guru_terbaru)): ?>
                    <li>
                        <span>
                            <span class="nama"><?php echo htmlspecialchars($guru['nama']); ?></span>
                            <span class="detail"> - <?php echo htmlspecialchars($guru['tempat_mengajar']); ?></span>
                        </span>
                        <span>
                            <span class="status-badge <?php echo $guru['status_verifikasi']; ?>">
                                <?php echo $guru['status_verifikasi']; ?>
                            </span>
                            <span style="font-size:0.75rem;color:#999;margin-left:10px;">
                                <?php echo tanggal_indonesia($guru['created_at']); ?>
                            </span>
                        </span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p style="color:#999;text-align:center;padding:20px 0;">Belum ada data guru di kecamatan ini</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'include/admin_footer.php'; ?>