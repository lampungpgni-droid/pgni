<?php
// upload/upload_handler.php
// Handler utama untuk upload file dengan kompresi

session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// ============================================
// KONFIGURASI
// ============================================
$config = [
    'max_size' => 64 * 1024 * 1024, // 64MB
    'max_width' => 1920,
    'max_height' => 1080,
    'quality' => 80,
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'allowed_video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
];

// ============================================
// FUNGSI UTILITY
// ============================================

/**
 * Generate nama file sesuai format
 */
function generate_filename($nik, $nama, $folder, $extension) {
    $clean_nama = preg_replace('/[^a-zA-Z0-9]/', '_', $nama);
    $clean_nama = trim($clean_nama, '_');
    $timestamp = time();
    return $clean_nama . '_' . $nik . '_' . $folder . '_' . $timestamp . '.' . $extension;
}

/**
 * Get extension dari mime type
 */
function get_extension_from_mime($mime_type) {
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
    ];
    return $map[$mime_type] ?? 'jpg';
}

/**
 * Kompresi gambar menggunakan GD Library
 */
function compress_image_gd($file_path, $mime_type, $max_width, $max_height, $quality) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $info = getimagesize($file_path);
    if (!$info) {
        return false;
    }
    
    $width = $info[0];
    $height = $info[1];
    
    // Calculate new dimensions
    if ($width > $max_width || $height > $max_height) {
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    // Create image resource
    $image = null;
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($file_path);
            }
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($mime_type === 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $file_path, $quality);
            break;
        case 'image/png':
            $result = imagepng($new_image, $file_path, 8);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $result = imagewebp($new_image, $file_path, $quality);
            }
            break;
        case 'image/gif':
            $result = imagegif($new_image, $file_path);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}

/**
 * Simpan base64 image
 */
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
    
    $extension = get_extension_from_mime($mime);
    
    // Generate filename: NAMA_NIK_folder_timestamp.extension
    $nama_file = generate_filename($nik, $nama, $folder, $extension);
    
    // Path
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $folder . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
    }
    
    $file_path = $upload_dir . $nama_file;
    
    // Save file
    if (file_put_contents($file_path, $data) !== false) {
        // Kompresi jika gambar
        if (strpos($mime, 'image/') === 0) {
            compress_image_gd($file_path, $mime, 1920, 1080, 80);
        }
        return $nama_file;
    }
    
    return false;
}

// ============================================
// PROSES REQUEST
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ============================================
// 1. UPLOAD FILE (via $_FILES)
// ============================================
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => '', 'data' => null];
    
    // Cek file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Tidak ada file yang diupload.';
        echo json_encode($response);
        exit;
    }
    
    $file = $_FILES['file'];
    $folder = isset($_POST['folder']) ? $_POST['folder'] : 'temp';
    $nik = isset($_POST['nik']) ? $_POST['nik'] : 'unknown';
    $nama = isset($_POST['nama']) ? $_POST['nama'] : 'unknown';
    
    // Validasi ukuran
    if ($file['size'] > $config['max_size']) {
        $response['message'] = 'Ukuran file terlalu besar. Maksimal 64MB.';
        echo json_encode($response);
        exit;
    }
    
    // Validasi tipe
    $mime_type = mime_content_type($file['tmp_name']);
    $allowed = array_merge($config['allowed_types'], $config['allowed_video']);
    if (!in_array($mime_type, $allowed)) {
        $response['message'] = 'Tipe file tidak diizinkan.';
        echo json_encode($response);
        exit;
    }
    
    $extension = get_extension_from_mime($mime_type);
    $nama_file = generate_filename($nik, $nama, $folder, $extension);
    
    // Path upload
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $folder . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $upload_path = $upload_dir . $nama_file;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $response['message'] = 'Gagal menyimpan file.';
        echo json_encode($response);
        exit;
    }
    
    // Kompresi jika gambar
    if (strpos($mime_type, 'image/') === 0) {
        compress_image_gd($upload_path, $mime_type, $config['max_width'], $config['max_height'], $config['quality']);
    }
    
    $response['status'] = 'success';
    $response['message'] = 'File berhasil diupload.';
    $response['data'] = [
        'filename' => $nama_file,
        'path' => '/assets/images/' . $folder . '/' . $nama_file,
        'size' => filesize($upload_path)
    ];
    
    echo json_encode($response);
    exit;
}

// ============================================
// 2. UPLOAD BASE64 (dari kamera)
// ============================================
if ($action === 'base64' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => '', 'data' => null];
    
    $base64 = isset($_POST['base64']) ? $_POST['base64'] : '';
    $folder = isset($_POST['folder']) ? $_POST['folder'] : 'temp';
    $nik = isset($_POST['nik']) ? $_POST['nik'] : 'unknown';
    $nama = isset($_POST['nama']) ? $_POST['nama'] : 'unknown';
    
    if (empty($base64)) {
        $response['message'] = 'Tidak ada data base64.';
        echo json_encode($response);
        exit;
    }
    
    $result = save_base64_image($base64, $folder, $nik, $nama);
    
    if (!$result) {
        $response['message'] = 'Gagal menyimpan file.';
        echo json_encode($response);
        exit;
    }
    
    $response['status'] = 'success';
    $response['message'] = 'File berhasil diupload.';
    $response['data'] = [
        'filename' => $result,
        'path' => '/assets/images/' . $folder . '/' . $result
    ];
    
    echo json_encode($response);
    exit;
}

// ============================================
// 3. DELETE FILE
// ============================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    
    $folder = isset($_POST['folder']) ? $_POST['folder'] : '';
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    
    if (empty($folder) || empty($filename)) {
        $response['message'] = 'Folder dan filename wajib diisi.';
        echo json_encode($response);
        exit;
    }
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $folder . '/' . $filename;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            $response['status'] = 'success';
            $response['message'] = 'File berhasil dihapus.';
        } else {
            $response['message'] = 'Gagal menghapus file.';
        }
    } else {
        $response['message'] = 'File tidak ditemukan.';
    }
    
    echo json_encode($response);
    exit;
}

// ============================================
// 4. GET FILE INFO
// ============================================
if ($action === 'info' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = ['status' => 'error', 'message' => '', 'data' => null];
    
    $folder = isset($_GET['folder']) ? $_GET['folder'] : '';
    $filename = isset($_GET['filename']) ? $_GET['filename'] : '';
    
    if (empty($folder) || empty($filename)) {
        $response['message'] = 'Folder dan filename wajib diisi.';
        echo json_encode($response);
        exit;
    }
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $folder . '/' . $filename;
    
    if (!file_exists($file_path)) {
        $response['message'] = 'File tidak ditemukan.';
        echo json_encode($response);
        exit;
    }
    
    $response['status'] = 'success';
    $response['data'] = [
        'filename' => $filename,
        'path' => '/assets/images/' . $folder . '/' . $filename,
        'size' => filesize($file_path),
        'mime' => mime_content_type($file_path),
        'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/assets/images/' . $folder . '/' . $filename
    ];
    
    echo json_encode($response);
    exit;
}

// ============================================
// DEFAULT RESPONSE
// ============================================
echo json_encode([
    'status' => 'error',
    'message' => 'Aksi tidak dikenali.',
    'available_actions' => ['upload', 'base64', 'delete', 'info']
]);
?>