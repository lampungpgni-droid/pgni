<?php
// member/guru_edit.php - Edit Data Mandiri oleh Member/Guru
$title = "Edit Data Diri";
require_once 'include/member_header.php';

// Pastikan variabel koneksi database tersedia dari header
if (!isset($conn)) {
    die("Koneksi database tidak tersedia.");
}

$error_msg = '';
$success_msg = '';

// Ambil ID dari URL dan pastikan sama dengan ID user yang sedang login
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id !== intval($member_id)) {
    echo "<div class='alert alert-danger m-3'>Akses ditolak. Anda tidak diizinkan mengubah data member lain.</div>";
    require_once 'include/member_footer.php';
    exit;
}

// Ambil data member dari database tabel guru_ngaji
$query_member = "SELECT * FROM guru_ngaji WHERE id = $id";
$result_member = mysqli_query($conn, $query_member);
$member_data = mysqli_fetch_assoc($result_member);

if (!$member_data) {
    echo "<div class='alert alert-danger m-3'>Data member tidak ditemukan.</div>";
    require_once 'include/member_footer.php';
    exit;
}

// Ambil NIK dari database untuk kebutuhan penamaan file foto
$member_nik = $member_data['nik'] ?? 'tanpa_nik';

// 1. PROSES UPDATE DATA (Jika Form Disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitisasi Input
    $nama               = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $tempat_lahir       = mysqli_real_escape_string($conn, $_POST['tempat_lahir'] ?? '');
    $tanggal_lahir      = mysqli_real_escape_string($conn, $_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin      = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? '');
    $no_telp            = mysqli_real_escape_string($conn, $_POST['no_telp'] ?? '');
    
    // Alamat & Wilayah Tugas
    $alamat             = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $provinsi           = mysqli_real_escape_string($conn, $_POST['provinsi'] ?? '');
    $kabupaten_kota     = mysqli_real_escape_string($conn, $_POST['kabupaten_kota'] ?? '');
    $kecamatan          = mysqli_real_escape_string($conn, $_POST['kecamatan'] ?? '');
    $kelurahan_desa     = mysqli_real_escape_string($conn, $_POST['kelurahan_desa'] ?? '');
    
    // Data Lembaga Tempat Mengajar
    $nama_lembaga       = mysqli_real_escape_string($conn, $_POST['nama_lembaga'] ?? '');
    $jenis_lembaga      = mysqli_real_escape_string($conn, $_POST['jenis_lembaga'] ?? '');
    $alamat_lembaga     = mysqli_real_escape_string($conn, $_POST['alamat_lembaga'] ?? '');
    $lama_mengajar      = mysqli_real_escape_string($conn, $_POST['lama_mengajar'] ?? '');

    // Data tambahan
    $tempat_mengajar    = mysqli_real_escape_string($conn, $_POST['tempat_mengajar'] ?? '');
    $tempat_mengajar_detail = mysqli_real_escape_string($conn, $_POST['tempat_mengajar_detail'] ?? '');
    $jenis_profesi      = mysqli_real_escape_string($conn, $_POST['jenis_profesi'] ?? '');
    $bank               = mysqli_real_escape_string($conn, $_POST['bank'] ?? '');
    $no_rekening        = mysqli_real_escape_string($conn, $_POST['no_rekening'] ?? '');

    // Mengambil nama file foto lama
    $foto_lama = $member_data['foto_profil'] ?? '';
    $nama_file_foto = $foto_lama;

    // 2. PROSES UPLOAD FOTO PROFIL
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto_profil']['tmp_name'];
        $file_name = $_FILES['foto_profil']['name'];
        $file_size = $_FILES['foto_profil']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024;

        if (!in_array($file_ext, $allowed_extensions)) {
            $error_msg = "Format foto tidak valid. Hanya diperbolehkan JPG, JPEG, dan PNG.";
        } elseif ($file_size > $max_size) {
            $error_msg = "Ukuran foto terlalu besar. Maksimal 2MB.";
        } else {
            $nama_file_foto = 'foto_' . $member_nik . '_' . time() . '.' . $file_ext;
            $upload_dir = '../uploads/foto/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $nama_file_foto)) {
                if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
                    @unlink($upload_dir . $foto_lama);
                }
            } else {
                $error_msg = "Gagal mengunggah foto profil ke direktori server.";
                $nama_file_foto = $foto_lama;
            }
        }
    }

    // Validasi Sederhana Server-side
    if (empty($nama)) {
        $error_msg = "Nama Lengkap wajib diisi.";
    }

    // Jika tidak ada error validasi, lakukan UPDATE
    if (empty($error_msg)) {
        $update_query = "UPDATE guru_ngaji SET 
            nama = '$nama',
            tempat_lahir = '$tempat_lahir',
            tanggal_lahir = '$tanggal_lahir',
            jenis_kelamin = '$jenis_kelamin',
            no_telp = '$no_telp',
            alamat = '$alamat',
            provinsi = '$provinsi',
            kabupaten_kota = '$kabupaten_kota',
            kecamatan = '$kecamatan',
            kelurahan_desa = '$kelurahan_desa',
            nama_lembaga = '$nama_lembaga',
            jenis_lembaga = '$jenis_lembaga',
            alamat_lembaga = '$alamat_lembaga',
            lama_mengajar = '$lama_mengajar',
            tempat_mengajar = '$tempat_mengajar',
            tempat_mengajar_detail = '$tempat_mengajar_detail',
            jenis_profesi = '$jenis_profesi',
            bank = '$bank',
            no_rekening = '$no_rekening',
            foto_profil = '$nama_file_foto',
            updated_at = NOW()
            WHERE id = $id";

        if (mysqli_query($conn, $update_query)) {
            $success_msg = "Data diri Anda berhasil diperbarui!";
            $_SESSION['member_nama'] = $nama;
            
            // Refresh data terbaru dari database
            $result_member = mysqli_query($conn, "SELECT * FROM guru_ngaji WHERE id = $id");
            $member_data = mysqli_fetch_assoc($result_member);
        } else {
            $error_msg = "Gagal menyimpan perubahan ke database: " . mysqli_error($conn);
        }
    }
}

