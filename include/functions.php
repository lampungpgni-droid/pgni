<?php
// include/functions.php


function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}


// ============================================
// FUNGSI UPLOAD FILE GURU - KHUSUS UNTUK KTP & KK
// ============================================

function upload_guru_file($file, $folder, $nik, $nama, $max_size = 5242880) {
    // Cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'Gagal upload file (error code: ' . $file['error'] . ')'];
    }
    
    // Cek ukuran file
    if ($file['size'] > $max_size) {
        return ['status' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    // Cek ekstensi file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        return ['status' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.'];
    }
    
    // Bersihkan nama
    $clean_nama = strtolower(trim($nama));
    $clean_nama = preg_replace('/[^a-z0-9\s]/', '', $clean_nama);
    $clean_nama = preg_replace('/\s+/', ' ', $clean_nama);
    $clean_nama = str_replace(' ', '_', $clean_nama);
    $clean_nama = trim($clean_nama, '_');
    
    if (empty($clean_nama)) {
        $clean_nama = 'guru';
    }
    
    $clean_nik = preg_replace('/[^0-9]/', '', $nik);
    $folder_lower = strtolower($folder);
    
    // Tambahkan timestamp agar unik
    $timestamp = date('Ymd_His');
    $filename = $clean_nama . '_' . $clean_nik . '_' . $folder_lower . '_' . $timestamp . '.' . $ext;
    
    $root_path = dirname(__DIR__);
    $upload_dir = $root_path . '/assets/images/' . $folder . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
    }
    
    $target_path = $upload_dir . $filename;
    
    // Jika file sudah ada, tambahkan counter
    $counter = 1;
    $pathinfo = pathinfo($filename);
    $base_name = $pathinfo['filename'];
    $extension = $pathinfo['extension'];
    
    while (file_exists($target_path)) {
        $new_filename = $base_name . '_' . $counter . '.' . $extension;
        $target_path = $upload_dir . $new_filename;
        $counter++;
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        compress_image($target_path, $target_path, 75);
        
        return [
            'status' => true, 
            'filename' => basename($target_path),
            'path' => $target_path
        ];
    }
    
    return ['status' => false, 'message' => 'Gagal menyimpan file ke server'];
}

// ============================================
// FUNGSI COMPRESS IMAGE
// ============================================
function compress_image($source, $destination, $quality = 70) {
    if (!file_exists($source)) {
        return false;
    }
    
    $info = getimagesize($source);
    if (!$info) {
        return false;
    }
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($source);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $pngQuality = 9 - round(($quality / 100) * 9);
            $result = imagepng($image, $destination, $pngQuality);
            break;
        case 'image/gif':
            $result = imagegif($image, $destination);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $result = imagewebp($image, $destination, $quality);
            }
            break;
    }
    
    imagedestroy($image);
    return $result;
}

// ============================================
// FUNGSI UPLOAD FILE DENGAN TITLE & AUTO WEBP
// ============================================
function uploadFileWithTitle($file, $targetDir, $title, $maxSize = 5242880, $quality = 75) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Paksa ekstensi menjadi .webp
    $filename = generateFilenameFromTitle($title, 'webp');
    $targetPath = $targetDir . '/' . $filename;
    
    // Cek duplikasi nama file
    $counter = 1;
    $pathInfo = pathinfo($filename);
    $baseName = $pathInfo['filename'];
    
    while (file_exists($targetPath)) {
        $newFilename = $baseName . '_' . $counter . '.webp';
        $targetPath = $targetDir . '/' . $newFilename;
        $counter++;
    }
    
    // Proses Konversi ke WebP + Compress
    if (convertToWebp($file['tmp_name'], $targetPath, $ext, $quality)) {
        return basename($targetPath);
    }
    
    // Fallback: Jika server tidak mendukung GD WebP, simpan secara normal
    $fallbackPath = $targetDir . '/' . generateFilenameFromTitle($title, $ext);
    if (move_uploaded_file($file['tmp_name'], $fallbackPath)) {
        return basename($fallbackPath);
    }
    
    return false;
}

