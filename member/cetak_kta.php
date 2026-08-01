<?php
// member/cetak_kta.php - Cetak Kartu Tanda Anggota (KTA) Digital
$title = "Cetak KTA";
require_once 'include/member_header.php';

// Pastikan koneksi dan data member tersedia dari header
if (!isset($conn) || !isset($member_data)) {
    die("<div class='alert alert-danger m-3'>Terjadi kesalahan: Data tidak dapat dimuat.</div>");
}

// Inisialisasi variabel internal agar aman dari error undefined variable
$member_nik  = $member_data['nik'] ?? '';
$member_nama = $member_data['nama_lengkap'] ?? $member_data['nama'] ?? '';

// Keamanan: Hanya member dengan status 'disetujui' yang bisa mencetak KTA
$status_verifikasi = $member_data['status_verifikasi'] ?? 'pending';
if ($status_verifikasi !== 'disetujui') {
    echo "
    <div class='container-fluid mt-4'>
        <div class='card shadow-sm border-0 border-start border-4 border-warning bg-white p-4'>
            <div class='d-flex align-items-center gap-3'>
                <div class='fs-1'>⚠️</div>
                <div>
                    <h5 class='fw-bold text-dark mb-1'>Kartu Anggota Belum Tersedia</h5>
                    <p class='text-muted mb-0'>Mohon maaf, Anda belum dapat mencetak KTA. Status akun Anda saat ini masih dalam proses <strong>" . strtoupper($status_verifikasi) . "</strong>.</p>
                    <p class='text-muted xsmall mt-2 mb-0'>Silakan hubungi admin wilayah PGNI Lampung untuk percepatan proses verifikasi dokumen administrasi Anda.</p>
                </div>
            </div>
            <div class='mt-3 text-end'>
                <a href='dashboard.php' class='btn btn-success btn-sm px-4'>Kembali ke Dashboard</a>
            </div>
        </div>
    </div>";
    require_once 'include/member_footer.php';
    exit;
}

// ============================================
// AMBIL DATA KETUA UMUM DARI TABEL PENGURUS
// ============================================
function getKetuaUmum($conn) {
    $query = "SELECT nama, jabatan FROM pengurus 
              WHERE jabatan LIKE '%Ketua%' AND status = 'aktif' 
              ORDER BY 
                CASE 
                    WHEN jabatan = 'Ketua Umum' THEN 1 
                    WHEN jabatan LIKE 'Ketua%' THEN 2 
                    ELSE 3 
                END 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    $query2 = "SELECT nama, jabatan FROM pengurus WHERE status = 'aktif' LIMIT 1";
    $result2 = mysqli_query($conn, $query2);
    
    if ($result2 && mysqli_num_rows($result2) > 0) {
        return mysqli_fetch_assoc($result2);
    }
    
    return ['nama' => 'PGNI Lampung', 'jabatan' => 'Ketua Wilayah'];
}

$ketua = getKetuaUmum($conn);

// ============================================
// GENERATE URL CEK STATUS & QR CODE
// ============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $domainName . str_replace("member/cetak_kta.php", "", $_SERVER['SCRIPT_NAME']);

$qr_data = "https://pgni.net/pgnil/cek_status.php?nik=" . urlencode($member_nik);
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_data);

