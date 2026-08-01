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

// Ambil data kabupaten untuk dropdown
$query_kabupaten = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $query_kabupaten);

// Daftar bank
$bank_list = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'Lampung', 'CIMB Niaga', 'Danamon', 'Permata', 'Lainnya'];

// Proses form
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
    
    // Validasi wajib
    if (empty($nik) || empty($nama) || empty($tempat_mengajar)) {
        $error = 'NIK, Nama Lengkap, dan Tempat Mengajar wajib diisi!';
    } elseif (strlen($nik) !== 16 || !is_numeric($nik)) {
        $error = 'NIK harus 16 digit angka!';
    } else {
        // Cek NIK duplicate
        $check = mysqli_query($conn, "SELECT id FROM guru_ngaji WHERE nik = '$nik'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'NIK sudah terdaftar! Silakan hubungi admin jika ini adalah kesalahan.';
        } else {
            // Proses KTP
            $ktp_file = '';
            if (!empty($ktp_base64)) {
                $ktp_file = save_base64_image($ktp_base64, 'ktp', $nik, $nama);
                if (!$ktp_file) {
                    $error = 'Gagal menyimpan file KTP! Pastikan file gambar valid.';
                }
            }
            
            // Proses KK
            $kk_file = '';
            if (empty($error) && !empty($kk_base64)) {
                $kk_file = save_base64_image($kk_base64, 'kk', $nik, $nama);
                if (!$kk_file) {
                    $error = 'Gagal menyimpan file KK! Pastikan file gambar valid.';
                }
            }
            
            // Jika tidak ada error, simpan
            if (empty($error)) {
                $status_verifikasi = 'pending';
                $status = 'aktif';
                $created_by = 0; // 0 = registrasi mandiri
                
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

<style>
/* ============================================
   STYLE REGISTRASI
============================================ */
.registrasi-section {
    padding: 60px 0;
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
    margin-bottom: 40px;
}

.registrasi-header h1 {
    font-size: 2.2rem;
    color: #1a1a2e;
    margin-bottom: 10px;
}

.registrasi-header h1 span {
    color: #d4a847;
}

.registrasi-header p {
    color: #666;
    font-size: 1.05rem;
}

.registrasi-header .badge-registrasi {
    display: inline-block;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 8px;
}

/* Card */
.registrasi-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    overflow: hidden;
}

.registrasi-card .card-body {
    padding: 35px 40px;
}

/* Steps */
.steps-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 35px;
    padding: 0 10px;
    position: relative;
}

.steps-indicator::before {
    content: '';
    position: absolute;
    top: 18px;
    left: 30px;
    right: 30px;
    height: 3px;
    background: #e0e0e0;
    z-index: 1;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    z-index: 2;
    flex: 1;
}

.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.step-item.active .step-number {
    background: #1a6e3a;
    color: #fff;
    box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
}

.step-item.done .step-number {
    background: #2d8f52;
    color: #fff;
}

.step-label {
    font-size: 0.7rem;
    color: #999;
    font-weight: 500;
    text-align: center;
}

.step-item.active .step-label {
    color: #1a6e3a;
}

/* Form */
.form-group-reg {
    margin-bottom: 20px;
}

.form-group-reg label {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.form-group-reg label .required {
    color: #e74c3c;
    margin-left: 3px;
}

.form-group-reg .form-control {
    width: 100%;
    padding: 11px 16px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 0.95rem;
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
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
    display: block;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* ============================================
   GOOGLE LENS BUTTON - OPEN LENS APP
============================================ */
.google-lens-wrapper {
    position: relative;
}

.google-lens-wrapper .form-control {
    padding-right: 120px;
}

.btn-google-lens {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
    background-size: 200% 200%;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.7rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    font-family: 'Poppins', sans-serif;
    z-index: 5;
    animation: googleLensPulse 3s ease-in-out infinite;
}

.btn-google-lens:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 4px 20px rgba(66, 133, 244, 0.5);
}

.btn-google-lens i {
    font-size: 0.9rem;
}

.btn-google-lens .lens-text {
    font-size: 0.6rem;
}

.btn-google-lens .lens-badge {
    background: rgba(255,255,255,0.3);
    border-radius: 4px;
    padding: 1px 6px;
    font-size: 0.5rem;
    font-weight: 600;
}

@keyframes googleLensPulse {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* Google Lens Result Popup */
.lens-result-popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(8px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

.lens-result-popup.active {
    display: flex;
}

.lens-result-popup .popup-content {
    background: #fff;
    border-radius: 24px;
    max-width: 500px;
    width: 100%;
    padding: 30px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

.lens-result-popup .popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.lens-result-popup .popup-header h3 {
    font-size: 1.2rem;
    color: #1a1a2e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lens-result-popup .popup-header h3 .lens-icon {
    background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 1.5rem;
}

.lens-result-popup .popup-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #999;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 5px 10px;
    border-radius: 8px;
}

.lens-result-popup .popup-close:hover {
    background: #f5f5f5;
    color: #333;
}

.lens-result-popup .popup-body {
    text-align: center;
}

.lens-result-popup .popup-body .instruction-box {
    background: #f0f7ff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    text-align: left;
    border-left: 4px solid #4285f4;
}

.lens-result-popup .popup-body .instruction-box .step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 6px 0;
    font-size: 0.9rem;
    color: #333;
}

.lens-result-popup .popup-body .instruction-box .step .num {
    background: #4285f4;
    color: #fff;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
}

.lens-result-popup .popup-body .lens-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e8e8e8;
    border-radius: 12px;
    font-size: 0.9rem;
    font-family: 'Poppins', sans-serif;
    resize: vertical;
    min-height: 100px;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.lens-result-popup .popup-body .lens-textarea:focus {
    border-color: #4285f4;
    outline: none;
    box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.1);
}

.lens-result-popup .popup-body .lens-textarea::placeholder {
    color: #aaa;
}

.lens-result-popup .popup-body .lens-result {
    display: none;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    margin-top: 15px;
    text-align: left;
}

.lens-result-popup .popup-body .lens-result.active {
    display: block;
}

.lens-result-popup .popup-body .lens-result .result-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}

.lens-result-popup .popup-body .lens-result .result-item:last-child {
    border-bottom: none;
}

.lens-result-popup .popup-body .lens-result .result-item .label {
    color: #888;
    font-weight: 500;
}

.lens-result-popup .popup-body .lens-result .result-item .value {
    color: #333;
    font-weight: 600;
}

.lens-result-popup .popup-body .lens-result .result-item .value.nik-value {
    color: #1a6e3a;
    font-family: 'Courier New', monospace;
    letter-spacing: 2px;
}

.lens-result-popup .popup-body .lens-result .result-item .value.name-value {
    color: #2c3e50;
}

.lens-result-popup .popup-body .lens-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    min-width: 100px;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-primary {
    background: linear-gradient(135deg, #4285f4, #34a853);
    color: #fff;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-success {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-success:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-secondary {
    background: #e8e8e8;
    color: #666;
}

.lens-result-popup .popup-body .lens-buttons .btn-lens-secondary:hover {
    background: #ddd;
}

.lens-result-popup .popup-body .open-lens-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 30px;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
    background-size: 200% 200%;
    color: #fff;
    margin-top: 10px;
    animation: googleLensPulse 3s ease-in-out infinite;
}

.lens-result-popup .popup-body .open-lens-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 30px rgba(66, 133, 244, 0.4);
}

