<?php
// registrasi.php - Halaman Registrasi Member PGNI Lampung
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Registrasi Member PGNI Lampung';
$error = '';
$success = false;

// ============================================================
// CEK MODE EDIT - Dari tombol perbaikan di cek_status.php
// ============================================================
$is_edit_mode = false;
$edit_nik = '';
$edit_no_telp = '';
$edit_data = null;

// Cek parameter GET untuk edit
if (isset($_GET['edit']) && $_GET['edit'] == 1 && isset($_GET['nik']) && !empty($_GET['nik'])) {
    $edit_nik = trim($_GET['nik']);
    $edit_no_telp = isset($_GET['no_telp']) ? trim($_GET['no_telp']) : '';
    
    // Cek apakah NIK terdaftar di database
    $nik_escaped = mysqli_real_escape_string($conn, $edit_nik);
    $query_check = "SELECT * FROM guru_ngaji WHERE nik = '$nik_escaped'";
    $result_check = mysqli_query($conn, $query_check);
    
    if ($result_check && mysqli_num_rows($result_check) > 0) {
        $edit_data = mysqli_fetch_assoc($result_check);
        $is_edit_mode = true;
        
        // Tampilkan pesan info
        $info_message = '🔧 Mode Perbaikan Data - Silakan perbarui data Anda.';
    } else {
        $error = 'NIK tidak ditemukan dalam database. Silakan daftar sebagai member baru.';
        $is_edit_mode = false;
    }
}

// ============================================================
// AMBIL DATA KABUPATEN
// ============================================================
$query_kabupaten = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $query_kabupaten);

// Daftar bank
$bank_list = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'Lampung', 'CIMB Niaga', 'Danamon', 'Permata', 'SeaBank','DANA','OVO', 'Lainnya'];

