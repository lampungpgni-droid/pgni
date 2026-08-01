<?php
// chatbot/chat-handler.php
// Single handler for all chatbot requests

error_reporting(0);
ini_set('display_errors', 0);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// LOGGING
// ============================================
function writeLog($msg) {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($dir . '/chat.log', date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND);
}

// ============================================
// LOAD DATABASE CONFIGURATION
// ============================================
$databaseFile = __DIR__ . '/../config/database.php';
if (!file_exists($databaseFile)) {
    writeLog("Database file not found: " . $databaseFile);
    echo json_encode(['status' => false, 'response' => '❌ Konfigurasi database tidak ditemukan.']);
    exit;
}

require_once $databaseFile;

if (!isset($conn) || !$conn) {
    writeLog("Database connection failed");
    echo json_encode(['status' => false, 'response' => '❌ Koneksi database gagal.']);
    exit;
}

// ============================================
// LOAD MIDTRANS CONFIGURATION
// ============================================
$midtransFile = __DIR__ . '/../config/midtrans.php';
$midtransConfig = [];
if (file_exists($midtransFile)) {
    require_once $midtransFile;
} else {
    writeLog("Midtrans config file not found: " . $midtransFile);
}

// ============================================
// PROCESS REQUEST
// ============================================
$rawInput = file_get_contents('php://input');
writeLog("Input: " . substr($rawInput, 0, 300));

if (empty($rawInput)) {
    echo json_encode(['status' => false, 'response' => '❌ Tidak ada data.']);
    exit;
}

$inputData = json_decode($rawInput, true);
if (!$inputData) {
    writeLog("Invalid JSON");
    echo json_encode(['status' => false, 'response' => '❌ Format data salah.']);
    exit;
}

$userId = $inputData['user_id'] ?? '';
$userName = $inputData['user_name'] ?? 'Pengunjung';
$message = trim($inputData['message'] ?? '');

if (empty($userId) || empty($message)) {
    echo json_encode(['status' => false, 'response' => '❌ Data tidak lengkap.']);
    exit;
}

// ============================================
// SESSION MANAGEMENT
// ============================================
$sessionFile = __DIR__ . '/sessions.json';
$sessions = [];
if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $sessions = json_decode($content, true) ?: [];
}

$session = $sessions[$userId] ?? ['state' => 'menu', 'data' => []];
$state = $session['state'];
$data = $session['data'];

function saveSession($userId, $state, $data) {
    global $sessionFile, $sessions;
    $sessions[$userId] = ['state' => $state, 'data' => $data];
    @file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));
}

// ============================================
// HELPER & RESPONSE FUNCTIONS
// ============================================
function sendResponse($text, $state, $data, $quickReplies = null) {
    global $userId;
    saveSession($userId, $state, $data);
    
    if ($quickReplies === null) {
        $quickReplies = ['menu', 'batal'];
        if ($state === 'menu') {
            $quickReplies = ['1', '2', '3', '4', '5', '6', '7', '8', 'menu'];
        } elseif ($state === 'registrasi_4') {
            $quickReplies = ['1', '2', '3', '4', '5', '6', '7'];
        } elseif ($state === 'registrasi_profesi') {
            $quickReplies = ['1', '2', '3'];
        } elseif ($state === 'registrasi_5') {
            $quickReplies = ['skip'];
        } elseif ($state === 'registrasi_6') {
            $quickReplies = ['YA', 'TIDAK'];
        }
    }
    
    echo json_encode([
        'status' => true,
        'response' => $text,
        'session' => ['state' => $state, 'data' => $data],
        'quick_replies' => $quickReplies
    ]);
    exit;
}

function getMenuText() {
    return "📋 *Menu Utama PGNI Lampung Bot*\n\n" .
           "1️⃣ Registrasi Member\n" .
           "2️⃣ Cek Status Pendaftaran\n" .
           "3️⃣ Perbaharui Data Member\n" .
           "4️⃣ Login Member Area\n" .
           "5️⃣ Berita & Informasi\n" .
           "6️⃣ Donasi\n" .
           "7️⃣ Lokasi Kantor\n" .
           "8️⃣ Tentang PGNI\n\n" .
           "📌 Ketik nomor pilihan Anda.";
}

function getStatusText($status) {
    $map = [
        'pending' => '⏳ Menunggu Verifikasi',
        'disetujui' => '✅ Disetujui',
        'ditolak' => '❌ Ditolak'
    ];
    return $map[$status] ?? $status;
}

