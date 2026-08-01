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
$success = '';

// ============================================
// AMBIL PARAMETER
// ============================================
$level = isset($_GET['level']) ? $_GET['level'] : 'kabupaten';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// Validasi level yang diizinkan
$allowed_levels = ['kabupaten', 'kecamatan', 'desa'];
if (!in_array($level, $allowed_levels)) {
    $level = 'kabupaten';
}

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
$parent_list = null;
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
                    $success = 'Data kabupaten berhasil ditambahkan!';
                    $_POST = [];
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
                        $success = 'Data kecamatan berhasil ditambahkan!';
                        $_POST = [];
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
                        $success = 'Data desa berhasil ditambahkan!';
                        $_POST = [];
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
        <p class="text-muted">Tambahkan data <?php echo $level; ?> baru ke dalam sistem</p>
    </div>
    <div class="page-header-right">
        <a href="wilayah.php?level=<?php echo $level; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- ALERT MESSAGES -->
<!-- ============================================ -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FORM -->
<!-- ============================================ -->
<div class="form-wrapper">
    <form action="" method="POST" id="formWilayah" autocomplete="off">
        
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
                       value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                       autofocus>
            </div>
            <small class="form-text text-muted">Nama lengkap <?php echo $level; ?> tanpa singkatan</small>
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
            <label for="kode" class="form-label">Kode Wilayah</label>
            <div class="input-group">
                <span class="input-icon"><i class="fas fa-hashtag"></i></span>
                <input type="text" class="form-control" id="kode" name="kode" 
                       placeholder="Masukkan kode (opsional)"
                       value="<?php echo isset($_POST['kode']) ? htmlspecialchars($_POST['kode']) : ''; ?>">
            </div>
            <small class="form-text text-muted">Kode opsional, misalnya kode BPS atau kode administrasi</small>
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
                    <option value="">-- Pilih <?php echo $level == 'kecamatan' ? 'Kabupaten' : 'Kecamatan'; ?> --</option>
                    <?php 
                    if ($parent_list && mysqli_num_rows($parent_list) > 0) {
                        mysqli_data_seek($parent_list, 0);
                        while ($row = mysqli_fetch_assoc($parent_list)): 
                            $selected = (isset($_POST['parent_id']) && $_POST['parent_id'] == $row['id']) || 
                                        (!isset($_POST['parent_id']) && $parent_id == $row['id']);
                    ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nama']); ?>
                        </option>
                    <?php 
                        endwhile;
                    } else {
                        echo '<option value="" disabled>Tidak ada data tersedia</option>';
                    }
                    ?>
                </select>
            </div>
            <small class="form-text text-muted">
                Pilih <?php echo $level == 'kecamatan' ? 'kabupaten' : 'kecamatan'; ?> induk untuk wilayah ini
            </small>
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
<!-- STYLE - RESPONSIF & MODERN -->
<!-- ============================================ -->
<style>
    /* ===== RESET & BASE ===== */
    * {
        box-sizing: border-box;
    }
    
    /* ===== PAGE HEADER ===== */
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
    
    /* ===== ALERT ===== */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        position: relative;
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
        padding: 5px 10px;
    }
    .alert-close:hover {
        opacity: 1;
    }
    
    /* ===== FORM WRAPPER ===== */
    .form-wrapper {
        background: #ffffff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0,0,0,0.06);
        max-width: 650px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }
    
    /* ===== FORM GROUP ===== */
    .form-group {
        margin-bottom: 22px;
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
    
    /* ===== INPUT GROUP ===== */
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
        pointer-events: none;
        transition: color 0.3s ease;
    }
    .input-group:focus-within .input-icon {
        color: #1a6e3a;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 14px 12px 45px;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.3s ease;
        background: #fafafa;
        color: #333;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    .form-control::placeholder {
        color: #bbb;
    }
    
    /* ===== SELECT KHUSUS - PERBAIKAN BAYANGAN ===== */
    select.form-control {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23999' d='M6 8L1 0h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 12px 8px;
        padding-right: 45px;
        cursor: pointer;
        background-color: #fafafa;
    }
    select.form-control:focus {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%231a6e3a' d='M6 8L1 0h10z'/%3E%3C/svg%3E");
        border-color: #1a6e3a;
        background-color: #ffffff;
    }
    /* Hilangkan icon panah default di browser */
    select.form-control::-ms-expand {
        display: none;
    }
    
    .form-text {
        font-size: 0.8rem;
        color: #999;
        margin-top: 5px;
        display: block;
    }
    
    /* ===== BUTTONS ===== */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 22px;
        border: none;
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: inherit;
        min-height: 48px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        flex: 1;
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
        border-radius: 12px;
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
        flex: 1;
    }
    
    /* ============================================ */
    /* RESPONSIVE */
    /* ============================================ */
    
    /* Tablet & Mobile */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .page-header-left h2 {
            font-size: 1.2rem;
        }
        .page-header-right .btn {
            width: 100%;
            justify-content: center;
        }
        
        .form-wrapper {
            padding: 20px 16px !important;
            max-width: 100%;
            border-radius: 12px;
        }
        
        .form-control {
            font-size: 0.9rem;
            padding: 10px 12px 10px 40px;
        }
        .input-group .input-icon {
            font-size: 0.9rem;
            left: 12px;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 10px;
        }
        .form-actions .btn {
            width: 100%;
            justify-content: center;
            min-height: 48px;
        }
        
        .alert {
            padding: 12px 16px;
            font-size: 0.9rem;
            flex-wrap: wrap;
        }
        
        select.form-control {
            background-position: right 12px center;
            padding-right: 40px;
        }
    }
    
    /* Mobile Small */
    @media (max-width: 480px) {
        .page-header-left h2 {
            font-size: 1rem;
        }
        .page-header-left .text-muted {
            font-size: 0.8rem;
        }
        
        .form-wrapper {
            padding: 15px 12px !important;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            font-size: 0.8rem;
        }
        .form-control {
            font-size: 0.85rem;
            padding: 9px 10px 9px 36px;
            border-radius: 8px;
        }
        .input-group .input-icon {
            font-size: 0.8rem;
            left: 10px;
        }
        
        .btn {
            font-size: 0.85rem;
            padding: 8px 16px;
            min-height: 42px;
            border-radius: 8px;
        }
        .btn-lg {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        
        .form-actions {
            padding-top: 18px;
            gap: 8px;
        }
        
        .alert {
            font-size: 0.8rem;
            padding: 10px 14px;
            border-radius: 8px;
        }
        .alert-close {
            font-size: 1rem;
            right: 10px;
        }
        
        .form-text {
            font-size: 0.7rem;
        }
        
        select.form-control {
            background-position: right 10px center;
            background-size: 10px 7px;
            padding-right: 35px;
        }
    }
    
    /* Desktop Large */
    @media (min-width: 1200px) {
        .form-wrapper {
            padding: 45px 50px;
            max-width: 700px;
        }
        .form-control {
            font-size: 1rem;
            padding: 14px 16px 14px 50px;
        }
        .input-group .input-icon {
            font-size: 1.1rem;
            left: 16px;
        }
    }
</style>

<!-- ============================================ -->
<!-- SCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== AUTO CLOSE ALERT =====
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease, transform 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // ===== VALIDASI FORM =====
    const form = document.getElementById('formWilayah');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nama = document.getElementById('nama');
            const parentSelect = document.getElementById('parent_id');
            let isValid = true;
            
            // Reset border
            document.querySelectorAll('.form-control').forEach(function(el) {
                el.style.borderColor = '';
            });
            
            // Validasi Nama
            if (nama && nama.value.trim() === '') {
                e.preventDefault();
                nama.style.borderColor = '#e74c3c';
                nama.focus();
                alert('Nama wajib diisi!');
                isValid = false;
                return false;
            }
            
            // Validasi Parent (jika ada)
            if (parentSelect && parentSelect.value === '') {
                e.preventDefault();
                parentSelect.style.borderColor = '#e74c3c';
                parentSelect.focus();
                alert('Silakan pilih induk wilayah!');
                isValid = false;
                return false;
            }
        });
        
        // Hilangkan border error saat input
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('input', function() {
                this.style.borderColor = '';
            });
            input.addEventListener('change', function() {
                this.style.borderColor = '';
            });
            input.addEventListener('focus', function() {
                this.style.borderColor = '';
            });
        });
    }
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>