<?php
// kontak.php - Halaman Kontak
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

$title = 'Kontak Kami - PGNI Lampung';
$success = '';
$error = '';

// ============================================
// CEK DAN BUAT TABEL KONTAK_MESSAGES
// ============================================
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'kontak_messages'");
$table_exists = mysqli_num_rows($check_table) > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE IF NOT EXISTS `kontak_messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nama` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `subjek` VARCHAR(255) NOT NULL,
        `pesan` TEXT NOT NULL,
        `status` ENUM('belum_dibaca', 'sudah_dibaca') DEFAULT 'belum_dibaca',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
}

// ============================================
// AMBIL DATA YAYASAN
// ============================================
$query = "SELECT * FROM yayasan WHERE id = 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    // Jika tabel yayasan belum ada, buat data default
    $yayasan = [
        'nama_yayasan' => 'PGNI Lampung',
        'alamat' => 'Jl. KH. Ahmad Dahlan No. 123, Bandar Lampung',
        'no_telp' => '0721-1234567',
        'email' => 'info@pgnilampung.or.id',
        'website' => 'www.pgnilampung.or.id',
        'logo' => '',
        'deskripsi' => 'Persatuan Guru Ngaji Indonesia (PGNI) Provinsi Lampung'
    ];
} else {
    $yayasan = mysqli_fetch_assoc($result);
    if (!$yayasan) {
        $insert_default = "INSERT INTO `yayasan` SET 
            `nama_yayasan` = 'PGNI Lampung',
            `alamat` = 'Jl. KH. Ahmad Dahlan No. 123, Bandar Lampung',
            `no_telp` = '0721-1234567',
            `email` = 'info@pgnilampung.or.id',
            `website` = 'www.pgnilampung.or.id',
            `status` = 'aktif'";
        mysqli_query($conn, $insert_default);
        
        $result = mysqli_query($conn, $query);
        $yayasan = mysqli_fetch_assoc($result);
    }
}

// ============================================
// PROSES FORM KONTAK
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $subjek = isset($_POST['subjek']) ? mysqli_real_escape_string($conn, trim($_POST['subjek'])) : '';
    $pesan = isset($_POST['pesan']) ? mysqli_real_escape_string($conn, trim($_POST['pesan'])) : '';
    
    // Validasi
    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Simpan ke database
        $insert_query = "INSERT INTO `kontak_messages` SET 
            `nama` = '$nama',
            `email` = '$email',
            `subjek` = '$subjek',
            `pesan` = '$pesan',
            `created_at` = NOW()";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = 'Pesan Anda berhasil dikirim! Kami akan segera merespon.';
            
            // Kirim email notifikasi (opsional)
            $to = $yayasan['email'] ?? 'info@pgnilampung.or.id';
            $subject = "Kontak dari Website: $subjek";
            $message = "Nama: $nama\n";
            $message .= "Email: $email\n";
            $message .= "Subjek: $subjek\n\n";
            $message .= "Pesan:\n$pesan";
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            
            @mail($to, $subject, $message, $headers);
            
        } else {
            $error = 'Maaf, terjadi kesalahan saat menyimpan pesan. Silakan coba lagi.';
        }
    }
}

include $root_path . '/include/header.php';
?>

<!-- ============================================ -->
<!-- PAGE BANNER -->
<!-- ============================================ -->
<div class="page-banner" style="background: linear-gradient(135deg, #1a6e3a, #2d8f52); padding: 50px 0; color: #fff; text-align: center; direction: ltr;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;">📞 Kontak Kami</h1>
        <p style="font-size: 1rem; opacity: 0.9;">Hubungi kami untuk informasi lebih lanjut</p>
    </div>
</div>

