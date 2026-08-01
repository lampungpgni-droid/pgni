<?php
// admin/berita_edit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH - Absolut Root
// ============================================
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login kembali.']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// Ambil dan validasi ID Berita
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: berita.php');
    exit;
}

// Ambil data berita lama
$query_tampil = mysqli_query($conn, "SELECT * FROM berita WHERE id = $id");
$berita = mysqli_fetch_assoc($query_tampil);

if (!$berita) {
    header('Location: berita.php');
    exit;
}

$title = 'Edit Berita';

// Cek kolom kategori di database
$check_kategori = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'kategori'");
$has_kategori = ($check_kategori && mysqli_num_rows($check_kategori) > 0);

// ============================================
// PROSES AJAX POST (UPDATE DATA)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    
    $judul    = isset($_POST['judul']) ? mysqli_real_escape_string($conn, trim($_POST['judul'])) : '';
    $isi      = isset($_POST['isi']) ? mysqli_real_escape_string($conn, $_POST['isi']) : '';
    $kategori = $has_kategori && isset($_POST['kategori']) ? mysqli_real_escape_string($conn, $_POST['kategori']) : '';
    $status   = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'draft';
    
    $gambar_base64 = $_POST['gambar_base64'] ?? '';
    $hapus_gambar  = isset($_POST['hapus_gambar']) ? (int)$_POST['hapus_gambar'] : 0;
    
    // Validasi Sisi Server
    if (empty($judul)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Judul berita wajib diisi!']);
        exit;
    }
    if (empty($isi)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Isi berita wajib diisi!']);
        exit;
    }
    
    // Slug generator
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    $slug = trim($slug, '-');
    if (empty($slug)) { $slug = 'berita-' . time(); }
    
    // Default gambar tetap menggunakan yang lama
    $gambar = $berita['gambar'];
    $target_dir = $root_path . '/uploads/berita/';
    
    // Aksi 1: Jika admin memilih untuk menghapus gambar lama tanpa upload baru
    if ($hapus_gambar == 1 && empty($gambar_base64)) {
        if (!empty($berita['gambar'])) {
            $file_lama = $target_dir . $berita['gambar'];
            if (file_exists($file_lama) && is_file($file_lama)) {
                @unlink($file_lama);
            }
        }
        $gambar = '';
    }
    
    // Aksi 2: Jika ada upload gambar baru (Base64)
    if (!empty($gambar_base64)) {
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $gambar_baru = save_base64_image($gambar_base64, 'berita', $slug, $judul);
        if ($gambar_baru) {
            // Hapus file fisik lama jika ada di server
            if (!empty($berita['gambar'])) {
                $file_lama = $target_dir . $berita['gambar'];
                if (file_exists($file_lama) && is_file($file_lama)) {
                    @unlink($file_lama);
                }
            }
            $gambar = $gambar_baru;
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses gambar baru. Pastikan format valid (JPG/PNG/WebP).']);
            exit;
        }
    }
    
    // Query UPDATE
    if ($has_kategori) {
        $query = "UPDATE berita SET judul='$judul', slug='$slug', isi='$isi', gambar='$gambar', kategori='$kategori', status='$status' WHERE id=$id";
    } else {
        $query = "UPDATE berita SET judul='$judul', slug='$slug', isi='$isi', gambar='$gambar', status='$status' WHERE id=$id";
    }
    
    if (mysqli_query($conn, $query)) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Berita berhasil diperbarui!']);
        exit;
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
}

// Load Header Tampilan
include $root_path . '/admin/include/admin_header.php';
?>

