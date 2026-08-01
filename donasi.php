<?php
// admin/donasi.php - Manajemen Donasi di Admin Panel

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';
require_once __DIR__ . '/../include/functions.php';

// ============================================
// FUNGSI CEK STATUS LANGSUNG KE MIDTRANS API
// ============================================
function update_status_from_midtrans($conn, $order_id) {
    if (!defined('MIDTRANS_SERVER_KEY') || empty(MIDTRANS_SERVER_KEY)) {
        return ['success' => false, 'message' => 'MIDTRANS_SERVER_KEY belum dikonfigurasi.'];
    }
    
    $is_prod = defined('MIDTRANS_IS_PRODUCTION') ? MIDTRANS_IS_PRODUCTION : false;
    $api_url = $is_prod 
        ? "https://api.midtrans.com/v2/" . urlencode($order_id) . "/status"
        : "https://api.sandbox.midtrans.com/v2/" . urlencode($order_id) . "/status";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200 || !$response) {
        return ['success' => false, 'message' => "Order ID tidak ditemukan di Midtrans (HTTP $http_code)."];
    }

    $payload = json_decode($response, true);
    if (!$payload || !isset($payload['transaction_status'])) {
        return ['success' => false, 'message' => 'Respon Midtrans tidak valid.'];
    }

    $transaction_status = $payload['transaction_status'];
    $fraud_status       = $payload['fraud_status'] ?? '';
    
    $db_status = 'pending';
    if ($transaction_status == 'capture') {
        $db_status = ($fraud_status == 'challenge') ? 'challenge' : 'settlement';
    } else {
        $db_status = $transaction_status;
    }

    $transaction_id = $payload['transaction_id'] ?? null;
    $payment_type   = $payload['payment_type'] ?? null;
    $response_raw   = json_encode($payload);

    $query = "UPDATE donasi SET 
                status = ?, 
                transaction_id = ?, 
                payment_type = ?, 
                response_raw = ? 
              WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssss', $db_status, $transaction_id, $payment_type, $response_raw, $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => true, 'status' => $db_status];
    }

    return ['success' => false, 'message' => 'Gagal memperbarui database.'];
}

// ============================================
// HANDLER SINKRONISASI MANUAL VIA URL ACTION
// ============================================
$msg = '';
$msg_type = '';

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'sync' && !empty($_GET['order_id'])) {
        $sync_id = $_GET['order_id'];
        $res = update_status_from_midtrans($conn, $sync_id);
        if ($res['success']) {
            $msg = "Status Order ID <strong>" . htmlspecialchars($sync_id) . "</strong> berhasil diperbarui menjadi <strong>" . strtoupper($res['status']) . "</strong>.";
            $msg_type = "success";
        } else {
            $msg = "Gagal sinkronisasi: " . htmlspecialchars($res['message']);
            $msg_type = "danger";
        }
    } elseif ($_GET['action'] === 'sync_pending') {
        $q = "SELECT order_id FROM donasi WHERE status = 'pending'";
        $res_pending = mysqli_query($conn, $q);
        $updated_count = 0;
        if ($res_pending) {
            while ($p_row = mysqli_fetch_assoc($res_pending)) {
                $sync_res = update_status_from_midtrans($conn, $p_row['order_id']);
                if ($sync_res['success'] && $sync_res['status'] !== 'pending') {
                    $updated_count++;
                }
            }
            $msg = "Proses selesai! <strong>$updated_count</strong> transaksi berhasil diperbarui statusnya dari Midtrans.";
            $msg_type = "success";
        }
    }
}

// Filter & Query Data
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];
$types = '';

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_filter)) {
    $where[] = "DATE(created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(order_id LIKE ? OR nama_donatur LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM donasi $where_clause ORDER BY created_at DESC LIMIT 50";
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$donasi_result = mysqli_stmt_get_result($stmt);

$donasi_list = [];
if ($donasi_result && mysqli_num_rows($donasi_result) > 0) {
    while ($row = mysqli_fetch_assoc($donasi_result)) {
        $donasi_list[] = $row;
    }
}
mysqli_stmt_close($stmt);

// Summary stats
$stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('settlement', 'capture') THEN jumlah ELSE 0 END) as total_donasi,
                    COUNT(CASE WHEN status IN ('settlement', 'capture') THEN 1 END) as success_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
                FROM donasi";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$title = 'Manajemen Donasi';
include_once __DIR__ . '/include/admin_header.php'; 
?>

