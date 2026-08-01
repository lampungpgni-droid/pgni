<?php
// tentang.php - Halaman Tentang Kami
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

$title = 'Tentang Kami - PGNI Lampung';

// ============================================
// CEK DAN BUAT TABEL LEGALITAS JIKA BELUM ADA
// ============================================
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'legalitas'");
if (!$check_table || mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS `legalitas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nomor_sk` varchar(100) DEFAULT NULL,
        `tanggal_sk` date DEFAULT NULL,
        `akta_notaris` varchar(100) DEFAULT NULL,
        `notaris` varchar(100) DEFAULT NULL,
        `deskripsi` text DEFAULT NULL,
        `status` enum('aktif','nonaktif') DEFAULT 'aktif',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    mysqli_query($conn, $create_table);
    
    $insert_default = "INSERT INTO `legalitas` SET 
        `nomor_sk` = 'AHU-0001890.AH.01.07.TAHUN 2023',
        `tanggal_sk` = '2023-03-14',
        `akta_notaris` = 'No. 12, 17 Februari 2023',
        `notaris` = 'Nedi Heryandi, S.H.',
        `deskripsi` = 'Persatuan Guru Ngaji Lampung telah resmi terdaftar sebagai badan hukum berdasarkan Keputusan Menteri Hukum dan Hak Asasi Manusia Republik Indonesia',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
}

// ============================================
// CEK DAN BUAT TABEL PROGRAM UNGGULAN JIKA BELUM ADA
// ============================================
$check_program = mysqli_query($conn, "SHOW TABLES LIKE 'program_unggulan'");
if (!$check_program || mysqli_num_rows($check_program) == 0) {
    $create_program = "CREATE TABLE IF NOT EXISTS `program_unggulan` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nama` varchar(100) NOT NULL,
        `deskripsi` text DEFAULT NULL,
        `icon` varchar(50) DEFAULT 'fa-star',
        `urutan` int(11) DEFAULT 0,
        `status` enum('aktif','nonaktif') DEFAULT 'aktif',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    mysqli_query($conn, $create_program);
    
    $programs_default = [
        ['Pelatihan Guru Ngaji', 'Program pelatihan untuk meningkatkan kompetensi guru ngaji di seluruh Lampung', 'fa-chalkboard-teacher', 1],
        ['Sertifikasi Guru Ngaji', 'Sertifikasi resmi bagi guru ngaji yang telah memenuhi standar kompetensi', 'fa-certificate', 2],
        ['Beasiswa Pendidikan', 'Beasiswa bagi guru ngaji berprestasi untuk melanjutkan pendidikan', 'fa-graduation-cap', 3],
        ['Dakwah & Pengabdian', 'Program dakwah dan pengabdian masyarakat berbasis Al-Qur\'an', 'fa-hands-helping', 4]
    ];
    foreach ($programs_default as $program) {
        $insert = "INSERT INTO `program_unggulan` SET 
            `nama` = '" . mysqli_real_escape_string($conn, $program[0]) . "',
            `deskripsi` = '" . mysqli_real_escape_string($conn, $program[1]) . "',
            `icon` = '" . mysqli_real_escape_string($conn, $program[2]) . "',
            `urutan` = " . $program[3] . ",
            `status` = 'aktif'";
        mysqli_query($conn, $insert);
    }
}

// ============================================
// AMBIL DATA YAYASAN
// ============================================
$query = "SELECT * FROM yayasan WHERE id = 1";
$result = mysqli_query($conn, $query);
$yayasan = mysqli_fetch_assoc($result);

if (!$yayasan) {
    $insert_default = "INSERT INTO `yayasan` SET 
        `nama_yayasan` = 'PGNI Lampung',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_default);
    $result = mysqli_query($conn, $query);
    $yayasan = mysqli_fetch_assoc($result);
}

// ============================================
// AMBIL DATA PENGURUS - TAMPILKAN SEMUA
// ============================================
$pengurus_query = "SELECT * FROM pengurus WHERE status = 'aktif' ORDER BY urutan ASC, jabatan ASC";
$pengurus_list = mysqli_query($conn, $pengurus_query);

// ============================================
// AMBIL DATA LEGALITAS
// ============================================
$legalitas_query = "SELECT * FROM legalitas WHERE id = 1";
$legalitas_result = mysqli_query($conn, $legalitas_query);
$legalitas = mysqli_fetch_assoc($legalitas_result);

if (!$legalitas) {
    $insert_legalitas = "INSERT INTO `legalitas` SET 
        `nomor_sk` = 'AHU-0001890.AH.01.07.TAHUN 2023',
        `tanggal_sk` = '2023-03-14',
        `akta_notaris` = 'No. 12, 17 Februari 2023',
        `notaris` = 'Nedi Heryandi, S.H.',
        `deskripsi` = 'Persatuan Guru Ngaji Lampung telah resmi terdaftar sebagai badan hukum berdasarkan Keputusan Menteri Hukum dan Hak Asasi Manusia Republik Indonesia',
        `status` = 'aktif'";
    mysqli_query($conn, $insert_legalitas);
    $legalitas_result = mysqli_query($conn, $legalitas_query);
    $legalitas = mysqli_fetch_assoc($legalitas_result);
}

