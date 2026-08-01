<?php
// admin/permission.php
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

// Hanya super_admin yang bisa mengelola hak akses
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: dashboard.php?error=akses_ditolak');
    exit;
}

$title = 'Manajemen Hak Akses';

// Proses update permission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Hapus semua permission untuk role tersebut
    $delete_query = "DELETE FROM role_permissions WHERE role = '$role'";
    mysqli_query($conn, $delete_query);
    
    // Insert permission baru
    if (!empty($permissions)) {
        foreach ($permissions as $permission_id) {
            $permission_id = (int)$permission_id;
            $insert_query = "INSERT INTO role_permissions (role, permission_id) VALUES ('$role', $permission_id)";
            mysqli_query($conn, $insert_query);
        }
    }
    
    $success = 'Hak akses untuk role ' . ucfirst(str_replace('_', ' ', $role)) . ' berhasil diperbarui!';
}

// Ambil daftar roles yang tersedia
$roles = ['super_admin', 'admin', 'petugas_kecamatan'];

// Ambil daftar semua permissions
$query_permissions = "SELECT * FROM permissions ORDER BY id";
$permissions_list = mysqli_query($conn, $query_permissions);

// Ambil permission yang sudah dimiliki oleh setiap role
$role_permissions = [];
foreach ($roles as $role) {
    $query = "SELECT permission_id FROM role_permissions WHERE role = '$role'";
    $result = mysqli_query($conn, $query);
    $role_permissions[$role] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $role_permissions[$role][] = $row['permission_id'];
    }
}

// Definisikan label role
$role_labels = [
    'super_admin' => '👑 Super Admin',
    'admin' => '⚙️ Admin',
    'petugas_kecamatan' => '📋 Petugas Kecamatan'
];

// Definisikan deskripsi role
$role_descriptions = [
    'super_admin' => 'Memiliki semua akses, termasuk mengelola user dan hak akses',
    'admin' => 'Mengelola data guru, berita, pengurus, dan yayasan',
    'petugas_kecamatan' => 'Mengelola data guru di kecamatan masing-masing'
];

include 'include/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-key"></i> Manajemen Hak Akses</h2>
        <p class="text-muted">Kelola hak akses untuk setiap role user</p>
    </div>
    <div class="page-header-right">
        <a href="user.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke User
        </a>
        <a href="permission.php?refresh=1" class="btn btn-primary">
            <i class="fas fa-sync"></i> Refresh
        </a>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="permission-wrapper">
    <div class="permission-info">
        <div class="info-card">
            <i class="fas fa-info-circle"></i>
            <div>
                <h4>Informasi Hak Akses</h4>
                <p>Setiap role memiliki hak akses yang berbeda. Centang permission yang ingin diberikan kepada role tersebut.</p>
            </div>
        </div>
    </div>
    
    <?php foreach ($roles as $role): ?>
        <div class="permission-card">
            <div class="permission-header">
                <div class="role-info">
                    <span class="role-icon"><?php 
                        switch($role) {
                            case 'super_admin': echo '👑'; break;
                            case 'admin': echo '⚙️'; break;
                            case 'petugas_kecamatan': echo '📋'; break;
                            default: echo '👤';
                        }
                    ?></span>
                    <div>
                        <h3><?php echo $role_labels[$role]; ?></h3>
                        <p class="role-desc"><?php echo $role_descriptions[$role]; ?></p>
                    </div>
                </div>
                <?php if ($role === 'super_admin'): ?>
                    <span class="badge badge-full">Akses Penuh</span>
                <?php endif; ?>
            </div>
            
            <form action="" method="POST" class="permission-form">
                <input type="hidden" name="role" value="<?php echo $role; ?>">
                <input type="hidden" name="update_permissions" value="1">
                
                <div class="permission-grid">
                    <?php 
                    mysqli_data_seek($permissions_list, 0);
                    while ($perm = mysqli_fetch_assoc($permissions_list)): 
                        $checked = in_array($perm['id'], $role_permissions[$role]);
                    ?>
                        <div class="permission-item <?php echo $checked ? 'checked' : ''; ?>">
                            <label class="permission-checkbox">
                                <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>"
                                    <?php echo $checked ? 'checked' : ''; ?>
                                    <?php echo $role === 'super_admin' ? 'disabled' : ''; ?>>
                                <span class="checkmark"></span>
                                <div class="permission-label">
                                    <span class="perm-name"><?php echo htmlspecialchars($perm['name']); ?></span>
                                    <span class="perm-desc"><?php echo htmlspecialchars($perm['description']); ?></span>
                                </div>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($role !== 'super_admin'): ?>
                    <div class="permission-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Hak Akses
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                <?php else: ?>
                    <div class="permission-actions">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Super Admin memiliki semua hak akses dan tidak dapat diubah.
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endforeach; ?>
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
    .btn-primary {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-primary:hover {
        background: #0e4a26;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
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
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .permission-wrapper {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .permission-info {
        margin-bottom: 25px;
    }
    
    .info-card {
        background: #fff;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid #d4a847;
    }
    .info-card i {
        font-size: 2rem;
        color: #d4a847;
    }
    .info-card h4 {
        font-size: 1rem;
        color: #1a1a2e;
        margin-bottom: 3px;
    }
    .info-card p {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .permission-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .permission-header {
        padding: 18px 25px;
        background: #f8f9fa;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .role-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .role-icon {
        font-size: 2rem;
    }
    
    .role-info h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
    }
    
    .role-desc {
        font-size: 0.85rem;
        color: #666;
        margin: 0;
    }
    
    .badge-full {
        background: #d4edda;
        color: #155724;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .permission-grid {
        padding: 20px 25px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 10px;
    }
    
    .permission-item {
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    
    .permission-item:hover {
        background: #f8f9fa;
    }
    
    .permission-item.checked {
        background: #f0f7f3;
        border-color: #1a6e3a;
    }
    
    .permission-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        width: 100%;
        position: relative;
        padding: 5px 0;
    }
    
    .permission-checkbox input[type="checkbox"] {
        display: none;
    }
    
    .checkmark {
        width: 20px;
        height: 20px;
        border: 2px solid #ccc;
        border-radius: 4px;
        flex-shrink: 0;
        margin-top: 2px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .permission-checkbox input[type="checkbox"]:checked + .checkmark {
        background: #1a6e3a;
        border-color: #1a6e3a;
    }
    
    .permission-checkbox input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
    }
    
    .permission-checkbox input[type="checkbox"]:disabled + .checkmark {
        background: #e8e8e8;
        border-color: #ccc;
        cursor: not-allowed;
    }
    
    .permission-checkbox input[type="checkbox"]:disabled:checked + .checkmark {
        background: #1a6e3a;
        border-color: #1a6e3a;
    }
    
    .permission-checkbox input[type="checkbox"]:disabled:checked + .checkmark::after {
        color: #fff;
    }
    
    .permission-label {
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .perm-name {
        font-size: 0.9rem;
        font-weight: 500;
        color: #333;
    }
    
    .perm-desc {
        font-size: 0.75rem;
        color: #999;
        margin-top: 1px;
    }
    
    .permission-actions {
        padding: 18px 25px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        background: #fafafa;
    }
    
    .permission-actions .btn {
        min-width: 140px;
        justify-content: center;
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
        .permission-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .permission-grid {
            grid-template-columns: 1fr;
            padding: 15px;
        }
        .permission-actions {
            flex-direction: column;
        }
        .permission-actions .btn {
            width: 100%;
            justify-content: center;
        }
        .info-card {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<?php include 'include/admin_footer.php'; ?>