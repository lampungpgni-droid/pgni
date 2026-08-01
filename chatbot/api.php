<?php
// chatbot/api.php - API Endpoint untuk Chatbot
// Versi sederhana dan stabil

// Matikan semua error output
error_reporting(0);
ini_set('display_errors', 0);

// Header untuk response JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// FUNGSI LOGGING SEDERHANA
// ============================================
function logApiError($msg) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/api_error.log', date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND);
}

// ============================================
// LOAD KONFIGURASI
// ============================================
require_once __DIR__ . '/config.php';

// Cek koneksi database
if (!isset($conn) || !$conn) {
    logApiError("Database connection failed");
    echo json_encode(['status' => false, 'response' => '❌ Koneksi database gagal.']);
    exit;
}

// ============================================
// HANDLE GET REQUEST (Polling)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $userId = $_GET['user_id'] ?? '';
    
    if ($action === 'poll' && $userId) {
        $sessionFile = __DIR__ . '/web_sessions.json';
        $messages = [];
        if (file_exists($sessionFile)) {
            $sessions = @json_decode(@file_get_contents($sessionFile), true);
            if ($sessions && isset($sessions[$userId]['admin_messages'])) {
                $messages = $sessions[$userId]['admin_messages'];
                // Kosongkan setelah dibaca
                if (!empty($messages)) {
                    $sessions[$userId]['admin_messages'] = [];
                    @file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));
                }
            }
        }
        echo json_encode(['status' => true, 'messages' => $messages]);
        exit;
    }
    
    echo json_encode(['status' => false]);
    exit;
}

// ============================================
// HANDLE POST REQUEST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = @file_get_contents('php://input');
    logApiError("Raw input: " . substr($rawInput, 0, 200));
    
    if (empty($rawInput)) {
        logApiError("Empty input");
        echo json_encode(['status' => false, 'response' => '❌ Data kosong.']);
        exit;
    }
    
    $data = @json_decode($rawInput, true);
    if (!$data) {
        logApiError("Invalid JSON: " . $rawInput);
        echo json_encode(['status' => false, 'response' => '❌ Format data tidak valid.']);
        exit;
    }
    
    $userId = $data['user_id'] ?? '';
    $userName = $data['user_name'] ?? 'Pengunjung';
    $message = trim($data['message'] ?? '');
    $sessionState = $data['session'] ?? 'menu';
    $tempData = $data['data'] ?? [];
    
    logApiError("Processing: userId=$userId, message=$message");
    
    if (empty($userId) || empty($message)) {
        logApiError("Missing fields");
        echo json_encode(['status' => false, 'response' => '❌ Data tidak lengkap.']);
        exit;
    }
    
    // ============================================
    // PROSES PESAN DENGAN HANDLER SEDERHANA
    // ============================================
    try {
        require_once __DIR__ . '/SimpleChatHandler.php';
        $handler = new SimpleChatHandler($conn);
        $result = $handler->processMessage($userId, $message, $userName, $sessionState, $tempData);
        
        logApiError("Result: " . json_encode($result));
        echo json_encode($result);
    } catch (Exception $e) {
        logApiError("Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo json_encode([
            'status' => false,
            'response' => '❌ Terjadi kesalahan. Silakan coba lagi.',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Method tidak dikenal
echo json_encode(['status' => false, 'response' => 'Method not allowed']);