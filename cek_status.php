<?php
// cek_status.php - Halaman Cek Status Pendaftaran PGNI Lampung
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Cek Status Pendaftaran - PGNI Lampung';
$guru_data = null; // Ubah nama variabel dari $result menjadi $guru_data
$error = '';
$nik = '';

// ============================================================
// CEK STATUS - MENERIMA METODE POST ATAU GET
// ============================================================
// Cek dari GET parameter (dari link WhatsApp)
if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = trim($_GET['nik']);
}
// Atau dari POST form
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nik'])) {
    $nik = trim($_POST['nik']);
}

// Proses jika ada NIK
if (!empty($nik)) {
    if (strlen($nik) !== 16 || !is_numeric($nik)) {
        $error = 'NIK harus 16 digit angka.';
    } else {
        $nik_escaped = mysqli_real_escape_string($conn, $nik);
        $query = "SELECT g.*, 
          k.nama as kabupaten_nama, 
          kec.nama as kecamatan_nama, 
          d.nama as desa_nama,
          COALESCE(u.nama_lengkap, 'Tidak diketahui') as verifikator_nama,
          u.username as verifikator_username,
          u.profile_photo as verifikator_foto,
          g.created_by as verifikator_id
          FROM guru_ngaji g
          LEFT JOIN kabupaten k ON g.kabupaten_id = k.id
          LEFT JOIN kecamatan kec ON g.kecamatan_id = kec.id
          LEFT JOIN desa d ON g.desa_id = d.id
          LEFT JOIN users u ON g.created_by = u.id
          WHERE g.nik = '$nik_escaped'";
        $result_query = mysqli_query($conn, $query);
        
        if ($result_query && mysqli_num_rows($result_query) > 0) {
            $guru_data = mysqli_fetch_assoc($result_query); // Gunakan $guru_data
        } else {
            $error = 'NIK tidak ditemukan dalam database. Pastikan Anda sudah mendaftar.';
        }
    }
}

include $root_path . '/include/header.php';
?>

<style>
/* ============================================
   STYLE CEK STATUS
============================================ */
.status-page {
    padding: 40px 0;
    min-height: calc(100vh - 300px);
    background: #f8f9fa;
}

.status-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.status-header {
    text-align: center;
    margin-bottom: 35px;
}

.status-header h1 {
    font-size: 2rem;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.status-header h1 i {
    color: #d4a847;
}

.status-header p {
    color: #666;
    font-size: 1rem;
}

/* Form Cek */
.status-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.08);
    overflow: hidden;
}

.status-card .card-body {
    padding: 35px 40px;
}

.search-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.search-form .form-group {
    flex: 1;
    min-width: 250px;
}

.search-form .form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.search-form .form-group label .required {
    color: #e74c3c;
}

.search-form .form-group .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: #fafafa;
}

.search-form .form-group .form-control:focus {
    border-color: #1a6e3a;
    outline: none;
    box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    background: #fff;
}

.search-form .form-group .form-text {
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
    display: block;
}

.search-form .btn-submit {
    padding: 12px 35px;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Poppins', sans-serif;
    min-width: 150px;
    justify-content: center;
}

.search-form .btn-submit:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}

