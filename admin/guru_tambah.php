<?php
// admin/guru_tambah.php
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

$title = 'Tambah Guru Ngaji';
$error = '';

// ============================================
// AMBIL KECAMATAN ID DARI SESSION UNTUK PETUGAS
// ============================================
$user_role = $_SESSION['role'] ?? 'admin';
$user_kecamatan_id = $_SESSION['kecamatan_id'] ?? 0;

// Query kabupaten (semua)
$query_kabupaten = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $query_kabupaten);

// Jika petugas kecamatan, hanya ambil kecamatan miliknya
if ($user_role === 'petugas_kecamatan' && $user_kecamatan_id > 0) {
    $query_kecamatan = "SELECT id, nama FROM kecamatan WHERE id = $user_kecamatan_id ORDER BY nama";
    $kecamatan_list = mysqli_query($conn, $query_kecamatan);
} else {
    $query_kecamatan = "SELECT id, nama FROM kecamatan ORDER BY nama";
    $kecamatan_list = mysqli_query($conn, $query_kecamatan);
}

// Daftar bank
$bank_list = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'Lampung', 'CIMB Niaga', 'Danamon', 'Permata', 'SeaBank','DANA','OVO', 'Lainnya'];

// ... lanjutkan kode selanjutnya ...
// ============================================
// PROSES FORM - MENERIMA BASE64
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = isset($_POST['nik']) ? mysqli_real_escape_string($conn, trim($_POST['nik'])) : '';
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
    $no_telp = isset($_POST['no_telp']) ? mysqli_real_escape_string($conn, trim($_POST['no_telp'])) : '';
    $tempat_mengajar = isset($_POST['tempat_mengajar']) ? mysqli_real_escape_string($conn, $_POST['tempat_mengajar']) : '';
    $tempat_mengajar_detail = isset($_POST['tempat_mengajar_detail']) ? mysqli_real_escape_string($conn, $_POST['tempat_mengajar_detail']) : '';
    $jenis_profesi = isset($_POST['jenis_profesi']) ? mysqli_real_escape_string($conn, $_POST['jenis_profesi']) : '';
    $bank = isset($_POST['bank']) ? mysqli_real_escape_string($conn, $_POST['bank']) : '';
    $no_rekening = isset($_POST['no_rekening']) ? mysqli_real_escape_string($conn, $_POST['no_rekening']) : '';
    $kabupaten_id = isset($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : 0;
    $kecamatan_id = isset($_POST['kecamatan_id']) ? (int)$_POST['kecamatan_id'] : 0;
    $desa_id = isset($_POST['desa_id']) ? (int)$_POST['desa_id'] : 0;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'aktif';
    $status_verifikasi = isset($_POST['status_verifikasi']) ? mysqli_real_escape_string($conn, $_POST['status_verifikasi']) : 'pending';
    $created_by = $_SESSION['user_id'];
    
    // Ambil data file dari hidden input (base64)
    $ktp_base64 = isset($_POST['ktp_base64']) ? $_POST['ktp_base64'] : '';
    $kk_base64 = isset($_POST['kk_base64']) ? $_POST['kk_base64'] : '';
    
    // Validasi
    if (empty($nik) || empty($nama) || empty($tempat_mengajar)) {
        echo json_encode(['status' => 'error', 'message' => 'NIK, Nama, dan Tempat Mengajar wajib diisi!']);
        exit;
    }
    
    // Validasi NIK 16 digit
    if (strlen($nik) !== 16 || !is_numeric($nik)) {
        echo json_encode(['status' => 'error', 'message' => 'NIK harus 16 digit angka!']);
        exit;
    }
    
    // Cek NIK duplicate
    $check = mysqli_query($conn, "SELECT id FROM guru_ngaji WHERE nik = '$nik'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'NIK sudah terdaftar!']);
        exit;
    }
    
    // Proses KTP - menggunakan save_base64_image
    $ktp_file = '';
    if (!empty($ktp_base64)) {
        $ktp_file = save_base64_image($ktp_base64, 'ktp', $nik, $nama);
        if (!$ktp_file) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file KTP! Pastikan file gambar valid.']);
            exit;
        }
    }
    
    // Proses KK - menggunakan save_base64_image
    $kk_file = '';
    if (!empty($kk_base64)) {
        $kk_file = save_base64_image($kk_base64, 'kk', $nik, $nama);
        if (!$kk_file) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file KK! Pastikan file gambar valid.']);
            exit;
        }
    }
    
    // Insert ke database
    $query = "INSERT INTO guru_ngaji SET 
        nik = '$nik',
        nama = '$nama',
        no_telp = '$no_telp',
        tempat_mengajar = '$tempat_mengajar',
        tempat_mengajar_detail = '$tempat_mengajar_detail',
        jenis_profesi = '$jenis_profesi',
        bank = '$bank',
        no_rekening = '$no_rekening',
        kabupaten_id = $kabupaten_id,
        kecamatan_id = $kecamatan_id,
        desa_id = $desa_id,
        ktp_file = '$ktp_file',
        kk_file = '$kk_file',
        status = '$status',
        status_verifikasi = '$status_verifikasi',
        created_by = $created_by";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Data guru berhasil ditambahkan!',
            'ktp_file' => $ktp_file,
            'kk_file' => $kk_file
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . mysqli_error($conn)]);
        exit;
    }
}

