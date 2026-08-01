<?php
// admin/berita.php - Halaman Manajemen Berita
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

$title = 'Manajemen Berita';
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Konfigurasi pagination
$per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Query untuk menghitung total
$count_query = "SELECT COUNT(*) as total FROM berita WHERE 1=1";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $count_query .= " AND (judul LIKE '%$search_escaped%' OR isi LIKE '%$search_escaped%')";
}
if (!empty($status_filter)) {
    $status_escaped = mysqli_real_escape_string($conn, $status_filter);
    $count_query .= " AND status = '$status_escaped'";
}

$count_result = mysqli_query($conn, $count_query);
$total_data = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_data / $per_page);

// Query ambil data
$query = "SELECT * FROM berita WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (judul LIKE '%$search_escaped%' OR isi LIKE '%$search_escaped%')";
}
if (!empty($status_filter)) {
    $query .= " AND status = '$status_escaped'";
}
$query .= " ORDER BY created_at DESC LIMIT $offset, $per_page";

$berita_list = mysqli_query($conn, $query);

include 'include/admin_header.php';
?>

<!-- CSS Premium, Elegant & Fully Responsive - LTR Normal -->
<style>
    /* ============================================
       GLOBAL LTR - OVERRIDE
       ============================================ */
    * {
        direction: ltr !important;
        box-sizing: border-box;
    }

    body {
        direction: ltr;
        text-align: left;
    }

    /* ============================================
       RESET & BASE GAYA
       ============================================ */
    * {
        box-sizing: border-box;
    }

    /* ============================================
       PAGE HEADER
       ============================================ */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eef2f5;
    }
    .page-header-left h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 5px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left !important;
    }
    .page-header-left h2 i {
        color: #d4a847;
    }
    .page-header-left .text-muted {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
        text-align: left !important;
    }
    .page-header-right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* ============================================
       ALERTS & NOTIFIKASI
       ============================================ */
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
        border: 1px solid transparent;
        position: relative;
        direction: ltr !important;
        text-align: left !important;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    .alert-dismissible {
        padding-right: 45px;
    }
    .alert-close {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-size: 1.4rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.6;
        transition: opacity 0.2s ease;
    }
    .alert-close:hover {
        opacity: 1;
    }

    /* ============================================
       FILTER SECTION
       ============================================ */
    .filter-section {
        background: #fff;
        padding: 20px 25px;
        border-radius: 14px;
        margin-bottom: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.02);
        border: 1px solid #f0f2f5;
        direction: ltr !important;
        text-align: left !important;
    }
    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
        direction: ltr !important;
        text-align: left !important;
    }
    .filter-group-search {
        flex: 1;
        min-width: 200px;
    }
    .filter-group-select {
        min-width: 150px;
    }

    /* ============================================
       FORM CONTROLS
       ============================================ */
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
        direction: ltr !important;
    }
    .input-group .input-icon {
        position: absolute;
        left: 15px;
        color: #95a5a6;
        font-size: 0.95rem;
        z-index: 1;
    }
    .input-group .form-control {
        padding-left: 45px;
    }
    .form-control {
        width: 100%;
        padding: 10px 16px;
        border: 2px solid #eef2f5;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: inherit;
        transition: all 0.25s ease;
        background: #fff;
        color: #2c3e50;
        direction: ltr !important;
        text-align: left !important;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 15px;
        padding-right: 40px;
        direction: ltr !important;
        text-align: left !important;
    }
    select.form-control option {
        direction: ltr !important;
        text-align: left !important;
    }

    /* ============================================
       TOMBOL-TOMBOL (BUTTONS)
       ============================================ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        font-family: inherit;
        border: none;
        white-space: nowrap;
    }
    .btn:hover {
        transform: translateY(-2px);
    }
    .btn:active {
        transform: translateY(0);
    }
    .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        box-shadow: 0 4px 12px rgba(26, 110, 58, 0.15);
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        box-shadow: 0 6px 18px rgba(26, 110, 58, 0.25);
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
        box-shadow: 0 4px 12px rgba(149, 165, 166, 0.1);
    }
    .btn-secondary:hover {
        background: #7f8c8d;
    }

    /* ============================================
       TABLE STYLE
       ============================================ */
    .table-wrapper {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        overflow: hidden;
        border: 1px solid #f0f2f5;
        margin-bottom: 25px;
        direction: ltr !important;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        direction: ltr !important;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        direction: ltr !important;
    }
    .table thead {
        background: #fafbfc;
    }
    .table th {
        padding: 16px 20px;
        text-align: left !important;
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
        border-bottom: 2px solid #edf2f7;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        direction: ltr !important;
    }
    .table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #495057;
        text-align: left !important;
        direction: ltr !important;
    }
    .table tbody tr {
        transition: background-color 0.2s ease;
    }
    .table tbody tr:hover {
        background: #fafbfc;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* ============================================
       KONTEN TABEL DATA KHUSUS
       ============================================ */
    .berita-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #f0f2f5;
        transition: transform 0.2s ease;
    }
    .berita-img:hover {
        transform: scale(1.08);
    }
    .berita-title-link {
        color: #2c3e50;
        text-decoration: none;
        font-weight: 600;
        display: block;
        transition: color 0.2s ease;
        line-height: 1.4;
        text-align: left !important;
    }
    .berita-title-link:hover {
        color: #1a6e3a;
    }
    .text-excerpt {
        color: #7f8c8d;
        font-size: 0.8rem;
        display: block;
        margin-top: 4px;
        line-height: 1.4;
        text-align: left !important;
    }

    /* Badge Kategori & Status */
    .badge-kategori {
        display: inline-block;
        padding: 4px 12px;
        background: #e8f0fe;
        color: #1a6e3a;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 500;
        text-align: left !important;
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        text-align: left !important;
    }
    .status-badge.publish {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-badge.draft {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    /* Aksi Aksi */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        direction: ltr !important;
    }
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-action:hover {
        transform: translateY(-2px);
    }
    .btn-action-edit {
        background: #e8f0fe;
        color: #1a6e3a;
    }
    .btn-action-edit:hover {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-action-view {
        background: #e1f5fe;
        color: #0288d1;
    }
    .btn-action-view:hover {
        background: #0288d1;
        color: #fff;
    }
    .btn-action-delete {
        background: #fde8e8;
        color: #e74c3c;
    }
    .btn-action-delete:hover {
        background: #e74c3c;
        color: #fff;
    }

    /* ============================================
       EMPTY STATE
       ============================================ */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        direction: ltr !important;
    }
    .empty-state i {
        font-size: 3.5rem;
        color: #d4a847;
        margin-bottom: 15px;
    }
    .empty-state h3 {
        margin: 0 0 8px 0;
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.2rem;
        text-align: center !important;
    }
    .empty-state p {
        color: #7f8c8d;
        margin: 0 0 20px 0;
        font-size: 0.9rem;
        text-align: center !important;
    }

    /* ============================================
       PAGINATION
       ============================================ */
    .pagination-container {
        padding: 18px 20px;
        border-top: 1px solid #f1f3f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        direction: ltr !important;
    }
    .pagination-info {
        font-size: 0.85rem;
        color: #7f8c8d;
        font-weight: 500;
        text-align: left !important;
    }
    .pagination-list {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        direction: ltr !important;
    }
    .pagination-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        background: #f1f3f5;
        border-radius: 8px;
        text-decoration: none;
        color: #2c3e50;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .pagination-link:hover {
        background: #e2e8f0;
    }
    .pagination-link.active {
        background: #1a6e3a;
        color: #fff;
    }
    .pagination-dots {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        color: #bdc3c7;
    }

    /* ============================================
       MOBILE RESPONSIVE (CARD-VIEW)
       ============================================ */
    @media (max-width: 991px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        .page-header-right {
            display: flex;
            width: 100%;
        }
        .page-header-right .btn {
            flex: 1;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        /* Filter Mobile */
        .filter-section {
            padding: 15px;
        }
        .filter-form {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .filter-form > div {
            width: 100% !important;
            min-width: unset !important;
        }
        .filter-form .btn, .filter-form a {
            width: 100%;
            justify-content: center;
        }

        /* Transform Table to Cards */
        .table-wrapper {
            background: transparent;
            box-shadow: none;
            border: none;
        }
        .table thead {
            display: none;
        }
        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }
        .table tr {
            margin-bottom: 16px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            padding: 16px 20px;
            border: 1px solid #f0f2f5;
        }
        .table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.85rem;
            gap: 15px;
            text-align: left !important;
        }
        .table td:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .table td:first-child {
            padding-top: 0;
        }
        .table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left !important;
        }
        .table td > * {
            text-align: right;
            word-break: break-word;
        }

        .table td[data-label="Judul"] {
            flex-direction: column;
            align-items: flex-start;
        }
        .table td[data-label="Judul"]::before {
            margin-bottom: 5px;
        }
        .table td[data-label="Judul"] > * {
            text-align: left !important;
        }

        .table td:last-child {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px dashed #eef2f5;
        }
        .table td:last-child::before {
            content: 'Menu Navigasi Aksi';
            align-self: center;
            margin-bottom: 5px;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            width: 100%;
        }
        .btn-action {
            width: 100%;
            height: 38px;
            border-radius: 8px;
        }

        .pagination-container {
            flex-direction: column;
            align-items: center;
            padding: 15px;
        }
        .pagination-info {
            text-align: center !important;
        }
    }

    @media (max-width: 480px) {
        .action-buttons {
            grid-template-columns: 1fr 1fr;
        }
        .btn-action-delete {
            grid-column: span 2;
        }
    }
