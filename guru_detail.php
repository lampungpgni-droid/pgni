<?php
// guru_detail.php - Halaman Detail Guru Ngaji
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH - Gunakan __DIR__ untuk mendapatkan folder saat ini
// ============================================
$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: guru.php');
    exit;
}

// ============================================
// AMBIL DATA GURU
// ============================================
$query = "SELECT g.*, 
          k.nama as kabupaten_nama, 
          kec.nama as kecamatan_nama, 
          d.nama as desa_nama 
          FROM guru_ngaji g 
          LEFT JOIN kabupaten k ON g.kabupaten_id = k.id 
          LEFT JOIN kecamatan kec ON g.kecamatan_id = kec.id 
          LEFT JOIN desa d ON g.desa_id = d.id 
          WHERE g.id = $id AND g.status = 'aktif' AND g.status_verifikasi = 'disetujui'";

$result = mysqli_query($conn, $query);
$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    header('Location: guru.php');
    exit;
}

$title = htmlspecialchars($guru['nama']) . ' - Guru Ngaji PGNI Lampung';

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- PAGE BANNER -->
<!-- ============================================ -->
<div class="page-banner" style="background: linear-gradient(135deg, #1a6e3a, #2d8f52); padding: 40px 0; color: #fff; text-align: center; direction: ltr;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 2rem; margin-bottom: 5px;">👨‍🏫 Detail Guru Ngaji</h1>
        <p style="font-size: 1rem; opacity: 0.9;">Informasi lengkap guru ngaji terverifikasi</p>
    </div>
</div>

<!-- ============================================ -->
<!-- DETAIL CONTENT -->
<!-- ============================================ -->
<section class="detail-section" style="padding: 40px 0;">
    <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 25px; font-size: 0.85rem; color: #999; direction: ltr; text-align: left;">
            <a href="index.php" style="color: #1a6e3a; text-decoration: none;">Home</a>
            <span> / </span>
            <a href="guru.php" style="color: #1a6e3a; text-decoration: none;">Guru Ngaji</a>
            <span> / </span>
            <span style="color: #666;"><?php echo htmlspecialchars($guru['nama']); ?></span>
        </nav>
        
        <!-- Card Detail -->
        <div style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 30px rgba(0,0,0,0.08); direction: ltr;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #1a6e3a, #2d8f52); padding: 30px; color: #fff;">
                <div style="display: flex; align-items: center; gap: 25px; flex-wrap: wrap; text-align: left;">
                    <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #fff; flex-shrink: 0;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div style="text-align: left;">
                        <h2 style="font-size: 1.8rem; margin-bottom: 5px; text-align: left;"><?php echo htmlspecialchars($guru['nama']); ?></h2>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; font-size: 0.95rem; opacity: 0.9; text-align: left;">
                            <span><i class="fas fa-id-card"></i> NIK: </span>
                            <?php if (!empty($guru['jenis_profesi'])): ?>
                                <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($guru['jenis_profesi']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; text-align: left;">
                    
                    <!-- Kolom Kiri -->
                    <div style="text-align: left;">
                        <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; text-align: left;">
                            <i class="fas fa-info-circle" style="color: #d4a847;"></i> Informasi Pribadi
                        </h3>
                        <div style="display: grid; gap: 12px; text-align: left;">
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">NIK</label>
                                
                            </div>
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Nama Lengkap</label>
                                <span style="color: #333; font-size: 0.95rem;"><?php echo htmlspecialchars($guru['nama']); ?></span>
                            </div>
                            
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Jenis Profesi</label>
                                <span style="color: #333; font-size: 0.95rem;"><?php echo htmlspecialchars($guru['jenis_profesi'] ?: '-'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan -->
                    <div style="text-align: left;">
                        <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; text-align: left;">
                            <i class="fas fa-chalkboard-teacher" style="color: #d4a847;"></i> Data Mengajar
                        </h3>
                        <div style="display: grid; gap: 12px; text-align: left;">
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Tempat Mengajar</label>
                                <span style="color: #333; font-size: 0.95rem;"><?php echo htmlspecialchars($guru['tempat_mengajar']); ?></span>
                                <?php if (!empty($guru['tempat_mengajar_detail'])): ?>
                                    <span style="color: #999; font-size: 0.85rem; display: block;"><?php echo htmlspecialchars($guru['tempat_mengajar_detail']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Alamat</label>
                                <span style="color: #333; font-size: 0.95rem;">
                                    <?php 
                                    $alamat = [];
                                    if (!empty($guru['desa_nama'])) $alamat[] = $guru['desa_nama'];
                                    if (!empty($guru['kecamatan_nama'])) $alamat[] = $guru['kecamatan_nama'];
                                    if (!empty($guru['kabupaten_nama'])) $alamat[] = $guru['kabupaten_nama'];
                                    echo htmlspecialchars(implode(', ', $alamat) ?: '-');
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($guru['bank']) || !empty($guru['no_rekening'])): ?>
                                <div style="text-align: left;">
                                    <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Rekening</label>
                                    <span style="color: #333; font-size: 0.95rem;">
                                        <?php 
                                        $rekening = [];
                                        if (!empty($guru['bank'])) $rekening[] = $guru['bank'];
                                        if (!empty($guru['no_rekening'])) $rekening[] = $guru['no_rekening'];
                                        echo htmlspecialchars(implode(' - ', $rekening) ?: '-');
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div style="text-align: left;">
                                <label style="font-weight: 600; color: #555; font-size: 0.85rem; display: block; text-align: left;">Terdaftar Sejak</label>
                                <span style="color: #333; font-size: 0.95rem;"><?php echo tanggal_indonesia($guru['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tombol Kembali -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0; display: flex; gap: 15px; flex-wrap: wrap; text-align: left;">
                    <a href="guru.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 25px; background: #1a6e3a; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Guru
                    </a>
                    
                </div>
            </div>
        </div>
        
    </div>
</section>

<style>
    * {
        direction: ltr !important;
    }
    
    body {
        direction: ltr;
        text-align: left;
    }
    
    .detail-section .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .detail-section .btn:last-child:hover {
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
    }
    
    @media (max-width: 768px) {
        .detail-section .container > div:last-child > div:last-child > div:first-child {
            grid-template-columns: 1fr !important;
        }
        .page-banner {
            padding: 25px 0 !important;
        }
        .page-banner h1 {
            font-size: 1.3rem !important;
        }
        .detail-section .container > div:last-child > div:first-child {
            padding: 20px !important;
        }
        .detail-section .container > div:last-child > div:last-child {
            padding: 20px !important;
        }
        .detail-section .container > div:last-child > div:first-child > div {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<?php include $root_path . '/include/footer.php'; ?>