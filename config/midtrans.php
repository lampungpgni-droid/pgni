<?php
/**
 * Konfigurasi Midtrans Production
 */

// ============================================
// GANTI DENGAN KEY DARI DASHBOARD MIDTRANS ANDA
// ============================================
define('MIDTRANS_SERVER_KEY', 'Mid-server-ENxxxx'); // GANTI DENGAN SERVER KEY ANDA
define('MIDTRANS_CLIENT_KEY', 'Mid-client-GIWxxxxx'); // GANTI DENGAN CLIENT KEY ANDA
define('MIDTRANS_IS_PRODUCTION', true); // Set ke true untuk production

// ============================================
// API URL
// ============================================
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_SNAP_URL', 'https://app.midtrans.com/snap/snap.js');
    define('MIDTRANS_API_URL', 'https://api.midtrans.com/v2');
} else {
    define('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js');
    define('MIDTRANS_API_URL', 'https://api.sandbox.midtrans.com/v2');
}

// ============================================
// LOAD MIDTRANS LIBRARY
// ============================================
require_once __DIR__ . '/../vendor/autoload.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$clientKey = MIDTRANS_CLIENT_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// ============================================
// FUNGSI UNTUK DEBUG
// ============================================
function midtrans_debug($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $log .= ' - ' . json_encode($data);
    }
    error_log($log);
    // Optional: simpan ke file
    // file_put_contents(__DIR__ . '/../midtrans_debug.log', $log . PHP_EOL, FILE_APPEND);
}