.lens-result-popup .popup-body .open-lens-btn i {
    font-size: 1.2rem;
}

.lens-result-popup .popup-body .open-lens-btn .btn-sub {
    font-size: 0.7rem;
    font-weight: 400;
    opacity: 0.8;
}

/* Upload Section */
.upload-section {
    margin-top: 5px;
}

.upload-options {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.btn-upload-reg {
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

.btn-upload-reg i {
    font-size: 1rem;
}

.btn-camera-reg {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.25);
}
.btn-camera-reg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.35);
}

.btn-gallery-reg {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: #fff;
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.25);
}
.btn-gallery-reg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.35);
}

.btn-remove-reg {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: #fff;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.25);
}
.btn-remove-reg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.35);
}

.file-upload-area-reg {
    border: 2px dashed #d4a847;
    border-radius: 12px;
    padding: 25px 20px;
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

.file-upload-area-reg i {
    font-size: 2rem;
    color: #d4a847;
    display: block;
    margin-bottom: 5px;
}

.file-upload-area-reg p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
    font-weight: 500;
}

.file-upload-area-reg .file-types {
    font-size: 0.7rem;
    color: #999;
    display: block;
    margin-top: 3px;
}

.file-upload-area-reg .file-input {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0;
    cursor: pointer;
}

.file-upload-area-reg.has-file {
    border-color: #2ecc71;
    background: #ecfdf5;
}

.file-upload-area-reg.dragover {
    border-color: #1a6e3a;
    background: #f0f7f3;
}

.file-preview-reg {
    margin-top: 5px;
}

.file-preview-reg img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 8px;
    border: 2px solid #e8e8e8;
    padding: 4px;
    background: #fff;
    object-fit: cover;
}

.file-status-reg {
    font-size: 0.8rem;
    margin-top: 5px;
    padding: 4px 12px;
    border-radius: 4px;
    display: inline-block;
}

.file-status-reg.success {
    color: #28a745;
    background: #d4edda;
}
.file-status-reg.error {
    color: #dc3545;
    background: #f8d7da;
}
.file-status-reg.loading {
    color: #f39c12;
    background: #fff3cd;
    animation: pulse 1s infinite;
}

.file-info-reg {
    display: flex;
    gap: 15px;
    justify-content: center;
    font-size: 0.7rem;
    color: #888;
    margin-top: 5px;
    flex-wrap: wrap;
}
.file-info-reg span {
    background: #f8f9fa;
    padding: 2px 10px;
    border-radius: 12px;
}

/* Alert */
.alert-reg {
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 500;
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
    width: 80px;
    height: 80px;
    background: #d4edda;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
    color: #28a745;
}

.success-card h2 {
    color: #1a1a2e;
    margin-bottom: 10px;
}

.success-card p {
    color: #666;
    font-size: 1rem;
    max-width: 500px;
    margin: 0 auto 20px;
    line-height: 1.6;
}

.success-card .info-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px 25px;
    margin: 15px 0;
    text-align: left;
}

.success-card .info-box .info-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
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