// ============================================================
// PROSES FORM (REGISTRASI BARU ATAU UPDATE)
// ============================================================
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
    $ktp_base64 = isset($_POST['ktp_base64']) ? $_POST['ktp_base64'] : '';
    $kk_base64 = isset($_POST['kk_base64']) ? $_POST['kk_base64'] : '';
    $is_update = isset($_POST['is_update']) ? (int)$_POST['is_update'] : 0;
    $original_nik = isset($_POST['original_nik']) ? mysqli_real_escape_string($conn, trim($_POST['original_nik'])) : '';
    
    // Validasi wajib
    if (empty($nik) || empty($nama) || empty($tempat_mengajar)) {
        $error = 'NIK, Nama Lengkap, dan Tempat Mengajar wajib diisi!';
    } elseif (strlen($nik) !== 16 || !is_numeric($nik)) {
        $error = 'NIK harus 16 digit angka!';
    } else {
        // ============================================================
        // CEK DUPLIKAT NIK (kecuali untuk mode update dengan NIK sama)
        // ============================================================
        $check_duplicate = true;
        if ($is_update == 1 && $nik === $original_nik) {
            $check_duplicate = false; // Update dengan NIK yang sama, tidak perlu cek duplikat
        }
        
        if ($check_duplicate) {
            $check = mysqli_query($conn, "SELECT id FROM guru_ngaji WHERE nik = '$nik'");
            if (mysqli_num_rows($check) > 0) {
                $error = 'NIK sudah terdaftar! Silakan gunakan NIK lain.';
            }
        }
        
        // ============================================================
        // PROSES UPLOAD FILE
        // ============================================================
        if (empty($error)) {
            // Proses KTP
            $ktp_file = '';
            if (!empty($ktp_base64)) {
                $ktp_file = save_base64_image($ktp_base64, 'ktp', $nik, $nama);
                if (!$ktp_file) {
                    $error = 'Gagal menyimpan file KTP! Pastikan file gambar valid.';
                }
            } elseif ($is_update == 1 && !empty($edit_data['ktp_file'])) {
                // Jika update dan tidak upload baru, gunakan file lama
                $ktp_file = $edit_data['ktp_file'];
            }

            // Proses KK
            $kk_file = '';
            if (empty($error) && !empty($kk_base64)) {
                $kk_file = save_base64_image($kk_base64, 'kk', $nik, $nama);
                if (!$kk_file) {
                    $error = 'Gagal menyimpan file KK! Pastikan file gambar valid.';
                }
            } elseif ($is_update == 1 && !empty($edit_data['kk_file'])) {
                // Jika update dan tidak upload baru, gunakan file lama
                $kk_file = $edit_data['kk_file'];
            }
        }
        
        // ============================================================
        // SIMPAN ATAU UPDATE DATA
        // ============================================================
        if (empty($error)) {
            $kabupaten_sql = $kabupaten_id > 0 ? $kabupaten_id : 'NULL';
            $kecamatan_sql = $kecamatan_id > 0 ? $kecamatan_id : 'NULL';
            $desa_sql = $desa_id > 0 ? $desa_id : 'NULL';
            
            if ($is_update == 1 && !empty($original_nik)) {
                // ============================================================
                // MODE UPDATE - Perbarui data di database
                // ============================================================
                $update_query = "UPDATE guru_ngaji SET
                    nik = '$nik',
                    nama = '$nama',
                    no_telp = '$no_telp',
                    tempat_mengajar = '$tempat_mengajar',
                    tempat_mengajar_detail = '$tempat_mengajar_detail',
                    jenis_profesi = '$jenis_profesi',
                    bank = '$bank',
                    no_rekening = '$no_rekening',
                    kabupaten_id = $kabupaten_sql,
                    kecamatan_id = $kecamatan_sql,
                    desa_id = $desa_sql,
                    ktp_file = '$ktp_file',
                    kk_file = '$kk_file',
                    status_verifikasi = 'pending',
                    updated_at = NOW()
                    WHERE nik = '$original_nik'";
                
                if (mysqli_query($conn, $update_query)) {
                    $success = true;
                    $is_edit_mode = false; // Matikan mode edit setelah sukses
                    $edit_data = null;
                    
                    // Ambil data terbaru untuk ditampilkan
                    $query_new = "SELECT * FROM guru_ngaji WHERE nik = '$nik'";
                    $result_new = mysqli_query($conn, $query_new);
                    if ($result_new && mysqli_num_rows($result_new) > 0) {
                        $guru_baru = mysqli_fetch_assoc($result_new);
                        $nik = $guru_baru['nik'];
                        $nama = $guru_baru['nama'];
                        $tempat_mengajar = $guru_baru['tempat_mengajar'];
                    }
                } else {
                    $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
                }
            } else {
                // ============================================================
                // MODE REGISTRASI BARU
                // ============================================================
                $status_verifikasi = 'pending';
                $status = 'aktif';
                $created_by = 0;

                $insert_query = "INSERT INTO guru_ngaji SET
                    nik = '$nik',
                    nama = '$nama',
                    no_telp = '$no_telp',
                    tempat_mengajar = '$tempat_mengajar',
                    tempat_mengajar_detail = '$tempat_mengajar_detail',
                    jenis_profesi = '$jenis_profesi',
                    bank = '$bank',
                    no_rekening = '$no_rekening',
                    kabupaten_id = $kabupaten_sql,
                    kecamatan_id = $kecamatan_sql,
                    desa_id = $desa_sql,
                    ktp_file = '$ktp_file',
                    kk_file = '$kk_file',
                    status = '$status',
                    status_verifikasi = '$status_verifikasi',
                    created_by = $created_by";

                if (mysqli_query($conn, $insert_query)) {
                    $success = true;
                } else {
                    $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
                }
            }
        }
    }
}

include $root_path . '/include/header.php';
?>

<!-- ============================================================
CSS STYLE - SAMA SEPERTI SEBELUMNYA
============================================================ -->
<style>
/* ============================================
   STYLE REGISTRASI
============================================ */
.registrasi-section {
    padding: 40px 0 60px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
    min-height: calc(100vh - 200px);
}

.registrasi-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

.registrasi-header {
    text-align: center;
    margin-bottom: 30px;
}

.registrasi-header h1 {
    font-size: 2rem;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.registrasi-header h1 span {
    color: #d4a847;
}

.registrasi-header p {
    color: #666;
    font-size: 1rem;
}

.registrasi-header .badge-registrasi {
    display: inline-block;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    padding: 5px 18px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 6px;
}

/* Card */
.registrasi-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    overflow: hidden;
}

.registrasi-card .card-body {
    padding: 30px 35px;
}