function isPhoneRegistered($phone, $conn) {
    $phone = mysqli_real_escape_string($conn, $phone);
    $query = "SELECT id, nama, nik, status_verifikasi FROM guru_ngaji WHERE no_telp = '$phone'";
    $result = mysqli_query($conn, $query);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : false;
}

function isNikRegistered($nik, $conn) {
    $nik = mysqli_real_escape_string($conn, $nik);
    $query = "SELECT id FROM guru_ngaji WHERE nik = '$nik'";
    $result = mysqli_query($conn, $query);
    return ($result && mysqli_num_rows($result) > 0);
}

function getStatusVerifikasi($nik, $conn) {
    $nik = mysqli_real_escape_string($conn, $nik);
    $query = "SELECT nama, nik, status_verifikasi, tempat_mengajar, alasan_ditolak FROM guru_ngaji WHERE nik = '$nik'";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $row['status_text'] = getStatusText($row['status_verifikasi']);
        return $row;
    }
    return null;
}

// Midtrans Payment Handler
function generateMidtransPaymentLink($amount, $email, $userId, $userName) {
    global $midtransConfig;
    
    if (empty($midtransConfig) || !isset($midtransConfig['server_key'])) {
        return "https://pgni.net/pgnil/donasi.php?nominal=" . $amount . "&email=" . urlencode($email);
    }
    
    $serverKey = $midtransConfig['server_key'];
    $environment = $midtransConfig['environment'] ?? 'production';
    $expiry = $midtransConfig['expiry'] ?? 60;
    
    $apiUrl = ($environment === 'production') 
        ? 'https://api.midtrans.com/v1/payment-links' 
        : 'https://api.sandbox.midtrans.com/v1/payment-links';
    
    $orderId = 'PGNI-DONASI-' . date('Ymd') . '-' . time() . '-' . rand(1000, 9999);
    
    $params = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)$amount
        ],
        'customer_details' => [
            'email' => $email,
            'first_name' => $userName,
            'user_id' => $userId
        ],
        'item_details' => [
            [
                'id' => 'DONASI-PGNI',
                'price' => (int)$amount,
                'quantity' => 1,
                'name' => 'Donasi PGNI Lampung'
            ]
        ],
        'expiry' => [
            'start_time' => date('Y-m-d H:i:s O'),
            'duration' => $expiry,
            'unit' => 'minutes'
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    writeLog("Midtrans Response Code: " . $httpCode);
    writeLog("Midtrans Response: " . substr($response, 0, 500));
    
    if (($httpCode === 200 || $httpCode === 201) && $response) {
        $result = json_decode($response, true);
        if (isset($result['payment_url'])) {
            saveTransaction($orderId, $userId, $userName, $email, $amount, $result['payment_url']);
            return $result['payment_url'];
        }
    }
    
    return "https://pgni.net/pgnil/donasi.php?nominal=" . $amount . "&email=" . urlencode($email) . "&order=" . $orderId;
}

function saveTransaction($orderId, $userId, $userName, $email, $amount, $paymentUrl) {
    global $conn;
    $orderId = mysqli_real_escape_string($conn, $orderId);
    $userId = mysqli_real_escape_string($conn, $userId);
    $userName = mysqli_real_escape_string($conn, $userName);
    $email = mysqli_real_escape_string($conn, $email);
    $paymentUrl = mysqli_real_escape_string($conn, $paymentUrl);
    
    $query = "INSERT INTO donasi (order_id, user_id, user_name, email, nominal, payment_url, status, created_at) 
              VALUES ('$orderId', '$userId', '$userName', '$email', $amount, '$paymentUrl', 'pending', NOW())";
    mysqli_query($conn, $query);
}

// ============================================
// GLOBAL ROUTING / CANCEL
// ============================================
$lowerMsg = strtolower($message);

if (in_array($lowerMsg, ['menu', '0', 'batal', 'cancel', 'reset'])) {
    saveSession($userId, 'menu', []);
    if (in_array($lowerMsg, ['batal', 'cancel'])) {
        sendResponse("✅ Proses dibatalkan.\n\n" . getMenuText(), 'menu', []);
    }
    sendResponse(getMenuText(), 'menu', []);
}

