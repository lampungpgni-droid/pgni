<?php
// admin/kegiatan.php - Manajemen Kegiatan
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

$title = 'Manajemen Kegiatan';
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// ============================================
// PASTIKAN TABEL KEGIATAN ADA DENGAN STRUKTUR BENAR
// ============================================
$create_table = "CREATE TABLE IF NOT EXISTS `kegiatan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `judul` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `jenis` VARCHAR(50) NULL DEFAULT 'pelatihan',
    `tanggal_mulai` DATETIME NULL,
    `tanggal_selesai` DATETIME NULL,
    `lokasi` VARCHAR(255) NULL,
    `alamat` TEXT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `radius` INT(11) DEFAULT 100,
    `kuota` INT(11) DEFAULT 0,
    `qr_code` VARCHAR(255) NULL,
    `status` VARCHAR(20) NULL DEFAULT 'draft',
    `created_by` INT(11) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_tanggal` (`tanggal_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $create_table);

// ============================================
// CEK KOLOM YANG ADA DI TABEL KEGIATAN
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM kegiatan");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

// ============================================
// DETEKSI NAMA TABEL USER DAN KOLOM NAMA
// ============================================
$table_user = 'users';
$column_name = 'nama';

// Cek tabel user
$check_user = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
if (mysqli_num_rows($check_user) > 0) {
    $table_user = 'user';
}

// Cek kolom nama di tabel users
$check_columns_user = mysqli_query($conn, "SHOW COLUMNS FROM $table_user");
$columns_user = [];
while ($col = mysqli_fetch_assoc($check_columns_user)) {
    $columns_user[] = $col['Field'];
}

// Deteksi kolom nama yang tersedia
if (in_array('nama', $columns_user)) {
    $column_name = 'nama';
} elseif (in_array('full_name', $columns_user)) {
    $column_name = 'full_name';
} elseif (in_array('username', $columns_user)) {
    $column_name = 'username';
} elseif (in_array('name', $columns_user)) {
    $column_name = 'name';
} else {
    $column_name = 'id';
}

// ============================================
// KONFIGURASI PAGINATION
// ============================================
$per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$jenis_filter = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// ============================================
// QUERY TOTAL
// ============================================
$count_query = "SELECT COUNT(*) as total FROM kegiatan WHERE 1=1";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $count_query .= " AND (judul LIKE '%$search_escaped%' OR lokasi LIKE '%$search_escaped%')";
}
if (!empty($jenis_filter)) {
    $jenis_escaped = mysqli_real_escape_string($conn, $jenis_filter);
    $count_query .= " AND jenis = '$jenis_escaped'";
}
if (!empty($status_filter)) {
    $status_escaped = mysqli_real_escape_string($conn, $status_filter);
    $count_query .= " AND status = '$status_escaped'";
}

$count_result = mysqli_query($conn, $count_query);
$total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_data / $per_page);

// ============================================
// QUERY DATA - DENGAN CEK KOLOM
// ============================================
$select_fields = "k.id, k.judul, k.created_at";

// Tambahkan field opsional jika ada
if (in_array('jenis', $existing_columns)) {
    $select_fields .= ", k.jenis";
}
if (in_array('tanggal_mulai', $existing_columns)) {
    $select_fields .= ", k.tanggal_mulai";
}
if (in_array('tanggal_selesai', $existing_columns)) {
    $select_fields .= ", k.tanggal_selesai";
}
if (in_array('lokasi', $existing_columns)) {
    $select_fields .= ", k.lokasi";
}
if (in_array('kuota', $existing_columns)) {
    $select_fields .= ", k.kuota";
}
if (in_array('status', $existing_columns)) {
    $select_fields .= ", k.status";
}
if (in_array('qr_code', $existing_columns)) {
    $select_fields .= ", k.qr_code";
}
if (in_array('deskripsi', $existing_columns)) {
    $select_fields .= ", k.deskripsi";
}

// Tambahkan created_by_name
$select_fields .= ", u.$column_name as created_by_name";

$query = "SELECT $select_fields 
          FROM kegiatan k 
          LEFT JOIN $table_user u ON k.created_by = u.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (k.judul LIKE '%$search_escaped%' OR k.lokasi LIKE '%$search_escaped%')";
}
if (!empty($jenis_filter)) {
    $query .= " AND k.jenis = '$jenis_escaped'";
}
if (!empty($status_filter)) {
    $query .= " AND k.status = '$status_escaped'";
}
$query .= " ORDER BY k.created_at DESC LIMIT $offset, $per_page";