function generateFilenameFromTitle($title, $extension) {
    $filename = strtolower(trim($title));
    $filename = preg_replace('/[^a-z0-9]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    $filename = trim($filename, '_');
    
    if (empty($filename)) {
        $filename = 'gambar';
    }
    
    return $filename . '.' . $extension;
}

// ============================================
// FUNGSI HELPER: KONVERSI GAMBAR KE WEBP
// ============================================
function convertToWebp($sourcePath, $destinationPath, $ext, $quality = 75) {
    if (!function_exists('imagewebp')) {
        return false; // Server tidak mendukung ekstensi GD WebP
    }

    $image = null;

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $image = @imagecreatefrompng($sourcePath);
            if ($image) {
                // Mempertahankan transparansi PNG
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'gif':
            $image = @imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $image = @imagecreatefromwebp($sourcePath);
            break;
    }

    if ($image) {
        // Simpan gambar sebagai .webp dengan kompresi $quality (default 75%)
        $result = imagewebp($image, $destinationPath, $quality);
        imagedestroy($image); // Hapus memori sementara
        return $result;
    }

    return false;
}

// ============================================
// FUNGSI UPLOAD FILE - UNTUK COMPATIBILITY
// ============================================
function upload_file($file, $folder, $allowed_types = ['jpg','jpeg','png','gif','webp'], $max_size = 67108864) {
    $nama_file = $file['name'];
    $ukuran_file = $file['size'];
    $tmp_file = $file['tmp_name'];
    $error = $file['error'];
    
    if ($error === 4) {
        return ['status' => false, 'message' => 'Tidak ada file yang diupload'];
    }
    
    if ($ukuran_file > $max_size) {
        return ['status' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 64MB'];
    }
    
    $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    if (!in_array($ekstensi, $allowed_types)) {
        return ['status' => false, 'message' => 'Ekstensi file tidak diizinkan'];
    }
    
    $nama_baru = time() . '_' . rand(1000, 9999) . '.' . $ekstensi;
    
    $root_path = dirname(__DIR__);
    $upload_dir = $root_path . '/assets/images/' . $folder . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $path_upload = $upload_dir . $nama_baru;
    
    if (move_uploaded_file($tmp_file, $path_upload)) {
        compress_image($path_upload, $path_upload, 75);
        return ['status' => true, 'nama_file' => $nama_baru];
    }
    
    return ['status' => false, 'message' => 'Gagal upload file'];
}

// ============================================
// FUNGSI SAVE BASE64 IMAGE (UNTUK GURU_EDIT & GURU_TAMBAH)
// ============================================
function save_base64_image($base64_string, $folder, $nik, $nama) {
    if (empty($base64_string)) {
        return false;
    }
    
    $parts = explode(',', $base64_string);
    if (count($parts) < 2) {
        return false;
    }
    
    $data = base64_decode($parts[1]);
    if (!$data || strlen($data) < 100) {
        return false;
    }
    
    // Get mime type
    $ext_parts = explode(';', $parts[0]);
    $mime_parts = explode(':', $ext_parts[0]);
    $mime = $mime_parts[1] ?? 'image/jpeg';
    
    $extension = 'jpg';
    if (strpos($mime, 'png') !== false) $extension = 'png';
    else if (strpos($mime, 'webp') !== false) $extension = 'webp';
    else if (strpos($mime, 'gif') !== false) $extension = 'gif';
    else if (strpos($mime, 'jpeg') !== false) $extension = 'jpg';
    
    // ============================================
    // BERSIHKAN NAMA UNTUK FILENAME
    // Format: NAMA_NIK_FOLDER_TIMESTAMP.ext
    // ============================================
    
    // Bersihkan nama (lowercase, hanya huruf/angka, spasi jadi underscore)
    $clean_nama = strtolower(trim($nama));
    $clean_nama = preg_replace('/[^a-z0-9\s]/', '', $clean_nama);
    $clean_nama = preg_replace('/\s+/', ' ', $clean_nama);
    $clean_nama = str_replace(' ', '_', $clean_nama);
    $clean_nama = trim($clean_nama, '_');
    
    if (empty($clean_nama)) {
        $clean_nama = 'guru';
    }
    
    $clean_nik = preg_replace('/[^0-9]/', '', $nik);
    $folder_lower = strtolower($folder);
    
    // Tambahkan timestamp
    $timestamp = date('Ymd_His');
    $filename = $clean_nama . '_' . $clean_nik . '_' . $folder_lower . '_' . $timestamp . '.' . $extension;
    
    $root_path = dirname(__DIR__);
    $upload_dir = $root_path . '/assets/images/' . $folder . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
    }
    
    $file_path = $upload_dir . $filename;
    
    $counter = 1;
    $pathInfo = pathinfo($filename);
    $baseName = $pathInfo['filename'];
    $extensionFile = $pathInfo['extension'];
    
    while (file_exists($file_path)) {
        $newFilename = $baseName . '_' . $counter . '.' . $extensionFile;
        $file_path = $upload_dir . $newFilename;
        $counter++;
    }
    
    if (file_put_contents($file_path, $data) !== false) {
        compress_image($file_path, $file_path, 75);
        return basename($file_path);
    }
    
    return false;
}







// ============================================
// FUNGSI LAINNYA
// ============================================
function get_file_url($filename, $folder) {
    if (empty($filename)) return '';
    
    $base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($script_dir, '/admin') !== false) {
        $script_dir = dirname($script_dir);
    }
    
    return $base_url . $script_dir . '/assets/images/' . $folder . '/' . $filename;
}

