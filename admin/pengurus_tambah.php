<?php
// admin/pengurus_tambah.php - Tambah Pengurus
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

$title = 'Tambah Pengurus';
$error = '';

// ============================================
// CEK DAN PERBAIKI TABEL PENGURUS
// ============================================
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pengurus'");
$table_exists = mysqli_num_rows($check_table) > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE IF NOT EXISTS `pengurus` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `parent_id` INT(11) NULL,
        `nama` VARCHAR(255) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `is_ketua_bidang` TINYINT(1) DEFAULT 0,
        `jenis_kelamin` ENUM('L', 'P') NULL,
        `tempat_lahir` VARCHAR(100) NULL,
        `tanggal_lahir` DATE NULL,
        `foto` VARCHAR(255) NULL,
        `bio` TEXT NULL,
        `email` VARCHAR(100) NULL,
        `no_telp` VARCHAR(20) NULL,
        `alamat` TEXT NULL,
        `kabupaten_id` INT(11) NULL,
        `kecamatan_id` INT(11) NULL,
        `desa_id` INT(11) NULL,
        `urutan` INT(11) DEFAULT 0,
        `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_jabatan` (`jabatan`),
        INDEX `idx_parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
} else {
    // Cek dan tambahkan kolom yang hilang
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pengurus");
    $existing_columns = [];
    while ($col = mysqli_fetch_assoc($check_columns)) {
        $existing_columns[] = $col['Field'];
    }
    
    $columns_to_add = [
        'parent_id' => "ALTER TABLE `pengurus` ADD COLUMN `parent_id` INT(11) NULL AFTER `id`",
        'is_ketua_bidang' => "ALTER TABLE `pengurus` ADD COLUMN `is_ketua_bidang` TINYINT(1) DEFAULT 0 AFTER `jabatan`"
    ];
    
    foreach ($columns_to_add as $col_name => $alter_query) {
        if (!in_array($col_name, $existing_columns)) {
            mysqli_query($conn, $alter_query);
        }
    }
    
    // Tambahkan index parent_id jika belum ada
    $check_index = mysqli_query($conn, "SHOW INDEX FROM pengurus WHERE Key_name = 'idx_parent_id'");
    if (mysqli_num_rows($check_index) == 0) {
        mysqli_query($conn, "ALTER TABLE `pengurus` ADD INDEX `idx_parent_id` (`parent_id`)");
    }
}

// ============================================
// AMBIL DAFTAR KOLOM YANG ADA
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pengurus");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

$has_bio = in_array('bio', $existing_columns);
$has_desa = in_array('desa_id', $existing_columns);
$has_kecamatan = in_array('kecamatan_id', $existing_columns);
$has_kabupaten = in_array('kabupaten_id', $existing_columns);
$has_urutan = in_array('urutan', $existing_columns);
$has_updated_at = in_array('updated_at', $existing_columns);
$has_parent_id = in_array('parent_id', $existing_columns);
$has_is_ketua_bidang = in_array('is_ketua_bidang', $existing_columns);

// ============================================
// AMBIL DAFTAR KABUPATEN
// ============================================
$kabupaten_query = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $kabupaten_query);

// ============================================
// AMBIL DAFTAR KETUA BIDANG (untuk dropdown parent)
// ============================================
$ketua_bidang_query = "SELECT id, nama, jabatan FROM pengurus WHERE is_ketua_bidang = 1 AND status = 'aktif' ORDER BY nama";
$ketua_bidang_list = mysqli_query($conn, $ketua_bidang_query);

// ============================================
// DAFTAR JABATAN
// ============================================
$jabatan_list = [
    'Pembina',
    'Penasehat', 
    'Pengawas',
    'Ketua Umum',
    'Wakil Ketua',
    'Sekretaris Umum',
    'Wakil Sekretaris',
    'Bendahara Umum',
    'Wakil Bendahara',
    'Bidang Organisasi dan Kaderisasi',
    'Bidang Ketenagakerjaan dan Kesejahteraan',
    'Bidang Pendidikan dan Pelatihan',
    'Bidang Informasi dan Komunikasi',
    'Bidang Pengembangan Karir dan Profesi',
    'Bidang Penelitian dan Pengembangan',
    'Bidang Humas',
    'Bidang Da\'wah',
    'Bidang Pengabdian Masyarakat',
    'Bidang Advokasi dan Perlindungan Hukum',
    'Anggota'
];

// ============================================
// FUNGSI HELPER UNTUK ESCAPE
// ============================================
function escape_null($value) {
    if ($value === '' || $value === NULL) {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($GLOBALS['conn'], $value) . "'";
}

// ============================================
// PROSES FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $jabatan = isset($_POST['jabatan']) ? trim($_POST['jabatan']) : '';
    $is_ketua_bidang = isset($_POST['is_ketua_bidang']) ? (int)$_POST['is_ketua_bidang'] : 0;
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    $tempat_lahir = isset($_POST['tempat_lahir']) ? trim($_POST['tempat_lahir']) : '';
    $tanggal_lahir = isset($_POST['tanggal_lahir']) && !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $no_telp = isset($_POST['no_telp']) ? trim($_POST['no_telp']) : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    $kabupaten_id = isset($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : 0;
    $kecamatan_id = isset($_POST['kecamatan_id']) ? (int)$_POST['kecamatan_id'] : 0;
    $desa_id = isset($_POST['desa_id']) ? (int)$_POST['desa_id'] : 0;
    $urutan = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'aktif';
    
    // Validasi
    if (empty($nama) || empty($jabatan)) {
        $error = 'Nama dan Jabatan wajib diisi!';
    } else {
        // Upload foto
        $foto = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $upload = upload_file($_FILES['foto'], 'pengurus', ['jpg','jpeg','png','gif','webp'], 5242880);
            if ($upload['status']) {
                $foto = $upload['nama_file'];
            } else {
                $error = 'Upload foto gagal: ' . $upload['message'];
            }
        }
        
        if (empty($error)) {
            // ==========================================
            // BUILD QUERY DENGAN ESCAPE YANG BENAR
            // ==========================================
            $fields = [];
            $values = [];
            
            // Kolom wajib
            $fields[] = "nama";
            $values[] = "'" . mysqli_real_escape_string($conn, $nama) . "'";
            
            $fields[] = "jabatan";
            $values[] = "'" . mysqli_real_escape_string($conn, $jabatan) . "'";
            
            if ($has_is_ketua_bidang) {
                $fields[] = "is_ketua_bidang";
                $values[] = $is_ketua_bidang;
            }
            
            if ($has_parent_id) {
                $fields[] = "parent_id";
                $values[] = $parent_id > 0 ? $parent_id : "NULL";
            }
            
            $fields[] = "foto";
            $values[] = "'" . mysqli_real_escape_string($conn, $foto) . "'";
            
            // JENIS KELAMIN - PERBAIKAN UTAMA
            // Hanya masukkan jika nilai 'L' atau 'P', selain itu NULL
            $fields[] = "jenis_kelamin";
            if ($jenis_kelamin === 'L' || $jenis_kelamin === 'P') {
                $values[] = "'" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
            } else {
                $values[] = "NULL";
            }
            
            // TEMPAT LAHIR
            $fields[] = "tempat_lahir";
            $values[] = !empty($tempat_lahir) ? "'" . mysqli_real_escape_string($conn, $tempat_lahir) . "'" : "NULL";
            
            // TANGGAL LAHIR
            $fields[] = "tanggal_lahir";
            $values[] = !empty($tanggal_lahir) ? "'" . mysqli_real_escape_string($conn, $tanggal_lahir) . "'" : "NULL";
            
            if ($has_bio) {
                $fields[] = "bio";
                $values[] = !empty($bio) ? "'" . mysqli_real_escape_string($conn, $bio) . "'" : "NULL";
            }
            
            if ($has_kabupaten) {
                $fields[] = "kabupaten_id";
                $values[] = $kabupaten_id > 0 ? $kabupaten_id : "NULL";
            }
            
            if ($has_kecamatan) {
                $fields[] = "kecamatan_id";
                $values[] = $kecamatan_id > 0 ? $kecamatan_id : "NULL";
            }
            
            if ($has_desa) {
                $fields[] = "desa_id";
                $values[] = $desa_id > 0 ? $desa_id : "NULL";
            }
            
            $fields[] = "email";
            $values[] = !empty($email) ? "'" . mysqli_real_escape_string($conn, $email) . "'" : "NULL";
            
            $fields[] = "no_telp";
            $values[] = !empty($no_telp) ? "'" . mysqli_real_escape_string($conn, $no_telp) . "'" : "NULL";
            
            $fields[] = "alamat";
            $values[] = !empty($alamat) ? "'" . mysqli_real_escape_string($conn, $alamat) . "'" : "NULL";
            
            if ($has_urutan) {
                $fields[] = "urutan";
                $values[] = $urutan;
            }
            
            $fields[] = "status";
            $values[] = "'" . mysqli_real_escape_string($conn, $status) . "'";
            
            $fields[] = "created_at";
            $values[] = "NOW()";
            
            if ($has_updated_at) {
                $fields[] = "updated_at";
                $values[] = "NOW()";
            }
            
            $query = "INSERT INTO pengurus (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            
            if (mysqli_query($conn, $query)) {
                header('Location: pengurus.php?msg=tambah');
                exit;
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
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
        <h2><i class="fas fa-user-plus"></i> Tambah Pengurus</h2>
        <p class="text-muted">Tambahkan data pengurus baru</p>
    </div>
    <div class="page-header-right">
        <a href="pengurus.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FORM -->
<!-- ============================================ -->
<div class="form-wrapper">
    <form action="" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <!-- KOLOM KIRI -->
            <div>
                <div class="form-group">
                    <label for="nama" class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               placeholder="Masukkan nama lengkap" required
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="jabatan" class="form-label">Jabatan <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tie"></i></span>
                        <select class="form-control" id="jabatan" name="jabatan" required>
                            <option value="">Pilih Jabatan</option>
                            <?php foreach ($jabatan_list as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo (isset($_POST['jabatan']) && $_POST['jabatan'] == $j) ? 'selected' : ''; ?>>
                                    <?php echo $j; ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Lainnya" <?php echo (isset($_POST['jabatan']) && $_POST['jabatan'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($has_is_ketua_bidang): ?>
                <div class="form-group">
                    <label for="is_ketua_bidang" class="form-label">Status</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                        <select class="form-control" id="is_ketua_bidang" name="is_ketua_bidang">
                            <option value="0" <?php echo (isset($_POST['is_ketua_bidang']) && $_POST['is_ketua_bidang'] == 0) ? 'selected' : ''; ?>>Anggota Biasa</option>
                            <option value="1" <?php echo (isset($_POST['is_ketua_bidang']) && $_POST['is_ketua_bidang'] == 1) ? 'selected' : ''; ?>>Ketua Bidang</option>
                        </select>
                    </div>
                    <small class="form-text text-muted">Pilih "Ketua Bidang" jika ini adalah ketua dari suatu bidang</small>
                </div>
                <?php endif; ?>
                
                <?php if ($has_parent_id): ?>
                <div class="form-group" id="parentGroup">
                    <label for="parent_id" class="form-label">Di Bawah Ketua Bidang</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tie"></i></span>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- Tidak ada (Independen) --</option>
                            <?php 
                            if ($ketua_bidang_list && mysqli_num_rows($ketua_bidang_list) > 0):
                                while ($kb = mysqli_fetch_assoc($ketua_bidang_list)): 
                            ?>
                                <option value="<?php echo $kb['id']; ?>" <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $kb['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kb['nama']); ?> (<?php echo htmlspecialchars($kb['jabatan']); ?>)
                                </option>
                            <?php endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                    <small class="form-text text-muted">Pilih Ketua Bidang jika anggota ini berada di bawah bidang tertentu</small>
                </div>
                <?php endif; ?>
                
                <?php if ($has_urutan): ?>
                <div class="form-group">
                    <label for="urutan" class="form-label">Urutan Tampil</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-sort-numeric-up"></i></span>
                        <input type="number" class="form-control" id="urutan" name="urutan" 
                               placeholder="0" min="0"
                               value="<?php echo isset($_POST['urutan']) ? (int)$_POST['urutan'] : 0; ?>">
                    </div>
                    <small class="form-text text-muted">Semakin kecil angka, semakin atas tampilannya</small>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="foto" class="form-label">Foto</label>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-area" id="fotoUploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau drag & drop untuk upload foto</p>
                            <span class="file-types">JPG, PNG, WebP (Max 5MB)</span>
                            <div class="file-info">
                                <span>📐 Ukuran disarankan: 400x400px</span>
                            </div>
                            <input type="file" class="file-input" id="foto" name="foto" accept="image/*">
                        </div>
                        <div class="file-preview" id="fotoPreview"></div>
                        <div class="file-status" id="fotoStatus"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-toggle-on"></i></span>
                        <select class="form-control" id="status" name="status">
                            <option value="aktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>✅ Aktif</option>
                            <option value="nonaktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'nonaktif') ? 'selected' : ''; ?>>❌ Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- KOLOM KANAN -->
            <div>
                <div class="form-group">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-venus-mars"></i></span>
                        <select class="form-control" id="jenis_kelamin" name="jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="L" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map-pin"></i></span>
                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" 
                               placeholder="Contoh: Bandar Lampung"
                               value="<?php echo isset($_POST['tempat_lahir']) ? htmlspecialchars($_POST['tempat_lahir']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir"
                               value="<?php echo isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Contoh: email@domain.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="no_telp" class="form-label">No. Telepon</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="no_telp" name="no_telp" 
                               placeholder="Contoh: 0812-3456-7890"
                               value="<?php echo isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alamat" class="form-label">Alamat</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" id="alamat" name="alamat" 
                                  rows="3" placeholder="Masukkan alamat lengkap"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                    </div>
                </div>
                
                <?php if ($has_kabupaten): ?>
                <div class="form-group">
                    <label for="kabupaten_id" class="form-label">Kabupaten</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-city"></i></span>
                        <select class="form-control" id="kabupaten_id" name="kabupaten_id">
                            <option value="">Pilih Kabupaten</option>
                            <?php 
                            mysqli_data_seek($kabupaten_list, 0);
                            while ($kab = mysqli_fetch_assoc($kabupaten_list)): 
                            ?>
                                <option value="<?php echo $kab['id']; ?>" <?php echo (isset($_POST['kabupaten_id']) && $_POST['kabupaten_id'] == $kab['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kab['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_kecamatan): ?>
                <div class="form-group">
                    <label for="kecamatan_id" class="form-label">Kecamatan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map"></i></span>
                        <select class="form-control" id="kecamatan_id" name="kecamatan_id">
                            <option value="">Pilih Kecamatan</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_desa): ?>
                <div class="form-group">
                    <label for="desa_id" class="form-label">Desa/Kelurahan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-location-dot"></i></span>
                        <select class="form-control" id="desa_id" name="desa_id">
                            <option value="">Pilih Desa</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_bio): ?>
                <div class="form-group">
                    <label for="bio" class="form-label">Bio / Deskripsi</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-align-left"></i></span>
                        <textarea class="form-control" id="bio" name="bio" 
                                  rows="2" placeholder="Tulis bio atau deskripsi singkat"><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Simpan Data
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
                <i class="fas fa-undo"></i> Reset
            </button>
            <a href="pengurus.php" class="btn btn-danger btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle parent_id berdasarkan is_ketua_bidang
    const isKetuaBidang = document.getElementById('is_ketua_bidang');
    const parentGroup = document.getElementById('parentGroup');
    const parentSelect = document.getElementById('parent_id');

    function toggleParentGroup() {
        if (isKetuaBidang && isKetuaBidang.value === '1') {
            parentGroup.style.display = 'none';
            if (parentSelect) parentSelect.value = '';
        } else {
            parentGroup.style.display = 'block';
        }
    }

    if (isKetuaBidang) {
        isKetuaBidang.addEventListener('change', toggleParentGroup);
        // Trigger on load
        toggleParentGroup();
    }
    
    // Load kecamatan
    document.getElementById('kabupaten_id').addEventListener('change', function() {
        const kabupatenId = this.value;
        const kecamatanSelect = document.getElementById('kecamatan_id');
        const desaSelect = document.getElementById('desa_id');
        
        kecamatanSelect.innerHTML = '<option value="">Loading...</option>';
        desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
        
        if (kabupatenId) {
            fetch('../ajax/get_kecamatan.php?kabupaten_id=' + kabupatenId)
                .then(response => response.json())
                .then(data => {
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    data.forEach(kec => {
                        kecamatanSelect.innerHTML += `<option value="${kec.id}">${kec.nama}</option>`;
                    });
                })
                .catch(() => {
                    kecamatanSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        } else {
            kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
        }
    });

    document.getElementById('kecamatan_id').addEventListener('change', function() {
        const kecamatanId = this.value;
        const desaSelect = document.getElementById('desa_id');
        
        desaSelect.innerHTML = '<option value="">Loading...</option>';
        
        if (kecamatanId) {
            fetch('../ajax/get_desa.php?kecamatan_id=' + kecamatanId)
                .then(response => response.json())
                .then(data => {
                    desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
                    data.forEach(desa => {
                        desaSelect.innerHTML += `<option value="${desa.id}">${desa.nama}</option>`;
                    });
                })
                .catch(() => {
                    desaSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        } else {
            desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
        }
    });

    // File upload preview
    const uploadArea = document.getElementById('fotoUploadArea');
    const fileInput = document.getElementById('foto');
    const preview = document.getElementById('fotoPreview');
    const status = document.getElementById('fotoStatus');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                uploadArea.classList.add('has-file');
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview Foto">`;
                    status.innerHTML = `✅ File siap upload (${(file.size / 1024 / 1024).toFixed(2)}MB)`;
                    status.className = 'file-status success';
                };
                reader.readAsDataURL(file);
            } else {
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status';
            }
        });
    }
    
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script>

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
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .form-group {
        margin-bottom: 18px;
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
        border-color: #1a6e3a !important;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    textarea.form-control {
        padding-left: 14px;
        resize: vertical;
        font-family: inherit;
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
        padding-left: 45px;
    }
    .input-group textarea.form-control {
        padding-left: 14px;
    }
    
    .file-upload-wrapper {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .file-upload-area {
        border: 2px dashed #d4a847;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fdfcf8;
        position: relative;
    }
    .file-upload-area:hover {
        background: #f8f6f1;
        border-color: #1a6e3a;
    }
    .file-upload-area.dragover {
        border-color: #1a6e3a;
        background: #f0f7f3;
    }
    .file-upload-area.has-file {
        border-color: #2ecc71;
        background: #ecfdf5;
    }
    .file-upload-area i {
        font-size: 2rem;
        color: #d4a847;
        display: block;
        margin-bottom: 5px;
    }
    .file-upload-area p {
        margin: 0;
        color: #555;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .file-upload-area .file-types {
        font-size: 0.7rem;
        color: #999;
        display: block;
        margin-top: 3px;
    }
    .file-upload-area .file-input {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0;
        cursor: pointer;
    }
    
    .file-preview {
        margin-top: 5px;
    }
    .file-preview img {
        max-width: 100px;
        max-height: 100px;
        border-radius: 50%;
        border: 2px solid #e8e8e8;
        padding: 3px;
        background: #fff;
        object-fit: cover;
    }
    
    .file-status {
        font-size: 0.8rem;
        margin-top: 5px;
        padding: 4px 12px;
        border-radius: 4px;
        display: inline-block;
    }
    .file-status.success {
        color: #28a745;
        background: #d4edda;
    }
    .file-status.error {
        color: #dc3545;
        background: #f8d7da;
    }
    .file-status.loading {
        color: #f39c12;
        background: #fff3cd;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .file-info {
        display: flex;
        gap: 15px;
        justify-content: center;
        font-size: 0.7rem;
        color: #888;
        margin-top: 5px;
        flex-wrap: wrap;
    }
    .file-info span {
        background: #f8f9fa;
        padding: 2px 10px;
        border-radius: 12px;
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
    
    @media (max-width: 992px) {
        .form-wrapper form > div:first-child {
            grid-template-columns: 1fr !important;
        }
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
        }
        .form-actions {
            flex-direction: column;
        }
        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>