<?php
// admin/berita_edit.php
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

$title = 'Edit Berita';
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: berita.php?error=notfound');
    exit;
}

// Ambil data berita
$query = "SELECT * FROM berita WHERE id = $id";
$result = mysqli_query($conn, $query);
$berita = mysqli_fetch_assoc($result);

if (!$berita) {
    header('Location: berita.php?error=notfound');
    exit;
}

// CEK TIPE DATA STATUS
$check_status_type = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'status'");
$status_col = mysqli_fetch_assoc($check_status_type);
$status_type = $status_col['Type'] ?? '';

$is_enum = strpos(strtolower($status_type), 'enum') !== false;
$is_int  = strpos(strtolower($status_type), 'int') !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = 'Ukuran gambar yang diupload terlalu besar melebihi batas server!';
    } else {
        $judul        = trim($_POST['judul'] ?? '');
        $isi          = mysqli_real_escape_string($conn, $_POST['isi'] ?? '');
        $kategori     = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
        $status_input = $_POST['status'] ?? 'draft';
        
        if ($is_enum) {
            $status = ($status_input == 'publish') ? 'publish' : 'draft';
        } elseif ($is_int) {
            $status = ($status_input == 'publish') ? 1 : 0;
        } else {
            $status = ($status_input == 'publish') ? 'publish' : 'draft';
        }
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
        $slug = trim($slug, '-');
        
        if (empty($judul) || empty($isi)) {
            $error = 'Judul dan Isi berita wajib diisi!';
        } else {
            $gambar = $berita['gambar'];
            
            if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1' && $gambar) {
                $old_path = $root_path . '/assets/images/berita/' . $gambar;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
                $gambar = '';
            }
            
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = upload_file($_FILES['gambar'], 'berita', ['jpg','jpeg','png','gif','webp'], 10485760);
                
                if ($upload['status']) {
                    if ($gambar) {
                        $old_path = $root_path . '/assets/images/berita/' . $gambar;
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                    }
                    $gambar = $upload['nama_file'];
                } else {
                    $error = 'Upload gambar gagal: ' . $upload['message'];
                }
            }
            
            if (empty($error)) {
                $judul_escaped  = mysqli_real_escape_string($conn, $judul);
                $slug_escaped   = mysqli_real_escape_string($conn, $slug);
                $status_escaped = mysqli_real_escape_string($conn, $status);
                
                $query = "UPDATE berita SET 
                    judul = '$judul_escaped',
                    slug = '$slug_escaped',
                    isi = '$isi',
                    gambar = '$gambar',
                    kategori = '$kategori',
                    status = '$status_escaped',
                    updated_at = NOW()
                    WHERE id = $id";
                
                if (mysqli_query($conn, $query)) {
                    header('Location: berita.php?msg=edit');
                    exit;
                } else {
                    $error = 'Gagal memperbarui berita: ' . mysqli_error($conn);
                }
            }
        }
    }
}

include 'include/admin_header.php';
?>