/* Button */
.btn-reg {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 30px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
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
    gap: 15px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.form-actions-reg .btn-reg {
    flex: 1;
    justify-content: center;
    min-width: 140px;
}

/* Syarat & Ketentuan */
.terms-box {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px 20px;
    margin-top: 10px;
    border-left: 4px solid #d4a847;
}

.terms-box h4 {
    font-size: 0.9rem;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.terms-box ul {
    margin: 0;
    padding-left: 20px;
    font-size: 0.85rem;
    color: #666;
    line-height: 1.8;
}

.terms-box ul li {
    list-style: disc;
}

.terms-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 15px;
    padding: 12px 15px;
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
    width: 20px;
    height: 20px;
    margin-top: 2px;
    accent-color: #1a6e3a;
    flex-shrink: 0;
    cursor: pointer;
}

.terms-check label {
    font-size: 0.9rem;
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

/* Animasi */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .registrasi-section {
        padding: 30px 0;
    }
    
    .registrasi-header h1 {
        font-size: 1.6rem;
    }
    
    .registrasi-card .card-body {
        padding: 20px;
    }
    
    .form-row-2 {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .steps-indicator {
        display: none;
    }
    
    .upload-options {
        flex-direction: row;
        justify-content: stretch;
    }
    
    .upload-options .btn-upload-reg {
        flex: 1;
        justify-content: center;
        font-size: 0.75rem;
        padding: 8px 12px;
    }
    
    .file-upload-area-reg {
        padding: 15px;
    }
    
    .file-upload-area-reg i {
        font-size: 1.5rem;
    }
    
    .file-upload-area-reg p {
        font-size: 0.8rem;
    }
    
    .file-info-reg {
        flex-direction: column;
        gap: 3px;
    }
    
    .form-actions-reg {
        flex-direction: column;
    }
    
    .form-actions-reg .btn-reg {
        width: 100%;
    }
    
    .terms-check {
        padding: 10px 12px;
    }
    .terms-check label {
        font-size: 0.8rem;
    }
    
    .google-lens-wrapper .form-control {
        padding-right: 70px;
    }
    
    .btn-google-lens .lens-text {
        display: none;
    }
    
    .btn-google-lens .lens-badge {
        display: none;
    }
    
    .lens-result-popup .popup-content {
        padding: 20px;
        margin: 10px;
    }
    
    .lens-result-popup .popup-body .lens-buttons {
        flex-direction: column;
    }
    
    .lens-result-popup .popup-body .lens-buttons .btn-lens {
        width: 100%;
    }
    
    .lens-result-popup .popup-body .open-lens-btn {
        width: 100%;
        justify-content: center;
        font-size: 0.9rem;
        padding: 12px 20px;
    }
}

@media (max-width: 480px) {
    .registrasi-card .card-body {
        padding: 15px;
    }
    
    .form-group-reg .form-control {
        padding: 9px 12px;
        font-size: 0.85rem;
    }
    
    .btn-upload-reg {
        font-size: 0.7rem;
        padding: 6px 10px;
    }
    
    .btn-upload-reg i {
        font-size: 0.8rem;
    }
    
    .success-card .info-box {
        padding: 15px;
    }
    
    .success-card .info-box .info-item {
        flex-direction: column;
        gap: 2px;
        padding: 8px 0;
    }
    
    .terms-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
    }
    
    .btn-google-lens {
        padding: 4px 8px;
        font-size: 0.6rem;
    }
    
    .btn-google-lens i {
        font-size: 0.7rem;
    }
}
</style>

<section class="registrasi-section">
    <div class="registrasi-container">
        <div class="registrasi-header">
            <h1><span>Registrasi</span> Member PGNI</h1>
            <p>Daftarkan diri Anda sebagai anggota <strong>Persatuan Guru Ngaji Indonesia</strong> Provinsi Lampung</p>
            <span class="badge-registrasi"><i class="fas fa-id-card"></i> Pendaftaran Gratis</span>
        </div>

        <div class="registrasi-card">
            <div class="card-body">
                
                <!-- Steps -->
                <div class="steps-indicator">
                    <div class="step-item active">
                        <div class="step-number">1</div>
                        <span class="step-label">Data Pribadi</span>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <span class="step-label">Data Mengajar</span>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <span class="step-label">Dokumen</span>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <span class="step-label">Selesai</span>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-reg error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <!-- SUCCESS -->
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>🎉 Pendaftaran Berhasil!</h2>
                        <p>Data Anda telah berhasil dikirim. Tim admin akan melakukan verifikasi dan menghubungi Anda dalam waktu 3x24 jam.</p>
                        
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
                                <span class="value" style="color: #f39c12;">⏳ Menunggu Verifikasi</span>
                            </div>
                        </div>
                        
                        <p style="font-size: 0.9rem; color: #999;">
                            <i class="fas fa-info-circle"></i> Anda akan mendapatkan notifikasi setelah data terverifikasi.
                        </p>
                        
                        <p style="font-size: 0.9rem; color: #999; margin-top: 10px;">
                            <i class="fas fa-search"></i> 
                            Anda dapat mengecek status pendaftaran Anda melalui halaman 
                            <a href="cek_status.php" style="color: #1a6e3a; font-weight: 600; text-decoration: underline;">Cek Status</a>
                        </p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 15px;">
                            <a href="index.php" class="btn-reg btn-reg-success">
                                <i class="fas fa-home"></i> Kembali ke Beranda
                            </a>
                            <a href="guru.php" class="btn-reg btn-reg-outline">
                                <i class="fas fa-users"></i> Lihat Guru Ngaji
                            </a>
                            <a href="cek_status.php" class="btn-reg btn-reg-primary">
                                <i class="fas fa-search"></i> Cek Status
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- FORM -->
                    <form action="" method="POST" id="formRegistrasi" enctype="multipart/form-data">
                        <input type="hidden" id="ktp_base64" name="ktp_base64" value="">
                        <input type="hidden" id="kk_base64" name="kk_base64" value="">
                        
                        <!-- DATA PRIBADI -->
                        <div class="form-section-reg">
                            <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="display: inline-block; width: 4px; height: 24px; background: #d4a847; border-radius: 2px;"></span>
                                <i class="fas fa-user-circle" style="color: #d4a847;"></i> Data Pribadi
                            </h3>
                            
                            <!-- NIK dengan Google Lens -->
                            <div class="form-group-reg">
                                <label>NIK <span class="required">*</span></label>
                                <div class="google-lens-wrapper">
                                    <input type="text" class="form-control" id="nik" name="nik" 
                                           placeholder="Masukkan NIK (16 digit angka)" 
                                           maxlength="16" required
                                           value="<?php echo isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''; ?>">
                                    <button type="button" class="btn-google-lens" id="btnGoogleLensNik" title="Buka Google Lens untuk scan KTP">
                                        <i class="fas fa-camera"></i>
                                        <span class="lens-text">Google Lens</span>
                                        <span class="lens-badge">Scan</span>
                                    </button>
                                </div>
                                <span class="form-text">
                                    <i class="fas fa-arrow-right" style="color: #4285f4;"></i> 
                                    Klik tombol <strong style="color:#4285f4;">Google Lens</strong> untuk scan KTP secara otomatis
                                </span>
                            </div>
                            
                            <!-- Nama dengan Google Lens -->
                            <div class="form-group-reg">
                                <label>Nama Lengkap <span class="required">*</span></label>
                                <div class="google-lens-wrapper">
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           placeholder="Masukkan nama lengkap sesuai KTP" required
                                           value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                                    <button type="button" class="btn-google-lens" id="btnGoogleLensNama" title="Buka Google Lens untuk scan KTP">
                                        <i class="fas fa-camera"></i>
                                        <span class="lens-text">Google Lens</span>
                                        <span class="lens-badge">Scan</span>
                                    </button>
                                </div>
                                <span class="form-text">
                                    <i class="fas fa-arrow-right" style="color: #4285f4;"></i> 
                                    Klik tombol <strong style="color:#4285f4;">Google Lens</strong> untuk scan KTP secara otomatis
                                </span>
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telp" name="no_telp" 
                                           placeholder="Contoh: 0812-3456-7890"
                                           value="<?php echo isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''; ?>">
                                </div>
                                
                                <div class="form-group-reg">
                                    <label>Jenis Profesi</label>
                                    <select class="form-control" id="jenis_profesi" name="jenis_profesi">
                                        <option value="">Pilih Profesi</option>
                                        <option value="Guru Ngaji">Guru Ngaji</option>
                                        <option value="Marbot">Marbot</option>
                                        
                                        <option value="Penjaga Makam">Penjaga Makam</option>
                                        
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DATA MENGAJAR -->
                        <div class="form-section-reg" style="margin-top: 25px;">
                            <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="display: inline-block; width: 4px; height: 24px; background: #d4a847; border-radius: 2px;"></span>
                                <i class="fas fa-chalkboard-teacher" style="color: #d4a847;"></i> Data Mengajar
                            </h3>
                            
                            <div class="form-group-reg">
                                <label>Tempat Mengajar <span class="required">*</span></label>
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
                            
                            <div class="form-group-reg">
                                <label>Detail Tempat Mengajar</label>
                                <input type="text" class="form-control" id="tempat_mengajar_detail" name="tempat_mengajar_detail" 
                                       placeholder="Contoh: TPA Al-Ikhlas / Masjid Agung"
                                       value="<?php echo isset($_POST['tempat_mengajar_detail']) ? htmlspecialchars($_POST['tempat_mengajar_detail']) : ''; ?>">
                                <span class="form-text">Nama spesifik tempat mengajar (opsional)</span>
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>Bank</label>
                                    <select class="form-control" id="bank" name="bank">
                                        <option value="">Pilih Bank</option>
                                        <?php foreach ($bank_list as $b): ?>
                                            <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group-reg">
                                    <label>No. Rekening</label>
                                    <input type="text" class="form-control" id="no_rekening" name="no_rekening" 
                                           placeholder="Masukkan nomor rekening"
                                           value="<?php echo isset($_POST['no_rekening']) ? htmlspecialchars($_POST['no_rekening']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- ALAMAT -->
                        <div class="form-section-reg" style="margin-top: 25px;">
                            <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="display: inline-block; width: 4px; height: 24px; background: #d4a847; border-radius: 2px;"></span>
                                <i class="fas fa-map-marker-alt" style="color: #d4a847;"></i> Alamat
                            </h3>
                            
                            <div class="form-group-reg">
                                <label>Kabupaten</label>
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
                            
                            <div class="form-row-2">
                                <div class="form-group-reg">
                                    <label>Kecamatan</label>
                                    <select class="form-control" id="kecamatan_id" name="kecamatan_id">
                                        <option value="">Pilih Kecamatan</option>
                                    </select>
                                </div>
                                
                                <div class="form-group-reg">
                                    <label>Desa/Kelurahan</label>
                                    <select class="form-control" id="desa_id" name="desa_id">
                                        <option value="">Pilih Desa</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DOKUMEN -->
                        <div class="form-section-reg" style="margin-top: 25px;">
                            <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="display: inline-block; width: 4px; height: 24px; background: #d4a847; border-radius: 2px;"></span>
                                <i class="fas fa-file-alt" style="color: #d4a847;"></i> Dokumen
                            </h3>
                            
                            <p style="font-size: 0.85rem; color: #888; margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Upload foto KTP dan KK (opsional, namun sangat disarankan untuk verifikasi)
                            </p>
                            
                            <!-- Info Nama File -->
                            <div style="background: #f0f7f3; padding: 12px 18px; border-radius: 8px; border-left: 4px solid #1a6e3a; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <i class="fas fa-info-circle" style="color: #1a6e3a; font-size: 1.2rem;"></i>
                                    <div style="flex: 1;">
                                        <strong style="color: #1a6e3a; font-size: 0.85rem;">Format Nama File:</strong>
                                        <span style="color: #555; font-size: 0.8rem; display: block;">
                                            KTP: <code id="ktpFileNamePreview">NAMA_NIK_ktp_TIMESTAMP.jpg</code>
                                        </span>
                                        <span style="color: #555; font-size: 0.8rem; display: block;">
                                            KK: <code id="kkFileNamePreview">NAMA_NIK_kk_TIMESTAMP.jpg</code>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload KTP -->
                            <div class="form-group-reg">
                                <label>Foto KTP</label>
                                <div class="upload-section">
                                    <div class="upload-options">
                                        <button type="button" class="btn-upload-reg btn-camera-reg" data-target="ktp">
                                            <i class="fas fa-camera"></i> Kamera
                                        </button>
                                        <button type="button" class="btn-upload-reg btn-gallery-reg" data-target="ktp">
                                            <i class="fas fa-images"></i> Galeri
                                        </button>
                                        <button type="button" class="btn-upload-reg btn-remove-reg" data-target="ktp" style="display:none;">
                                            <i class="fas fa-times"></i> Hapus
                                        </button>
                                    </div>
                                    <div class="file-upload-area-reg" id="ktpUploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Klik <strong>Kamera</strong> atau <strong>Galeri</strong></p>
                                        <span class="file-types">JPG, PNG, WebP (Max 64MB)</span>
                                        <input type="file" class="file-input" id="ktp_file" accept="image/*">
                                    </div>
                                    <div class="file-preview-reg" id="ktpPreview"></div>
                                    <div class="file-status-reg" id="ktpStatus"></div>
                                </div>
                            </div>
                            
                            <!-- Upload KK -->
                            <div class="form-group-reg">
                                <label>Foto KK</label>
                                <div class="upload-section">
                                    <div class="upload-options">
                                        <button type="button" class="btn-upload-reg btn-camera-reg" data-target="kk">
                                            <i class="fas fa-camera"></i> Kamera
                                        </button>
                                        <button type="button" class="btn-upload-reg btn-gallery-reg" data-target="kk">
                                            <i class="fas fa-images"></i> Galeri
                                        </button>
                                        <button type="button" class="btn-upload-reg btn-remove-reg" data-target="kk" style="display:none;">
                                            <i class="fas fa-times"></i> Hapus
                                        </button>
                                    </div>
                                    <div class="file-upload-area-reg" id="kkUploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Klik <strong>Kamera</strong> atau <strong>Galeri</strong></p>
                                        <span class="file-types">JPG, PNG, WebP (Max 64MB)</span>
                                        <input type="file" class="file-input" id="kk_file" accept="image/*">
                                    </div>
                                    <div class="file-preview-reg" id="kkPreview"></div>
                                    <div class="file-status-reg" id="kkStatus"></div>
                                </div>
                            </div>
                        </div>

                        <!-- SYARAT & KETENTUAN -->
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
                            <input type="checkbox" id="terms_agree" required>
                            <label for="terms_agree">
                                Saya menyatakan bahwa data yang saya isi adalah benar dan saya menyetujui 
                                <a href="#" onclick="return false;">syarat & ketentuan</a> yang berlaku.
                            </label>
                        </div>

                        <!-- BUTTON -->
                        <div class="form-actions-reg">
                            <button type="submit" class="btn-reg btn-reg-primary" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane"></i> Daftar Sekarang
                            </button>
                            <a href="index.php" class="btn-reg btn-reg-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<!-- Google Lens Result Popup -->
<div class="lens-result-popup" id="lensResultPopup">
    <div class="popup-content">
        <div class="popup-header">
            <h3>
                <span class="lens-icon">🔍</span> 
                Google Lens - Hasil Scan
            </h3>
            <button class="popup-close" id="lensPopupClose">&times;</button>
        </div>
        <div class="popup-body">
            <div class="instruction-box">
                <div class="step">
                    <span class="num">1</span>
                    <span>Klik tombol <strong>"Buka Google Lens"</strong> di bawah</span>
                </div>
                <div class="step">
                    <span class="num">2</span>
                    <span>Scan KTP Anda dan pilih <strong>"Copy text"</strong></span>
                </div>
                <div class="step">
                    <span class="num">3</span>
                    <span>Kembali ke halaman ini dan <strong>Paste</strong> hasil scan di bawah</span>
                </div>
            </div>
            
            <button class="open-lens-btn" id="openLensBtn">
                <i class="fas fa-camera"></i>
                Buka Google Lens
                <span class="btn-sub">• scan KTP</span>
            </button>
            
            <textarea class="lens-textarea" id="lensTextarea" placeholder="Paste hasil scan Google Lens di sini...&#10;Contoh:&#10;NIK: 1871010123456789&#10;Nama: Ahmad Fauzi"></textarea>
            
            <div class="lens-result" id="lensResult">
                <div class="result-item">
                    <span class="label">NIK</span>
                    <span class="value nik-value" id="lensNik">-</span>
                </div>
                <div class="result-item">
                    <span class="label">Nama</span>
                    <span class="value name-value" id="lensNama">-</span>
                </div>
            </div>
            
            <div class="lens-buttons">
                <button class="btn-lens btn-lens-primary" id="lensExtractBtn">
                    <i class="fas fa-magic"></i> Ekstrak Data
                </button>
                <button class="btn-lens btn-lens-success" id="lensApplyBtn" disabled>
                    <i class="fas fa-check"></i> Terapkan ke Form
                </button>
                <button class="btn-lens btn-lens-secondary" id="lensClearBtn">
                    <i class="fas fa-undo"></i> Bersihkan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // CEK APAKAH FORM ADA (BUKAN HALAMAN SUKSES)
    // ============================================
    const form = document.getElementById('formRegistrasi');
    if (!form) {
        return;
    }

    // ============================================
    // GOOGLE LENS - BUKA LENS APP
    // ============================================
    let currentTarget = ''; // 'nik' atau 'nama'
    let extractedNik = '';
    let extractedNama = '';
    
    const lensPopup = document.getElementById('lensResultPopup');
    const lensTextarea = document.getElementById('lensTextarea');
    const lensResult = document.getElementById('lensResult');
    const lensNik = document.getElementById('lensNik');
    const lensNama = document.getElementById('lensNama');
    const lensExtractBtn = document.getElementById('lensExtractBtn');
    const lensApplyBtn = document.getElementById('lensApplyBtn');
    const lensClearBtn = document.getElementById('lensClearBtn');
    const lensPopupClose = document.getElementById('lensPopupClose');
    const openLensBtn = document.getElementById('openLensBtn');
    
    // Fungsi untuk membuka Google Lens
    function openGoogleLens() {
        // Intent URL untuk Android
        const androidIntent = 'intent://scan/#Intent;package=com.google.ar.lens;scheme=google lens;end';
        
        // URL untuk iOS / Universal Link
        const iosUrl = 'https://lens.google.com/scan';
        
        // Deteksi perangkat
        const isAndroid = /Android/i.test(navigator.userAgent);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
        
        if (isAndroid) {
            // Untuk Android - coba buka Google Lens
            try {
                window.location.href = androidIntent;
            } catch(e) {
                // Fallback ke play store jika tidak terinstall
                window.open('https://play.google.com/store/apps/details?id=com.google.ar.lens', '_blank');
            }
        } else if (isIOS) {
            // Untuk iOS - coba buka via universal link
            window.open(iosUrl, '_blank');
        } else {
            // Desktop - buka web version
            window.open('https://lens.google.com/', '_blank');
        }
        
        // Tampilkan instruksi manual
        setTimeout(function() {
            // Fokus ke textarea untuk paste
            lensTextarea.focus();
            lensTextarea.placeholder = 'Paste hasil scan Google Lens di sini...';
        }, 2000);
    }
    
    // Buka popup untuk NIK
    document.getElementById('btnGoogleLensNik').addEventListener('click', function(e) {
        e.preventDefault();
        currentTarget = 'nik';
        lensPopup.classList.add('active');
        document.body.style.overflow = 'hidden';
        lensTextarea.value = '';
        lensResult.classList.remove('active');
        lensNik.textContent = '-';
        lensNama.textContent = '-';
        lensApplyBtn.disabled = true;
        lensTextarea.placeholder = 'Paste hasil scan Google Lens untuk NIK...';
        
        // Buka Google Lens otomatis
        setTimeout(function() {
            openGoogleLens();
        }, 500);
    });
    
    // Buka popup untuk Nama
    document.getElementById('btnGoogleLensNama').addEventListener('click', function(e) {
        e.preventDefault();
        currentTarget = 'nama';
        lensPopup.classList.add('active');
        document.body.style.overflow = 'hidden';
        lensTextarea.value = '';
        lensResult.classList.remove('active');
        lensNik.textContent = '-';
        lensNama.textContent = '-';
        lensApplyBtn.disabled = true;
        lensTextarea.placeholder = 'Paste hasil scan Google Lens untuk Nama...';
        
        // Buka Google Lens otomatis
        setTimeout(function() {
            openGoogleLens();
        }, 500);
    });
    
    // Tombol buka Google Lens di popup
    openLensBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openGoogleLens();
    });
    
    // Tutup popup
    function closeLensPopup() {
        lensPopup.classList.remove('active');
        document.body.style.overflow = '';
        lensResult.classList.remove('active');
        lensApplyBtn.disabled = true;
    }
    
    lensPopupClose.addEventListener('click', closeLensPopup);
    lensPopup.addEventListener('click', function(e) {
        if (e.target === this) {
            closeLensPopup();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLensPopup();
        }
    });
    
    // ============================================
    // EKSTRAK DATA DARI TEKS
    // ============================================
    function extractData(text) {
        const result = { nik: '', nama: '' };
        
        // Cari NIK (16 digit angka)
        const nikMatch = text.match(/\b\d{16}\b/);
        if (nikMatch) {
            result.nik = nikMatch[0];
        }
        
        // Cari Nama
        const namaPatterns = [
            /Nama\s*[:|]\s*([^\n\r,]+)/i,
            /Nama\s+Lengkap\s*[:|]\s*([^\n\r,]+)/i,
            /Nama\s+(\w+\s+\w+)/i,
            /(?:Nama|Nama Lengkap)\s*[:|]\s*([^\n\r]+)/i,
            /NIK\s*[:|]\s*\d{16}\s*([^\n\r,]+)/i
        ];
        
        for (const pattern of namaPatterns) {
            const match = text.match(pattern);
            if (match && match[1]) {
                result.nama = match[1].trim().replace(/[^\w\s\-\.']/g, '');
                break;
            }
        }
        
        // Jika tidak ditemukan dengan pola, coba ambil setelah kata "Nama"
        if (!result.nama) {
            const lines = text.split('\n');
            for (const line of lines) {
                if (line.toLowerCase().includes('nama')) {
                    const parts = line.split(/[:|]/);
                    if (parts.length > 1) {
                        result.nama = parts[1].trim().replace(/[^\w\s\-\.']/g, '');
                        break;
                    }
                }
            }
        }
        
        return result;
    }
    
    // Ekstrak data dari textarea
    lensExtractBtn.addEventListener('click', function() {
        const text = lensTextarea.value.trim();
        if (!text) {
            alert('Silakan paste hasil scan Google Lens terlebih dahulu!');
            return;
        }
        
        const extracted = extractData(text);
        extractedNik = extracted.nik;
        extractedNama = extracted.nama;
        
        // Tampilkan hasil
        lensNik.textContent = extractedNik || '-';
        lensNama.textContent = extractedNama || '-';
        lensResult.classList.add('active');
        
        // Enable apply button jika ada data yang sesuai dengan target
        if (currentTarget === 'nik' && extractedNik) {
            lensApplyBtn.disabled = false;
        } else if (currentTarget === 'nama' && extractedNama) {
            lensApplyBtn.disabled = false;
        } else {
            lensApplyBtn.disabled = true;
            if (currentTarget === 'nik') {
                alert('Tidak ditemukan NIK (16 digit angka) dalam teks!');
            } else {
                alert('Tidak ditemukan Nama dalam teks!');
            }
        }
    });
    
    // Apply data ke form
    lensApplyBtn.addEventListener('click', function() {
        if (currentTarget === 'nik' && extractedNik) {
            document.getElementById('nik').value = extractedNik;
            document.getElementById('nik').dispatchEvent(new Event('input'));
            document.getElementById('nik').dispatchEvent(new Event('change'));
        } else if (currentTarget === 'nama' && extractedNama) {
            document.getElementById('nama').value = extractedNama;
            document.getElementById('nama').dispatchEvent(new Event('input'));
            document.getElementById('nama').dispatchEvent(new Event('change'));
        }
        updateFileNamePreview();
        closeLensPopup();
    });
    
    // Clear textarea
    lensClearBtn.addEventListener('click', function() {
        lensTextarea.value = '';
        lensResult.classList.remove('active');
        lensNik.textContent = '-';
        lensNama.textContent = '-';
        lensApplyBtn.disabled = true;
        lensTextarea.focus();
    });
    
    // Shortcut: Ctrl+Enter atau Cmd+Enter untuk ekstrak
    lensTextarea.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            lensExtractBtn.click();
        }
    });

    // ============================================
    // TOMBOL DAFTAR - AKTIF SETELAH CHECKLIST
    // ============================================
    const termsCheck = document.getElementById('terms_agree');
    const submitBtn = document.getElementById('submitBtn');
    const termsContainer = document.getElementById('termsCheck');

    if (termsCheck && submitBtn) {
        function updateSubmitButton() {
            if (termsCheck.checked) {
                submitBtn.disabled = false;
                submitBtn.classList.add('active');
                if (termsContainer) {
                    termsContainer.classList.add('checked');
                }
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('active');
                if (termsContainer) {
                    termsContainer.classList.remove('checked');
                }
            }
        }

        termsCheck.addEventListener('change', updateSubmitButton);
        updateSubmitButton();
    }

    // ============================================
    // PREVIEW NAMA FILE
    // ============================================
    function updateFileNamePreview() {
        const nikInput = document.getElementById('nik');
        const namaInput = document.getElementById('nama');
        const ktpPreview = document.getElementById('ktpFileNamePreview');
        const kkPreview = document.getElementById('kkFileNamePreview');
        
        if (!nikInput || !namaInput || !ktpPreview || !kkPreview) {
            return;
        }
        
        const nik = nikInput.value || 'NIK';
        const nama = namaInput.value || 'NAMA';
        const cleanNama = nama.replace(/[^a-zA-Z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
        const now = new Date();
        const timestamp = now.getFullYear() + 
                         String(now.getMonth() + 1).padStart(2, '0') + 
                         String(now.getDate()).padStart(2, '0') + '_' +
                         String(now.getHours()).padStart(2, '0') + 
                         String(now.getMinutes()).padStart(2, '0') + 
                         String(now.getSeconds()).padStart(2, '0');
        
        ktpPreview.textContent = (cleanNama || 'NAMA') + '_' + nik + '_ktp_' + timestamp + '.jpg';
        kkPreview.textContent = (cleanNama || 'NAMA') + '_' + nik + '_kk_' + timestamp + '.jpg';
    }

    const nikInput = document.getElementById('nik');
    const namaInput = document.getElementById('nama');
    
    if (nikInput) {
        nikInput.addEventListener('input', updateFileNamePreview);
        nikInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 16) {
                this.value = this.value.slice(0, 16);
            }
        });
    }
    if (namaInput) {
        namaInput.addEventListener('input', updateFileNamePreview);
    }
    
    updateFileNamePreview();
    setInterval(updateFileNamePreview, 2000);

    // ============================================
    // LOAD KECAMATAN & DESA
    // ============================================
    const kabupatenSelect = document.getElementById('kabupaten_id');
    const kecamatanSelect = document.getElementById('kecamatan_id');
    const desaSelect = document.getElementById('desa_id');

    if (kabupatenSelect) {
        kabupatenSelect.addEventListener('change', function() {
            const kabupatenId = this.value;
            
            if (kecamatanSelect) {
                kecamatanSelect.innerHTML = '<option value="">Loading...</option>';
            }
            if (desaSelect) {
                desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
            }
            
            if (kabupatenId) {
                fetch('ajax/get_kecamatan.php?kabupaten_id=' + kabupatenId)
                    .then(response => response.json())
                    .then(data => {
                        if (kecamatanSelect) {
                            kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                            data.forEach(kec => {
                                kecamatanSelect.innerHTML += `<option value="${kec.id}">${kec.nama}</option>`;
                            });
                        }
                    })
                    .catch(() => {
                        if (kecamatanSelect) {
                            kecamatanSelect.innerHTML = '<option value="">Error loading data</option>';
                        }
                    });
            } else {
                if (kecamatanSelect) {
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                }
            }
        });
    }

    if (kecamatanSelect) {
        kecamatanSelect.addEventListener('change', function() {
            const kecamatanId = this.value;
            
            if (desaSelect) {
                desaSelect.innerHTML = '<option value="">Loading...</option>';
            }
            
            if (kecamatanId) {
                fetch('ajax/get_desa.php?kecamatan_id=' + kecamatanId)
                    .then(response => response.json())
                    .then(data => {
                        if (desaSelect) {
                            desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
                            data.forEach(desa => {
                                desaSelect.innerHTML += `<option value="${desa.id}">${desa.nama}</option>`;
                            });
                        }
                    })
                    .catch(() => {
                        if (desaSelect) {
                            desaSelect.innerHTML = '<option value="">Error loading data</option>';
                        }
                    });
            } else {
                if (desaSelect) {
                    desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
                }
            }
        });
    }

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
    // SETUP FILE UPLOAD
    // ============================================
    function setupFileUpload(targetId) {
        const input = document.getElementById(targetId + '_file');
        const preview = document.getElementById(targetId + 'Preview');
        const area = document.getElementById(targetId + 'UploadArea');
        const status = document.getElementById(targetId + 'Status');
        const hidden = document.getElementById(targetId + '_base64');
        const removeBtn = document.querySelector('.btn-remove-reg[data-target="' + targetId + '"]');
        
        if (!input || !preview || !area) {
            return;
        }
        
        const cameraBtn = document.querySelector('.btn-camera-reg[data-target="' + targetId + '"]');
        if (cameraBtn) {
            cameraBtn.addEventListener('click', function(e) {
                e.preventDefault();
                input.setAttribute('capture', 'environment');
                input.click();
            });
        }
        
        const galleryBtn = document.querySelector('.btn-gallery-reg[data-target="' + targetId + '"]');
        if (galleryBtn) {
            galleryBtn.addEventListener('click', function(e) {
                e.preventDefault();
                input.removeAttribute('capture');
                input.setAttribute('accept', 'image/*');
                input.click();
            });
        }
        
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                input.value = '';
                hidden.value = '';
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status-reg';
                area.classList.remove('has-file');
                removeBtn.style.display = 'none';
                if (cameraBtn) cameraBtn.style.display = 'inline-flex';
                if (galleryBtn) galleryBtn.style.display = 'inline-flex';
            });
        }
        
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                area.classList.remove('has-file');
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'file-status-reg';
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
                status.className = 'file-status-reg error';
                this.value = '';
                hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                status.innerHTML = '❌ Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP.';
                status.className = 'file-status-reg error';
                this.value = '';
                hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            let sourceText = '🖼️ Galeri';
            if (input.hasAttribute('capture')) {
                sourceText = '📷 Kamera';
            }
            
            status.innerHTML = '⏳ Mengompresi gambar (' + fileSizeMB + 'MB)...';
            status.className = 'file-status-reg loading';
            area.classList.add('has-file');
            
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
                        status.className = 'file-status-reg success';
                    };
                    base64Reader.readAsDataURL(compressedBlob);
                })
                .catch(function(err) {
                    console.error('Kompresi gagal:', err);
                    status.innerHTML = '⚠️ Kompresi gagal, menggunakan file asli...';
                    status.className = 'file-status-reg loading';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                        hidden.value = e.target.result;
                        status.innerHTML = '✅ File asli (' + fileSizeMB + 'MB) - tanpa kompresi';
                        status.className = 'file-status-reg success';
                    };
                    reader.readAsDataURL(file);
                });
        });
        
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
                input.removeAttribute('capture');
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    if (document.getElementById('ktp_file')) {
        setupFileUpload('ktp');
    }
    if (document.getElementById('kk_file')) {
        setupFileUpload('kk');
    }

    // ============================================
    // FORM SUBMIT - CEK CHECKBOX
    // ============================================
    if (form) {
        form.addEventListener('submit', function(e) {
            const termsCheck = document.getElementById('terms_agree');
            if (termsCheck && !termsCheck.checked) {
                e.preventDefault();
                alert('Silakan setujui syarat & ketentuan terlebih dahulu.');
                termsCheck.focus();
                return;
            }
            
            const nikField = document.getElementById('nik');
            const namaField = document.getElementById('nama');
            const tempatField = document.getElementById('tempat_mengajar');
            
            const nik = nikField ? nikField.value.trim() : '';
            const nama = namaField ? namaField.value.trim() : '';
            const tempat_mengajar = tempatField ? tempatField.value : '';
            
            if (!nik || !nama || !tempat_mengajar) {
                e.preventDefault();
                alert('NIK, Nama Lengkap, dan Tempat Mengajar wajib diisi!');
                return;
            }
            
            if (nik.length !== 16 || !/^\d+$/.test(nik)) {
                e.preventDefault();
                alert('NIK harus 16 digit angka!');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftarkan...';
            }
        });
    }
});
</script>

<?php include $root_path . '/include/footer.php'; ?>