<?php
// admin/guru_verifikasi.php - Verifikasi Guru dengan Hak Akses + WA Me
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

// ============================================
// CEK ROLE & AKSES WILAYAH
// ============================================
$user_role = $_SESSION['role'] ?? 'admin';
$user_id = $_SESSION['user_id'];
$user_kecamatan_id = $_SESSION['kecamatan_id'] ?? 0;

// ============================================
// DAFTAR ROLE YANG BOLEH VERIFIKASI
// ============================================
$can_verify = in_array($user_role, ['admin', 'super_admin', 'petugas_kecamatan']);

if (!$can_verify) {
    header('Location: login.php');
    exit;
}

// ============================================
// AMBIL AKSES WILAYAH USER
// ============================================
$user_wilayah = [
    'akses_semua' => 0,
    'kabupaten' => [],
    'kecamatan' => [],
    'desa' => []
];

// SUPER ADMIN: FULL ACCESS
if ($user_role === 'super_admin') {
    $user_wilayah['akses_semua'] = 1;
} 
// ADMIN: Ambil akses dari database
elseif ($user_role === 'admin') {
    $query_wilayah = "SELECT * FROM user_wilayah_akses WHERE user_id = $user_id";
    $result_wilayah = mysqli_query($conn, $query_wilayah);
    if ($result_wilayah) {
        while ($row = mysqli_fetch_assoc($result_wilayah)) {
            if ($row['akses_semua'] == 1) {
                $user_wilayah['akses_semua'] = 1;
            }
            if ($row['kabupaten_id']) {
                $user_wilayah['kabupaten'][] = $row['kabupaten_id'];
            }
            if ($row['kecamatan_id']) {
                $user_wilayah['kecamatan'][] = $row['kecamatan_id'];
            }
            if ($row['desa_id']) {
                $user_wilayah['desa'][] = $row['desa_id'];
            }
        }
    }
}
// PETUGAS KECAMATAN: Hanya kecamatan sendiri
elseif ($user_role === 'petugas_kecamatan') {
    if ($user_kecamatan_id > 0) {
        $user_wilayah['kecamatan'][] = $user_kecamatan_id;
    }
}

// ============================================
// FUNGSI FORMAT NOMOR WHATSAPP
// ============================================
function formatWA($no_telp) {
    // Hapus karakter non-digit
    $no = preg_replace('/[^0-9]/', '', $no_telp);
    
    // Jika dimulai dengan 0, ganti dengan 62
    if (substr($no, 0, 1) === '0') {
        $no = '62' . substr($no, 1);
    }
    
    // Jika dimulai dengan 8, tambahkan 62
    if (substr($no, 0, 1) === '8' && strlen($no) >= 10) {
        $no = '62' . $no;
    }
    
    return $no;
}

// ============================================
// CEK AKSES TERHADAP GURU
// ============================================
function checkGuruAccess($guru_id, $user_wilayah, $conn) {
    $query = "SELECT kabupaten_id, kecamatan_id, desa_id FROM guru_ngaji WHERE id = $guru_id";
    $result = mysqli_query($conn, $query);
    $guru = mysqli_fetch_assoc($result);
    
    if (!$guru) {
        return false;
    }
    
    // Super Admin: akses semua
    if ($user_wilayah['akses_semua'] == 1) {
        return true;
    }
    
    // Cek akses berdasarkan wilayah guru
    if (!empty($user_wilayah['kecamatan']) && in_array($guru['kecamatan_id'], $user_wilayah['kecamatan'])) {
        return true;
    }
    
    if (!empty($user_wilayah['kabupaten']) && in_array($guru['kabupaten_id'], $user_wilayah['kabupaten'])) {
        return true;
    }
    
    if (!empty($user_wilayah['desa']) && in_array($guru['desa_id'], $user_wilayah['desa'])) {
        return true;
    }
    
    return false;
}

// ============================================
// BUILD FILTER WILAYAH UNTUK DAFTAR PENDING
// ============================================
function buildVerifikasiFilter($user_wilayah) {
    if ($user_wilayah['akses_semua'] == 1) {
        return '';
    }
    
    $conditions = [];
    
    if (!empty($user_wilayah['kecamatan'])) {
        $kec_ids = implode(',', array_map('intval', $user_wilayah['kecamatan']));
        $conditions[] = "g.kecamatan_id IN ($kec_ids)";
    }
    
    if (!empty($user_wilayah['kabupaten'])) {
        $kab_ids = implode(',', array_map('intval', $user_wilayah['kabupaten']));
        $conditions[] = "g.kabupaten_id IN ($kab_ids)";
    }
    
    if (!empty($user_wilayah['desa'])) {
        $desa_ids = implode(',', array_map('intval', $user_wilayah['desa']));
        $conditions[] = "g.desa_id IN ($desa_ids)";
    }
    
    if (!empty($conditions)) {
        return 'AND (' . implode(' OR ', $conditions) . ')';
    }
    
    return 'AND 1=0';
}

