<?php
// chatbot/config.php

// 1. Matikan output error langsung ke browser agar format JSON Fonnte tidak terganggu
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buat folder logs jika belum ada
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// 2. Import file database utama dari root (config/database.php)
$databaseFile = __DIR__ . '/../config/database.php';

if (file_exists($databaseFile)) {
    require_once $databaseFile;
} else {
    file_put_contents($logDir . '/error_logs.txt', date('Y-m-d H:i:s') . " | CRITICAL: File config/database.php tidak ditemukan di path: {$databaseFile}\n", FILE_APPEND);
    die(json_encode(['status' => false, 'message' => 'Database configuration file missing.']));
}

// 3. Pastikan variabel koneksi database ($conn) terhubung
if (!isset($conn) || !$conn) {
    file_put_contents($logDir . '/error_logs.txt', date('Y-m-d H:i:s') . " | ERROR: Variabel \$conn dari config/database.php tidak valid atau gagal terhubung.\n", FILE_APPEND);
    die(json_encode(['status' => false, 'message' => 'Database connection failed.']));
}

// Set karakter encoding ke utf8mb4 agar mendukung emoji WhatsApp (1️⃣, 📋, dll.)
mysqli_set_charset($conn, "utf8mb4");

// 4. Konfigurasi Fonnte & Aplikasi Bot
// Konfigurasi Web Chat
if (!defined('WEB_CHAT_SESSION_LIFETIME')) {
    define('WEB_CHAT_SESSION_LIFETIME', 3600); // 1 jam
}

if (!defined('WEB_CHAT_MAX_HISTORY')) {
    define('WEB_CHAT_MAX_HISTORY', 100); // Maksimal pesan tersimpan
}

if (!defined('WEB_CHAT_ALLOW_GUEST')) {
    define('WEB_CHAT_ALLOW_GUEST', true);
}