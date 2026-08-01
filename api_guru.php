<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$where_clause = "";

if ($user_id > 0) {
    // Get user role
    $res_user = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
    if ($u = mysqli_fetch_assoc($res_user)) {
        $role = $u['role'];

        if ($role === 'admin') {
            // Filter by kabupaten assigned to this admin
            $query_wilayah = "SELECT kabupaten_id FROM user_wilayah_akses WHERE user_id = $user_id AND kabupaten_id IS NOT NULL";
            $res_wilayah = mysqli_query($conn, $query_wilayah);
            $kab_ids = [];
            while ($w = mysqli_fetch_assoc($res_wilayah)) {
                $kab_ids[] = $w['kabupaten_id'];
            }

            if (!empty($kab_ids)) {
                $where_clause = "WHERE g.kabupaten_id IN (" . implode(',', $kab_ids) . ")";
            }
        } elseif ($role === 'petugas_kecamatan') {
            // Filter by kecamatan assigned to this petugas
            $res_p = mysqli_query($conn, "SELECT kecamatan_id FROM users WHERE id = $user_id");
            if ($p = mysqli_fetch_assoc($res_p)) {
                $kec_id = (int)$p['kecamatan_id'];
                $where_clause = "WHERE g.kecamatan_id = $kec_id";
            }
        }
    }
}

$query = "SELECT g.*,
                 k.nama as kecamatan_nama,
                 kab.nama as kabupaten_nama,
                 d.nama as desa_nama
          FROM guru_ngaji g
          LEFT JOIN kecamatan k ON g.kecamatan_id = k.id
          LEFT JOIN kabupaten kab ON g.kabupaten_id = kab.id
          LEFT JOIN desa d ON g.desa_id = d.id
          $where_clause
          ORDER BY g.created_at DESC";

$result = mysqli_query($conn, $query);
$guru_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    $guru_list[] = [
        'id' => (int)$row['id'],
        'nik' => $row['nik'],
        'nama' => $row['nama'],
        'no_telp' => $row['no_telp'],
        'tempat_mengajar' => $row['tempat_mengajar'],
        'kabupaten' => $row['kabupaten_nama'],
        'kecamatan' => $row['kecamatan_nama'],
        'desa' => $row['desa_nama'],
        'ktp_file' => $row['ktp_file'],
        'kk_file' => $row['kk_file'],
        'status' => $row['status'],
        'status_verifikasi' => $row['status_verifikasi']
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $guru_list
]);
?>
