<?php
// admin/yayasan.php - Halaman Manajemen Yayasan
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

$title = 'Manajemen Yayasan';
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// ============================================
// CEK DAN BUAT TABEL YAYASAN
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
    
    // Insert data default
    $insert_default = "INSERT INTO `yayasan` SET 
        `nama_yayasan` = 'PGNI Lampung',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
}

// ============================================
// AMBIL DATA YAYASAN
// ============================================
$query = "SELECT * FROM yayasan WHERE id = 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

$yayasan = mysqli_fetch_assoc($result);

// Jika data kosong, buat data default
if (!$yayasan) {
    $insert_default = "INSERT INTO `yayasan` SET 
        `nama_yayasan` = 'PGNI Lampung',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
    $result = mysqli_query($conn, $query);
    $yayasan = mysqli_fetch_assoc($result);
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

include $root_path . '/admin/include/admin_header.php';
?>

<!-- ============================================ -->
<!-- PAGE HEADER -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-building"></i> Manajemen Yayasan</h2>
        <p class="text-muted">Kelola data profil yayasan atau organisasi</p>
    </div>
    <div class="page-header-right">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- ALERT MESSAGES -->
<!-- ============================================ -->
<?php if ($success == 'update'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Data yayasan berhasil diperbarui!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($error == 'update_failed'): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> Gagal memperbarui data yayasan! Silakan coba lagi.
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($error == 'empty_name'): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> Nama yayasan wajib diisi!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- DEBUG INFO (HAPUS SETELAH PROBLEM SOLVED) -->
<!-- ============================================ -->
<?php if ($error == 'update_failed'): ?>
<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffc107;">
    <h4 style="color: #856404; margin: 0 0 10px 0;">🔍 Debug Info</h4>
    <div style="font-size: 0.9rem; color: #666;">
        <p><strong>Error:</strong> Update data gagal</p>
        <p><strong>Tips:</strong> Pastikan tabel <code>yayasan</code> memiliki semua kolom yang diperlukan.</p>
        <p><strong>Kolom yang seharusnya ada:</strong> id, nama_yayasan, nama_pimpinan, alamat, kabupaten_id, kecamatan_id, desa_id, no_telp, email, website, logo, deskripsi, visi, misi, tahun_berdiri, status, created_at, updated_at</p>
        <a href="yayasan_check.php" class="btn btn-primary" style="margin-top: 5px; padding: 5px 15px; font-size: 0.85rem;">
            <i class="fas fa-stethoscope"></i> Cek Database
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FORM YAYASAN -->
<!-- ============================================ -->
<div class="form-wrapper">
    <form action="yayasan_update.php" method="POST" enctype="multipart/form-data" id="formYayasan">
        <input type="hidden" name="id" value="<?php echo $yayasan['id'] ?? 1; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <!-- KOLOM KIRI -->
            <div>
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
                
                <div class="form-group">
                    <label for="nama_pimpinan" class="form-label">Nama Pimpinan</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tie"></i></span>
                        <input type="text" class="form-control" id="nama_pimpinan" name="nama_pimpinan" 
                               placeholder="Masukkan nama pimpinan yayasan"
                               value="<?php echo htmlspecialchars($yayasan['nama_pimpinan'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tahun_berdiri" class="form-label">Tahun Berdiri</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-calendar"></i></span>
                        <input type="number" class="form-control" id="tahun_berdiri" name="tahun_berdiri" 
                               placeholder="Contoh: 2015" min="1900" max="<?php echo date('Y'); ?>"
                               value="<?php echo htmlspecialchars($yayasan['tahun_berdiri'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="logo" class="form-label">Logo Yayasan</label>
                    
                    <?php if (!empty($yayasan['logo'])): ?>
                        <div class="current-logo">
                            <img src="../assets/images/logo/<?php echo htmlspecialchars($yayasan['logo']); ?>" 
                                 alt="Logo Yayasan"
                                 style="max-width: 120px; max-height: 120px; border-radius: 8px; border: 2px solid #e8e8e8; padding: 5px; background: #fff;">
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
                        </div>
                        <div class="file-preview" id="logoPreview"></div>
                        <div class="file-status" id="logoStatus"></div>
                    </div>
                </div>
            </div>
            
            <!-- KOLOM KANAN -->
            <div>
                <div class="form-group">
                    <label for="alamat" class="form-label">Alamat</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" id="alamat" name="alamat" 
                                  rows="3" placeholder="Masukkan alamat lengkap yayasan"><?php echo htmlspecialchars($yayasan['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>
                
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
                
                <div class="form-group">
                    <label for="no_telp" class="form-label">No. Telepon</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="no_telp" name="no_telp" 
                               placeholder="Contoh: 0721-1234567"
                               value="<?php echo htmlspecialchars($yayasan['no_telp'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Contoh: info@yayasan.com"
                               value="<?php echo htmlspecialchars($yayasan['email'] ?? ''); ?>">
                    </div>
                </div>
                
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
                              placeholder="Tulis misi yayasan"><?php echo htmlspecialchars($yayasan['misi'] ?? ''); ?></textarea>
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
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Update Data
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
        
    </form>
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
        max-width: 120px;
        max-height: 120px;
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
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
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

    // File upload preview
    const uploadArea = document.getElementById('logoUploadArea');
    const fileInput = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');
    const status = document.getElementById('logoStatus');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                uploadArea.classList.add('has-file');
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview Logo">`;
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
    
    // Auto close alert
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