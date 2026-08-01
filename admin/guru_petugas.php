<?php
// admin/guru_petugas.php - Halaman Daftar Guru untuk Petugas Kecamatan
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

// Hanya petugas_kecamatan yang bisa akses
if ($_SESSION['role'] !== 'petugas_kecamatan') {
    header('Location: dashboard.php');
    exit;
}

$title = 'Data Guru - Kecamatan';

// Ambil kecamatan_id dari session
$kecamatan_id = $_SESSION['kecamatan_id'] ?? 0;

if ($kecamatan_id <= 0) {
    // Jika petugas tidak memiliki kecamatan_id, redirect
    header('Location: dashboard.php?error=no_kecamatan');
    exit;
}

// Ambil data guru berdasarkan kecamatan
$query_guru = "SELECT g.*, k.nama as kecamatan_nama 
               FROM guru_ngaji g 
               LEFT JOIN kecamatan k ON g.kecamatan_id = k.id 
               WHERE g.kecamatan_id = $kecamatan_id 
               ORDER BY g.created_at DESC";
$result_guru = mysqli_query($conn, $query_guru);

// Statistik
$total_guru = mysqli_num_rows($result_guru);

// Ambil nama kecamatan
$query_kec = "SELECT nama FROM kecamatan WHERE id = $kecamatan_id";
$result_kec = mysqli_query($conn, $query_kec);
$kecamatan = mysqli_fetch_assoc($result_kec);
$nama_kecamatan = $kecamatan['nama'] ?? 'Kecamatan';

include 'include/admin_header.php';
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .page-header-left h2 {
        font-size: 1.4rem;
        color: #1a1a2e;
        margin-bottom: 3px;
    }
    .page-header-left h2 i {
        color: #d4a847;
        margin-right: 10px;
    }
    .page-header-left .text-muted {
        color: #999;
        font-size: 0.9rem;
        margin: 0;
    }
    .page-header-right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    .stat-box {
        background: #fff;
        padding: 18px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-box-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem;
        flex-shrink: 0;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    }
    .stat-box-info h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 0;
        line-height: 1.2;
    }
    .stat-box-info p {
        font-size: 0.8rem;
        color: #999;
        margin: 0;
    }
    
    .table-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    .table thead {
        background: #f8f9fa;
    }
    .table th {
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #555;
        font-size: 0.8rem;
        border-bottom: 2px solid #e8e8e8;
        white-space: nowrap;
        text-transform: uppercase;
    }
    .table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    .status-badge.aktif {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.nonaktif {
        background: #f8d7da;
        color: #721c24;
    }
    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.disetujui {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.ditolak {
        background: #f8d7da;
        color: #721c24;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    .btn-primary {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-primary:hover {
        background: #0e4a26;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
    }
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .btn-warning:hover {
        background: #e67e22;
        transform: translateY(-2px);
    }
    .btn-info {
        background: #17a2b8;
        color: #fff;
    }
    .btn-info:hover {
        background: #138496;
        transform: translateY(-2px);
    }
    .btn-sm {
        padding: 5px 12px;
        font-size: 0.75rem;
        border-radius: 4px;
    }
    
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }
    .empty-state i {
        display: block;
        margin-bottom: 15px;
        font-size: 3rem;
        opacity: 0.3;
    }
    
    .kecamatan-info {
        background: #e8f5e9;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #1a6e3a;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .kecamatan-info i {
        color: #1a6e3a;
        font-size: 1.2rem;
    }
    .kecamatan-info strong {
        color: #1a6e3a;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        .page-header-right .btn {
            flex: 1;
            justify-content: center;
        }
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .table {
            min-width: 500px;
        }
        .table td, .table th {
            padding: 8px 10px;
            font-size: 0.75rem;
        }
    }
    
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-users"></i> Data Guru Ngaji</h2>
        <p class="text-muted">Data guru ngaji di wilayah <?php echo htmlspecialchars($nama_kecamatan); ?></p>
    </div>
    <div class="page-header-right">
        <a href="guru_tambah.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Tambah Guru
        </a>
    </div>
</div>

<!-- Info Kecamatan -->
<div class="kecamatan-info">
    <i class="fas fa-map-marker-alt"></i>
    <span>Anda berada di wilayah <strong><?php echo htmlspecialchars($nama_kecamatan); ?></strong></span>
    <span style="margin-left: auto; font-size: 0.8rem; color: #666;">
        <i class="fas fa-user"></i> Total: <?php echo $total_guru; ?> Guru
    </span>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-box-icon"><i class="fas fa-users"></i></div>
        <div class="stat-box-info">
            <h3><?php echo $total_guru; ?></h3>
            <p>Total Guru</p>
        </div>
    </div>
    <?php 
    $query_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id AND status = 'aktif'");
    $total_aktif = mysqli_fetch_assoc($query_aktif)['total'] ?? 0;
    ?>
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #2ecc71;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-box-info">
            <h3><?php echo $total_aktif; ?></h3>
            <p>Guru Aktif</p>
        </div>
    </div>
    <?php 
    $query_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru_ngaji WHERE kecamatan_id = $kecamatan_id AND status_verifikasi = 'pending'");
    $total_pending = mysqli_fetch_assoc($query_pending)['total'] ?? 0;
    ?>
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #f39c12;"><i class="fas fa-clock"></i></div>
        <div class="stat-box-info">
            <h3><?php echo $total_pending; ?></h3>
            <p>Menunggu Verifikasi</p>
        </div>
    </div>
</div>

<!-- Table -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Tempat Mengajar</th>
                    <th>Status</th>
                    <th>Verifikasi</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_guru && mysqli_num_rows($result_guru) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result_guru)): ?>
                        <tr>
                            <td style="text-align:center;"><?php echo $no++; ?></td>
                            <td><span style="background:#f1f3f5;padding:4px 10px;border-radius:4px;font-family:monospace;font-size:0.8rem;"><?php echo htmlspecialchars($row['nik']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['tempat_mengajar']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
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
                            <td style="text-align:center;">
                                <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                                    <a href="guru_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="guru_verifikasi.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Verifikasi">
                                        <i class="fas fa-check-double"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <p>Belum ada data guru di kecamatan <?php echo htmlspecialchars($nama_kecamatan); ?></p>
                                <a href="guru_tambah.php" class="btn btn-primary" style="margin-top:10px;">
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

<?php include 'include/admin_footer.php'; ?>