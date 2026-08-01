<?php
// member/profile.php - Halaman Profil Member dengan Upload Foto
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$member_id = $_SESSION['member_id'];
$member_nama = $_SESSION['member_nama'];
$member_nik = $_SESSION['member_nik'];

// ============================================
// CEK DAN BUAT DIREKTORI UPLOAD DI MEMBER
// ============================================
$upload_dir = dirname(__DIR__) . '/uploads/foto/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ============================================
// PROSES UPLOAD FOTO DARI PROFILE
// ============================================
$foto_error = '';
$foto_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_profil'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validasi
        if (!in_array($file['type'], $allowed_types)) {
            $foto_error = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } elseif ($file['size'] > $max_size) {
            $foto_error = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            // Generate nama file unik
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'foto_' . $member_id . '_' . date('Ymd_His') . '.' . $ext;
            $target_path = $upload_dir . $new_filename;
            
            // Upload ke member/uploads/foto
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Hapus foto lama
                $query_old = "SELECT foto_profil FROM guru_ngaji WHERE id = $member_id";
                $result_old = mysqli_query($conn, $query_old);
                $old_data = mysqli_fetch_assoc($result_old);
                if (!empty($old_data['foto_profil'])) {
                    $old_file = $upload_dir . $old_data['foto_profil'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                // Update database
                $update_foto = "UPDATE guru_ngaji SET foto_profil = '$new_filename' WHERE id = $member_id";
                if (mysqli_query($conn, $update_foto)) {
                    $foto_success = 'Foto profil berhasil diupload!';
                    header('Location: profile.php?success=1');
                    exit;
                } else {
                    $foto_error = 'Gagal memperbarui database: ' . mysqli_error($conn);
                }
            } else {
                $foto_error = 'Gagal mengupload file. Periksa permission folder.';
            }
        }
    } else {
        $foto_error = 'Pilih file foto terlebih dahulu.';
    }
}

