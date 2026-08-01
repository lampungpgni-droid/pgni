<?php
// admin/wilayah_tambah.php - Tambah Data Wilayah
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

$title = 'Tambah Wilayah';
$error = '';

// ============================================
// AMBIL PARAMETER
// ============================================
$level = isset($_GET['level']) ? $_GET['level'] : 'kabupaten';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// ============================================
// CEK DAN PERBAIKI TABEL
// ============================================
function add_column_if_not_exists($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $query = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        return mysqli_query($conn, $query);
    }
    return true;
}

add_column_if_not_exists($conn, 'kabupaten', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'kabupaten', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
add_column_if_not_exists($conn, 'kecamatan', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'kecamatan', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
add_column_if_not_exists($conn, 'desa', 'kode', 'VARCHAR(10) NULL');
add_column_if_not_exists($conn, 'desa', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');

// ============================================
// CEK KOLOM YANG ADA
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
$has_kode_kec = in_array('kode', $kec_columns);
$has_kode_desa = in_array('kode', $desa_columns);

// ============================================
// AMBIL DAFTAR PARENT
// ============================================
if ($level == 'kecamatan') {
    $parent_list = mysqli_query($conn, "SELECT id, nama FROM kabupaten ORDER BY nama");
} elseif ($level == 'desa') {
    $parent_list = mysqli_query($conn, "SELECT id, nama FROM kecamatan ORDER BY nama");
}

// ============================================
// PROSES TAMBAH
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
    $kode = isset($_POST['kode']) ? mysqli_real_escape_string($conn, trim($_POST['kode'])) : '';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    if (empty($nama)) {
        $error = 'Nama wajib diisi!';
    } else {
        if ($level == 'kabupaten') {
            // Cek duplikat
            $check = mysqli_query($conn, "SELECT id FROM kabupaten WHERE nama = '$nama'");
            if (mysqli_num_rows($check) > 0) {
                $error = 'Nama kabupaten sudah terdaftar!';
            } else {
                if ($has_kode_kab) {
                    $query = "INSERT INTO kabupaten (nama, kode) VALUES ('$nama', '$kode')";
                } else {
                    $query = "INSERT INTO kabupaten (nama) VALUES ('$nama')";
                }
                
                if (mysqli_query($conn, $query)) {
                    header('Location: wilayah.php?level=kabupaten&msg=tambah');
                    exit;
                } else {
                    $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
                }
            }
            
        } elseif ($level == 'kecamatan') {
            if ($parent_id <= 0) {
                $error = 'Silakan pilih kabupaten!';
            } else {
                $check = mysqli_query($conn, "SELECT id FROM kecamatan WHERE nama = '$nama' AND kabupaten_id = $parent_id");
                if (mysqli_num_rows($check) > 0) {
                    $error = 'Nama kecamatan sudah terdaftar di kabupaten ini!';
                } else {
                    if ($has_kode_kec) {
                        $query = "INSERT INTO kecamatan (nama, kode, kabupaten_id) VALUES ('$nama', '$kode', $parent_id)";
                    } else {
                        $query = "INSERT INTO kecamatan (nama, kabupaten_id) VALUES ('$nama', $parent_id)";
                    }
                    
                    if (mysqli_query($conn, $query)) {
                        header('Location: wilayah.php?level=kecamatan&msg=tambah');
                        exit;
                    } else {
                        $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
                    }
                }
            }
            
        } elseif ($level == 'desa') {
            if ($parent_id <= 0) {
                $error = 'Silakan pilih kecamatan!';
            } else {
                $check = mysqli_query($conn, "SELECT id FROM desa WHERE nama = '$nama' AND kecamatan_id = $parent_id");
                if (mysqli_num_rows($check) > 0) {
                    $error = 'Nama desa sudah terdaftar di kecamatan ini!';
                } else {
                    if ($has_kode_desa) {
                        $query = "INSERT INTO desa (nama, kode, kecamatan_id) VALUES ('$nama', '$kode', $parent_id)";
                    } else {
                        $query = "INSERT INTO desa (nama, kecamatan_id) VALUES ('$nama', $parent_id)";
                    }
                    
                    if (mysqli_query($conn, $query)) {
                        header('Location: wilayah.php?level=desa&msg=tambah');
                        exit;
                    } else {
                        $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
                    }
                }
            }
        }
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- PAGE HEADER -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-plus-circle"></i> Tambah <?php echo ucfirst($level); ?></h2>
        <p class="text-muted">Tambahkan data <?php echo $level; ?> baru</p>
    </div>
    <div class="page-header-right">
        <a href="wilayah.php?level=<?php echo $level; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- ALERT -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FORM -->
<!-- ============================================ -->
<div class="form-wrapper">
    <form action="" method="POST">
        
        <!-- Nama -->
        <div class="form-group">
            <label for="nama" class="form-label">
                Nama <?php echo ucfirst($level); ?> <span class="required">*</span>
            </label>
            <div class="input-group">
                <span class="input-icon">
                    <?php if ($level == 'kabupaten'): ?>
                        <i class="fas fa-city"></i>
                    <?php elseif ($level == 'kecamatan'): ?>
                        <i class="fas fa-map"></i>
                    <?php else: ?>
                        <i class="fas fa-location-dot"></i>
                    <?php endif; ?>
                </span>
                <input type="text" class="form-control" id="nama" name="nama" 
                       placeholder="Masukkan nama <?php echo $level; ?>" required
                       value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
            </div>
        </div>
        
        <!-- Kode (jika ada) -->
        <?php 
        $show_kode = false;
        if ($level == 'kabupaten' && $has_kode_kab) $show_kode = true;
        if ($level == 'kecamatan' && $has_kode_kec) $show_kode = true;
        if ($level == 'desa' && $has_kode_desa) $show_kode = true;
        ?>
        <?php if ($show_kode): ?>
        <div class="form-group">
            <label for="kode" class="form-label">Kode</label>
            <div class="input-group">
                <span class="input-icon"><i class="fas fa-hashtag"></i></span>
                <input type="text" class="form-control" id="kode" name="kode" 
                       placeholder="Masukkan kode (opsional)"
                       value="<?php echo isset($_POST['kode']) ? htmlspecialchars($_POST['kode']) : ''; ?>">
            </div>
            <small class="form-text text-muted">Kode opsional, misalnya kode BPS atau kode wilayah</small>
        </div>
        <?php endif; ?>
        
        <!-- Parent (untuk kecamatan dan desa) -->
        <?php if ($level == 'kecamatan' || $level == 'desa'): ?>
        <div class="form-group">
            <label for="parent_id" class="form-label">
                <?php echo $level == 'kecamatan' ? 'Kabupaten' : 'Kecamatan'; ?> <span class="required">*</span>
            </label>
            <div class="input-group">
                <span class="input-icon">
                    <?php if ($level == 'kecamatan'): ?>
                        <i class="fas fa-city"></i>
                    <?php else: ?>
                        <i class="fas fa-map"></i>
                    <?php endif; ?>
                </span>
                <select class="form-control" id="parent_id" name="parent_id" required>
                    <option value="">Pilih <?php echo $level == 'kecamatan' ? 'Kabupaten' : 'Kecamatan'; ?></option>
                    <?php 
                    if (isset($parent_list)) {
                        mysqli_data_seek($parent_list, 0);
                        while ($row = mysqli_fetch_assoc($parent_list)): 
                    ?>
                        <option value="<?php echo $row['id']; ?>" 
                            <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nama']); ?>
                        </option>
                    <?php 
                        endwhile;
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tombol -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Simpan Data
            </button>
            <a href="wilayah.php?level=<?php echo $level; ?>" class="btn btn-danger btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
        
    </form>
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
    
    .form-wrapper {
        background: #fff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.06);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: #333;
        font-size: 0.9rem;
    }
    .form-label .required {
        color: #e74c3c;
        font-weight: 700;
        margin-left: 3px;
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
    .form-control {
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
    
    .form-text {
        font-size: 0.8rem;
        color: #999;
        margin-top: 4px;
        display: block;
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
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c0392b;
        color: #fff;
        transform: translateY(-2px);
    }
    .btn-lg {
        padding: 12px 32px;
        font-size: 1rem;
        border-radius: 10px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        padding-top: 25px;
        border-top: 2px solid #f0f0f0;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .form-actions .btn {
        min-width: 140px;
        justify-content: center;
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
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        .page-header-right .btn {
            width: 100%;
            justify-content: center;
        }
        .form-wrapper {
            padding: 20px 15px !important;
            max-width: 100%;
        }
        .form-actions {
            flex-direction: column;
        }
        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .form-control {
            font-size: 0.85rem;
            padding: 9px 12px 9px 38px;
        }
        .input-group .input-icon {
            font-size: 0.85rem;
            left: 12px;
        }
    }
</style>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>