<style>
    .dashboard-content { padding: 0; }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .page-header-left h1 {
        font-size: 1.3rem; font-weight: 700; color: #1a1a2e; margin: 0;
        display: flex; align-items: center; gap: 10px;
    }
    .page-header-left h1 i { color: #d4a847; }
    .page-header-left .subtitle { color: #888; font-size: 0.85rem; margin-top: 4px; }
    .page-header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .header-badge {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 500;
        display: flex; align-items: center; gap: 8px;
    }
    .btn-sync-all {
        background: #0288d1; color: #fff; padding: 7px 16px; border-radius: 8px; font-size: 0.85rem;
        font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;
    }

    /* STATS ROW */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .stat-item {
        background: #fff; padding: 16px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex; align-items: center; gap: 14px;
    }
    .stat-item .icon {
        width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .stat-item .icon.green { background: rgba(26, 110, 58, 0.1); color: #1a6e3a; }
    .stat-item .icon.blue { background: rgba(52, 152, 219, 0.1); color: #3498db; }
    .stat-item .icon.yellow { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
    .stat-item .icon.purple { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
    .stat-item .info h3 { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; margin: 0; }
    .stat-item .info .label { font-size: 0.75rem; color: #999; text-transform: uppercase; }

    /* FILTER BAR */
    .filter-bar { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 18px 25px; margin-bottom: 25px; }
    .filter-bar .filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
    .filter-bar .filter-group { display: flex; align-items: center; gap: 8px; }
    .filter-bar .filter-group input, .filter-bar .filter-group select {
        border: 1px solid #e0e0e0; border-radius: 8px; padding: 7px 14px; font-size: 0.85rem; background: #fff;
    }
    .filter-actions { display: flex; gap: 8px; margin-left: auto; }
    .btn-filter { background: #1a6e3a; color: #fff; border: none; border-radius: 8px; padding: 7px 18px; font-size: 0.85rem; cursor: pointer; }
    .btn-reset { background: #f5f5f5; color: #666; border: none; border-radius: 8px; padding: 7px 18px; font-size: 0.85rem; text-decoration: none; }

    /* TABLE */
    .table-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
    .table-responsive { overflow-x: auto; }
    .table-transaction { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .table-transaction thead { background: #f8f9fa; border-bottom: 2px solid #e8e8e8; }
    .table-transaction thead th { padding: 14px 18px; text-align: left; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; color: #888; }
    .table-transaction tbody td { padding: 14px 18px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    
    .cell-order-id { font-family: monospace; font-size: 0.75rem; background: #f5f5f5; padding: 2px 8px; border-radius: 4px; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .status-dot.settlement, .status-dot.capture { background: #2ecc71; }
    .status-dot.pending { background: #f39c12; }
    .status-dot.deny, .status-dot.failure { background: #e74c3c; }
    .status-dot.cancel, .status-dot.expire { background: #95a5a6; }
    
    .status-label.settlement, .status-label.capture { color: #1a6e3a; font-weight: 600; }
    .status-label.pending { color: #f39c12; font-weight: 600; }

    .btn-detail { background: #f8f9fa; border: 1px solid #cbd5e1; border-radius: 6px; padding: 5px 12px; font-size: 0.75rem; color: #334155; cursor: pointer; transition: all 0.2s; }
    .btn-detail:hover { background: #d4a847; color: #fff; border-color: #d4a847; }
    .btn-sync { background: #e3f2fd; border: 1px solid #90caf9; border-radius: 6px; padding: 5px 12px; font-size: 0.75rem; color: #0d47a1; text-decoration: none; display: inline-block; }
    .btn-sync:hover { background: #1976d2; color: #fff; }

    /* CUSTOM POPUP MODAL (STANDALONE) */
    .custom-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px);
        display: none; justify-content: center; align-items: center; z-index: 9999;
    }
    .custom-modal-box {
        background: #fff; width: 90%; max-width: 550px; border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; animation: popIn 0.2s ease-out;
    }
    @keyframes popIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .custom-modal-header {
        padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .custom-modal-header h5 { margin: 0; font-size: 1rem; color: #0f172a; font-weight: 600; }
    .custom-modal-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b; }
    .custom-modal-body { padding: 20px; }
    .custom-modal-footer {
        padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: flex-end; gap: 10px;
    }
</style>

<div class="dashboard-content">
    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert" style="border-radius:10px; margin-bottom:20px;">
            <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-left">
            <h1><i class="fas fa-receipt"></i> Daftar Transaksi</h1>
            <div class="subtitle">Kelola semua data donasi yang masuk</div>
        </div>
        <div class="page-header-right">
            <a href="?action=sync_pending" class="btn-sync-all" onclick="return confirm('Proses sinkronisasi semua transaksi pending dari Midtrans?')">
                <i class="fas fa-rotate"></i> Sync Semua Pending
            </a>
            <span class="header-badge"><i class="fas fa-crown"></i> Super Admin</span>
        </div>
    </div>

    <!-- STATS ROW -->
    <div class="stats-row">
        <div class="stat-item">
            <div class="icon green"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="info">
                <h3>Rp <?php echo number_format($stats['total_donasi'] ?? 0, 0, ',', '.'); ?></h3>
                <span class="label">Total Terkumpul</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="icon blue"><i class="fas fa-check-circle"></i></div>
            <div class="info">
                <h3><?php echo $stats['success_count'] ?? 0; ?></h3>
                <span class="label">Berhasil</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="icon yellow"><i class="fas fa-clock"></i></div>
            <div class="info">
                <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                <span class="label">Pending</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="icon purple"><i class="fas fa-chart-simple"></i></div>
            <div class="info">
                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                <span class="label">Total Transaksi</span>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <form method="GET" class="filter-row">
            <div class="filter-group">
                <label><i class="fas fa-search"></i></label>
                <input type="text" name="search" placeholder="Cari Order ID / Donatur..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="settlement" <?php echo $status_filter === 'settlement' ? 'selected' : ''; ?>>Settlement</option>
                    <option value="capture" <?php echo $status_filter === 'capture' ? 'selected' : ''; ?>>Capture</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="cancel" <?php echo $status_filter === 'cancel' ? 'selected' : ''; ?>>Dibatalkan</option>
                    <option value="expire" <?php echo $status_filter === 'expire' ? 'selected' : ''; ?>>Kadaluarsa</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Tanggal</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                <a href="donasi.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table-transaction">
                <thead>
                    <tr>
                        <th>Tanggal & Waktu</th>
                        <th>Order ID</th>
                        <th>Jenis Transaksi</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th>E-mail Pelanggan</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($donasi_list)): ?>
                        <?php foreach ($donasi_list as $row): ?>
                        <tr>
                            <td>
                                <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                <div style="font-size:0.7rem; color:#999;"><?php echo date('H:i', strtotime($row['created_at'])); ?></div>
                            </td>
                            <td><span class="cell-order-id"><?php echo htmlspecialchars(substr($row['order_id'], 0, 20)) . '...'; ?></span></td>
                            <td>Pembayaran</td>
                            <td><?php echo htmlspecialchars(strtoupper($row['payment_type'] ?? 'QRIS')); ?></td>
                            <td>
                                <span class="status-dot <?php echo htmlspecialchars($row['status']); ?>"></span>
                                <span class="status-label <?php echo htmlspecialchars($row['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </span>
                            </td>
                            <td style="font-weight:bold; color:#1a6e3a;">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-detail" onclick="openModal('modal-<?php echo $row['id']; ?>')">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                <a href="?action=sync&order_id=<?php echo urlencode($row['order_id']); ?>" class="btn-sync" title="Cek Status dari Midtrans">
                                    <i class="fas fa-rotate"></i> Cek Status
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Belum ada transaksi</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- STANDALONE POP-UP MODAL DETIAL -->
<!-- ============================================ -->
<?php if (!empty($donasi_list)): ?>
    <?php foreach ($donasi_list as $row): ?>
    <div class="custom-modal-overlay" id="modal-<?php echo $row['id']; ?>">
        <div class="custom-modal-box">
            <div class="custom-modal-header">
                <h5><i class="fas fa-receipt me-2" style="color: #d4a847;"></i> Detail Transaksi</h5>
                <button type="button" class="custom-modal-close" onclick="closeModal('modal-<?php echo $row['id']; ?>')">&times;</button>
            </div>
            <div class="custom-modal-body">
                <div style="margin-bottom: 15px;">
                    <small style="color:#64748b; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Order ID</small>
                    <div style="font-family:monospace; background:#f1f5f9; padding:8px 12px; border-radius:6px; word-break:break-all; font-weight:600; margin-top:4px;">
                        <?php echo htmlspecialchars($row['order_id']); ?>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <small style="color:#64748b; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Nama Donatur</small>
                        <div style="font-weight:600; margin-top:2px;"><?php echo htmlspecialchars($row['nama_donatur']); ?></div>
                    </div>
                    <div>
                        <small style="color:#64748b; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Email</small>
                        <div style="margin-top:2px; word-break:break-all;"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></div>
                    </div>
                    <div>
                        <small style="color:#64748b; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Jumlah Donasi</small>
                        <div style="font-weight:700; color:#16a34a; font-size:1.05rem; margin-top:2px;">
                            Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div>
                        <small style="color:#64748b; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Status</small>
                        <div style="margin-top:2px;">
                            <span class="status-label <?php echo htmlspecialchars($row['status']); ?>" style="padding: 2px 8px; background: #f1f5f9; border-radius: 4px;">
                                <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="custom-modal-footer">
                <a href="?action=sync&order_id=<?php echo urlencode($row['order_id']); ?>" class="btn-sync" style="padding: 7px 14px; font-size:0.85rem;">
                    <i class="fas fa-rotate"></i> Sync Status Midtrans
                </a>
                <button type="button" class="btn-reset" onclick="closeModal('modal-<?php echo $row['id']; ?>')" style="cursor:pointer; padding: 7px 14px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- SCRIPT UNTUK MEMBUKA & MENUTUP MODAL -->
<script>
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Tutup modal jika user mengklik area gelap luar modal
window.onclick = function(event) {
    if (event.target.classList.contains('custom-modal-overlay')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include_once __DIR__ . '/include/admin_footer.php'; ?>