// ============================================
// PROSES HAPUS FOTO
// ============================================
if (isset($_GET['delete_foto']) && $_GET['delete_foto'] == '1') {
    $query_old = "SELECT foto_profil FROM guru_ngaji WHERE id = $member_id";
    $result_old = mysqli_query($conn, $query_old);
    $old_data = mysqli_fetch_assoc($result_old);
    if (!empty($old_data['foto_profil'])) {
        $old_file = $upload_dir . $old_data['foto_profil'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    $update_foto = "UPDATE guru_ngaji SET foto_profil = NULL WHERE id = $member_id";
    if (mysqli_query($conn, $update_foto)) {
        $foto_success = 'Foto profil berhasil dihapus!';
        header('Location: profile.php?deleted=1');
        exit;
    }
}

// ============================================
// AMBIL DATA GURU
// ============================================
$query = "SELECT g.*, 
          k.nama as kabupaten_nama, 
          kec.nama as kecamatan_nama, 
          d.nama as desa_nama,
          u.nama_lengkap as verifikator_nama,
          u.username as verifikator_username
          FROM guru_ngaji g 
          LEFT JOIN kabupaten k ON g.kabupaten_id = k.id
          LEFT JOIN kecamatan kec ON g.kecamatan_id = kec.id 
          LEFT JOIN desa d ON g.desa_id = d.id
          LEFT JOIN users u ON g.verified_by = u.id
          WHERE g.id = $member_id";
$result = mysqli_query($conn, $query);
$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ============================================
// FUNGSI GET FOTO URL - DIPERBAIKI
// ============================================
function getFotoUrl($foto_profil, $nama) {
    if (!empty($foto_profil)) {
        // Path file di server: /member/uploads/foto/nama_foto.jpg
        $foto_path = dirname(__DIR__) . '/uploads/foto/' . $foto_profil;
        
        // Cek apakah file ada di server
        if (file_exists($foto_path)) {
            // URL yang benar: /member/uploads/foto/nama_foto.jpg
            return 'uploads/foto/' . $foto_profil;
        }
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=1a6e3a&color=fff&size=200';
}
$foto_url = getFotoUrl($guru['foto_profil'] ?? '', $guru['nama'] ?? 'Member');

// ============================================
// PROSES UPDATE PROFIL
// ============================================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $tempat_mengajar = mysqli_real_escape_string($conn, $_POST['tempat_mengajar'] ?? '');
    $tempat_mengajar_detail = mysqli_real_escape_string($conn, $_POST['tempat_mengajar_detail'] ?? '');
    $jenis_profesi = mysqli_real_escape_string($conn, $_POST['jenis_profesi'] ?? '');
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp'] ?? '');
    $bank = mysqli_real_escape_string($conn, $_POST['bank'] ?? '');
    $no_rekening = mysqli_real_escape_string($conn, $_POST['no_rekening'] ?? '');
    
    if (empty($nama) || empty($tempat_mengajar)) {
        $error_message = 'Nama dan Tempat Mengajar wajib diisi!';
    } else {
        $update_query = "UPDATE guru_ngaji SET 
            nama = '$nama',
            tempat_mengajar = '$tempat_mengajar',
            tempat_mengajar_detail = '$tempat_mengajar_detail',
            jenis_profesi = '$jenis_profesi',
            no_telp = '$no_telp',
            bank = '$bank',
            no_rekening = '$no_rekening',
            updated_at = NOW()
            WHERE id = $member_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = 'Profil berhasil diperbarui!';
            $_SESSION['member_nama'] = $nama;
            $result = mysqli_query($conn, $query);
            $guru = mysqli_fetch_assoc($result);
            $foto_url = getFotoUrl($guru['foto_profil'] ?? '', $guru['nama'] ?? 'Member');
        } else {
            $error_message = 'Gagal memperbarui profil: ' . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES GANTI PASSWORD
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Semua field password harus diisi!';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Password baru dan konfirmasi tidak sama!';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password minimal 6 karakter!';
    } else {
        $default_password = 'pgnilampung';
        $password_valid = false;
        
        if (empty($guru['password'])) {
            if ($current_password === $default_password) {
                $password_valid = true;
            }
        } else {
            if (password_verify($current_password, $guru['password']) || $current_password === $default_password) {
                $password_valid = true;
            }
        }
        
        if ($password_valid) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = "UPDATE guru_ngaji SET password = '$hashed_password' WHERE id = $member_id";
            if (mysqli_query($conn, $update_pass)) {
                $success_message = 'Password berhasil diubah!';
            } else {
                $error_message = 'Gagal mengubah password: ' . mysqli_error($conn);
            }
        } else {
            $error_message = 'Password saat ini salah!';
        }
    }
}

$title = 'Profil Saya';
include 'include/member_header.php';
?>

<style>
    .profile-page { max-width:1000px; margin:0 auto; }
    .profile-header-card { background:linear-gradient(135deg,#1a6e3a,#0e4a26); color:#fff; border-radius:var(--radius); padding:30px 35px; margin-bottom:25px; display:flex; align-items:center; gap:25px; flex-wrap:wrap; box-shadow:0 4px 20px rgba(26,110,58,0.15); }
    .profile-header-card .foto-wrapper { position:relative; flex-shrink:0; }
    .profile-header-card .foto-wrapper .avatar-large { width:120px; height:120px; border-radius:50%; border:4px solid rgba(255,255,255,0.3); object-fit:cover; background:rgba(255,255,255,0.1); display:block; }
    .profile-header-card .foto-wrapper .foto-upload-btn { position:absolute; bottom:0; right:0; background:#fff; color:var(--primary); border:none; border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center; font-size:1rem; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.15); transition:var(--transition); }
    .profile-header-card .foto-wrapper .foto-upload-btn:hover { transform:scale(1.1); background:var(--primary); color:#fff; }
    .profile-header-card .info h1 { font-size:1.5rem; margin:0 0 5px 0; }
    .profile-header-card .info p { opacity:0.8; margin:0; font-size:0.9rem; }
    .profile-header-card .info .nik-text { font-family:monospace; background:rgba(255,255,255,0.1); padding:2px 12px; border-radius:4px; display:inline-block; margin-top:5px; font-size:0.85rem; }
    .profile-header-card .status-badge { margin-left:auto; background:rgba(255,255,255,0.12); padding:8px 18px; border-radius:30px; font-weight:600; font-size:0.8rem; backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.08); }
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:15px; display:flex; align-items:center; gap:10px; font-size:0.9rem; }
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .alert .icon { font-size:1.1rem; }
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
    .modal.active { display:flex; }
    .modal-content { background:#fff; border-radius:20px; padding:35px; max-width:420px; width:100%; margin:20px; box-shadow:0 30px 80px rgba(0,0,0,0.2); animation:modalIn 0.3s ease; }
    @keyframes modalIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
    .modal-content h3 { text-align:center; margin:0 0 8px 0; font-size:1.2rem; color:var(--dark); }
    .modal-content .subtitle { text-align:center; color:var(--gray); font-size:0.85rem; margin-bottom:20px; }
    .modal-content .dropzone { border:2px dashed #e2e8f0; border-radius:16px; padding:40px 20px; text-align:center; cursor:pointer; transition:var(--transition); background:#fafafa; }
    .modal-content .dropzone:hover { border-color:var(--primary); background:#f5fdf5; }
    .modal-content .dropzone .icon-big { font-size:3rem; display:block; margin-bottom:10px; }
    .modal-content .dropzone p { margin:0; color:var(--gray); font-size:0.9rem; }
    .modal-content .dropzone .file-types { font-size:0.7rem; color:#b0b0b0; margin-top:5px; }
    .modal-content .dropzone input[type="file"] { display:none; }
    .modal-content .preview-img { display:none; max-width:100%; max-height:200px; margin:15px auto; border-radius:12px; object-fit:cover; }
    .modal-actions { display:flex; gap:12px; margin-top:20px; justify-content:center; flex-wrap:wrap; }
    .modal-actions .btn-primary { padding:10px 30px; background:linear-gradient(135deg,#1a6e3a,#2d8f52); color:#fff; border:none; border-radius:10px; font-weight:600; font-size:0.9rem; cursor:pointer; transition:var(--transition); font-family:'Poppins',sans-serif; }
    .modal-actions .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(26,110,58,0.2); }
    .modal-actions .btn-secondary { padding:10px 30px; background:#eef2f5; color:#495057; border:none; border-radius:10px; font-weight:600; font-size:0.9rem; cursor:pointer; transition:var(--transition); font-family:'Poppins',sans-serif; }
    .modal-actions .btn-secondary:hover { background:#e2e6ea; }
    .modal-actions .btn-danger { padding:10px 20px; background:#e74c3c; color:#fff; border:none; border-radius:10px; font-weight:600; font-size:0.85rem; cursor:pointer; transition:var(--transition); font-family:'Poppins',sans-serif; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .modal-actions .btn-danger:hover { background:#c0392b; }
    .btn-edit-mode { padding:6px 16px; background:var(--primary); color:#fff; border:none; border-radius:8px; font-weight:600; font-size:0.75rem; cursor:pointer; transition:var(--transition); font-family:'Poppins',sans-serif; text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
    .btn-edit-mode:hover { background:var(--primary-dark); }
    .grid-2col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .card { background:#fff; border-radius:var(--radius); border:1px solid #f0f2f5; overflow:hidden; }
    .card-header { padding:15px 20px; background:#fafbfc; border-bottom:1px solid #f0f2f5; display:flex; justify-content:space-between; align-items:center; }
    .card-header h3 { margin:0; font-size:0.95rem; font-weight:600; color:var(--dark); display:flex; align-items:center; gap:8px; }
    .card-header h3 .icon { color:var(--primary); }
    .card-body { padding:20px; }
    .info-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f5f6f8; }
    .info-item:last-child { border-bottom:none; }
    .info-item .label { color:var(--gray); font-size:0.8rem; }
    .info-item .value { color:var(--dark); font-weight:500; font-size:0.85rem; text-align:right; word-break:break-word; max-width:55%; }

    @media (max-width:768px) {
        .grid-2col { grid-template-columns:1fr; }
        .profile-header-card { padding:20px; flex-direction:column; text-align:center; }
        .profile-header-card .foto-wrapper .avatar-large { width:100px; height:100px; }
        .profile-header-card .info h1 { font-size:1.2rem; }
        .profile-header-card .status-badge { margin-left:0; }
        .modal-content { padding:25px 20px; }
        .info-item { flex-direction:column; align-items:flex-start; gap:2px; }
        .info-item .value { text-align:left; max-width:100%; }
    }
    @media (max-width:480px) { .profile-header-card .foto-wrapper .avatar-large { width:80px; height:80px; } }
</style>

<div class="profile-page">

    <?php if (isset($_GET['success']) || $foto_success): ?>
        <div class="alert alert-success"><span class="icon">✅</span> <?php echo $foto_success ?: 'Foto profil berhasil diupload!'; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) || ($foto_success && strpos($foto_success, 'dihapus') !== false)): ?>
        <div class="alert alert-success"><span class="icon">✅</span> <?php echo $foto_success ?: 'Foto profil berhasil dihapus!'; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><span class="icon">✅</span> <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><span class="icon">❌</span> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($foto_error): ?>
        <div class="alert alert-danger"><span class="icon">❌</span> <?php echo $foto_error; ?></div>
    <?php endif; ?>

    <div class="profile-header-card">
        <div class="foto-wrapper">
            <img src="<?php echo $foto_url; ?>" 
                 alt="Foto Profil" 
                 class="avatar-large"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($guru['nama'] ?? 'User'); ?>&background=1a6e3a&color=fff&size=200'">
            <button class="foto-upload-btn" onclick="openModal()" title="Upload Foto Profil">📷</button>
        </div>
        <div class="info">
            <h1><?php echo htmlspecialchars($guru['nama']); ?></h1>
            <p><span class="nik-text">NIK: <?php echo htmlspecialchars($guru['nik']); ?></span></p>
            <p style="margin-top:5px;"><span class="icon">📚</span> <?php echo htmlspecialchars($guru['tempat_mengajar']); ?>
                <?php if (!empty($guru['tempat_mengajar_detail'])): ?>
                    <span style="opacity:0.6;">(<?php echo htmlspecialchars($guru['tempat_mengajar_detail']); ?>)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="status-badge">
            <span class="icon">✅</span>
            <?php echo $guru['status_verifikasi'] === 'disetujui' ? 'Terverifikasi' : 'Menunggu Verifikasi'; ?>
        </div>
    </div>

    <div class="grid-2col">
        <div class="card">
            <div class="card-header">
                <h3><span class="icon">👤</span> Data Diri</h3>
                <a href="guru_edit.php?id=<?php echo $member_id; ?>" class="btn-edit-mode">✏️ Edit</a>
            </div>
            <div class="card-body">
                <div class="info-item"><span class="label">NIK</span><span class="value"><?php echo htmlspecialchars($guru['nik'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="label">Nama Lengkap</span><span class="value"><?php echo htmlspecialchars($guru['nama'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="label">Tempat Mengajar</span><span class="value"><?php echo htmlspecialchars($guru['tempat_mengajar'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="label">Profesi</span><span class="value"><?php echo htmlspecialchars($guru['jenis_profesi'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="label">No. Telepon</span><span class="value"><?php echo htmlspecialchars($guru['no_telp'] ?? '-'); ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><span class="icon">⚡</span> Akses Cepat</h3>
                <span style="font-size:0.7rem; color:var(--gray);">Menu</span>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <a href="guru_edit.php?id=<?php echo $member_id; ?>" style="display:flex; align-items:center; gap:12px; padding:14px 16px; background:#f8f9fa; border-radius:10px; text-decoration:none; color:var(--dark); transition:var(--transition); border:1px solid transparent;">
                        <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; background:#cce5ff; color:#004085;">✏️</div>
                        <div style="flex:1;"><h4 style="margin:0; font-size:0.85rem; font-weight:600;">Edit Profil</h4><p style="margin:0; font-size:0.7rem; color:var(--gray);">Perbarui data diri</p></div>
                        <span style="color:#ccc; font-size:0.7rem;">›</span>
                    </a>
                    <a href="cetak_kta.php" style="display:flex; align-items:center; gap:12px; padding:14px 16px; background:#f8f9fa; border-radius:10px; text-decoration:none; color:var(--dark); transition:var(--transition); border:1px solid transparent;">
                        <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; background:#e8d5f5; color:#6c3483;">🪪</div>
                        <div style="flex:1;"><h4 style="margin:0; font-size:0.85rem; font-weight:600;">Cetak KTA</h4><p style="margin:0; font-size:0.7rem; color:var(--gray);">Cetak Kartu Anggota</p></div>
                        <span style="color:#ccc; font-size:0.7rem;">›</span>
                    </a>
                    <a href="logout.php" style="display:flex; align-items:center; gap:12px; padding:14px 16px; background:#f8f9fa; border-radius:10px; text-decoration:none; color:var(--dark); transition:var(--transition); border:1px solid transparent;" onclick="return confirm('Yakin ingin logout?')">
                        <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; background:#f8d7da; color:#721c24;">🚪</div>
                        <div style="flex:1;"><h4 style="margin:0; font-size:0.85rem; font-weight:600;">Logout</h4><p style="margin:0; font-size:0.7rem; color:var(--gray);">Keluar dari sistem</p></div>
                        <span style="color:#ccc; font-size:0.7rem;">›</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL UPLOAD FOTO -->
<div class="modal" id="fotoModal">
    <div class="modal-content">
        <h3>📷 Upload Foto Profil</h3>
        <p class="subtitle">Pilih foto untuk profil Anda</p>
        <form method="POST" action="" enctype="multipart/form-data" id="fotoForm">
            <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
                <span class="icon-big">🖼️</span>
                <p>Klik untuk memilih foto</p>
                <span class="file-types">JPG, PNG, GIF • Maks. 2MB</span>
                <input type="file" id="fileInput" name="foto_profil" accept="image/*" onchange="previewImage(this)">
                <img id="preview" class="preview-img" alt="Preview">
            </div>
            <div class="modal-actions">
                <button type="submit" name="upload_foto" class="btn-primary">📤 Upload</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <?php if (!empty($guru['foto_profil'])): ?>
                    <a href="?delete_foto=1" class="btn-danger" onclick="return confirm('Yakin ingin menghapus foto profil?')">🗑️ Hapus</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() { document.getElementById('fotoModal').classList.add('active'); }
function closeModal() {
    document.getElementById('fotoModal').classList.remove('active');
    document.getElementById('preview').style.display = 'none';
    document.getElementById('fileInput').value = '';
    document.querySelector('.dropzone p').textContent = 'Klik untuk memilih foto';
}
document.getElementById('fotoModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
function previewImage(input) {
    const preview = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.src = e.target.result; preview.style.display = 'block'; document.querySelector('.dropzone p').textContent = input.files[0].name; };
        reader.readAsDataURL(input.files[0]);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() { alert.style.opacity = '0'; alert.style.transition = 'opacity 0.5s ease'; setTimeout(function() { alert.style.display = 'none'; }, 500); }, 5000);
    });
});
</script>

<?php include 'include/member_footer.php'; ?>