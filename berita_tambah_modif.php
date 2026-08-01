<?php
// admin/berita_tambah.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH - Gunakan dirname(__DIR__) untuk mendapatkan root
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

$title = 'Tambah Berita';
$error = '';

// ============================================
// CEK APAKAH KOLOM KATEGORI ADA
// ============================================
$check_kategori = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'kategori'");
$has_kategori = ($check_kategori && mysqli_num_rows($check_kategori) > 0);

// ============================================
// PROSES FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = isset($_POST['judul']) ? mysqli_real_escape_string($conn, trim($_POST['judul'])) : '';
    $isi = isset($_POST['isi']) ? mysqli_real_escape_string($conn, $_POST['isi']) : '';
    $kategori = $has_kategori ? mysqli_real_escape_string($conn, $_POST['kategori']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'draft';
    $author = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin';
    
    $gambar_base64 = isset($_POST['gambar_base64']) ? $_POST['gambar_base64'] : '';
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'berita-' . time();
    }
    
    if (empty($judul)) {
        echo json_encode(['status' => 'error', 'message' => 'Judul berita wajib diisi!']);
        exit;
    }
    if (empty($isi)) {
        echo json_encode(['status' => 'error', 'message' => 'Isi berita wajib diisi!']);
        exit;
    }
    
    $gambar = '';
    if (!empty($gambar_base64)) {
        if (function_exists('save_base64_image')) {
            $gambar = save_base64_image($gambar_base64, 'berita', $slug, $judul);
        } else {
            // Fallback pemrosesan manual jika fungsi belum terdefinisi
            $data = explode(',', $gambar_base64);
            if (count($data) > 1) {
                $decoded = base64_decode($data[1]);
                $filename = time() . '_' . $slug . '.jpg';
                $target = $root_path . '/assets/images/berita/' . $filename;
                if (file_put_contents($target, $decoded)) {
                    $gambar = $filename;
                }
            }
        }
        
        if (!$gambar) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file gambar! Pastikan file gambar valid.']);
            exit;
        }
    }
    
    if ($has_kategori) {
        $query = "INSERT INTO berita SET 
            judul = '$judul',
            slug = '$slug',
            isi = '$isi',
            gambar = '$gambar',
            kategori = '$kategori',
            status = '$status',
            author = '$author',
            created_at = NOW()";
    } else {
        $query = "INSERT INTO berita SET 
            judul = '$judul',
            slug = '$slug',
            isi = '$isi',
            gambar = '$gambar',
            status = '$status',
            author = '$author',
            created_at = NOW()";
    }
    
    if (mysqli_query($conn, $query)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Berita berhasil ditambahkan!',
            'gambar' => $gambar
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan berita: ' . mysqli_error($conn)]);
        exit;
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-plus-circle"></i> Tambah Berita</h2>
        <p class="text-muted">Tambahkan berita atau informasi baru</p>
    </div>
    <div class="page-header-right">
        <a href="berita.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <i class="fas fa-newspaper"></i>
        </div>
        <h3 id="loadingText">Menyimpan Berita...</h3>
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

<!-- POPUP MODAL -->
<div class="popup-modal" id="popupModal">
    <div class="popup-content">
        <div class="popup-icon" id="popupIcon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 id="popupTitle">Berhasil!</h3>
        <p id="popupMessage">Berita berhasil ditambahkan.</p>
        <div class="popup-detail" id="popupDetail" style="display:none;">
            <div class="detail-item">
                <i class="fas fa-heading"></i>
                <span id="popupJudul">-</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-tag"></i>
                <span id="popupKategori">-</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-file-image"></i>
                <span id="popupGambar">-</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-check-circle"></i>
                <span id="popupStatus">-</span>
            </div>
        </div>
        <div class="popup-buttons">
            <button class="btn btn-primary" id="popupBtn" onclick="closePopup()">
                <i class="fas fa-check"></i> OK
            </button>
            <a href="berita.php" class="btn btn-secondary" id="popupRedirect" style="display:none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
            <button class="btn btn-primary" id="popupTambahLagi" style="display:none;" onclick="tambahLagi()">
                <i class="fas fa-plus"></i> Tambah Lagi
            </button>
        </div>
    </div>
</div>

<!-- FORM WRAPPER -->
<div class="form-wrapper">
    <form action="" method="POST" id="formBerita" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- KOLOM KIRI -->
            <div>
                <div style="background: #f0f7f3; padding: 12px 18px; border-radius: 8px; border-left: 4px solid #1a6e3a; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <i class="fas fa-info-circle" style="color: #1a6e3a; font-size: 1.2rem;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #1a6e3a;">Format Nama File Gambar:</strong>
                            <span style="color: #555; font-size: 0.9rem; display: block;">
                                <code id="gambarFileNamePreview">JUDUL_slug_timestamp.jpg</code>
                            </span>
                            <span style="color: #999; font-size: 0.8rem; display: block; margin-top: 5px;">
                                📁 Disimpan di: <code>assets/images/berita/</code>
                            </span>
                            <span style="color: #999; font-size: 0.8rem; display: block;">
                                🔗 URL: <code><?php 
                                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                    $host = $_SERVER['HTTP_HOST'];
                                    $path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                    echo $protocol . $host . $path . '/assets/images/berita/';
                                ?></code>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="judul" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Judul Berita <span style="color: #e74c3c;">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-heading"></i></span>
                        <input type="text" class="form-control" id="judul" name="judul" 
                               placeholder="Masukkan judul berita" required
                               style="padding-left: 45px;">
                    </div>
                    <small class="form-text text-muted">Judul akan digunakan untuk membuat slug URL</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="isi" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Isi Berita <span style="color: #e74c3c;">*</span>
                    </label>
                    <textarea class="form-control" id="isi" name="isi" rows="14" 
                              placeholder="Tulis isi berita di sini..." required
                              style="font-family: inherit; resize: vertical; min-height: 300px;"></textarea>
                </div>
            </div>
            
            <!-- KOLOM KANAN -->
            <div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gambar" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Gambar Berita
                    </label>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-area" id="gambarUploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau drag & drop untuk upload</p>
                            <span class="file-types">JPG, PNG, WebP (Max 10MB)</span>
                            <div class="file-info">
                                <span>📱 Bisa upload dari kamera HP</span>
                                <span>🔄 Auto kompresi ke 1200x800</span>
                                <span>📊 Ukuran disarankan 800x500px</span>
                            </div>
                            <input type="file" class="file-input" id="gambar" name="gambar" 
                                   accept="image/*" capture="environment">
                            <input type="hidden" id="gambar_base64" name="gambar_base64" value="">
                        </div>
                        <div class="file-preview" id="gambarPreview"></div>
                        <div class="file-status" id="gambarStatus"></div>
                    </div>
                    <small class="form-text text-muted">Upload gambar yang relevan dengan berita</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="kategori" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Kategori
                    </label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-tag"></i></span>
                        <input type="text" class="form-control" id="kategori" name="kategori" 
                               placeholder="Contoh: Kegiatan, Pengumuman"
                               style="padding-left: 45px;">
                    </div>
                    <small class="form-text text-muted">Kategori untuk mengelompokkan berita</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="status" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Status
                    </label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-toggle-on"></i></span>
                        <select class="form-control" id="status" name="status" style="padding-left: 45px;">
                            <option value="draft">📝 Draft</option>
                            <option value="publish" selected>✅ Publish</option>
                        </select>
                    </div>
                    <small class="form-text text-muted">Pilih "Publish" untuk menampilkan di website</small>
                </div>
                
                <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; margin-bottom: 4px;">
                        <i class="fas fa-link"></i> Preview URL
                    </label>
                    <code id="previewSlug" style="font-size: 0.8rem; word-break: break-all;">
                        <?php 
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'];
                        $path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                        echo $protocol . $host . $path . '/berita_detail.php?id=NEW';
                        ?>
                    </code>
                </div>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" style="flex: 1;">
                        <i class="fas fa-save"></i> Simpan Berita
                    </button>
                    <button type="reset" class="btn btn-secondary btn-lg" id="resetBtn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>
            
        </div>
    </form>
</div>

<!-- STYLE -->
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
    .page-header-left h2 { font-size: 1.4rem; color: #1a1a2e; margin-bottom: 3px; }
    .page-header-left h2 i { color: #d4a847; margin-right: 10px; }
    .page-header-left .text-muted { color: #999; font-size: 0.9rem; margin: 0; }
    .page-header-right { display: flex; gap: 10px; flex-wrap: wrap; }
    .form-wrapper { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 1rem; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; background: #fff; color: #333; }
    .form-control:focus { border-color: #1a6e3a !important; outline: none; box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08); }
    .form-text { font-size: 0.8rem; color: #999; margin-top: 4px; display: block; }
    .input-group { position: relative; display: flex; align-items: center; }
    .input-group .input-icon { position: absolute; left: 14px; color: #999; font-size: 1rem; z-index: 1; }
    .input-group .form-control { padding-left: 45px; }
    .input-group select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px; }
    .file-upload-wrapper { display: flex; flex-direction: column; gap: 10px; }
    .file-upload-area { border: 2px dashed #d4a847; border-radius: 10px; padding: 25px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #fdfcf8; position: relative; }
    .file-upload-area:hover { background: #f8f6f1; border-color: #1a6e3a; }
    .file-upload-area.dragover { border-color: #1a6e3a; background: #f0f7f3; }
    .file-upload-area.has-file { border-color: #2ecc71; background: #ecfdf5; }
    .file-upload-area i { font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 8px; }
    .file-upload-area p { margin: 0; color: #555; font-size: 0.9rem; font-weight: 500; }
    .file-upload-area .file-types { font-size: 0.75rem; color: #999; display: block; margin-top: 3px; }
    .file-upload-area .file-input { position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0; cursor: pointer; }
    .file-preview { margin-top: 5px; }
    .file-preview img { max-width: 200px; max-height: 150px; border-radius: 8px; border: 2px solid #e8e8e8; padding: 4px; background: #fff; object-fit: cover; }
    .file-status { font-size: 0.8rem; margin-top: 5px; padding: 4px 12px; border-radius: 4px; display: inline-block; }
    .file-status.success { color: #28a745; background: #d4edda; }
    .file-status.error { color: #dc3545; background: #f8d7da; }
    .file-status.loading { color: #f39c12; background: #fff3cd; animation: pulse 1s infinite; }
    .file-info { display: flex; gap: 15px; justify-content: center; font-size: 0.7rem; color: #888; margin-top: 5px; flex-wrap: wrap; }
    .file-info span { background: #f8f9fa; padding: 2px 10px; border-radius: 12px; }
    code { background: #f0f0f0; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; color: #1a6e3a; word-break: break-all; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; font-family: 'Poppins', sans-serif; }
    .btn-primary { background: linear-gradient(135deg, #1a6e3a, #2d8f52); color: #fff; }
    .btn-primary:hover { background: linear-gradient(135deg, #0e4a26, #1a6e3a); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3); }
    .btn-secondary { background: #95a5a6; color: #fff; }
    .btn-secondary:hover { background: #7f8c8d; color: #fff; transform: translateY(-2px); }
    .btn-lg { padding: 12px 32px; font-size: 1rem; border-radius: 10px; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
    .loading-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .loading-overlay.active { display: flex; }
    .loading-content { background: #fff; padding: 40px 50px; border-radius: 20px; text-align: center; max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
    .loading-spinner { position: relative; width: 80px; height: 80px; margin: 0 auto 20px; }
    .spinner-ring { width: 80px; height: 80px; border-radius: 50%; border: 4px solid #f0f0f0; border-top: 4px solid #1a6e3a; animation: spin 1s linear infinite; }
    .loading-spinner i { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2rem; color: #d4a847; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    .loading-content h3 { font-size: 1.2rem; color: #1a1a2e; margin-bottom: 5px; }
    .loading-content p { color: #666; font-size: 0.9rem; margin-bottom: 15px; }
    .progress-bar-container { width: 100%; height: 8px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin-bottom: 5px; }
    .progress-bar { height: 100%; background: linear-gradient(90deg, #1a6e3a, #2d8f52); border-radius: 10px; transition: width 0.3s ease; width: 0%; }
    .progress-text { font-size: 0.8rem; color: #999; }
    .loading-detail { margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px; font-size: 0.85rem; color: #555; }
    .loading-detail span { display: block; }
    .popup-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .popup-modal.active { display: flex; }
    .popup-content { background: #fff; padding: 40px 45px; border-radius: 20px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popupIn 0.5s ease; }
    @keyframes popupIn { 0% { transform: scale(0.8) rotate(-3deg); opacity: 0; } 100% { transform: scale(1) rotate(0deg); opacity: 1; } }
    .popup-icon { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2.5rem; }
    .popup-icon.success { background: #d4edda; color: #28a745; }
    .popup-icon.error { background: #f8d7da; color: #dc3545; }
    .popup-icon.warning { background: #fff3cd; color: #ffc107; }
    .popup-content h3 { font-size: 1.3rem; color: #1a1a2e; margin-bottom: 8px; }
    .popup-content p { color: #666; font-size: 0.95rem; margin-bottom: 15px; line-height: 1.6; white-space: pre-line; }
    .popup-detail { background: #f8f9fa; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; text-align: left; }
    .popup-detail .detail-item { display: flex; align-items: center; gap: 10px; padding: 4px 0; font-size: 0.85rem; color: #555; }
    .popup-detail .detail-item i { width: 20px; color: #d4a847; text-align: center; }
    .popup-detail .detail-item span { word-break: break-all; }
    .popup-buttons { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .popup-buttons .btn { min-width: 120px; justify-content: center; }
    @media (max-width: 992px) { .form-wrapper form > div { grid-template-columns: 1fr !important; } }
    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: stretch; }
        .page-header-right .btn { width: 100%; justify-content: center; }
        .form-wrapper { padding: 20px 15px !important; }
        .loading-content { padding: 30px 20px; }
        .popup-content { padding: 30px 20px; }
        .popup-buttons .btn { min-width: 100%; }
        .file-info { flex-direction: column; gap: 3px; }
    }
    @media (max-width: 480px) {
        .form-control { font-size: 0.85rem; padding: 10px 12px; }
        .input-group .input-icon { font-size: 0.85rem; left: 12px; }
        .input-group .form-control { padding-left: 38px; }
        .file-preview img { max-width: 100px; max-height: 100px; }
        .popup-content { padding: 25px 15px; }
    }
</style>

<!-- SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function updatePreview() {
        const judul = document.getElementById('judul').value || 'JUDUL';
        const cleanJudul = judul.replace(/[^a-zA-Z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
        const timestamp = 'timestamp';
        
        document.getElementById('gambarFileNamePreview').textContent = cleanJudul + '_' + timestamp + '.jpg';
        
        const slug = judul.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        if (slug) {
            const url = '<?php 
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                echo $protocol . $host . $path;
            ?>/berita_detail.php?id=NEW';
            document.getElementById('previewSlug').textContent = url;
        }
    }
    
    document.getElementById('judul').addEventListener('input', updatePreview);
    updatePreview();

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
                    
                    if (file.type === 'image/png') {
                        ctx.clearRect(0, 0, width, height);
                    }
                    
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
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Gagal mengompresi gambar'));
                        }
                    }, mimeType, outputQuality);
                };
                img.onerror = function() { reject(new Error('Gagal memuat gambar')); };
                img.src = e.target.result;
            };
            reader.onerror = function() { reject(new Error('Gagal membaca file')); };
            reader.readAsDataURL(file);
        });
    }

    const uploadArea = document.getElementById('gambarUploadArea');
    const fileInput = document.getElementById('gambar');
    const preview = document.getElementById('gambarPreview');
    const status = document.getElementById('gambarStatus');
    const hiddenBase64 = document.getElementById('gambar_base64');
    
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
            
            const maxSize = 50 * 1024 * 1024;
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            if (file.size > maxSize) {
                status.innerHTML = `❌ Ukuran file terlalu besar (${fileSizeMB}MB). Maksimal 50MB.`;
                status.className = 'file-status error';
                this.value = '';
                hiddenBase64.value = '';
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                status.innerHTML = `❌ Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP.`;
                status.className = 'file-status error';
                this.value = '';
                hiddenBase64.value = '';
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            status.innerHTML = `⏳ Mengompresi gambar (${fileSizeMB}MB)...`;
            status.className = 'file-status loading';
            uploadArea.classList.add('has-file');
            
            compressImage(file, 1200, 800, 0.8)
                .then(compressedBlob => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
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
                    status.innerHTML = '⚠️ Kompresi gagal, menggunakan file asli...';
                    status.className = 'file-status loading';
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        hiddenBase64.value = e.target.result;
                        status.innerHTML = `✅ File asli (${fileSizeMB}MB) - tanpa kompresi`;
                        status.className = 'file-status success';
                    };
                    reader.readAsDataURL(file);
                });
        });
    }
    
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('dragover'); });
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

    document.getElementById('formBerita').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const judul = document.getElementById('judul').value.trim();
        const isi = document.getElementById('isi').value.trim();
        
        if (!judul || !isi) {
            showPopup('error', '⚠️ Gagal!', 'Judul dan Isi berita wajib diisi!');
            return;
        }
        
        const formData = new FormData();
        formData.append('judul', judul);
        formData.append('isi', isi);
        formData.append('kategori', document.getElementById('kategori').value.trim());
        formData.append('status', document.getElementById('status').value);
        
        const gambarBase64 = document.getElementById('gambar_base64').value;
        if (gambarBase64) {
            formData.append('gambar_base64', gambarBase64);
        }
        
        showLoading('Menyimpan Berita...', 'Mohon tunggu sebentar');
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
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Berita';
                
                if (data.status === 'success') {
                    showPopupWithDetail(
                        'success', 
                        '✅ Berhasil!', 
                        'Berita berhasil ditambahkan!',
                        {
                            judul: judul,
                            kategori: document.getElementById('kategori').value.trim() || '-',
                            gambar: data.gambar || '-',
                            status: document.getElementById('status').value == 'publish' ? '✅ Publish' : '📝 Draft'
                        },
                        true
                    );
                } else {
                    showPopup('error', '❌ Gagal!', data.message || 'Terjadi kesalahan saat menyimpan berita.');
                }
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            hideLoading();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Berita';
            showPopup('error', '❌ Error!', 'Terjadi kesalahan koneksi: ' + error.message);
        });
    });

    function showLoading(text, subText) {
        document.getElementById('loadingText').textContent = text || 'Menyimpan Berita...';
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
        const tambahLagiBtn = document.getElementById('popupTambahLagi');
        const detailEl = document.getElementById('popupDetail');
        
        icon.className = 'popup-icon';
        detailEl.style.display = 'none';
        tambahLagiBtn.style.display = 'none';
        
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
        
        titleEl.textContent = title || (type === 'success' ? '✅ Berhasil!' : '❌ Gagal!');
        messageEl.textContent = message || 'Operasi selesai.';
        
        if (redirect) {
            redirectBtn.style.display = 'inline-flex';
            okBtn.textContent = 'OK';
        } else {
            redirectBtn.style.display = 'none';
            okBtn.textContent = 'OK';
        }
        
        modal.classList.add('active');
    }
    
    function showPopupWithDetail(type, title, message, detail, redirect = false) {
        const modal = document.getElementById('popupModal');
        const icon = document.getElementById('popupIcon');
        const titleEl = document.getElementById('popupTitle');
        const messageEl = document.getElementById('popupMessage');
        const redirectBtn = document.getElementById('popupRedirect');
        const okBtn = document.getElementById('popupBtn');
        const tambahLagiBtn = document.getElementById('popupTambahLagi');
        const detailEl = document.getElementById('popupDetail');
        
        icon.className = 'popup-icon';
        detailEl.style.display = 'block';
        tambahLagiBtn.style.display = 'inline-flex';
        redirectBtn.style.display = 'inline-flex';
        
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
        
        titleEl.textContent = title || (type === 'success' ? '✅ Berhasil!' : '❌ Gagal!');
        messageEl.textContent = message || 'Operasi selesai.';
        
        document.getElementById('popupJudul').textContent = detail.judul || '-';
        document.getElementById('popupKategori').textContent = detail.kategori || '-';
        document.getElementById('popupGambar').textContent = detail.gambar || '-';
        document.getElementById('popupStatus').textContent = detail.status || '-';
        
        okBtn.textContent = 'OK';
        modal.classList.add('active');
    }
    
    window.closePopup = function() {
        document.getElementById('popupModal').classList.remove('active');
    }
    
    window.tambahLagi = function() {
        closePopup();
        document.getElementById('formBerita').reset();
        document.getElementById('gambarPreview').innerHTML = '';
        document.getElementById('gambarStatus').innerHTML = '';
        document.getElementById('gambar_base64').value = '';
        document.getElementById('gambar').value = '';
        document.querySelector('.file-upload-area').classList.remove('has-file', 'dragover');
        updatePreview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.getElementById('judul').focus();
    }
    
    document.getElementById('popupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });

    document.getElementById('resetBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('formBerita').reset();
        document.getElementById('gambarPreview').innerHTML = '';
        document.getElementById('gambarStatus').innerHTML = '';
        document.getElementById('gambar_base64').value = '';
        document.getElementById('gambar').value = '';
        document.querySelector('.file-upload-area').classList.remove('has-file', 'dragover');
        updatePreview();
    });
    
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>