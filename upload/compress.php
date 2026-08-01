<?php
// upload/compress.php
// File untuk kompresi gambar menggunakan HTML5 Canvas + PHP

header('Content-Type: application/json');

// Konfigurasi
$max_size = 64 * 1024 * 1024; // 64MB
$max_width = 1920;
$max_height = 1080;
$quality = 80;

$response = ['status' => 'error', 'message' => '', 'data' => null];

// Cek apakah ada file yang diupload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Tidak ada file yang diupload atau terjadi error upload.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_type = $file['type'];

// Validasi ukuran file
if ($file_size > $max_size) {
    $response['message'] = 'Ukuran file terlalu besar. Maksimal 64MB.';
    echo json_encode($response);
    exit;
}

// Validasi tipe file
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file_type, $allowed_types)) {
    $response['message'] = 'Tipe file tidak diizinkan. Hanya gambar (JPG, PNG, WebP, GIF).';
    echo json_encode($response);
    exit;
}

// Generate nama file unik
$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$new_filename = time() . '_' . rand(1000, 9999) . '.' . $extension;

// Path upload
$upload_dir = '../assets/images/temp/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$upload_path = $upload_dir . $new_filename;

// Pindahkan file ke folder temp
if (!move_uploaded_file($file_tmp, $upload_path)) {
    $response['message'] = 'Gagal memindahkan file.';
    echo json_encode($response);
    exit;
}

// Kompresi gambar
$compressed = compress_image($upload_path, $file_type, $max_width, $max_height, $quality);

if (!$compressed) {
    $response['message'] = 'Gagal mengompresi gambar.';
    echo json_encode($response);
    exit;
}

// Baca file hasil kompresi ke base64
$image_data = file_get_contents($upload_path);
$base64 = base64_encode($image_data);
$mime_type = mime_content_type($upload_path);

$response['status'] = 'success';
$response['message'] = 'File berhasil diupload dan dikompresi.';
$response['data'] = [
    'filename' => $new_filename,
    'size' => filesize($upload_path),
    'base64' => 'data:' . $mime_type . ';base64,' . $base64,
    'original_size' => $file_size,
    'compressed_size' => filesize($upload_path)
];

echo json_encode($response);

/**
 * Fungsi kompresi gambar menggunakan GD Library
 */
function compress_image($file_path, $file_type, $max_width, $max_height, $quality) {
    // Cek apakah file ada
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Get image info
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
    
    // Create image resource based on file type
    $image = null;
    switch ($file_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($file_path);
            } else {
                return false;
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
    if ($file_type === 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save compressed image
    $result = false;
    switch ($file_type) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $file_path, $quality);
            break;
        case 'image/png':
            $png_quality = 8; // 0-9, 9 = best compression
            $result = imagepng($new_image, $file_path, $png_quality);
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
    
    // Free memory
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}
?>