// ============================================
// AMBIL STATISTIK - SOLUSI NaN
// ============================================

// Fungsi untuk mendapatkan nilai count dengan aman
function get_safe_count($conn, $table, $where = '') {
    $query = "SELECT COUNT(*) as total FROM $table";
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row ? (int)$row['total'] : 0;
    }
    return 0;
}

// Total Guru Ngaji
$total_guru = get_safe_count($conn, 'guru_ngaji', "status = 'aktif' AND status_verifikasi = 'disetujui'");

// Total Berita
$total_berita = get_safe_count($conn, 'berita', "status = 'publish'");

// Total Pengurus
$total_pengurus = get_safe_count($conn, 'pengurus', "status = 'aktif'");

// Total Kabupaten
$total_kabupaten = get_safe_count($conn, 'kabupaten', "id BETWEEN 1801 AND 1813 OR id IN (1871, 1872)");

// Pastikan semua nilai adalah integer positif
$total_guru = max(0, (int)$total_guru);
$total_berita = max(0, (int)$total_berita);
$total_pengurus = max(0, (int)$total_pengurus);
$total_kabupaten = max(0, (int)$total_kabupaten);

// Jika semua masih 0, set nilai default
if ($total_guru == 0 && $total_berita == 0 && $total_pengurus == 0 && $total_kabupaten == 0) {
    $total_kabupaten = 15; // Default: 15 kabupaten/kota di Lampung
}

// ============================================
// AMBIL PROGRAM UNGGULAN
// ============================================
$program_query = "SELECT * FROM program_unggulan WHERE status = 'aktif' ORDER BY urutan ASC LIMIT 4";
$program_list = mysqli_query($conn, $program_query);

if (!$program_list || mysqli_num_rows($program_list) == 0) {
    $programs = [
        ['nama' => 'Pelatihan Guru Ngaji', 'deskripsi' => 'Program pelatihan untuk meningkatkan kompetensi guru ngaji di seluruh Lampung', 'icon' => 'fa-chalkboard-teacher'],
        ['nama' => 'Sertifikasi Guru Ngaji', 'deskripsi' => 'Sertifikasi resmi bagi guru ngaji yang telah memenuhi standar kompetensi', 'icon' => 'fa-certificate'],
        ['nama' => 'Beasiswa Pendidikan', 'deskripsi' => 'Beasiswa bagi guru ngaji berprestasi untuk melanjutkan pendidikan', 'icon' => 'fa-graduation-cap'],
        ['nama' => 'Dakwah & Pengabdian', 'deskripsi' => 'Program dakwah dan pengabdian masyarakat berbasis Al-Qur\'an', 'icon' => 'fa-hands-helping']
    ];
} else {
    $programs = [];
    while ($row = mysqli_fetch_assoc($program_list)) {
        $programs[] = $row;
    }
}

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- PAGE BANNER -->
<!-- ============================================ -->
<section class="page-banner">
    <div class="container">
        <h1 class="banner-title">📖 Tentang Kami</h1>
        <p class="banner-subtitle">
            <?php echo htmlspecialchars($yayasan['nama_yayasan'] ?? 'PGNI Lampung'); ?>
        </p>
        <div class="banner-breadcrumb">
            <a href="index.php">Beranda</a> / <span>Tentang Kami</span>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- TENTANG KAMI - DESKRIPSI -->