// Generate foto URL
$foto_url_edit = !empty($member_data['foto_profil']) ? '../uploads/foto/' . $member_data['foto_profil'] : 'https://ui-avatars.com/api/?name=' . urlencode($member_data['nama'] ?? 'Member') . '&background=1a6e3a&color=fff&size=200';
?>

<style>
/* ============================================
   EDIT GURU MODERN STYLE
============================================ */
:root {
    --primary: #1a6e3a;
    --primary-dark: #0e4a26;
    --primary-light: #2d8f52;
    --gold: #d4a847;
    --dark: #1a1a2e;
    --gray: #6b7280;
    --light-gray: #f8f9fa;
    --radius: 16px;
    --shadow: 0 4px 20px rgba(0,0,0,0.06);
    --shadow-hover: 0 8px 30px rgba(26,110,58,0.12);
    --transition: all 0.3s ease;
}

.edit-profile-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px 30px;
}

/* ===== PAGE HEADER ===== */
.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
}

.page-header-custom .title-section h3 {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0 0 2px 0;
}

.page-header-custom .title-section p {
    color: var(--gray);
    font-size: 0.85rem;
    margin: 0;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    background: var(--light-gray);
    color: var(--gray);
    border: 1px solid #e2e6ea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    transition: var(--transition);
}

.btn-back:hover {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(26,110,58,0.2);
}

