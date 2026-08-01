<?php
// berita.php - Halaman Berita Frontend
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PERBAIKI PATH - Gunakan __DIR__ untuk mendapatkan folder saat ini
// ============================================
$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Berita - PGNI Lampung';
$per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

// ============================================
// CEK APAKAH KOLOM KATEGORI ADA
// ============================================
$check_kategori = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'kategori'");
$has_kategori = ($check_kategori && mysqli_num_rows($check_kategori) > 0);

// ============================================
// QUERY UNTUK MENGHITUNG TOTAL BERITA
// ============================================
$count_query = "SELECT COUNT(*) as total FROM berita WHERE status = 'publish'";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $count_query .= " AND (judul LIKE '%$search_escaped%' OR isi LIKE '%$search_escaped%')";
}
if ($has_kategori && !empty($kategori)) {
    $kategori_escaped = mysqli_real_escape_string($conn, $kategori);
    $count_query .= " AND kategori = '$kategori_escaped'";
}

$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    $total_data = 0;
} else {
    $total_data = mysqli_fetch_assoc($count_result)['total'];
}
$total_pages = ceil($total_data / $per_page);

// ============================================
// QUERY AMBIL BERITA
// ============================================
$query = "SELECT * FROM berita WHERE status = 'publish'";
if (!empty($search)) {
    $query .= " AND (judul LIKE '%$search_escaped%' OR isi LIKE '%$search_escaped%')";
}
if ($has_kategori && !empty($kategori)) {
    $query .= " AND kategori = '$kategori_escaped'";
}
$query .= " ORDER BY created_at DESC LIMIT $offset, $per_page";

$berita_list = mysqli_query($conn, $query);

// ============================================
// AMBIL DAFTAR KATEGORI (JIKA ADA)
// ============================================
$kategori_list = null;
if ($has_kategori) {
    $kategori_query = "SELECT DISTINCT kategori FROM berita WHERE status = 'publish' AND kategori != '' AND kategori IS NOT NULL ORDER BY kategori";
    $kategori_list = mysqli_query($conn, $kategori_query);
}

// ============================================
// AMBIL BERITA TERBARU UNTUK SIDEBAR
// ============================================
$berita_terbaru_query = "SELECT id, judul, created_at FROM berita WHERE status = 'publish' ORDER BY created_at DESC LIMIT 5";
$berita_terbaru = mysqli_query($conn, $berita_terbaru_query);

include $root_path . '/include/header.php';
?>

<div class="page-banner" style="background: linear-gradient(135deg, #1a6e3a, #2d8f52); padding: 60px 0; color: #fff; text-align: center; direction: ltr;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;">📰 Berita & Informasi</h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Informasi terbaru seputar kegiatan PGNI Lampung</p>
    </div>
</div>

