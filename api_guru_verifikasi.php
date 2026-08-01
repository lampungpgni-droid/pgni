<?php
header('Content-Type: application/json');
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get JSON body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id = $data['id'] ?? 0;
$status = $data['status'] ?? ''; // 'disetujui', 'ditolak', 'perbaikan'

if ($id <= 0 || !in_array($status, ['disetujui', 'ditolak', 'perbaikan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

// Get guru data for WA template
$query_guru = "SELECT * FROM guru_ngaji WHERE id = $id";
$result_guru = mysqli_query($conn, $query_guru);
$guru_data = mysqli_fetch_assoc($result_guru);

if (!$guru_data) {
    echo json_encode(['status' => 'error', 'message' => 'Guru not found']);
    exit;
}

$wa_url = "";

if ($status === 'perbaikan') {
    // Pesan Minta Perbaikan Data (KTP/KK Tidak Jelas)
    $wa_number = cleanPhoneNumber($guru_data['no_telp']);
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $edit_link = $protocol . "://" . $host . "/pgnil/cek_status.php?nik=" . $guru_data['nik'] . "&no_telp=" . urlencode($guru_data['no_telp']);

    $message = "Assalamu'alaikum, Yth. Bapak/Ibu " . $guru_data['nama'] . "\n\n" .
                "Kami dari PGNI Lampung menginformasikan bahwa berkas dokumen (KTP/KK) yang Anda unggah *kurang jelas atau tidak terbaca*.\n\n" .
                "Mohon untuk mengunggah ulang foto dokumen yang lebih jelas melalui link berikut:\n" .
                $edit_link . "\n\n" .
                "Silakan klik tombol *'Perbarui Data'* pada halaman tersebut untuk upload ulang.\n\n" .
                "Terima kasih.\n\n" .
                "*PGNI Lampung*";

    $wa_url = "https://wa.me/{$wa_number}?text=" . urlencode($message);

    echo json_encode([
        'status' => 'success',
        'message' => 'Link perbaikan WA berhasil dibuat',
        'whatsapp_url' => $wa_url
    ]);
    exit;
} else {
    // Approve or Reject
    $query = "UPDATE guru_ngaji SET status_verifikasi = '$status', updated_at = NOW() WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        // Generate WA URL using functions.php
        sendVerificationWhatsApp($guru_data, $status);
        $wa_url = $_SESSION['whatsapp_guru_url'] ?? "";

        echo json_encode([
            'status' => 'success',
            'message' => 'Status verifikasi berhasil diperbarui',
            'whatsapp_url' => $wa_url
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database']);
    }
}
?>
