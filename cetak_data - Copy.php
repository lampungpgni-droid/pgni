<?php
// admin/cetak_data.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$title = 'Cetak Data Guru';

// ============================================
// GET FILTER
// ============================================
$filter_kabupaten = isset($_GET['kabupaten']) ? trim($_GET['kabupaten']) : '';
$filter_kecamatan = isset($_GET['kecamatan']) ? trim($_GET['kecamatan']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// ============================================
// AMBIL DATA GURU DENGAN JOIN
// ============================================
$data_guru = [];
$query_guru = "
    SELECT g.*, 
           kb.nama as nama_kabupaten, 
           k.nama as nama_kecamatan
    FROM guru_ngaji g
    LEFT JOIN kabupaten kb ON g.kabupaten_id = kb.id
    LEFT JOIN kecamatan k ON g.kecamatan_id = k.id
    WHERE 1=1
";

if (!empty($filter_kabupaten)) {
    $filter_kabupaten = mysqli_real_escape_string($conn, $filter_kabupaten);
    $query_guru .= " AND kb.nama = '$filter_kabupaten'";
}

if (!empty($filter_kecamatan)) {
    $filter_kecamatan = mysqli_real_escape_string($conn, $filter_kecamatan);
    $query_guru .= " AND k.nama = '$filter_kecamatan'";
}

if (!empty($filter_status)) {
    $filter_status = mysqli_real_escape_string($conn, $filter_status);
    $query_guru .= " AND g.status_verifikasi = '$filter_status'";
}

$query_guru .= " ORDER BY kb.nama, k.nama, g.nama ASC";
$result_guru = mysqli_query($conn, $query_guru);
if ($result_guru) {
    while ($row = mysqli_fetch_assoc($result_guru)) {
        $data_guru[] = $row;
    }
}

// ============================================
// AMBIL DATA UNTUK FILTER
// ============================================
$kabupaten_list = [];
$query_kab = "SELECT DISTINCT nama FROM kabupaten ORDER BY nama ASC";
$result_kab = mysqli_query($conn, $query_kab);
if ($result_kab) {
    while ($row = mysqli_fetch_assoc($result_kab)) {
        $kabupaten_list[] = $row['nama'];
    }
}

$kecamatan_list = [];
$query_kec = "SELECT DISTINCT nama FROM kecamatan ORDER BY nama ASC";
$result_kec = mysqli_query($conn, $query_kec);
if ($result_kec) {
    while ($row = mysqli_fetch_assoc($result_kec)) {
        $kecamatan_list[] = $row['nama'];
    }
}

// ============================================
// GROUP DATA PER KABUPATEN & KECAMATAN
// ============================================
$grouped_data = [];
foreach ($data_guru as $guru) {
    $kab = $guru['nama_kabupaten'] ?? 'Tidak Diketahui';
    $kec = $guru['nama_kecamatan'] ?? 'Tidak Diketahui';
    
    if (!isset($grouped_data[$kab])) {
        $grouped_data[$kab] = [];
    }
    if (!isset($grouped_data[$kab][$kec])) {
        $grouped_data[$kab][$kec] = [];
    }
    $grouped_data[$kab][$kec][] = $guru;
}

include 'include/admin_header.php';
?>

<style>
    /* ============================================
       KOP SURAT - LOGO KIRI
    ============================================ */
    .kop-surat {
        padding: 15px 0 15px 0;
        border-bottom: 3px double #1a6e3a;
        margin-bottom: 20px;
        display: none;
        overflow: hidden;
    }

    .kop-surat .logo-wrapper {
        display: flex;
        align-items: center;
        gap: 20px;
        float: left;
    }

    .kop-surat .logo-wrapper img {
        max-height: 75px;
        width: auto;
        object-fit: contain;
    }

    .kop-surat .logo-wrapper .logo-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: 'Amiri', serif;
        flex-shrink: 0;
    }

    .kop-surat .kop-text {
        float: left;
        text-align: left;
        padding-left: 10px;
    }

    .kop-surat .kop-text .nama-organisasi {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a6e3a;
        letter-spacing: 1px;
        font-family: 'Amiri', serif;
    }

    .kop-surat .kop-text .nama-organisasi span {
        color: #d4a847;
    }

    .kop-surat .kop-text .alamat {
        font-size: 0.8rem;
        color: #555;
        margin-top: 2px;
    }

    .kop-surat .kop-text .alamat i {
        color: #d4a847;
        margin: 0 4px;
    }

    .kop-surat .judul-laporan {
        clear: both;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin-top: 10px;
        padding: 6px 0;
        border-top: 2px solid #1a6e3a;
        border-bottom: 2px solid #1a6e3a;
        display: block;
    }

    .kop-surat .info-filter {
        text-align: center;
        font-size: 0.8rem;
        color: #666;
        margin-top: 6px;
    }

    .kop-surat .info-filter span {
        background: #f8f9fa;
        padding: 2px 12px;
        border-radius: 20px;
        margin: 0 4px;
        border: 1px solid #e9ecef;
        display: inline-block;
        font-size: 0.75rem;
    }

    /* ============================================
       MAIN CONTAINER
    ============================================ */
    .print-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 5px;
    }

    /* ============================================
       HEADER
    ============================================ */
    .print-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
        background: #fff;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .print-header h1 {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .print-header h1 i {
        color: #d4a847;
    }

    /* ============================================
       FILTER SECTION
    ============================================ */
    .filter-section {
        background: #fff;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        border: 2px solid #1a6e3a;
        border-left: 5px solid #d4a847;
    }

    .filter-section label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .filter-section label i {
        color: #d4a847;
    }

    .filter-section select {
        padding: 6px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.8rem;
        background: #fff;
        min-width: 140px;
    }

    .filter-section select:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
    }

    .filter-section .filter-actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }

    .btn-filter {
        padding: 6px 16px;
        border: none;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
    }

    .btn-filter-primary {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-filter-primary:hover {
        background: #0e4a26;
    }

    .btn-filter-secondary {
        background: #6c757d;
        color: #fff;
    }
    .btn-filter-secondary:hover {
        background: #5a6268;
    }

    .btn-print {
        padding: 6px 14px;
        border: none;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-print-primary {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-print-primary:hover {
        background: #0e4a26;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 110, 58, 0.3);
    }

    .btn-print-success {
        background: #28a745;
        color: #fff;
    }
    .btn-print-success:hover {
        background: #1e7e34;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-print-danger {
        background: #dc3545;
        color: #fff;
    }
    .btn-print-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-print-info {
        background: #17a2b8;
        color: #fff;
    }
    .btn-print-info:hover {
        background: #138496;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }

    /* ============================================
       SECTION
    ============================================ */
    .print-section {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid #f0f0f0;
    }

    .print-section-header {
        padding: 10px 16px;
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .print-section-header h3 {
        margin: 0;
        font-size: 0.9rem;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .print-section-header h3 i {
        color: #d4a847;
        font-size: 0.95rem;
    }

    .print-section-header .count-badge {
        background: #1a6e3a;
        color: #fff;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .print-section-header .filter-info {
        font-size: 0.7rem;
        color: #6c757d;
        background: #e9ecef;
        padding: 2px 10px;
        border-radius: 20px;
        margin-right: 4px;
    }

    /* ============================================
       TABLE - AUTO PRESISI KOLOM
    ============================================ */
    .print-table-wrapper {
        overflow-x: auto;
        padding: 0;
        -webkit-overflow-scrolling: touch;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
        table-layout: auto;
    }

    .print-table thead {
        background: #1a6e3a;
        color: #fff;
    }

    .print-table thead th {
        padding: 8px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #0e4a26;
        white-space: nowrap;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: #1a6e3a;
        color: #fff;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .print-table td {
        padding: 7px 12px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
        vertical-align: middle;
        font-size: 0.75rem;
        word-break: break-word;
    }

    .print-table tbody tr:nth-child(even) {
        background: #fafafa;
    }

    .print-table tbody tr:hover {
        background: #e8f5e9;
    }

    .print-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* ============================================
       SUB TOTAL & GRAND TOTAL
    ============================================ */
    .print-table .sub-total {
        background: #f5f5f5 !important;
        font-weight: 600;
    }

    .print-table .sub-total td {
        border-top: 2px solid #1a6e3a;
        padding: 6px 12px;
        font-size: 0.75rem;
        color: #1a6e3a;
    }

    .print-table .sub-total-kabupaten {
        background: #e8f5e9 !important;
        font-weight: 700;
    }

    .print-table .sub-total-kabupaten td {
        border-top: 3px solid #1a6e3a;
        padding: 7px 12px;
        font-size: 0.8rem;
        color: #1a6e3a;
    }

    .print-table .grand-total {
        background: #1a6e3a !important;
        color: #fff !important;
    }

    .print-table .grand-total td {
        border-top: 3px solid #0e4a26;
        padding: 8px 12px;
        font-size: 0.85rem;
        color: #fff !important;
        font-weight: 700;
    }

    /* ============================================
       STATUS BADGE
    ============================================ */
    .print-table .status-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.6rem;
        font-weight: 500;
        text-transform: capitalize;
        letter-spacing: 0.2px;
        white-space: nowrap;
        min-width: 40px;
        text-align: center;
    }

    .print-table .status-badge.disetujui,
    .print-table .status-badge.aktif,
    .print-table .status-badge.verified {
        background: #d4edda;
        color: #155724;
    }

    .print-table .status-badge.pending,
    .print-table .status-badge.waiting {
        background: #fff3cd;
        color: #856404;
    }

    .print-table .status-badge.ditolak,
    .print-table .status-badge.nonaktif,
    .print-table .status-badge.rejected {
        background: #f8d7da;
        color: #721c24;
    }

    /* ============================================
       EMPTY DATA
    ============================================ */
    .empty-data {
        text-align: center;
        padding: 30px 15px;
        color: #999;
    }

    .empty-data i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
        color: #ddd;
    }

    .empty-data p {
        font-size: 0.85rem;
        margin: 0;
    }

    /* ============================================
       FOOTER
    ============================================ */
    .print-footer {
        text-align: center;
        padding: 12px 0;
        color: #999;
        font-size: 0.7rem;
        border-top: 1px solid #e9ecef;
        margin-top: 10px;
    }

    .print-footer i {
        margin: 0 4px;
    }

    .print-footer .separator {
        margin: 0 6px;
        color: #ddd;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 992px) {
        .filter-section select {
            min-width: 120px;
        }
    }

    @media (max-width: 768px) {
        .print-header {
            flex-direction: column;
            align-items: stretch;
            padding: 12px 15px;
        }
        .print-header h1 {
            font-size: 1rem;
            justify-content: center;
        }
        .print-actions {
            justify-content: center;
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .btn-print {
            flex: 0 0 auto;
            padding: 5px 10px;
            font-size: 0.65rem;
        }
        .filter-section {
            flex-direction: column;
            align-items: stretch;
            padding: 12px 15px;
        }
        .filter-section select {
            min-width: 100%;
        }
        .filter-section .filter-actions {
            margin-left: 0;
            justify-content: stretch;
        }
        .filter-section .filter-actions .btn-filter {
            flex: 1;
            justify-content: center;
        }
        .print-table {
            font-size: 0.65rem;
        }
        .print-table thead th {
            font-size: 0.58rem;
            padding: 5px 8px;
        }
        .print-table td {
            padding: 5px 8px;
            font-size: 0.62rem;
        }
        .print-table .status-badge {
            font-size: 0.5rem;
            padding: 1px 6px;
            min-width: 28px;
        }
        .kop-surat .logo-wrapper img {
            max-height: 50px;
        }
        .kop-surat .kop-text .nama-organisasi {
            font-size: 1.1rem;
        }
        .kop-surat .judul-laporan {
            font-size: 0.9rem;
        }
        .print-table .sub-total-kabupaten td {
            font-size: 0.7rem;
            padding: 5px 8px;
        }
        .print-table .grand-total td {
            font-size: 0.75rem;
            padding: 6px 8px;
        }
    }

    @media (max-width: 480px) {
        .print-header h1 {
            font-size: 0.85rem;
        }
        .btn-print {
            padding: 4px 8px;
            font-size: 0.55rem;
        }
        .print-table {
            font-size: 0.58rem;
        }
        .print-table thead th {
            font-size: 0.5rem;
            padding: 4px 6px;
        }
        .print-table td {
            padding: 4px 6px;
            font-size: 0.55rem;
        }
        .print-table .status-badge {
            font-size: 0.45rem;
            padding: 1px 4px;
            min-width: 22px;
        }
        .print-table .sub-total td,
        .print-table .sub-total-kabupaten td,
        .print-table .grand-total td {
            padding: 4px 6px;
            font-size: 0.65rem;
        }
    }

    /* ============================================
       PRINT STYLES
    ============================================ */
    @media print {
        body {
            background: #fff !important;
            margin: 0;
            padding: 0;
        }

        .admin-sidebar,
        .admin-topbar,
        .print-actions,
        .btn-print,
        .filter-section .filter-actions,
        .sidebar-overlay,
        .mobile-toggle,
        .print-header {
            display: none !important;
        }

        .kop-surat {
            display: block !important;
        }

        .filter-section {
            border: 1px solid #ddd !important;
            background: #f8f9fa !important;
            padding: 6px 10px !important;
            margin-bottom: 10px !important;
            border-left: 3px solid #d4a847 !important;
        }

        .filter-section select {
            border: 1px solid #ccc !important;
            background: #fff !important;
            font-size: 0.65rem !important;
            padding: 3px 6px !important;
        }

        .filter-section label {
            font-size: 0.65rem !important;
        }

        .filter-section .filter-actions {
            display: none !important;
        }

        .admin-main {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        .admin-content {
            padding: 5px !important;
        }

        .print-section {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            page-break-inside: avoid;
            margin-bottom: 8px !important;
        }

        .print-section-header {
            background: #f8f9fa !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table thead {
            background: #1a6e3a !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table thead th {
            background: #1a6e3a !important;
            color: #fff !important;
        }

        .print-table .status-badge {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table .status-badge.disetujui,
        .print-table .status-badge.aktif,
        .print-table .status-badge.verified {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .print-table .status-badge.pending,
        .print-table .status-badge.waiting {
            background: #fff3cd !important;
            color: #856404 !important;
        }

        .print-table .status-badge.ditolak,
        .print-table .status-badge.nonaktif,
        .print-table .status-badge.rejected {
            background: #f8d7da !important;
            color: #721c24 !important;
        }

        .print-table .sub-total {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table .sub-total-kabupaten {
            background: #e8f5e9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table .grand-total {
            background: #1a6e3a !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-table .grand-total td {
            color: #fff !important;
        }

        .print-table {
            font-size: 0.65rem !important;
            width: 100% !important;
            table-layout: auto !important;
        }

        .print-table th,
        .print-table td {
            padding: 4px 8px !important;
        }

        .print-footer {
            border-top: 1px solid #ddd !important;
            font-size: 0.55rem !important;
        }

        .kop-surat .logo-wrapper img {
            max-height: 60px !important;
        }
        .kop-surat .kop-text .nama-organisasi {
            font-size: 1.2rem !important;
        }
        .kop-surat .judul-laporan {
            font-size: 0.9rem !important;
            padding: 4px 0 !important;
        }
        .kop-surat .kop-text .alamat {
            font-size: 0.65rem !important;
        }
        .kop-surat .info-filter {
            font-size: 0.65rem !important;
        }
        .kop-surat .info-filter span {
            font-size: 0.6rem !important;
            padding: 1px 8px !important;
        }
        .print-section-header h3 {
            font-size: 0.75rem !important;
        }
        .print-section-header .count-badge {
            font-size: 0.55rem !important;
            padding: 1px 10px !important;
        }
        .print-table .sub-total td {
            font-size: 0.65rem !important;
            padding: 4px 8px !important;
        }
        .print-table .sub-total-kabupaten td {
            font-size: 0.7rem !important;
            padding: 5px 8px !important;
        }
        .print-table .grand-total td {
            font-size: 0.75rem !important;
            padding: 6px 8px !important;
        }
    }
</style>

<div class="dashboard-content">
    <div class="print-container">

        <!-- ==========================================
        KOP SURAT - LOGO KIRI
        ========================================== -->
        <div class="kop-surat">
            <div class="logo-wrapper">
                <?php
                $logo_paths = [
                    '../admin/images/default.jpg',
                    '../admin/images/logo.png',
                    '../admin/images/logo_pgni.png',
                    '../admin/images/logo_pgni.jpg',
                    '../admin/images/default.png'
                ];
                $logo_found = false;
                foreach ($logo_paths as $path) {
                    if (file_exists($root_path . '/admin/images/' . basename($path))) {
                        echo '<img src="' . $path . '" alt="Logo PGNI Lampung">';
                        $logo_found = true;
                        break;
                    }
                }
                if (!$logo_found) {
                    echo '<div class="logo-placeholder">PGNI</div>';
                }
                ?>
                <div class="kop-text">
                    <div class="nama-organisasi">PGNI <span>Lampung</span></div>
                    <div class="alamat">
                        <i class="fas fa-map-marker-alt"></i> Gg. Pondok No.16, Durian Payung, Tanjung Karang Pusat, Bandar Lampung 35116
                        <i class="fas fa-phone"></i> 0812-7343-7568
                        <i class="fas fa-envelope"></i> info@pgnilampung.org
                    </div>
                </div>
            </div>
            <div class="judul-laporan">
                <i class="fas fa-users"></i> LAPORAN DATA GURU NGAJI PGNI LAMPUNG
            </div>
            <div class="info-filter">
                <?php if (!empty($filter_kabupaten)): ?>
                <span><i class="fas fa-building"></i> Kab. <?php echo htmlspecialchars($filter_kabupaten); ?></span>
                <?php endif; ?>
                <?php if (!empty($filter_kecamatan)): ?>
                <span><i class="fas fa-map-marker-alt"></i> Kec. <?php echo htmlspecialchars($filter_kecamatan); ?></span>
                <?php endif; ?>
                <?php if (!empty($filter_status)): ?>
                <span><i class="fas fa-info-circle"></i> Status: <?php echo ucfirst($filter_status); ?></span>
                <?php endif; ?>
                <?php if (empty($filter_kabupaten) && empty($filter_kecamatan) && empty($filter_status)): ?>
                <span><i class="fas fa-database"></i> Seluruh Data</span>
                <?php endif; ?>
                <span><i class="fas fa-calendar-alt"></i> <?php echo tanggal_indonesia(date('Y-m-d')); ?></span>
            </div>
        </div>

        <!-- ==========================================
        HEADER
        ========================================== -->
        <div class="print-header">
            <h1>
                <i class="fas fa-print"></i>
                Cetak Data Guru
            </h1>
            <div class="print-actions">
                <button onclick="window.print()" class="btn-print btn-print-primary">
                    <i class="fas fa-print"></i> Cetak PDF
                </button>
                <button onclick="exportToCSV()" class="btn-print btn-print-success">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button onclick="exportToExcel()" class="btn-print btn-print-info">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
        </div>

        <!-- ==========================================
        FILTER
        ========================================== -->
        <form method="GET" action="" class="filter-section" id="filterForm">
            <label><i class="fas fa-filter"></i> Filter:</label>

            <select name="kabupaten" id="filterKabupaten">
                <option value="">-- Kabupaten --</option>
                <?php foreach ($kabupaten_list as $kab): ?>
                <option value="<?php echo htmlspecialchars($kab); ?>" <?php echo $filter_kabupaten == $kab ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($kab); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="kecamatan" id="filterKecamatan">
                <option value="">-- Kecamatan --</option>
                <?php foreach ($kecamatan_list as $kec): ?>
                <option value="<?php echo htmlspecialchars($kec); ?>" <?php echo $filter_kecamatan == $kec ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($kec); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="status" id="filterStatus">
                <option value="">-- Status --</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="disetujui" <?php echo $filter_status == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
            </select>

            <div class="filter-actions">
                <button type="submit" class="btn-filter btn-filter-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="cetak_data.php" class="btn-filter btn-filter-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>

        <!-- ==========================================
        DATA GURU - GROUPED BY KABUPATEN & KECAMATAN
        ========================================== -->
        <div class="print-section">
            <div class="print-section-header">
                <h3>
                    <i class="fas fa-users"></i>
                    Daftar Guru Ngaji
                    <?php if (!empty($filter_kabupaten)): ?>
                    <span class="filter-info"><i class="fas fa-building"></i> <?php echo htmlspecialchars($filter_kabupaten); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($filter_kecamatan)): ?>
                    <span class="filter-info"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($filter_kecamatan); ?></span>
                    <?php endif; ?>
                </h3>
                <span class="count-badge"><i class="fas fa-user"></i> <?php echo count($data_guru); ?> Guru</span>
            </div>

            <?php if (count($data_guru) > 0): ?>
            <div class="print-table-wrapper">
                <table class="print-table" id="table-guru">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">NO</th>
                            <th style="min-width:140px;">NAMA</th>
                            <th style="min-width:100px;">PROFESI</th>
                            <th style="min-width:140px;">TEMPAT MENGAJAR</th>
                            <th style="min-width:80px;">BANK</th>
                            <th style="min-width:110px;">NO. REKENING</th>
                            <th style="min-width:100px;">NO. HP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no_global = 1;
                        $grand_total = 0;
                        
                        foreach ($grouped_data as $kabupaten => $kecamatan_data):
                            $kab_total = 0;
                            $kab_first = true;
                            
                            foreach ($kecamatan_data as $kecamatan => $gurus):
                                $kec_total = count($gurus);
                                $kab_total += $kec_total;
                                $kec_first = true;
                                
                                foreach ($gurus as $guru):
                                    $grand_total++;
                        ?>
                        <tr>
                            <td style="text-align:center;"><?php echo $no_global++; ?></td>
                            <td><strong><?php echo htmlspecialchars($guru['nama'] ?? '-'); ?></strong></td>
                            <td><?php echo htmlspecialchars($guru['jenis_profesi'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['tempat_mengajar'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['bank'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['no_rekening'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['no_telp'] ?? '-'); ?></td>
                        </tr>
                        <?php 
                                endforeach;
                                
                                // SUB TOTAL PER KECAMATAN
                                echo '<tr class="sub-total">';
                                echo '<td colspan="6" style="text-align:right;padding-right:15px;">';
                                echo '<i class="fas fa-map-pin" style="color:#d4a847;"></i> Total Kecamatan ' . htmlspecialchars($kecamatan) . ' :';
                                echo '</td>';
                                echo '<td style="font-weight:700;color:#1a6e3a;text-align:center;">' . $kec_total . ' Guru</td>';
                                echo '</tr>';
                                
                            endforeach;
                            
                            // SUB TOTAL PER KABUPATEN
                            echo '<tr class="sub-total-kabupaten">';
                            echo '<td colspan="6" style="text-align:right;padding-right:15px;">';
                            echo '<i class="fas fa-building" style="color:#d4a847;"></i> TOTAL KABUPATEN ' . htmlspecialchars($kabupaten) . ' :';
                            echo '</td>';
                            echo '<td style="font-weight:700;color:#1a6e3a;text-align:center;">' . $kab_total . ' Guru</td>';
                            echo '</tr>';
                            
                        endforeach;
                        
                        // GRAND TOTAL
                        echo '<tr class="grand-total">';
                        echo '<td colspan="6" style="text-align:right;padding-right:15px;">';
                        echo '<i class="fas fa-users" style="margin-right:8px;"></i> TOTAL SELURUH GURU :';
                        echo '</td>';
                        echo '<td style="font-weight:700;text-align:center;">' . $grand_total . ' Guru</td>';
                        echo '</tr>';
                        ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-data">
                <i class="fas fa-users"></i>
                <p>
                    Belum ada data guru ngaji
                    <?php if (!empty($filter_kabupaten)): ?>
                    di Kabupaten <?php echo htmlspecialchars($filter_kabupaten); ?>
                    <?php endif; ?>
                    <?php if (!empty($filter_kecamatan)): ?>
                    Kecamatan <?php echo htmlspecialchars($filter_kecamatan); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
        FOOTER
        ========================================== -->
        <div class="print-footer">
            <i class="fas fa-print"></i>
            Dicetak: <?php echo tanggal_indonesia(date('Y-m-d H:i:s')); ?>
            <span class="separator">|</span>
            <i class="fas fa-building"></i>
            PGNI Lampung
            <span class="separator">|</span>
            <i class="fas fa-calendar-alt"></i>
            <?php echo date('Y'); ?>
            <?php if (!empty($filter_kabupaten) || !empty($filter_kecamatan)): ?>
            <span class="separator">|</span>
            <i class="fas fa-filter"></i>
            <?php if (!empty($filter_kabupaten)): echo 'Kab. ' . htmlspecialchars($filter_kabupaten); endif; ?>
            <?php if (!empty($filter_kecamatan)): echo ' Kec. ' . htmlspecialchars($filter_kecamatan); endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('table-guru');
    if (!table) return;
    
    let csvContent = '';
    
    // Header
    csvContent += '"LAPORAN DATA GURU NGAJI PGNI LAMPUNG"\n';
    csvContent += '"Dicetak pada: ' + new Date().toLocaleString('id-ID') + '"\n';
    
    // Filter info
    const kabFilter = document.getElementById('filterKabupaten');
    const kecFilter = document.getElementById('filterKecamatan');
    const statusFilter = document.getElementById('filterStatus');
    
    if (kabFilter && kabFilter.value) {
        csvContent += '"Kabupaten: ' + kabFilter.options[kabFilter.selectedIndex].text + '"';
        if (kecFilter && kecFilter.value || statusFilter && statusFilter.value) {
            csvContent += ',';
        }
        csvContent += '\n';
    }
    if (kecFilter && kecFilter.value) {
        csvContent += '"Kecamatan: ' + kecFilter.options[kecFilter.selectedIndex].text + '"';
        if (statusFilter && statusFilter.value) {
            csvContent += ',';
        }
        csvContent += '\n';
    }
    if (statusFilter && statusFilter.value) {
        csvContent += '"Status: ' + statusFilter.options[statusFilter.selectedIndex].text + '"\n';
    }
    csvContent += '\n';
    
    // Header tabel
    csvContent += '"No","Nama","Profesi","Tempat Mengajar","Bank","No. Rekening","No. HP"\n';
    
    // Data - skip sub total dan grand total
    const tbody = table.querySelector('tbody');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(row => {
            // Skip sub total dan grand total
            if (row.classList.contains('sub-total') || row.classList.contains('sub-total-kabupaten') || row.classList.contains('grand-total')) {
                return;
            }
            const cells = [];
            row.querySelectorAll('td').forEach(td => {
                let text = td.textContent.trim().replace(/\s+/g, ' ');
                text = text.replace(/"/g, '""');
                cells.push('"' + text + '"');
            });
            if (cells.length > 0) {
                csvContent += cells.join(',') + '\n';
            }
        });
    }
    
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'data_guru_pgni_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

function exportToExcel() {
    const table = document.getElementById('table-guru');
    if (!table) return;
    
    let htmlContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Data Guru</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #999; padding: 4px 6px; font-size: 10px; }
                th { background: #1a6e3a; color: #fff; font-weight: bold; }
                .header-title { font-size: 14px; font-weight: bold; text-align: center; }
                .filter-info { text-align: center; margin: 3px 0; font-size: 11px; color: #666; }
                .sub-total { background: #f5f5f5; font-weight: bold; }
                .sub-total-kabupaten { background: #e8f5e9; font-weight: bold; }
                .grand-total { background: #1a6e3a; color: #fff; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header-title">DATA GURU NGAJI PGNI LAMPUNG</div>
            <p style="text-align:center;">Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
    `;
    
    const kabFilter = document.getElementById('filterKabupaten');
    const kecFilter = document.getElementById('filterKecamatan');
    const statusFilter = document.getElementById('filterStatus');
    
    if (kabFilter && kabFilter.value) {
        htmlContent += `<p class="filter-info">Kabupaten: ${kabFilter.options[kabFilter.selectedIndex].text}</p>`;
    }
    if (kecFilter && kecFilter.value) {
        htmlContent += `<p class="filter-info">Kecamatan: ${kecFilter.options[kecFilter.selectedIndex].text}</p>`;
    }
    if (statusFilter && statusFilter.value) {
        htmlContent += `<p class="filter-info">Status: ${statusFilter.options[statusFilter.selectedIndex].text}</p>`;
    }
    htmlContent += `<br>`;
    htmlContent += table.outerHTML;
    htmlContent += `
        <p style="text-align:center;margin-top:15px;color:#999;font-size:9px;">
            &copy; ${new Date().getFullYear()} PGNI Lampung - Data Guru Ngaji
        </p>
        </body></html>
    `;
    
    const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'data_guru_pgni_' + new Date().toISOString().slice(0,10) + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

window.addEventListener('beforeprint', function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
});
</script>

<?php include 'include/admin_footer.php'; ?>