/* Upload Section */
.upload-section-top {
    background: linear-gradient(135deg, #f0f7f3, #e8f5e9);
    border-radius: 16px;
    padding: 25px 30px;
    margin-bottom: 30px;
    border: 2px solid #c8e6c9;
    position: relative;
}

.upload-section-top .upload-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.upload-section-top .upload-title i {
    font-size: 28px;
    color: #1a6e3a;
}

.upload-section-top .upload-title h3 {
    font-size: 1.1rem;
    color: #1a1a2e;
    margin: 0;
}

.upload-section-top .upload-title .badge-wajib {
    background: #dc3545;
    color: #fff;
    padding: 2px 12px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 8px;
}

.upload-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.upload-item {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.upload-item .upload-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.upload-item .upload-label .optional {
    font-weight: 400;
    color: #999;
    font-size: 0.7rem;
}

.upload-area-reg {
    border: 2px dashed #d4a847;
    border-radius: 10px;
    padding: 18px 15px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fdfcf8;
    position: relative;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.upload-area-reg:hover {
    border-color: #1a6e3a;
    background: #f8fff8;
}

.upload-area-reg.has-file {
    border-color: #28a745;
    background: #f0fff4;
}

.upload-area-reg i {
    font-size: 28px;
    color: #d4a847;
    margin-bottom: 4px;
}

.upload-area-reg p {
    margin: 0;
    color: #555;
    font-size: 0.8rem;
    font-weight: 500;
}

.upload-area-reg .file-types {
    font-size: 0.65rem;
    color: #999;
    display: block;
}

.upload-area-reg .file-input {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-area-reg.dragover {
    border-color: #1a6e3a;
    background: #f0f7f3;
}

.upload-preview {
    margin-top: 8px;
}

.upload-preview img {
    max-width: 120px;
    max-height: 120px;
    border-radius: 8px;
    border: 2px solid #e8e8e8;
    padding: 3px;
    background: #fff;
    object-fit: cover;
}

.upload-status {
    font-size: 0.75rem;
    margin-top: 4px;
    padding: 2px 10px;
    border-radius: 4px;
    display: inline-block;
}

.upload-status.success {
    color: #28a745;
    background: #d4edda;
}

.upload-status.error {
    color: #dc3545;
    background: #f8d7da;
}

.upload-status.loading {
    color: #f39c12;
    background: #fff3cd;
    animation: pulse 1s infinite;
}

.upload-actions {
    display: flex;
    gap: 6px;
    margin-top: 6px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-upload-mini {
    padding: 4px 12px;
    border: none;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-family: 'Poppins', sans-serif;
}

.btn-upload-mini i {
    font-size: 0.8rem;
}

.btn-camera {
    background: #3498db;
    color: #fff;
}

.btn-camera:hover {
    background: #2980b9;
}

.btn-gallery {
    background: #2ecc71;
    color: #fff;
}

.btn-gallery:hover {
    background: #27ae60;
}

.btn-remove {
    background: #e74c3c;
    color: #fff;
}

.btn-remove:hover {
    background: #c0392b;
}

.btn-scan-ktp {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border: none;
    padding: 5px 16px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-scan-ktp:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Form Sections */
.form-section-reg {
    margin-top: 20px;
}

.form-section-reg:first-of-type {
    margin-top: 0;
}

.form-section-reg .section-title {
    font-size: 1rem;
    color: #1a1a2e;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-reg .section-title span {
    display: inline-block;
    width: 4px;
    height: 22px;
    background: #d4a847;
    border-radius: 2px;
}

.form-section-reg .section-title i {
    color: #d4a847;
}

.form-group-reg {
    margin-bottom: 16px;
}

.form-group-reg label {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.form-group-reg label .required {
    color: #e74c3c;
    margin-left: 3px;
}

.form-group-reg .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 0.9rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: #fafafa;
    color: #333;
}

.form-group-reg .form-control:focus {
    border-color: #1a6e3a;
    outline: none;
    box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    background: #fff;
}

.form-group-reg .form-control::placeholder {
    color: #aaa;
}

.form-group-reg select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
}

.form-group-reg .form-text {
    font-size: 0.75rem;
    color: #999;
    margin-top: 3px;
    display: block;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Alert */
.alert-reg {
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}

.alert-reg.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-reg.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Success Card */
.success-card {
    text-align: center;
    padding: 30px 20px;
}

.success-card .success-icon {
    width: 70px;
    height: 70px;
    background: #d4edda;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 2rem;
    color: #28a745;
}

.success-card h2 {
    color: #1a1a2e;
    margin-bottom: 8px;
    font-size: 1.5rem;
}

.success-card p {
    color: #666;
    font-size: 0.95rem;
    max-width: 500px;
    margin: 0 auto 15px;
    line-height: 1.6;
}

.success-card .info-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    margin: 12px 0;
    text-align: left;
}

.success-card .info-box .info-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
    font-size: 0.85rem;
}

.success-card .info-box .info-item:last-child {
    border-bottom: none;
}

.success-card .info-box .info-item .label {
    color: #888;
    font-weight: 500;
}

.success-card .info-box .info-item .value {
    color: #333;
    font-weight: 600;
}

/* Terms */
.terms-box {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 12px 18px;
    margin-top: 10px;
    border-left: 4px solid #d4a847;
}

.terms-box h4 {
    font-size: 0.85rem;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.terms-box ul {
    margin: 0;
    padding-left: 18px;
    font-size: 0.8rem;
    color: #666;
    line-height: 1.7;
}

.terms-box ul li {
    list-style: disc;
}

.terms-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 12px;
    padding: 10px 14px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.terms-check.checked {
    border-color: #28a745;
    background: #f0fff4;
}

.terms-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #1a6e3a;
    flex-shrink: 0;
    cursor: pointer;
}

.terms-check label {
    font-size: 0.85rem;
    color: #555;
    line-height: 1.5;
    cursor: pointer;
}

.terms-check label a {
    color: #1a6e3a;
    text-decoration: underline;
}

.terms-check label a:hover {
    color: #d4a847;
}

/* Buttons */
.btn-reg {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 28px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}

.btn-reg-primary {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    width: 100%;
    justify-content: center;
}

.btn-reg-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}

.btn-reg-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

.btn-reg-primary.active {
    opacity: 1;
    cursor: pointer;
}

.btn-reg-secondary {
    background: #95a5a6;
    color: #fff;
}

.btn-reg-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.btn-reg-success {
    background: #28a745;
    color: #fff;
}

.btn-reg-success:hover {
    background: #1e7e34;
    transform: translateY(-2px);
}

.btn-reg-outline {
    background: transparent;
    color: #1a6e3a;
    border: 2px solid #1a6e3a;
}

.btn-reg-outline:hover {
    background: #1a6e3a;
    color: #fff;
    transform: translateY(-2px);
}

.form-actions-reg {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.form-actions-reg .btn-reg {
    flex: 1;
    justify-content: center;
    min-width: 120px;
}

/* Animasi */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .registrasi-section {
        padding: 20px 0 40px;
    }

    .registrasi-header h1 {
        font-size: 1.5rem;
    }

    .registrasi-card .card-body {
        padding: 18px;
    }

    .upload-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .upload-section-top {
        padding: 15px;
    }

    .upload-section-top .upload-title h3 {
        font-size: 0.95rem;
    }

    .form-row-2 {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .upload-actions {
        flex-wrap: wrap;
    }

    .btn-upload-mini {
        flex: 1;
        justify-content: center;
        min-width: 60px;
        font-size: 0.65rem;
        padding: 4px 8px;
    }

    .form-actions-reg {
        flex-direction: column;
    }

    .form-actions-reg .btn-reg {
        width: 100%;
    }

    .success-card .info-box .info-item {
        flex-direction: column;
        gap: 2px;
        padding: 6px 0;
    }

    .terms-check {
        padding: 8px 12px;
    }
    .terms-check label {
        font-size: 0.78rem;
    }
}

@media (max-width: 480px) {
    .registrasi-card .card-body {
        padding: 12px;
    }

    .upload-section-top {
        padding: 12px;
    }

    .upload-item {
        padding: 10px;
    }

    .upload-area-reg {
        padding: 12px;
        min-height: 60px;
    }

    .upload-area-reg i {
        font-size: 22px;
    }

    .upload-area-reg p {
        font-size: 0.7rem;
    }

    .form-group-reg .form-control {
        padding: 8px 12px;
        font-size: 0.82rem;
    }

    .btn-reg {
        font-size: 0.85rem;
        padding: 10px 16px;
    }

    .btn-scan-ktp {
        font-size: 0.7rem;
        padding: 4px 12px;
    }
}
</style>

<section class="registrasi-section">
    <div class="registrasi-container">
        <div class="registrasi-header">
            <h1><span><?php echo $is_edit_mode ? 'Perbarui' : 'Registrasi'; ?></span> Member PGNI</h1>
            <p><?php echo $is_edit_mode ? 'Perbarui data diri Anda sebagai anggota <strong>Persatuan Guru Ngaji Indonesia</strong> Provinsi Lampung' : 'Daftarkan diri Anda sebagai anggota <strong>Persatuan Guru Ngaji Indonesia</strong> Provinsi Lampung'; ?></p>
            <span class="badge-registrasi">
                <i class="fas <?php echo $is_edit_mode ? 'fa-edit' : 'fa-id-card'; ?>"></i> 
                <?php echo $is_edit_mode ? 'Mode Perbaikan Data' : 'Pendaftaran Gratis'; ?>
            </span>
        </div>

        <div class="registrasi-card">
            <div class="card-body">
                
                <?php if (!empty($error)): ?>
                    <div class="alert-reg error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($info_message)): ?>
                    <div class="alert-reg" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;">
                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($info_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <!-- SUCCESS -->
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2><?php echo $is_update ? '✅ Data Berhasil Diperbarui!' : '🎉 Pendaftaran Berhasil!'; ?></h2>
                        <p><?php echo $is_update ? 'Data Anda telah berhasil diperbarui. Tim admin akan melakukan verifikasi ulang dalam waktu 3x24 jam.' : 'Data Anda telah berhasil dikirim. Tim admin akan melakukan verifikasi dan menghubungi Anda dalam waktu 3x24 jam.'; ?></p>

                        <div class="info-box">
                            <div class="info-item">
                                <span class="label">NIK</span>
                                <span class="value"><?php echo htmlspecialchars($nik); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Nama Lengkap</span>
                                <span class="value"><?php echo htmlspecialchars($nama); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Tempat Mengajar</span>
                                <span class="value"><?php echo htmlspecialchars($tempat_mengajar); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Status</span>
                                <span class="value" style="color: #f39c12;">⏳ Menunggu Verifikasi Ulang</span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 15px;">
                            <a href="index.php" class="btn-reg btn-reg-success">
                                <i class="fas fa-home"></i> Beranda
                            </a>
                            <a href="cek_status.php" class="btn-reg btn-reg-primary">
                                <i class="fas fa-search"></i> Cek Status
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- FORM -->
                    <form action="" method="POST" id="formRegistrasi">
                        <input type="hidden" id="ktp_base64" name="ktp_base64" value="">
                        <input type="hidden" id="kk_base64" name="kk_base64" value="">
                        
                        <?php if ($is_edit_mode && $edit_data): ?>
                            <!-- Hidden field untuk mode update -->
                            <input type="hidden" name="is_update" value="1">
                            <input type="hidden" name="original_nik" value="<?php echo htmlspecialchars($edit_data['nik']); ?>">
                        <?php endif; ?>

                        <!-- ============================================
                        UPLOAD SECTION
                        ============================================ -->
                        <div class="upload-section-top">
                            <div class="upload-title">
                                <i class="fas fa-camera"></i>
                                <h3>Upload Dokumen <span class="badge-wajib">Wajib</span></h3>
                                <span style="font-size:0.75rem; color:#999; margin-left:auto;">
                                    <i class="fas fa-info-circle"></i> KTP & KK
                                </span>
                            </div>

                            <div class="upload-grid">
                                <!-- Upload KTP -->
                                <div class="upload-item">
                                    <div class="upload-label">
                                        <i class="fas fa-id-card" style="color:#1a6e3a;"></i> KTP
                                        <span class="optional">(wajib)</span>
                                        <?php if ($is_edit_mode && !empty($edit_data['ktp_file'])): ?>
                                            <span style="font-size:0.65rem;color:#28a745;margin-left:5px;">
                                                <i class="fas fa-check-circle"></i> Ada file lama
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="upload-area-reg <?php echo ($is_edit_mode && !empty($edit_data['ktp_file'])) ? 'has-file' : ''; ?>" id="ktpUploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p><?php echo ($is_edit_mode && !empty($edit_data['ktp_file'])) ? 'Klik untuk ganti KTP' : 'Upload KTP'; ?></p>
                                        <span class="file-types">JPG, PNG, WebP</span>
                                        <input type="file" class="file-input" id="ktp_file" accept="image/*">
                                    </div>
                                    <div class="upload-preview" id="ktpPreview">
                                        <?php if ($is_edit_mode && !empty($edit_data['ktp_file'])): ?>
                                            <img src="/pgnil/assets/images/ktp/<?php echo $edit_data['ktp_file']; ?>" alt="KTP" style="max-width:120px;max-height:120px;border-radius:8px;border:2px solid #e8e8e8;padding:3px;background:#fff;object-fit:cover;">
                                            <div style="font-size:0.7rem;color:#28a745;margin-top:4px;">
                                                <i class="fas fa-check-circle"></i> File KTP tersimpan
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="upload-status" id="ktpStatus"></div>
                                    <div class="upload-actions">
                                        <button type="button" class="btn-upload-mini btn-camera" data-target="ktp">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                        <button type="button" class="btn-upload-mini btn-gallery" data-target="ktp">
                                            <i class="fas fa-images"></i>
                                        </button>
                                        <button type="button" class="btn-upload-mini btn-remove" data-target="ktp" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload KK -->
                                <div class="upload-item">
                                    <div class="upload-label">
                                        <i class="fas fa-address-card" style="color:#1a6e3a;"></i> KK
                                        <span class="optional">(opsional)</span>
                                        <?php if ($is_edit_mode && !empty($edit_data['kk_file'])): ?>
                                            <span style="font-size:0.65rem;color:#28a745;margin-left:5px;">
                                                <i class="fas fa-check-circle"></i> Ada file lama
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="upload-area-reg <?php echo ($is_edit_mode && !empty($edit_data['kk_file'])) ? 'has-file' : ''; ?>" id="kkUploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p><?php echo ($is_edit_mode && !empty($edit_data['kk_file'])) ? 'Klik untuk ganti KK' : 'Upload KK'; ?></p>
                                        <span class="file-types">JPG, PNG, WebP</span>
                                        <input type="file" class="file-input" id="kk_file" accept="image/*">
                                    </div>
                                    <div class="upload-preview" id="kkPreview">
                                        <?php if ($is_edit_mode && !empty($edit_data['kk_file'])): ?>
                                            <img src="/pgnil/assets/images/kk/<?php echo $edit_data['kk_file']; ?>" alt="KK" style="max-width:120px;max-height:120px;border-radius:8px;border:2px solid #e8e8e8;padding:3px;background:#fff;object-fit:cover;">
                                            <div style="font-size:0.7rem;color:#28a745;margin-top:4px;">
                                                <i class="fas fa-check-circle"></i> File KK tersimpan
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="upload-status" id="kkStatus"></div>
                                    <div class="upload-actions">
                                        <button type="button" class="btn-upload-mini btn-camera" data-target="kk">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                        <button type="button" class="btn-upload-mini btn-gallery" data-target="kk">
                                            <i class="fas fa-images"></i>
                                        </button>
                                        <button type="button" class="btn-upload-mini btn-remove" data-target="kk" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Scan KTP -->
                            <div style="margin-top:12px; text-align:center;">
                                <button type="button" class="btn-scan-ktp" id="btnScanKTP">
                                    <i class="fas fa-magic"></i> Scan Otomatis dari KTP
                                </button>
                                <span style="font-size:0.7rem; color:#999; display:block; margin-top:4px;">
                                    <i class="fas fa-info-circle"></i> Isi otomatis NIK, Nama, dan Alamat dari foto KTP
                                </span>
                            </div>
                        </div>

                        <!-- ============================================
                        DATA PRIBADI
                        ============================================ -->
                        <div class="form-section-reg">
                            <div class="section-title">
                                <span></span>
                                <i class="fas fa-user-circle"></i> Data Pribadi
                            </div>

                            <div class="form-group-reg">
                                <label>NIK <span class="required">*</span></label>
                                <input type="text" class="form-control" id="nik" name="nik"
                                       placeholder="Masukkan NIK (16 digit angka)"
                                       maxlength="16" required
                                       value="<?php echo $is_edit_mode && $edit_data ? htmlspecialchars($edit_data['nik']) : (isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''); ?>"
                                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,16)">
                                <span class="form-text">Nomor Induk Kependudukan sesuai KTP (16 digit angka)</span>
                            </div>

                            <div class="form-group-reg">
                                <label>Nama Lengkap <span class="required">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama"
                                       placeholder="Masukkan nama lengkap sesuai KTP" required
                                       value="<?php echo $is_edit_mode && $edit_data ? htmlspecialchars($edit_data['nama']) : (isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''); ?>">
                            </div>

                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telp" name="no_telp"
                                           placeholder="Contoh: 0812-3456-7890"
                                           value="<?php echo $is_edit_mode && $edit_data ? htmlspecialchars($edit_data['no_telp']) : (isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''); ?>">
                                </div>

                                <div class="form-group-reg">
                                    <label>Jenis Profesi</label>
                                    <select class="form-control" id="jenis_profesi" name="jenis_profesi">
                                        <option value="">Pilih Profesi</option>
                                        <option value="Guru Ngaji" <?php echo ($is_edit_mode && $edit_data && $edit_data['jenis_profesi'] == 'Guru Ngaji') ? 'selected' : ''; ?>>Guru Ngaji</option>
                                        <option value="Marbot" <?php echo ($is_edit_mode && $edit_data && $edit_data['jenis_profesi'] == 'Marbot') ? 'selected' : ''; ?>>Marbot</option>
                                        <option value="Penjaga Makam" <?php echo ($is_edit_mode && $edit_data && $edit_data['jenis_profesi'] == 'Penjaga Makam') ? 'selected' : ''; ?>>Penjaga Makam</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================
                        DATA MENGAJAR
                        ============================================ -->
                        <div class="form-section-reg">
                            <div class="section-title">
                                <span></span>
                                <i class="fas fa-chalkboard-teacher"></i> Data Mengajar
                            </div>

                            <div class="form-group-reg">
                                <label>Tempat Mengajar <span class="required">*</span></label>
                                <select class="form-control" id="tempat_mengajar" name="tempat_mengajar" required>
                                    <option value="">Pilih Tempat Mengajar</option>
                                    <?php 
                                    $tempat_options = [
                                        'Rumah Pribadi' => '🏠 Rumah Pribadi',
                                        'TPA (Taman Pendidikan Al-Qur\'an)' => '📖 TPA',
                                        'MDTA (Madrasah Diniyah Takmiliyah)' => '🕌 MDTA',
                                        'Pondok Pesantren' => '🕌 Pondok Pesantren',
                                        'Masjid/Musholla' => '🕌 Masjid/Musholla',
                                        'Yayasan' => '🏢 Yayasan',
                                        'Lainnya' => '📌 Lainnya'
                                    ];
                                    foreach ($tempat_options as $value => $label):
                                        $selected = ($is_edit_mode && $edit_data && $edit_data['tempat_mengajar'] == $value) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-reg">
                                <label>Detail Tempat Mengajar</label>
                                <input type="text" class="form-control" id="tempat_mengajar_detail" name="tempat_mengajar_detail"
                                       placeholder="Contoh: TPA Al-Ikhlas / Masjid Agung"
                                       value="<?php echo $is_edit_mode && $edit_data ? htmlspecialchars($edit_data['tempat_mengajar_detail']) : (isset($_POST['tempat_mengajar_detail']) ? htmlspecialchars($_POST['tempat_mengajar_detail']) : ''); ?>">
                                <span class="form-text">Nama spesifik tempat mengajar (opsional)</span>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>Bank</label>
                                    <select class="form-control" id="bank" name="bank">
                                        <option value="">Pilih Bank</option>
                                        <?php foreach ($bank_list as $b): ?>
                                            <option value="<?php echo $b; ?>" <?php echo ($is_edit_mode && $edit_data && $edit_data['bank'] == $b) ? 'selected' : ''; ?>><?php echo $b; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group-reg">
                                    <label>No. Rekening</label>
                                    <input type="text" class="form-control" id="no_rekening" name="no_rekening"
                                           placeholder="Masukkan nomor rekening"
                                           value="<?php echo $is_edit_mode && $edit_data ? htmlspecialchars($edit_data['no_rekening'] ?? '') : htmlspecialchars($_POST['no_rekening'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- ============================================
                        ALAMAT
                        ============================================ -->
                        <div class="form-section-reg">
                            <div class="section-title">
                                <span></span>
                                <i class="fas fa-map-marker-alt"></i> Alamat
                            </div>

                            <div class="form-group-reg">
                                <label>Kabupaten</label>
                                <select class="form-control" id="kabupaten_id" name="kabupaten_id">
                                    <option value="">Pilih Kabupaten</option>
                                    <?php 
                                    mysqli_data_seek($kabupaten_list, 0);
                                    while ($kab = mysqli_fetch_assoc($kabupaten_list)):
                                        $selected = ($is_edit_mode && $edit_data && $edit_data['kabupaten_id'] == $kab['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $kab['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($kab['nama']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>Kecamatan</label>
                                    <select class="form-control" id="kecamatan_id" name="kecamatan_id">
                                        <option value="">Pilih Kecamatan</option>
                                        <?php if ($is_edit_mode && $edit_data && $edit_data['kecamatan_id'] > 0): ?>
                                            <?php
                                            $kec_query = "SELECT id, nama FROM kecamatan WHERE id = " . $edit_data['kecamatan_id'];
                                            $kec_result = mysqli_query($conn, $kec_query);
                                            if ($kec_result && $kec_row = mysqli_fetch_assoc($kec_result)):
                                            ?>
                                                <option value="<?php echo $kec_row['id']; ?>" selected><?php echo htmlspecialchars($kec_row['nama']); ?></option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group-reg">
                                    <label>Desa/Kelurahan</label>
                                    <select class="form-control" id="desa_id" name="desa_id">
                                        <option value="">Pilih Desa</option>
                                        <?php if ($is_edit_mode && $edit_data && $edit_data['desa_id'] > 0): ?>
                                            <?php
                                            $desa_query = "SELECT id, nama FROM desa WHERE id = " . $edit_data['desa_id'];
                                            $desa_result = mysqli_query($conn, $desa_query);
                                            if ($desa_result && $desa_row = mysqli_fetch_assoc($desa_result)):
                                            ?>
                                                <option value="<?php echo $desa_row['id']; ?>" selected><?php echo htmlspecialchars($desa_row['nama']); ?></option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================
                        SYARAT & KETENTUAN
                        ============================================ -->
                        <div class="terms-box">
                            <h4><i class="fas fa-gavel" style="color: #d4a847;"></i> Syarat & Ketentuan</h4>
                            <ul>
                                <li>Data yang diisi harus benar dan sesuai dengan dokumen resmi.</li>
                                <li>Pendaftaran ini adalah untuk menjadi anggota PGNI Provinsi Lampung.</li>
                                <li>Data akan diverifikasi oleh admin PGNI dalam waktu 3x24 jam.</li>
                                <li>Setelah terverifikasi, Anda akan mendapatkan Kartu Tanda Anggota (KTA) digital.</li>
                                <li>Keanggotaan ini gratis dan tidak dipungut biaya apapun.</li>
                            </ul>
                        </div>

                        <div class="terms-check" id="termsCheck">
                            <input type="checkbox" id="terms_agree" required <?php echo $is_edit_mode ? 'checked' : ''; ?>>
                            <label for="terms_agree">
                                Saya menyatakan bahwa data yang saya isi adalah benar dan saya menyetujui
                                <a href="#" onclick="return false;">syarat & ketentuan</a> yang berlaku.
                            </label>
                        </div>

                        <!-- ============================================
                        BUTTON
                        ============================================ -->
                        <div class="form-actions-reg">
                            <button type="submit" class="btn-reg btn-reg-primary" id="submitBtn" <?php echo $is_edit_mode ? '' : 'disabled'; ?>>
                                <i class="fas <?php echo $is_edit_mode ? 'fa-save' : 'fa-paper-plane'; ?>"></i> 
                                <?php echo $is_edit_mode ? 'Perbarui Data' : 'Daftar Sekarang'; ?>
                            </button>
                            <a href="<?php echo $is_edit_mode ? 'cek_status.php' : 'index.php'; ?>" class="btn-reg btn-reg-secondary">
                                <i class="fas fa-arrow-left"></i> <?php echo $is_edit_mode ? 'Kembali ke Cek Status' : 'Kembali'; ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<!-- ============================================
MODAL SCAN KTP (SAMA SEPERTI SEBELUMNYA)
============================================ -->
<div id="modalScanKTP" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:20px;overflow-y:auto;">
    <div style="max-width:550px;margin:30px auto;background:white;border-radius:20px;padding:30px;position:relative;">
        <button onclick="closeScanModal()" style="position:absolute;top:15px;right:20px;background:none;border:none;font-size:28px;color:#999;cursor:pointer;">&times;</button>
        <h3 style="color:#1a1a2e;margin-bottom:10px;"><i class="fas fa-magic" style="color:#667eea;"></i> Scan KTP Otomatis</h3>
        <p style="color:#666;font-size:0.85rem;margin-bottom:18px;">Upload foto KTP untuk mengisi data NIK, Nama, dan Alamat secara otomatis.</p>
        <div style="border:2px dashed #d4a847;border-radius:12px;padding:25px;text-align:center;background:#fdfcf8;position:relative;min-height:150px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <i class="fas fa-id-card" style="font-size:40px;color:#d4a847;margin-bottom:8px;"></i>
            <p style="color:#555;font-weight:500;">Upload foto KTP</p>
            <span style="font-size:0.7rem;color:#999;">JPG, PNG, WebP (Max 10MB)</span>
            <input type="file" id="scanImageInput" accept="image/*" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0;cursor:pointer;">
            <div id="scanPreview" style="margin-top:10px;"></div>
            <div id="scanStatus" style="margin-top:8px;font-size:0.8rem;"></div>
            <div id="scanLoading" style="display:none;margin-top:10px;">
                <div style="border:4px solid #f3f3f3;border-top:4px solid #667eea;border-radius:50%;width:35px;height:35px;animation:spin 1s linear infinite;margin:0 auto;"></div>
                <p style="color:#666;margin-top:8px;font-size:0.85rem;">Memproses OCR...</p>
            </div>
        </div>
        <div id="scanResult" style="display:none;margin-top:15px;background:#f8f9fa;border-radius:10px;padding:15px;">
            <h4 style="color:#1a6e3a;margin-bottom:8px;font-size:0.95rem;"><i class="fas fa-check-circle"></i> Data Terdeteksi:</h4>
            <div id="scanResultData" style="font-size:0.85rem;"></div>
            <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                <button onclick="applyScanResult()" style="flex:1;padding:10px 18px;background:linear-gradient(135deg,#1a6e3a,#2d8f52);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;font-size:0.85rem;">
                    <i class="fas fa-check"></i> Gunakan Data
                </button>
                <button onclick="closeScanModal()" style="padding:10px 18px;background:#95a5a6;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;font-size:0.85rem;">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
JAVASCRIPT - Gunakan File External
============================================ -->
<script src="js/registrasi.js?v=<?php echo time(); ?>"></script>

<?php include $root_path . '/include/footer.php'; ?>