<?php
// admin/yayasan_edit.php - Edit Data Yayasan dengan Loading & Popup
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

$title = 'Edit Yayasan';

// ============================================
// CEK APAKAH TABEL YAYASAN ADA
// ============================================
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'yayasan'");
$table_exists = mysqli_num_rows($check_table) > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE IF NOT EXISTS `yayasan` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nama_yayasan` VARCHAR(255) NOT NULL,
        `nama_pimpinan` VARCHAR(255) NULL,
        `alamat` TEXT NULL,
        `kabupaten_id` INT(11) NULL,
        `kecamatan_id` INT(11) NULL,
        `desa_id` INT(11) NULL,
        `no_telp` VARCHAR(20) NULL,
        `email` VARCHAR(100) NULL,
        `website` VARCHAR(100) NULL,
        `logo` VARCHAR(255) NULL,
        `deskripsi` TEXT NULL,
        `visi` TEXT NULL,
        `misi` TEXT NULL,
        `tahun_berdiri` YEAR NULL,
        `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
    
    $insert_default = "INSERT INTO `yayasan` SET 
        `nama_yayasan` = 'PGNI Lampung',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
}

// ============================================
// AMBIL DATA YAYASAN
// ============================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$query = "SELECT * FROM yayasan WHERE id = $id";
$result = mysqli_query($conn, $query);
$yayasan = mysqli_fetch_assoc($result);

if (!$yayasan) {
    header('Location: yayasan.php');
    exit;
}

// ============================================
// AMBIL DAFTAR KABUPATEN
// ============================================
$kabupaten_query = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $kabupaten_query);

$kabupaten_id = $yayasan['kabupaten_id'] ?? 0;
$kecamatan_query = "SELECT id, nama FROM kecamatan WHERE kabupaten_id = $kabupaten_id ORDER BY nama";
$kecamatan_list = mysqli_query($conn, $kecamatan_query);

$kecamatan_id = $yayasan['kecamatan_id'] ?? 0;
$desa_query = "SELECT id, nama FROM desa WHERE kecamatan_id = $kecamatan_id ORDER BY nama";
$desa_list = mysqli_query($conn, $desa_query);

