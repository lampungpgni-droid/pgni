<?php
/**
 * proses_donasi.php - Proses pembuatan transaksi donasi via Midtrans
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
require_once __DIR__ . '/vendor/autoload.php';

// Tentukan BASE_URL dan pastikan tidak ada trailing slash (/) di akhir URL
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($script_path, '/admin') !== false) {
        $script_path = dirname($script_path);
    }
    define('BASE_URL', rtrim($protocol . $host . $script_path, '/'));
}

// Gunakan variabel pembersih untuk menjamin tidak ada double slash
// meskipun BASE_URL sudah didefinisikan dari file config/database.php dengan slash di ujungnya
$clean_base_url = rtrim(BASE_URL, '/');

// Header wajib JSON
header('Content-Type: application/json');

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ambil dan bersihkan data dari form
$nama_donatur = trim($_POST['nama_donatur'] ?? '');
$email        = trim($_POST['email'] ?? '');
$no_telepon   = trim($_POST['no_telepon'] ?? '');
$jumlah       = intval($_POST['jumlah'] ?? 0);
$pesan        = trim($_POST['pesan'] ?? '');

// Validasi kelengkapan data dan minimal nominal Rp 100
if (empty($nama_donatur) || empty($email) || empty($no_telepon) || $jumlah < 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap atau nominal kurang dari Rp 100']);
    exit;
}

// Generate Order ID unik
$order_id = 'DON-' . date('Ymd') . '-' . uniqid() . '-' . rand(100, 999);

// ============================================
// SIMPAN DATA DONASI KE DATABASE (PENDING)
// ============================================
$query = "INSERT INTO donasi (order_id, nama_donatur, email, no_telepon, jumlah, pesan, status) 
          VALUES (?, ?, ?, ?, ?, ?, 'pending')";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mempersiapkan query database']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ssssds', $order_id, $nama_donatur, $email, $no_telepon, $jumlah, $pesan);
mysqli_stmt_execute($stmt);
$donasi_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

if (!$donasi_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan data donasi ke database']);
    exit;
}

// ============================================
// BUAT PARAMS UNTUK MIDTRANS
// ============================================
$params = [
    'transaction_details' => [
        'order_id'     => $order_id,
        'gross_amount' => $jumlah,
    ],
    'customer_details' => [
        'first_name' => $nama_donatur,
        'email'      => $email,
        'phone'      => $no_telepon,
    ],
    'item_details' => [
        [
            'id'       => 'DONASI-PGNI',
            'price'    => $jumlah,
            'quantity' => 1,
            'name'     => 'Donasi PGNI Lampung',
        ]
    ],
    // Callback URLs - Menggunakan $clean_base_url agar bebas double slash
    'callbacks' => [
        'finish' => $clean_base_url . '/donasi.php?status=success&order_id=' . $order_id,
        'error'  => $clean_base_url . '/donasi.php?status=error',
    ]
];

try {
    // ============================================
    // BUAT SNAP TOKEN MIDTRANS
    // ============================================
    $snap_token = \Midtrans\Snap::getSnapToken($params);
    
    // ============================================
    // UPDATE TOKEN DI DATABASE
    // ============================================
    $query_update = "UPDATE donasi SET payment_url = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, 'si', $snap_token, $donasi_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }
    
    // ============================================
    // BUILD REDIRECT URL - Bebas double slash
    // ============================================
    $redirect_url = $clean_base_url . '/snap_payment_simple.php?order_id=' . $order_id;
    
    // ============================================
    // RETURN RESPONSE SUKSES
    // ============================================
    echo json_encode([
        'success'      => true,
        'order_id'     => $order_id,
        'snap_token'   => $snap_token,
        'redirect_url' => $redirect_url
    ]);
    
} catch (Exception $e) {
    // ============================================
    // LOG ERROR & UPDATE STATUS KE FAILURE
    // ============================================
    $error_message = $e->getMessage();
    error_log('Midtrans Error: ' . $error_message);
    
    $query_fail = "UPDATE donasi SET status = 'failure', response_raw = ? WHERE id = ?";
    $stmt_fail = mysqli_prepare($conn, $query_fail);
    if ($stmt_fail) {
        $error_json = json_encode(['error' => $error_message]);
        mysqli_stmt_bind_param($stmt_fail, 'si', $error_json, $donasi_id);
        mysqli_stmt_execute($stmt_fail);
        mysqli_stmt_close($stmt_fail);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Gagal memproses donasi: ' . $error_message
    ]);
}