/* ===== ALERT ===== */
.alert-custom {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
    border: 1px solid transparent;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-custom.success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-custom.error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

/* ===== FORM SECTIONS ===== */
.form-section {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid #f0f2f5;
    overflow: hidden;
    margin-bottom: 24px;
    transition: var(--transition);
}

.form-section:hover {
    box-shadow: var(--shadow-hover);
}

.form-section .section-header {
    padding: 16px 24px;
    background: linear-gradient(135deg, #f8fdf9, #f0f7f2);
    border-bottom: 1px solid #f0f2f5;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section .section-header .section-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.form-section .section-header h5 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
}

.form-section .section-body {
    padding: 22px 24px;
}

/* ===== FORM ELEMENTS ===== */
.form-group {
    margin-bottom: 16px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label-custom {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
    display: block;
}

.form-control-custom {
    width: 100%;
    padding: 10px 14px;
    font-size: 0.9rem;
    border: 2px solid #e8ecf1;
    border-radius: 10px;
    transition: var(--transition);
    background: #fafbfc;
    color: var(--dark);
}

.form-control-custom:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26,110,58,0.1);
    outline: none;
}

.form-control-custom::placeholder {
    color: #b0b8c4;
    font-size: 0.85rem;
}

textarea.form-control-custom {
    resize: vertical;
    min-height: 70px;
}

select.form-control-custom {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
    cursor: pointer;
}

.form-hint {
    font-size: 0.7rem;
    color: #b0b8c4;
    margin-top: 4px;
}

/* ===== SIDEBAR CARD ===== */
.sidebar-card {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid #f0f2f5;
    overflow: hidden;
    position: sticky;
    top: 90px;
    transition: var(--transition);
}

.sidebar-card:hover {
    box-shadow: var(--shadow-hover);
}

.sidebar-card .sidebar-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    text-align: center;
}

.sidebar-card .sidebar-header h5 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.sidebar-card .sidebar-body {
    padding: 20px;
    text-align: center;
}

/* Avatar Preview */
.avatar-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary);
    box-shadow: 0 4px 20px rgba(26,110,58,0.15);
    margin-bottom: 16px;
    background: #f0f2f5;
}

.avatar-preview:hover {
    transform: scale(1.02);
    transition: var(--transition);
}

/* File Input Styling */
.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-wrapper .file-input-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--light-gray);
    border: 2px dashed #dce0e5;
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.85rem;
    color: var(--gray);
}

.file-input-wrapper .file-input-label:hover {
    border-color: var(--primary);
    background: #f5fdf5;
}

.file-input-wrapper .file-input-label .icon {
    font-size: 1.1rem;
}

/* Info Box */
.info-box {
    background: var(--light-gray);
    border-radius: 10px;
    padding: 16px;
    text-align: left;
    border: 1px solid #e8ecf1;
}

.info-box .info-item {
    padding: 6px 0;
}

.info-box .info-item:not(:last-child) {
    border-bottom: 1px solid #e8ecf1;
}

.info-box .info-item .label {
    font-size: 0.7rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    display: block;
}

.info-box .info-item .value {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark);
    font-family: monospace;
}

/* Status Badge */
.status-badge-custom {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge-custom.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge-custom.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge-custom.rejected {
    background: #f8d7da;
    color: #721c24;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: var(--light-gray);
    border-radius: 0 0 var(--radius) var(--radius);
    flex-wrap: wrap;
}

.btn-reset {
    padding: 10px 24px;
    background: #eef2f5;
    color: #495057;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Poppins', sans-serif;
}

.btn-reset:hover {
    background: #e2e6ea;
}

.btn-save {
    padding: 10px 32px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26,110,58,0.25);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .sidebar-card {
        position: relative;
        top: 0;
        margin-bottom: 24px;
    }
}