// ============================================
// PROSES FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_yayasan = isset($_POST['nama_yayasan']) ? mysqli_real_escape_string($conn, trim($_POST['nama_yayasan'])) : '';
    $nama_pimpinan = isset($_POST['nama_pimpinan']) ? mysqli_real_escape_string($conn, trim($_POST['nama_pimpinan'])) : '';
    $alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, trim($_POST['alamat'])) : '';
    $kabupaten_id = isset($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : 0;
    $kecamatan_id = isset($_POST['kecamatan_id']) ? (int)$_POST['kecamatan_id'] : 0;
    $desa_id = isset($_POST['desa_id']) ? (int)$_POST['desa_id'] : 0;
    $no_telp = isset($_POST['no_telp']) ? mysqli_real_escape_string($conn, trim($_POST['no_telp'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $website = isset($_POST['website']) ? mysqli_real_escape_string($conn, trim($_POST['website'])) : '';
    $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($conn, trim($_POST['deskripsi'])) : '';
    $visi = isset($_POST['visi']) ? mysqli_real_escape_string($conn, trim($_POST['visi'])) : '';
    $misi = isset($_POST['misi']) ? mysqli_real_escape_string($conn, trim($_POST['misi'])) : '';
    $tahun_berdiri = isset($_POST['tahun_berdiri']) ? (int)$_POST['tahun_berdiri'] : 0;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'aktif';
    $hapus_logo = isset($_POST['hapus_logo']) ? true : false;
    
    // Ambil base64 logo
    $logo_base64 = isset($_POST['logo_base64']) ? $_POST['logo_base64'] : '';
    
    if (empty($nama_yayasan)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama yayasan wajib diisi!']);
        exit;
    }
    
    // Proses logo
    $logo = $yayasan['logo'] ?? '';
    
    if ($hapus_logo && $logo) {
        $old_path = $root_path . '/assets/images/logo/' . $logo;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
        $logo = '';
    }
    
    if (!empty($logo_base64)) {
        if ($logo) {
            $old_path = $root_path . '/assets/images/logo/' . $logo;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        $logo = save_base64_image($logo_base64, 'logo', 'yayasan', $nama_yayasan);
        if (!$logo) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan logo!']);
            exit;
        }
    }
    
    // Update database
    $query = "UPDATE yayasan SET 
        nama_yayasan = '$nama_yayasan',
        nama_pimpinan = '$nama_pimpinan',
        alamat = '$alamat',
        kabupaten_id = $kabupaten_id,
        kecamatan_id = $kecamatan_id,
        desa_id = $desa_id,
        no_telp = '$no_telp',
        email = '$email',
        website = '$website',
        logo = '$logo',
        deskripsi = '$deskripsi',
        visi = '$visi',
        misi = '$misi',
        tahun_berdiri = $tahun_berdiri,
        status = '$status',
        updated_at = NOW()
        WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data yayasan berhasil diperbarui!',
            'logo' => $logo
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . mysqli_error($conn)]);
        exit;
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- PAGE HEADER -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-edit"></i> Edit Yayasan</h2>
        <p class="text-muted">Perbarui data profil yayasan</p>
    </div>
    <div class="page-header-right">
        <a href="yayasan.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- LOADING OVERLAY -->
<!-- ============================================ -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <i class="fas fa-building"></i>
        </div>
        <h3 id="loadingText">Menyimpan Data...</h3>
        <p id="loadingSubText">Mohon tunggu sebentar</p>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
        </div>
        <span class="progress-text" id="progressText">0%</span>
        <div class="loading-detail" id="loadingDetail">
            <span id="loadingStep">⏳ Mempersiapkan data...</span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- POPUP MODAL -->
<!-- ============================================ -->
<div class="popup-modal" id="popupModal">
    <div class="popup-content">
        <div class="popup-icon" id="popupIcon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 id="popupTitle">Berhasil!</h3>
        <p id="popupMessage">Data yayasan berhasil diperbarui.</p>
        <div class="popup-detail" id="popupDetail" style="display:none;">
            <div class="detail-item">
                <i class="fas fa-building"></i>
                <span id="popupNama">-</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-file-image"></i>
                <span id="popupLogo">-</span>
            </div>
        </div>
        <div class="popup-buttons">
            <button class="btn btn-primary" id="popupBtn" onclick="closePopup()">
                <i class="fas fa-check"></i> OK
            </button>
            <a href="yayasan.php" class="btn btn-secondary" id="popupRedirect" style="display:none;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FORM -->
<!-- ============================================ -->
<div class="form-wrapper">
    <form action="" method="POST" id="formYayasan" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $yayasan['id']; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <!-- KOLOM KIRI -->
            <div>
                <!-- Nama Yayasan -->
                <div class="form-group">
                    <label for="nama_yayasan" class="form-label">
                        Nama Yayasan <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-building"></i></span>
                        <input type="text" class="form-control" id="nama_yayasan" name="nama_yayasan" 
                               placeholder="Masukkan nama yayasan" required
                               value="<?php echo htmlspecialchars($yayasan['nama_yayasan'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Nama Pimpinan -->
                <div class="form-group">
                    <label for="nama_pimpinan" class="form-label">Nama Pimpinan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tie"></i></span>
                        <input type="text" class="form-control" id="nama_pimpinan" name="nama_pimpinan" 
                               placeholder="Masukkan nama pimpinan yayasan"
                               value="<?php echo htmlspecialchars($yayasan['nama_pimpinan'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Tahun Berdiri -->
                <div class="form-group">
                    <label for="tahun_berdiri" class="form-label">Tahun Berdiri</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-calendar"></i></span>
                        <input type="number" class="form-control" id="tahun_berdiri" name="tahun_berdiri" 
                               placeholder="Contoh: 2015" min="1900" max="<?php echo date('Y'); ?>"
                               value="<?php echo htmlspecialchars($yayasan['tahun_berdiri'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Logo -->
                <div class="form-group">
                    <label for="logo" class="form-label">Logo Yayasan</label>
                    
                    <?php if (!empty($yayasan['logo'])): ?>
                        <div class="current-logo">
                            <img src="../assets/images/logo/<?php echo htmlspecialchars($yayasan['logo']); ?>" 
                                 alt="Logo Yayasan"
                                 style="max-width: 100px; max-height: 100px; border-radius: 8px; border: 2px solid #e8e8e8; padding: 4px; background: #fff;">
                            <div style="margin-top: 5px;">
                                <label style="cursor: pointer; font-size: 0.85rem; color: #e74c3c;">
                                    <input type="checkbox" name="hapus_logo" value="1">
                                    Hapus logo ini
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="file-upload-wrapper">
                        <div class="file-upload-area" id="logoUploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau drag & drop untuk upload logo baru</p>
                            <span class="file-types">JPG, PNG, WebP (Max 5MB)</span>
                            <div class="file-info">
                                <span>📐 Ukuran disarankan: 200x200px</span>
                            </div>
                            <input type="file" class="file-input" id="logo" name="logo" accept="image/*">
                            <input type="hidden" id="logo_base64" name="logo_base64" value="">
                        </div>
                        <div class="file-preview" id="logoPreview"></div>
                        <div class="file-status" id="logoStatus"></div>
                    </div>
                </div>
            </div>
            
            <!-- KOLOM KANAN -->
            <div>
                <!-- Alamat -->
                <div class="form-group">
                    <label for="alamat" class="form-label">Alamat</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" id="alamat" name="alamat" 
                                  rows="3" placeholder="Masukkan alamat lengkap yayasan"><?php echo htmlspecialchars($yayasan['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Kabupaten -->
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
                                <option value="<?php echo $kab['id']; ?>" 
                                    <?php echo ($yayasan['kabupaten_id'] ?? 0) == $kab['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kab['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Kecamatan -->
                <div class="form-group">
                    <label for="kecamatan_id" class="form-label">Kecamatan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map"></i></span>
                        <select class="form-control" id="kecamatan_id" name="kecamatan_id">
                            <option value="">Pilih Kecamatan</option>
                            <?php while ($kec = mysqli_fetch_assoc($kecamatan_list)): ?>
                                <option value="<?php echo $kec['id']; ?>" 
                                    <?php echo ($yayasan['kecamatan_id'] ?? 0) == $kec['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kec['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Desa -->
                <div class="form-group">
                    <label for="desa_id" class="form-label">Desa/Kelurahan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-location-dot"></i></span>
                        <select class="form-control" id="desa_id" name="desa_id">
                            <option value="">Pilih Desa</option>
                            <?php while ($desa = mysqli_fetch_assoc($desa_list)): ?>
                                <option value="<?php echo $desa['id']; ?>" 
                                    <?php echo ($yayasan['desa_id'] ?? 0) == $desa['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($desa['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <!-- No Telepon -->
                <div class="form-group">
                    <label for="no_telp" class="form-label">No. Telepon</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="no_telp" name="no_telp" 
                               placeholder="Contoh: 0721-1234567"
                               value="<?php echo htmlspecialchars($yayasan['no_telp'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Contoh: info@yayasan.com"
                               value="<?php echo htmlspecialchars($yayasan['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Website -->
                <div class="form-group">
                    <label for="website" class="form-label">Website</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-globe"></i></span>
                        <input type="text" class="form-control" id="website" name="website" 
                               placeholder="Contoh: www.yayasan.com"
                               value="<?php echo htmlspecialchars($yayasan['website'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- VISI & MISI -->
        <div style="margin-top: 30px; border-top: 2px solid #f0f0f0; padding-top: 25px;">
            <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px;">
                <i class="fas fa-bullseye" style="color: #d4a847;"></i> Visi & Misi
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div class="form-group">
                    <label for="visi" class="form-label">Visi</label>
                    <textarea class="form-control" id="visi" name="visi" rows="4" 
                              placeholder="Tulis visi yayasan"><?php echo htmlspecialchars($yayasan['visi'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="misi" class="form-label">Misi</label>
                    <textarea class="form-control" id="misi" name="misi" rows="4" 
                              placeholder="Tulis misi yayasan (pisahkan dengan baris baru)"><?php echo htmlspecialchars($yayasan['misi'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">Pisahkan setiap misi dengan baris baru</small>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="deskripsi" class="form-label">Deskripsi Yayasan</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" 
                          placeholder="Tulis deskripsi lengkap tentang yayasan"><?php echo htmlspecialchars($yayasan['deskripsi'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="status" class="form-label">Status</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-toggle-on"></i></span>
                    <select class="form-control" id="status" name="status">
                        <option value="aktif" <?php echo ($yayasan['status'] ?? 'aktif') == 'aktif' ? 'selected' : ''; ?>>✅ Aktif</option>
                        <option value="nonaktif" <?php echo ($yayasan['status'] ?? 'aktif') == 'nonaktif' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- TOMBOL -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="fas fa-save"></i> Update Data
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
        
    </form>
</div>

<style>
    /* ========================================== */
    /* STYLE - SAMA SEPERTI YAYASAN.PHP */
    /* ========================================== */
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
        border-radius: 8px;
        border: 2px solid #e8e8e8;
        padding: 4px;
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
    
    .current-logo {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px;
        border-left: 3px solid #d4a847;
        margin-bottom: 10px;
    }
    .current-logo label {
        cursor: pointer;
        font-size: 0.85rem;
        color: #e74c3c;
    }
    .current-logo label input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
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
    .btn-lg {
        padding: 12px 32px;
        font-size: 1rem;
        border-radius: 10px;
    }
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        padding-top: 25px;
        border-top: 2px solid #f0f0f0;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    .form-actions .btn {
        min-width: 160px;
        justify-content: center;
    }
    
    /* Loading Overlay */
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.75);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-content {
        background: #fff;
        padding: 40px 50px;
        border-radius: 20px;
        text-align: center;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        0% { transform: scale(0.9) translateY(20px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    
    .loading-spinner {
        position: relative;
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
    }
    .spinner-ring {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 4px solid #f0f0f0;
        border-top: 4px solid #1a6e3a;
        animation: spin 1s linear infinite;
    }
    .loading-spinner i {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 2rem;
        color: #d4a847;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 5px;
    }
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #1a6e3a, #2d8f52);
        border-radius: 10px;
        transition: width 0.3s ease;
        width: 0%;
    }
    .progress-text {
        font-size: 0.8rem;
        color: #999;
    }
    
    .loading-detail {
        margin-top: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #555;
    }
    .loading-detail span {
        display: block;
    }
    
    /* Popup Modal */
    .popup-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }
    .popup-modal.active {
        display: flex;
    }
    
    .popup-content {
        background: #fff;
        padding: 40px 45px;
        border-radius: 20px;
        text-align: center;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: popupIn 0.5s ease;
    }
    
    @keyframes popupIn {
        0% { transform: scale(0.8) rotate(-3deg); opacity: 0; }
        100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }
    
    .popup-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2.5rem;
    }
    .popup-icon.success {
        background: #d4edda;
        color: #28a745;
    }
    .popup-icon.error {
        background: #f8d7da;
        color: #dc3545;
    }
    .popup-icon.warning {
        background: #fff3cd;
        color: #ffc107;
    }
    
    .popup-content h3 {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    .popup-content p {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 15px;
        line-height: 1.6;
        white-space: pre-line;
    }
    
    .popup-detail {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 20px;
        text-align: left;
    }
    .popup-detail .detail-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 4px 0;
        font-size: 0.85rem;
        color: #555;
    }
    .popup-detail .detail-item i {
        width: 20px;
        color: #d4a847;
        text-align: center;
    }
    .popup-detail .detail-item span {
        word-break: break-all;
    }
    
    .popup-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .popup-buttons .btn {
        min-width: 120px;
        justify-content: center;
    }
    
    @media (max-width: 992px) {
        .form-wrapper form > div:first-child {
            grid-template-columns: 1fr !important;
        }
        .form-wrapper form > div:last-child > div:first-child {
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
        .loading-content {
            padding: 30px 20px;
        }
        .popup-content {
            padding: 30px 20px;
        }
        .popup-buttons .btn {
            min-width: 100%;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // LOAD KECAMATAN & DESA
    // ==========================================
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
                    const selectedKec = <?php echo $yayasan['kecamatan_id'] ?? 0; ?>;
                    if (selectedKec > 0) {
                        kecamatanSelect.value = selectedKec;
                        kecamatanSelect.dispatchEvent(new Event('change'));
                    }
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
                    const selectedDesa = <?php echo $yayasan['desa_id'] ?? 0; ?>;
                    if (selectedDesa > 0) {
                        desaSelect.value = selectedDesa;
                    }
                })
                .catch(() => {
                    desaSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        } else {
            desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
        }
    });

    // ==========================================
    // KOMPRESI LOGO
    // ==========================================
    function compressImage(file, maxWidth, maxHeight, quality) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;
                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob(function(blob) {
                        if (blob) resolve(blob);
                        else reject(new Error('Gagal kompresi'));
                    }, 'image/jpeg', quality);
                };
                img.onerror = function() { reject(new Error('Gagal memuat gambar')); };
                img.src = e.target.result;
            };
            reader.onerror = function() { reject(new Error('Gagal membaca file')); };
            reader.readAsDataURL(file);
        });
    }

    // ==========================================
    // SETUP FILE UPLOAD
    // ==========================================
    const uploadArea = document.getElementById('logoUploadArea');
    const fileInput = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');
    const status = document.getElementById('logoStatus');
    const hiddenBase64 = document.getElementById('logo_base64');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status';
                hiddenBase64.value = '';
                return;
            }
            
            const maxSize = 5 * 1024 * 1024;
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            if (file.size > maxSize) {
                status.innerHTML = `❌ Ukuran file terlalu besar (${fileSizeMB}MB). Maksimal 5MB.`;
                status.className = 'file-status error';
                this.value = '';
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                hiddenBase64.value = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                status.innerHTML = `❌ Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP.`;
                status.className = 'file-status error';
                this.value = '';
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                hiddenBase64.value = '';
                return;
            }
            
            status.innerHTML = `⏳ Mengompresi gambar (${fileSizeMB}MB)...`;
            status.className = 'file-status loading';
            uploadArea.classList.add('has-file');
            
            compressImage(file, 400, 400, 0.8)
                .then(compressedBlob => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview Logo">`;
                    };
                    reader.readAsDataURL(compressedBlob);
                    
                    const base64Reader = new FileReader();
                    base64Reader.onload = function(e) {
                        hiddenBase64.value = e.target.result;
                        const compressedSizeMB = (compressedBlob.size / (1024 * 1024)).toFixed(2);
                        const savedPercent = ((1 - (compressedBlob.size / file.size)) * 100).toFixed(0);
                        status.innerHTML = `✅ Berhasil! ${fileSizeMB}MB → ${compressedSizeMB}MB (hemat ${savedPercent}%)`;
                        status.className = 'file-status success';
                    };
                    base64Reader.readAsDataURL(compressedBlob);
                })
                .catch(function(err) {
                    console.error('Kompresi gagal:', err);
                    status.innerHTML = '⚠️ Kompresi gagal, menggunakan file asli...';
                    status.className = 'file-status loading';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview Logo">`;
                        hiddenBase64.value = e.target.result;
                        status.innerHTML = `✅ File asli (${fileSizeMB}MB) - tanpa kompresi`;
                        status.className = 'file-status success';
                    };
                    reader.readAsDataURL(file);
                });
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

    // ==========================================
    // FORM SUBMIT
    // ==========================================
    document.getElementById('formYayasan').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nama = document.getElementById('nama_yayasan').value.trim();
        if (!nama) {
            showPopup('error', '⚠️ Gagal!', 'Nama yayasan wajib diisi!');
            return;
        }
        
        const formData = new FormData(this);
        const logoBase64 = document.getElementById('logo_base64').value;
        if (logoBase64) {
            formData.append('logo_base64', logoBase64);
        }
        
        showLoading('Menyimpan Data...', 'Mohon tunggu sebentar');
        updateProgress(0);
        updateLoadingStep('⏳ Mempersiapkan data...');
        
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) {
                clearInterval(progressInterval);
                progress = 90;
            }
            updateProgress(Math.min(progress, 90));
        }, 200);
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            updateLoadingStep('📤 Menerima respon server...');
            return response.json();
        })
        .then(data => {
            clearInterval(progressInterval);
            updateProgress(100);
            updateLoadingStep('✅ Selesai!');
            
            setTimeout(() => {
                hideLoading();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Data';
                
                if (data.status === 'success') {
                    showPopupWithDetail(
                        'success',
                        '✅ Berhasil!',
                        'Data yayasan berhasil diperbarui!',
                        {
                            nama: nama,
                            logo: data.logo || '-'
                        },
                        true
                    );
                } else {
                    showPopup('error', '❌ Gagal!', data.message || 'Terjadi kesalahan.');
                }
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            hideLoading();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Data';
            showPopup('error', '❌ Error!', 'Terjadi kesalahan koneksi: ' + error.message);
        });
    });

    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================
    function showLoading(text, subText) {
        document.getElementById('loadingText').textContent = text || 'Menyimpan Data...';
        document.getElementById('loadingSubText').textContent = subText || 'Mohon tunggu sebentar';
        document.getElementById('loadingOverlay').classList.add('active');
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressText').textContent = '0%';
        document.getElementById('loadingDetail').style.display = 'block';
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
    
    function updateProgress(value) {
        const progress = Math.min(Math.max(value, 0), 100);
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressText').textContent = Math.round(progress) + '%';
    }
    
    function updateLoadingStep(text) {
        document.getElementById('loadingStep').textContent = text;
    }
    
    function showPopup(type, title, message, redirect = false) {
        const modal = document.getElementById('popupModal');
        const icon = document.getElementById('popupIcon');
        const titleEl = document.getElementById('popupTitle');
        const messageEl = document.getElementById('popupMessage');
        const redirectBtn = document.getElementById('popupRedirect');
        const okBtn = document.getElementById('popupBtn');
        const detailEl = document.getElementById('popupDetail');
        
        icon.className = 'popup-icon';
        detailEl.style.display = 'none';
        
        if (type === 'success') {
            icon.classList.add('success');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            icon.classList.add('error');
            icon.innerHTML = '<i class="fas fa-times-circle"></i>';
        } else {
            icon.classList.add('warning');
            icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
        }
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        messageEl.style.whiteSpace = 'pre-line';
        
        if (redirect) {
            redirectBtn.style.display = 'inline-flex';
        } else {
            redirectBtn.style.display = 'none';
        }
        okBtn.textContent = 'OK';
        
        modal.classList.add('active');
    }
    
    function showPopupWithDetail(type, title, message, detail, redirect = false) {
        const modal = document.getElementById('popupModal');
        const icon = document.getElementById('popupIcon');
        const titleEl = document.getElementById('popupTitle');
        const messageEl = document.getElementById('popupMessage');
        const redirectBtn = document.getElementById('popupRedirect');
        const okBtn = document.getElementById('popupBtn');
        const detailEl = document.getElementById('popupDetail');
        
        icon.className = 'popup-icon';
        detailEl.style.display = 'block';
        
        if (type === 'success') {
            icon.classList.add('success');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            icon.classList.add('error');
            icon.innerHTML = '<i class="fas fa-times-circle"></i>';
        } else {
            icon.classList.add('warning');
            icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
        }
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        messageEl.style.whiteSpace = 'pre-line';
        
        document.getElementById('popupNama').textContent = detail.nama || '-';
        document.getElementById('popupLogo').textContent = detail.logo || '-';
        
        if (redirect) {
            redirectBtn.style.display = 'inline-flex';
        } else {
            redirectBtn.style.display = 'none';
        }
        okBtn.textContent = 'OK';
        
        modal.classList.add('active');
    }
    
    window.closePopup = function() {
        document.getElementById('popupModal').classList.remove('active');
    }
    
    document.getElementById('popupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>