include 'include/admin_header.php';
?>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <i class="fas fa-quran"></i>
        </div>
        <h3 id="loadingText">Menyimpan Data...</h3>
        <p id="loadingSubText">Mohon tunggu sebentar</p>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
        </div>
        <span class="progress-text" id="progressText">0%</span>
    </div>
</div>

<!-- Popup Modal -->
<div class="popup-modal" id="popupModal">
    <div class="popup-content">
        <div class="popup-icon" id="popupIcon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 id="popupTitle">Berhasil!</h3>
        <p id="popupMessage">Data guru berhasil ditambahkan.</p>
        <div class="popup-buttons">
            <button class="btn btn-primary" id="popupBtn" onclick="closePopup()">
                <i class="fas fa-check"></i> OK
            </button>
            <a href="guru.php" class="btn btn-secondary" id="popupRedirect" style="display:none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-user-plus"></i> Tambah Guru Ngaji</h2>
        <p class="text-muted">Isi data guru ngaji baru dengan lengkap</p>
    </div>
    <div class="page-header-right">
        <a href="guru.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="form-wrapper">
    <form action="" method="POST" id="formGuru" enctype="multipart/form-data">
        
        <!-- Data Pribadi -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-user-circle"></i>
                <h3>Data Pribadi</h3>
                <span class="section-badge">Wajib diisi</span>
            </div>
            <div class="form-section-body">
                <div class="form-group-row">
                    <label for="nik" class="form-label">NIK <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="nik" name="nik" 
                                   placeholder="Masukkan NIK" required maxlength="16"
                                   value="<?php echo isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''; ?>">
                        </div>
                        <small class="form-text text-muted">Nomor Induk Kependudukan sesuai KTP (16 digit angka)</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="nama" class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nama" name="nama" 
                                   placeholder="Masukkan nama lengkap" required
                                   value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                        </div>
                        <small class="form-text text-muted">Nama akan digunakan untuk nama file KTP dan KK</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="no_telp" class="form-label">No. Telepon</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="no_telp" name="no_telp" 
                                   placeholder="Contoh: 0812-3456-7890"
                                   value="<?php echo isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="jenis_profesi" class="form-label">Jenis Profesi</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-briefcase"></i></span>
                            <select class="form-control" id="jenis_profesi" name="jenis_profesi">
                                <option value="">Pilih Profesi</option>
                                <option value="Guru Ngaji">Guru Ngaji</option>
                                <option value="Marbot">Marbot</option>
                                <option value="Penjaga Makam">Penjaga Makam</option>
                                </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Mengajar -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Data Mengajar</h3>
                <span class="section-badge">Wajib diisi</span>
            </div>
            <div class="form-section-body">
                <div class="form-group-row">
                    <label for="tempat_mengajar" class="form-label">Tempat Mengajar <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-school"></i></span>
                            <select class="form-control" id="tempat_mengajar" name="tempat_mengajar" required>
                                <option value="">Pilih Tempat Mengajar</option>
                                <option value="Rumah Pribadi">🏠 Rumah Pribadi</option>
                                <option value="TPA (Taman Pendidikan Al-Qur'an)">📖 TPA (Taman Pendidikan Al-Qur'an)</option>
                                <option value="MDTA (Madrasah Diniyah Takmiliyah)">🕌 MDTA (Madrasah Diniyah Takmiliyah)</option>
                                <option value="Pondok Pesantren">🕌 Pondok Pesantren</option>
                                <option value="Masjid/Musholla">🕌 Masjid/Musholla</option>
                                <option value="Yayasan">🏢 Yayasan</option>
                                <option value="Lainnya">📌 Lainnya</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="tempat_mengajar_detail" class="form-label">Detail Tempat</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-map-pin"></i></span>
                            <input type="text" class="form-control" id="tempat_mengajar_detail" name="tempat_mengajar_detail" 
                                   placeholder="Contoh: TPA Al-Ikhlas / Masjid Agung"
                                   value="<?php echo isset($_POST['tempat_mengajar_detail']) ? htmlspecialchars($_POST['tempat_mengajar_detail']) : ''; ?>">
                        </div>
                        <small class="form-text text-muted">Nama spesifik tempat mengajar (opsional)</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="bank" class="form-label">Bank</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-university"></i></span>
                            <select class="form-control" id="bank" name="bank">
                                <option value="">Pilih Bank</option>
                                <?php foreach ($bank_list as $b): ?>
                                    <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="no_rekening" class="form-label">No. Rekening</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-credit-card"></i></span>
                            <input type="text" class="form-control" id="no_rekening" name="no_rekening" 
                                   placeholder="Masukkan nomor rekening"
                                   value="<?php echo isset($_POST['no_rekening']) ? htmlspecialchars($_POST['no_rekening']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alamat -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Alamat</h3>
                <span class="section-badge">Opsional</span>
            </div>
            <div class="form-section-body">
                <div class="form-group-row">
                    <label for="kabupaten_id" class="form-label">Kabupaten</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-city"></i></span>
                            <select class="form-control" id="kabupaten_id" name="kabupaten_id">
                                <option value="">Pilih Kabupaten</option>
                                <?php 
                                mysqli_data_seek($kabupaten_list, 0);
                                while ($kab = mysqli_fetch_assoc($kabupaten_list)): 
                                ?>
                                    <option value="<?php echo $kab['id']; ?>">
                                        <?php echo htmlspecialchars($kab['nama']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="kecamatan_id" class="form-label">Kecamatan</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-map"></i></span>
                            <select class="form-control" id="kecamatan_id" name="kecamatan_id">
                                <option value="">Pilih Kecamatan</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="desa_id" class="form-label">Desa/Kelurahan</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-location-dot"></i></span>
                            <select class="form-control" id="desa_id" name="desa_id">
                                <option value="">Pilih Desa</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dokumen & Status -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-file-alt"></i>
                <h3>Dokumen & Status</h3>
                <span class="section-badge">Opsional</span>
            </div>
            <div class="form-section-body">
                <!-- Info Nama File -->
                <div class="form-group-row" style="background: #f0f7f3; padding: 12px 18px; border-radius: 8px; border-left: 4px solid #1a6e3a;">
                    <div style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap;">
                        <i class="fas fa-info-circle" style="color: #1a6e3a; font-size: 1.2rem;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #1a6e3a;">Format Nama File:</strong>
                            <span style="color: #555; font-size: 0.9rem; display: block;">
                                KTP: <code id="ktpFileNamePreview">NAMA_NIK_ktp_TIMESTAMP.jpg</code>
                            </span>
                            <span style="color: #555; font-size: 0.9rem; display: block;">
                                KK: <code id="kkFileNamePreview">NAMA_NIK_kk_TIMESTAMP.jpg</code>
                            </span>
                            <span style="color: #999; font-size: 0.8rem; display: block; margin-top: 5px;">
                                📁 Disimpan di: <code>assets/images/ktp/</code> dan <code>assets/images/kk/</code>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Upload KTP dengan Pilihan Kamera atau Galeri -->
                <div class="form-group-row">
                    <label for="ktp_file" class="form-label">Foto KTP</label>
                    <div class="form-control-wrap">
                        <div class="file-upload-wrapper">
                            <!-- Tombol Pilihan Upload -->
                            <div class="upload-options">
                                <button type="button" class="btn-upload btn-camera" data-target="ktp">
                                    <i class="fas fa-camera"></i> Kamera
                                </button>
                                <button type="button" class="btn-upload btn-gallery" data-target="ktp">
                                    <i class="fas fa-images"></i> Galeri
                                </button>
                                <button type="button" class="btn-upload btn-remove" data-target="ktp" style="display:none;">
                                    <i class="fas fa-times"></i> Hapus
                                </button>
                            </div>
                            
                            <!-- Area Preview -->
                            <div class="file-upload-area" id="ktpUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Pilih <strong>Kamera</strong> atau <strong>Galeri</strong> di atas</p>
                                <span class="file-types">JPG, PNG, WebP (Max 64MB setelah kompresi)</span>
                                <div class="file-info">
                                    <span>📷 Ambil foto langsung</span>
                                    <span>🖼️ Pilih dari galeri</span>
                                    <span>🔄 Auto kompresi</span>
                                </div>
                                <!-- Hidden input untuk file -->
                                <input type="file" class="file-input" id="ktp_file" name="ktp_file" 
                                       accept="image/*">
                                <input type="hidden" id="ktp_base64" name="ktp_base64" value="">
                            </div>
                            <div class="file-preview" id="ktpPreview"></div>
                            <div class="file-status" id="ktpStatus"></div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Klik tombol <strong>Kamera</strong> untuk foto langsung, atau <strong>Galeri</strong> untuk pilih dari album
                        </small>
                    </div>
                </div>
                
                <!-- Upload KK dengan Pilihan Kamera atau Galeri -->
                <div class="form-group-row">
                    <label for="kk_file" class="form-label">Foto KK</label>
                    <div class="form-control-wrap">
                        <div class="file-upload-wrapper">
                            <!-- Tombol Pilihan Upload -->
                            <div class="upload-options">
                                <button type="button" class="btn-upload btn-camera" data-target="kk">
                                    <i class="fas fa-camera"></i> Kamera
                                </button>
                                <button type="button" class="btn-upload btn-gallery" data-target="kk">
                                    <i class="fas fa-images"></i> Galeri
                                </button>
                                <button type="button" class="btn-upload btn-remove" data-target="kk" style="display:none;">
                                    <i class="fas fa-times"></i> Hapus
                                </button>
                            </div>
                            
                            <!-- Area Preview -->
                            <div class="file-upload-area" id="kkUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Pilih <strong>Kamera</strong> atau <strong>Galeri</strong> di atas</p>
                                <span class="file-types">JPG, PNG, WebP (Max 64MB setelah kompresi)</span>
                                <div class="file-info">
                                    <span>📷 Ambil foto langsung</span>
                                    <span>🖼️ Pilih dari galeri</span>
                                    <span>🔄 Auto kompresi</span>
                                </div>
                                <input type="file" class="file-input" id="kk_file" name="kk_file" 
                                       accept="image/*">
                                <input type="hidden" id="kk_base64" name="kk_base64" value="">
                            </div>
                            <div class="file-preview" id="kkPreview"></div>
                            <div class="file-status" id="kkStatus"></div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Klik tombol <strong>Kamera</strong> untuk foto langsung, atau <strong>Galeri</strong> untuk pilih dari album
                        </small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="status" class="form-label">Status</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-toggle-on"></i></span>
                            <select class="form-control" id="status" name="status">
                                <option value="aktif">✅ Aktif</option>
                                <option value="nonaktif">❌ Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="status_verifikasi" class="form-label">Status Verifikasi</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-check-circle"></i></span>
                            <select class="form-control" id="status_verifikasi" name="status_verifikasi">
                                <option value="pending">⏳ Pending</option>
                                <option value="disetujui">✅ Disetujui</option>
                                <option value="ditolak">❌ Ditolak</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="fas fa-save"></i> Simpan Data
            </button>
            <button type="reset" class="btn btn-secondary btn-lg" id="resetBtn">
                <i class="fas fa-undo"></i> Reset
            </button>
            <a href="guru.php" class="btn btn-danger btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>

<style>
    /* ============================================
       STYLE LENGKAP
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
    
    .form-wrapper {
        background: #fff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.06);
    }
    
    .form-section {
        background: #fafafa;
        border-radius: 12px;
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #eee;
        transition: all 0.3s ease;
    }
    .form-section:hover {
        border-color: #d4a847;
    }
    .form-section-header {
        background: #fff;
        padding: 14px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid #f0f0f0;
    }
    .form-section-header i {
        color: #d4a847;
        font-size: 1.1rem;
        width: 22px;
        text-align: center;
    }
    .form-section-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
        flex: 1;
    }
    .form-section-header .section-badge {
        font-size: 0.7rem;
        padding: 2px 12px;
        border-radius: 20px;
        background: #e8e8e8;
        color: #666;
        font-weight: 500;
    }
    .form-section-body {
        padding: 20px 25px;
    }
    
    .form-group-row {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #f5f5f5;
    }
    .form-group-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .form-label {
        min-width: 170px;
        max-width: 170px;
        padding-top: 10px;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
        text-align: left;
        flex-shrink: 0;
    }
    .form-label .required {
        color: #e74c3c;
        font-weight: 700;
        margin-left: 3px;
    }
    .form-control-wrap {
        flex: 1;
        min-width: 0;
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
    .form-control-wrap .form-control {
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
    .form-control-wrap .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    .form-control-wrap .form-control::placeholder {
        color: #aaa;
    }
    .form-control-wrap select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }
    .form-control-wrap .form-text {
        font-size: 0.8rem;
        color: #999;
        margin-top: 4px;
        display: block;
    }
    
    /* ============================================
       UPLOAD OPTIONS - TOMBOL KAMERA & GALERI
    ============================================ */
    .upload-options {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .btn-upload {
        padding: 8px 18px;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-upload i {
        font-size: 1rem;
    }
    
    .btn-camera {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: #fff;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.25);
    }
    .btn-camera:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.35);
    }
    
    .btn-gallery {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: #fff;
        box-shadow: 0 4px 12px rgba(46, 204, 113, 0.25);
    }
    .btn-gallery:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(46, 204, 113, 0.35);
    }
    
    .btn-remove {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: #fff;
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.25);
    }
    .btn-remove:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.35);
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
        transition: all 0.3s ease;
        background: #fdfcf8;
        position: relative;
        min-height: 100px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
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
    .file-upload-area.has-file {
        border-color: #2ecc71;
        background: #ecfdf5;
    }
    .file-upload-area.dragover {
        border-color: #1a6e3a;
        background: #f0f7f3;
    }
    
    .file-preview {
        margin-top: 5px;
    }
    .file-preview img {
        max-width: 150px;
        max-height: 150px;
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
    
    code {
        background: #f0f0f0;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #1a6e3a;
        word-break: break-all;
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
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .form-actions .btn {
        min-width: 140px;
        justify-content: center;
    }
    
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
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
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        0% { transform: scale(0.9); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
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
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .loading-content h3 {
        font-size: 1.2rem;
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    .loading-content p {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
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
        max-width: 420px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: popupIn 0.5s ease;
    }
    
    @keyframes popupIn {
        0% { transform: scale(0.8) rotate(-5deg); opacity: 0; }
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
    
    .popup-content h3 {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    .popup-content p {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 20px;
        line-height: 1.6;
        white-space: pre-line;
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
    
    @media (max-width: 768px) {
        .form-wrapper { padding: 20px 15px; }
        .form-group-row {
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        .form-label {
            min-width: auto;
            max-width: none;
            width: 100%;
            padding-top: 0;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .form-control-wrap { width: 100%; }
        .form-section-body { padding: 15px 18px; }
        .form-section-header { padding: 12px 18px; }
        .form-actions { flex-direction: column; }
        .form-actions .btn { width: 100%; justify-content: center; }
        .page-header { flex-direction: column; align-items: stretch; }
        .page-header-right .btn { flex: 1; justify-content: center; }
        .loading-content { padding: 30px 20px; }
        .popup-content { padding: 30px 20px; }
        .file-upload-area { padding: 15px; }
        .file-upload-area i { font-size: 1.5rem; }
        .file-info { flex-direction: column; gap: 3px; }
        .upload-options {
            flex-direction: row;
            justify-content: stretch;
        }
        .upload-options .btn-upload {
            flex: 1;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .upload-options .btn-upload {
            font-size: 0.7rem;
            padding: 6px 12px;
        }
        .upload-options .btn-upload i {
            font-size: 0.8rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // PREVIEW NAMA FILE DENGAN TIMESTAMP
    // ============================================
    function updateFileNamePreview() {
        const nik = document.getElementById('nik').value || 'NIK';
        const nama = document.getElementById('nama').value || 'NAMA';
        const cleanNama = nama.replace(/[^a-zA-Z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
        const now = new Date();
        const timestamp = now.getFullYear() + 
                         String(now.getMonth() + 1).padStart(2, '0') + 
                         String(now.getDate()).padStart(2, '0') + '_' +
                         String(now.getHours()).padStart(2, '0') + 
                         String(now.getMinutes()).padStart(2, '0') + 
                         String(now.getSeconds()).padStart(2, '0');
        
        document.getElementById('ktpFileNamePreview').textContent = 
            (cleanNama || 'NAMA') + '_' + nik + '_ktp_' + timestamp + '.jpg';
        document.getElementById('kkFileNamePreview').textContent = 
            (cleanNama || 'NAMA') + '_' + nik + '_kk_' + timestamp + '.jpg';
    }
    
    document.getElementById('nik').addEventListener('input', updateFileNamePreview);
    document.getElementById('nama').addEventListener('input', updateFileNamePreview);
    setInterval(updateFileNamePreview, 1000);
    updateFileNamePreview();

    // ============================================
    // LOAD KECAMATAN & DESA
    // ============================================
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

    // ============================================
    // KOMPRESI GAMBAR
    // ============================================
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
                    let mimeType = 'image/jpeg';
                    let outputQuality = quality;
                    if (file.type === 'image/png') {
                        mimeType = 'image/png';
                        outputQuality = 0.9;
                    } else if (file.type === 'image/webp') {
                        mimeType = 'image/webp';
                        outputQuality = quality;
                    }
                    canvas.toBlob(function(blob) {
                        if (blob) resolve(blob);
                        else reject(new Error('Gagal mengompresi gambar'));
                    }, mimeType, outputQuality);
                };
                img.onerror = function() { reject(new Error('Gagal memuat gambar')); };
                img.src = e.target.result;
            };
            reader.onerror = function() { reject(new Error('Gagal membaca file')); };
            reader.readAsDataURL(file);
        });
    }

    // ============================================
    // SETUP FILE UPLOAD - KAMERA & GALERI
    // ============================================
    function setupFileUpload(targetId) {
        const input = document.getElementById(targetId + '_file');
        const preview = document.getElementById(targetId + 'Preview');
        const area = document.getElementById(targetId + 'UploadArea');
        const status = document.getElementById(targetId + 'Status');
        const hidden = document.getElementById(targetId + '_base64');
        const removeBtn = document.querySelector('.btn-remove[data-target="' + targetId + '"]');
        
        if (!input || !preview || !area) return;
        
        // Tombol Kamera
        const cameraBtn = document.querySelector('.btn-camera[data-target="' + targetId + '"]');
        if (cameraBtn) {
            cameraBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Set capture ke environment (kamera belakang)
                input.setAttribute('capture', 'environment');
                input.click();
            });
        }
        
        // Tombol Galeri
        const galleryBtn = document.querySelector('.btn-gallery[data-target="' + targetId + '"]');
        if (galleryBtn) {
            galleryBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Hapus capture agar bisa pilih dari galeri
                input.removeAttribute('capture');
                // Set accept ke image/*
                input.setAttribute('accept', 'image/*');
                input.click();
            });
        }
        
        // Tombol Hapus
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Reset semua
                input.value = '';
                hidden.value = '';
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status';
                area.classList.remove('has-file');
                removeBtn.style.display = 'none';
                // Reset juga tombol kamera/galeri
                if (cameraBtn) cameraBtn.style.display = 'inline-flex';
                if (galleryBtn) galleryBtn.style.display = 'inline-flex';
            });
        }
        
        // Event change pada input file
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                area.classList.remove('has-file');
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status';
                hidden.value = '';
                if (removeBtn) removeBtn.style.display = 'none';
                if (cameraBtn) cameraBtn.style.display = 'inline-flex';
                if (galleryBtn) galleryBtn.style.display = 'inline-flex';
                return;
            }
            
            const maxSize = 200 * 1024 * 1024;
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            if (file.size > maxSize) {
                status.innerHTML = '❌ Ukuran file terlalu besar (' + fileSizeMB + 'MB). Maksimal 200MB.';
                status.className = 'file-status error';
                this.value = '';
                hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                status.innerHTML = '❌ Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP.';
                status.className = 'file-status error';
                this.value = '';
                hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            // Tentukan sumber (kamera atau galeri) berdasarkan atribut capture
            let sourceText = '🖼️ Galeri';
            if (input.hasAttribute('capture')) {
                sourceText = '📷 Kamera';
            }
            
            status.innerHTML = '⏳ Mengompresi gambar (' + fileSizeMB + 'MB)...';
            status.className = 'file-status loading';
            area.classList.add('has-file');
            
            // Sembunyikan tombol kamera/galeri, tampilkan tombol hapus
            if (cameraBtn) cameraBtn.style.display = 'none';
            if (galleryBtn) galleryBtn.style.display = 'none';
            if (removeBtn) removeBtn.style.display = 'inline-flex';
            
            compressImage(file, 1280, 720, 0.75)
                .then(compressedBlob => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    };
                    reader.readAsDataURL(compressedBlob);
                    
                    const base64Reader = new FileReader();
                    base64Reader.onload = function(e) {
                        hidden.value = e.target.result;
                        const compressedSizeMB = (compressedBlob.size / (1024 * 1024)).toFixed(2);
                        const savedPercent = ((1 - (compressedBlob.size / file.size)) * 100).toFixed(0);
                        
                        status.innerHTML = '✅ ' + sourceText + ' ' + fileSizeMB + 'MB → ' + compressedSizeMB + 'MB (hemat ' + savedPercent + '%)';
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
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                        hidden.value = e.target.result;
                        status.innerHTML = '✅ File asli (' + fileSizeMB + 'MB) - tanpa kompresi';
                        status.className = 'file-status success';
                    };
                    reader.readAsDataURL(file);
                });
        });
        
        // Drag and drop support
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                // Hapus capture agar bisa drag drop
                input.removeAttribute('capture');
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    // Inisialisasi upload untuk KTP dan KK
    setupFileUpload('ktp');
    setupFileUpload('kk');

    // ============================================
    // FORM SUBMIT
    // ============================================
    document.getElementById('formGuru').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nik = document.getElementById('nik').value.trim();
        const nama = document.getElementById('nama').value.trim();
        const tempat_mengajar = document.getElementById('tempat_mengajar').value;
        
        if (!nik || !nama || !tempat_mengajar) {
            showPopup('error', '⚠️ Gagal!', 'NIK, Nama, dan Tempat Mengajar wajib diisi!');
            return;
        }
        
        if (nik.length !== 16 || !/^\d+$/.test(nik)) {
            showPopup('error', '⚠️ Gagal!', 'NIK harus 16 digit angka!');
            return;
        }
        
        const formData = new FormData();
        formData.append('nik', nik);
        formData.append('nama', nama);
        formData.append('no_telp', document.getElementById('no_telp').value);
        formData.append('tempat_mengajar', tempat_mengajar);
        formData.append('tempat_mengajar_detail', document.getElementById('tempat_mengajar_detail').value);
        formData.append('jenis_profesi', document.getElementById('jenis_profesi').value);
        formData.append('bank', document.getElementById('bank').value);
        formData.append('no_rekening', document.getElementById('no_rekening').value);
        formData.append('kabupaten_id', document.getElementById('kabupaten_id').value);
        formData.append('kecamatan_id', document.getElementById('kecamatan_id').value);
        formData.append('desa_id', document.getElementById('desa_id').value);
        formData.append('status', document.getElementById('status').value);
        formData.append('status_verifikasi', document.getElementById('status_verifikasi').value);
        
        const ktpBase64 = document.getElementById('ktp_base64').value;
        const kkBase64 = document.getElementById('kk_base64').value;
        
        if (ktpBase64) formData.append('ktp_base64', ktpBase64);
        if (kkBase64) formData.append('kk_base64', kkBase64);
        
        showLoading('Menyimpan Data...', 'Mohon tunggu sebentar');
        updateProgress(0);
        
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
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            updateProgress(100);
            
            setTimeout(() => {
                hideLoading();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Data';
                
                if (data.status === 'success') {
                    showPopup('success', '✅ Berhasil!', 
                        data.message + '\n📁 KTP: ' + (data.ktp_file || '-') + '\n📁 KK: ' + (data.kk_file || '-'), 
                        true
                    );
                } else {
                    showPopup('error', '❌ Gagal!', data.message || 'Terjadi kesalahan saat menyimpan data.');
                }
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            hideLoading();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Data';
            showPopup('error', '❌ Error!', 'Terjadi kesalahan koneksi: ' + error.message);
        });
    });

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function showLoading(text, subText) {
        document.getElementById('loadingText').textContent = text || 'Menyimpan Data...';
        document.getElementById('loadingSubText').textContent = subText || 'Mohon tunggu sebentar';
        document.getElementById('loadingOverlay').classList.add('active');
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressText').textContent = '0%';
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
    
    function updateProgress(value) {
        const progress = Math.min(Math.max(value, 0), 100);
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressText').textContent = Math.round(progress) + '%';
    }
    
    function showPopup(type, title, message, redirect = false) {
        const modal = document.getElementById('popupModal');
        const icon = document.getElementById('popupIcon');
        const titleEl = document.getElementById('popupTitle');
        const messageEl = document.getElementById('popupMessage');
        const redirectBtn = document.getElementById('popupRedirect');
        const okBtn = document.getElementById('popupBtn');
        
        icon.className = 'popup-icon';
        if (type === 'success') {
            icon.classList.add('success');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else {
            icon.classList.add('error');
            icon.innerHTML = '<i class="fas fa-times-circle"></i>';
        }
        
        titleEl.textContent = title || (type === 'success' ? '✅ Berhasil!' : '❌ Gagal!');
        messageEl.textContent = message || 'Operasi selesai.';
        messageEl.style.whiteSpace = 'pre-line';
        
        if (redirect) {
            redirectBtn.style.display = 'inline-flex';
            okBtn.textContent = 'OK';
        } else {
            redirectBtn.style.display = 'none';
            okBtn.textContent = 'OK';
        }
        
        modal.classList.add('active');
    }
    
    window.closePopup = function() {
        document.getElementById('popupModal').classList.remove('active');
    }
    
    document.getElementById('popupModal').addEventListener('click', function(e) {
        if (e.target === this) closePopup();
    });

    // ============================================
    // RESET FORM
    // ============================================
    document.getElementById('resetBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('ktpPreview').innerHTML = '';
        document.getElementById('kkPreview').innerHTML = '';
        document.getElementById('ktpStatus').innerHTML = '';
        document.getElementById('kkStatus').innerHTML = '';
        document.getElementById('ktp_base64').value = '';
        document.getElementById('kk_base64').value = '';
        document.querySelectorAll('.file-upload-area').forEach(el => {
            el.classList.remove('has-file');
            el.classList.remove('dragover');
        });
        document.getElementById('ktp_file').value = '';
        document.getElementById('kk_file').value = '';
        document.getElementById('formGuru').reset();
        updateFileNamePreview();
        
        // Reset tombol upload
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.style.display = 'none';
        });
        document.querySelectorAll('.btn-camera').forEach(btn => {
            btn.style.display = 'inline-flex';
        });
        document.querySelectorAll('.btn-gallery').forEach(btn => {
            btn.style.display = 'inline-flex';
        });
    });
});
</script>

<?php include 'include/admin_footer.php'; ?>