<?php
/**
 * snap_payment_embed.php - Pembayaran dengan embed iframe
 * Gunakan ini jika popup tidak muncul
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: donasi.php?status=error&msg=Order ID tidak valid');
    exit;
}

// Cek data donasi
$query = "SELECT * FROM donasi WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donasi = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$donasi) {
    header('Location: donasi.php?status=error&msg=Donasi tidak ditemukan');
    exit;
}

$title = 'Pembayaran Donasi - PGNI Lampung';
include __DIR__ . '/include/header.php';
?>

<style>
.payment-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.1);
    text-align: center;
}
.payment-container h2 {
    color: #1a6e3a;
    margin-bottom: 5px;
    font-size: 1.5rem;
}
.payment-container .amount {
    font-size: 2.2rem;
    font-weight: 700;
    color: #d4a847;
    margin: 10px 0 20px;
}
.payment-iframe {
    width: 100%;
    min-height: 500px;
    border: none;
    border-radius: 12px;
    background: #f8f9fa;
}
.loading-spinner {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    gap: 15px;
}
.loading-spinner i {
    font-size: 3rem;
    color: #1a6e3a;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.btn-back {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 30px;
    background: #1a6e3a;
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
}
.btn-back:hover {
    background: #0e4a26;
    color: #fff;
}
</style>

<div class="container">
    <div class="payment-container">
        <h2>💳 Selesaikan Pembayaran</h2>
        <div class="amount">Rp <?php echo number_format($donasi['jumlah'], 0, ',', '.'); ?></div>
        
        <div class="loading-spinner" id="loadingSpinner">
            <i class="fas fa-spinner"></i>
            <p>Memuat halaman pembayaran...</p>
        </div>
        
        <iframe 
            id="paymentIframe"
            class="payment-iframe"
            src="https://app.midtrans.com/snap/v2/<?php echo $donasi['payment_url']; ?>"
            style="display:none;"
            allow="payment *"
        ></iframe>
        
        <a href="<?php echo BASE_URL; ?>donasi.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('paymentIframe');
    const loading = document.getElementById('loadingSpinner');
    
    // Tampilkan iframe setelah dimuat
    iframe.addEventListener('load', function() {
        loading.style.display = 'none';
        iframe.style.display = 'block';
    });
    
    // Tampilkan iframe setelah 2 detik (fallback)
    setTimeout(function() {
        loading.style.display = 'none';
        iframe.style.display = 'block';
    }, 3000);
});
</script>

<?php include __DIR__ . '/include/footer.php'; ?>