<!-- ============================================ -->
<section class="about-section">
    <div class="container">
        <div class="about-wrapper">
            <div class="about-image">
                <?php if (!empty($yayasan['logo'])): ?>
                    <img src="assets/images/logo/<?php echo htmlspecialchars($yayasan['logo']); ?>" 
                         alt="<?php echo htmlspecialchars($yayasan['nama_yayasan']); ?>"
                         loading="lazy"
                         onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="about-image-placeholder">
                        <i class="fas fa-building"></i>
                        <h3><?php echo htmlspecialchars($yayasan['nama_yayasan'] ?? 'PGNI Lampung'); ?></h3>
                    </div>
                <?php endif; ?>
            </div>
            <div class="about-content">
                <h2 class="about-title">
                    <span class="highlight">Selamat Datang</span> di PGNI Lampung
                </h2>
                <div class="about-description">
                    <?php 
                    $deskripsi = $yayasan['deskripsi'] ?? 'Persatuan Guru Ngaji Indonesia (PGNI) Provinsi Lampung adalah organisasi yang menghimpun para guru ngaji, ustadz/ustadzah, pengajar TPA/MDTA, dan para pengajar Al-Qur\'an lainnya di seluruh Kabupaten/Kota se-Provinsi Lampung.';
                    echo nl2br(htmlspecialchars($deskripsi)); 
                    ?>
                </div>
                <?php if (!empty($yayasan['tahun_berdiri'])): ?>
                    <div class="about-established">
                        <i class="fas fa-calendar"></i> Berdiri sejak <?php echo htmlspecialchars($yayasan['tahun_berdiri']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- LEGALITAS -->
<!-- ============================================ -->
<section class="legalitas-about-section">
    <div class="container">
        <div class="legalitas-about-wrapper">
            <div class="legalitas-about-left">
                <h2 class="legalitas-about-title">
                    <i class="fas fa-gavel" style="color: #d4a847;"></i> Legalitas PGNI Lampung
                </h2>
                <p class="legalitas-about-text">
                    <?php 
                    $legalitas_text = $legalitas['deskripsi'] ?? 'Persatuan Guru Ngaji Lampung telah resmi terdaftar sebagai badan hukum berdasarkan Keputusan Menteri Hukum dan Hak Asasi Manusia Republik Indonesia';
                    echo nl2br(htmlspecialchars($legalitas_text));
                    ?>
                </p>
                <div class="legalitas-about-badge">
                    <?php echo htmlspecialchars($legalitas['nomor_sk'] ?? 'AHU-0001890.AH.01.07.TAHUN 2023'); ?>
                </div>
            </div>
            <div class="legalitas-about-right">
                <ul class="legalitas-about-list">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong>Kedudukan:</strong> <?php echo htmlspecialchars($yayasan['alamat_kota'] ?? 'Kota Bandar Lampung'); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-file-signature"></i>
                        <span><strong>Akta Notaris:</strong> <?php echo htmlspecialchars($legalitas['akta_notaris'] ?? 'No. 12, 17 Februari 2023'); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-user-tie"></i>
                        <span><strong>Notaris:</strong> <?php echo htmlspecialchars($legalitas['notaris'] ?? 'Nedi Heryandi, S.H.'); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-calendar-check"></i>
                        <span><strong>Tanggal Pengesahan:</strong> <?php echo isset($legalitas['tanggal_sk']) && $legalitas['tanggal_sk'] ? tanggal_indonesia($legalitas['tanggal_sk']) : '14 Maret 2023'; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- ANGGARAN DASAR -->
<!-- ============================================ -->
<section class="tentang-adart" style="padding: 40px 0;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: var(--primary); font-weight: 700;">Anggaran Dasar (AD)</h2>
            <p style="color: var(--text-light);">Berdasarkan Akta Pendirian Perkumpulan Nomor 12 Tanggal 17 Februari 2023, Notaris Nedi Heryandi, S.H.</p>
            <hr style="border: 2px solid var(--gold); width: 100px; margin: 10px auto;">
        </div>

        <div class="adart-content" style="background: var(--bg-white); padding: 30px; border-radius: var(--radius); box-shadow: var(--shadow);">
            
            <h4 style="color: var(--primary-dark); border-bottom: 1px solid #eee; padding-bottom: 10px;">Nama dan Tempat Kedudukan (Pasal 1)</h4>
            <ul style="margin-bottom: 20px; padding-left: 20px; list-style-type: disc;">
                <li>Perkumpulan ini bernama: <strong>PERSATUAN GURU NGAJI LAMPUNG</strong>.</li>
                <li>Berkedudukan di Kota Bandar Lampung (berkantor di Jalan Durian 2 Gg. Pondok No. 16, Kel. Durian Payung, Kec. Tanjung Karang Pusat).</li>
                <li>Dapat membuka kantor cabang atau perwakilan di tempat lain di dalam maupun luar negeri.</li>
            </ul>

            <h4 style="color: var(--primary-dark); border-bottom: 1px solid #eee; padding-bottom: 10px;">Maksud, Tujuan & Kegiatan Usaha (Pasal 3 & 4)</h4>
            <ul style="margin-bottom: 20px; padding-left: 20px; list-style-type: disc;">
                <li>Menjadikan generasi muda Indonesia (khususnya beragama Islam) untuk lebih mengenal, memahami, dan mengamalkan Al-Quran.</li>
                <li>Membantu usaha pemerintah di bidang pendidikan untuk mencerdaskan anak bangsa.</li>
                <li>Mensejahterakan masyarakat tertinggal demi keadilan sosial.</li>
                <li>Menjalin kerjasama serta meningkatkan mutu guru ngaji di Lampung, baik secara formal maupun nonformal.</li>
                <li>Membina akhlak anggota supaya berbudi pekerti luhur dan mewujudkan kesejahteraan organisasi.</li>
            </ul>

            <h4 style="color: var(--primary-dark); border-bottom: 1px solid #eee; padding-bottom: 10px;">Hak dan Kewajiban Anggota (Pasal 8)</h4>
            <ul style="margin-bottom: 20px; padding-left: 20px; list-style-type: disc;">
                <li><strong>Hak:</strong> Mendapatkan kartu anggota, menjaga nama baik perkumpulan, mengikuti kegiatan, dan mengeluarkan suara dalam Rapat Anggota.</li>
                <li><strong>Kewajiban:</strong> Menjunjung nama baik perkumpulan, mampu membaca Al-Quran beserta hukumnya, taat pada AD/ART, dan menyumbangkan tenaga/pikiran/harta apabila diperlukan.</li>
            </ul>

            <h4 style="color: var(--primary-dark); border-bottom: 1px solid #eee; padding-bottom: 10px;">Susunan Pengurus Pertama (Pasal 22)</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 250px;">
                    <strong>Pengurus Pusat:</strong>
                    <ul style="padding-left: 20px; list-style-type: circle;">
                        <li><strong>Ketua:</strong> Mas Amiruddin</li>
                        <li><strong>Sekretaris:</strong> Didi Mawardi, SP</li>
                        <li><strong>Wakil Sekretaris:</strong> Royani</li>
                        <li><strong>Bendahara:</strong> Lili Khosairi</li>
                        <li><strong>Wakil Bendahara:</strong> Dedi Gunawan</li>
                    </ul>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <strong>Badan Pengawas:</strong>
                    <ul style="padding-left: 20px; list-style-type: circle;">
                        <li><strong>Ketua:</strong> Hj. Hariyanti Syafrin, SH</li>
                        <li><strong>Anggota:</strong> H. Bokhari Ishlah</li>
                        <li><strong>Anggota:</strong> H. Hulman Ardhinata</li>
                        <li><strong>Anggota:</strong> Ust. H. Rahmat Hidayat, S.Sos.I</li>
                        <li><strong>Anggota:</strong> Babai</li>
                    </ul>
                </div>
            </div>

            <p style="font-size: 0.9em; color: var(--text-light); text-align: center; margin-top: 30px;">
                <em>* Ketentuan operasional yang lebih terperinci diatur lebih lanjut melalui Anggaran Rumah Tangga (ART) berdasarkan keputusan Rapat Anggota.</em>
            </p>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- VISI & MISI -->
<!-- ============================================ -->
<section class="visi-misi-section">
    <div class="container">
        <div class="visi-misi-wrapper">
            <div class="visi-box">
                <h3 class="visi-title">
                    <i class="fas fa-bullseye" style="color: #d4a847;"></i> Visi
                </h3>
                <div class="visi-content">
                    <?php 
                    $visi = $yayasan['visi'] ?? 'Mewujudkan generasi Qur\'ani yang berakhlak mulia dan berdaya saing global.';
                    echo nl2br(htmlspecialchars($visi)); 
                    ?>
                </div>
            </div>
            <div class="misi-box">
                <h3 class="misi-title">
                    <i class="fas fa-tasks" style="color: #d4a847;"></i> Misi
                </h3>
                <div class="misi-content">
                    <?php 
                    $misi = $yayasan['misi'] ?? "1. Meningkatkan kualitas pendidikan Al-Qur'an di Provinsi Lampung\n2. Meningkatkan kesejahteraan para guru ngaji\n3. Mengembangkan program-program dakwah dan pendidikan berbasis Al-Qur'an\n4. Menjalin kerjasama dengan berbagai pihak untuk kemajuan pendidikan Al-Qur'an";
                    $misi_array = explode("\n", $misi);
                    echo "<ul class='misi-list'>";
                    foreach ($misi_array as $item) {
                        if (trim($item) != '') {
                            echo "<li>" . htmlspecialchars(trim($item)) . "</li>";
                        }
                    }
                    echo "</ul>";
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- PROGRAM UNGGULAN DENGAN DETAIL -->
<!-- ============================================ -->
<section class="program-section">
    <div class="container">
        <div class="section-header-center">
            <h2 class="section-title">
                <i class="fas fa-star" style="color: #d4a847;"></i> Program Unggulan
            </h2>
            <p class="section-subtitle">Berbagai program unggulan PGNI Lampung untuk kemajuan pendidikan Al-Qur'an</p>
        </div>
        
        <div class="program-grid">
            <?php 
            // Definisikan detail program
            $program_details = [
                'Pelatihan Guru Ngaji' => [
                    'tujuan' => 'Program ini dirancang khusus untuk meningkatkan kompetensi, wawasan, serta kualitas metode pengajaran para guru ngaji di seluruh wilayah Provinsi Lampung.',
                    'sasaran' => 'Menjangkau para pengajar Al-Qur\'an, ustadz, ustadzah, serta pengajar TPA/MDTA agar memiliki standar pengajaran yang mumpuni.',
                    'materi' => [
                        'Metodologi Pengajaran Al-Qur\'an',
                        'Manajemen Kelas dan Psikologi Anak',
                        'Pemanfaatan Teknologi dalam Pengajaran',
                        'Pengembangan Kurikulum TPA/MDTA'
                    ]
                ],
                'Sertifikasi Guru Ngaji' => [
                    'tujuan' => 'Memberikan pengakuan dan legalitas formal berupa sertifikasi resmi bagi para guru ngaji yang telah memenuhi standar kompetensi.',
                    'sasaran' => 'Ditujukan kepada para pengajar Al-Qur\'an yang telah melalui dan memenuhi standar kompetensi yang ditetapkan oleh organisasi.',
                    'materi' => [
                        'Mampu membaca Al-Qur\'an dengan tartil',
                        'Memahami ilmu tajwid dengan baik',
                        'Menguasai metode pengajaran',
                        'Memiliki pengalaman mengajar minimal 1 tahun'
                    ]
                ],
                'Beasiswa Pendidikan' => [
                    'tujuan' => 'Memberikan dukungan finansial dan akses peningkatan mutu sumber daya manusia bagi para guru ngaji.',
                    'sasaran' => 'Diberikan kepada guru ngaji yang berprestasi agar dapat melanjutkan atau menempuh jenjang pendidikan yang lebih tinggi.',
                    'materi' => [
                        'Beasiswa Pendidikan Formal (S1/S2)',
                        'Beasiswa Pendidikan Non-Formal',
                        'Beasiswa Pelatihan & Workshop',
                        'Beasiswa Riset & Pengembangan'
                    ]
                ],
                'Dakwah & Pengabdian' => [
                    'tujuan' => 'Menggerakkan roda syiar Islam serta memberikan kontribusi nyata bagi masyarakat luas.',
                    'sasaran' => 'Pelaksanaan program-program dakwah dan pengabdian masyarakat yang berlandaskan dan berbasis pada nilai-nilai Al-Qur\'an.',
                    'materi' => [
                        'Kajian Islam Rutin di Masjid/Musholla',
                        'Program Santunan Yatim & Dhuafa',
                        'Bakti Sosial dan Pengabdian Masyarakat',
                        'Pelatihan Dai dan Mubaligh'
                    ]
                ]
            ];
            ?>
            
            <?php foreach ($programs as $program): 
                $nama_program = $program['nama'];
                $detail = isset($program_details[$nama_program]) ? $program_details[$nama_program] : null;
            ?>
                <div class="program-card">
                    <div class="program-icon">
                        <i class="fas <?php echo htmlspecialchars($program['icon'] ?? 'fa-star'); ?>"></i>
                    </div>
                    <h3 class="program-name"><?php echo htmlspecialchars($program['nama']); ?></h3>
                    <p class="program-desc"><?php echo htmlspecialchars($program['deskripsi']); ?></p>
                    
                    <?php if ($detail): ?>
                        <!-- Tombol Toggle Detail -->
                        <button class="program-detail-toggle" onclick="toggleProgramDetail(this)">
                            <i class="fas fa-chevron-down"></i> Lihat Detail
                        </button>
                        
                        <div class="program-detail-content" style="display: none;">
                            <div class="detail-item">
                                <strong>🎯 Tujuan & Fokus:</strong>
                                <p><?php echo htmlspecialchars($detail['tujuan']); ?></p>
                            </div>
                            <div class="detail-item">
                                <strong>👥 Sasaran:</strong>
                                <p><?php echo htmlspecialchars($detail['sasaran']); ?></p>
                            </div>
                            <div class="detail-item">
                                <strong>📋 <?php echo in_array($nama_program, ['Pelatihan Guru Ngaji', 'Sertifikasi Guru Ngaji']) ? 'Materi' : (in_array($nama_program, ['Beasiswa Pendidikan']) ? 'Jenis Beasiswa' : 'Program Unggulan'); ?>:</strong>
                                <ul>
                                    <?php foreach ($detail['materi'] as $item): ?>
                                        <li><?php echo htmlspecialchars($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- STATISTIK -->
<!-- ============================================ -->
<section class="statistik-about-section">
    <div class="container">
        <div class="statistik-about-grid">
            <div class="stat-item">
                <div class="stat-number" data-count="<?php echo $total_guru; ?>">0</div>
                <div class="stat-label">Guru Ngaji Terdaftar</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="<?php echo $total_berita; ?>">0</div>
                <div class="stat-label">Total Berita</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="<?php echo $total_pengurus; ?>">0</div>
                <div class="stat-label">Pengurus Aktif</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="<?php echo $total_kabupaten; ?>">0</div>
                <div class="stat-label">Kabupaten/Kota</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- STRUKTUR PENGURUS - TAMPILKAN SEMUA -->
<!-- ============================================ -->
<section class="pengurus-section">
    <div class="container">
        <div class="section-header-center">
            <h2 class="section-title">
                <i class="fas fa-users" style="color: #d4a847;"></i> Struktur Pengurus
            </h2>
            <p class="section-subtitle">Para pengurus PGNI Lampung yang berdedikasi untuk kemajuan pendidikan Al-Qur'an</p>
        </div>
        
        <?php if ($pengurus_list && mysqli_num_rows($pengurus_list) > 0): ?>
            <div class="pengurus-grid">
                <?php while ($pengurus = mysqli_fetch_assoc($pengurus_list)): ?>
                    <div class="pengurus-card">
                        <?php if (!empty($pengurus['foto'])): ?>
                            <img src="assets/images/pengurus/<?php echo htmlspecialchars($pengurus['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($pengurus['nama']); ?>"
                                 loading="lazy"
                                 onerror="this.src='assets/images/pengurus/default.jpg'">
                        <?php else: ?>
                            <div class="pengurus-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="pengurus-name"><?php echo htmlspecialchars($pengurus['nama']); ?></h4>
                        <p class="pengurus-jabatan"><?php echo htmlspecialchars($pengurus['jabatan']); ?></p>
                        <?php if (!empty($pengurus['instansi'])): ?>
                            <p class="pengurus-instansi"><?php echo htmlspecialchars($pengurus['instansi']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="pengurus-empty">
                <i class="fas fa-users"></i>
                <p>Belum ada data pengurus. Silakan tambahkan pengurus melalui panel admin.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================ -->
<!-- KONTAK -->
<!-- ============================================ -->
<section class="kontak-about-section">
    <div class="container">
        <div class="section-header-center">
            <h2 class="section-title">
                <i class="fas fa-phone-alt" style="color: #d4a847;"></i> Hubungi Kami
            </h2>
            <p class="section-subtitle">Silakan hubungi kami melalui kontak di bawah ini</p>
        </div>
        
        <div class="kontak-about-grid">
            <?php if (!empty($yayasan['alamat'])): ?>
                <div class="kontak-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <p><?php echo nl2br(htmlspecialchars($yayasan['alamat'])); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($yayasan['no_telp'])): ?>
                <div class="kontak-item">
                    <i class="fas fa-phone"></i>
                    <p><?php echo htmlspecialchars($yayasan['no_telp']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($yayasan['email'])): ?>
                <div class="kontak-item">
                    <i class="fas fa-envelope"></i>
                    <p>
                        <a href="mailto:<?php echo htmlspecialchars($yayasan['email']); ?>">
                            <?php echo htmlspecialchars($yayasan['email']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($yayasan['website'])): ?>
                <div class="kontak-item">
                    <i class="fas fa-globe"></i>
                    <p>
                        <a href="http://<?php echo htmlspecialchars($yayasan['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($yayasan['website']); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- STYLES -->
<!-- ============================================ -->
<style>
    /* ===== RESET & BASE ===== */
    * {
        direction: ltr !important;
        box-sizing: border-box;
    }
    
    body {
        direction: ltr;
        text-align: left;
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* ===== PAGE BANNER ===== */
    .page-banner {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        padding: 60px 0;
        color: #fff;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .page-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: rgba(212, 168, 71, 0.1);
        border-radius: 50%;
    }
    
    .banner-title {
        font-size: 2.2rem;
        margin-bottom: 8px;
        position: relative;
        z-index: 2;
    }
    
    .banner-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        position: relative;
        z-index: 2;
    }
    
    .banner-breadcrumb {
        margin-top: 12px;
        font-size: 0.85rem;
        opacity: 0.8;
        position: relative;
        z-index: 2;
    }
    
    .banner-breadcrumb a {
        color: #fff;
        text-decoration: none;
    }
    
    .banner-breadcrumb a:hover {
        text-decoration: underline;
    }
    
    /* ===== ABOUT SECTION ===== */
    .about-section {
        padding: 60px 0;
        background: #ffffff;
    }
    
    .about-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: center;
    }
    
    .about-image img {
        max-width: 50%;
        height: auto;
        border-radius: 12px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    
    .about-image-placeholder {
        background: #f0f7f3;
        padding: 60px 40px;
        border-radius: 12px;
        text-align: center;
        border: 2px dashed #d4a847;
    }
    
    .about-image-placeholder i {
        font-size: 4rem;
        color: #d4a847;
        display: block;
        margin-bottom: 10px;
    }
    
    .about-image-placeholder h3 {
        color: #1a1a2e;
    }
    
    .about-content {
        direction: ltr;
        text-align: left;
    }
    
    .about-title {
        font-size: 1.8rem;
        color: #1a1a2e;
        margin-bottom: 15px;
    }
    
    .highlight {
        color: #d4a847;
    }
    
    .about-description {
        color: #555;
        font-size: 1rem;
        line-height: 1.8;
        margin-bottom: 20px;
    }
    
    .about-established {
        display: inline-block;
        padding: 8px 20px;
        background: #f0f7f3;
        border-radius: 8px;
        color: #1a6e3a;
    }
    
    /* ===== LEGALITAS ABOUT ===== */
    .legalitas-about-section {
        padding: 50px 0;
        background: linear-gradient(135deg, #f8f9fa, #e8f0e8);
    }
    
    .legalitas-about-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: start;
    }
    
    .legalitas-about-title {
        font-size: 1.6rem;
        color: #1a1a2e;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .legalitas-about-text {
        color: #444;
        font-size: 0.98rem;
        line-height: 1.8;
        margin-bottom: 20px;
    }
    
    .legalitas-about-badge {
        display: inline-block;
        background: #1a6e3a;
        color: #fff;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .legalitas-about-right {
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border-left: 5px solid #d4a847;
    }
    
    .legalitas-about-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .legalitas-about-list li {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .legalitas-about-list li:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .legalitas-about-list li i {
        color: #1a6e3a;
        margin-top: 3px;
        width: 18px;
        flex-shrink: 0;
    }
    
    /* ===== VISI MISI ===== */
    .visi-misi-section {
        padding: 60px 0;
        background: #ffffff;
    }
    
    .visi-misi-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .visi-box {
        background: #f8f9fa;
        padding: 35px;
        border-radius: 16px;
        border-top: 4px solid #d4a847;
    }
    
    .visi-title {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .visi-content {
        color: #555;
        font-size: 1rem;
        line-height: 1.8;
        padding-left: 20px;
        border-left: 4px solid #d4a847;
    }
    
    .misi-box {
        background: #f8f9fa;
        padding: 35px;
        border-radius: 16px;
        border-top: 4px solid #1a6e3a;
    }
    
    .misi-title {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .misi-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .misi-list li {
        padding: 8px 0;
        padding-left: 30px;
        position: relative;
        color: #555;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .misi-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #d4a847;
        font-weight: 700;
    }
    
    /* ===== PROGRAM SECTION ===== */
    .program-section {
        padding: 60px 0;
        background: #f8f9fa;
    }
    
    .section-header-center {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .section-title {
        font-size: 1.8rem;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    
    .section-subtitle {
        color: #999;
        font-size: 0.95rem;
    }
    
    .program-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    
    .program-card {
        background: #fff;
        padding: 30px 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        border-bottom: 4px solid transparent;
    }
    
    .program-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-bottom-color: #d4a847;
    }
    
    .program-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.8rem;
        color: #fff;
    }
    
    .program-name {
        font-size: 1.1rem;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    
    .program-desc {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 10px;
    }
    
    /* ========================================== */
    /* PROGRAM DETAIL - EXPANDABLE */
    /* ========================================== */
    .program-detail-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
        padding: 6px 16px;
        background: #f0f7f3;
        border: 1px solid #d4a847;
        border-radius: 20px;
        color: #1a6e3a;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: inherit;
    }
    
    .program-detail-toggle:hover {
        background: #d4a847;
        color: #fff;
    }
    
    .program-detail-toggle.active {
        background: #d4a847;
        color: #fff;
    }
    
    .program-detail-toggle i {
        transition: transform 0.3s ease;
    }
    
    .program-detail-toggle.active i {
        transform: rotate(180deg);
    }
    
    .program-detail-content {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
        text-align: left;
        animation: fadeInDetail 0.3s ease;
    }
    
    @keyframes fadeInDetail {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .program-detail-content .detail-item {
        margin-bottom: 12px;
        font-size: 0.85rem;
        color: #555;
        line-height: 1.6;
    }
    
    .program-detail-content .detail-item:last-child {
        margin-bottom: 0;
    }
    
    .program-detail-content .detail-item strong {
        color: #1a1a2e;
        display: block;
        margin-bottom: 3px;
    }
    
    .program-detail-content .detail-item p {
        margin: 0 0 8px 0;
        color: #666;
    }
    
    .program-detail-content .detail-item ul {
        margin: 5px 0 0 0;
        padding-left: 20px;
        list-style-type: disc;
    }
    
    .program-detail-content .detail-item ul li {
        color: #666;
        font-size: 0.82rem;
        padding: 2px 0;
        line-height: 1.5;
    }
    
    /* ===== STATISTIK ABOUT ===== */
    .statistik-about-section {
        padding: 60px 0;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    
    .statistik-about-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
    }
    
    .stat-item {
        text-align: center;
        padding: 25px 20px;
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        background: rgba(255,255,255,0.2);
        transform: scale(1.02);
    }
    
    .stat-number {
        font-size: 2.8rem;
        font-weight: 700;
        color: #d4a847;
        line-height: 1.2;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-label {
        font-size: 0.95rem;
        opacity: 0.9;
        margin-top: 8px;
    }
    
    /* ===== PENGURUS SECTION ===== */
    .pengurus-section {
        padding: 60px 0;
        background: #ffffff;
    }
    
    .pengurus-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
    }
    
    .pengurus-card {
        background: #fff;
        padding: 25px 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    
    .pengurus-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-color: #d4a847;
    }
    
    .pengurus-card img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #f0f0f0;
        margin-bottom: 10px;
    }
    
    .pengurus-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 2rem;
        color: #fff;
    }
    
    .pengurus-name {
        font-size: 0.95rem;
        color: #1a1a2e;
        margin-bottom: 2px;
    }
    
    .pengurus-jabatan {
        color: #d4a847;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .pengurus-instansi {
        color: #999;
        font-size: 0.75rem;
        margin-top: 5px;
    }
    
    .pengurus-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 12px;
    }
    
    .pengurus-empty i {
        font-size: 2.5rem;
        color: #d4a847;
        display: block;
        margin-bottom: 10px;
    }
    
    .pengurus-empty p {
        color: #999;
    }
    
    /* ===== KONTAK ABOUT ===== */
    .kontak-about-section {
        padding: 60px 0;
        background: #f8f9fa;
    }
    
    .kontak-about-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
    }
    
    .kontak-item {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
    }
    
    .kontak-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .kontak-item i {
        font-size: 2rem;
        color: #d4a847;
        margin-bottom: 10px;
    }
    
    .kontak-item p {
        color: #555;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .kontak-item a {
        color: #1a6e3a;
        text-decoration: none;
    }
    
    .kontak-item a:hover {
        text-decoration: underline;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .about-wrapper {
            grid-template-columns: 1fr !important;
            gap: 30px;
        }
        
        .legalitas-about-wrapper {
            grid-template-columns: 1fr !important;
            gap: 25px;
        }
        
        .visi-misi-wrapper {
            grid-template-columns: 1fr !important;
            gap: 20px;
        }
        
        .statistik-about-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .page-banner {
            padding: 40px 0 !important;
        }
        
        .banner-title {
            font-size: 1.6rem !important;
        }
        
        .section-title {
            font-size: 1.5rem !important;
        }
        
        .about-title {
            font-size: 1.4rem !important;
        }
        
        .program-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 15px;
        }
        
        .pengurus-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)) !important;
        }
        
        .kontak-about-grid {
            grid-template-columns: 1fr 1fr !important;
        }
        
        .stat-number {
            font-size: 2.2rem !important;
            min-height: 50px;
        }
        
        .stat-label {
            font-size: 0.85rem !important;
        }
        
        .visi-box, .misi-box {
            padding: 25px !important;
        }
        
        /* Responsive program detail */
        .program-detail-content .detail-item {
            font-size: 0.8rem;
        }
        .program-detail-content .detail-item ul li {
            font-size: 0.78rem;
        }
        .program-detail-toggle {
            font-size: 0.7rem;
            padding: 5px 12px;
        }
    }
    
    @media (max-width: 480px) {
        .page-banner {
            padding: 30px 0 !important;
        }
        
        .banner-title {
            font-size: 1.3rem !important;
        }
        
        .banner-subtitle {
            font-size: 0.85rem !important;
        }
        
        .section-title {
            font-size: 1.2rem !important;
        }
        
        .program-grid {
            grid-template-columns: 1fr !important;
        }
        
        .pengurus-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 15px;
        }
        
        .kontak-about-grid {
            grid-template-columns: 1fr !important;
        }
        
        .statistik-about-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 12px;
        }
        
        .stat-item {
            padding: 18px 15px !important;
        }
        
        .stat-number {
            font-size: 1.8rem !important;
            min-height: 40px;
        }
        
        .stat-label {
            font-size: 0.75rem !important;
        }
        
        .about-image-placeholder {
            padding: 30px 20px !important;
        }
        
        .about-image-placeholder i {
            font-size: 3rem !important;
        }
        
        .legalitas-about-right {
            padding: 20px !important;
        }
        
        .legalitas-about-list li {
            font-size: 0.85rem !important;
        }
        
        .visi-box, .misi-box {
            padding: 20px !important;
        }
        
        .visi-content {
            padding-left: 15px !important;
            font-size: 0.9rem !important;
        }
        
        .misi-list li {
            font-size: 0.9rem !important;
            padding: 6px 0 !important;
            padding-left: 25px !important;
        }
        
        /* Responsive program detail mobile */
        .program-detail-content .detail-item {
            font-size: 0.75rem;
        }
        .program-detail-content .detail-item ul li {
            font-size: 0.72rem;
        }
        .program-detail-toggle {
            font-size: 0.65rem;
            padding: 4px 10px;
        }
    }
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
    // ==========================================
    // TOGGLE PROGRAM DETAIL
    // ==========================================
    function toggleProgramDetail(button) {
        const card = button.closest('.program-card');
        const content = card.querySelector('.program-detail-content');
        const icon = button.querySelector('i');
        
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-chevron-up"></i> Sembunyikan Detail';
        } else {
            content.style.display = 'none';
            button.classList.remove('active');
            button.innerHTML = '<i class="fas fa-chevron-down"></i> Lihat Detail';
        }
    }

    // ==========================================
    // ANIMASI COUNTER STATISTIK
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        function animateCounter(element) {
            const target = parseInt(element.getAttribute('data-count')) || 0;
            let current = 0;
            const increment = Math.ceil(target / 60);
            const duration = 1500;
            const stepTime = duration / 60;
            
            if (target === 0) {
                element.textContent = '0';
                return;
            }
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = current.toLocaleString('id-ID');
            }, stepTime);
        }
        
        // Gunakan Intersection Observer untuk trigger animasi
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        if (!el.dataset.animated) {
                            el.dataset.animated = 'true';
                            animateCounter(el);
                        }
                    }
                });
            }, { threshold: 0.5 });
            
            statNumbers.forEach(el => observer.observe(el));
        } else {
            // Fallback untuk browser lama
            statNumbers.forEach(el => animateCounter(el));
        }
    });
</script>

<?php include $root_path . '/include/footer.php'; ?>