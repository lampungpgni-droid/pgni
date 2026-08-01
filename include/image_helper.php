<?php
// include/image_helper.php

function get_image_url($filename, $folder) {
    if (empty($filename)) {
        return '';
    }
    
    // Base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Deteksi path
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Jika di admin, naik satu level
    if (strpos($script_path, '/admin') !== false) {
        $script_path = dirname($script_path);
    }
    
    // Hapus trailing slash
    $script_path = rtrim($script_path, '/');
    
    return $protocol . $host . $script_path . '/assets/images/' . $folder . '/' . $filename;
}

// ============================================
// FUNGSI UNTUK OG IMAGE (ABSOLUTE URL)
// ============================================
function get_og_image_absolute($filename, $folder = 'berita') {
    if (empty($filename)) {
        // Return logo default
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host . '/pgnil/assets/images/logo/logo-pgni.png';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Cek apakah file ada
    $root_path = dirname(__DIR__);
    $file_path = $root_path . '/assets/images/' . $folder . '/' . $filename;
    
    if (!file_exists($file_path)) {
        // Return logo default jika file tidak ada
        return $protocol . $host . '/pgnil/assets/images/logo/logo-pgni.png';
    }
    
    return $protocol . $host . '/pgnil/assets/images/' . $folder . '/' . $filename;
}

function show_image($filename, $folder, $alt = '', $class = '', $default = 'default.jpg') {
    if (empty($filename) || !file_exists(dirname(__DIR__) . '/assets/images/' . $folder . '/' . $filename)) {
        $filename = $default;
    }
    
    $url = get_image_url($filename, $folder);
    $alt = htmlspecialchars($alt);
    $class = htmlspecialchars($class);
    
    return '<img src="' . $url . '" alt="' . $alt . '" class="' . $class . '">';
}

function get_image_path($filename, $folder) {
    if (empty($filename)) {
        return '';
    }
    return dirname(__DIR__) . '/assets/images/' . $folder . '/' . $filename;
}

function delete_image($filename, $folder) {
    if (empty($filename)) {
        return true;
    }
    $path = get_image_path($filename, $folder);
    if (file_exists($path)) {
        return unlink($path);
    }
    return true;
}
?>