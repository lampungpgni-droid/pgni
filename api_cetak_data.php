<?php
header('Content-Type: application/json');
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';

// Disable error display for production JSON, but log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$kabupaten = isset($_GET['kabupaten']) ? $_GET['kabupaten'] : '';
$kecamatan = isset($_GET['kecamatan']) ? $_GET['kecamatan'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$where_clause = "WHERE 1=1";
$role = '';

// Role-based filtering logic
if ($user_id > 0) {
    $res_user = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
    if ($res_user && $u = mysqli_fetch_assoc($res_user)) {
        $role = $u['role'];
        if ($role === 'admin') {
            $query_wilayah = "SELECT kabupaten_id FROM user_wilayah_akses WHERE user_id = $user_id AND kabupaten_id IS NOT NULL";
            $res_wilayah = mysqli_query($conn, $query_wilayah);
            $kab_ids = [];
            if ($res_wilayah) {
                while ($w = mysqli_fetch_assoc($res_wilayah)) {
                    $kab_ids[] = (int)$w['kabupaten_id'];
                }
            }
            if (!empty($kab_ids)) {
                $where_clause .= " AND g.kabupaten_id IN (" . implode(',', $kab_ids) . ")";
            }
        } elseif ($role === 'petugas_kecamatan') {
            $res_p = mysqli_query($conn, "SELECT kecamatan_id FROM users WHERE id = $user_id");
            if ($res_p && $p = mysqli_fetch_assoc($res_p)) {
                $kec_id = (int)$p['kecamatan_id'];
                if ($kec_id > 0) {
                    $where_clause .= " AND g.kecamatan_id = $kec_id";
                }
            }
        }
    }
}

// User-defined filters
if (!empty($kabupaten) && $kabupaten !== 'null') {
    $kab = mysqli_real_escape_string($conn, $kabupaten);
    $where_clause .= " AND kab.nama = '$kab'";
}
if (!empty($kecamatan) && $kecamatan !== 'null') {
    $kec = mysqli_real_escape_string($conn, $kecamatan);
    $where_clause .= " AND k.nama = '$kec'";
}
if (!empty($status) && $status !== 'null' && $status !== 'Semua') {
    $st = mysqli_real_escape_string($conn, $status);
    $where_clause .= " AND g.status_verifikasi = '$st'";
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
          ORDER BY kab.nama, k.nama, g.nama ASC";

$result = mysqli_query($conn, $query);
$report_list = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $report_list[] = [
            'id' => (int)$row['id'],
            'nik' => $row['nik'] ?: '',
            'nama' => $row['nama'] ?: '',
            'no_telp' => $row['no_telp'] ?: '',
            'tempat_mengajar' => $row['tempat_mengajar'] ?: '',
            'kabupaten' => $row['kabupaten_nama'] ?: '-',
            'kecamatan' => $row['kecamatan_nama'] ?: '-',
            'desa' => $row['desa_nama'] ?: '-',
            'jenis_profesi' => $row['jenis_profesi'] ?: '-',
            'bank' => $row['bank'] ?: '-',
            'no_rekening' => $row['no_rekening'] ?: '-',
            'status_verifikasi' => $row['status_verifikasi'] ?: 'pending'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'total' => count($report_list),
        'data' => $report_list
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Query error: ' . mysqli_error($conn)
    ]);
}
?>
