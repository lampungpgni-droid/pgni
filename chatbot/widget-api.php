<?php
// chatbot/widget-api.php
// API Endpoint untuk Widget Live Chat

// Set error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Header untuk response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log untuk debugging
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logError($msg) {
    global $logDir;
    file_put_contents($logDir . '/widget_api_error.log', date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND);
}

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include config
require_once __DIR__ . '/config.php';

// Cek koneksi database
if (!isset($conn) || !$conn) {
    logError("Database connection failed");
    echo json_encode(['status' => false, 'response' => '❌ Gagal terhubung ke database. Silakan coba lagi.']);
    exit;
}

// Include WebChatHandler
require_once __DIR__ . '/WebChatHandler.php';

// Inisialisasi response
$response = ['status' => false, 'response' => 'Invalid request'];

try {
    // GET request untuk polling
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        $userId = $_GET['user_id'] ?? '';
        
        if ($action === 'poll' && $userId) {
            $sessionFile = __DIR__ . '/web_sessions.json';
            if (file_exists($sessionFile)) {
                $sessions = json_decode(file_get_contents($sessionFile), true);
                if ($sessions && isset($sessions[$userId]['admin_messages'])) {
                    $messages = $sessions[$userId]['admin_messages'];
                    // Kosongkan setelah dibaca
                    if (!empty($messages)) {
                        $sessions[$userId]['admin_messages'] = [];
                        file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));
                    }
                    echo json_encode(['status' => true, 'messages' => $messages]);
                    exit;
                }
            }
            echo json_encode(['status' => true, 'messages' => []]);
            exit;
        }
        
        echo json_encode(['status' => false]);
        exit;
    }

    // POST request untuk chat
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        logError("Raw input: " . substr($rawInput, 0, 500));
        
        if (empty($rawInput)) {
            logError("Empty input received");
            echo json_encode(['status' => false, 'response' => '❌ Tidak ada data yang diterima.']);
            exit;
        }
        
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            logError("Invalid JSON: " . $rawInput);
            echo json_encode(['status' => false, 'response' => '❌ Format data tidak valid.']);
            exit;
        }
        
        $userId = $data['user_id'] ?? '';
        $userName = $data['user_name'] ?? 'Pengunjung';
        $message = trim($data['message'] ?? '');
        $sessionState = $data['session'] ?? 'menu';
        $tempData = $data['data'] ?? [];
        
        logError("Processing: userId=$userId, message=$message, state=$sessionState");
        
        if (empty($userId) || empty($message)) {
            logError("Missing fields: userId=" . (empty($userId) ? 'empty' : $userId) . ", message=" . (empty($message) ? 'empty' : $message));
            echo json_encode(['status' => false, 'response' => '❌ Data tidak lengkap.']);
            exit;
        }
        
        // Proses dengan WebChatHandler
        $handler = new WebChatHandler($conn);
        $result = $handler->processMessage($userId, $message, $userName, $sessionState, $tempData);
        
        logError("Result: " . json_encode($result));
        echo json_encode($result);
        exit;
    }
    
    echo json_encode(['status' => false, 'response' => 'Method not allowed']);

} catch (Exception $e) {
    logError("Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    logError("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => false,
        'response' => '❌ Terjadi kesalahan pada server. Silakan coba lagi nanti.',
        'error' => $e->getMessage()
    ]);
}