// ============================================
// STATE ROUTING
// ============================================
switch ($state) {
    case 'menu':
        switch ($lowerMsg) {
            case '1':
            case 'registrasi':
                $userData = isPhoneRegistered($userId, $conn);
                if ($userData) {
                    sendResponse(
                        "⚠️ Anda sudah terdaftar dengan NIK: *{$userData['nik']}*\n" .
                        "Status: " . getStatusText($userData['status_verifikasi']) . "\n\n" .
                        "Jika ingin memperbaharui data, ketik *3*",
                        'menu', []
                    );
                }
                saveSession($userId, 'registrasi_1', []);
                sendResponse(
                    "📝 *Registrasi Member PGNI Lampung*\n\n" .
                    "1️⃣ *Nama Lengkap*\n" .
                    "Silakan ketik nama lengkap Anda sesuai KTP.\n\n" .
                    "📌 Ketik *batal* untuk membatalkan",
                    'registrasi_1', []
                );
                break;
                
            case '2':
            case 'cek status':
            case 'cek':
                saveSession($userId, 'cek_status', []);
                sendResponse(
                    "🔍 *Cek Status Pendaftaran*\n\n" .
                    "Masukkan *NIK* (16 digit):\n\n📌 Ketik *batal*",
                    'cek_status', []
                );
                break;
                
            case '3':
            case 'update':
            case 'perbaharui':
                $userData = isPhoneRegistered($userId, $conn);
                if (!$userData) {
                    sendResponse("⚠️ Anda belum terdaftar. Ketik *1* untuk registrasi.", 'menu', []);
                }
                $link = "https://pgni.net/pgnil/cek_status.php?nik=" . urlencode($userData['nik']);
                sendResponse(
                    "📝 *Perbaharui Data*\n\n" .
                    "Klik link berikut untuk memperbaharui data:\n" .
                    "🔗 {$link}\n\n📌 Ketik *menu*",
                    'menu', []
                );
                break;
                
            case '4':
            case 'login':
                saveSession($userId, 'login_nik', []);
                sendResponse(
                    "🔐 *Login Member Area*\n\n" .
                    "Masukkan NIK (16 digit):\n\n📌 Ketik *batal*",
                    'login_nik', []
                );
                break;
                
            case '5':
            case 'berita':
            case 'info':
                $query = "SELECT id, judul, created_at FROM berita WHERE status = 1 ORDER BY created_at DESC LIMIT 5";
                $result = mysqli_query($conn, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $response = "📰 *Berita Terbaru*\n\n";
                    while ($row = mysqli_fetch_assoc($result)) {
                        $response .= "• *{$row['judul']}*\n";
                        $response .= "  📅 " . date('d/m/Y', strtotime($row['created_at'])) . "\n\n";
                    }
                    $response .= "🔗 https://www.pgni.net/pgnil/berita.php\n\n📌 Ketik *menu*";
                    sendResponse($response, 'menu', []);
                } else {
                    sendResponse("📰 Belum ada berita.\n\n📌 Ketik *menu*", 'menu', []);
                }
                break;
                
            case '6':
            case 'donasi':
                sendResponse(
                    "🤲 *Donasi PGNI Lampung*\n\n" .
                    "Terima kasih atas kebaikan hati Anda untuk berdonasi.\n\n" .
                    "💰 Silakan pilih nominal donasi:\n" .
                    "• Rp 10.000\n" .
                    "• Rp 25.000\n" .
                    "• Rp 50.000\n" .
                    "• Rp 100.000\n" .
                    "Atau ketik nominal lainnya (minimal Rp 5.000)\n\n" .
                    "📌 Ketik *batal* untuk membatalkan",
                    'donasi_nominal', [], ['10000', '25000', '50000', '100000', 'batal']
                );
                break;
                
            case '7':
            case 'lokasi':
            case 'kantor':
                sendResponse(
                    "📍 *Lokasi Kantor*\n\n" .
                    "Gg.Pondok No.16 Kel. Durian Payung\n" .
                    "Kec. Tanjung Karang Pusat, Bandar Lampung - 35116\n\n" .
                    "🗺️ https://maps.google.com/?q=Durian+Payung+Bandar+Lampung\n\n📌 Ketik *menu*",
                    'menu', []
                );
                break;
                
            case '8':
            case 'tentang':
                sendResponse(
                    "🏛️ *PGNI Lampung*\n\n" .
                    "Persatuan Guru Ngaji Indonesia Provinsi Lampung\n" .
                    "Organisasi profesi guru ngaji dan pengajar Al-Qur'an.\n\n" .
                    "🌐 https://www.pgni.net/pgnil/tentang.php\n\n📌 Ketik *menu*",
                    'menu', []
                );
                break;
                
            default:
                $userData = isPhoneRegistered($userId, $conn);
                if ($userData) {
                    sendResponse(
                        "👋 Halo *{$userData['nama']}*\n\n" .
                        "Anda terdaftar sebagai member.\n" .
                        "Status: " . getStatusText($userData['status_verifikasi']) . "\n\n" .
                        getMenuText(),
                        'menu', []
                    );
                } else {
                    sendResponse(
                        "Halo! 👋\n\nSaya asisten PGNI Lampung.\n\n" . getMenuText(),
                        'menu', []
                    );
                }
        }
        break;

    // ============================================
    // REGISTRATION FLOW
    // ============================================
    case 'registrasi_1':
        if (strlen($message) < 3) {
            sendResponse("❌ Nama terlalu pendek (min 3 huruf).\n\n📌 Ketik *batal*", 'registrasi_1', $data);
        }
        $data['nama'] = $message;
        sendResponse("✅ Nama: *{$message}*\n\n2️⃣ *NIK (16 digit)*\nSilakan ketik NIK Anda.\n\n📌 Ketik *batal*", 'registrasi_2', $data);
        break;

    case 'registrasi_2':
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            sendResponse("❌ NIK harus 16 digit angka.\n\n📌 Ketik *batal*", 'registrasi_2', $data);
        }
        if (isNikRegistered($message, $conn)) {
            sendResponse("⚠️ NIK *{$message}* sudah terdaftar.\n\n📌 Ketik *batal*", 'registrasi_2', $data);
        }
        $data['nik'] = $message;
        sendResponse("✅ NIK: *{$message}*\n\n3️⃣ *Nomor Telepon*\nSilakan ketik nomor telepon aktif.\n\n📌 Ketik *batal*", 'registrasi_3', $data);
        break;

    case 'registrasi_3':
        $phone = preg_replace('/[^0-9]/', '', $message);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            sendResponse("❌ Nomor telepon tidak valid (10-15 digit).\n\n📌 Ketik *batal*", 'registrasi_3', $data);
        }
        if (isPhoneRegistered($phone, $conn)) {
            sendResponse("⚠️ Nomor *{$phone}* sudah terdaftar.\n\n📌 Ketik *batal*", 'registrasi_3', $data);
        }
        $data['no_telp'] = $phone;
        sendResponse(
            "✅ Telepon: *{$phone}*\n\n4️⃣ *Tempat Mengajar*\nPilih:\n1️⃣ Rumah Pribadi\n2️⃣ TPA\n3️⃣ MDTA\n4️⃣ Ponpes\n5️⃣ Masjid\n6️⃣ Yayasan\n7️⃣ Lainnya\n\n📌 Ketik *batal*",
            'registrasi_4', $data, ['1', '2', '3', '4', '5', '6', '7', 'batal']
        );
        break;

    case 'registrasi_4':
        $tempatMap = [
            '1' => 'Rumah Pribadi',
            '2' => 'TPA',
            '3' => 'MDTA',
            '4' => 'Ponpes',
            '5' => 'Masjid',
            '6' => 'Yayasan',
            '7' => 'Lainnya'
        ];
        $tempat = $tempatMap[$message] ?? $message;
        $data['tempat_mengajar'] = $tempat;
        sendResponse("✅ Tempat Mengajar: *{$tempat}*\n\n5️⃣ *Email (Opsional)*\nKetik email Anda atau *skip* jika tidak ada.", 'registrasi_5', $data, ['skip', 'batal']);
        break;

    case 'registrasi_5':
        if (strtolower($message) !== 'skip') {
            if (!filter_var($message, FILTER_VALIDATE_EMAIL)) {
                sendResponse("❌ Format email tidak valid. Ketik email yang benar atau *skip*.", 'registrasi_5', $data, ['skip', 'batal']);
            }
            $data['email'] = $message;
        } else {
            $data['email'] = '';
        }

        $summary = "📋 *Konfirmasi Data Pendaftaran*\n\n" .
                   "• Nama: *{$data['nama']}*\n" .
                   "• NIK: *{$data['nik']}*\n" .
                   "• No. Telp: *{$data['no_telp']}*\n" .
                   "• Tempat Mengajar: *{$data['tempat_mengajar']}*\n" .
                   "• Email: *" . ($data['email'] ?: '-') . "*\n\n" .
                   "Apakah data sudah benar?\n\nKetik *YA* untuk memproses atau *TIDAK* untuk batal.";
        sendResponse($summary, 'registrasi_6', $data, ['YA', 'TIDAK']);
        break;

    case 'registrasi_6':
        if ($lowerMsg === 'ya') {
            $nama = mysqli_real_escape_string($conn, $data['nama']);
            $nik = mysqli_real_escape_string($conn, $data['nik']);
            $noTelp = mysqli_real_escape_string($conn, $data['no_telp']);
            $tempat = mysqli_real_escape_string($conn, $data['tempat_mengajar']);
            $email = mysqli_real_escape_string($conn, $data['email']);

            $query = "INSERT INTO guru_ngaji (nama, nik, no_telp, tempat_mengajar, email, status_verifikasi, created_at) 
                      VALUES ('$nama', '$nik', '$noTelp', '$tempat', '$email', 'pending', NOW())";
            
            if (mysqli_query($conn, $query)) {
                sendResponse("🎉 *Pendaftaran Berhasil!*\n\nData Anda telah berhasil dikirim dan sedang dalam status *Menunggu Verifikasi* oleh admin.\n\n📌 Ketik *menu* untuk kembali.", 'menu', []);
            } else {
                sendResponse("❌ Terjadi kesalahan sistem saat menyimpan data. Silakan coba lagi nanti.\n\n📌 Ketik *menu*", 'menu', []);
            }
        } else {
            sendResponse("✅ Pendaftaran dibatalkan.\n\n" . getMenuText(), 'menu', []);
        }
        break;

    // ============================================
    // CHECK STATUS & LOGIN FLOW
    // ============================================
    case 'cek_status':
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            sendResponse("❌ NIK harus 16 digit angka.\n\n📌 Ketik *batal*", 'cek_status', []);
        }
        $info = getStatusVerifikasi($message, $conn);
        if ($info) {
            $resp = "🔍 *Hasil Cek Status*\n\n" .
                    "• Nama: *{$info['nama']}*\n" .
                    "• NIK: *{$info['nik']}*\n" .
                    "• Tempat Mengajar: *{$info['tempat_mengajar']}*\n" .
                    "• Status: *{$info['status_text']}*\n";
            if ($info['status_verifikasi'] === 'ditolak' && !empty($info['alasan_ditolak'])) {
                $resp .= "• Catatan: {$info['alasan_ditolak']}\n";
            }
            $resp .= "\n📌 Ketik *menu*";
            sendResponse($resp, 'menu', []);
        } else {
            sendResponse("❌ NIK *{$message}* belum terdaftar dalam sistem.\n\n📌 Ketik *1* untuk pendaftaran baru atau *menu*.", 'menu', ['1', 'menu']);
        }
        break;

    case 'login_nik':
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            sendResponse("❌ NIK harus 16 digit angka.\n\n📌 Ketik *batal*", 'login_nik', []);
        }
        $info = getStatusVerifikasi($message, $conn);
        if ($info) {
            $link = "https://pgni.net/pgnil/member/login.php?nik=" . urlencode($info['nik']);
            sendResponse("🔐 *Akses Member Area*\n\nSilakan klik link berikut untuk mengakses halaman member Anda:\n🔗 {$link}\n\n📌 Ketik *menu*", 'menu', []);
        } else {
            sendResponse("❌ NIK tidak ditemukan. Ketik *1* untuk pendaftaran.", 'menu', ['1', 'menu']);
        }
        break;

