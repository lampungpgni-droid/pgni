<?php
// Suggested api_berita.php for api/ folder
header('Content-Type: application/json');
require_once '../config/database.php';

$stmt = $db->prepare("SELECT id, judul, isi, gambar, created_at FROM berita WHERE status = 1 ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$berita = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($berita);
?>
