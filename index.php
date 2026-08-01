<?php
// index.php - Halaman Utama PGNI Lampung
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

$title = 'PGNI Lampung - Persatuan Guru Ngaji Indonesia Lampung';

// ============================================
// CEK APAKAH KOLOM KATEGORI ADA
// ============================================
$check_kategori = mysqli_query($conn, "SHOW COLUMNS FROM berita LIKE 'kategori'");
$has_kategori = ($check_kategori && mysqli_num_rows($check_kategori) > 0);

// ============================================
// AMBIL BERITA UNTUK SLIDE HERO
// ============================================
$slide_query = "SELECT id, judul, isi, gambar, created_at, author";
if ($has_kategori) {
    $slide_query .= ", kategori";
}
$slide_query .= " FROM berita WHERE status = 'publish' ORDER BY created_at DESC LIMIT 10";

$slide_berita = mysqli_query($conn, $slide_query);

// ============================================
// AMBIL BERITA TERBARU (5 Berita Terbaru)
// ============================================
$berita_query = "SELECT id, judul, isi, gambar, created_at, author";
if ($has_kategori) {
    $berita_query .= ", kategori";
}
$berita_query .= " FROM berita WHERE status = 'publish' ORDER BY created_at DESC LIMIT 5";

$berita_terbaru = mysqli_query($conn, $berita_query);

// ============================================
// AMBIL STATISTIK
// ============================================
// Total Guru Ngaji
$guru_query = "SELECT COUNT(*) as total FROM guru_ngaji WHERE status = 'aktif'";
$guru_result = mysqli_query($conn, $guru_query);
$total_guru = $guru_result ? mysqli_fetch_assoc($guru_result)['total'] : 0;

// Total Berita
$berita_count_query = "SELECT COUNT(*) as total FROM berita WHERE status = 'publish'";
$berita_count_result = mysqli_query($conn, $berita_count_query);
$total_berita = $berita_count_result ? mysqli_fetch_assoc($berita_count_result)['total'] : 0;

// Total Pengurus
$pengurus_query = "SELECT COUNT(*) as total FROM pengurus WHERE status = 'aktif'";
$pengurus_result = mysqli_query($conn, $pengurus_query);
$total_pengurus = $pengurus_result ? mysqli_fetch_assoc($pengurus_result)['total'] : 0;

// ============================================
// STATISTIK PER KABUPATEN
// ============================================
$stat_kabupaten = [];
$stat_query = "
    SELECT 
        k.id,
        k.nama,
        COUNT(g.id) as total_guru
    FROM kabupaten k
    LEFT JOIN kecamatan kec ON kec.kabupaten_id = k.id
    LEFT JOIN desa d ON d.kecamatan_id = kec.id
    LEFT JOIN guru_ngaji g ON g.desa_id = d.id AND g.status = 'aktif'
    WHERE k.id BETWEEN 1801 AND 1813 OR k.id IN (1871, 1872)
    GROUP BY k.id, k.nama
    ORDER BY total_guru DESC, k.nama ASC
";
$stat_result = mysqli_query($conn, $stat_query);
if ($stat_result) {
    while ($row = mysqli_fetch_assoc($stat_result)) {
        $stat_kabupaten[] = $row;
    }
}

// Total Guru dari statistik (untuk validasi)
$total_guru_stat = 0;
foreach ($stat_kabupaten as $stat) {
    $total_guru_stat += $stat['total_guru'];
}
// Jika total guru statistik berbeda dengan total_guru, gunakan yang lebih besar
$total_guru_display = max($total_guru, $total_guru_stat);