/* Alert */
.alert {
    padding: 14px 20px;
    border-radius: 10px;
    margin-top: 20px;
    font-weight: 500;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Auto detect info */
.auto-detect-info {
    background: #e8f5e9;
    border-radius: 10px;
    padding: 12px 18px;
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #c8e6c9;
}

.auto-detect-info i {
    color: #2e7d32;
    font-size: 1.1rem;
}

.auto-detect-info p {
    margin: 0;
    color: #2e7d32;
    font-size: 0.9rem;
}

/* ============================================
   RESULT CARD
============================================ */
.result-card {
    margin-top: 25px;
    border: 2px solid #1a6e3a;
    border-radius: 12px;
    overflow: hidden;
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}

.result-card .result-header {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    padding: 15px 25px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.result-card .result-header h3 {
    font-size: 1.1rem;
    margin: 0;
}

.result-card .result-header h3 i {
    margin-right: 8px;
}

.result-card .result-body {
    padding: 25px;
}

/* Status Badge */
.status-badge-large {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-badge-large.disetujui {
    background: #d4edda;
    color: #28a745;
}

.status-badge-large.pending {
    background: #fff3cd;
    color: #f39c12;
}

.status-badge-large.ditolak {
    background: #f8d7da;
    color: #dc3545;
}

/* Status Summary */
.status-summary {
    display: flex;
    align-items: center;
    gap: 20px;
    background: #f8f9fa;
    padding: 20px 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    border-left: 5px solid #d4a847;
}

.status-icon-large {
    font-size: 3rem;
    line-height: 1;
    flex-shrink: 0;
}

.status-message h4 {
    margin: 0 0 5px 0;
    font-size: 0.95rem;
    color: #1a1a2e;
}

.status-message p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 30px;
    margin-bottom: 20px;
}

.info-item {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item .label {
    display: block;
    font-size: 0.7rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-item .value {
    display: block;
    font-size: 0.95rem;
    color: #1a1a2e;
    font-weight: 500;
    margin-top: 2px;
    word-break: break-word;
}

.info-item .value .text-muted {
    color: #999;
    font-weight: 400;
    font-size: 0.85rem;
}

.info-item.full {
    grid-column: 1 / -1;
}

/* NIK Value */
.nik-value {
    font-family: 'Courier New', monospace;
    letter-spacing: 2px;
    background: #f0f0f0;
    padding: 2px 10px;
    border-radius: 4px;
    display: inline-block;
}

/* Verifikator Info */
.verifikator-info-box {
    display: flex;
    align-items: center;
    gap: 15px;
    border-radius: 10px;
    padding: 12px 18px;
    margin-top: 10px;
    border: 1px solid #d6e9ff;
    background: #f0f7ff;
}

.verifikator-info-box.warning {
    background: #fff8e1;
    border-color: #ffe082;
}

.verifikator-info-box.info {
    background: #e8f5e9;
    border-color: #a5d6a7;
}

.verifikator-info-box .verifikator-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #1a6e3a;
}

.verifikator-info-box .verifikator-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.verifikator-info-box .verifikator-avatar .default-avatar {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    font-weight: 700;
    font-size: 1.2rem;
}

.verifikator-info-box .verifikator-avatar .default-avatar i {
    font-size: 1rem;
}

.verifikator-info-box .verifikator-detail {
    flex: 1;
}

.verifikator-info-box .verifikator-detail .label {
    font-size: 0.7rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.verifikator-info-box .verifikator-detail .name {
    font-weight: 600;
    color: #1a1a2e;
    font-size: 0.95rem;
}

.verifikator-info-box .verifikator-detail .username {
    font-size: 0.8rem;
    color: #666;
}

.verifikator-info-box .verifikator-detail .status-text {
    font-size: 0.85rem;
    color: #f39c12;
}

/* Info Box Status */
.info-box-status {
    background: #e8f5e9;
    border-radius: 10px;
    padding: 15px 20px;
    margin-top: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.info-box-status i {
    color: #1a6e3a;
    font-size: 1.2rem;
    margin-top: 2px;
}

.info-box-status p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
    line-height: 1.6;
}

.info-box-status strong {
    color: #1a6e3a;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 25px;
    flex-wrap: wrap;
    border-top: 1px solid #e8e8e8;
    padding-top: 20px;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

.btn-action i {
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
    color: #fff;
}

.btn-print {
    background: linear-gradient(135deg, #d4a847, #c99a3b);
    color: #fff;
}

.btn-print:hover {
    background: linear-gradient(135deg, #b8922e, #a07d28);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(212, 168, 71, 0.3);
    color: #fff;
}

.btn-secondary {
    background: #e8e8e8;
    color: #555;
}

.btn-secondary:hover {
    background: #d5d5d5;
    transform: translateY(-2px);
    color: #333;
}

/* FAQ */
.faq-section {
    margin-top: 30px;
    background: #fff;
    border-radius: 12px;
    padding: 20px 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

.faq-section h4 {
    font-size: 1rem;
    color: #1a1a2e;
    margin-bottom: 10px;
}

.faq-section h4 i {
    color: #d4a847;
}

.faq-item {
    display: grid;
    gap: 12px;
    font-size: 0.9rem;
    color: #555;
}

.faq-item div {
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.faq-item div:last-child {
    border-bottom: none;
}

.faq-item strong {
    color: #1a1a2e;
    display: block;
    margin-bottom: 2px;
}

.faq-item p {
    margin: 3px 0 0 0;
}

.faq-item a {
    color: #1a6e3a;
    text-decoration: none;
    font-weight: 500;
}

.faq-item a:hover {
    text-decoration: underline;
}

/* ============================================
   STYLE CETAK (PRINT)
============================================ */
@media print {
    /* Sembunyikan elemen yang tidak perlu */
    .status-header,
    .search-form,
    .auto-detect-info,
    .alert,
    .info-box-status,
    .action-buttons,
    .btn-action,
    .footer,
    header,
    nav,
    .status-card .card-body > form,
    .status-card .card-body > .alert,
    .status-card .card-body > .auto-detect-info,
    .faq-section,
    .container > div:not(.result-card) {
        display: none !important;
    }

    /* Tampilkan hanya hasil */
    .status-page {
        padding: 0 !important;
        background: #fff !important;
        min-height: auto !important;
    }

    .status-container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .status-card {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
    }

    .status-card .card-body {
        padding: 20px !important;
    }

    .result-card {
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        margin-top: 0 !important;
    }

    .result-header {
        background: #1a6e3a !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 12px 20px !important;
    }

    .result-header h3 {
        color: #fff !important;
        font-size: 1rem !important;
    }

    .status-badge-large {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .status-badge-large.disetujui {
        background: #d4edda !important;
        color: #28a745 !important;
    }
    .status-badge-large.pending {
        background: #fff3cd !important;
        color: #f39c12 !important;
    }
    .status-badge-large.ditolak {
        background: #f8d7da !important;
        color: #dc3545 !important;
    }

    .status-summary {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #f8f9fa !important;
    }

    .info-item {
        padding: 6px 0 !important;
    }

    .info-item .label {
        font-size: 0.6rem !important;
    }

    .info-item .value {
        font-size: 0.85rem !important;
    }

    .nik-value {
        background: #f0f0f0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .verifikator-info-box {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #f0f7ff !important;
        border-color: #d6e9ff !important;
    }

    .verifikator-info-box .verifikator-avatar .default-avatar {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52) !important;
    }

    /* Logo PGNI di print */
    .result-body::before {
        content: "PGNI Lampung";
        display: block;
        text-align: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a6e3a;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1a6e3a;
        letter-spacing: 2px;
    }

    .result-body {
        padding: 20px !important;
    }

    /* Footer print */
    .result-body::after {
        content: "Dicetak dari https://pgni.net/pgnil/cek_status.php pada " attr(data-print-date);
        display: block;
        text-align: center;
        font-size: 0.6rem;
        color: #999;
        margin-top: 20px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
}

/* ============================================
   RESPONSIVE
============================================ */
@media (max-width: 768px) {
    .status-page {
        padding: 25px 0;
    }
    
    .status-header h1 {
        font-size: 1.5rem;
    }
    
    .status-card .card-body {
        padding: 20px;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-form .form-group {
        min-width: auto;
        width: 100%;
    }
    
    .search-form .btn-submit {
        width: 100%;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .result-card .result-header {
        flex-direction: column;
        text-align: center;
    }
    
    .result-card .result-body {
        padding: 15px 18px;
    }
    
    .status-summary {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        justify-content: center;
        width: 100%;
    }
    
    .verifikator-info-box {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .status-header h1 {
        font-size: 1.2rem;
    }
    
    .status-card .card-body {
        padding: 15px;
    }
    
    .search-form .form-group .form-control {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
    
    .search-form .btn-submit {
        font-size: 0.9rem;
        padding: 10px 20px;
    }
    
    .info-item .value {
        font-size: 0.85rem;
    }
    
    .status-badge-large {
        font-size: 0.8rem;
        padding: 4px 14px;
    }
}
</style>

<section class="status-page">
    <div class="status-container">
        <!-- Header -->
        <div class="status-header">
            <h1><i class="fas fa-search"></i> Cek Status Pendaftaran</h1>
            <p>Masukkan NIK Anda untuk mengetahui status verifikasi pendaftaran sebagai anggota PGNI Lampung</p>
        </div>

        <!-- Form -->
        <div class="status-card">
            <div class="card-body">
                <form action="" method="POST" class="search-form">
                    <div class="form-group">
                        <label>NIK <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nik" id="nikInput" 
                               placeholder="Masukkan 16 digit NIK" 
                               maxlength="16" 
                               value="<?php echo htmlspecialchars($nik); ?>"
                               pattern="[0-9]{16}"
                               title="NIK harus 16 digit angka"
                               required>
                        <span class="form-text"><i class="fas fa-info-circle"></i> Masukkan NIK sesuai KTP (16 digit angka)</span>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-search"></i> Cek Status
                    </button>
                </form>

                <!-- Auto Detect Info -->
                <?php if (!empty($nik) && empty($error) && !$guru_data): ?>
                    <div class="auto-detect-info">
                        <i class="fas fa-sync-alt fa-spin"></i>
                        <p>Sedang mencari data dengan NIK: <strong><?php echo htmlspecialchars($nik); ?></strong></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($nik) && !empty($error)): ?>
                    <div class="auto-detect-info" style="background: #fff3cd; border-color: #ffeaa7;">
                        <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>
                        <p style="color: #856404;">Pencarian dengan NIK: <strong><?php echo htmlspecialchars($nik); ?></strong> - <?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if (!empty($error) && empty($nik)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- ============================================================
                HASIL PENCARIAN - Menggunakan $guru_data
                ============================================================ -->
                <?php if ($guru_data): 
                    // ============================================================
                    // FIX: AMAN - Cek semua key sebelum diakses
                    // ============================================================
                    $status_verifikasi = isset($guru_data['status_verifikasi']) ? $guru_data['status_verifikasi'] : 'pending';
                    
                    $status_icons = [
                        'disetujui' => '✅',
                        'pending' => '⏳',
                        'ditolak' => '❌'
                    ];
                    $status_labels = [
                        'disetujui' => 'Terverifikasi',
                        'pending' => 'Menunggu Verifikasi',
                        'ditolak' => 'Ditolak'
                    ];
                    $status_descriptions = [
                        'disetujui' => 'Selamat! Data Anda telah diverifikasi dan Anda resmi menjadi anggota PGNI Lampung.',
                        'pending' => 'Data Anda sedang dalam proses verifikasi oleh admin. Mohon tunggu 1x24 jam.',
                        'ditolak' => 'Maaf, data Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.'
                    ];
                    
                    // AMAN: Cek semua key dengan null coalescing
                    $verifikator_id = isset($guru_data['verifikator_id']) ? $guru_data['verifikator_id'] : null;
                    $verifikator_nama = isset($guru_data['verifikator_nama']) ? $guru_data['verifikator_nama'] : null;
                    $verifikator_username = isset($guru_data['verifikator_username']) ? $guru_data['verifikator_username'] : null;
                    $verifikator_foto = isset($guru_data['verifikator_foto']) ? $guru_data['verifikator_foto'] : null;
                    
                    // AMAN: Data diri dengan null coalescing
                    $nama = isset($guru_data['nama']) ? $guru_data['nama'] : '-';
                    $nik_value = isset($guru_data['nik']) ? $guru_data['nik'] : '-';
                    $tempat_mengajar = isset($guru_data['tempat_mengajar']) ? $guru_data['tempat_mengajar'] : '-';
                    $tempat_mengajar_detail = isset($guru_data['tempat_mengajar_detail']) ? $guru_data['tempat_mengajar_detail'] : '';
                    $jenis_profesi = isset($guru_data['jenis_profesi']) ? $guru_data['jenis_profesi'] : '-';
                    $kabupaten_nama = isset($guru_data['kabupaten_nama']) ? $guru_data['kabupaten_nama'] : '';
                    $kecamatan_nama = isset($guru_data['kecamatan_nama']) ? $guru_data['kecamatan_nama'] : '';
                    $desa_nama = isset($guru_data['desa_nama']) ? $guru_data['desa_nama'] : '';
                    $created_at = isset($guru_data['created_at']) ? $guru_data['created_at'] : date('Y-m-d H:i:s');
                    $updated_at = isset($guru_data['updated_at']) ? $guru_data['updated_at'] : $created_at;
                    $guru_id = isset($guru_data['id']) ? $guru_data['id'] : 0;
                    
                    // Tentukan class untuk verifikator box
                    $verifikator_class = 'info';
                    $verifikator_status_text = '';
                    $verifikator_icon = '<i class="fas fa-user"></i>';
                    
                    if (!empty($verifikator_nama) && !empty($verifikator_id)) {
                        $verifikator_class = 'info';
                        $verifikator_status_text = '';
                    } elseif ($status_verifikasi == 'disetujui') {
                        $verifikator_class = 'warning';
                        $verifikator_status_text = '⚠️ Data diverifikasi oleh sistem (verifikator tidak tercatat)';
                        $verifikator_icon = '<i class="fas fa-check-circle"></i>';
                    } elseif ($status_verifikasi == 'pending') {
                        $verifikator_class = 'warning';
                        $verifikator_status_text = '⏳ Menunggu verifikasi oleh admin';
                        $verifikator_icon = '<i class="fas fa-clock"></i>';
                    } else {
                        $verifikator_class = 'warning';
                        $verifikator_status_text = 'ℹ️ Belum tercatat (data dari sistem lama)';
                        $verifikator_icon = '<i class="fas fa-info-circle"></i>';
                    }
                ?>
                    <div class="result-card" id="resultCard">
                        <div class="result-header">
                            <h3><i class="fas fa-user-check"></i> Hasil Pencarian</h3>
                            <span class="status-badge-large <?php echo $status_verifikasi; ?>">
                                <?php echo (isset($status_icons[$status_verifikasi]) ? $status_icons[$status_verifikasi] : '') . ' ' . (isset($status_labels[$status_verifikasi]) ? $status_labels[$status_verifikasi] : $status_verifikasi); ?>
                            </span>
                        </div>
                        <div class="result-body" id="printArea">
                            <!-- Status Summary -->
                            <div class="status-summary">
                                <div class="status-icon-large">
                                    <?php 
                                    if ($status_verifikasi == 'disetujui') {
                                        echo '🎉';
                                    } elseif ($status_verifikasi == 'pending') {
                                        echo '⏳';
                                    } else {
                                        echo 'ℹ️';
                                    }
                                    ?>
                                </div>
                                <div class="status-message">
                                    <h4>Keterangan Status:</h4>
                                    <p><?php echo isset($status_descriptions[$status_verifikasi]) ? $status_descriptions[$status_verifikasi] : 'Status tidak diketahui.'; ?></p>
                                </div>
                            </div>

                            <!-- Data Diri -->
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="label">Nama Lengkap</span>
                                    <span class="value"><?php echo htmlspecialchars($nama); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">NIK</span>
                                    <span class="value nik-value"><?php echo htmlspecialchars($nik_value); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Tempat Mengajar</span>
                                    <span class="value">
                                        <?php echo htmlspecialchars($tempat_mengajar); ?>
                                        <?php if (!empty($tempat_mengajar_detail)): ?>
                                            <span class="text-muted">(<?php echo htmlspecialchars($tempat_mengajar_detail); ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Profesi</span>
                                    <span class="value"><?php echo htmlspecialchars($jenis_profesi); ?></span>
                                </div>
                                <?php if (!empty($kabupaten_nama) || !empty($kecamatan_nama) || !empty($desa_nama)): ?>
                                <div class="info-item full">
                                    <span class="label">Alamat</span>
                                    <span class="value">
                                        <?php 
                                        $alamat = array_filter([$desa_nama, $kecamatan_nama, $kabupaten_nama]);
                                        echo htmlspecialchars(implode(', ', $alamat));
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="label">Tanggal Pendaftaran</span>
                                    <span class="value"><?php echo date('d-m-Y H:i', strtotime($created_at)); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Terakhir Diperbarui</span>
                                    <span class="value"><?php echo date('d-m-Y H:i', strtotime($updated_at)); ?></span>
                                </div>
                            </div>

                            <!-- Verifikator Info -->
                            <div class="verifikator-info-box <?php echo $verifikator_class; ?>">
                                <div class="verifikator-avatar">
                                    <?php if (!empty($verifikator_nama) && !empty($verifikator_id)): ?>
                                        <?php if (!empty($verifikator_foto) && file_exists('assets/images/profile/' . $verifikator_foto)): ?>
                                            <img src="assets/images/profile/<?php echo $verifikator_foto; ?>" alt="Verifikator">
                                        <?php else: ?>
                                            <div class="default-avatar">
                                                <?php echo strtoupper(substr($verifikator_nama, 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="default-avatar" style="background: <?php echo ($status_verifikasi == 'disetujui') ? '#f39c12' : '#95a5a6'; ?>;">
                                            <?php echo $verifikator_icon; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="verifikator-detail">
                                    <div class="label">Diverifikasi Oleh</div>
                                    <?php if (!empty($verifikator_nama) && !empty($verifikator_id)): ?>
                                        <div class="name">
                                            <?php echo htmlspecialchars($verifikator_nama); ?>
                                            <?php if (!empty($verifikator_username)): ?>
                                                <span class="username">(@<?php echo htmlspecialchars($verifikator_username); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-text">
                                            <?php echo $verifikator_status_text; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Informasi Tambahan -->
                            <div class="info-box-status">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <p>
                                        <strong>Informasi:</strong> 
                                        <?php if ($status_verifikasi == 'disetujui'): ?>
                                            Anda telah terverifikasi sebagai anggota PGNI Lampung. Sertifikat dapat diunduh melalui admin atau hubungi pengurus.
                                        <?php elseif ($status_verifikasi == 'pending'): ?>
                                            Mohon bersabar, admin akan memverifikasi data Anda dalam waktu 1x24 jam. Anda akan mendapat notifikasi setelah diverifikasi.
                                        <?php else: ?>
                                            Silakan hubungi admin PGNI Lampung untuk informasi lebih lanjut mengenai penolakan data Anda.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="action-buttons">
                                
<?php if ($status_verifikasi == 'disetujui' && $guru_id > 0): ?>
    <a href="member/login.php?nik=<?php echo $nik_value; ?>" class="btn-action btn-primary">
        <i class="fas fa-sign-in-alt"></i> Login Member
    </a>
    
<?php elseif ($guru_id > 0): ?>
    <a href="registrasi.php?nik=<?php echo $nik_value; ?>&no_telp=<?php echo urlencode($guru_data['no_telp'] ?? ''); ?>&edit=1" class="btn-action btn-primary" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
        <i class="fas fa-edit"></i> Perbarui Data
    </a>
<?php endif; ?>
                                <button onclick="printResult()" class="btn-action btn-print">
                                    <i class="fas fa-print"></i> Cetak Bukti
                                </button>
                                <a href="cek_status.php" class="btn-action btn-secondary">
                                    <i class="fas fa-redo"></i> Cek Lagi
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FAQ -->
        <div class="faq-section">
            <h4><i class="fas fa-question-circle"></i> Pertanyaan Umum</h4>
            <div class="faq-item">
                <div>
                    <strong>Bagaimana cara mendaftar?</strong>
                    <p>Klik tombol <a href="registrasi.php">Daftar Member</a> di halaman utama atau menu registrasi.</p>
                </div>
                <div>
                    <strong>Berapa lama proses verifikasi?</strong>
                    <p>Proses verifikasi dilakukan dalam waktu 1x24 jam setelah pendaftaran.</p>
                </div>
                <div>
                    <strong>Apa yang harus dilakukan jika status ditolak?</strong>
                    <p>Silakan hubungi admin PGNI Lampung melalui <a href="kontak.php">halaman kontak</a> untuk informasi lebih lanjut.</p>
                </div>
                <div>
                    <strong>Apakah ada biaya pendaftaran?</strong>
                    <p>Pendaftaran menjadi anggota PGNI Lampung adalah <strong>GRATIS</strong> dan tidak dipungut biaya apapun.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validasi NIK 16 digit
    const nikInput = document.getElementById('nikInput');
    if (nikInput) {
        nikInput.addEventListener('input', function() {
            // Hanya angka
            this.value = this.value.replace(/\D/g, '');
            // Batasi 16 digit
            if (this.value.length > 16) {
                this.value = this.value.slice(0, 16);
            }
        });
        
        nikInput.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length !== 16) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });
        
        nikInput.addEventListener('focus', function() {
            this.style.borderColor = '';
        });
    }
    
    // Scroll ke hasil jika ada
    <?php if ($guru_data): ?>
    setTimeout(function() {
        const resultCard = document.querySelector('.result-card');
        if (resultCard) {
            resultCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 300);
    <?php endif; ?>
});

// Fungsi Cetak Bukti
function printResult() {
    const printArea = document.getElementById('printArea');
    if (printArea) {
        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        printArea.setAttribute('data-print-date', dateStr);
    }
    window.print();
}
</script>

<?php include $root_path . '/include/footer.php'; ?>