<!-- Kustomisasi CSS Modern UI -->
<style>
    .custom-card {
        border: none;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04) !important;
        overflow: hidden;
    }
    .custom-card-header {
        background: #ffffff;
        border-bottom: 1px solid #f1f3f7;
        padding: 20px 25px;
    }
    .custom-card-body {
        padding: 30px 25px;
    }
    .form-label-custom {
        font-size: 0.88rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
    }
    .form-control-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 12px 16px;
        font-size: 0.92rem;
        color: #334155;
        transition: all 0.2s ease-in-out;
    }
    .form-control-custom:focus {
        border-color: #1a6e3a;
        box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
        outline: none;
    }
    .dropzone-area {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .dropzone-area:hover {
        border-color: #1a6e3a;
        background: rgba(26, 110, 58, 0.02);
    }
    .dropzone-icon {
        font-size: 2.2rem;
        color: #94a3b8;
        margin-bottom: 10px;
    }
    .preview-container {
        position: relative;
        display: inline-block;
        max-width: 100%;
        margin-top: 15px;
    }
    .preview-image-wrapper {
        max-width: 380px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    .btn-remove-preview {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: transform 0.2s ease;
    }
    .btn-remove-preview:hover {
        transform: scale(1.1);
    }
    .btn-save-custom {
        background: #1a6e3a;
        border: none;
        color: white;
        font-weight: 500;
        padding: 12px 28px;
        border-radius: 10px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(26, 110, 58, 0.2);
    }
    .btn-save-custom:hover:not(:disabled) {
        background: #0e4a26;
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(26, 110, 58, 0.3);
    }
    .btn-cancel-custom {
        background: #f1f5f9;
        color: #64748b;
        border: none;
        font-weight: 500;
        padding: 12px 24px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    .btn-cancel-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }
    @media(max-width: 576px) {
        .custom-card-body { padding: 20px 15px; }
        .btn-action-group { width: 100%; display: flex; flex-direction: column; gap: 10px; }
        .btn-action-group button, .btn-action-group a { width: 100%; text-align: center; }
    }
</style>

<div class="container-fluid px-3 px-md-4">
    <div class="card custom-card">
        <div class="custom-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="m-0 font-weight-bold text-dark" style="font-size: 1.25rem;"><i class="fas fa-edit text-success me-2"></i> Edit Berita</h5>
                <small class="text-muted">Perbarui isi konten, kategori, status publikasi, atau gambar sampul berita</small>
            </div>
            <a href="berita.php" class="btn btn-cancel-custom py-2 px-3"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
        </div>
        
        <div class="custom-card-body">
            <form id="formBerita" autocomplete="off">
                <!-- Data Penunjang Aksi Hidden -->
                <input type="hidden" id="gambar_base64" name="gambar_base64">
                <input type="hidden" id="hapus_gambar" name="hapus_gambar" value="0">
                
                <div class="row">
                    <!-- Kolom Kiri: Formulir Utama -->
                    <div class="col-lg-8">
                        <div class="form-group mb-4">
                            <label for="judul" class="form-label-custom">Judul Berita <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-custom" id="judul" name="judul" value="<?php echo htmlspecialchars($berita['judul']); ?>" placeholder="Masukkan judul utama berita...">
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="isi" class="form-label-custom">Konten / Isi Berita <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-custom" id="isi" name="isi" rows="12"><?php echo htmlspecialchars($berita['isi']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan: Meta Pengaturan & Uploader -->
                    <div class="col-lg-4">
                        <?php if ($has_kategori): ?>
                        <div class="form-group mb-4">
                            <label for="kategori" class="form-label-custom">Kategori Konten</label>
                            <select class="form-control form-control-custom" id="kategori" name="kategori">
                                <option value="Informasi" <?php echo ($berita['kategori'] === 'Informasi') ? 'selected' : ''; ?>>📰 Informasi</option>
                                <option value="Kegiatan" <?php echo ($berita['kategori'] === 'Kegiatan') ? 'selected' : ''; ?>>📅 Kegiatan</option>
                                <option value="Pengumuman" <?php echo ($berita['kategori'] === 'Pengumuman') ? 'selected' : ''; ?>>📢 Pengumuman</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group mb-4">
                            <label for="status" class="form-label-custom">Opsi Publikasi</label>
                            <select class="form-control form-control-custom" id="status" name="status">
                                <option value="publish" <?php echo ($berita['status'] === 'publish') ? 'selected' : ''; ?>>🚀 Publish (Tampilkan)</option>
                                <option value="draft" <?php echo ($berita['status'] === 'draft') ? 'selected' : ''; ?>>📁 Draft (Arsip Internal)</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label class="form-label-custom">Gambar Cover Utama</label>
                            
                            <!-- Logika Cek Eksistensi File agar BEBAS Error 404 Get -->
                            <?php 
                            $is_image_exist = false;
                            $src_preview = '#';
                            if (!empty($berita['gambar'])) {
                                $path_fisik = $root_path . '/uploads/berita/' . $berita['gambar'];
                                if (file_exists($path_fisik) && is_file($path_fisik)) {
                                    $src_preview = '../uploads/berita/' . $berita['gambar'];
                                    $is_image_exist = true;
                                }
                            }
                            ?>
                            
                            <!-- Area Dropzone Uploader -->
                            <div class="dropzone-area" onclick="document.getElementById('file_gambar').click()">
                                <i class="<?php echo $is_image_exist ? 'fas fa-check-circle text-success' : 'fas fa-cloud-upload-alt'; ?> dropzone-icon" id="icon_upload_placeholder"></i>
                                <p class="mb-1 text-secondary font-weight-500" id="text_upload_placeholder" style="font-size: 0.85rem;">
                                    <?php echo $is_image_exist ? 'Gambar aktif termuat' : 'Klik untuk ubah file gambar'; ?>
                                </p>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Mendukung format JPG, PNG, atau WebP</small>
                                <input type="file" id="file_gambar" accept="image/*" style="display: none;">
                            </div>
                            
                            <!-- Wadah Preview Gambar Aman -->
                            <div class="text-center">
                                <div class="preview-container">
                                    <button type="button" class="btn-remove-preview" id="btnRemovePreview" style="<?php echo $is_image_exist ? 'display: flex;' : 'display: none;'; ?>" title="Hapus Gambar"><i class="fas fa-times"></i></button>
                                    <img id="preview_gambar" class="preview-image-wrapper" src="<?php echo $src_preview; ?>" style="<?php echo $is_image_exist ? 'display: block;' : 'display: none;'; ?>" alt="Preview Cover">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4" style="border-color: #f1f3f7;">
                
                <!-- Tombol Submit Aksi -->
                <div class="d-flex justify-content-end gap-2 btn-action-group">
                    <a href="berita.php" class="btn btn-cancel-custom text-center">Batalkan</a>
                    <button type="submit" id="submitBtn" class="btn btn-save-custom"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================================================
     JAVASCRIPT AREA 
     =================================================== -->
<script>
const fileGambar = document.getElementById('file_gambar');
const gambarBase64 = document.getElementById('gambar_base64');
const hapusGambar = document.getElementById('hapus_gambar');
const previewGambar = document.getElementById('preview_gambar');
const btnRemovePreview = document.getElementById('btnRemovePreview');
const dropzoneIcon = document.getElementById('icon_upload_placeholder');
const dropzoneText = document.getElementById('text_upload_placeholder');

// Pemrosesan Preview Gambar Terpilih Baru & Encode ke Base64
fileGambar.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('⚠️ Ukuran berkas gambar terlalu besar! Maksimal 5MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            gambarBase64.value = event.target.result;
            hapusGambar.value = "0"; // Reset flag hapus karena ada input baru
            previewGambar.src = event.target.result;
            
            previewGambar.style.display = 'block';
            btnRemovePreview.style.display = 'flex';
            dropzoneIcon.className = "fas fa-check-circle text-success dropzone-icon";
            dropzoneText.innerHTML = "Gambar baru siap diunggah!";
        }
        reader.readAsDataURL(file);
    }
});