// ============================================
// DONATION FLOW - SIMPLE FIX
// ============================================
case 'donasi_nominal':
    $amount = (int)preg_replace('/[^0-9]/', '', $message);
    if ($amount < 5000) {
        sendResponse("❌ Nominal minimal donasi adalah Rp 5.000.\n\nSilakan masukan nominal yang sesuai:", 'donasi_nominal', []);
    }
    $data['nominal'] = $amount;
    sendResponse("💰 Nominal: *Rp " . number_format($amount, 0, ',', '.') . "*\n\nSilakan ketik **Email** Anda untuk pengiriman bukti/instruksi pembayaran (atau ketik *skip*):", 'donasi_email', $data, ['skip', 'batal']);
    break;

case 'donasi_email':
    $email = (strtolower($message) !== 'skip' && filter_var($message, FILTER_VALIDATE_EMAIL)) 
        ? $message 
        : 'info@pgni.net';

    $amount = $data['nominal'];
    $paymentUrl = generateMidtransPaymentLink($amount, $email, $userId, $userName);

    // KIRIMKAN DENGAN TAG HTML SINKRON SAMA REGEX JS
    $text = "🤲 *PEMBAYARAN DONASI*\n\n" .
            "Nominal: *Rp " . number_format($amount, 0, ',', '.') . "*\n" .
            "Email: *{$email}*\n\n" .
            "⬇️ KLIK TOMBOL DI BAWAH UNTUK BAYAR:\n\n" .
            $paymentUrl . "\n\n" .
            "📌 Setelah selesai, ketik *menu* untuk kembali";

    sendResponse($text, 'menu', []);
    break;

    default:
        sendResponse(getMenuText(), 'menu', []);
}
?>