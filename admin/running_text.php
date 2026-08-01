<?php
// admin/running_text.php - Kelola Running Text
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hanya Super Admin yang bisa akses
$user_role = $_SESSION['role'] ?? '';
if ($user_role !== 'super_admin') {
    header('Location: dashboard_admin.php');
    exit;
}

$title = 'Kelola Running Text';
$message = '';
$error = '';

// ============================================
// PROSES TAMBAH RUNNING TEXT
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $teks = trim($_POST['teks'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    $urutan = intval($_POST['urutan'] ?? 0);
    
    if (empty($teks)) {
        $error = 'Teks tidak boleh kosong!';
    } else {
        $teks = mysqli_real_escape_string($conn, $teks);
        $status = mysqli_real_escape_string($conn, $status);
        
        $query = "INSERT INTO running_text (teks, status, urutan) VALUES ('$teks', '$status', $urutan)";
        if (mysqli_query($conn, $query)) {
            $message = 'Running text berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan: ' . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES EDIT RUNNING TEXT
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $teks = trim($_POST['teks'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    $urutan = intval($_POST['urutan'] ?? 0);
    
    if ($id <= 0 || empty($teks)) {
        $error = 'Data tidak valid!';
    } else {
        $teks = mysqli_real_escape_string($conn, $teks);
        $status = mysqli_real_escape_string($conn, $status);
        
        $query = "UPDATE running_text SET teks='$teks', status='$status', urutan=$urutan WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = 'Running text berhasil diperbarui!';
        } else {
            $error = 'Gagal memperbarui: ' . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES HAPUS RUNNING TEXT
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
    
    if ($confirm) {
        $query = "DELETE FROM running_text WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $message = 'Running text berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus: ' . mysqli_error($conn);
        }
    }
}

// ============================================
// AMBIL SEMUA RUNNING TEXT
// ============================================
$query = "SELECT * FROM running_text ORDER BY urutan ASC, id ASC";
$result = mysqli_query($conn, $query);
$running_texts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $running_texts[] = $row;
}

include 'include/admin_header.php';
?>

<div class="dashboard-content">
    <!-- Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-running"></i> Kelola Running Text</h1>
            <p>Kelola teks berjalan yang muncul di bagian atas halaman website</p>
        </div>
        <div class="header-right">
            <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-eye"></i> Lihat Website
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Tambah Running Text -->
        <div class="col-md-4">
            <div class="card card-form">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Tambah Running Text</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label>Teks</label>
                            <textarea name="teks" class="form-control" rows="3" placeholder="Masukkan teks berjalan..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" name="urutan" class="form-control" value="0" min="0">
                            <small class="form-text text-muted">Angka lebih kecil akan ditampilkan lebih dulu</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Running Text -->
        <div class="col-md-8">
            <div class="card card-list">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Running Text</h3>
                    <span class="badge badge-info"><?php echo count($running_texts); ?> item</span>
                </div>
                <div class="card-body">
                    <?php if (count($running_texts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Teks</th>
                                        <th width="100">Status</th>
                                        <th width="80">Urutan</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($running_texts as $rt): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <div class="running-text-preview">
                                                    <span class="text-badge"><?php echo htmlspecialchars($rt['teks']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $rt['status']; ?>">
                                                    <?php echo $rt['status'] == 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $rt['urutan']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-warning btn-edit" 
                                                            data-id="<?php echo $rt['id']; ?>"
                                                            data-teks="<?php echo htmlspecialchars($rt['teks']); ?>"
                                                            data-status="<?php echo $rt['status']; ?>"
                                                            data-urutan="<?php echo $rt['urutan']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo $rt['id']; ?>&confirm=yes" 
                                                       class="btn btn-sm btn-danger btn-delete"
                                                       onclick="return confirm('Yakin ingin menghapus running text ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-running" style="font-size: 3rem; color: #ddd;"></i>
                            <p>Belum ada running text</p>
                            <small>Tambahkan running text pertama Anda</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Running Text -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Running Text</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="form-group">
                        <label>Teks</label>
                        <textarea name="teks" id="edit_teks" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Urutan</label>
                        <input type="number" name="urutan" id="edit_urutan" class="form-control" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ===== PAGE HEADER ===== */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .page-header h1 {
        font-size: 1.5rem;
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    .page-header h1 i {
        color: #d4a847;
    }
    .page-header p {
        color: #666;
        margin: 0;
        font-size: 0.9rem;
    }
    .header-right .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    .btn-primary {
        background: #1a6e3a;
        color: #fff;
    }
    .btn-primary:hover {
        background: #0e4a26;
        color: #fff;
    }
    .btn-success {
        background: #28a745;
        color: #fff;
    }
    .btn-success:hover {
        background: #218838;
        color: #fff;
    }
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    .btn-warning:hover {
        background: #e0a800;
        color: #212529;
    }
    .btn-danger {
        background: #dc3545;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c82333;
        color: #fff;
    }
    .btn-block {
        width: 100%;
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
    }
    .btn-group {
        display: flex;
        gap: 5px;
    }

    /* ===== ALERT ===== */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
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
    .alert i {
        font-size: 1.2rem;
    }

    /* ===== CARDS ===== */
    .row {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 25px;
    }
    .card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .card-header {
        padding: 18px 25px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-header h3 i {
        color: #d4a847;
    }
    .card-body {
        padding: 25px;
    }

    /* ===== FORM ===== */
    .form-group {
        margin-bottom: 18px;
    }
    .form-group label {
        display: block;
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #fff;
        color: #333;
    }
    .form-control:focus {
        outline: none;
        border-color: #1a6e3a;
        box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
    }
    .form-control[readonly] {
        background: #f8f9fa;
        cursor: not-allowed;
    }
    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
    .form-text {
        font-size: 0.8rem;
        color: #999;
        margin-top: 4px;
    }
    .text-muted {
        color: #999;
    }

    /* ===== TABLE ===== */
    .table-responsive {
        overflow-x: auto;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th {
        background: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        border-bottom: 2px solid #dee2e6;
    }
    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .table-striped tbody tr:nth-child(odd) {
        background: #fafafa;
    }
    .table tbody tr:hover {
        background: #f0f8f0;
    }

    /* ===== RUNNING TEXT PREVIEW ===== */
    .running-text-preview {
        max-width: 300px;
    }
    .text-badge {
        display: inline-block;
        background: #f0f0f0;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* ===== STATUS BADGE ===== */
    .status-badge {
        display: inline-block;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    .status-badge.aktif {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.nonaktif {
        background: #f8d7da;
        color: #721c24;
    }
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
    .empty-state i {
        display: block;
        margin-bottom: 15px;
    }
    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 5px;
        color: #666;
    }
    .empty-state small {
        font-size: 0.85rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .row {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .table-responsive {
            font-size: 0.8rem;
        }
        .card-body {
            padding: 15px;
        }
        .btn-group {
            flex-wrap: wrap;
        }
        .running-text-preview {
            max-width: 150px;
        }
        .text-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
        }
    }
    @media (max-width: 480px) {
        .table th,
        .table td {
            padding: 8px 10px;
            font-size: 0.75rem;
        }
        .status-badge {
            font-size: 0.65rem;
            padding: 2px 8px;
        }
        .running-text-preview {
            max-width: 100px;
        }
    }

    /* ===== MODAL ===== */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .modal.show {
        display: flex;
    }
    .modal-dialog {
        max-width: 500px;
        width: 90%;
        margin: 20px auto;
    }
    .modal-content {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        overflow: hidden;
    }
    .modal-header {
        padding: 18px 25px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a1a2e;
    }
    .modal-header h5 i {
        color: #d4a847;
        margin-right: 8px;
    }
    .modal-header .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #999;
        padding: 0 5px;
        line-height: 1;
    }
    .modal-header .close:hover {
        color: #333;
    }
    .modal-body {
        padding: 25px;
    }
    .modal-footer {
        padding: 15px 25px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-secondary {
        background: #6c757d;
        color: #fff;
        padding: 8px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    .btn-secondary:hover {
        background: #5a6268;
        color: #fff;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // MODAL EDIT
    // ============================================
    const editModal = document.getElementById('editModal');
    const btnEdits = document.querySelectorAll('.btn-edit');
    const btnSaveEdit = document.getElementById('btnSaveEdit');

    // Fungsi buka modal
    function openEditModal(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_teks').value = data.teks;
        document.getElementById('edit_status').value = data.status;
        document.getElementById('edit_urutan').value = data.urutan;
        editModal.classList.add('show');
    }

    // Event click tombol edit
    btnEdits.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const data = {
                id: this.getAttribute('data-id'),
                teks: this.getAttribute('data-teks'),
                status: this.getAttribute('data-status'),
                urutan: this.getAttribute('data-urutan')
            };
            openEditModal(data);
        });
    });

    // Tutup modal saat klik di luar
    editModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });

    // Tutup modal dengan tombol close
    document.querySelectorAll('#editModal .close, #editModal .btn-secondary').forEach(function(el) {
        el.addEventListener('click', function() {
            editModal.classList.remove('show');
        });
    });

    // Simpan edit
    btnSaveEdit.addEventListener('click', function() {
        document.getElementById('editForm').submit();
    });

    // Enter key untuk submit edit
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && editModal.classList.contains('show')) {
            e.preventDefault();
            document.getElementById('editForm').submit();
        }
        if (e.key === 'Escape') {
            editModal.classList.remove('show');
        }
    });
});
</script>

<?php include 'include/admin_footer.php'; ?>