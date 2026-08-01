<?php
// admin/wilayah.php - Halaman Manajemen Wilayah
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

$title = 'Manajemen Wilayah';
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// ============================================
// CEK DAN PERBAIKI TABEL WILAYAH
// ============================================

// Fungsi untuk cek dan tambah kolom
function add_column_if_not_exists($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $query = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        return mysqli_query($conn, $query);
    }
    return true;
}

// 1. Cek tabel kabupaten
$check_kab = mysqli_query($conn, "SHOW TABLES LIKE 'kabupaten'");
if (mysqli_num_rows($check_kab) == 0) {
    $create_kab = "CREATE TABLE IF NOT EXISTS `kabupaten` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nama` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `nama` (`nama`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_kab);
}

// Tambahkan kolom yang hilang ke kabupaten
add_column_if_not_exists($conn, 'kabupaten', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'kabupaten', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// 2. Cek tabel kecamatan
$check_kec = mysqli_query($conn, "SHOW TABLES LIKE 'kecamatan'");
if (mysqli_num_rows($check_kec) == 0) {
    $create_kec = "CREATE TABLE IF NOT EXISTS `kecamatan` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `kabupaten_id` INT(11) NOT NULL,
        `nama` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `kabupaten_id` (`kabupaten_id`),
        UNIQUE KEY `nama_kabupaten` (`kabupaten_id`, `nama`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_kec);
}

add_column_if_not_exists($conn, 'kecamatan', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'kecamatan', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// 3. Cek tabel desa
$check_desa = mysqli_query($conn, "SHOW TABLES LIKE 'desa'");
if (mysqli_num_rows($check_desa) == 0) {
    $create_desa = "CREATE TABLE IF NOT EXISTS `desa` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `kecamatan_id` INT(11) NOT NULL,
        `nama` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `kecamatan_id` (`kecamatan_id`),
        UNIQUE KEY `nama_kecamatan` (`kecamatan_id`, `nama`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_desa);
}

add_column_if_not_exists($conn, 'desa', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'desa', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// ============================================
// CEK KOLOM YANG ADA UNTUK QUERY
// ============================================
function get_existing_columns($conn, $table) {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

$kab_columns = get_existing_columns($conn, 'kabupaten');
$kec_columns = get_existing_columns($conn, 'kecamatan');
$desa_columns = get_existing_columns($conn, 'desa');

$has_kode_kab = in_array('kode', $kab_columns);
$has_created_kab = in_array('created_at', $kab_columns);
$has_kode_kec = in_array('kode', $kec_columns);
$has_created_kec = in_array('created_at', $kec_columns);
$has_kode_desa = in_array('kode', $desa_columns);
$has_created_desa = in_array('created_at', $desa_columns);

// ============================================
// INSERT DATA DASAR KABUPATEN LAMPUNG
// ============================================
$check_data = mysqli_query($conn, "SELECT COUNT(*) as total FROM kabupaten");
$total_kab = 0;
if ($check_data) {
    $row = mysqli_fetch_assoc($check_data);
    $total_kab = $row['total'] ?? 0;
}

if ($total_kab == 0) {
    $kabupaten_data = [
        'Lampung Barat',
        'Lampung Selatan',
        'Lampung Tengah',
        'Lampung Timur',
        'Lampung Utara',
        'Bandar Lampung',
        'Metro',
        'Pesawaran',
        'Pringsewu',
        'Mesuji',
        'Tulang Bawang',
        'Tanggamus',
        'Way Kanan',
        'Tulang Bawang Barat'
    ];
    
    foreach ($kabupaten_data as $nama) {
        $nama_escaped = mysqli_real_escape_string($conn, $nama);
        mysqli_query($conn, "INSERT INTO kabupaten (nama) VALUES ('$nama_escaped')");
    }
}

// ============================================
// KONFIGURASI PAGINATION
// ============================================
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$level = isset($_GET['level']) ? $_GET['level'] : 'kabupaten';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// ============================================
// QUERY AMBIL DATA SESUAI LEVEL
// ============================================
$total_data = 0;
$data_list = null;
$total_pages = 1;

if ($level == 'kabupaten') {
    $count_query = "SELECT COUNT(*) as total FROM kabupaten WHERE 1=1";
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $count_query .= " AND nama LIKE '%$search_escaped%'";
    }
    $count_result = mysqli_query($conn, $count_query);
    $total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
    $total_pages = max(1, ceil($total_data / $per_page));
    
    // Build query dinamis
    $select_fields = "id, nama";
    if ($has_kode_kab) $select_fields .= ", kode";
    if ($has_created_kab) $select_fields .= ", created_at";
    
    $query = "SELECT $select_fields FROM kabupaten";
    if (!empty($search)) {
        $query .= " WHERE nama LIKE '%$search_escaped%'";
    }
    $query .= " ORDER BY nama ASC LIMIT $offset, $per_page";
    $data_list = mysqli_query($conn, $query);
    
} elseif ($level == 'kecamatan') {
    $count_query = "SELECT COUNT(*) as total FROM kecamatan WHERE 1=1";
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $count_query .= " AND nama LIKE '%$search_escaped%'";
    }
    if ($parent_id > 0) {
        $count_query .= " AND kabupaten_id = $parent_id";
    }
    $count_result = mysqli_query($conn, $count_query);
    $total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
    $total_pages = max(1, ceil($total_data / $per_page));
    
    // Build query dinamis
    $select_fields = "kec.*";
    if ($has_kode_kec) $select_fields .= ", kec.kode";
    if ($has_created_kec) $select_fields .= ", kec.created_at";
    $select_fields .= ", kab.nama as kabupaten_nama";
    
    $query = "SELECT $select_fields 
              FROM kecamatan kec 
              LEFT JOIN kabupaten kab ON kec.kabupaten_id = kab.id";
    
    $where = [];
    if (!empty($search)) {
        $where[] = "kec.nama LIKE '%$search_escaped%'";
    }
    if ($parent_id > 0) {
        $where[] = "kec.kabupaten_id = $parent_id";
    }
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    $query .= " ORDER BY kec.nama ASC LIMIT $offset, $per_page";
    $data_list = mysqli_query($conn, $query);
    
} elseif ($level == 'desa') {
    $count_query = "SELECT COUNT(*) as total FROM desa WHERE 1=1";
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $count_query .= " AND nama LIKE '%$search_escaped%'";
    }
    if ($parent_id > 0) {
        $count_query .= " AND kecamatan_id = $parent_id";
    }
    $count_result = mysqli_query($conn, $count_query);
    $total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
    $total_pages = max(1, ceil($total_data / $per_page));
    
    // Build query dinamis
    $select_fields = "des.*";
    if ($has_kode_desa) $select_fields .= ", des.kode";
    if ($has_created_desa) $select_fields .= ", des.created_at";
    $select_fields .= ", kec.nama as kecamatan_nama, kab.nama as kabupaten_nama";
    
    $query = "SELECT $select_fields 
              FROM desa des 
              LEFT JOIN kecamatan kec ON des.kecamatan_id = kec.id 
              LEFT JOIN kabupaten kab ON kec.kabupaten_id = kab.id";
    
    $where = [];
    if (!empty($search)) {
        $where[] = "des.nama LIKE '%$search_escaped%'";
    }
    if ($parent_id > 0) {
        $where[] = "des.kecamatan_id = $parent_id";
    }
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    $query .= " ORDER BY des.nama ASC LIMIT $offset, $per_page";
    $data_list = mysqli_query($conn, $query);
}

// ============================================
// AMBIL DAFTAR KABUPATEN UNTUK FILTER
// ============================================
$kabupaten_query = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $kabupaten_query);