<div class="berita-section" style="padding: 50px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px; direction: ltr;">
            
            <!-- Main Content -->
            <div class="berita-main">
                <!-- Search & Filter -->
                <div class="berita-filter" style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 12px; direction: ltr;">
                    <form action="" method="GET" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Cari berita..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="flex: 1; padding: 10px 15px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 0.95rem; min-width: 150px; direction: ltr; text-align: left;">
                        
                        <?php if ($has_kategori && $kategori_list && mysqli_num_rows($kategori_list) > 0): ?>
                            <select name="kategori" style="padding: 10px 15px; border: 2px solid #e8e8e8; border-radius: 8px; background: #fff; min-width: 150px; direction: ltr; text-align: left;">
                                <option value="">Semua Kategori</option>
                                <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                                    <option value="<?php echo htmlspecialchars($kat['kategori']); ?>" 
                                        <?php echo $kategori == $kat['kategori'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kat['kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                        
                        <button type="submit" style="padding: 10px 25px; background: #1a6e3a; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        
                        <?php if (!empty($search) || !empty($kategori)): ?>
                            <a href="berita.php" style="padding: 10px 20px; background: #e74c3c; color: #fff; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Hasil Pencarian -->
                <?php if (!empty($search) || !empty($kategori)): ?>
                    <div style="margin-bottom: 20px; color: #666; direction: ltr; text-align: left;">
                        Menampilkan <?php echo number_format($total_data); ?> hasil 
                        <?php if (!empty($search)): ?>untuk "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                        <?php if (!empty($kategori)): ?>di kategori "<?php echo htmlspecialchars($kategori); ?>"<?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- List Berita -->
                <?php if ($berita_list && mysqli_num_rows($berita_list) > 0): ?>
                    <div class="berita-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; direction: ltr;">
                        <?php while ($berita = mysqli_fetch_assoc($berita_list)): ?>
                            <div class="berita-card" style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.08); transition: all 0.3s ease; direction: ltr; text-align: left;">
                                <?php if (!empty($berita['gambar'])): ?>
                                    <div class="berita-image" style="height: 220px; overflow: hidden; position: relative;">
                                        <img src="assets/images/berita/<?php echo htmlspecialchars($berita['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($berita['judul']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                             onerror="this.src='assets/images/berita/default.jpg'">
                                        <?php if ($has_kategori && !empty($berita['kategori'])): ?>
                                            <span style="position: absolute; top: 15px; left: 15px; background: #d4a847; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 500;">
                                                <?php echo htmlspecialchars($berita['kategori']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="berita-content" style="padding: 20px;">
                                    <div class="berita-meta" style="display: flex; gap: 15px; font-size: 0.8rem; color: #999; margin-bottom: 10px; flex-wrap: wrap; direction: ltr; text-align: left;">
                                        <span><i class="fas fa-calendar-alt"></i> <?php echo tanggal_indonesia($berita['created_at']); ?></span>
                                        <?php if (!empty($berita['author'])): ?>
                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($berita['author']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 style="font-size: 1.1rem; margin-bottom: 10px; line-height: 1.4; direction: ltr; text-align: left;">
                                        <a href="berita_detail.php?id=<?php echo $berita['id']; ?>" 
                                           style="color: #1a1a2e; text-decoration: none; transition: color 0.3s ease;">
                                            <?php echo htmlspecialchars($berita['judul']); ?>
                                        </a>
                                    </h3>
                                    <p style="color: #666; font-size: 0.9rem; line-height: 1.6; margin-bottom: 15px; direction: ltr; text-align: left;">
                                        <?php echo potong_teks($berita['isi'], 120); ?>
                                    </p>
                                    <a href="berita_detail.php?id=<?php echo $berita['id']; ?>" 
                                       style="color: #1a6e3a; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;">
                                        Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 40px; flex-wrap: wrap; direction: ltr;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($kategori) ? '&kategori=' . urlencode($kategori) : ''; ?>" 
                                   style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333;">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($kategori) ? '&kategori=' . urlencode($kategori) : '') . '" style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333;">1</a>';
                                if ($start_page > 2) echo '<span style="padding: 8px 8px; color: #999;">...</span>';
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($kategori) ? '&kategori=' . urlencode($kategori) : ''; ?>" 
                                   style="padding: 8px 16px; background: <?php echo $i == $page ? '#1a6e3a' : '#f0f0f0'; ?>; color: <?php echo $i == $page ? '#fff' : '#333'; ?>; border-radius: 6px; text-decoration: none; <?php echo $i == $page ? 'font-weight: 600;' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php 
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<span style="padding: 8px 8px; color: #999;">...</span>';
                                echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($kategori) ? '&kategori=' . urlencode($kategori) : '') . '" style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333;">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($kategori) ? '&kategori=' . urlencode($kategori) : ''; ?>" 
                                   style="padding: 8px 16px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333;">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 12px; direction: ltr;">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: #d4a847; margin-bottom: 15px; display: block;"></i>
                        <h3 style="color: #1a1a2e; margin-bottom: 10px;">Belum Ada Berita</h3>
                        <p style="color: #666;">Saat ini belum ada berita yang dipublikasikan.</p>
                        <?php if (!empty($search) || !empty($kategori)): ?>
                            <a href="berita.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: #1a6e3a; color: #fff; border-radius: 8px; text-decoration: none;">
                                Lihat Semua Berita
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="berita-sidebar" style="direction: ltr;">
                <!-- Berita Terbaru -->
                <div style="background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 25px; direction: ltr; text-align: left;">
                    <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; text-align: left;">
                        <i class="fas fa-clock" style="color: #d4a847;"></i> Berita Terbaru
                    </h3>
                    <?php if ($berita_terbaru && mysqli_num_rows($berita_terbaru) > 0): ?>
                        <ul style="list-style: none; padding: 0; margin: 0; text-align: left;">
                            <?php while ($item = mysqli_fetch_assoc($berita_terbaru)): ?>
                                <li style="padding: 10px 0; border-bottom: 1px solid #f5f5f5;">
                                    <a href="berita_detail.php?id=<?php echo $item['id']; ?>" 
                                       style="color: #333; text-decoration: none; display: block;">
                                        <span style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($item['judul']); ?></span>
                                        <span style="display: block; font-size: 0.75rem; color: #999; margin-top: 3px;">
                                            <i class="fas fa-calendar-alt"></i> <?php echo tanggal_indonesia($item['created_at']); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: #999; font-size: 0.9rem;">Belum ada berita terbaru.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Kategori (jika ada) -->
                <?php if ($has_kategori && $kategori_list && mysqli_num_rows($kategori_list) > 0): ?>
                    <?php 
                    // Reset pointer untuk query kedua
                    mysqli_data_seek($kategori_list, 0);
                    ?>
                    <div style="background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); direction: ltr; text-align: left;">
                        <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; text-align: left;">
                            <i class="fas fa-tags" style="color: #d4a847;"></i> Kategori
                        </h3>
                        <ul style="list-style: none; padding: 0; margin: 0; text-align: left;">
                            <?php 
                            mysqli_data_seek($kategori_list, 0);
                            while ($kat = mysqli_fetch_assoc($kategori_list)): 
                            ?>
                                <li style="padding: 6px 0;">
                                    <a href="berita.php?kategori=<?php echo urlencode($kat['kategori']); ?>" 
                                       style="color: #555; text-decoration: none; display: flex; justify-content: space-between; align-items: center; text-align: left;">
                                        <span><i class="fas fa-folder" style="color: #d4a847; margin-right: 8px;"></i> <?php echo htmlspecialchars($kat['kategori']); ?></span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    * {
        direction: ltr !important;
    }
    
    body {
        direction: ltr;
        text-align: left;
    }
    
    .berita-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .berita-card:hover .berita-image img {
        transform: scale(1.05);
    }
    
    .berita-card h3 a:hover {
        color: #1a6e3a !important;
    }
    
    .pagination a:hover {
        background: #1a6e3a !important;
        color: #fff !important;
    }
    
    @media (max-width: 1024px) {
        .berita-section .container > div {
            grid-template-columns: 1fr !important;
        }
        .berita-list {
            grid-template-columns: 1fr 1fr !important;
        }
        .berita-sidebar {
            order: -1;
        }
    }
    
    @media (max-width: 768px) {
        .berita-list {
            grid-template-columns: 1fr !important;
        }
        .berita-filter form {
            flex-direction: column;
        }
        .berita-filter form input,
        .berita-filter form select {
            width: 100%;
        }
        .page-banner h1 {
            font-size: 1.8rem !important;
        }
        .page-banner {
            padding: 40px 0 !important;
        }
    }
    
    @media (max-width: 480px) {
        .page-banner h1 {
            font-size: 1.5rem !important;
        }
    }
</style>

<?php include $root_path . '/include/footer.php'; ?>