</style>

<!-- ============================================
     KONTEN LAYOUT UTAMA
     ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-newspaper"></i> Manajemen Berita</h2>
        <p class="text-muted">Kelola semua berita dan informasi secara dinamis</p>
    </div>
    <div class="page-header-right">
        <a href="berita_tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Berita
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($success == 'tambah'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Berita berhasil ditambahkan!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($success == 'edit'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Berita berhasil diperbarui!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($success == 'hapus'): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> Berita berhasil dihapus!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php elseif ($error == 'notfound'): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> Berita tidak ditemukan!
        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- Filter & Search Section -->
<div class="filter-section">
    <form action="" method="GET" class="filter-form">
        <div class="filter-group-search">
            <div class="input-group">
                <span class="input-icon"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari berita..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="filter-group-select">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>📝 Draft</option>
                <option value="publish" <?php echo $status_filter == 'publish' ? 'selected' : ''; ?>>✅ Publish</option>
                
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="berita.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Table Container -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No</th>
                    <th style="width: 90px;">Gambar</th>
                    <th>Judul Berita</th>
                    <th style="width: 140px;">Kategori</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 150px;">Tanggal</th>
                    <th style="text-align: center; width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($berita_list && mysqli_num_rows($berita_list) > 0): ?>
                    <?php $no = $offset + 1; ?>
                    <?php while ($berita = mysqli_fetch_assoc($berita_list)): ?>
                        <tr>
                            <td data-label="No" style="text-align: center; font-weight: 600; color: #7f8c8d;">
                                <?php echo $no++; ?>
                            </td>
                            <td data-label="Gambar">
                                <?php if (!empty($berita['gambar'])): ?>
                                    <img src="../assets/images/berita/<?php echo htmlspecialchars($berita['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($berita['judul']); ?>"
                                         class="berita-img"
                                         onerror="this.src='../assets/images/berita/default.jpg'">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #bdc3c7; border: 2px solid #f0f2f5; font-size: 1.2rem;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Judul">
                                <a href="berita_edit.php?id=<?php echo $berita['id']; ?>" class="berita-title-link">
                                    <?php echo htmlspecialchars($berita['judul']); ?>
                                </a>
                                <span class="text-excerpt">
                                    <?php echo potong_teks($berita['isi'], 80); ?>
                                </span>
                            </td>
                            <td data-label="Kategori">
                                <?php if (!empty($berita['kategori'])): ?>
                                    <span class="badge-kategori">
                                        <?php echo htmlspecialchars($berita['kategori']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #bdc3c7; font-size: 0.85rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <!-- Di bagian status badge, ganti dengan ini: -->
<!-- Di bagian tabel, perbaiki status badge -->
<td data-label="Status">
    <?php 
    $status_value = $berita['status'];
    if ($status_value == 1 || $status_value === '1'): ?>
        <span class="status-badge publish">📝 Draft<span>
    <?php else: ?>
        <span class="status-badge draft">✅ Publish<//span>
    <?php endif; ?>
</td>
                            <td data-label="Tanggal" style="color: #7f8c8d; font-size: 0.85rem;">
                                <i class="far fa-calendar-alt" style="margin-right: 5px;"></i> <?php echo tanggal_indonesia($berita['created_at']); ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="action-buttons">
                                    <a href="berita_edit.php?id=<?php echo $berita['id']; ?>" 
                                       class="btn-action btn-action-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../berita_detail.php?id=<?php echo $berita['id']; ?>" 
                                       target="_blank" class="btn-action btn-action-view" title="Lihat">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="berita_hapus.php?id=<?php echo $berita['id']; ?>" 
                                       class="btn-action btn-action-delete" title="Hapus"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 0;">
                            <div class="empty-state">
                                <i class="fas fa-newspaper"></i>
                                <h3>Belum Ada Berita</h3>
                                <p>Silakan buat tulisan atau tambahkan berita baru</p>
                                <a href="berita_tambah.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Berita
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Area -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <span class="pagination-info">
                Menampilkan <?php echo number_format(($page - 1) * $per_page + 1); ?> - 
                <?php echo number_format(min($page * $per_page, $total_data)); ?> 
                dari <?php echo number_format($total_data); ?> data
            </span>
            <div class="pagination-list">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '" class="pagination-link">1</a>';
                    if ($start_page > 2) echo '<span class="pagination-dots">...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span class="pagination-dots">...</span>';
                    echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '" class="pagination-link">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto close alert after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

<?php include 'include/admin_footer.php'; ?>