// Fungsi get foto url
function getFotoUrlKTA($foto_profil, $nama) {
    if (!empty($foto_profil)) {
        $foto_path = dirname(__DIR__) . '/uploads/foto/' . $foto_profil;
        if (file_exists($foto_path)) {
            return '../uploads/foto/' . $foto_profil;
        }
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=1a6e3a&color=fff&size=200';
}
$foto_url = getFotoUrlKTA($member_data['foto_profil'] ?? '', $member_nama);

// Path logo
$logo_path = '../assets/images/logo/logo-pgni.png';
if (!file_exists(dirname(__DIR__) . '/assets/images/logo/logo-pgni.png')) {
    $logo_path = 'https://ui-avatars.com/api/?name=PGNI&background=1a6e3a&color=fff&size=100';
}
?>

<style>
/* ============================================
   RESET & BASE
============================================ */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: #f0f2f5;
    font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

/* ============================================
   KTA CARD - LAYOUT KOKOH
============================================ */
.kta-container {
    max-width: 580px;
    margin: 20px auto;
    padding: 0 12px;
}

.kta-card {
    width: 100%;
    background: linear-gradient(145deg, #1a6e3a 0%, #0d4222 50%, #062a15 100%);
    border-radius: 14px;
    border: 3px solid #d4a847;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25), 0 0 0 6px rgba(26,110,58,0.15);
    padding: 18px 20px 16px 20px;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    aspect-ratio: 1 / 0.63;
    min-height: 300px;
}

/* Background Decoration - Lebih Halus */
.kta-card .bg-deco {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0.03;
}
.kta-card .bg-deco.d1 {
    width: 250px;
    height: 250px;
    top: -100px;
    right: -60px;
    background: radial-gradient(circle, #d4a847, transparent);
}
.kta-card .bg-deco.d2 {
    width: 150px;
    height: 150px;
    bottom: -50px;
    left: -50px;
    background: radial-gradient(circle, #d4a847, transparent);
}

/* ===== HEADER ===== */
.kta-header {
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1.5px solid rgba(212,168,71,0.25);
    padding-bottom: 8px;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}

.kta-logo {
    width: 44px;
    height: 44px;
    background: #fff;
    border-radius: 50%;
    padding: 2px;
    border: 2px solid #d4a847;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.kta-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.kta-title {
    flex: 1;
    text-align: left;
    min-width: 0;
}
.kta-title h2 {
    font-size: 17px;
    font-weight: 800;
    margin: 0;
    color: #fff;
    letter-spacing: 0.5px;
    line-height: 1.2;
}
.kta-title h2 span {
    color: #d4a847;
}
.kta-title p {
    font-size: 7.5px;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: rgba(255,255,255,0.6);
    font-weight: 500;
}

/* ===== BODY ===== */
.kta-body {
    display: flex;
    gap: 12px;
    position: relative;
    z-index: 1;
    align-items: flex-start;
}

.kta-photo {
    width: 85px;
    height: 112px;
    border-radius: 7px;
    overflow: hidden;
    border: 2.5px solid #d4a847;
    flex-shrink: 0;
    background: #0d4222;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
.kta-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.kta-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.kta-info-table {
    width: 100%;
    border-collapse: collapse;
}

.kta-info-table td {
    padding: 2px 0;
    vertical-align: top;
}

.kta-info-table td.label-col {
    width: 65px; /* Dikecilkan dari 90px agar kolom kanan lebih luas */
    font-size: 7px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: rgba(255,255,255,0.5);
    font-weight: 500;
    line-height: 1.3;
    white-space: nowrap;
}

.kta-info-table td.value-col {
    font-size: 10.5px;
    font-weight: 600;
    color: #ffffff;
    line-height: 1.3;
    padding-left: 4px;
    word-break: break-word;
}

.kta-info-table td.value-col.nik {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #fbd374;
    font-weight: 700;
    letter-spacing: 0.2px;
}

.kta-info-table td.value-col.nama {
    font-size: 11.5px;
    font-weight: 700;
}

/* ===== QR CODE ===== */
.kta-qr-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
    margin-left: 6px;
}
.kta-qr-section img {
    width: 56px;
    height: 56px;
    background: #fff;
    padding: 3px;
    border-radius: 5px;
    border: 2px solid #d4a847;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.kta-qr-section .label-qr {
    font-size: 5.5px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: rgba(255,255,255,0.4);
    margin: 0;
}

/* ===== FOOTER ===== */
.kta-footer {
    position: absolute;
    bottom: 14px;
    right: 20px;
    text-align: right;
    z-index: 1;
}
.kta-footer .job {
    font-size: 7px;
    color: rgba(255,255,255,0.45);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.kta-footer .signature {
    font-size: 10.5px;
    font-weight: 700;
    color: #ffffff;
    margin-top: 1px;
    letter-spacing: 0.2px;
}

/* ============================================
   BUTTONS & TIPS
============================================ */
.btn-print-wrapper {
    text-align: center;
    margin: 25px 0 35px 0;
    padding: 0 15px;
}
.btn-print {
    padding: 13px 40px;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    border: none;
    border-radius: 11px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(26,110,58,0.3);
}
.btn-print:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 35px rgba(26,110,58,0.4);
}
.print-tips {
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 10px;
    padding: 0 10px;
}
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
}

/* ============================================
   RESPONSIVE MOBILE
============================================ */
@media screen and (max-width: 480px) {
    .kta-container {
        padding: 0 8px;
        margin: 12px auto;
    }

    .kta-card {
        padding: 12px 12px 10px 12px;
        border-radius: 10px;
        border-width: 2px;
        min-height: 240px;
        aspect-ratio: 1 / 0.6;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2), 0 0 0 4px rgba(26,110,58,0.12);
    }

    .kta-header {
        gap: 8px;
        padding-bottom: 5px;
        margin-bottom: 8px;
        border-bottom-width: 1px;
    }

    .kta-logo {
        width: 30px;
        height: 30px;
        padding: 1.5px;
        border-width: 1.5px;
    }

    .kta-title h2 {
        font-size: 12px;
        letter-spacing: 0.2px;
        line-height: 1.1;
    }

    .kta-title p {
        font-size: 5.5px;
        letter-spacing: 0.8px;
    }

    .kta-body {
        gap: 8px;
        align-items: flex-start;
    }

    .kta-photo {
        width: 58px;
        height: 76px;
        border-width: 1.8px;
        border-radius: 5px;
        flex-shrink: 0;
    }

    .kta-info-table td {
        padding: 1.5px 0;
    }

    .kta-info-table td.label-col {
        width: 45px;
        font-size: 5.5px;
        letter-spacing: 0.3px;
        padding-right: 2px;
    }

    .kta-info-table td.value-col {
        font-size: 8px;
        padding-left: 2px;
        letter-spacing: 0.1px;
        line-height: 1.2;
    }

    .kta-info-table td.value-col.nik {
        font-size: 8.5px;
        letter-spacing: 0.1px;
    }

    .kta-info-table td.value-col.nama {
        font-size: 8.5px;
    }

    .kta-qr-section {
        margin-left: 3px;
        gap: 2px;
    }

    .kta-qr-section img {
        width: 38px;
        height: 38px;
        padding: 2px;
        border-width: 1.5px;
        border-radius: 4px;
    }

    .kta-qr-section .label-qr {
        font-size: 4px;
        letter-spacing: 0.4px;
    }

    .kta-footer {
        bottom: 10px;
        right: 12px;
    }

    .kta-footer .job {
        font-size: 5px;
        letter-spacing: 0.3px;
    }

    .kta-footer .signature {
        font-size: 7.5px;
        margin-top: 0px;
        letter-spacing: 0.1px;
    }

    .kta-card .bg-deco {
        display: none !important;
    }

    .btn-print {
        padding: 10px 24px;
        font-size: 0.8rem;
        gap: 8px;
        border-radius: 9px;
        width: 100%;
        justify-content: center;
    }

    .btn-print-wrapper {
        margin: 18px 0 25px 0;
        padding: 0 12px;
    }

    .print-tips {
        font-size: 0.65rem;
        margin-top: 8px;
        padding: 0 5px;
    }
}

/* ============================================
   PRINT STYLES - UKURAN PRESISI & ANTI POTONG
============================================ */
@media print {
    body * {
        visibility: hidden !important;
    }
    
    #printable-area, #printable-area * {
        visibility: visible !important;
    }
    
    #printable-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 85.6mm !important;
        height: 54mm !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .kta-card {
        width: 85.6mm !important;
        height: 54mm !important;
        border-radius: 4mm !important;
        border: 1.5px solid #d4a847 !important;
        padding: 4mm 4.5mm !important;
        background: linear-gradient(145deg, #1a6e3a 0%, #0d4222 50%, #062a15 100%) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
        aspect-ratio: auto !important;
        min-height: auto !important;
    }
    
    .kta-logo {
        width: 8.5mm !important;
        height: 8.5mm !important;
        padding: 0.5mm !important;
    }
    .kta-title h2 {
        font-size: 11pt !important;
    }
    .kta-title p {
        font-size: 5pt !important;
    }
    .kta-header {
        padding-bottom: 1.5mm !important;
        margin-bottom: 2mm !important;
        gap: 2mm !important;
    }
    
    .kta-body {
        gap: 2.5mm !important;
    }
    .kta-photo {
        width: 17.5mm !important;
        height: 23mm !important;
        border-width: 1.5px !important;
    }
    
    /* PENYELARASAN TABEL DATA SAAT DI-PRINT */
    .kta-info-table td.label-col {
        width: 13mm !important;  /* Diperkecil agar kolom nilai mendapat porsi lebih luas */
        font-size: 4.5pt !important;
    }
    .kta-info-table td.value-col {
        font-size: 6.8pt !important; /* Dikecilkan sedikit agar muat satu baris lurus */
    }
    .kta-info-table td.value-col.nik {
        font-size: 7.2pt !important; /* Khusus NIK agar presisi tidak turun ke bawah */
        letter-spacing: 0px !important;
    }
    .kta-info-table td.value-col.nama {
        font-size: 7.2pt !important;
    }
    .kta-info-table td {
        padding: 0.4mm 0 !important;
    }
    
    .kta-qr-section img {
        width: 11.5mm !important;
        height: 11.5mm !important;
        padding: 0.5mm !important;
    }
    .kta-qr-section .label-qr {
        font-size: 4pt !important;
    }
    
    .kta-footer {
        bottom: 3mm !important;
        right: 4.5mm !important;
    }
    .kta-footer .job {
        font-size: 5pt !important;
    }
    .kta-footer .signature {
        font-size: 7.5pt !important;
    }
    
    .kta-card .bg-deco {
        display: none !important;
    }
}
</style>

<!-- ============================================
     HALAMAN CETAK KTA
============================================ -->
<div class="container-fluid">
    <!-- Header Web View -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3 print-hidden" style="flex-wrap: wrap; gap: 8px;">
        <div>
            <h4 class="fw-bold text-success mb-0" style="font-size: 1.1rem;">
                <i class="fas fa-id-card" style="font-size: 0.95rem;"></i> Kartu Anggota Digital
            </h4>
            <small class="text-muted" style="font-size: 0.7rem;">KTA resmi anggota PGNI Provinsi Lampung</small>
        </div>
        <div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- KTA Card Container -->
    <div id="printable-area" class="kta-container">
        <div class="kta-card">
            <!-- Background Decoration -->
            <div class="bg-deco d1"></div>
            <div class="bg-deco d2"></div>

            <!-- Header -->
            <div class="kta-header">
                <div class="kta-logo">
                    <img src="<?php echo $logo_path; ?>" alt="PGNI Logo" onerror="this.src='https://ui-avatars.com/api/?name=PGNI&background=1a6e3a&color=fff&size=100'">
                </div>
                <div class="kta-title">
                    <h2>PGNI <span>LAMPUNG</span></h2>
                    <p>Persatuan Guru Ngaji Indonesia</p>
                </div>
            </div>

            <!-- Body -->
            <div class="kta-body">
                <!-- Foto Anggota -->
                <div class="kta-photo">
                    <img src="<?php echo $foto_url; ?>" 
                         alt="Foto Anggota"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($member_nama); ?>&background=1a6e3a&color=fff&size=200'">
                </div>

                <!-- Informasi Menggunakan Tabel Murni Tanpa Pemotong Teks substr() -->
                <div class="kta-info">
                    <table class="kta-info-table">
                        <tr>
                            <td class="label-col">Nomor</td>
                            <td class="value-col nik"><?php echo htmlspecialchars($member_nik); ?></td>
                        </tr>
                        <tr>
                            <td class="label-col">Nama</td>
                            <td class="value-col nama"><?php echo htmlspecialchars(strtoupper($member_nama)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-col">Lembaga</td>
                            <td class="value-col"><?php echo htmlspecialchars(strtoupper($member_data['nama_lembaga'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <td class="label-col">Wilayah</td>
                            <td class="value-col"><?php echo htmlspecialchars(strtoupper($member_data['kabupaten_kota'] ?? '-')); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- QR Code Validasi -->
                <div class="kta-qr-section">
                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code Validasi" onerror="this.src='https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=<?php echo urlencode($qr_data); ?>&choe=UTF-8'">
                    <p class="label-qr">Scan</p>
                </div>
            </div>

            <!-- Footer Tanda Tangan -->
            <div class="kta-footer">
                <p class="job"><?php echo htmlspecialchars(strtoupper($ketua['jabatan'] ?? 'KETUA WILAYAH')); ?></p>
                <p class="signature"><?php echo htmlspecialchars($ketua['nama'] ?? 'PGNI LAMPUNG'); ?></p>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Web View -->
    <div class="btn-print-wrapper print-hidden">
        <button onclick="window.print();" class="btn-print">
            <i class="fas fa-print"></i> Cetak Kartu Anggota
        </button>
        <p class="print-tips">
            <i class="fas fa-info-circle"></i> 
            <strong>Tips Cetak:</strong> Centang opsi <strong>"Background Graphics"</strong> pada dialog print browser Anda.
        </p>
    </div>
</div>

<?php require_once 'include/member_footer.php'; ?>