function tanggal_indonesia($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    if (empty($tanggal)) return '-';
    $tgl = date('j', strtotime($tanggal));
    $bln = $bulan[(int)date('n', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

function potong_teks($teks, $panjang = 150) {
    $teks = strip_tags($teks);
    if (strlen($teks) > $panjang) {
        return substr($teks, 0, $panjang) . '...';
    }
    return $teks;
}


// ============================================
// FUNGSI GET OG IMAGE URL UNTUK SHARE
// ============================================
function get_og_image_url($filename, $folder = 'berita') {
    if (empty($filename)) {
        return '';
    }
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $root_path = dirname(__DIR__);
    
    // Cek apakah file ada
    $file_path = $root_path . '/assets/images/' . $folder . '/' . $filename;
    if (!file_exists($file_path)) {
        // Jika tidak ada, gunakan logo default
        $logo_path = $root_path . '/assets/images/logo/logo-pgni.png';
        if (file_exists($logo_path)) {
            return $protocol . $host . '/pgnil/assets/images/logo/logo-pgni.png';
        }
        return '';
    }
    
    return $protocol . $host . '/pgnil/assets/images/' . $folder . '/' . $filename;
}

function generate_og_tags($title, $description, $image_url, $url) {
    $tags = [];
    $tags[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '" />';
    $tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '" />';
    $tags[] = '<meta property="og:image" content="' . htmlspecialchars($image_url) . '" />';
    $tags[] = '<meta property="og:image:width" content="1200" />';
    $tags[] = '<meta property="og:image:height" content="630" />';
    $tags[] = '<meta property="og:url" content="' . htmlspecialchars($url) . '" />';
    $tags[] = '<meta property="og:type" content="article" />';
    $tags[] = '<meta property="og:site_name" content="PGNI Lampung" />';
    $tags[] = '<meta name="twitter:card" content="summary_large_image" />';
    $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '" />';
    $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '" />';
    $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($image_url) . '" />';
    
    return implode("\n", $tags);
}

function cek_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function cek_role($role_required) {
    if ($_SESSION['role'] !== $role_required && $_SESSION['role'] !== 'super_admin') {
        header('Location: dashboard.php?error=akses_ditolak');
        exit;
    }
}

 
function sendVerificationWhatsApp($guru_data, $status) {
    // Konfigurasi
    $admin_phone = '6281366584799'; // Ganti dengan nomor admin PGNI
    $organization_name = 'PGNI Lampung';
    $organization_website = 'https://pgni.net';
    $base_url = 'https://pgni.net/pgnil/';
    
    // Nomor tujuan (guru)
    $target_phone = $guru_data['no_telp'];
    if (empty($target_phone)) {
        return false;
    }
    
    // Bersihkan nomor telepon
    $target_phone = cleanPhoneNumber($target_phone);
    $admin_phone = cleanPhoneNumber($admin_phone);
    
    // Format tanggal
    $tanggal = date('d-m-Y H:i');
    $tanggal_indonesia = tanggal_indonesia(date('Y-m-d'));
    
    // Data guru
    $nama = $guru_data['nama'];
    $nik = $guru_data['nik'];
    $tempat_mengajar = $guru_data['tempat_mengajar'];
    $tempat_detail = $guru_data['tempat_mengajar_detail'] ?? '';
    $profesi = $guru_data['jenis_profesi'] ?? '-';
    
    // Buat link cek status otomatis
    $cek_status_url = $base_url . 'cek_status.php?nik=' . $nik;
    
    // Buat pesan berdasarkan status
    if ($status === 'disetujui') {
        $message = "✅ *VERIFIKASI DISETUJUI* ✅\n\n";
        $message .= "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\n";
        $message .= "Yth. Bapak/Ibu *{$nama}*\n\n";
        $message .= "Dengan hormat, kami sampaikan bahwa data pendaftaran Anda sebagai guru ngaji di *{$organization_name}* telah *DISETUJUI* dan diverifikasi.\n\n";
        $message .= "📋 *Detail Data Anda:*\n";
        $message .= "┌──────────────────────────────────\n";
        $message .= "│ NIK            : {$nik}\n";
        $message .= "│ Nama Lengkap   : {$nama}\n";
        $message .= "│ Tempat Mengajar: {$tempat_mengajar}\n";
        if (!empty($tempat_detail)) {
            $message .= "│ Detail Tempat  : {$tempat_detail}\n";
        }
        $message .= "│ Profesi        : {$profesi}\n";
        $message .= "│ Status         : ✅ DISETUJUI\n";
        $message .= "│ Tanggal Verif  : {$tanggal_indonesia}\n";
        $message .= "└──────────────────────────────────\n\n";
        $message .= "🔗 *Cek Status Online:*\n";
        $message .= "{$cek_status_url}\n\n";
        $message .= "🌟 *Langkah Selanjutnya:*\n";
        $message .= "1. Klik link di atas untuk cek status dan login\n";
        $message .= "2. Login ke Member Area:\n";
        $message .= "   🔑 NIK: {$nik}\n";
        $message .= "   🔑 Password: pgnilampung\n";
        $message .= "3. Lengkapi data profil Anda\n\n";
        $message .= "📱 *Member Area:*\n";
        $message .= "{$base_url}member/login.php\n\n";
        $message .= "🙏 Terima kasih atas partisipasi Anda.\n";
        $message .= "Semoga Allah membalas kebaikan Anda.\n\n";
        $message .= "---\n";
        $message .= "📱 *{$organization_name}*\n";
        $message .= "🌐 {$organization_website}\n";
        $message .= "📅 {$tanggal} WIB";
    } else {
        $message = "❌ *VERIFIKASI DITOLAK* ❌\n\n";
        $message .= "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\n";
        $message .= "Yth. Bapak/Ibu *{$nama}*\n\n";
        $message .= "Dengan hormat, kami sampaikan bahwa data pendaftaran Anda sebagai guru ngaji di *{$organization_name}* *DITOLAK* setelah melalui proses verifikasi.\n\n";
        $message .= "📋 *Data yang Diajukan:*\n";
        $message .= "┌──────────────────────────────────\n";
        $message .= "│ NIK            : {$nik}\n";
        $message .= "│ Nama Lengkap   : {$nama}\n";
        $message .= "│ Tempat Mengajar: {$tempat_mengajar}\n";
        if (!empty($tempat_detail)) {
            $message .= "│ Detail Tempat  : {$tempat_detail}\n";
        }
        $message .= "│ Status         : ❌ DITOLAK\n";
        $message .= "│ Tanggal Verif  : {$tanggal_indonesia}\n";
        $message .= "└──────────────────────────────────\n\n";
        $message .= "🔗 *Cek Status:*\n";
        $message .= "{$cek_status_url}\n\n";
        $message .= "💡 *Yang Perlu Dilakukan:*\n";
        $message .= "1. Klik link di atas untuk cek detail penolakan\n";
        $message .= "2. Periksa kembali kelengkapan data Anda\n";
        $message .= "3. Pastikan dokumen yang diunggah jelas dan valid\n";
        $message .= "4. Hubungi admin untuk informasi lebih lanjut\n\n";
        $message .= "📞 *Kontak Admin:*\n";
        $message .= "WA: 0852-xxxx-xxxx\n\n";
        $message .= "Kami mohon maaf atas ketidaknyamanan ini.\n";
        $message .= "Semoga Allah memudahkan urusan Anda.\n\n";
        $message .= "---\n";
        $message .= "📱 *{$organization_name}*\n";
        $message .= "🌐 {$organization_website}\n";
        $message .= "📅 {$tanggal} WIB";
    }
    
    // Encode pesan untuk URL
    $encoded_message = urlencode($message);
    
    // URL WhatsApp
    $whatsapp_url = "https://wa.me/{$target_phone}?text={$encoded_message}";
    
    // Pesan untuk admin (notifikasi internal)
    $admin_message = "📢 *NOTIFIKASI VERIFIKASI GURU*\n\n";
    $admin_message .= "Admin telah " . ($status === 'disetujui' ? 'MENYETUJUI ✅' : 'MENOLAK ❌') . " verifikasi guru:\n\n";
    $admin_message .= "┌──────────────────────────────────\n";
    $admin_message .= "│ Nama    : {$nama}\n";
    $admin_message .= "│ NIK     : {$nik}\n";
    $admin_message .= "│ Mengajar: {$tempat_mengajar}\n";
    $admin_message .= "│ Status  : " . ($status === 'disetujui' ? '✅ DISETUJUI' : '❌ DITOLAK') . "\n";
    $admin_message .= "│ Waktu   : {$tanggal_indonesia}\n";
    $admin_message .= "└──────────────────────────────────\n\n";
    $admin_message .= "🔗 Cek Status: {$cek_status_url}\n\n";
    $admin_message .= "Notifikasi telah dikirim ke guru.\n";
    $admin_message .= "---\n";
    $admin_message .= "📱 PGNI Lampung - Sistem Manajemen";
    
    $admin_encoded = urlencode($admin_message);
    $admin_whatsapp_url = "https://wa.me/{$admin_phone}?text={$admin_encoded}";
    
    // Simpan URL WhatsApp untuk popup
    $_SESSION['whatsapp_guru_url'] = $whatsapp_url;
    $_SESSION['whatsapp_admin_url'] = $admin_whatsapp_url;
    $_SESSION['whatsapp_status'] = $status;
    $_SESSION['whatsapp_guru_name'] = $guru_data['nama'];
    $_SESSION['whatsapp_guru_phone'] = $target_phone;
    $_SESSION['whatsapp_cek_status_url'] = $cek_status_url;
    
    return true;
}

/**
 * Bersihkan nomor telepon untuk WhatsApp
 */
function cleanPhoneNumber($phone) {
    // Hapus semua karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Jika dimulai dengan 0, ganti dengan 62
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Jika dimulai dengan 8, tambahkan 62
    if (substr($phone, 0, 1) === '8' && strlen($phone) <= 13) {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Tampilkan tombol kirim WhatsApp setelah verifikasi
 */
function showWhatsAppButtons() {
    if (isset($_SESSION['whatsapp_guru_url'])) {
        $guru_url = $_SESSION['whatsapp_guru_url'];
        $admin_url = $_SESSION['whatsapp_admin_url'];
        $status = $_SESSION['whatsapp_status'] ?? '';
        $name = $_SESSION['whatsapp_guru_name'] ?? 'Guru';
        
        $status_text = $status === 'disetujui' ? 'Disetujui' : 'Ditolak';
        $status_icon = $status === 'disetujui' ? '✅' : '❌';
        
        // Hapus session setelah ditampilkan
        unset($_SESSION['whatsapp_guru_url']);
        unset($_SESSION['whatsapp_admin_url']);
        unset($_SESSION['whatsapp_status']);
        unset($_SESSION['whatsapp_guru_name']);
        
        echo '<div class="whatsapp-popup">';
        echo '  <div class="whatsapp-popup-content">';
        echo '    <h4><i class="fab fa-whatsapp" style="color:#25D366;"></i> Kirim Notifikasi WhatsApp</h4>';
        echo '    <p>Verifikasi telah ' . strtolower($status_text) . ' untuk <strong>' . htmlspecialchars($name) . '</strong></p>';
        echo '    <div class="whatsapp-buttons">';
        echo '      <a href="' . $guru_url . '" target="_blank" class="btn-whatsapp-guru">';
        echo '        <i class="fab fa-whatsapp"></i> Kirim ke Guru';
        echo '      </a>';
        echo '      <a href="' . $admin_url . '" target="_blank" class="btn-whatsapp-admin">';
        echo '        <i class="fab fa-whatsapp"></i> Kirim ke Admin';
        echo '      </a>';
        echo '    </div>';
        echo '    <p class="whatsapp-note"><small>Klik tombol di atas untuk membuka WhatsApp dan mengirim pesan</small></p>';
        echo '    <a href="#" class="whatsapp-close" onclick="this.parentElement.parentElement.style.display=\'none\'; return false;">Tutup</a>';
        echo '  </div>';
        echo '</div>';
        
        echo '<style>
            .whatsapp-popup {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
                animation: slideUp 0.5s ease;
            }
            .whatsapp-popup-content {
                background: #fff;
                border-radius: 16px;
                padding: 25px 30px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.15);
                max-width: 380px;
                border: 1px solid #e8f5e9;
                border-top: 4px solid #25D366;
            }
            .whatsapp-popup-content h4 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-size: 1.1rem;
            }
            .whatsapp-popup-content p {
                margin: 0 0 15px 0;
                color: #7f8c8d;
                font-size: 0.9rem;
            }
            .whatsapp-buttons {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
            }
            .whatsapp-buttons a {
                flex: 1;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 15px;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.85rem;
                transition: all 0.3s ease;
            }
            .btn-whatsapp-guru {
                background: #25D366;
                color: #fff;
            }
            .btn-whatsapp-guru:hover {
                background: #1da851;
                transform: translateY(-2px);
            }
            .btn-whatsapp-admin {
                background: #075e54;
                color: #fff;
            }
            .btn-whatsapp-admin:hover {
                background: #054740;
                transform: translateY(-2px);
            }
            .whatsapp-note {
                margin: 0 0 15px 0;
                color: #95a5a6;
                font-size: 0.8rem !important;
            }
            .whatsapp-close {
                display: block;
                text-align: center;
                color: #95a5a6;
                text-decoration: none;
                font-size: 0.8rem;
                padding: 5px;
            }
            .whatsapp-close:hover {
                color: #e74c3c;
            }
            @keyframes slideUp {
                from { transform: translateY(30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @media (max-width: 480px) {
                .whatsapp-popup {
                    bottom: 15px;
                    right: 15px;
                    left: 15px;
                }
                .whatsapp-popup-content {
                    padding: 20px;
                    max-width: 100%;
                }
                .whatsapp-buttons {
                    flex-direction: column;
                }
            }
        </style>';
    }
    

// Tambahkan atau perbaiki fungsi-fungsi ini di include/functions.php

/**
 * Kirim notifikasi ke user
 */
function send_notification($user_id, $judul, $pesan, $link = '', $tipe = 'info') {
    global $conn;
    $judul = mysqli_real_escape_string($conn, $judul);
    $pesan = mysqli_real_escape_string($conn, $pesan);
    $link = mysqli_real_escape_string($conn, $link);
    
    $query = "INSERT INTO notifikasi (user_id, tipe, judul, pesan, link, created_at) 
              VALUES ($user_id, '$tipe', '$judul', '$pesan', '$link', NOW())";
    return mysqli_query($conn, $query);
}

/**
 * Kirim notifikasi ke semua user dengan level tertentu
 * PERBAIKAN: Deteksi otomatis nama tabel user
 */
function send_notification_to_all($judul, $pesan, $link = '', $tipe = 'info', $level = 'member') {
    global $conn;
    $judul = mysqli_real_escape_string($conn, $judul);
    $pesan = mysqli_real_escape_string($conn, $pesan);
    $link = mysqli_real_escape_string($conn, $link);
    
    // Deteksi nama tabel user
    $table_user = 'users';
    $check_user = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
    if (mysqli_num_rows($check_user) > 0) {
        $table_user = 'user';
    }
    
    $query = "INSERT INTO notifikasi (user_id, tipe, judul, pesan, link, created_at)
              SELECT id, '$tipe', '$judul', '$pesan', '$link', NOW()
              FROM $table_user WHERE level = '$level'";
    return mysqli_query($conn, $query);
}

/**
 * Ambil notifikasi untuk user
 */
function get_notifikasi($user_id, $limit = 10) {
    global $conn;
    $query = "SELECT * FROM notifikasi WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit";
    return mysqli_query($conn, $query);
}

/**
 * Mark notifikasi sebagai sudah dibaca
 */
function mark_notification_read($notif_id, $user_id) {
    global $conn;
    $query = "UPDATE notifikasi SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id";
    return mysqli_query($conn, $query);
}

/**
 * Hitung notifikasi belum dibaca
 */
function count_unread_notifications($user_id) {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM notifikasi WHERE user_id = $user_id AND is_read = 0";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row ? $row['total'] : 0;
}
   
}


function send_whatsapp_message($target, $message, $apiKey = null) {
    // Ambil API Key dari environment atau parameter
    if ($apiKey === null) {
        $apiKey = getenv('FONNTE_API_KEY') ?: '1zxAyx9GWBT561RuYKZJ';
    }
    
    $apiUrl = 'https://api.fonnte.com/send';
    
    // Bersihkan nomor telepon
    $target = cleanPhoneNumber($target);
    
    $payload = [
        'target' => $target,
        'message' => $message,
        'countryCode' => '62'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log
    log_whatsapp_activity($target, $message, $response, $httpCode);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

/**
 * Kirim pesan broadcast ke banyak nomor
 * 
 * @param array $targets Array nomor tujuan
 * @param string $message Pesan yang akan dikirim
 * @param int $delay Jeda antar pengiriman (detik)
 * @return array Hasil pengiriman
 */
function send_whatsapp_broadcast($targets, $message, $delay = 1) {
    $results = [];
    $apiKey = getenv('FONNTE_API_KEY') ?: '1zxAyx9GWBT561RuYKZJ';
    
    foreach ($targets as $index => $target) {
        $result = send_whatsapp_message($target, $message, $apiKey);
        $results[] = [
            'target' => $target,
            'success' => $result['success'],
            'index' => $index + 1
        ];
        
        // Jeda agar tidak kena limit
        if ($delay > 0 && $index < count($targets) - 1) {
            sleep($delay);
        }
    }
    
    return $results;
}

/**
 * Kirim notifikasi verifikasi ke guru via WhatsApp
 * Menggunakan fungsi yang sudah ada dengan tambahan integrasi Fonnte
 * 
 * @param array $guru_data Data guru
 * @param string $status Status verifikasi (disetujui/ditolak)
 * @param bool $use_fonnte Gunakan Fonnte API (true) atau URL WhatsApp (false)
 * @return array Hasil pengiriman
 */
function sendVerificationWhatsAppFonnte($guru_data, $status, $use_fonnte = true) {
    $admin_phone = '6282384350165';
    $organization_name = 'PGNI Lampung';
    $organization_website = 'https://pgni.net';
    $base_url = 'https://pgni.net/pgnil/';
    
    $target_phone = $guru_data['no_telp'] ?? '';
    if (empty($target_phone)) {
        return ['success' => false, 'message' => 'Nomor telepon tidak tersedia'];
    }
    
    $target_phone = cleanPhoneNumber($target_phone);
    $admin_phone = cleanPhoneNumber($admin_phone);
    
    $tanggal = date('d-m-Y H:i');
    $tanggal_indonesia = tanggal_indonesia(date('Y-m-d'));
    
    $nama = $guru_data['nama'];
    $nik = $guru_data['nik'];
    $tempat_mengajar = $guru_data['tempat_mengajar'];
    $tempat_detail = $guru_data['tempat_mengajar_detail'] ?? '';
    $profesi = $guru_data['jenis_profesi'] ?? '-';
    
    $cek_status_url = $base_url . 'cek_status.php?nik=' . $nik;
    
    // Buat pesan berdasarkan status
    if ($status === 'disetujui') {
        $message = "✅ *VERIFIKASI DISETUJUI* ✅\n\n";
        $message .= "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\n";
        $message .= "Yth. Bapak/Ibu *{$nama}*\n\n";
        $message .= "Dengan hormat, kami sampaikan bahwa data pendaftaran Anda sebagai guru ngaji di *{$organization_name}* telah *DISETUJUI* dan diverifikasi.\n\n";
        $message .= "📋 *Detail Data Anda:*\n";
        $message .= "┌──────────────────────────────────\n";
        $message .= "│ NIK            : {$nik}\n";
        $message .= "│ Nama Lengkap   : {$nama}\n";
        $message .= "│ Tempat Mengajar: {$tempat_mengajar}\n";
        if (!empty($tempat_detail)) {
            $message .= "│ Detail Tempat  : {$tempat_detail}\n";
        }
        $message .= "│ Profesi        : {$profesi}\n";
        $message .= "│ Status         : ✅ DISETUJUI\n";
        $message .= "│ Tanggal Verif  : {$tanggal_indonesia}\n";
        $message .= "└──────────────────────────────────\n\n";
        $message .= "🔗 *Cek Status Online:*\n";
        $message .= "{$cek_status_url}\n\n";
        $message .= "🌟 *Langkah Selanjutnya:*\n";
        $message .= "1. Klik link di atas untuk cek status dan login\n";
        $message .= "2. Login ke Member Area:\n";
        $message .= "   🔑 NIK: {$nik}\n";
        $message .= "   🔑 Password: pgnilampung\n";
        $message .= "3. Lengkapi data profil Anda\n\n";
        $message .= "📱 *Member Area:*\n";
        $message .= "{$base_url}member/login.php\n\n";
        $message .= "🙏 Terima kasih atas partisipasi Anda.\n";
        $message .= "Semoga Allah membalas kebaikan Anda.\n\n";
        $message .= "---\n";
        $message .= "📱 *{$organization_name}*\n";
        $message .= "🌐 {$organization_website}\n";
        $message .= "📅 {$tanggal} WIB";
    } else {
        $message = "❌ *VERIFIKASI DITOLAK* ❌\n\n";
        $message .= "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\n";
        $message .= "Yth. Bapak/Ibu *{$nama}*\n\n";
        $message .= "Dengan hormat, kami sampaikan bahwa data pendaftaran Anda sebagai guru ngaji di *{$organization_name}* *DITOLAK* setelah melalui proses verifikasi.\n\n";
        $message .= "📋 *Data yang Diajukan:*\n";
        $message .= "┌──────────────────────────────────\n";
        $message .= "│ NIK            : {$nik}\n";
        $message .= "│ Nama Lengkap   : {$nama}\n";
        $message .= "│ Tempat Mengajar: {$tempat_mengajar}\n";
        if (!empty($tempat_detail)) {
            $message .= "│ Detail Tempat  : {$tempat_detail}\n";
        }
        $message .= "│ Status         : ❌ DITOLAK\n";
        $message .= "│ Tanggal Verif  : {$tanggal_indonesia}\n";
        $message .= "└──────────────────────────────────\n\n";
        $message .= "🔗 *Cek Status:*\n";
        $message .= "{$cek_status_url}\n\n";
        $message .= "💡 *Yang Perlu Dilakukan:*\n";
        $message .= "1. Klik link di atas untuk cek detail penolakan\n";
        $message .= "2. Periksa kembali kelengkapan data Anda\n";
        $message .= "3. Pastikan dokumen yang diunggah jelas dan valid\n";
        $message .= "4. Hubungi admin untuk informasi lebih lanjut\n\n";
        $message .= "📞 *Kontak Admin:*\n";
        $message .= "WA: 0852-xxxx-xxxx\n\n";
        $message .= "Kami mohon maaf atas ketidaknyamanan ini.\n";
        $message .= "Semoga Allah memudahkan urusan Anda.\n\n";
        $message .= "---\n";
        $message .= "📱 *{$organization_name}*\n";
        $message .= "🌐 {$organization_website}\n";
        $message .= "📅 {$tanggal} WIB";
    }
    
    if ($use_fonnte) {
        // Kirim via Fonnte API
        $result = send_whatsapp_message($target_phone, $message);
        
        // Kirim juga ke admin
        $admin_message = "📢 *NOTIFIKASI VERIFIKASI GURU*\n\n";
        $admin_message .= "Admin telah " . ($status === 'disetujui' ? 'MENYETUJUI ✅' : 'MENOLAK ❌') . " verifikasi guru:\n\n";
        $admin_message .= "┌──────────────────────────────────\n";
        $admin_message .= "│ Nama    : {$nama}\n";
        $admin_message .= "│ NIK     : {$nik}\n";
        $admin_message .= "│ Mengajar: {$tempat_mengajar}\n";
        $admin_message .= "│ Status  : " . ($status === 'disetujui' ? '✅ DISETUJUI' : '❌ DITOLAK') . "\n";
        $admin_message .= "│ Waktu   : {$tanggal_indonesia}\n";
        $admin_message .= "└──────────────────────────────────\n\n";
        $admin_message .= "🔗 Cek Status: {$cek_status_url}";
        
        send_whatsapp_message($admin_phone, $admin_message);
        
        return $result;
    } else {
        // Fallback ke URL WhatsApp (seperti fungsi asli)
        $encoded_message = urlencode($message);
        $whatsapp_url = "https://wa.me/{$target_phone}?text={$encoded_message}";
        
        $_SESSION['whatsapp_guru_url'] = $whatsapp_url;
        $_SESSION['whatsapp_status'] = $status;
        $_SESSION['whatsapp_guru_name'] = $guru_data['nama'];
        
        return ['success' => true, 'url' => $whatsapp_url];
    }
}

/**
 * Cek apakah nomor terdaftar di database
 * 
 * @param string $phone Nomor telepon
 * @return array|false Data guru jika terdaftar, false jika tidak
 */
function cek_nomor_terdaftar($phone, $conn = null) {
    global $conn;
    if (!$conn) return false;
    
    $phone = cleanPhoneNumber($phone);
    $phone = mysqli_real_escape_string($conn, $phone);
    
    $query = "SELECT id, nama, nik, status_verifikasi, tempat_mengajar 
              FROM guru_ngaji 
              WHERE no_telp = '$phone'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

/**
 * Cek NIK terdaftar
 * 
 * @param string $nik NIK
 * @return array|false Data guru jika terdaftar, false jika tidak
 */
function cek_nik_terdaftar($nik, $conn = null) {
    global $conn;
    if (!$conn) return false;
    
    $nik = mysqli_real_escape_string($conn, $nik);
    
    $query = "SELECT id, nama, no_telp, status_verifikasi, tempat_mengajar 
              FROM guru_ngaji 
              WHERE nik = '$nik'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

/**
 * Dapatkan status verifikasi dalam teks
 * 
 * @param string $status Status verifikasi
 * @return string Status dalam teks dengan emoji
 */
function get_status_verifikasi_text($status) {
    $map = [
        'pending' => '⏳ Menunggu Verifikasi',
        'disetujui' => '✅ Disetujui',
        'ditolak' => '❌ Ditolak'
    ];
    return $map[$status] ?? $status;
}

/**
 * Format pesan berita untuk WhatsApp
 * 
 * @param array $berita Data berita
 * @return string Pesan terformat
 */
function format_berita_whatsapp($berita) {
    $tanggal = date('d F Y H:i', strtotime($berita['created_at']));
    $isi = strip_tags($berita['isi']);
    $isi = strlen($isi) > 500 ? substr($isi, 0, 500) . '...' : $isi;
    
    $message = "📰 *" . strtoupper($berita['judul']) . "*\n\n";
    $message .= "📅 {$tanggal}\n\n";
    $message .= "{$isi}\n\n";
    $message .= "🔗 Baca selengkapnya:\n";
    $message .= "https://www.pgni.net/pgnil/berita_detail.php?id=" . $berita['id'];
    
    return $message;
}

/**
 * Format daftar berita untuk WhatsApp
 * 
 * @param array $berita_list List berita
 * @return string Pesan terformat
 */
function format_berita_list_whatsapp($berita_list) {
    if (empty($berita_list)) {
        return "📰 *Berita Terbaru*\n\nBelum ada berita.";
    }
    
    $message = "📰 *Berita Terbaru PGNI Lampung*\n\n";
    foreach ($berita_list as $index => $berita) {
        $num = $index + 1;
        $tanggal = date('d/m/Y', strtotime($berita['created_at']));
        $judul = strlen($berita['judul']) > 50 ? substr($berita['judul'], 0, 50) . '...' : $berita['judul'];
        $message .= "{$num}. *{$judul}*\n";
        $message .= "   📅 {$tanggal}\n\n";
    }
    $message .= "📌 Ketik nomor berita untuk detail.";
    
    return $message;
}

/**
 * Format info donasi untuk WhatsApp
 * 
 * @return string Pesan donasi
 */
function format_donasi_whatsapp() {
    $message = "🤲 *Donasi PGNI Lampung*\n\n";
    $message .= "Salurkan donasi terbaik Anda untuk mendukung program:\n";
    $message .= "✅ Pelatihan & Sertifikasi Guru Ngaji\n";
    $message .= "✅ Program Kesejahteraan Guru Ngaji\n";
    $message .= "✅ Beasiswa Pendidikan\n";
    $message .= "✅ Dakwah & Pengabdian Masyarakat\n\n";
    $message .= "📋 *Rekening Donasi:*\n";
    $message .= "Bank: *BRI*\n";
    $message .= "No. Rekening: *8905-3656-96*\n";
    $message .= "Atas Nama: *PGNI Lampung*\n\n";
    $message .= "📌 *Konfirmasi Donasi:*\n";
    $message .= "WA: 0813-6658-4799\n\n";
    $message .= "🔗 *Donasi Online:*\n";
    $message .= "https://www.pgni.net/pgnil/donasi.php\n\n";
    $message .= "Jazakumullahu Khairan 🙏";
    
    return $message;
}

/**
 * Format info lokasi untuk WhatsApp
 * 
 * @return string Pesan lokasi
 */
function format_lokasi_whatsapp() {
    $message = "📍 *Lokasi Kantor PGNI Lampung*\n\n";
    $message .= "🏢 *PGNI Lampung*\n";
    $message .= "📌 Alamat: Gg.Pondok No.16 Kel. Durian Payung\n";
    $message .= "   Kec. Tanjung Karang Pusat, Bandar Lampung - 35116\n";
    $message .= "📞 Telepon: 0812-7343-7568\n";
    $message .= "📧 Email: info@pgni.net\n\n";
    $message .= "🗺️ *Google Maps:*\n";
    $message .= "https://maps.google.com/?q=Durian+Payung+Tanjung+Karang+Pusat+Bandar+Lampung\n\n";
    $message .= "🕐 *Jam Kantor:*\n";
    $message .= "Senin - Jumat: 08:00 - 17:00 WIB\n";
    $message .= "Sabtu: 08:00 - 13:00 WIB";
    
    return $message;
}

/**
 * Format info tentang PGNI untuk WhatsApp
 * 
 * @param array $yayasan Data yayasan
 * @return string Pesan tentang PGNI
 */
function format_tentang_whatsapp($yayasan = null) {
    global $conn;
    
    $message = "🏛️ *Tentang PGNI Lampung*\n\n";
    $message .= "Persatuan Guru Ngaji Indonesia (PGNI) Provinsi Lampung\n";
    $message .= "adalah organisasi profesi yang menghimpun para\n";
    $message .= "guru ngaji, ustadz/ustadzah, dan pengajar Al-Qur'an.\n\n";
    
    if ($yayasan && !empty($yayasan['visi'])) {
        $visi = strip_tags($yayasan['visi']);
        $visi = strlen($visi) > 200 ? substr($visi, 0, 200) . '...' : $visi;
        $message .= "📋 *Visi:*\n";
        $message .= "{$visi}\n\n";
    }
    
    // Ambil pengurus
    $query = "SELECT nama, jabatan FROM pengurus 
              WHERE status = 'aktif' 
              ORDER BY urutan ASC LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $message .= "👥 *Pengurus:*\n";
        while ($row = mysqli_fetch_assoc($result)) {
            $message .= "• {$row['nama']} - *{$row['jabatan']}*\n";
        }
        $message .= "\n";
    }
    
    $message .= "🔗 *Website:*\n";
    $message .= "https://www.pgni.net/pgnil/\n\n";
    $message .= "📌 *Media Sosial:*\n";
    $message .= "📱 Instagram: @pgni_lampung\n";
    $message .= "📱 Facebook: PGNI Lampung";
    
    return $message;
}

/**
 * Log aktivitas WhatsApp
 * 
 * @param string $target Nomor target
 * @param string $message Pesan
 * @param string $response Response
 * @param int $httpCode HTTP Code
 */
function log_whatsapp_activity($target, $message, $response, $httpCode) {
    $logDir = __DIR__ . '/../chatbot/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/whatsapp_activity.log';
    $log = date('Y-m-d H:i:s') . " | To: $target | HTTP: $httpCode | Msg: " . substr($message, 0, 200) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}

/**
 * Generate kode OTP sederhana untuk WhatsApp
 * 
 * @param int $length Panjang OTP
 * @return string Kode OTP
 */
function generate_otp($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= rand(0, 9);
    }
    return $otp;
}

/**
 * Kirim OTP via WhatsApp
 * 
 * @param string $target Nomor tujuan
 * @param string $otp Kode OTP
 * @return array Hasil pengiriman
 */
function send_otp_whatsapp($target, $otp) {
    $message = "🔐 *Kode Verifikasi PGNI Lampung*\n\n";
    $message .= "Kode OTP Anda adalah:\n";
    $message .= "*{$otp}*\n\n";
    $message .= "Kode berlaku selama 5 menit.\n";
    $message .= "Jangan berikan kode ini kepada siapapun.\n\n";
    $message .= "📌 Jika Anda tidak meminta kode ini, abaikan pesan ini.";
    
    return send_whatsapp_message($target, $message);
}

/**
 * Reset password member via WhatsApp
 * 
 * @param string $nik NIK member
 * @param string $newPassword Password baru (opsional)
 * @return bool|string Password baru jika berhasil, false jika gagal
 */
function reset_password_member($nik, $newPassword = null) {
    global $conn;
    
    if ($newPassword === null) {
        $newPassword = 'pgnilampung';
    }
    
    $nik = mysqli_real_escape_string($conn, $nik);
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE guru_ngaji SET password = '$passwordHash' WHERE nik = '$nik'";
    if (mysqli_query($conn, $query)) {
        return $newPassword;
    }
    return false;
}

/**
 * Cek login member
 * 
 * @param string $nik NIK
 * @param string $password Password
 * @return array|false Data member jika login berhasil
 */
function verify_member_login($nik, $password) {
    global $conn;
    
    $nik = mysqli_real_escape_string($conn, $nik);
    $query = "SELECT * FROM guru_ngaji WHERE nik = '$nik'";
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            return $row;
        }
    }
    return false;
}

/**
 * Get menu WhatsApp
 * 
 * @return string Menu teks
 */
function get_whatsapp_menu() {
    return "
🌙 *Assalamu'alaikum Warahmatullahi Wabarakatuh*

Selamat datang di *PGNI Lampung Bot* 🤖

📋 *Menu Layanan:*
0️⃣ ️Informasi & Bantuan
1️⃣ ️Registrasi Member Baru
2️⃣ ️Cek Status Pendaftaran
3️⃣ ️Perbaharui Data Member
4️⃣ ️Login Member Area
5️⃣ ️Informasi & Berita
6️⃣ ️Donasi
7️⃣ ️Lokasi Kantor
8️⃣ ️Tentang PGNI

📌 Ketik *menu* kapan saja untuk kembali ke menu utama
📌 Ketik *batal* untuk membatalkan proses
";
}

/**
 * Get daftar bank untuk WhatsApp
 * 
 * @return array Daftar bank
 */
function get_bank_list() {
    return ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'Lampung', 'CIMB Niaga', 'Danamon', 'Permata', 'SeaBank', 'DANA', 'OVO', 'Lainnya'];
}

/**
 * Get daftar tempat mengajar untuk WhatsApp
 * 
 * @return array Daftar tempat mengajar
 */
function get_tempat_mengajar_list() {
    return [
        '1' => 'Rumah Pribadi',
        '2' => 'TPA (Taman Pendidikan Al-Qur\'an)',
        '3' => 'MDTA (Madrasah Diniyah Takmiliyah)',
        '4' => 'Pondok Pesantren',
        '5' => 'Masjid/Musholla',
        '6' => 'Yayasan',
        '7' => 'Lainnya'
    ];
}

/**
 * Validasi NIK
 * 
 * @param string $nik NIK
 * @return bool True jika valid
 */
function validate_nik($nik) {
    return preg_match('/^[0-9]{16}$/', $nik);
}

/**
 * Validasi nomor telepon
 * 
 * @param string $phone Nomor telepon
 * @return bool True jika valid
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

