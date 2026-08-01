<?php
// admin/user.php
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

// Cek role - hanya super_admin dan admin yang bisa mengelola user
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header('Location: dashboard.php?error=akses_ditolak');
    exit;
}

$title = 'Manajemen User';

// Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

// Query
$query = "SELECT u.*, k.nama as kecamatan_nama 
          FROM users u 
          LEFT JOIN kecamatan k ON u.kecamatan_id = k.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (u.username LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%' OR u.email LIKE '%$search%')";
}

if (!empty($role_filter)) {
    $query .= " AND u.role = '$role_filter'";
}

$query .= " ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $query);

// Statistik
$query_total = "SELECT COUNT(*) as total FROM users";
$total = mysqli_fetch_assoc(mysqli_query($conn, $query_total))['total'];

$query_super_admin = "SELECT COUNT(*) as total FROM users WHERE role = 'super_admin'";
$total_super_admin = mysqli_fetch_assoc(mysqli_query($conn, $query_super_admin))['total'];

$query_admin = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, $query_admin))['total'];

$query_petugas = "SELECT COUNT(*) as total FROM users WHERE role = 'petugas_kecamatan'";
$total_petugas = mysqli_fetch_assoc(mysqli_query($conn, $query_petugas))['total'];

// Ambil daftar kecamatan
$query_kecamatan = "SELECT id, nama FROM kecamatan ORDER BY nama";
$kecamatan_list = mysqli_query($conn, $query_kecamatan);

include 'include/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-users-cog"></i> Manajemen User</h2>
        <p class="text-muted">Kelola akun user yang memiliki akses ke sistem</p>
    </div>
    <div class="page-header-right">
        <a href="user_tambah.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Tambah User
        </a>
        <a href="permission.php" class="btn btn-warning">
            <i class="fas fa-key"></i> Hak Akses
        </a>
    </div>
</div>

<!-- Statistik Cards -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #3498db;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo number_format($total); ?></h3>
            <p>Total User</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #e74c3c;">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo number_format($total_super_admin); ?></h3>
            <p>Super Admin</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #f39c12;">
            <i class="fas fa-user-cog"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo number_format($total_admin); ?></h3>
            <p>Admin</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon" style="background: #2ecc71;">
            <i class="fas fa-user-tag"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo number_format($total_petugas); ?></h3>
            <p>Petugas Kecamatan</p>
        </div>
    </div>
</div>

<!-- Filter & Search -->
<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <input type="text" name="search" class="form-control" placeholder="Cari username, nama, atau email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group">
            <select name="role" class="form-control">
                <option value="">Semua Role</option>
                <option value="super_admin" <?php echo $role_filter == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="petugas_kecamatan" <?php echo $role_filter == 'petugas_kecamatan' ? 'selected' : ''; ?>>Petugas Kecamatan</option>
            </select>
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="user.php" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Alert Message -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
            switch($_GET['msg']) {
                case 'tambah': echo 'User berhasil ditambahkan!'; break;
                case 'edit': echo 'User berhasil diperbarui!'; break;
                case 'hapus': echo 'User berhasil dihapus!'; break;
                default: echo 'Operasi berhasil!';
            }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php 
            switch($_GET['error']) {
                case 'hapus': echo 'Gagal menghapus user!'; break;
                case 'notfound': echo 'User tidak ditemukan!'; break;
                case 'hapus_self': echo 'Tidak bisa menghapus akun sendiri!'; break;
                default: echo 'Terjadi kesalahan!';
            }
        ?>
    </div>
<?php endif; ?>

