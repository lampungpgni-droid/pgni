<?php
// admin/guru_hapus.php
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: guru.php?error=notfound');
    exit;
}

// Ambil data guru untuk ditampilkan dan hapus file
$query = "SELECT * FROM guru_ngaji WHERE id = $id";
$result = mysqli_query($conn, $query);
$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    header('Location: guru.php?error=notfound');
    exit;
}

// Proses penghapusan
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Hapus file KTP jika ada
    if ($guru['ktp_file'] && !empty($guru['ktp_file'])) {
        $ktp_path = $_SERVER['DOCUMENT_ROOT'] . '/images/ktp/' . $guru['ktp_file'];
        if (file_exists($ktp_path)) {
            unlink($ktp_path);
        }
    }
    
    // Hapus file KK jika ada
    if ($guru['kk_file'] && !empty($guru['kk_file'])) {
        $kk_path = $_SERVER['DOCUMENT_ROOT'] . '/images/kk/' . $guru['kk_file'];
        if (file_exists($kk_path)) {
            unlink($kk_path);
        }
    }
    
    // Hapus data dari database
    $delete_query = "DELETE FROM guru_ngaji WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        header('Location: guru.php?msg=hapus');
        exit;
    } else {
        $error = 'Gagal menghapus data: ' . mysqli_error($conn);
    }
}

$title = 'Hapus Guru Ngaji';
include 'include/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-user-minus"></i> Hapus Guru Ngaji</h2>
        <p class="text-muted">Konfirmasi penghapusan data guru ngaji</p>
    </div>
    <div class="page-header-right">
        <a href="guru.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="delete-confirmation">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Anda yakin ingin menghapus data ini?</h3>
        <p class="delete-warning">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait termasuk file KTP dan KK.</p>
        
        <div class="guru-info">
            <h4>Data yang akan dihapus:</h4>
            <table class="info-table">
                <tr>
                    <td><strong>NIK</strong></td>
                    <td>: <?php echo htmlspecialchars($guru['nik']); ?></td>
                </tr>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>: <?php echo htmlspecialchars($guru['nama']); ?></td>
                </tr>
                <tr>
                    <td><strong>No. Telepon</strong></td>
                    <td>: <?php echo htmlspecialchars($guru['no_telp'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Tempat Mengajar</strong></td>
                    <td>: <?php echo htmlspecialchars($guru['tempat_mengajar']); ?></td>
                </tr>
                <tr>
                    <td><strong>Jenis Profesi</strong></td>
                    <td>: <?php echo htmlspecialchars($guru['jenis_profesi'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>: 
                        <span class="status-badge <?php echo $guru['status']; ?>">
                            <?php echo ucfirst($guru['status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Status Verifikasi</strong></td>
                    <td>: 
                        <span class="status-badge <?php echo $guru['status_verifikasi']; ?>">
                            <?php 
                                switch($guru['status_verifikasi']) {
                                    case 'pending': echo '⏳ Pending'; break;
                                    case 'disetujui': echo '✅ Disetujui'; break;
                                    case 'ditolak': echo '❌ Ditolak'; break;
                                    default: echo $guru['status_verifikasi'];
                                }
                            ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>File KTP</strong></td>
                    <td>: <?php echo $guru['ktp_file'] ? '<i class="fas fa-check-circle" style="color:#2ecc71;"></i> Ada' : '<i class="fas fa-times-circle" style="color:#e74c3c;"></i> Tidak ada'; ?></td>
                </tr>
                <tr>
                    <td><strong>File KK</strong></td>
                    <td>: <?php echo $guru['kk_file'] ? '<i class="fas fa-check-circle" style="color:#2ecc71;"></i> Ada' : '<i class="fas fa-times-circle" style="color:#e74c3c;"></i> Tidak ada'; ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Daftar</strong></td>
                    <td>: <?php echo tanggal_indonesia($guru['created_at']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="delete-actions">
            <form action="" method="POST">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash"></i> Ya, Hapus Data
                </button>
                <a href="guru.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Batal
                </a>
            </form>
        </div>
    </div>
</div>

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
        color: #e74c3c;
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
    
    .delete-confirmation {
        max-width: 700px;
        margin: 0 auto;
    }
    
    .delete-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.06);
        text-align: center;
        border-top: 4px solid #e74c3c;
    }
    
    .delete-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fef2f2;
        color: #e74c3c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 0 auto 20px;
    }
    
    .delete-card h3 {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin-bottom: 10px;
    }
    
    .delete-warning {
        color: #e74c3c;
        font-size: 0.95rem;
        margin-bottom: 25px;
        background: #fef2f2;
        padding: 10px 15px;
        border-radius: 8px;
        border: 1px solid #fecaca;
    }
    
    .guru-info {
        text-align: left;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 25px;
    }
    
    .guru-info h4 {
        font-size: 0.95rem;
        color: #1a1a2e;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e8e8e8;
    }
    
    .info-table {
        width: 100%;
        font-size: 0.9rem;
    }
    
    .info-table tr {
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-table tr:last-child {
        border-bottom: none;
    }
    
    .info-table td {
        padding: 8px 5px;
        vertical-align: middle;
    }
    
    .info-table td:first-child {
        width: 40%;
        color: #555;
        font-weight: 500;
    }
    
    .info-table td:last-child {
        width: 60%;
        color: #333;
    }
    
    .status-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
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
    
    .delete-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c0392b;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
    }
    
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #7f8c8d;
        color: #fff;
        transform: translateY(-2px);
    }
    
    .btn-lg {
        padding: 12px 32px;
        font-size: 1rem;
        border-radius: 10px;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    @media (max-width: 768px) {
        .delete-card {
            padding: 25px 20px;
        }
        .guru-info {
            padding: 15px;
        }
        .info-table td {
            display: block;
            width: 100% !important;
            padding: 4px 5px;
        }
        .info-table td:first-child {
            padding-top: 8px;
        }
        .info-table td:last-child {
            padding-bottom: 8px;
        }
        .delete-actions {
            flex-direction: column;
        }
        .delete-actions .btn {
            width: 100%;
            justify-content: center;
        }
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        .page-header-right .btn {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<?php include 'include/admin_footer.php'; ?>