<?php
// admin/user_hapus.php
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

// Hanya super_admin yang bisa hapus user
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: dashboard.php?error=akses_ditolak');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: user.php?error=notfound');
    exit;
}

// Cek apakah user yang akan dihapus adalah diri sendiri
if ($id == $_SESSION['user_id']) {
    header('Location: user.php?error=hapus_self');
    exit;
}

// Ambil data user
$query = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: user.php?error=notfound');
    exit;
}

// Proses hapus
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    $delete_query = "DELETE FROM users WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        header('Location: user.php?msg=hapus');
        exit;
    } else {
        $error = 'Gagal menghapus user: ' . mysqli_error($conn);
    }
}

$title = 'Hapus User';
include 'include/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-user-minus"></i> Hapus User</h2>
        <p class="text-muted">Konfirmasi penghapusan user</p>
    </div>
    <div class="page-header-right">
        <a href="user.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="delete-confirmation">
    <div class="delete-card">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Anda yakin ingin menghapus user ini?</h3>
        <p class="delete-warning">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua akses user.</p>
        
        <div class="user-info">
            <table class="info-table">
                <tr>
                    <td><strong>Username</strong></td>
                    <td>: <?php echo htmlspecialchars($user['username']); ?></td>
                </tr>
                <tr>
                    <td><strong>Nama Lengkap</strong></td>
                    <td>: <?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                </tr>
                <tr>
                    <td><strong>Email</strong></td>
                    <td>: <?php echo htmlspecialchars($user['email'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Role</strong></td>
                    <td>: <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="delete-actions">
            <form action="" method="POST">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash"></i> Ya, Hapus User
                </button>
                <a href="user.php" class="btn btn-secondary btn-lg">
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
    
    .delete-confirmation { max-width: 600px; margin: 0 auto; }
    
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
    
    .user-info {
        text-align: left;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 25px;
    }
    
    .info-table {
        width: 100%;
        font-size: 0.9rem;
    }
    .info-table tr {
        border-bottom: 1px solid #f0f0f0;
    }
    .info-table tr:last-child { border-bottom: none; }
    .info-table td {
        padding: 8px 5px;
        vertical-align: middle;
    }
    .info-table td:first-child { width: 40%; color: #555; font-weight: 500; }
    .info-table td:last-child { width: 60%; color: #333; }
    
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
    
    .delete-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .delete-card { padding: 25px 20px; }
        .user-info { padding: 15px; }
        .info-table td { display: block; width: 100% !important; padding: 4px 5px; }
        .info-table td:first-child { padding-top: 8px; }
        .info-table td:last-child { padding-bottom: 8px; }
        .delete-actions { flex-direction: column; }
        .delete-actions .btn { width: 100%; justify-content: center; }
        .page-header { flex-direction: column; align-items: stretch; }
        .page-header-right .btn { flex: 1; justify-content: center; }
    }
</style>

<?php include 'include/admin_footer.php'; ?>