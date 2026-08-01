<?php
// provinsi.php - Manajemen Provinsi
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: admin/login.php');
    exit;
}

// Include database
require_once 'config/database.php';

// Koneksi database menggunakan variabel global dari database.php
global $conn;

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data pengguna untuk role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Cek hak akses (hanya super_admin yang bisa mengelola provinsi)
if ($user_role !== 'super_admin') {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!'); window.location.href='admin/dashboard.php';</script>";
    exit;
}

// Proses tambah data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah') {
        $nama = trim($_POST['nama']);
        if (!empty($nama)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO provinsi (nama) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $nama);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = 'Provinsi berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $nama = trim($_POST['nama']);
        if (!empty($nama) && $id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE provinsi SET nama = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $nama, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = 'Provinsi berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        }
    } elseif ($_POST['action'] === 'hapus' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        // Cek apakah provinsi memiliki relasi di kabupaten
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM kabupaten WHERE provinsi_id = ?");
        mysqli_stmt_bind_param($check, "i", $id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count > 0) {
            $_SESSION['message'] = 'Provinsi tidak dapat dihapus karena masih memiliki data kabupaten terkait!';
            $_SESSION['message_type'] = 'danger';
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM provinsi WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = 'Provinsi berhasil dihapus!';
            $_SESSION['message_type'] = 'success';
        }
    }
}

// Ambil semua data provinsi
$query = "SELECT * FROM provinsi ORDER BY id";
$result = mysqli_query($conn, $query);
$provinsi_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $provinsi_list[] = $row;
}

// Cek pesan dari session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Provinsi - PGNI Lampung</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .header h1 small {
            font-size: 14px;
            font-weight: 400;
            opacity: 0.8;
            display: block;
            margin-top: 4px;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s;
            font-size: 14px;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Card */
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            padding: 18px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1b5e20;
        }

        .card-body {
            padding: 25px;
        }

        /* Form */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-primary {
            background: #2e7d32;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1b5e20;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            color: #495057;
        }

        tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #495057;
        }

        .empty-state p {
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            padding: 25px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-30px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1b5e20;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
            line-height: 1;
        }

        .modal-close:hover {
            color: #dc3545;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 20px;
            }

            .card-body {
                padding: 15px;
            }

            .form-row {
                flex-direction: column;
            }

            .form-row .form-group {
                min-width: 100%;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 8px 10px;
            }

            .action-buttons .btn {
                font-size: 11px;
                padding: 5px 10px;
            }

            .modal {
                padding: 20px;
                margin: 10px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 17px;
            }

            .header h1 small {
                font-size: 12px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            th, td {
                padding: 6px 8px;
                font-size: 12px;
            }

            .badge {
                font-size: 10px;
                padding: 2px 8px;
            }
        }

        /* Print */
        @media print {
            .btn, .btn-back, .form-group, .action-buttons {
                display: none !important;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .header {
                background: #2e7d32 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>
                    📋 Manajemen Provinsi
                    <small>Kelola data provinsi di wilayah Lampung</small>
                </h1>
            </div>
            <div>
                <a href="admin/dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
            </div>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> show" id="alertMessage">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('alertMessage');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }
            }, 4000);
        </script>
        <?php endif; ?>

        <!-- Form Tambah -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Tambah Provinsi Baru</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formTambah">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Provinsi</label>
                            <input type="text" id="nama" name="nama" placeholder="Contoh: Lampung" required>
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end;">
                            <button type="submit" class="btn btn-success">Simpan Provinsi</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card">
            <div class="card-header">
                <h2>📌 Daftar Provinsi</h2>
                <span class="badge badge-primary">Total: <?= count($provinsi_list) ?> Provinsi</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Nama Provinsi</th>
                                <th style="width:200px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($provinsi_list) > 0): ?>
                                <?php $no = 1; foreach ($provinsi_list as $provinsi): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($provinsi['nama']) ?></strong></td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-warning btn-sm" onclick="openEditModal(<?= $provinsi['id'] ?>, '<?= addslashes(htmlspecialchars($provinsi['nama'])) ?>')">
                                                ✏️ Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $provinsi['id'] ?>, '<?= addslashes(htmlspecialchars($provinsi['nama'])) ?>')">
                                                🗑️ Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <div class="icon">📭</div>
                                            <h3>Belum Ada Data Provinsi</h3>
                                            <p>Silakan tambahkan provinsi pertama Anda melalui form di atas.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3>✏️ Edit Provinsi</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="" id="formEdit">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_nama">Nama Provinsi</label>
                    <input type="text" id="edit_nama" name="nama" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background:#6c757d;color:#fff;" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3>🗑️ Konfirmasi Hapus</h3>
                <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p style="margin-bottom: 10px; font-size: 15px;">Apakah Anda yakin ingin menghapus provinsi <strong id="delete_nama"></strong>?</p>
            <p style="color: #dc3545; font-size: 14px;">⚠️ Provinsi yang memiliki data kabupaten <strong>tidak dapat</strong> dihapus.</p>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#6c757d;color:#fff;" onclick="closeDeleteModal()">Batal</button>
                <form method="POST" action="" id="formDelete" style="display:inline;">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Edit Modal
        function openEditModal(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        // Delete Modal
        function confirmDelete(id, nama) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nama').textContent = nama;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Tutup modal dengan klik di luar
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });

        // Tombol ESC untuk menutup modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(function(modal) {
                    modal.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>