// ============================================
// AMBIL GURU TERBARU
// ============================================
$guru_terbaru_query = "SELECT id, nama, tempat_mengajar, tempat_mengajar_detail, created_at FROM guru_ngaji WHERE status = 'aktif' ORDER BY created_at DESC LIMIT 3";
$guru_terbaru = mysqli_query($conn, $guru_terbaru_query);

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- HERO SECTION WITH SLIDER -->
<!-- ============================================ -->
<section class="hero-slider-section" style="padding: 0; background: #1a1a2e; position: relative; overflow: hidden;">
    <div class="hero-slider-container" style="position: relative; width: 100%; max-width: 1200px; margin: 0 auto; overflow: hidden;">
        
        <!-- Slide Container -->
        <div class="hero-slides" style="display: flex; transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); height: 500px; position: relative;">
            <?php 
            $slide_data = [];
            $index = 0;
            
            if ($slide_berita && mysqli_num_rows($slide_berita) > 0):
                while ($slide = mysqli_fetch_assoc($slide_berita)):
                    $slide_data[] = $slide;
                endwhile;
            endif;
            
            // Jika tidak ada berita, gunakan slide default
            if (empty($slide_data)) {
                $slide_data = [
                    [
                        'id' => 0,
                        'judul' => 'Selamat Datang di PGNI Lampung',
                        'isi' => 'Persatuan Guru Ngaji Indonesia Provinsi Lampung adalah wadah bagi para guru ngaji untuk bersinergi dalam meningkatkan kualitas pendidikan Al-Qur\'an di Lampung.',
                        'gambar' => '',
                        'created_at' => date('Y-m-d H:i:s'),
                        'author' => 'PGNI Lampung'
                    ]
                ];
            }
            
            // Ambil 5 slide teratas untuk hero
            $slide_display = array_slice($slide_data, 0, 5);
            $total_slides = count($slide_display);
            ?>
            
            <?php foreach ($slide_display as $index => $slide): ?>
                <div class="hero-slide" style="min-width: 100%; height: 500px; position: relative; flex-shrink: 0; background: #1a1a2e;">
                    <!-- Background Image -->
                    <?php if (!empty($slide['gambar']) && file_exists($root_path . '/assets/images/berita/' . $slide['gambar'])): ?>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('assets/images/berita/<?php echo htmlspecialchars($slide['gambar']); ?>') center/cover no-repeat; filter: brightness(0.4); transform: scale(1.05); transition: transform 8s ease;">
                        </div>
                    <?php else: ?>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, #1a6e3a, #2d8f52, #1a6e3a); background-size: 200% 200%; animation: gradientMove 10s ease infinite;">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content Overlay -->
                    <div class="hero-content-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; padding: 0 50px; background: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 70%, rgba(0,0,0,0.1) 100%);">
                        <div class="hero-content" style="max-width: 600px; color: #fff; position: relative; z-index: 2; width: 100%;">
                            <?php if (isset($has_kategori) && $has_kategori && !empty($slide['kategori'])): ?>
                                <span class="hero-category" style="display: inline-block; background: #d4a847; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                                    <?php echo htmlspecialchars($slide['kategori']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 10px; line-height: 1.3; text-shadow: 0 2px 15px rgba(0,0,0,0.5);">
                                <?php echo htmlspecialchars($slide['judul']); ?>
                            </h2>
                            
                            <p style="font-size: 0.95rem; opacity: 0.9; margin-bottom: 16px; line-height: 1.5; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                                <?php echo potong_teks($slide['isi'], 110); ?>
                            </p>
                            
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                <a href="berita_detail.php?id=<?php echo $slide['id']; ?>" class="btn-read" 
                                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease;">
                                    <i class="fas fa-book-open"></i> Baca Selengkapnya
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Slide Counter -->
                    <div class="slide-counter" style="position: absolute; bottom: 20px; right: 40px; color: rgba(255,255,255,0.6); font-size: 0.8rem; z-index: 2;">
                        <?php echo ($index + 1); ?> / <?php echo $total_slides; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navigation Buttons -->
        <button class="hero-slider-btn hero-slider-prev" onclick="prevSlide()" style="
            position: absolute; top: 50%; left: 20px; transform: translateY(-50%); 
            background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); 
            border: 1px solid rgba(255,255,255,0.2); color: #fff; 
            width: 45px; height: 45px; border-radius: 50%; 
            font-size: 1.2rem; cursor: pointer; 
            transition: all 0.3s ease; z-index: 10;
            display: flex; align-items: center; justify-content: center;
        ">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <button class="hero-slider-btn hero-slider-next" onclick="nextSlide()" style="
            position: absolute; top: 50%; right: 20px; transform: translateY(-50%);
            background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2); color: #fff;
            width: 45px; height: 45px; border-radius: 50%;
            font-size: 1.2rem; cursor: pointer;
            transition: all 0.3s ease; z-index: 10;
            display: flex; align-items: center; justify-content: center;
        ">
            <i class="fas fa-chevron-right"></i>
        </button>
        
        <!-- Dots Indicator -->
        <div class="hero-slider-dots" style="
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 10px; z-index: 10;
        ">
            <?php for ($i = 0; $i < $total_slides; $i++): ?>
                <span class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" 
                      onclick="goToSlide(<?php echo $i; ?>)"
                      style="
                        width: 12px; height: 12px; border-radius: 50%;
                        background: <?php echo $i === 0 ? '#d4a847' : 'rgba(255,255,255,0.4)'; ?>;
                        cursor: pointer; transition: all 0.3s ease;
                        border: <?php echo $i === 0 ? '2px solid #fff' : 'none'; ?>;
                    "></span>
            <?php endfor; ?>
        </div>
        
        <!-- Tombol Daftar di bawah slide -->
        <div class="hero-button-wrapper" style="
            position: relative; 
            z-index: 15;
            display: flex; 
            justify-content: center; 
            gap: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #1a6e3a, #2d8f52);
            flex-wrap: wrap;
        ">
            <a href="registrasi.php" class="hero-btn hero-btn-primary" style="
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                padding: 12px 30px; 
                background: #d4a847; 
                color: #fff; 
                border-radius: 8px; 
                text-decoration: none; 
                font-weight: 600; 
                font-size: 0.95rem; 
                transition: all 0.3s ease; 
                box-shadow: 0 4px 15px rgba(212, 168, 71, 0.3);
            ">
                <i class="fas fa-user-plus"></i> Daftar Keanggotaan
            </a>
            <a href="guru.php" class="hero-btn hero-btn-secondary" style="
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                padding: 12px 30px; 
                background: rgba(255,255,255,0.15); 
                backdrop-filter: blur(10px);
                color: #fff; 
                border: 1px solid rgba(255,255,255,0.3); 
                border-radius: 8px; 
                text-decoration: none; 
                font-weight: 500; 
                font-size: 0.95rem; 
                transition: all 0.3s ease;
            ">
                <i class="fas fa-users"></i> Lihat Guru
            </a>
        </div>
    </div>
