<?php
/**
 * webhook_midtrans.php - MIDTRANS HTTP NOTIFICATION HANDLER
 */

// Sembunyikan output error HTML agar tidak merusak HTTP Response Code 200 ke Midtrans
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ============================================
// 1. PATH CONFIG DATABASE & MIDTRANS
// ============================================
$root_path = __DIR__;

// Cari file config/database.php
$db_path = null;
$possible_db_paths = [
    $root_path . '/config/database.php',
    $root_path . '/../config/database.php',
    dirname($root_path) . '/config/database.php'
];

foreach ($possible_db_paths as $path) {
    if (file_exists($path)) {
        $db_path = $path;
        break;
    }
}

if (!$db_path) {
    file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . " - Error: Config database tidak ditemukan\n", FILE_APPEND);
    http_response_code(500);
    echo 'Database config not found';
    exit;
}

require_once $db_path;

// Cari file config/midtrans.php
$midtrans_path = null;
$possible_midtrans_paths = [
    $root_path . '/config/midtrans.php',
    $root_path . '/../config/midtrans.php',
    dirname($root_path) . '/config/midtrans.php'
];

foreach ($possible_midtrans_paths as $path) {
    if (file_exists($path)) {
        $midtrans_path = $path;
        break;
    }
}

if ($midtrans_path) {
    require_once $midtrans_path;
}

// ============================================
// 2. FUNGSI LOGGING
// ============================================
function logWebhook($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - " . json_encode($data);
    }
    file_put_contents(__DIR__ . '/webhook.log', $log . "\n", FILE_APPEND);
}

// ============================================
// 3. CEK REQUEST METHOD
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logWebhook('Method not allowed: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// ============================================
// 4. AMBIL INPUT PAYLOAD
// ============================================
$raw_input = file_get_contents('php://input');

if (empty($raw_input)) {
    logWebhook('Empty payload received');
    http_response_code(400);
    echo 'Empty payload';
    exit;
}

$payload = json_decode($raw_input, true);

if (!$payload) {
    logWebhook('Invalid JSON payload', ['raw' => $raw_input]);
    http_response_code(400);
    echo 'Invalid JSON';
    exit;
}

logWebhook('Payload received', $payload);

// ============================================
// 5. VERIFIKASI SIGNATURE KEY (SECURITY)
// ============================================
$order_id           = $payload['order_id'] ?? '';
$status_code        = $payload['status_code'] ?? '';
$gross_amount       = $payload['gross_amount'] ?? '';
$signature_key      = $payload['signature_key'] ?? '';
$transaction_status = $payload['transaction_status'] ?? '';
$fraud_status       = $payload['fraud_status'] ?? '';

if (defined('MIDTRANS_SERVER_KEY') && !empty(MIDTRANS_SERVER_KEY)) {
    $my_signature = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
    
    if ($signature_key !== $my_signature) {
        logWebhook('Invalid Signature Key', [
            'expected' => $my_signature,
            'received' => $signature_key
        ]);
        http_response_code(403);
        echo 'Invalid signature';
        exit;
    }
}

// ============================================
// 6. PENENTUAN STATUS TRANSAKSI
// ============================================
$db_status = 'pending';

if ($transaction_status == 'capture') {
    if ($fraud_status == 'challenge') {
        $db_status = 'challenge';
    } else if ($fraud_status == 'accept') {
        $db_status = 'settlement';
    }
} else if ($transaction_status == 'settlement') {
    $db_status = 'settlement';
} else if ($transaction_status == 'pending') {
    $db_status = 'pending';
} else if ($transaction_status == 'deny') {
    $db_status = 'deny';
} else if ($transaction_status == 'expire') {
    $db_status = 'expire';
} else if ($transaction_status == 'cancel') {
    $db_status = 'cancel';
} else if ($transaction_status == 'failure') {
    $db_status = 'failure';
} else {
    $db_status = $transaction_status;
}

// ============================================
// 7. UPDATE DATABASE
// ============================================
if (isset($conn) && $conn) {
    $query = "UPDATE donasi SET 
                status = ?,
                transaction_id = ?,
                payment_type = ?,
                response_raw = ?
              WHERE order_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        $transaction_id = $payload['transaction_id'] ?? null;
        $payment_type   = $payload['payment_type'] ?? null;
        $response_raw   = json_encode($payload);
        
        mysqli_stmt_bind_param($stmt, 'sssss', 
            $db_status, 
            $transaction_id, 
            $payment_type, 
            $response_raw, 
            $order_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            logWebhook('Update success', ['order_id' => $order_id, 'status' => $db_status]);
        } else {
            logWebhook('Execute failed', ['error' => mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        logWebhook('Prepare query failed', ['error' => mysqli_error($conn)]);
    }
} else {
    logWebhook('Database connection ($conn) is null or missing');
    http_response_code(500);
    echo 'Database connection error';
    exit;
}

// ============================================
// 8. RESPONSE KE MIDTRANS (WAJIB HTTP 200)
// ============================================
http_response_code(200);
echo 'OK';