// ============================================
// AMBIL DAFTAR KECAMATAN UNTUK FILTER
// ============================================
$kecamatan_query = "SELECT id, nama, kabupaten_id FROM kecamatan ORDER BY nama";
$kecamatan_list = mysqli_query($conn, $kecamatan_query);

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- PAGE HEADER -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-map-marker-alt"></i> Manajemen Wilayah</h2>
        <p class="text-muted">Kelola data kabupaten, kecamatan, dan desa</p>
    </div>
    <div class="page-header-right">
        <a href="wilayah_tambah.php?level=<?php echo $level; ?><?php echo $parent_id > 0 ? '&parent_id=' . $parent_id : ''; ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah <?php echo ucfirst($level); ?>
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- ALERT MESSAGES -->
<!-- ============================================ -->
<?php if ($success == 'tambah'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Data berhasil ditambahkan!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($success == 'edit'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Data berhasil diperbarui!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($success == 'hapus'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Data berhasil dihapus!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($error == 'notfound'): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> Data tidak ditemukan!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($error == 'delete_failed'): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> Gagal menghapus data! Data mungkin masih digunakan.
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- NAVIGASI LEVEL -->
<!-- ============================================ -->
<div class="level-navigation" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; background: #fff; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <a href="?level=kabupaten" class="btn <?php echo $level == 'kabupaten' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 20px;">
        <i class="fas fa-city"></i> Kabupaten
    </a>
    <a href="?level=kecamatan" class="btn <?php echo $level == 'kecamatan' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 20px;">
        <i class="fas fa-map"></i> Kecamatan
    </a>
    <a href="?level=desa" class="btn <?php echo $level == 'desa' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 20px;">
        <i class="fas fa-location-dot"></i> Desa
    </a>
    
    <?php if ($level == 'kecamatan' && $parent_id > 0): ?>
        <span style="display: flex; align-items: center; color: #666; font-size: 0.9rem; margin-left: 10px;">
            <i class="fas fa-filter"></i> Filter: 
            <?php 
            $kab_nama = '';
            $kab_query = "SELECT nama FROM kabupaten WHERE id = $parent_id";
            $kab_result = mysqli_query($conn, $kab_query);
            if ($kab_result) {
                $kab_row = mysqli_fetch_assoc($kab_result);
                $kab_nama = $kab_row['nama'] ?? '';
            }
            echo htmlspecialchars($kab_nama);
            ?>
            <a href="?level=kecamatan" style="color: #e74c3c; margin-left: 8px; text-decoration: none;">
                <i class="fas fa-times"></i>
            </a>
        </span>
    <?php endif; ?>
    
    <?php if ($level == 'desa' && $parent_id > 0): ?>
        <span style="display: flex; align-items: center; color: #666; font-size: 0.9rem; margin-left: 10px;">
            <i class="fas fa-filter"></i> Filter: 
            <?php 
            $kec_nama = '';
            $kec_query = "SELECT nama FROM kecamatan WHERE id = $parent_id";
            $kec_result = mysqli_query($conn, $kec_query);
            if ($kec_result) {
                $kec_row = mysqli_fetch_assoc($kec_result);
                $kec_nama = $kec_row['nama'] ?? '';
            }
            echo htmlspecialchars($kec_nama);
            ?>
            <a href="?level=desa" style="color: #e74c3c; margin-left: 8px; text-decoration: none;">
                <i class="fas fa-times"></i>
            </a>
        </span>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- FILTER & SEARCH -->
<!-- ============================================ -->
<div class="filter-section" style="background: #fff; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <form action="" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="level" value="<?php echo $level; ?>">
        
        <div style="flex: 2; min-width: 200px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px;">Cari</label>
            <div class="input-group">
                <span class="input-icon"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <?php if ($level == 'kecamatan'): ?>
            <div style="min-width: 150px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px;">Kabupaten</label>
                <select name="parent_id" class="form-control">
                    <option value="0">Semua Kabupaten</option>
                    <?php 
                    mysqli_data_seek($kabupaten_list, 0);
                    while ($kab = mysqli_fetch_assoc($kabupaten_list)): 
                    ?>
                        <option value="<?php echo $kab['id']; ?>" <?php echo $parent_id == $kab['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kab['nama']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <?php if ($level == 'desa'): ?>
            <div style="min-width: 150px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px;">Kecamatan</label>
                <select name="parent_id" class="form-control">
                    <option value="0">Semua Kecamatan</option>
                    <?php 
                    mysqli_data_seek($kecamatan_list, 0);
                    while ($kec = mysqli_fetch_assoc($kecamatan_list)): 
                    ?>
                        <option value="<?php echo $kec['id']; ?>" <?php echo $parent_id == $kec['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kec['nama']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || $parent_id > 0): ?>
                <a href="?level=<?php echo $level; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- TABLE DATA -->
<!-- ============================================ -->
<div class="table-wrapper" style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #e8e8e8;">
                    <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; width: 60px;">No</th>
                    <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555;">Nama</th>
                    <?php if ($level != 'kabupaten'): ?>
                        <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555;">Induk</th>
                    <?php endif; ?>
                    <?php if ($level == 'kabupaten' && $has_kode_kab): ?>
                        <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; width: 100px;">Kode</th>
                    <?php endif; ?>
                    <?php if ($level == 'kecamatan' && $has_kode_kec): ?>
                        <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; width: 100px;">Kode</th>
                    <?php endif; ?>
                    <?php if ($level == 'desa' && $has_kode_desa): ?>
                        <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; width: 100px;">Kode</th>
                    <?php endif; ?>
                    <?php if (
                        ($level == 'kabupaten' && $has_created_kab) ||
                        ($level == 'kecamatan' && $has_created_kec) ||
                        ($level == 'desa' && $has_created_desa)
                    ): ?>
                        <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; width: 130px;">Tanggal</th>
                    <?php endif; ?>
                    <th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #555; width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_list && mysqli_num_rows($data_list) > 0): ?>
                    <?php $no = $offset + 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($data_list)): ?>
                        <tr style="border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                            <td style="padding: 12px 15px; text-align: center; color: #999; font-size: 0.9rem;">
                                <?php echo $no++; ?>
                            </td>
                            <td style="padding: 12px 15px;">
                                <strong style="color: #1a1a2e;">
                                    <?php if ($level == 'kabupaten'): ?>
                                        <i class="fas fa-city" style="color: #d4a847; margin-right: 8px;"></i>
                                    <?php elseif ($level == 'kecamatan'): ?>
                                        <i class="fas fa-map" style="color: #d4a847; margin-right: 8px;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-location-dot" style="color: #d4a847; margin-right: 8px;"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($row['nama']); ?>
                                </strong>
                            </td>
                            <?php if ($level != 'kabupaten'): ?>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?php 
                                    if ($level == 'kecamatan') {
                                        echo htmlspecialchars($row['kabupaten_nama'] ?? '-');
                                    } elseif ($level == 'desa') {
                                        echo htmlspecialchars($row['kecamatan_nama'] ?? '-');
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($level == 'kabupaten' && $has_kode_kab): ?>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?php echo htmlspecialchars($row['kode'] ?? '-'); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($level == 'kecamatan' && $has_kode_kec): ?>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?php echo htmlspecialchars($row['kode'] ?? '-'); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($level == 'desa' && $has_kode_desa): ?>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?php echo htmlspecialchars($row['kode'] ?? '-'); ?>
                                </td>
                            <?php endif; ?>
                            <?php if (
                                ($level == 'kabupaten' && $has_created_kab) ||
                                ($level == 'kecamatan' && $has_created_kec) ||
                                ($level == 'desa' && $has_created_desa)
                            ): ?>
                                <td style="padding: 12px 15px; font-size: 0.8rem; color: #999;">
                                    <?php echo isset($row['created_at']) && !empty($row['created_at']) ? tanggal_indonesia($row['created_at']) : '-'; ?>
                                </td>
                            <?php endif; ?>
                            <td style="padding: 12px 15px; text-align: center;">
                                <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                    <?php if ($level == 'kabupaten'): ?>
                                        <a href="?level=kecamatan&parent_id=<?php echo $row['id']; ?>" 
                                           class="btn-action btn-view" title="Lihat Kecamatan"
                                           style="padding: 6px 10px; background: #e8f0fe; color: #3498db; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                            <i class="fas fa-map"></i>
                                        </a>
                                    <?php elseif ($level == 'kecamatan'): ?>
                                        <a href="?level=desa&parent_id=<?php echo $row['id']; ?>" 
                                           class="btn-action btn-view" title="Lihat Desa"
                                           style="padding: 6px 10px; background: #e8f0fe; color: #3498db; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                            <i class="fas fa-location-dot"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="wilayah_edit.php?level=<?php echo $level; ?>&id=<?php echo $row['id']; ?>" 
                                       class="btn-action btn-edit" title="Edit"
                                       style="padding: 6px 10px; background: #e8f0fe; color: #1a6e3a; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="wilayah_hapus.php?level=<?php echo $level; ?>&id=<?php echo $row['id']; ?>" 
                                       class="btn-action btn-delete" title="Hapus"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"
                                       style="padding: 6px 10px; background: #fde8e8; color: #e74c3c; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php 
                            $colspan = 3; // No + Nama + Aksi
                            if ($level != 'kabupaten') $colspan++; // Induk
                            if ($level == 'kabupaten' && $has_kode_kab) $colspan++;
                            if ($level == 'kecamatan' && $has_kode_kec) $colspan++;
                            if ($level == 'desa' && $has_kode_desa) $colspan++;
                            if (
                                ($level == 'kabupaten' && $has_created_kab) ||
                                ($level == 'kecamatan' && $has_created_kec) ||
                                ($level == 'desa' && $has_created_desa)
                            ) $colspan++;
                            echo $colspan;
                        ?>" style="padding: 40px 15px; text-align: center;">
                            <i class="fas fa-map-marker-alt" style="font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 10px;"></i>
                            <h3 style="color: #1a1a2e; margin-bottom: 5px;">Belum Ada Data</h3>
                            <p style="color: #999; font-size: 0.95rem;">Silakan tambahkan data <?php echo $level; ?></p>
                            <a href="wilayah_tambah.php?level=<?php echo $level; ?><?php echo $parent_id > 0 ? '&parent_id=' . $parent_id : ''; ?>" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Tambah <?php echo ucfirst($level); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- ============================================ -->
    <!-- PAGINATION -->
    <!-- ============================================ -->
    <?php if ($total_pages > 1): ?>
        <div style="padding: 15px 20px; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <span style="font-size: 0.85rem; color: #666;">
                Menampilkan <?php echo number_format(($page - 1) * $per_page + 1); ?> - 
                <?php echo number_format(min($page * $per_page, $total_data)); ?> 
                dari <?php echo number_format($total_data); ?> data
            </span>
            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&level=<?php echo $level; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $parent_id > 0 ? '&parent_id=' . $parent_id : ''; ?>" 
                       style="padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.85rem;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?page=1&level=' . $level . (!empty($search) ? '&search=' . urlencode($search) : '') . ($parent_id > 0 ? '&parent_id=' . $parent_id : '') . '" style="padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.85rem;">1</a>';
                    if ($start_page > 2) echo '<span style="padding: 6px 4px; color: #999;">...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <a href="?page=<?php echo $i; ?>&level=<?php echo $level; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $parent_id > 0 ? '&parent_id=' . $parent_id : ''; ?>" 
                       style="padding: 6px 12px; background: <?php echo $i == $page ? '#1a6e3a' : '#f0f0f0'; ?>; color: <?php echo $i == $page ? '#fff' : '#333'; ?>; border-radius: 4px; text-decoration: none; font-size: 0.85rem; <?php echo $i == $page ? 'font-weight: 600;' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span style="padding: 6px 4px; color: #999;">...</span>';
                    echo '<a href="?page=' . $total_pages . '&level=' . $level . (!empty($search) ? '&search=' . urlencode($search) : '') . ($parent_id > 0 ? '&parent_id=' . $parent_id : '') . '" style="padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.85rem;">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&level=<?php echo $level; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $parent_id > 0 ? '&parent_id=' . $parent_id : ''; ?>" 
                       style="padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.85rem;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- STATISTIK -->
<!-- ============================================ -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 25px;">
    <?php
    $count_kab = 0;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kabupaten");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count_kab = $row['total'] ?? 0;
    }
    
    $count_kec = 0;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kecamatan");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count_kec = $row['total'] ?? 0;
    }
    
    $count_desa = 0;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM desa");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count_desa = $row['total'] ?? 0;
    }
    ?>
    <div style="background: #fff; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #1a6e3a;">
        <div style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;"><?php echo number_format($count_kab); ?></div>
        <div style="font-size: 0.8rem; color: #999;">Kabupaten</div>
    </div>
    <div style="background: #fff; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #d4a847;">
        <div style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;"><?php echo number_format($count_kec); ?></div>
        <div style="font-size: 0.8rem; color: #999;">Kecamatan</div>
    </div>
    <div style="background: #fff; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;"><?php echo number_format($count_desa); ?></div>
        <div style="font-size: 0.8rem; color: #999;">Desa</div>
    </div>
</div>

<!-- ============================================ -->
<!-- STYLE -->
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
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
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
    
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-group .input-icon {
        position: absolute;
        left: 14px;
        color: #999;
        font-size: 1rem;
        z-index: 1;
    }
    .input-group .form-control {
        width: 100%;
        padding: 10px 14px 10px 45px;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #fff;
        color: #333;
    }
    .input-group .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #fff;
        color: #333;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        position: relative;
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
    .alert-dismissible {
        padding-right: 45px;
    }
    .alert-close {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.6;
    }
    .alert-close:hover {
        opacity: 1;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .btn-action:hover {
        transform: scale(1.1);
    }
    .btn-edit:hover {
        background: #1a6e3a !important;
        color: #fff !important;
    }
    .btn-view:hover {
        background: #3498db !important;
        color: #fff !important;
    }
    .btn-delete:hover {
        background: #e74c3c !important;
        color: #fff !important;
    }
    
    .level-navigation .btn {
        font-size: 0.85rem;
        padding: 8px 18px;
    }
    .level-navigation .btn-primary {
        background: #1a6e3a;
    }
    .level-navigation .btn-secondary {
        background: #e8e8e8;
        color: #555;
    }
    .level-navigation .btn-secondary:hover {
        background: #d5d5d5;
        color: #333;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        .page-header-right .btn {
            width: 100%;
            justify-content: center;
        }
        .filter-section form {
            flex-direction: column;
        }
        .filter-section form > div {
            width: 100% !important;
            min-width: unset !important;
        }
        .filter-section form .btn {
            width: 100%;
            justify-content: center;
        }
        .table-responsive {
            font-size: 0.85rem;
        }
        .table td, .table th {
            padding: 8px 10px !important;
        }
        .level-navigation {
            flex-direction: column;
        }
        .level-navigation .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .table td, .table th {
            font-size: 0.75rem;
            padding: 6px 8px !important;
        }
        .btn-action {
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto close alert after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>