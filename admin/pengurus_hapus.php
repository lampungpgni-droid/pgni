<?php
// admin/pengurus_hapus.php - Hapus Pengurus
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH
// ============================================
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
// AMBIL ID DARI URL
// ============================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: pengurus.php?error=notfound');
    exit;
}

// ============================================
// AMBIL DATA PENGURUS
// ============================================
$query = "SELECT * FROM pengurus WHERE id = $id";
$result = mysqli_query($conn, $query);
$pengurus = mysqli_fetch_assoc($result);

if (!$pengurus) {
    header('Location: pengurus.php?error=notfound');
    exit;
}

// ============================================
// PROSES HAPUS
// ============================================
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Hapus file foto jika ada
    if (!empty($pengurus['foto'])) {
        $paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $pengurus['foto'],
            $_SERVER['DOCUMENT_ROOT'] . '/pgnil/assets/images/pengurus/' . $pengurus['foto'],
            dirname(__DIR__) . '/assets/images/pengurus/' . $pengurus['foto']
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
                break;
            }
        }
    }
    
    // Hapus data dari database
    $delete_query = "DELETE FROM pengurus WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        header('Location: pengurus.php?msg=hapus');
        exit;
    } else {
        $error = 'Gagal menghapus data: ' . mysqli_error($conn);
        header('Location: pengurus.php?error=delete_failed');
        exit;
    }
}

// Jika tombol batal
if (isset($_POST['cancel'])) {
    header('Location: pengurus.php');
    exit;
}

$title = 'Hapus Pengurus';
include $root_path . '/admin/include/admin_header.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .delete-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px 30px;
        box-shadow: 0 4px 30px rgba(0,0,0,0.08);
        text-align: center;
        border: 1px solid #f0f2f5;
    }
    
    .delete-icon {
        font-size: 4rem;
        color: #e74c3c;
        margin-bottom: 15px;
        display: block;
    }
    
    .delete-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    
    .delete-subtitle {
        color: #7f8c8d;
        font-size: 0.95rem;
        margin-bottom: 25px;
    }
    
    .delete-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        text-align: left;
        margin-bottom: 25px;
        border-left: 4px solid #e74c3c;
    }
    
    .delete-info .label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    
    .delete-info .value {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
    }
    
    .delete-info .value-small {
        font-size: 0.85rem;
        color: #7f8c8d;
    }
    
    .delete-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: #fff;
    }
    .btn-danger:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
    }
    
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #7f8c8d;
        transform: translateY(-2px);
    }
    
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .btn-warning:hover {
        background: #d68910;
        transform: translateY(-2px);
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #eef2f5;
        margin: 0 auto 15px;
        display: block;
    }
    
    .avatar-placeholder {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2.5rem;
        color: #fff;
    }
    
    @media (max-width: 480px) {
        .delete-card {
            padding: 25px 15px;
        }
        .delete-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .delete-actions .btn {
            justify-content: center;
        }
        .delete-icon {
            font-size: 3rem;
        }
    }
</style>

<div class="delete-container">
    <div class="delete-card">
        
        <span class="delete-icon">
            <i class="fas fa-trash-alt"></i>
        </span>
        
        <h2 class="delete-title">Hapus Data Pengurus</h2>
        <p class="delete-subtitle">Anda yakin ingin menghapus data pengurus berikut? Tindakan ini <strong>tidak dapat dibatalkan</strong>.</p>
        
        <!-- Informasi Pengurus -->
        <div class="delete-info">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <?php if (!empty($pengurus['foto'])): ?>
                    <?php 
                    $foto_path = '';
                    $paths = [
                        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $pengurus['foto'],
                        $_SERVER['DOCUMENT_ROOT'] . '/pgnil/assets/images/pengurus/' . $pengurus['foto'],
                        dirname(__DIR__) . '/assets/images/pengurus/' . $pengurus['foto']
                    ];
                    foreach ($paths as $path) {
                        if (file_exists($path)) {
                            $foto_path = (strpos($path, '/pgnil/') !== false) ? '/pgnil/assets/images/pengurus/' . $pengurus['foto'] : '/assets/images/pengurus/' . $pengurus['foto'];
                            break;
                        }
                    }
                    ?>
                    <?php if ($foto_path): ?>
                        <img src="<?php echo $foto_path; ?>" alt="<?php echo htmlspecialchars($pengurus['nama']); ?>" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="avatar-placeholder" style="display: none;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <div style="flex: 1; min-width: 150px; text-align: left;">
                    <div class="label">Nama Lengkap</div>
                    <div class="value"><?php echo htmlspecialchars($pengurus['nama']); ?></div>
                    
                    <div class="label" style="margin-top: 8px;">Jabatan</div>
                    <div class="value-small"><?php echo htmlspecialchars($pengurus['jabatan']); ?></div>
                    
                    <?php if (!empty($pengurus['email'])): ?>
                        <div class="label" style="margin-top: 8px;">Email</div>
                        <div class="value-small"><?php echo htmlspecialchars($pengurus['email']); ?></div>
                    <?php endif; ?>
                    
                    <div class="label" style="margin-top: 8px;">Status</div>
                    <div class="value-small">
                        <span class="badge-status <?php echo $pengurus['status']; ?>">
                            <?php echo $pengurus['status'] == 'aktif' ? '✅ Aktif' : '❌ Nonaktif'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tombol Aksi -->
        <div class="delete-actions">
            <form action="" method="POST" style="display: inline;">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Ya, Hapus Data
                </button>
            </form>
            
            <form action="" method="POST" style="display: inline;">
                <input type="hidden" name="cancel" value="yes">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </button>
            </form>
            
            <a href="pengurus.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <p style="margin-top: 20px; font-size: 0.8rem; color: #bdc3c7;">
            <i class="fas fa-info-circle"></i> 
            Data yang dihapus termasuk foto profil (jika ada) akan hilang secara permanen.
        </p>
        
    </div>
</div>

<style>
    .badge-status {
        display: inline-flex;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-status.aktif {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .badge-status.nonaktif {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>