$redirect_after = 'guru.php';

// ============================================
// PROSES VERIFIKASI
// ============================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

if ($id > 0 && in_array($status, ['disetujui', 'ditolak'])) {
    
    // CEK AKSES TERHADAP GURU
    if (!checkGuruAccess($id, $user_wilayah, $conn)) {
        header('Location: ' . $redirect_after . '?error=akses_ditolak');
        exit;
    }
    
    $query_guru = "SELECT * FROM guru_ngaji WHERE id = $id";
    $result_guru = mysqli_query($conn, $query_guru);
    $guru_data = mysqli_fetch_assoc($result_guru);
    
    if (!$guru_data) {
        header('Location: ' . $redirect_after . '?error=notfound');
        exit;
    }
    
    // KONFIRMASI
    if ($confirm === 'yes') {
        $query = "UPDATE guru_ngaji SET status_verifikasi = '$status', updated_at = NOW() WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            // Log aktivitas dengan IP dan User Agent
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // CEK APAKAH TABEL log_aktivitas ADA
            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'log_aktivitas'");
            if (mysqli_num_rows($check_table) > 0) {
                $log_query = "INSERT INTO log_aktivitas 
                              (user_id, aktivitas, tabel, record_id, ip_address, user_agent, created_at) 
                              VALUES (
                                  $user_id, 
                                  'Verifikasi guru $status', 
                                  'guru_ngaji', 
                                  $id, 
                                  '$ip_address', 
                                  '" . mysqli_real_escape_string($conn, $user_agent) . "', 
                                  NOW()
                              )";
                mysqli_query($conn, $log_query);
            }
            
            sendVerificationWhatsApp($guru_data, $status);
            header('Location: ' . $redirect_after . '?msg=verifikasi');
            exit;
        } else {
            header('Location: ' . $redirect_after . '?error=verifikasi');
            exit;
        }
    }
    
    // TAMPILAN KONFIRMASI
    $title = 'Konfirmasi Verifikasi';
    include 'include/admin_header.php';
    ?>
    
    <div class="verification-confirm-wrapper">
        <div class="confirm-card">
            <div class="confirm-icon">
                <i class="fas fa-<?php echo $status === 'disetujui' ? 'check-circle' : 'times-circle'; ?>"></i>
            </div>
            <h2>Konfirmasi Verifikasi</h2>
            <p class="confirm-text">
                Anda akan <strong><?php echo $status === 'disetujui' ? 'MENYETUJUI' : 'MENOLAK'; ?></strong> 
                verifikasi data guru:
            </p>
            
            <div class="guru-summary">
                <div class="summary-item">
                    <span class="label">Nama</span>
                    <span class="value"><?php echo htmlspecialchars($guru_data['nama']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">NIK</span>
                    <span class="value"><?php echo htmlspecialchars($guru_data['nik']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Tempat Mengajar</span>
                    <span class="value"><?php echo htmlspecialchars($guru_data['tempat_mengajar']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">No. Telepon</span>
                    <span class="value"><?php echo htmlspecialchars($guru_data['no_telp'] ?: '-'); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Status</span>
                    <span class="value status-preview <?php echo $status; ?>">
                        <?php echo $status === 'disetujui' ? '✅ Disetujui' : '❌ Ditolak'; ?>
                    </span>
                </div>
            </div>
            
            <div class="whatsapp-notice">
                <i class="fab fa-whatsapp"></i>
                <p>Notifikasi akan dikirim ke WhatsApp guru setelah verifikasi</p>
            </div>
            
            <div class="confirm-actions">
                <a href="<?php echo $redirect_after; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <a href="guru_verifikasi.php?id=<?php echo $id; ?>&status=<?php echo $status; ?>&confirm=yes" 
                   class="btn btn-<?php echo $status === 'disetujui' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $status === 'disetujui' ? 'check' : 'times'; ?>"></i> 
                    <?php echo $status === 'disetujui' ? 'Setujui & Kirim WA' : 'Tolak & Kirim WA'; ?>
                </a>
            </div>
        </div>
    </div>
    
    <style>
        .verification-confirm-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 70vh;
            padding: 20px;
        }
        .confirm-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            text-align: center;
            border: 1px solid #f0f2f5;
        }
        .confirm-icon { font-size: 4rem; margin-bottom: 15px; }
        .confirm-icon .fa-check-circle { color: #2ecc71; }
        .confirm-icon .fa-times-circle { color: #e74c3c; }
        .confirm-card h2 { font-size: 1.5rem; color: #2c3e50; margin: 0 0 10px 0; }
        .confirm-text { color: #7f8c8d; margin-bottom: 25px; font-size: 0.95rem; }
        .confirm-text strong { color: #2c3e50; }
        .guru-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eef2f5;
        }
        .summary-item:last-child { border-bottom: none; }
        .summary-item .label { color: #7f8c8d; font-size: 0.85rem; }
        .summary-item .value {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        .status-preview {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-preview.disetujui { background: #d4edda; color: #155724; }
        .status-preview.ditolak { background: #f8d7da; color: #721c24; }
        .whatsapp-notice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            background: #e8f5e9;
            border-radius: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .whatsapp-notice i { color: #25D366; font-size: 1.5rem; }
        .whatsapp-notice p { margin: 0; color: #2e7d32; font-size: 0.9rem; font-weight: 500; }
        .confirm-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .confirm-actions .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-secondary { background: #eef2f5; color: #495057; }
        .btn-secondary:hover { background: #e2e6ea; }
        .btn-success { background: #2ecc71; color: #fff; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        
        @media (max-width: 600px) {
            .verification-confirm-wrapper { padding: 10px; min-height: auto; margin: 20px 0; }
            .confirm-card { padding: 20px 16px; border-radius: 16px; }
            .confirm-icon { font-size: 3rem; margin-bottom: 10px; }
            .confirm-card h2 { font-size: 1.2rem; }
            .confirm-text { font-size: 0.85rem; margin-bottom: 18px; }
            .guru-summary { padding: 14px; margin-bottom: 15px; }
            .summary-item { flex-direction: column; align-items: flex-start; padding: 6px 0; gap: 2px; }
            .summary-item .label { font-size: 0.75rem; }
            .summary-item .value { font-size: 0.85rem; text-align: left; max-width: 100%; width: 100%; }
            .whatsapp-notice { padding: 10px 14px; flex-direction: column; text-align: center; gap: 5px; }
            .whatsapp-notice i { font-size: 1.8rem; }
            .whatsapp-notice p { font-size: 0.8rem; }
            .confirm-actions { flex-direction: column; gap: 8px; }
            .confirm-actions .btn { width: 100%; padding: 12px 16px; font-size: 0.85rem; justify-content: center; }
        }
        @media (max-width: 400px) {
            .confirm-card { padding: 16px 12px; }
            .confirm-icon { font-size: 2.5rem; }
            .confirm-card h2 { font-size: 1rem; }
        }
    </style>
    
    <?php
    include 'include/admin_footer.php';
    exit;
}

// ============================================
// HALAMAN DETAIL VERIFIKASI DENGAN WA ME
// ============================================
if ($id > 0) {
    // CEK AKSES TERHADAP GURU
    if (!checkGuruAccess($id, $user_wilayah, $conn)) {
        header('Location: ' . $redirect_after . '?error=akses_ditolak');
        exit;
    }
    
    $query = "SELECT g.*, kab.nama as kabupaten_nama, k.nama as kecamatan_nama, d.nama as desa_nama 
              FROM guru_ngaji g 
              LEFT JOIN kabupaten kab ON g.kabupaten_id = kab.id
              LEFT JOIN kecamatan k ON g.kecamatan_id = k.id 
              LEFT JOIN desa d ON g.desa_id = d.id 
              WHERE g.id = $id";
    $result = mysqli_query($conn, $query);
    $guru = mysqli_fetch_assoc($result);
    
    if (!$guru) {
        header('Location: ' . $redirect_after . '?error=notfound');
        exit;
    }
    
    // ============================================
    // FORMAT NOMOR WHATSAPP & PESAN
    // ============================================
    $wa_number = formatWA($guru['no_telp']);

    // Pesan Default
    $wa_message = urlencode("Assalamu'alaikum, Yth. Bapak/Ibu " . $guru['nama'] . "\n\n" .
                           "Kami dari PGNI Lampung ingin menginformasikan bahwa proses verifikasi data guru ngaji Anda sedang kami proses.\n\n" .
                           "Mohon untuk segera melengkapi data yang diperlukan.\n\n" .
                           "Terima kasih.\n\n" .
                           "*PGNI Lampung*");

    // Pesan Minta Perbaikan Data (KTP/KK Tidak Jelas)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $edit_link = $protocol . "://" . $host . "/pgnil/cek_status.php?nik=" . $guru['nik'] . "&no_telp=" . urlencode($guru['no_telp']);

    $wa_fix_message = urlencode("Assalamu'alaikum, Yth. Bapak/Ibu " . $guru['nama'] . "\n\n" .
                                "Kami dari PGNI Lampung menginformasikan bahwa berkas dokumen (KTP/KK) yang Anda unggah *kurang jelas atau tidak terbaca*.\n\n" .
                                "Mohon untuk mengunggah ulang foto dokumen yang lebih jelas melalui link berikut:\n" .
                                $edit_link . "\n\n" .
                                "Silakan klik tombol *'Perbarui Data'* pada halaman tersebut untuk upload ulang.\n\n" .
                                "Terima kasih.\n\n" .
                                "*PGNI Lampung*");

    $title = 'Verifikasi Guru Ngaji';
    include 'include/admin_header.php';
    ?>
    
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="fas fa-check-double"></i> Verifikasi Guru Ngaji</h2>
            <p class="text-muted">Periksa dan verifikasi data guru ngaji</p>
            <?php if ($user_role === 'super_admin'): ?>
                <span class="badge-super">👑 Super Admin - Akses Penuh</span>
            <?php elseif ($user_role === 'admin'): ?>
                <span class="badge-admin">🔑 Admin - <?php echo $user_wilayah['akses_semua'] ? 'Akses Semua' : 'Akses Terbatas'; ?></span>
            <?php elseif ($user_role === 'petugas_kecamatan'): ?>
                <span class="badge-petugas">📍 Petugas Kecamatan</span>
            <?php endif; ?>
        </div>
        <div class="page-header-right">
            <a href="guru_verifikasi.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
    
    <div class="verification-wrapper">
        <div class="verification-card">
            <!-- Profile Header -->
            <div class="guru-profile">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if ($guru['ktp_file']): ?>
                            <img src="<?php echo BASE_URL; ?>assets/images/ktp/<?php echo $guru['ktp_file']; ?>" alt="Foto KTP" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.jpg'">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($guru['nama']); ?></h3>
                        <p><i class="fas fa-id-card"></i> NIK: <?php echo htmlspecialchars($guru['nik']); ?></p>
                        <p>
                            <i class="fas fa-phone"></i> 
                            <?php echo htmlspecialchars($guru['no_telp'] ?: '-'); ?>
                            <?php if ($guru['no_telp']): ?>
                                <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" class="btn-wa-sm" title="Chat via WhatsApp">
                                    <i class="fab fa-whatsapp"></i> WA Me
                                </a>
                            <?php endif; ?>
                        </p>
                        <p><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($guru['jenis_profesi'] ?: '-'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Detail Data -->
            <div class="guru-details">
                <div class="detail-section">
                    <h4><i class="fas fa-school"></i> Data Mengajar</h4>
                    <div class="detail-row">
                        <span class="detail-label">Tempat Mengajar</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['tempat_mengajar']); ?></span>
                    </div>
                    <?php if ($guru['tempat_mengajar_detail']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Detail Tempat</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['tempat_mengajar_detail']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($guru['bank'] || $guru['no_rekening']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Rekening</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['bank'] ?: '-'); ?> – <?php echo htmlspecialchars($guru['no_rekening'] ?: '-'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Alamat</h4>
                    <div class="detail-row">
                        <span class="detail-label">Kabupaten</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['kabupaten_nama'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kecamatan</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['kecamatan_nama'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Desa</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guru['desa_nama'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-file-alt"></i> Dokumen</h4>
                    <div class="detail-row">
                        <span class="detail-label">Foto KTP</span>
                        <span class="detail-value">
                            <?php if ($guru['ktp_file']): ?>
                                <a href="/pgnil/assets/images/ktp/<?php echo $guru['ktp_file']; ?>" target="_blank" class="btn btn-sm btn-info-outline">
                                    <i class="fas fa-eye"></i> Lihat KTP
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Foto KK</span>
                        <span class="detail-value">
                            <?php if ($guru['kk_file']): ?>
                                <a href="/pgnil/assets/images/kk/<?php echo $guru['kk_file']; ?>" target="_blank" class="btn btn-sm btn-info-outline">
                                    <i class="fas fa-eye"></i> Lihat KK
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="verification-actions">
                <div class="current-status">
                    <span class="status-label">Status:</span>
                    <span class="status-badge <?php echo $guru['status_verifikasi']; ?>">
                        <?php 
                            switch($guru['status_verifikasi']) {
                                case 'pending': echo '⏳ Menunggu'; break;
                                case 'disetujui': echo '✅ Disetujui'; break;
                                case 'ditolak': echo '❌ Ditolak'; break;
                                default: echo $guru['status_verifikasi'];
                            }
                        ?>
                    </span>
                </div>
                <div class="action-buttons">
                    <!-- TOMBOL WA ME & MINTA PERBAIKAN -->
                    <?php if ($guru['no_telp']): ?>
                        <a href="https://wa.me/<?php echo $wa_number; ?>?text=<?php echo $wa_message; ?>"
                           target="_blank"
                           class="btn btn-wa btn-lg"
                           title="Hubungi via WhatsApp">
                            <i class="fab fa-whatsapp"></i> WA Me
                        </a>
                        <a href="https://wa.me/<?php echo $wa_number; ?>?text=<?php echo $wa_fix_message; ?>"
                           target="_blank" 
                           class="btn btn-warning btn-lg"
                           style="background: #f39c12; color: #fff;"
                           title="Minta Perbaikan Berkas via WhatsApp">
                            <i class="fas fa-exclamation-triangle"></i> Minta Perbaikan (WA)
                        </a>
                    <?php endif; ?>

                    <a href="guru_verifikasi.php?id=<?php echo $id; ?>&status=disetujui" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Setujui
                    </a>
                    <a href="guru_verifikasi.php?id=<?php echo $id; ?>&status=ditolak" class="btn btn-danger btn-lg">
                        <i class="fas fa-times-circle"></i> Tolak
                    </a>
                    <a href="guru_edit.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* ============================================
           STYLE DETAIL VERIFIKASI + WA ME
        ============================================ */
        
        /* Tombol WhatsApp */
        .btn-wa {
            background: #25D366;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }
        .btn-wa:hover {
            background: #1DA851;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }
        
        .btn-wa-sm {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #25D366;
            color: #fff !important;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-left: 8px;
        }
        .btn-wa-sm:hover {
            background: #1DA851;
            transform: scale(1.05);
            color: #fff !important;
        }
        .btn-wa-sm i {
            font-size: 0.8rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eef2f5;
        }
        .page-header-left h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 5px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header-left h2 i { color: #27ae60; }
        .page-header-left .text-muted { color: #7f8c8d; font-size: 0.85rem; margin: 0; }
        
        .badge-super { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
        .badge-admin { background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
        .badge-petugas { background: #cce5ff; color: #004085; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
        
        .verification-wrapper { max-width: 1000px; margin: 0 auto; padding: 5px 0; }
        .verification-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #f0f2f5;
        }
        
        .guru-profile {
            padding: 30px;
            background: linear-gradient(135deg, #1e7e34, #2d8f52);
            color: #fff;
            position: relative;
        }
        .profile-header {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.25);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: rgba(255,255,255,0.6);
        }
        .profile-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }
        .profile-info p {
            margin: 5px 0;
            opacity: 0.9;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .profile-info p i { width: 18px; text-align: center; opacity: 0.85; }
        
        .guru-details {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .detail-section {
            background: #fafbfc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #f1f3f5;
        }
        .detail-section h4 {
            font-size: 0.95rem;
            color: #2c3e50;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        .detail-section h4 i { color: #27ae60; }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f5;
            font-size: 0.88rem;
            gap: 15px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #7f8c8d; font-weight: 500; flex-shrink: 0; }
        .detail-value { color: #2c3e50; font-weight: 600; text-align: right; word-break: break-word; }
        
        .verification-actions {
            padding: 25px 30px;
            border-top: 1px solid #eef2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            background: #fafbfc;
        }
        .current-status {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .current-status .status-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-badge.disetujui { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-badge.ditolak { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            font-family: inherit;
            border: none;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-success { background: #2ecc71; color: #fff; box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2); }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; color: #fff; box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2); }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: #f39c12; color: #fff; box-shadow: 0 4px 12px rgba(243, 156, 18, 0.2); }
        .btn-warning:hover { background: #d35400; }
        .btn-secondary { background: #95a5a6; color: #fff; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-info-outline {
            background: transparent;
            color: #2980b9;
            border: 1px solid #3498db;
            padding: 5px 12px;
            font-size: 0.78rem;
        }
        .btn-info-outline:hover { background: #3498db; color: #fff; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; border-radius: 6px; }
        .btn-lg { padding: 11px 24px; font-size: 0.9rem; border-radius: 8px; }
        
        @media (max-width: 991px) {
            .guru-details { grid-template-columns: 1fr; gap: 20px; padding: 25px; }
        }
        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: stretch; gap: 12px; }
            .page-header-left h2 { font-size: 1.1rem; }
            .page-header-right .btn { width: 100%; justify-content: center; }
            .guru-profile { padding: 20px 16px; }
            .profile-header { flex-direction: column; text-align: center; gap: 15px; }
            .profile-avatar { width: 80px; height: 80px; margin: 0 auto; }
            .profile-info h3 { font-size: 1.2rem; }
            .profile-info p { justify-content: center; font-size: 0.8rem; flex-wrap: wrap; }
            .guru-details { padding: 15px; gap: 15px; }
            .detail-section { padding: 14px; }
            .detail-section h4 { font-size: 0.85rem; }
            .detail-row { flex-direction: column; align-items: flex-start; padding: 6px 0; gap: 2px; font-size: 0.82rem; }
            .detail-label { font-size: 0.75rem; }
            .detail-value { text-align: left; width: 100%; font-size: 0.85rem; }
            .detail-value .btn-sm { width: 100%; text-align: center; padding: 8px; }
            .verification-actions { flex-direction: column; align-items: stretch; text-align: center; padding: 16px 18px; gap: 15px; }
            .current-status { flex-direction: column; gap: 5px; justify-content: center; }
            .current-status .status-label { font-size: 0.8rem; }
            .action-buttons { flex-direction: column; width: 100%; gap: 8px; }
            .action-buttons .btn { width: 100%; justify-content: center; padding: 12px; font-size: 0.85rem; }
            .btn-lg { padding: 12px !important; font-size: 0.85rem !important; }
        }
        @media (max-width: 480px) {
            .guru-profile { padding: 16px 12px; }
            .profile-avatar { width: 70px; height: 70px; }
            .profile-info h3 { font-size: 1rem; }
            .profile-info p { font-size: 0.75rem; }
            .detail-section { padding: 12px; }
            .verification-actions { padding: 14px 12px; }
        }
    </style>
    
    <?php
    include 'include/admin_footer.php';
    exit;
}

// ============================================
// HALAMAN DAFTAR PENDING DENGAN FILTER WILAYAH
// ============================================
$title = 'Verifikasi Guru Ngaji';
include 'include/admin_header.php';

// ============================================
// PROSES PENCARIAN
// ============================================
$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : '';

// Build filter untuk daftar pending
$filter_sql = buildVerifikasiFilter($user_wilayah);

// Tambahkan kondisi pencarian jika ada
$search_sql = '';
if (!empty($search)) {
    $search_terms = explode(' ', $search);
    $search_conditions = [];
    
    foreach ($search_terms as $term) {
        $term = mysqli_real_escape_string($conn, $term);
        if (strlen($term) > 1) {
            $search_conditions[] = "(g.nik LIKE '%$term%' 
                                   OR g.nama LIKE '%$term%' 
                                   OR g.tempat_mengajar LIKE '%$term%' 
                                   OR kab.nama LIKE '%$term%' 
                                   OR k.nama LIKE '%$term%' 
                                   OR d.nama LIKE '%$term%')";
        }
    }
    
    if (!empty($search_conditions)) {
        $search_sql = 'AND (' . implode(' AND ', $search_conditions) . ')';
    }
}

$query_pending = "SELECT g.*, kab.nama as kabupaten_nama, k.nama as kecamatan_nama, d.nama as desa_nama 
                  FROM guru_ngaji g 
                  LEFT JOIN kabupaten kab ON g.kabupaten_id = kab.id
                  LEFT JOIN kecamatan k ON g.kecamatan_id = k.id 
                  LEFT JOIN desa d ON g.desa_id = d.id 
                  WHERE g.status_verifikasi = 'pending' 
                  $filter_sql 
                  $search_sql
                  ORDER BY g.created_at DESC";
$pending_list = mysqli_query($conn, $query_pending);
$total_pending = mysqli_num_rows($pending_list);

// Jika ada pencarian, ambil total semua pending (tanpa filter pencarian) untuk statistik
$query_total = "SELECT COUNT(*) as total FROM guru_ngaji g 
                WHERE g.status_verifikasi = 'pending' $filter_sql";
$result_total = mysqli_query($conn, $query_total);
$total_all_pending = mysqli_fetch_assoc($result_total)['total'];
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-check-double"></i> Verifikasi Guru Ngaji</h2>
        <p class="text-muted">Daftar guru ngaji yang menunggu verifikasi</p>
        <?php if ($user_role === 'super_admin'): ?>
            <span class="badge-super">👑 Super Admin - Akses Penuh</span>
        <?php elseif ($user_role === 'admin' && !$user_wilayah['akses_semua']): ?>
            <span class="badge-admin">🔑 Admin - Filter Wilayah</span>
        <?php elseif ($user_role === 'petugas_kecamatan'): ?>
            <span class="badge-petugas">📍 Petugas Kecamatan</span>
        <?php endif; ?>
        <?php if (!$user_wilayah['akses_semua'] && $user_role !== 'super_admin'): ?>
            <span class="filter-info"><i class="fas fa-filter"></i> Menampilkan data sesuai akses wilayah</span>
        <?php endif; ?>
    </div>
    <div class="page-header-right">
        <a href="guru.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-box-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo $total_all_pending; ?></h3>
            <p>Menunggu Verifikasi</p>
            <?php if (!$user_wilayah['akses_semua'] && $user_role !== 'super_admin'): ?>
                <small class="stat-note">(sesuai akses wilayah)</small>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($search)): ?>
    <div class="stat-box stat-box-search">
        <div class="stat-box-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
            <i class="fas fa-search"></i>
        </div>
        <div class="stat-box-info">
            <h3><?php echo $total_pending; ?></h3>
            <p>Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- FORM PENCARIAN -->
<div class="search-wrapper">
    <form method="GET" action="" class="search-form">
        <div class="search-input-group">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Cari berdasarkan Nama, NIK, Kecamatan, Desa..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-search">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="guru_verifikasi.php" class="btn btn-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($total_pending > 0): ?>
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Tempat Mengajar</th>
                        <th>Kabupaten</th>
                        <th>Kecamatan</th>
                        <th>No. Telepon</th>
                        <th>Tanggal Daftar</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($pending_list)): ?>
                        <tr>
                            <td class="col-no" data-label="No" style="text-align: center; color: #7f8c8d; font-weight: 600;">
                                <?php echo $no++; ?>
                            </td>
                            <td data-label="NIK">
                                <span class="nik-badge"><?php echo htmlspecialchars($row['nik']); ?></span>
                            </td>
                            <td data-label="Nama">
                                <strong class="nama-text"><?php echo htmlspecialchars($row['nama']); ?></strong>
                            </td>
                            <td data-label="Tempat Mengajar">
                                <span class="text-value"><?php echo htmlspecialchars($row['tempat_mengajar']); ?></span>
                            </td>
                            <td data-label="Kabupaten">
                                <span class="text-value">
                                    <i class="fas fa-map-marker-alt text-icon"></i> 
                                    <?php echo htmlspecialchars($row['kabupaten_nama'] ?? '-'); ?>
                                </span>
                            </td>
                            <td data-label="Kecamatan">
                                <span class="text-value">
                                    <i class="fas fa-map-marker-alt text-icon"></i> 
                                    <?php echo htmlspecialchars($row['kecamatan_nama'] ?? '-'); ?>
                                </span>
                            </td>
                            <td data-label="No. Telepon">
                                <span class="text-value">
                                    <i class="fas fa-phone text-icon"></i>
                                    <?php echo htmlspecialchars($row['no_telp'] ?: '-'); ?>
                                    <?php if ($row['no_telp']): ?>
                                        <a href="https://wa.me/<?php echo formatWA($row['no_telp']); ?>" 
                                           target="_blank" 
                                           class="btn-wa-sm" 
                                           title="Hubungi via WhatsApp">
                                            <i class="fab fa-whatsapp"></i> WA
                                        </a>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td data-label="Tanggal Daftar">
                                <span class="text-value">
                                    <i class="far fa-calendar-alt text-icon"></i> 
                                    <?php echo tanggal_indonesia($row['created_at']); ?>
                                </span>
                            </td>
                            <td class="col-action" data-label="Aksi" style="text-align: center;">
                                <a href="guru_verifikasi.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary btn-action-verif">
                                    <i class="fas fa-check-double"></i> Verifikasi
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas <?php echo !empty($search) ? 'fa-search' : 'fa-check-circle'; ?>"></i>
        </div>
        <h4><?php echo !empty($search) ? 'Tidak Ditemukan' : 'Semua Data Bersih!'; ?></h4>
        <p class="text-muted">
            <?php if (!empty($search)): ?>
                Tidak ada guru ngaji yang cocok dengan pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>" di wilayah akses Anda.
            <?php else: ?>
                Tidak ada guru ngaji yang membutuhkan verifikasi di wilayah akses Anda.
            <?php endif; ?>
        </p>
        <?php if (!empty($search)): ?>
            <a href="guru_verifikasi.php" class="btn btn-primary" style="margin-top: 15px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        <?php endif; ?>
        <?php if ($user_role === 'super_admin' && empty($search)): ?>
            <p class="text-muted" style="font-size: 0.85rem;">(Super Admin - Semua wilayah sudah terverifikasi)</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    /* ============================================
       STYLE PENCARIAN
    ============================================ */
    .search-wrapper {
        margin-bottom: 20px;
    }
    .search-form {
        background: #fff;
        padding: 16px 20px;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.03);
        border: 1px solid #f0f2f5;
    }
    .search-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .search-input-wrapper {
        flex: 1;
        position: relative;
        min-width: 200px;
    }
    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #95a5a6;
        font-size: 0.9rem;
    }
    .search-input {
        width: 100%;
        padding: 11px 16px 11px 40px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: #fafbfc;
        color: #2c3e50;
        outline: none;
    }
    .search-input:focus {
        border-color: #27ae60;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.08);
    }
    .search-input::placeholder {
        color: #adb5bd;
        font-size: 0.85rem;
    }
    
    .btn-search {
        background: #27ae60;
        color: #fff;
        padding: 11px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-search:hover {
        background: #1e8449;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    }
    
    .btn-reset {
        background: #e74c3c;
        color: #fff;
        padding: 11px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-reset:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
    }
    
    .stat-box-search {
        margin-left: 15px;
    }
    
    /* Tombol WA untuk daftar */
    .btn-wa-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #25D366;
        color: #fff !important;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-left: 8px;
    }
    .btn-wa-sm:hover {
        background: #1DA851;
        transform: scale(1.05);
        color: #fff !important;
    }
    .btn-wa-sm i {
        font-size: 0.8rem;
    }
    
    .badge-super { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
    .badge-admin { background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
    .badge-petugas { background: #cce5ff; color: #004085; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 5px; }
    .filter-info {
        display: inline-block;
        background: #e8f5e9;
        color: #1a6e3a;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
        margin-top: 5px;
    }
    .filter-info i { margin-right: 4px; }
    
    .stats-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
        align-items: center;
    }
    .stat-box {
        background: #fff;
        padding: 20px 24px;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        display: inline-flex;
        align-items: center;
        gap: 18px;
        border: 1px solid #f0f2f5;
        min-width: 200px;
        width: auto;
        flex: 0 1 auto;
    }
    .stat-box-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.3rem;
        flex-shrink: 0;
        background: linear-gradient(135deg, #f39c12, #e67e22);
        box-shadow: 0 8px 16px rgba(243, 156, 18, 0.2);
    }
    .stat-box-info h3 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        line-height: 1.1;
    }
    .stat-box-info p {
        font-size: 0.8rem;
        color: #7f8c8d;
        margin: 4px 0 0 0;
        font-weight: 500;
    }
    .stat-note {
        font-size: 0.65rem;
        color: #999;
        display: block;
        margin-top: 2px;
    }
    
    .table-wrapper {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        overflow: hidden;
        border: 1px solid #f0f2f5;
    }
    .table-responsive { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; }
    .table thead { background: #fafbfc; }
    .table th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 0.75rem;
        border-bottom: 2px solid #edf2f7;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
        font-size: 0.85rem;
        color: #495057;
        white-space: nowrap;
    }
    .table tbody tr { transition: background-color 0.2s ease; }
    .table tbody tr:hover { background: #fafbfc; }
    .table tbody tr:last-child td { border-bottom: none; }
    
    .nik-badge {
        background: #f1f3f5;
        color: #495057;
        padding: 4px 10px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    .text-icon { color: #95a5a6; margin-right: 5px; font-size: 0.85rem; }
    
    .btn-primary {
        background: #1e7e34;
        color: #fff;
        box-shadow: 0 4px 10px rgba(30, 126, 52, 0.15);
        padding: 8px 16px;
        font-size: 0.8rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .btn-primary:hover {
        background: #155d24;
        box-shadow: 0 6px 15px rgba(30, 126, 52, 0.25);
        color: #fff;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        border: 1px solid #f0f2f5;
    }
    .empty-icon { font-size: 3.5rem; color: #2ecc71; margin-bottom: 15px; }
    .empty-state h4 { margin: 0 0 8px 0; color: #2c3e50; font-weight: 700; font-size: 1.15rem; }
    .empty-state p { margin: 0; font-size: 0.88rem; }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .page-header-right .btn {
            width: 100%;
            justify-content: center;
        }
        
        .stats-row {
            flex-direction: column;
            gap: 10px;
        }
        .stat-box {
            width: 100%;
            min-width: auto;
            flex: 1 1 auto;
        }
        .stat-box-search {
            margin-left: 0;
        }
        
        .search-form {
            padding: 12px 14px;
        }
        .search-input-group {
            flex-direction: column;
            width: 100%;
        }
        .search-input-wrapper {
            width: 100%;
            min-width: auto;
        }
        .search-input {
            padding: 10px 14px 10px 38px;
            font-size: 0.85rem;
        }
        .btn-search,
        .btn-reset {
            width: 100%;
            justify-content: center;
            padding: 10px;
            font-size: 0.85rem;
        }
        
        .table thead { display: none; }
        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }
        .table tr {
            margin-bottom: 12px;
            background: #fff;
            border: 1px solid #f0f2f5;
            border-radius: 10px;
            padding: 12px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .table tr:last-child { margin-bottom: 0; }
        .table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 6px;
            border-bottom: 1px solid #f5f6f8;
            font-size: 0.82rem;
            white-space: normal;
            text-align: right;
            gap: 10px;
        }
        .table td:last-child {
            border-bottom: none;
            padding-top: 12px;
            justify-content: center;
        }
        .table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.7rem;
            text-align: left;
            flex-shrink: 0;
            min-width: 80px;
        }
        .table td.col-no { display: none; }
        .table td .text-value,
        .table td .nama-text,
        .table td .nik-badge {
            text-align: right;
            width: 100%;
        }
        
        .col-action {
            flex-direction: column !important;
            gap: 8px !important;
        }
        .col-action .btn-primary {
            width: 100%;
            justify-content: center;
            padding: 10px;
            font-size: 0.85rem;
        }
        .col-action::before {
            content: 'Aksi' !important;
        }
    }
    
    @media (max-width: 480px) {
        .stat-box {
            padding: 15px 18px;
        }
        .stat-box-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        .stat-box-info h3 {
            font-size: 1.2rem;
        }
        .stat-box-info p {
            font-size: 0.7rem;
        }
        
        .table td {
            font-size: 0.78rem;
            padding: 6px 4px;
        }
        .table td::before {
            font-size: 0.65rem;
            min-width: 60px;
        }
        .nik-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
        .btn-wa-sm {
            font-size: 0.6rem;
            padding: 1px 8px;
            margin-left: 4px;
        }
        .btn-wa-sm i {
            font-size: 0.7rem;
        }
    }
</style>

<?php include 'include/admin_footer.php'; ?>