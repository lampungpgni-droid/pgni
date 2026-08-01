<?php
// member/cetak_sertifikat.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$member_id = $_SESSION['member_id'];

// Ambil data nama, nik, dan tanggal terbit
$query = "SELECT nama, nik, IFNULL(created_at, NOW()) AS tanggal_terbit FROM guru_ngaji WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    die("Data anggota tidak ditemukan.");
}

$nama_guru = $guru['nama'];
$nik_guru  = $guru['nik'];

// Fungsi konversi tanggal ke format Indonesia
function tgl_indo($tanggal) {
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $split = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
$tanggal_sertifikat = tgl_indo($guru['tanggal_terbit']);

$title = 'Cetak Sertifikat';
include 'include/member_header.php';
?>

<style>
/* ==================================================================
   1. TAMPILAN SCREEN (WEB & MOBILE DASHBOARD RESPONSIF)
   ================================================================== */
.cert-page-wrapper {
    max-width: 800px;
    margin: 0 auto 40px;
    padding: 0 16px;
    width: 100%;
}

.cert-action-box {
    background: #fff;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #f0f2f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.cert-instruction h4 {
    margin: 0 0 4px 0;
    color: #1a1a2e;
}
.cert-instruction p {
    margin: 0;
    font-size: 0.85rem;
    color: #6b7280;
}

.btn-trigger-print {
    padding: 10px 24px;
    background: linear-gradient(135deg, #1a6e3a, #0e4a26);
    color: #fff !important;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(26,110,58,0.2);
    transition: all 0.2s;
}

.btn-trigger-print:hover {
    transform: translateY(-2px);
}

.cert-preview-area {
    width: 100%;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    display: flex;
    justify-content: center;
    overflow: hidden; 
    container-type: inline-size;
}

.cert-aspect-ratio-box {
    width: 100%;
    max-width: 550px; 
    position: relative;
    aspect-ratio: 210 / 297; 
    background-image: url('../assets/images/SERTIFIKAT_PGNII.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    background-color: #fff;
}

.identity-content {
    position: absolute;
    width: 100%;
    top: 46.5%; 
    left: 0;
    text-align: center;
    padding: 0 10%;
}

.nama-guru {
    font-size: 4.5cqw; 
    font-weight: bold;
    color: #113319; 
    text-transform: uppercase;
    font-family: 'Times New Roman', Times, serif;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.date-content {
    position: absolute;
    width: 100%;
    top: 69.4%; 
    left: 0;
}

.text-tanggal {
    font-size: 2.2cqw; 
    font-weight: bold;
    color: #333;
    margin: 0;
    padding-left: 71.5%; 
    text-align: left;
    font-family: 'Times New Roman', Times, serif;
}

/* ==================================================================
   2. FIX PRINT MODE (HANYA MENCETAK SERTIFIKAT A4)
   ================================================================== */
@media print {
    /* Sembunyikan semua komponen pembungkus luar layout dashboard */
    header, footer, nav, aside, 
    .sidebar, .navbar, .cert-action-box, .btn-trigger-print,
    #sidebar-wrapper, .main-header, .footer, .nav-tabs, .breadcrumb,
    .left-sidebar, .menu-sidebar {
        display: none !important;
    }

    /* Hilangkan pengaruh margin padding dari container bawaan dashboard admin */
    .main-content, .content-wrapper, .wrapper, #wrapper, .member-dashboard, .cert-page-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        width: 210mm !important;
        height: 297mm !important;
        max-width: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    
    /* Paksa setup kertas A4 putih bersih */
    html, body {
        background-color: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
    }

    /* Set area preview agar lepas dari posisi layouting web */
    .cert-preview-area {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        background: #fff !important;
        overflow: visible !important;
    }

    /* Maksimalkan kotak sertifikat A4 */
    .cert-aspect-ratio-box {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        max-width: none !important;
        box-shadow: none !important;
        border: none !important;
        background-image: url('../assets/images/SERTIFIKAT_PGNII.jpg') !important;
        background-size: 100% 100% !important;
        page-break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Koordinat presisi millimeter cetak asli */
    .identity-content {
        position: absolute !important;
        width: 100% !important;
        top: 138mm !important;
        left: 0 !important;
        padding: 0 30mm !important;
        text-align: center !important;
    }

    .nama-guru {
        font-size: 26pt !important;
        white-space: normal !important;
        color: #113319 !important;
        display: block !important;
    }

    .date-content {
        position: absolute !important;
        width: 100% !important;
        top: 206mm !important;
        left: 0 !important;
    }

    .text-tanggal {
        font-size: 12pt !important;
        padding-left: 150mm !important;
        color: #333 !important;
        text-align: left !important;
    }
}

@page {
    size: A4 portrait;
    margin: 0;
}
</style>

<div class="cert-page-wrapper">
    <!-- Tombol Navigasi/Aksi -->
    <div class="cert-action-box">
        <div class="cert-instruction">
            <h4>Pratinjau Sertifikat Resmi</h4>
            <p>Tampilan telah disesuaikan agar rapi di HP. Tekan tombol di kanan untuk mencetak.</p>
        </div>
        <button class="btn-trigger-print" onclick="window.print()">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <!-- Area Kontainer Sertifikat -->
    <div class="cert-preview-area">
        <div class="cert-aspect-ratio-box">
            
            <!-- Elemen Nama -->
            <div class="identity-content">
                <div class="nama-guru"><?php echo htmlspecialchars($nama_guru); ?></div>
            </div>

            <!-- Elemen Tanggal -->
            <div class="date-content">
                <p class="text-tanggal"><?php echo $tanggal_sertifikat; ?></p>
            </div>

        </div>
    </div>
</div>

<?php include 'include/member_footer.php'; ?>