<style>
    * { direction: ltr !important; box-sizing: border-box; }
    body { direction: ltr; text-align: left; }
    .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eef2f5; }
    .page-header-left h2 { font-size: 1.5rem; font-weight: 700; color: #2c3e50; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px; text-align: left; }
    .page-header-left h2 i { color: #d4a847; }
    .page-header-left .text-muted { color: #7f8c8d; font-size: 0.9rem; margin: 0; text-align: left; }
    .page-header-right { display: flex; gap: 10px; flex-wrap: wrap; }
    .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; border: 1px solid transparent; direction: ltr; text-align: left; }
    .alert-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .form-wrapper { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); direction: ltr; text-align: left; }
    .form-wrapper form { direction: ltr; text-align: left; }
    .form-wrapper form > div { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
    .form-group { margin-bottom: 20px; text-align: left; direction: ltr; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; text-align: left; }
    .form-group label span { color: #e74c3c; }
    .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 1rem; font-family: inherit; transition: all 0.3s ease; background: #fff; color: #333; direction: ltr; text-align: left; box-sizing: border-box; }
    .form-control:focus { border-color: #1a6e3a; outline: none; box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08); }
    textarea.form-control { resize: vertical; min-height: 200px; font-family: inherit; direction: ltr; text-align: left; }
    select.form-control { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; background-size: 15px; padding-right: 40px; cursor: pointer; direction: ltr; text-align: left; }
    .file-upload-area { border: 2px dashed #d4a847; border-radius: 10px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #fdfcf8; position: relative; direction: ltr; }
    .file-upload-area:hover { background: #f8f6f1; border-color: #1a6e3a; }
    .file-upload-area.has-file { border-color: #2ecc71; background: #ecfdf5; }
    .file-upload-area i { font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 10px; }
    .file-upload-area p { margin: 0; color: #555; font-weight: 500; text-align: center; }
    .file-upload-area span { font-size: 0.8rem; color: #999; display: block; text-align: center; }
    .file-upload-area .file-input { position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .file-preview { margin-top: 10px; text-align: left; }
    .file-preview img { max-width: 200px; max-height: 150px; border-radius: 8px; border: 2px solid #e8e8e8; padding: 4px; background: #fff; object-fit: cover; }
    .form-text { display: block; margin-top: 5px; color: #999; font-size: 0.8rem; text-align: left; }
    .gambar-lama { margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; text-align: left; direction: ltr; }
    .gambar-lama img { max-width: 100%; max-height: 150px; border-radius: 6px; }
    .gambar-lama label { display: flex; align-items: center; gap: 8px; margin-top: 8px; cursor: pointer; font-size: 0.9rem; color: #e74c3c; font-weight: 500; text-align: left; }
    .gambar-lama label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #e74c3c; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; font-family: inherit; white-space: nowrap; }
    .btn:hover { transform: translateY(-2px); }
    .btn-primary { background: linear-gradient(135deg, #1a6e3a, #2d8f52); color: #fff; box-shadow: 0 4px 12px rgba(26, 110, 58, 0.15); flex: 1; }
    .btn-primary:hover { background: linear-gradient(135deg, #0e4a26, #1a6e3a); box-shadow: 0 6px 18px rgba(26, 110, 58, 0.25); }
    .btn-secondary { background: #95a5a6; color: #fff; box-shadow: 0 4px 12px rgba(149, 165, 166, 0.1); }
    .btn-secondary:hover { background: #7f8c8d; }
    .form-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; direction: ltr; text-align: left; }
    @media (max-width: 992px) { .form-wrapper form > div { grid-template-columns: 1fr !important; } }
    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: stretch; }
        .page-header-right .btn { width: 100%; justify-content: center; }
        .form-wrapper { padding: 20px 15px !important; }
        .form-actions { flex-direction: column; }
        .form-actions .btn { width: 100%; justify-content: center; }
        .file-upload-area { padding: 20px 15px !important; }
    }
    @media (max-width: 480px) {
        .page-header-left h2 { font-size: 1.2rem !important; }
        .form-control { font-size: 0.9rem; padding: 10px 14px; }
        .gambar-lama img { max-height: 120px; }
    }
</style>

<!-- KONTEN HALAMAN -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-edit"></i> Edit Berita</h2>
        <p class="text-muted">Perbarui konten berita yang sudah ada</p>
    </div>
    <div class="page-header-right">
        <a href="berita.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="form-wrapper">
    <form action="" method="POST" enctype="multipart/form-data">
        <div>
            <!-- KOLOM KIRI -->
            <div>
                <div class="form-group">
                    <label for="judul">Judul Berita <span>*</span></label>
                    <input type="text" class="form-control" id="judul" name="judul" 
                           placeholder="Masukkan judul berita" required
                           value="<?php echo htmlspecialchars($berita['judul']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="isi">Isi Berita <span>*</span></label>
                    <textarea class="form-control" id="isi" name="isi" rows="12" 
                              placeholder="Tulis isi berita di sini..." required><?php echo htmlspecialchars($berita['isi']); ?></textarea>
                </div>
            </div>
            
            <!-- KOLOM KANAN -->
            <div>
                <div class="form-group">
                    <label for="gambar">Gambar</label>
                    
                    <?php if ($berita['gambar']): ?>
                        <div class="gambar-lama">
                            <img src="../assets/images/berita/<?php echo htmlspecialchars($berita['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($berita['judul']); ?>">
                            <label>
                                <input type="checkbox" name="hapus_gambar" value="1">
                                <i class="fas fa-trash-alt"></i> Hapus gambar ini
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="file-upload-area" id="gambarUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Klik atau drag & drop untuk upload baru</p>
                        <span>JPG, PNG, GIF, WebP (Max 10MB)</span>
                        <input type="file" class="file-input" id="gambar" name="gambar" accept="image/*">
                    </div>
                    <div class="file-preview" id="gambarPreview"></div>
                    <small class="form-text">Ukuran gambar disarankan 800x500px</small>
                </div>
                
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <input type="text" class="form-control" id="kategori" name="kategori" 
                           placeholder="Contoh: Kegiatan, Pengumuman"
                           value="<?php echo htmlspecialchars($berita['kategori']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <?php
                        $current_status = $berita['status'];
                        if ($current_status === 1 || $current_status === '1') {
                            $selected_publish = 'selected';
                            $selected_draft = '';
                        } elseif ($current_status === 0 || $current_status === '0') {
                            $selected_publish = '';
                            $selected_draft = 'selected';
                        } else {
                            $selected_publish = ($current_status == 'publish') ? 'selected' : '';
                            $selected_draft = ($current_status == 'draft') ? 'selected' : '';
                        }
                        ?>
                        <option value="draft" <?php echo $selected_draft; ?>>📝 Draft</option>
                        <option value="publish" <?php echo $selected_publish; ?>>✅ Publish</option>
                    </select>
                    <small class="form-text" style="color: #999; font-size: 0.8rem; margin-top: 4px;">
                        Status saat ini: <strong><?php 
                            if ($berita['status'] === 1 || $berita['status'] === '1') {
                                echo 'Publish';
                            } elseif ($berita['status'] === 0 || $berita['status'] === '0') {
                                echo 'Draft';
                            } else {
                                echo ucfirst($berita['status']);
                            }
                        ?></strong>
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Berita
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('gambarUploadArea');
    const fileInput = document.getElementById('gambar');
    const preview = document.getElementById('gambarPreview');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                uploadArea.classList.add('has-file');
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            } else {
                uploadArea.classList.remove('has-file');
                preview.innerHTML = '';
            }
        });
    }
    
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#1a6e3a';
            this.style.background = '#f0f7f3';
        });
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d4a847';
            this.style.background = '#fdfcf8';
        });
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d4a847';
            this.style.background = '#fdfcf8';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script>

<?php include 'include/admin_footer.php'; ?>