$kegiatan_list = mysqli_query($conn, $query);

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f2f5;
    }
    .page-header-left h2 {
        font-size: 1.4rem;
        color: #1a1a2e;
        margin: 0 0 3px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-header-left h2 i { color: #d4a847; }
    .page-header-left .text-muted { color: #7f8c8d; font-size: 0.9rem; margin: 0; }
    .page-header-right { display: flex; gap: 10px; flex-wrap: wrap; }

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
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
    }
    .btn-sm {
        padding: 6px 14px;
        font-size: 0.8rem;
    }
    .btn-info {
        background: #17a2b8;
        color: #fff;
    }
    .btn-info:hover {
        background: #138496;
        color: #fff;
    }
    .btn-danger {
        background: #dc3545;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c82333;
        color: #fff;
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #7f8c8d;
        color: #fff;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-status.draft { background: #f8f9fa; color: #6c757d; }
    .badge-status.aktif { background: #d4edda; color: #155724; }
    .badge-status.selesai { background: #cce5ff; color: #004085; }
    .badge-status.batal { background: #f8d7da; color: #721c24; }

    .filter-section {
        background: #fff;
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        border: 1px solid #f0f2f5;
    }
    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .filter-form-group {
        flex-direction: column;
        display: flex;
        flex: 1 1 auto;
        min-width: 150px;
    }
    .filter-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #eef2f5;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.25s ease;
        background: #fff;
        color: #2c3e50;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }

    .table-wrapper {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        border: 1px solid #f0f2f5;
        margin-bottom: 25px;
        width: 100%;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    .table thead tr { background: #fafbfc; border-bottom: 2px solid #edf2f7; }
    .table th {
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    .table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #495057;
    }
    .table tbody tr:hover { background: #fafbfc; }

    .empty-box {
        text-align: center;
        padding: 50px 20px;
    }
    .empty-box i {
        font-size: 3rem;
        color: #d4a847;
        margin-bottom: 12px;
        display: block;
    }

    .pagination-wrapper {
        padding: 15px 20px;
        border-top: 1px solid #f1f3f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        background: #f1f3f5;
        border-radius: 6px;
        text-decoration: none;
        color: #2c3e50;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .pagination-btn.active {
        background: #1a6e3a;
        color: #fff;
    }
    .pagination-btn:hover {
        background: #e2e8f0;
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
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<!-- ============================================ -->
<!-- CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="fas fa-calendar-check"></i> Manajemen Kegiatan</h2>
            <p class="text-muted">Kelola kegiatan pelatihan, rapat, dan event lainnya</p>
        </div>
        <div class="page-header-right">
            <a href="kegiatan_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Kegiatan
            </a>
        </div>
    </div>

    <?php if ($success == 'tambah'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Kegiatan berhasil ditambahkan!</div>
    <?php elseif ($success == 'edit'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Kegiatan berhasil diperbarui!</div>
    <?php elseif ($success == 'hapus'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Kegiatan berhasil dihapus!</div>
    <?php elseif ($error == 'notfound'): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Data kegiatan tidak ditemukan!</div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="filter-section">
        <form action="" method="GET" class="filter-form">
            <div class="filter-form-group" style="flex: 2; min-width: 200px;">
                <label class="filter-label">Cari</label>
                <input type="text" name="search" class="form-control" placeholder="Cari judul atau lokasi..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            </div>
            <div class="filter-form-group" style="min-width: 150px;">
                <label class="filter-label">Jenis</label>
                <select name="jenis" class="form-control">
                    <option value="">Semua</option>
                    <option value="pelatihan" <?php echo ($jenis_filter == 'pelatihan') ? 'selected' : ''; ?>>Pelatihan</option>
                    <option value="rapat" <?php echo ($jenis_filter == 'rapat') ? 'selected' : ''; ?>>Rapat</option>
                    <option value="sosialisasi" <?php echo ($jenis_filter == 'sosialisasi') ? 'selected' : ''; ?>>Sosialisasi</option>
                    <option value="workshop" <?php echo ($jenis_filter == 'workshop') ? 'selected' : ''; ?>>Workshop</option>
                    <option value="lainnya" <?php echo ($jenis_filter == 'lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                </select>
            </div>
            <div class="filter-form-group" style="min-width: 140px;">
                <label class="filter-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">Semua</option>
                    <option value="draft" <?php echo ($status_filter == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="aktif" <?php echo ($status_filter == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="selesai" <?php echo ($status_filter == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                    <option value="batal" <?php echo ($status_filter == 'batal') ? 'selected' : ''; ?>>Batal</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <?php if (!empty($search) || !empty($jenis_filter) || !empty($status_filter)): ?>
                    <a href="kegiatan.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Kuota</th>
                        <th>Status</th>
                        <th style="text-align:center;width:200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kegiatan_list && mysqli_num_rows($kegiatan_list) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($k = mysqli_fetch_assoc($kegiatan_list)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($k['judul'] ?? ''); ?></strong>
                                    <?php if (!empty($k['created_by_name'])): ?>
                                        <br><small class="text-muted">Dibuat oleh: <?php echo htmlspecialchars($k['created_by_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $jenis = $k['jenis'] ?? '';
                                    echo !empty($jenis) ? '<span class="badge badge-info">' . ucfirst($jenis) . '</span>' : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($k['tanggal_mulai'])): ?>
                                        <?php echo date('d/m/Y', strtotime($k['tanggal_mulai'])); ?>
                                        <br><small>
                                            <?php echo date('H:i', strtotime($k['tanggal_mulai'])); ?> - 
                                            <?php echo !empty($k['tanggal_selesai']) ? date('H:i', strtotime($k['tanggal_selesai'])) : 'Selesai'; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($k['lokasi'] ?? '-'); ?></td>
                                <td><?php echo isset($k['kuota']) && $k['kuota'] > 0 ? $k['kuota'] : '∞'; ?></td>
                                <td>
                                    <?php 
                                    $status = $k['status'] ?? 'draft';
                                    $status_label = ucfirst($status);
                                    ?>
                                    <span class="badge-status <?php echo $status; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <a href="absensi.php?kegiatan_id=<?php echo $k['id']; ?>" class="btn btn-info btn-sm" title="Absensi">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <a href="kegiatan_edit.php?id=<?php echo $k['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="kegiatan_hapus.php?id=<?php echo $k['id']; ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Yakin hapus kegiatan ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-box">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>Belum Ada Kegiatan</h3>
                                    <p>Klik "Tambah Kegiatan" untuk membuat kegiatan baru</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <span class="pagination-counter">
                    Menampilkan <?php echo number_format(($page - 1) * $per_page + 1); ?> - 
                    <?php echo number_format(min($page * $per_page, $total_data)); ?> 
                    dari <?php echo number_format($total_data); ?> data
                </span>
                <div class="pagination-nav">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($jenis_filter) ? '&jenis=' . urlencode($jenis_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>