@media (max-width: 768px) {
    .page-header-custom {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-section .section-body {
        padding: 16px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-reset, .btn-save {
        width: 100%;
        justify-content: center;
    }
    
    .avatar-preview {
        width: 120px;
        height: 120px;
    }
}

@media (max-width: 480px) {
    .form-section .section-header {
        padding: 12px 16px;
    }
    
    .form-section .section-body {
        padding: 12px;
    }
    
    .form-control-custom {
        padding: 8px 12px;
        font-size: 0.85rem;
    }
    
    .avatar-preview {
        width: 100px;
        height: 100px;
    }
}
</style>

<div class="edit-profile-page">
    
    <!-- Page Header -->
    <div class="page-header-custom">
        <div class="title-section">
            <h3>✏️ Perbarui Data Anggota</h3>
            <p>Pastikan data yang Anda masukkan sudah benar dan sesuai dokumen asli.</p>
        </div>
        <a href="profile.php" class="btn-back">⬅️ Kembali ke Profil</a>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert-custom error">
            <span>❌</span>
            <?php echo htmlspecialchars($error_msg); ?>
            <button type="button" style="margin-left:auto;background:none;border:none;font-size:1.2rem;cursor:pointer;color:inherit;" onclick="this.parentElement.style.display='none'">×</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-custom success">
            <span>✅</span>
            <?php echo htmlspecialchars($success_msg); ?>
            <button type="button" style="margin-left:auto;background:none;border:none;font-size:1.2rem;cursor:pointer;color:inherit;" onclick="this.parentElement.style.display='none'">×</button>
        </div>
    <?php endif; ?>

    <!-- Form Utama -->
    <form action="" method="POST" enctype="multipart/form-data" novalidate>
        <div class="row g-4">
            
            <!-- KOLOM KIRI: FOTO PROFIL -->
            <div class="col-lg-4">
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <h5>📷 Foto Profil</h5>
                    </div>
                    <div class="sidebar-body">
                        <img src="<?php echo $foto_url_edit; ?>" 
                             id="preview-avatar" 
                             alt="Avatar Preview" 
                             class="avatar-preview"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($member_data['nama'] ?? 'Member'); ?>&background=1a6e3a&color=fff&size=200'">
                        
                        <div class="file-input-wrapper mb-3">
                            <div class="file-input-label">
                                <span class="icon">📤</span>
                                <span id="file-label">Pilih Foto Baru</span>
                            </div>
                            <input type="file" id="foto_profil" name="foto_profil" accept="image/png, image/jpeg, image/jpg" onchange="previewImage(this)">
                        </div>
                        <div class="form-hint text-start">* Format: JPG, JPEG, PNG. Maksimal 2MB.</div>

                        <hr class="my-3">

                        <!-- Info Sistem -->
                        <div class="info-box">
                            <div class="info-item">
                                <span class="label">NIK</span>
                                <span class="value"><?php echo htmlspecialchars($member_data['nik'] ?? '-'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Status Verifikasi</span>
                                <span class="status-badge-custom <?php 
                                    $status_verif = strtolower($member_data['status_verifikasi'] ?? 'pending');
                                    echo ($status_verif === 'disetujui' || $status_verif === 'approved') ? 'approved' : (($status_verif === 'ditolak' || $status_verif === 'rejected') ? 'rejected' : 'pending'); 
                                ?>">
                                    <?php echo strtoupper($member_data['status_verifikasi'] ?? 'PENDING'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM INPUT -->
            <div class="col-lg-8">
                
                <!-- BAGIAN 1: IDENTITAS PRIBADI -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">1</div>
                        <h5>Identitas Pribadi</h5>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nama" class="form-label-custom">Nama Lengkap</label>
                                <input type="text" class="form-control-custom" id="nama" name="nama" value="<?php echo htmlspecialchars($member_data['nama'] ?? ''); ?>" placeholder="Nama lengkap sesuai KTP" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tempat_lahir" class="form-label-custom">Tempat Lahir</label>
                                <input type="text" class="form-control-custom" id="tempat_lahir" name="tempat_lahir" value="<?php echo htmlspecialchars($member_data['tempat_lahir'] ?? ''); ?>" placeholder="Kota/Kabupaten">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tanggal_lahir" class="form-label-custom">Tanggal Lahir</label>
                                <input type="date" class="form-control-custom" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($member_data['tanggal_lahir'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="jenis_kelamin" class="form-label-custom">Jenis Kelamin</label>
                                <select class="form-control-custom" id="jenis_kelamin" name="jenis_kelamin">
                                    <option value="">-- Pilih --</option>
                                    <option value="Laki-laki" <?php echo (($member_data['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo (($member_data['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="no_telp" class="form-label-custom">No. HP / WhatsApp</label>
                                <input type="tel" class="form-control-custom" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($member_data['no_telp'] ?? ''); ?>" placeholder="Contoh: 0812xxxxxxxx">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: ALAMAT DOMISILI -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">2</div>
                        <h5>Alamat Domisili</h5>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="alamat" class="form-label-custom">Alamat Lengkap</label>
                                <textarea class="form-control-custom" id="alamat" name="alamat" rows="2" placeholder="Masukkan alamat lengkap (jalan, dusun, RT/RW)"><?php echo htmlspecialchars($member_data['alamat'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="provinsi" class="form-label-custom">Provinsi</label>
                                <input type="text" class="form-control-custom" id="provinsi" name="provinsi" value="<?php echo htmlspecialchars($member_data['provinsi'] ?? 'Lampung'); ?>" placeholder="Provinsi">
                            </div>

                            <div class="col-md-6">
                                <label for="kabupaten_kota" class="form-label-custom">Kabupaten / Kota</label>
                                <input type="text" class="form-control-custom" id="kabupaten_kota" name="kabupaten_kota" value="<?php echo htmlspecialchars($member_data['kabupaten_kota'] ?? ''); ?>" placeholder="Kabupaten/Kota">
                            </div>

                            <div class="col-md-6">
                                <label for="kecamatan" class="form-label-custom">Kecamatan</label>
                                <input type="text" class="form-control-custom" id="kecamatan" name="kecamatan" value="<?php echo htmlspecialchars($member_data['kecamatan'] ?? ''); ?>" placeholder="Kecamatan">
                            </div>

                            <div class="col-md-6">
                                <label for="kelurahan_desa" class="form-label-custom">Kelurahan / Desa</label>
                                <input type="text" class="form-control-custom" id="kelurahan_desa" name="kelurahan_desa" value="<?php echo htmlspecialchars($member_data['kelurahan_desa'] ?? ''); ?>" placeholder="Kelurahan/Desa">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: DATA MENGAJAR & KEUANGAN -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">3</div>
                        <h5>Data Mengajar & Keuangan</h5>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="tempat_mengajar" class="form-label-custom">Tempat Mengajar</label>
                                <input type="text" class="form-control-custom" id="tempat_mengajar" name="tempat_mengajar" value="<?php echo htmlspecialchars($member_data['tempat_mengajar'] ?? ''); ?>" placeholder="Contoh: TPA, TPQ, Madrasah, Rumah Pribadi">
                            </div>

                            <div class="col-12">
                                <label for="tempat_mengajar_detail" class="form-label-custom">Detail Tempat Mengajar</label>
                                <input type="text" class="form-control-custom" id="tempat_mengajar_detail" name="tempat_mengajar_detail" value="<?php echo htmlspecialchars($member_data['tempat_mengajar_detail'] ?? ''); ?>" placeholder="Nama lembaga atau alamat detail">
                            </div>

                            <div class="col-md-6">
                                <label for="nama_lembaga" class="form-label-custom">Nama Lembaga</label>
                                <input type="text" class="form-control-custom" id="nama_lembaga" name="nama_lembaga" value="<?php echo htmlspecialchars($member_data['nama_lembaga'] ?? ''); ?>" placeholder="Contoh: TPA Al-Ikhlas">
                            </div>

                            <div class="col-md-6">
                                <label for="jenis_lembaga" class="form-label-custom">Jenis Lembaga</label>
                                <select class="form-control-custom" id="jenis_lembaga" name="jenis_lembaga">
                                    <option value="">-- Pilih --</option>
                                    <option value="TPA" <?php echo (($member_data['jenis_lembaga'] ?? '') === 'TPA') ? 'selected' : ''; ?>>TPA</option>
                                    <option value="TPQ" <?php echo (($member_data['jenis_lembaga'] ?? '') === 'TPQ') ? 'selected' : ''; ?>>TPQ</option>
                                    <option value="Madin" <?php echo (($member_data['jenis_lembaga'] ?? '') === 'Madin') ? 'selected' : ''; ?>>Madrasah Diniyah</option>
                                    <option value="Ponpes" <?php echo (($member_data['jenis_lembaga'] ?? '') === 'Ponpes') ? 'selected' : ''; ?>>Pondok Pesantren</option>
                                    <option value="Lainnya" <?php echo (($member_data['jenis_lembaga'] ?? '') === 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="alamat_lembaga" class="form-label-custom">Alamat Lembaga</label>
                                <input type="text" class="form-control-custom" id="alamat_lembaga" name="alamat_lembaga" value="<?php echo htmlspecialchars($member_data['alamat_lembaga'] ?? ''); ?>" placeholder="Alamat lengkap lembaga">
                            </div>

                            <div class="col-md-6">
                                <label for="lama_mengajar" class="form-label-custom">Lama Mengajar (Tahun)</label>
                                <input type="number" class="form-control-custom" id="lama_mengajar" name="lama_mengajar" min="0" placeholder="0" value="<?php echo htmlspecialchars($member_data['lama_mengajar'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="jenis_profesi" class="form-label-custom">Profesi</label>
                                <input type="text" class="form-control-custom" id="jenis_profesi" name="jenis_profesi" value="<?php echo htmlspecialchars($member_data['jenis_profesi'] ?? ''); ?>" placeholder="Guru Ngaji / Ustadz/Ustadzah">
                            </div>

                            <div class="col-md-6">
                                <label for="bank" class="form-label-custom">Bank</label>
                                <input type="text" class="form-control-custom" id="bank" name="bank" value="<?php echo htmlspecialchars($member_data['bank'] ?? ''); ?>" placeholder="Nama Bank">
                            </div>

                            <div class="col-md-6">
                                <label for="no_rekening" class="form-label-custom">No. Rekening</label>
                                <input type="text" class="form-control-custom" id="no_rekening" name="no_rekening" value="<?php echo htmlspecialchars($member_data['no_rekening'] ?? ''); ?>" placeholder="Nomor Rekening">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="action-buttons">
                    <button type="button" class="btn-reset" onclick="handleReset()">↺ Reset</button>
                    <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- JAVASCRIPT -->
<script>
// Preview Image ketika memilih foto baru
function previewImage(input) {
    var preview = document.getElementById('preview-avatar');
    var label = document.getElementById('file-label');
    
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        label.textContent = input.files[0].name;
    } else {
        label.textContent = 'Pilih Foto Baru';
    }
}

// Auto dismiss alerts dalam waktu 5 detik
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert-custom').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 5000);
    });
});

// Intersepsi Form submit untuk validasi JavaScript sederhana
document.querySelector('form').addEventListener('submit', function(e) {
    var nama = document.getElementById('nama');
    if (nama.value.trim() === '') {
        e.preventDefault();
        alert('Nama Lengkap wajib diisi!');
        nama.focus();
        nama.style.borderColor = '#e74c3c';
    }
});

// Fungsi Reset Form dengan Konfirmasi
function handleReset() {
    if (confirm('Batalkan semua perubahan yang baru Anda masukkan?')) {
        document.querySelector('form').reset();
        // Kembalikan foto ke asal
        document.getElementById('preview-avatar').src = "<?php echo $foto_url_edit; ?>";
        document.getElementById('file-label').textContent = 'Pilih Foto Baru';
    }
}
</script>

<?php
require_once 'include/member_footer.php';
?>