// Aksi Menghapus/Mengosongkan Gambar
btnRemovePreview.addEventListener('click', function(e) {
    e.preventDefault();
    fileGambar.value = '';
    gambarBase64.value = '';
    hapusGambar.value = "1"; // Kirim sinyal ke PHP untuk mengosongkan kolom gambar di DB
    previewGambar.src = '#';
    previewGambar.style.display = 'none';
    btnRemovePreview.style.display = 'none';
    dropzoneIcon.className = "fas fa-cloud-upload-alt dropzone-icon";
    dropzoneText.innerHTML = "Gambar dikosongkan (Akan dihapus)";
});

// Proses Pengiriman Update via Ajax Fetch
document.getElementById('formBerita').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['isi']) {
        CKEDITOR.instances['isi'].updateElement();
    }
    
    const judulElement = document.getElementById('judul');
    const isiElement = document.getElementById('isi');
    const statusElement = document.getElementById('status');
    const kategoriElement = document.getElementById('kategori');
    
    const judul = judulElement ? judulElement.value.trim() : '';
    const isi = isiElement ? isiElement.value.trim() : '';
    const status = statusElement ? statusElement.value : 'draft';
    const kategori = kategoriElement ? kategoriElement.value : '';
    
    if (!judul) {
        alert('⚠️ Judul berita wajib diisi!');
        if(judulElement) judulElement.focus();
        return;
    }
    if (!isi) {
        alert('⚠️ Isi konten berita tidak boleh kosong!');
        if(isiElement) isiElement.focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('judul', judul);
    formData.append('isi', isi);
    formData.append('status', status);
    formData.append('gambar_base64', gambarBase64.value);
    formData.append('hapus_gambar', hapusGambar.value);
    if (kategori) {
        formData.append('kategori', kategori);
    }
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> Memperbarui...';
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (err) {
            console.error("Respon Malformed Server:", text);
            throw new Error('Format respon server tidak valid.');
        }
    })
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Perubahan';
        
        if (data.status === 'success') {
            alert('✅ Berhasil: ' + data.message);
            window.location.href = 'berita.php';
        } else {
            alert('❌ Gagal: ' + data.message);
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Perubahan';
        alert('❌ Terjadi gangguan sistem: ' + error.message);
    });
});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>