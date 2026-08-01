<?php
// include/image_helper.php - Helper untuk manipulasi gambar

function compressImage($source_path, $target_path, $quality = 80, $max_width = 800, $max_height = 800) {
    list($width, $height, $type) = getimagesize($source_path);
    
    // Hitung rasio
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio < 1) {
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    // Buat image baru
    $src = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    if (!$src) return false;
    
    // Resize
    $dst = imagecreatetruecolor($new_width, $new_height);
    
    // Jika PNG, pertahankan transparansi
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Simpan
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dst, $target_path, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dst, $target_path, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dst, $target_path);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($dst);
    
    return $result;
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function generateUniqueFilename($prefix, $extension) {
    return $prefix . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
}