<!-- Table -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th class="hide-mobile">Email</th>
                    <th>Role</th>
                    <th class="hide-tablet">Kecamatan</th>
                    <th class="hide-mobile">Tanggal Daftar</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <div class="user-cell">
                                    <span class="user-avatar">
                                        <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                    </span>
                                    <span class="user-username"><?php echo htmlspecialchars($row['username']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td class="hide-mobile"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                            <td>
                                <span class="role-badge <?php echo $row['role']; ?>">
                                    <?php 
                                        switch($row['role']) {
                                            case 'super_admin': echo '👑 Super Admin'; break;
                                            case 'admin': echo '⚙️ Admin'; break;
                                            case 'petugas_kecamatan': echo '📋 Petugas Kecamatan'; break;
                                            default: echo $row['role'];
                                        }
                                    ?>
                                </span>
                            </td>
                            <td class="hide-tablet"><?php echo htmlspecialchars($row['kecamatan_nama'] ?: '-'); ?></td>
                            <td class="hide-mobile"><?php echo tanggal_indonesia($row['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <a href="user_hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-delete" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-sm btn-secondary" title="Tidak bisa hapus sendiri" style="cursor:not-allowed;opacity:0.5;">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p>Belum ada user terdaftar</p>
                                <a href="user_tambah.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Tambah User Pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <div class="table-info">
            Menampilkan <?php echo mysqli_num_rows($result); ?> user
        </div>
        <div class="table-actions">
            <a href="user_tambah.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Tambah User
            </a>
        </div>
    </div>
</div>

<!-- Mobile Card View (untuk layar kecil) -->
<div class="mobile-card-view">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php 
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)): 
        ?>
            <div class="user-card">
                <div class="user-card-header">
                    <div class="user-card-avatar">
                        <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                    </div>
                    <div class="user-card-title">
                        <h4><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                        <span class="user-card-username">@<?php echo htmlspecialchars($row['username']); ?></span>
                    </div>
                    <span class="role-badge <?php echo $row['role']; ?>">
                        <?php 
                            switch($row['role']) {
                                case 'super_admin': echo '👑 Super Admin'; break;
                                case 'admin': echo '⚙️ Admin'; break;
                                case 'petugas_kecamatan': echo '📋 Petugas Kecamatan'; break;
                                default: echo $row['role'];
                            }
                        ?>
                    </span>
                </div>
                <div class="user-card-body">
                    <div class="user-card-row">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></span>
                    </div>
                    <div class="user-card-row">
                        <span class="label">Kecamatan</span>
                        <span class="value"><?php echo htmlspecialchars($row['kecamatan_nama'] ?: '-'); ?></span>
                    </div>
                    <div class="user-card-row">
                        <span class="label">Tanggal Daftar</span>
                        <span class="value"><?php echo tanggal_indonesia($row['created_at']); ?></span>
                    </div>
                </div>
                <div class="user-card-footer">
                    <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                        <a href="user_hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-delete">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    <?php else: ?>
                        <span class="btn btn-sm btn-secondary" style="cursor:not-allowed;opacity:0.5;">
                            <i class="fas fa-lock"></i> Tidak bisa hapus
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3;"></i>
            <p>Belum ada user terdaftar</p>
        </div>
    <?php endif; ?>
</div>

<style>
    /* ============================================
       PAGE HEADER
       ============================================ */
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
    
    /* ============================================
       BUTTONS
       ============================================ */
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
    .btn-warning {
        background: #f39c12;
        color: #fff;
    }
    .btn-warning:hover {
        background: #e67e22;
        color: #fff;
        transform: translateY(-2px);
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c0392b;
        color: #fff;
        transform: translateY(-2px);
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
    .btn-sm {
        padding: 5px 12px;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    
    /* ============================================
       STATS ROW
       ============================================ */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
        transition: all 0.3s ease;
    }
    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
    
    /* ============================================
       FILTER SECTION
       ============================================ */
    .filter-section {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }
    .filter-group {
        flex: 1;
        min-width: 180px;
    }
    .filter-group .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    .filter-group .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
        background: #fff;
    }
    .filter-group .btn {
        min-width: 100px;
        justify-content: center;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    /* ============================================
       TABLE
       ============================================ */
    .table-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        display: block;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    .table thead {
        background: #f8f9fa;
    }
    .table th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        color: #555;
        font-size: 0.85rem;
        border-bottom: 2px solid #e8e8e8;
        white-space: nowrap;
    }
    .table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* User Cell */
    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .user-username {
        font-weight: 600;
        color: #1a1a2e;
    }
    
    /* Role Badge */
    .role-badge {
        display: inline-block;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        white-space: nowrap;
    }
    .role-badge.super_admin {
        background: #fef2f2;
        color: #991b1b;
    }
    .role-badge.admin {
        background: #fef3c7;
        color: #92400e;
    }
    .role-badge.petugas_kecamatan {
        background: #dbeafe;
        color: #1e40af;
    }
    
    /* Button Group */
    .btn-group {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .btn-delete {
        cursor: pointer;
    }
    
    /* Table Footer */
    .table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-top: 1px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 10px;
    }
    .table-info {
        font-size: 0.85rem;
        color: #999;
    }
    .table-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    /* ============================================
       MOBILE CARD VIEW (hidden on desktop)
       ============================================ */
    .mobile-card-view {
        display: none;
        gap: 15px;
        flex-direction: column;
    }
    
    .user-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .user-card-header {
        padding: 15px 18px;
        background: #f8f9fa;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .user-card-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .user-card-title {
        flex: 1;
    }
    .user-card-title h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
    }
    .user-card-username {
        font-size: 0.8rem;
        color: #999;
    }
    .user-card-body {
        padding: 12px 18px;
    }
    .user-card-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid #f5f5f5;
        font-size: 0.85rem;
    }
    .user-card-row:last-child {
        border-bottom: none;
    }
    .user-card-row .label {
        color: #666;
        font-weight: 500;
    }
    .user-card-row .value {
        color: #333;
        text-align: right;
    }
    .user-card-footer {
        padding: 12px 18px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        background: #fafafa;
    }
    .user-card-footer .btn {
        flex: 1;
        justify-content: center;
        min-width: 80px;
    }
    
    /* ============================================
       EMPTY STATE & ALERT
       ============================================ */
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
    .empty-state p {
        margin-bottom: 15px;
        font-size: 1rem;
    }
    .text-center {
        text-align: center;
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
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    /* ============================================
       RESPONSIVE BREAKPOINTS
       ============================================ */
    
    /* Tablet - Sembunyikan kolom Kecamatan */
    @media (max-width: 1024px) {
        .hide-tablet {
            display: none !important;
        }
    }
    
    /* Mobile - Sembunyikan kolom Email & Tanggal, tampilkan card view */
    @media (max-width: 768px) {
        .hide-mobile {
            display: none !important;
        }
        
        /* Sembunyikan tabel, tampilkan card */
        .table-wrapper {
            display: none;
        }
        .mobile-card-view {
            display: flex;
        }
        
        /* Filter responsif */
        .filter-form {
            flex-direction: column;
            gap: 10px;
        }
        .filter-group {
            min-width: 100%;
            width: 100%;
        }
        .filter-actions {
            flex-direction: row;
        }
        .filter-actions .btn {
            flex: 1;
            justify-content: center;
        }
        
        /* Stats responsif */
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .stat-box {
            padding: 12px 15px;
        }
        .stat-box-icon {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }
        .stat-box-info h3 {
            font-size: 1.1rem;
        }
        .stat-box-info p {
            font-size: 0.7rem;
        }
        
        /* Page header responsif */
        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .page-header-right {
            flex-direction: column;
        }
        .page-header-right .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    /* Mobile kecil */
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .stat-box {
            padding: 10px 12px;
        }
        .stat-box-icon {
            width: 30px;
            height: 30px;
            font-size: 0.8rem;
        }
        .stat-box-info h3 {
            font-size: 1rem;
        }
        
        .page-header-left h2 {
            font-size: 1.1rem;
        }
        
        .user-card-footer {
            flex-direction: column;
        }
        .user-card-footer .btn {
            width: 100%;
        }
    }
</style>

<?php include 'include/admin_footer.php'; ?>