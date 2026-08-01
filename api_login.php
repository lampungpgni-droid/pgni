<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Get JSON body from Retrofit
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Username and Password required']);
    exit;
}

$username = mysqli_real_escape_string($conn, $username);
$query = "SELECT * FROM users WHERE username = '$username' OR email = '$username' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($user = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $user['password'])) {
        $role = $user['role'];
        $wilayah = 'Seluruh Wilayah';

        if ($role === 'petugas_kecamatan') {
            $kec_id = $user['kecamatan_id'] ?? 0;
            $query_kec = "SELECT nama FROM kecamatan WHERE id = $kec_id";
            $res_kec = mysqli_query($conn, $query_kec);
            if ($kec = mysqli_fetch_assoc($res_kec)) {
                $wilayah = $kec['nama'];
            }
        } elseif ($role === 'admin') {
            // Get from user_wilayah_akses
            $user_id = $user['id'];
            $query_w = "SELECT k.nama as kab, kec.nama as kec FROM user_wilayah_akses wa
                        LEFT JOIN kabupaten k ON wa.kabupaten_id = k.id
                        LEFT JOIN kecamatan kec ON wa.kecamatan_id = kec.id
                        WHERE wa.user_id = $user_id LIMIT 1";
            $res_w = mysqli_query($conn, $query_w);
            if ($w = mysqli_fetch_assoc($res_w)) {
                $wilayah = $w['kec'] ?: ($w['kab'] ?: 'Wilayah Terbatas');
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Login Berhasil',
            'data' => [
                'id' => (int)$user['id'],
                'nama' => $user['nama_lengkap'],
                'email' => $user['email'],
                'level' => $role,
                'wilayah' => $wilayah
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Password salah']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan']);
}
?>