<!-- ============================================ -->
<!-- KONTAK SECTION -->
<!-- ============================================ -->
<section class="kontak-section" style="padding: 50px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; direction: ltr;">
            
            <!-- ========================================== -->
            <!-- KOLOM KIRI - FORM KONTAK -->
            <!-- ========================================== -->
            <div style="direction: ltr; text-align: left;">
                <h2 style="font-size: 1.5rem; color: #1a1a2e; margin-bottom: 5px; text-align: left;">Kirim Pesan</h2>
                <p style="color: #999; font-size: 0.95rem; margin-bottom: 25px; text-align: left;">Isi form di bawah untuk menghubungi kami</p>
                
                <!-- Alert -->
                <?php if ($success): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-left: 4px solid #28a745; direction: ltr; text-align: left;">
                        <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.2rem;"></i> 
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-left: 4px solid #dc3545; direction: ltr; text-align: left;">
                        <i class="fas fa-exclamation-circle" style="color: #dc3545; font-size: 1.2rem;"></i> 
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" id="formKontak" style="direction: ltr; text-align: left;">
                    <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                        <label for="nama" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.9rem; text-align: left;">
                            Nama Lengkap <span style="color: #e74c3c;">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nama" name="nama" 
                                   placeholder="Masukkan nama lengkap" required
                                   value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                                   style="direction: ltr; text-align: left;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                        <label for="email" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.9rem; text-align: left;">
                            Email <span style="color: #e74c3c;">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Masukkan email aktif" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   style="direction: ltr; text-align: left;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                        <label for="subjek" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.9rem; text-align: left;">
                            Subjek <span style="color: #e74c3c;">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-tag"></i></span>
                            <input type="text" class="form-control" id="subjek" name="subjek" 
                                   placeholder="Masukkan subjek pesan" required
                                   value="<?php echo isset($_POST['subjek']) ? htmlspecialchars($_POST['subjek']) : ''; ?>"
                                   style="direction: ltr; text-align: left;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                        <label for="pesan" style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.9rem; text-align: left;">
                            Pesan <span style="color: #e74c3c;">*</span>
                        </label>
                        <div class="input-group" style="align-items: flex-start; text-align: left;">
                            <span class="input-icon" style="top: 14px;"><i class="fas fa-comment"></i></span>
                            <textarea class="form-control" id="pesan" name="pesan" rows="5" 
                                      placeholder="Tulis pesan Anda di sini..." required style="padding-left: 45px; min-height: 120px; direction: ltr; text-align: left;"><?php echo isset($_POST['pesan']) ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #1a6e3a, #2d8f52); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                    </button>
                </form>
            </div>
            
            <!-- ========================================== -->
            <!-- KOLOM KANAN - INFORMASI KONTAK -->
            <!-- ========================================== -->
            <div style="direction: ltr; text-align: left;">
                <h2 style="font-size: 1.5rem; color: #1a1a2e; margin-bottom: 5px; text-align: left;">Informasi Kontak</h2>
                <p style="color: #999; font-size: 0.95rem; margin-bottom: 25px; text-align: left;">Hubungi kami melalui berbagai channel</p>
                
                <div style="display: grid; gap: 20px; text-align: left;">
                    <!-- Alamat -->
                    <?php if (!empty($yayasan['alamat'])): ?>
                        <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #d4a847; text-align: left;">
                            <div style="width: 45px; height: 45px; background: rgba(212, 168, 71, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt" style="color: #d4a847; font-size: 1.2rem;"></i>
                            </div>
                            <div style="text-align: left;">
                                <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">Alamat</h4>
                                <p style="color: #555; font-size: 0.9rem; line-height: 1.6; margin: 0; text-align: left;">
                                    <?php echo nl2br(htmlspecialchars($yayasan['alamat'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Telepon -->
                    <?php if (!empty($yayasan['no_telp'])): ?>
                        <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #25d366; text-align: left;">
                            <div style="width: 45px; height: 45px; background: rgba(37, 211, 102, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-phone" style="color: #25d366; font-size: 1.2rem;"></i>
                            </div>
                            <div style="text-align: left;">
                                <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">Telepon</h4>
                                <p style="color: #555; font-size: 0.9rem; margin: 0; text-align: left;">
                                    <a href="tel:<?php echo htmlspecialchars($yayasan['no_telp']); ?>" style="color: #1a6e3a; text-decoration: none;">
                                        <?php echo htmlspecialchars($yayasan['no_telp']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- WhatsApp -->
                    <?php if (!empty($yayasan['no_telp'])): ?>
                        <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #25d366; text-align: left;">
                            <div style="width: 45px; height: 45px; background: rgba(37, 211, 102, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fab fa-whatsapp" style="color: #25d366; font-size: 1.5rem;"></i>
                            </div>
                            <div style="text-align: left;">
                                <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">WhatsApp</h4>
                                <p style="color: #555; font-size: 0.9rem; margin: 0; text-align: left;">
                                    <a href="https://wa.me/62<?php echo preg_replace('/[^0-9]/', '', $yayasan['no_telp']); ?>" target="_blank" style="color: #25d366; text-decoration: none; font-weight: 500;">
                                        <i class="fab fa-whatsapp"></i> Chat via WhatsApp
                                    </a>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Email -->
                    <?php if (!empty($yayasan['email'])): ?>
                        <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #3498db; text-align: left;">
                            <div style="width: 45px; height: 45px; background: rgba(52, 152, 219, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-envelope" style="color: #3498db; font-size: 1.2rem;"></i>
                            </div>
                            <div style="text-align: left;">
                                <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">Email</h4>
                                <p style="color: #555; font-size: 0.9rem; margin: 0; text-align: left;">
                                    <a href="mailto:<?php echo htmlspecialchars($yayasan['email']); ?>" style="color: #1a6e3a; text-decoration: none;">
                                        <?php echo htmlspecialchars($yayasan['email']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Website -->
                    <?php if (!empty($yayasan['website'])): ?>
                        <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #9b59b6; text-align: left;">
                            <div style="width: 45px; height: 45px; background: rgba(155, 89, 182, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-globe" style="color: #9b59b6; font-size: 1.2rem;"></i>
                            </div>
                            <div style="text-align: left;">
                                <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">Website</h4>
                                <p style="color: #555; font-size: 0.9rem; margin: 0; text-align: left;">
                                    <a href="http://<?php echo htmlspecialchars($yayasan['website']); ?>" target="_blank" style="color: #1a6e3a; text-decoration: none;">
                                        <?php echo htmlspecialchars($yayasan['website']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Sosial Media -->
                    <div class="info-card" style="display: flex; gap: 15px; align-items: flex-start; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s ease; border-left: 4px solid #1877f2; text-align: left;">
                        <div style="width: 45px; height: 45px; background: rgba(24, 119, 242, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-share-alt" style="color: #1877f2; font-size: 1.2rem;"></i>
                        </div>
                        <div style="text-align: left;">
                            <h4 style="font-size: 0.9rem; color: #1a1a2e; margin-bottom: 3px; text-align: left;">Ikuti Kami</h4>
                            <div style="display: flex; gap: 15px; margin-top: 5px; text-align: left;">
                                <a href="#" style="color: #1877f2; font-size: 1.4rem; transition: all 0.3s ease;" class="social-link">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <a href="#" style="color: #1da1f2; font-size: 1.4rem; transition: all 0.3s ease;" class="social-link">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" style="color: #e4405f; font-size: 1.4rem; transition: all 0.3s ease;" class="social-link">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" style="color: #ff0000; font-size: 1.4rem; transition: all 0.3s ease;" class="social-link">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- MAPS / LOKASI -->
<!-- ============================================ -->
<?php if (!empty($yayasan['alamat'])): ?>
<section class="map-section" style="padding: 0 0 50px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h3 style="font-size: 1.2rem; color: #1a1a2e; text-align: center; margin-bottom: 20px;">
            <i class="fas fa-map" style="color: #d4a847;"></i> Lokasi Kami
        </h3>
        <div style="background: #f0f0f0; border-radius: 12px; overflow: hidden; height: 300px; position: relative;">
            <!-- Google Maps Embed -->
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.876543210987!2d105.2667!3d-5.45!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMjcnMDAuMCJTIDEwNcKwMTYnMDAuMCJF!5e0!3m2!1sid!2sid!4v1234567890" 
                width="100%" 
                height="300" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.95); padding: 15px 25px; border-radius: 8px; text-align: center; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                <i class="fas fa-map-marker-alt" style="color: #e74c3c; font-size: 1.5rem;"></i>
                <p style="margin: 5px 0 0 0; color: #333; font-size: 0.85rem; font-weight: 500;">
                    <?php echo htmlspecialchars($yayasan['nama_yayasan'] ?? 'PGNI Lampung'); ?>
                </p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================ -->
<!-- STYLE -->
<!-- ============================================ -->
<style>
    * {
        direction: ltr !important;
    }
    
    body {
        direction: ltr;
        text-align: left;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px 12px 45px;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        font-size: 0.95rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #fff;
        color: #333;
        box-sizing: border-box;
        direction: ltr;
        text-align: left;
    }
    .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    .form-control::placeholder {
        color: #bbb;
    }
    textarea.form-control {
        padding-left: 45px;
        resize: vertical;
        font-family: inherit;
        min-height: 120px;
        direction: ltr;
        text-align: left;
    }
    
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-group .input-icon {
        position: absolute;
        left: 14px;
        color: #999;
        font-size: 1rem;
        z-index: 1;
        pointer-events: none;
    }
    .input-group .form-control {
        padding-left: 45px;
        width: 100%;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 25px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
    }
    .btn-primary:active {
        transform: translateY(0);
    }
    
    .info-card {
        transition: all 0.3s ease;
    }
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    .social-link {
        display: inline-block;
        transition: all 0.3s ease;
    }
    .social-link:hover {
        transform: scale(1.2);
    }
    
    /* Alert */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    @media (max-width: 992px) {
        .kontak-section .container > div:first-child {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 768px) {
        .page-banner {
            padding: 30px 0 !important;
        }
        .page-banner h1 {
            font-size: 1.5rem !important;
        }
        .kontak-section .container > div:first-child > div:first-child {
            order: 2;
        }
        .kontak-section .container > div:first-child > div:last-child {
            order: 1;
        }
        .map-section .container > div {
            height: 200px !important;
        }
        .map-section .container > div iframe {
            height: 200px !important;
        }
    }
    
    @media (max-width: 480px) {
        .page-banner h1 {
            font-size: 1.2rem !important;
        }
        .form-control {
            font-size: 0.85rem;
            padding: 10px 12px 10px 40px;
        }
        .input-group .input-icon {
            font-size: 0.85rem;
            left: 12px;
        }
        textarea.form-control {
            padding-left: 40px;
            min-height: 100px;
        }
        .btn {
            font-size: 0.9rem;
            padding: 10px 20px;
        }
        .info-card {
            padding: 15px !important;
        }
        .info-card .social-link {
            font-size: 1.2rem !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // FORM VALIDASI
    // ==========================================
    const form = document.getElementById('formKontak');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nama = document.getElementById('nama').value.trim();
            const email = document.getElementById('email').value.trim();
            const subjek = document.getElementById('subjek').value.trim();
            const pesan = document.getElementById('pesan').value.trim();
            
            let errors = [];
            
            if (!nama) {
                errors.push('Nama lengkap wajib diisi');
                document.getElementById('nama').style.borderColor = '#dc3545';
            } else {
                document.getElementById('nama').style.borderColor = '#e8e8e8';
            }
            
            if (!email) {
                errors.push('Email wajib diisi');
                document.getElementById('email').style.borderColor = '#dc3545';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Format email tidak valid');
                document.getElementById('email').style.borderColor = '#dc3545';
            } else {
                document.getElementById('email').style.borderColor = '#e8e8e8';
            }
            
            if (!subjek) {
                errors.push('Subjek wajib diisi');
                document.getElementById('subjek').style.borderColor = '#dc3545';
            } else {
                document.getElementById('subjek').style.borderColor = '#e8e8e8';
            }
            
            if (!pesan) {
                errors.push('Pesan wajib diisi');
                document.getElementById('pesan').style.borderColor = '#dc3545';
            } else {
                document.getElementById('pesan').style.borderColor = '#e8e8e8';
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('❌ ' + errors.join('\n'));
                return false;
            }
        });
        
        // Reset border on focus
        document.querySelectorAll('#formKontak .form-control').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#1a6e3a';
            });
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#e8e8e8';
                }
            });
        });
    }
    
    // ==========================================
    // AUTO CLOSE ALERT
    // ==========================================
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

<?php include $root_path . '/include/footer.php'; ?>