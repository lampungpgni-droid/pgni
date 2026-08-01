<?php
// ajax/ocr_scan.php - Menggunakan OCR.space API (Engine 2 - Optimal untuk NIK & Nama)
// API Key: 7c5626456288957

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// FUNGSI RESPONSE JSON
// ============================================
function sendJSON($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// ============================================
// CEK REQUEST & VALIDASI
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(false, 'Method tidak diizinkan');
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    sendJSON(false, 'Tidak ada file yang diupload');
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];

if (!in_array($file['type'], $allowedTypes)) {
    sendJSON(false, 'Tipe file tidak didukung. Gunakan: JPG, PNG, WebP');
}

if ($file['size'] > 10 * 1024 * 1024) {
    sendJSON(false, 'Ukuran file terlalu besar. Maksimal 10MB');
}

// ============================================
// PROSES OCR MENGGUNAKAN OCR.SPACE API
// ============================================
try {
    $apiKey = '7c5626456288957';
    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    
    // Menggunakan OCREngine 2 & 'eng' untuk akurasi angka NIK yang sangat tinggi
    $postData = [
        'apikey' => $apiKey,
        'base64Image' => 'data:' . $file['type'] . ';base64,' . $imageData,
        'language' => 'eng', 
        'isOverlayRequired' => 'false',
        'detectOrientation' => 'true',
        'scale' => 'true',
        'OCREngine' => '2' 
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('CURL Error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('HTTP Error: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        throw new Exception('Gagal parsing response API');
    }
    
    if (isset($result['IsErroredOnProcessing']) && $result['IsErroredOnProcessing'] === true) {
        $errorMsg = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'Unknown error';
        if (is_array($errorMsg)) {
            $errorMsg = implode(', ', $errorMsg);
        }
        throw new Exception('OCR Error: ' . $errorMsg);
    }
    
    $text = $result['ParsedResults'][0]['ParsedText'] ?? '';
    if (empty($text)) {
        throw new Exception('Hasil OCR kosong. Pastikan foto KTP tegak dan jelas.');
    }
    
    // Ekstrak data NIK, Nama, Kabupaten
    $data = extractKTPData($text);
    
    if (empty($data['nik']) && empty($data['nama'])) {
        sendJSON(false, 'Gagal mendeteksi NIK atau Nama. Silakan coba foto kembali dengan lebih dekat.', $data);
    } else {
        sendJSON(true, 'OCR berhasil', $data);
    }
    
} catch (Exception $e) {
    sendJSON(false, $e->getMessage());
}

// ============================================
// FUNGSI EKSTRAK SPESIFIK (NIK, NAMA, KABUPATEN)
// ============================================
function extractKTPData($text) {
    $data = [
        'nik' => '',
        'nama' => '',
        'kabupaten' => ''
    ];
    
    // Pembersihan noise karakter awal
    $text = str_replace(['|', '©', '®', '™'], ['', '', '', ''], $text);
    $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $text); 
    
    $lines = explode("\n", $text);
    $lines = array_filter(array_map(function($line) {
        return trim(preg_replace('/\s+/', ' ', $line));
    }, $lines));
    $lines = array_values($lines);
    
    // 1. Ekstrak NIK (Akurasi tinggi dengan pembersihan karakter OCR salah)
    foreach ($lines as $line) {
        if (preg_match('/NIK\s*[:>]?\s*([0-9lI\s]{16,20})/i', $line, $matches)) {
            $nikClean = str_replace(['l', 'I', 'i', 'o', 'O', ' '], ['1', '1', '1', '0', '0', ''], $matches[1]);
            $nikClean = preg_replace('/[^0-9]/', '', $nikClean);
            if (strlen($nikClean) >= 16) {
                $data['nik'] = substr($nikClean, 0, 16);
                break;
            }
        }
        // Cari fallback 16 digit angka beruntun
        if (preg_match('/\b(\d{16})\b/', $line, $matches)) {
            $data['nik'] = $matches[1];
            break;
        }
    }
    
    // 2. Ekstrak NAMA
    foreach ($lines as $i => $line) {
        if (preg_match('/Nama\s*[:>]?\s*(.+)/i', $line, $matches)) {
            $data['nama'] = trim($matches[1]);
            break;
        }
        if (preg_match('/^Nama$/i', trim($line)) && isset($lines[$i+1])) {
            $data['nama'] = trim($lines[$i+1]);
            break;
        }
    }
    
    // 3. Ekstrak KABUPATEN / KOTA
    foreach ($lines as $line) {
        if (preg_match('/(?:KABUPATEN|KOTA)\s*(.+)/i', $line, $matches)) {
            $data['kabupaten'] = trim($matches[1]);
            break;
        }
    }
    
    // Standardisasi Output (Hapus simbol tersisa & ubah ke UPPERCASE)
    foreach ($data as $key => $value) {
        $data[$key] = trim(preg_replace('/[^a-zA-Z0-9\s\-\.\/,]/', '', $value));
        $data[$key] = strtoupper($data[$key]);
    }
    
    return $data;
}
?>