</section>

<style>
    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .hero-slider-btn:hover {
        background: rgba(255,255,255,0.3) !important;
        transform: translateY(-50%) scale(1.1) !important;
    }
    
    .hero-dot:hover {
        transform: scale(1.2);
    }
    
    .hero-dot.active {
        background: #d4a847 !important;
        border: 2px solid #fff !important;
        transform: scale(1.1);
    }
    
    .hero-btn:hover {
        transform: translateY(-2px);
    }
    
    .hero-btn-primary:hover {
        background: #c49a3a !important;
        box-shadow: 0 6px 25px rgba(212, 168, 71, 0.4);
    }
    
    .hero-btn-secondary:hover {
        background: rgba(255,255,255,0.25) !important;
    }
    
    /* ============================================ */
    /* RESPONSIVE - MEMPERBAIKI OVERLAP MOBILE */
    /* ============================================ */
    @media (max-width: 768px) {
        .hero-slides, .hero-slide {
            height: 420px !important;
        }
        .hero-content-overlay {
            padding: 0 25px !important;
            /* Memastikan teks rata tengah tinggi slide */
            justify-content: center;
        }
        .hero-content {
            padding-bottom: 25px; /* Menambah jarak bawah agar tidak menabrak dots */
        }
        .hero-content h2 {
            font-size: 1.35rem !important;
            margin-bottom: 8px !important;
        }
        .hero-content p {
            font-size: 0.82rem !important;
            margin-bottom: 12px !important;
            /* Membatasi baris teks di HP agar tidak terlalu panjang */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-read {
            padding: 6px 14px !important;
            font-size: 0.78rem !important;
        }
        .hero-slider-btn {
            width: 35px !important;
            height: 35px !important;
            font-size: 0.9rem !important;
        }
        .hero-slider-btn.hero-slider-prev {
            left: 8px !important;
        }
        .hero-slider-btn.hero-slider-next {
            right: 8px !important;
        }
        .hero-slider-dots {
            bottom: 12px !important;
        }
        .slide-counter {
            display: none !important;
        }
        
        /* Tombol Bawah (Daftar / Lihat Guru) */
        .hero-button-wrapper {
            padding: 10px 15px !important;
            gap: 10px !important;
        }
        .hero-btn {
            padding: 8px 16px !important;
            font-size: 0.8rem !important;
            border-radius: 6px !important;
        }
    }
    
    @media (max-width: 480px) {
        .hero-slides, .hero-slide {
            height: 380px !important;
        }
        .hero-content h2 {
            font-size: 1.15rem !important;
        }
        .hero-content p {
            font-size: 0.78rem !important;
            -webkit-line-clamp: 2; /* Batasi 2 baris di layar sangat kecil */
        }
        .hero-slider-dots .hero-dot {
            width: 8px !important;
            height: 8px !important;
        }
        .hero-button-wrapper {
            gap: 8px !important;
        }
        .hero-btn {
            padding: 7px 12px !important;
            font-size: 0.75rem !important;
        }
    }

    @media (max-width: 380px) {
        .hero-button-wrapper {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .hero-btn {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>

<script>
    let currentSlide = 0;
    const totalSlides = <?php echo $total_slides; ?>;
    let slideInterval;
    
    function goToSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        currentSlide = index;
        
        const slides = document.querySelector('.hero-slides');
        if (slides) {
            slides.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        }
        
        // Update dots
        document.querySelectorAll('.hero-dot').forEach((dot, i) => {
            if (i === currentSlide) {
                dot.classList.add('active');
                dot.style.background = '#d4a847';
                dot.style.border = '2px solid #fff';
            } else {
                dot.classList.remove('active');
                dot.style.background = 'rgba(255,255,255,0.4)';
                dot.style.border = 'none';
            }
        });
        
        // Reset interval
        resetSlideInterval();
    }
    
    function nextSlide() {
        goToSlide(currentSlide + 1);
    }
    
    function prevSlide() {
        goToSlide(currentSlide - 1);
    }
    
    function resetSlideInterval() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
        if (totalSlides > 1) {
            slideInterval = setInterval(nextSlide, 6000);
        }
    }
    
    // Auto play & Pause on hover
    document.addEventListener('DOMContentLoaded', function() {
        if (totalSlides > 1) {
            slideInterval = setInterval(nextSlide, 6000);
        }

        const container = document.querySelector('.hero-slider-container');
        if (container) {
            container.addEventListener('mouseenter', function() {
                if (slideInterval) clearInterval(slideInterval);
            });
            container.addEventListener('mouseleave', function() {
                if (totalSlides > 1) {
                    slideInterval = setInterval(nextSlide, 6000);
                }
            });
        }
    });
</script>

<!-- ============================================ -->
<!-- LEGALITAS PGNI LAMPUNG -->
<!-- ============================================ -->
<section class="legalitas-section" style="padding: 50px 0; background: linear-gradient(135deg, #f8f9fa, #e8f0e8);">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start;">
            <div style="direction: ltr; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <i class="fas fa-gavel" style="font-size: 2rem; color: #d4a847;"></i>
                    <h2 style="font-size: 1.6rem; color: #1a1a2e; margin: 0;">Legalitas PGNI Lampung</h2>
                </div>
                <p style="color: #444; font-size: 0.98rem; line-height: 1.8; margin-bottom: 20px; direction: ltr; text-align: left;">
                    Persatuan Guru Ngaji Lampung telah resmi terdaftar sebagai badan hukum
                    berdasarkan <strong>Keputusan Menteri Hukum dan Hak Asasi Manusia Republik Indonesia</strong>
                    <br>
                    <span style="display: inline-block; background: #1a6e3a; color: #fff; padding: 6px 16px; border-radius: 6px; font-size: 0.9rem; margin-top: 8px;">
                        Nomor AHU-0001890.AH.01.07.TAHUN 2023
                    </span>
                </p>
                <p style="color: #555; font-size: 0.92rem; margin-bottom: 8px;">
                    <i class="fas fa-file-alt" style="color: #d4a847; width: 20px;"></i> 
                    Tentang Pengesahan Pendirian Perkumpulan
                </p>
            </div>
            <div style="background: #fff; padding: 25px 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border-left: 5px solid #d4a847; direction: ltr; text-align: left;">
                <h4 style="font-size: 1rem; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: #d4a847;"></i> Informasi Pendirian
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-map-marker-alt" style="color: #1a6e3a; margin-top: 3px; width: 18px;"></i>
                        <span><strong>Kedudukan:</strong> Kota Bandar Lampung</span>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-file-signature" style="color: #1a6e3a; margin-top: 3px; width: 18px;"></i>
                        <span><strong>Akta Notaris:</strong> No. 12, 17 Februari 2023</span>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-user-tie" style="color: #1a6e3a; margin-top: 3px; width: 18px;"></i>
                        <span><strong>Notaris:</strong> Nedi Heryandi, S.H.</span>
                    </li>
                    <li style="padding: 10px 0 0 0; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-calendar-check" style="color: #1a6e3a; margin-top: 3px; width: 18px;"></i>
                        <span><strong>Tanggal Pengesahan:</strong> 14 Maret 2023</span>
                    </li>
                </ul>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #e8f0e8; text-align: center;">
                    <span style="display: inline-block; background: #f8f9fa; padding: 5px 20px; border-radius: 20px; font-size: 0.8rem; color: #666;">
                        <i class="fas fa-check-circle" style="color: #1a6e3a;"></i> Resmi Terdaftar secara Hukum
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- STATISTIK PER KABUPATEN -->
<!-- ============================================ -->
<section class="statistik-kabupaten" style="padding: 60px 0; background: #ffffff;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="text-align: center; margin-bottom: 35px;">
            <h2 style="font-size: 1.8rem; color: #1a1a2e; margin-bottom: 8px;">
                <i class="fas fa-chart-bar" style="color: #d4a847;"></i> Statistik Guru Ngaji Per Kabupaten/Kota
            </h2>
            <p style="color: #999; font-size: 0.95rem;">Sebaran guru ngaji aktif di seluruh wilayah Provinsi Lampung</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php if (count($stat_kabupaten) > 0): ?>
                <?php 
                // Cari nilai maksimum untuk progress bar
                $max_guru = 0;
                foreach ($stat_kabupaten as $stat) {
                    if ($stat['total_guru'] > $max_guru) {
                        $max_guru = $stat['total_guru'];
                    }
                }
                $max_guru = max($max_guru, 1);
                ?>
                
                <?php foreach ($stat_kabupaten as $stat): ?>
                    <?php 
                    $persentase = round(($stat['total_guru'] / $max_guru) * 100);
                    if ($stat['total_guru'] >= 100) {
                        $warna = '#1a6e3a';
                    } elseif ($stat['total_guru'] >= 50) {
                        $warna = '#d4a847';
                    } elseif ($stat['total_guru'] >= 20) {
                        $warna = '#3498db';
                    } else {
                        $warna = '#95a5a6';
                    }
                    ?>
                    <div class="stat-kab-item" style="background: #f8f9fa; padding: 18px 20px; border-radius: 10px; transition: all 0.3s ease; border-left: 4px solid <?php echo $warna; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #1a1a2e; font-size: 0.9rem; direction: ltr; text-align: left;">
                                <?php echo htmlspecialchars($stat['nama']); ?>
                            </span>
                            <span style="font-weight: 700; color: <?php echo $warna; ?>; font-size: 1rem;">
                                <?php echo number_format($stat['total_guru']); ?>
                            </span>
                        </div>
                        <div style="width: 100%; height: 6px; background: #e8e8e8; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $persentase; ?>%; background: <?php echo $warna; ?>; border-radius: 3px; transition: width 1s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                            <span style="font-size: 0.65rem; color: #999;">Guru Ngaji</span>
                            <span style="font-size: 0.65rem; color: #999;"><?php echo $persentase; ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #f8f9fa; border-radius: 12px;">
                    <i class="fas fa-chart-bar" style="font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 10px;"></i>
                    <p style="color: #999;">Belum ada data statistik kabupaten.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Total Keseluruhan -->
        <div style="margin-top: 25px; text-align: center; padding: 15px; background: linear-gradient(135deg, #1a6e3a, #2d8f52); border-radius: 10px; color: #fff;">
            <span style="font-size: 1rem; opacity: 0.9;">
                <i class="fas fa-users"></i> Total Seluruh Guru Ngaji Aktif: 
                <strong style="font-size: 1.3rem; color: #d4a847;"><?php echo number_format($total_guru_display); ?></strong> 
                Guru dari <?php echo count($stat_kabupaten); ?> Kabupaten/Kota
            </span>
        </div>
        
        <div style="margin-top: 15px; text-align: center;">
            <a href="detail_statistik.php" style="display: inline-block; padding: 10px 30px; background: #1a6e3a; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                <i class="fas fa-chart-pie"></i> Lihat Statistik Lengkap
            </a>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- BERITA TERBARU SECTION -->
<!-- ============================================ -->
<section class="berita-section" style="padding: 60px 0; background: #f8f9fa;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <h2 style="font-size: 1.8rem; color: #1a1a2e;">
                <i class="fas fa-newspaper" style="color: #d4a847;"></i> Berita Terbaru
            </h2>
            <a href="berita.php" style="color: #1a6e3a; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
            <?php if ($berita_terbaru && mysqli_num_rows($berita_terbaru) > 0): ?>
                <?php while ($berita = mysqli_fetch_assoc($berita_terbaru)): ?>
                    <div class="berita-card" style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.08); transition: all 0.3s ease; direction: ltr; text-align: left;">
                        <?php if (!empty($berita['gambar'])): ?>
                            <div style="height: 200px; overflow: hidden; position: relative;">
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
                        <div style="padding: 20px;">
                            <div style="font-size: 0.8rem; color: #999; margin-bottom: 8px; direction: ltr; text-align: left;">
                                <i class="fas fa-calendar-alt"></i> <?php echo tanggal_indonesia($berita['created_at']); ?>
                                <?php if (!empty($berita['author'])): ?>
                                    <span style="margin-left: 10px;"><i class="fas fa-user"></i> <?php echo htmlspecialchars($berita['author']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 style="font-size: 1.05rem; margin-bottom: 8px; line-height: 1.4; direction: ltr; text-align: left;">
                                <a href="berita_detail.php?id=<?php echo $berita['id']; ?>" 
                                   style="color: #1a1a2e; text-decoration: none; transition: color 0.3s ease;">
                                    <?php echo htmlspecialchars($berita['judul']); ?>
                                </a>
                            </h3>
                            <p style="color: #666; font-size: 0.9rem; line-height: 1.6; margin-bottom: 12px; direction: ltr; text-align: left;">
                                <?php echo potong_teks($berita['isi'], 100); ?>
                            </p>
                            <a href="berita_detail.php?id=<?php echo $berita['id']; ?>" 
                               style="color: #1a6e3a; text-decoration: none; font-weight: 500; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                                Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 12px;">
                    <i class="fas fa-newspaper" style="font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 10px;"></i>
                    <p style="color: #999;">Belum ada berita terbaru.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- GURU TERBARU SECTION -->
<!-- ============================================ -->
<section class="guru-section" style="padding: 60px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <h2 style="font-size: 1.8rem; color: #1a1a2e;">
                <i class="fas fa-user-graduate" style="color: #d4a847;"></i> Guru Ngaji Terbaru
            </h2>
            <a href="guru.php" style="color: #1a6e3a; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
            <?php if ($guru_terbaru && mysqli_num_rows($guru_terbaru) > 0): ?>
                <?php while ($guru = mysqli_fetch_assoc($guru_terbaru)): ?>
                    <div class="guru-card" style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); text-align: center; transition: all 0.3s ease; direction: ltr;">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #1a6e3a, #2d8f52); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2rem; color: #fff;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; color: #1a1a2e; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($guru['nama']); ?>
                        </h3>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">
                            <i class="fas fa-school" style="color: #d4a847;"></i> 
                            <?php echo htmlspecialchars($guru['tempat_mengajar']); ?>
                            <?php if (!empty($guru['tempat_mengajar_detail'])): ?>
                                - <?php echo htmlspecialchars($guru['tempat_mengajar_detail']); ?>
                            <?php endif; ?>
                        </p>
                        <p style="color: #999; font-size: 0.8rem;">
                            <i class="fas fa-calendar-alt"></i> Bergabung: <?php echo tanggal_indonesia($guru['created_at']); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #f8f9fa; border-radius: 12px;">
                    <i class="fas fa-user-graduate" style="font-size: 2.5rem; color: #d4a847; display: block; margin-bottom: 10px;"></i>
                    <p style="color: #999;">Belum ada guru ngaji terdaftar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- TENTANG SINGKAT -->
<!-- ============================================ -->
<section class="tentang-singkat" style="padding: 60px 0; background: #f8f9fa;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
            <div>
                <img src="assets/images/logo/logo-pgni.png" 
                     alt="PGNI Lampung" 
                     style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 30px rgba(0,0,0,0.1);"
                     onerror="this.style.display='none'">
            </div>
            <div style="direction: ltr; text-align: left;">
                <h2 style="font-size: 1.8rem; color: #1a1a2e; margin-bottom: 15px;">
                    <span style="color: #d4a847;">PGNI</span> Lampung
                </h2>
                <p style="color: #555; font-size: 1rem; line-height: 1.8; margin-bottom: 15px; direction: ltr; text-align: left;">
                    Persatuan Guru Ngaji Indonesia (PGNI) Provinsi Lampung adalah organisasi yang menghimpun 
                    para guru ngaji, ustadz/ustadzah, pengajar TPA/MDTA, dan para pengajar Al-Qur'an lainnya 
                    di seluruh Kabupaten/Kota se-Provinsi Lampung.
                </p>
                <p style="color: #555; font-size: 1rem; line-height: 1.8; margin-bottom: 20px; direction: ltr; text-align: left;">
                    Tujuan utama PGNI adalah meningkatkan kualitas dan kesejahteraan para guru ngaji, 
                    serta memajukan pendidikan Al-Qur'an di Provinsi Lampung.
                </p>
                <a href="tentang.php" style="display: inline-block; padding: 12px 30px; background: #1a6e3a; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-info-circle"></i> Selengkapnya
                </a>
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
    
    .berita-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .berita-card:hover .berita-card img {
        transform: scale(1.05);
    }
    
    .berita-card h3 a:hover {
        color: #1a6e3a !important;
    }
    
    .guru-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .stat-kab-item {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }
    
    .stat-kab-item:nth-child(1) { animation-delay: 0.05s; }
    .stat-kab-item:nth-child(2) { animation-delay: 0.10s; }
    .stat-kab-item:nth-child(3) { animation-delay: 0.15s; }
    .stat-kab-item:nth-child(4) { animation-delay: 0.20s; }
    .stat-kab-item:nth-child(5) { animation-delay: 0.25s; }
    .stat-kab-item:nth-child(6) { animation-delay: 0.30s; }
    .stat-kab-item:nth-child(7) { animation-delay: 0.35s; }
    .stat-kab-item:nth-child(8) { animation-delay: 0.40s; }
    .stat-kab-item:nth-child(9) { animation-delay: 0.45s; }
    .stat-kab-item:nth-child(10) { animation-delay: 0.50s; }
    .stat-kab-item:nth-child(11) { animation-delay: 0.55s; }
    .stat-kab-item:nth-child(12) { animation-delay: 0.60s; }
    .stat-kab-item:nth-child(13) { animation-delay: 0.65s; }
    .stat-kab-item:nth-child(14) { animation-delay: 0.70s; }
    .stat-kab-item:nth-child(15) { animation-delay: 0.75s; }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-kab-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    @media (max-width: 1024px) {
        .tentang-singkat .container > div {
            grid-template-columns: 1fr !important;
        }
        .statistik-kabupaten .container > div {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)) !important;
        }
    }
    
    @media (max-width: 768px) {
        .berita-section .container > div:last-child {
            grid-template-columns: 1fr !important;
        }
        .guru-section .container > div:last-child {
            grid-template-columns: 1fr !important;
        }
        .statistik-kabupaten .container > div {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        }
        .stat-kab-item {
            padding: 14px 16px !important;
        }
    }
    
    @media (max-width: 480px) {
        .statistik-kabupaten .container > div {
            grid-template-columns: 1fr 1fr !important;
        }
        .stat-kab-item {
            padding: 12px 14px !important;
        }
        .stat-kab-item .stat-label {
            font-size: 0.75rem !important;
        }
        .stat-kab-item .stat-number {
            font-size: 0.85rem !important;
        }
    }
    
    @media (max-width: 1024px) {
        .legalitas-section .container > div { 
            grid-template-columns: 1fr !important; 
            gap: 25px; 
        }
    }
</style>

<?php include $root_path . '/include/footer.php'; ?>