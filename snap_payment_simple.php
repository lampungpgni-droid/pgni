<?php
/**
 * snap_payment_simple.php - Halaman Pembayaran Snap Midtrans Resmi
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    die('<div style="text-align:center;padding:50px;font-family:sans-serif;"><h3>❌ Order ID tidak ditemukan</h3><br><a href="donasi.php">Kembali ke Donasi</a></div>');
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
    die('<div style="text-align:center;padding:50px;font-family:sans-serif;"><h3>❌ Donasi tidak ditemukan</h3><br><a href="donasi.php">Kembali ke Donasi</a></div>');
}

$snap_token = $donasi['payment_url'];
$client_key = MIDTRANS_CLIENT_KEY;
$snap_url   = MIDTRANS_SNAP_URL;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Donasi - PGNI Lampung</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container { max-width: 450px; width: 100%; }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 35px 25px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card .logo { margin-bottom: 15px; }
        .card .logo img { max-height: 50px; }
        .card h2 { 
            color: #1a6e3a; 
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        .card .subtitle { 
            color: #888; 
            font-size: 0.85rem;
        }
        .card .amount {
            font-size: 2.2rem;
            font-weight: 700;
            color: #d4a847;
            margin: 20px 0;
        }
        .card .donatur {
            color: #555;
            font-size: 0.95rem;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-pay {
            width: 100%;
            padding: 14px;
            background: #1a6e3a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 12px;
        }
        .btn-pay:hover { background: #0e4a26; }
        .btn-back {
            display: inline-block;
            color: #777;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-back:hover { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <img src="assets/images/logo/logo-pgni.png" alt="PGNI" onerror="this.style.display='none'">
            </div>
            <h2>💳 Pembayaran Donasi</h2>
            <p class="subtitle">PGNI Provinsi Lampung</p>
            
            <div class="donatur">
                <strong><?php echo htmlspecialchars($donasi['nama_donatur']); ?></strong>
            </div>
            
            <div class="amount">
                Rp <?php echo number_format($donasi['jumlah'], 0, ',', '.'); ?>
            </div>
            
            <button id="pay-button" class="btn-pay">
                <i class="fas fa-credit-card"></i> Bayar Sekarang
            </button>

            <div>
                <a href="donasi.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Batal & Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Script Midtrans Snap SDK -->
    <script src="<?php echo $snap_url; ?>" data-client-key="<?php echo $client_key; ?>"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const snapToken = '<?php echo $snap_token; ?>';
        const orderId   = '<?php echo $order_id; ?>';
        const payButton = document.getElementById('pay-button');

        function triggerPayment() {
            if (typeof snap !== 'undefined' && snapToken) {
                snap.pay(snapToken, {
                    onSuccess: function(result) {
                        console.log('Success:', result);
                        window.location.href = 'donasi.php?status=success&order_id=' + orderId;
                    },
                    onPending: function(result) {
                        console.log('Pending:', result);
                        window.location.href = 'donasi.php?status=pending&order_id=' + orderId;
                    },
                    onError: function(result) {
                        console.error('Error:', result);
                        alert('Pembayaran gagal atau terjadi kesalahan.');
                    },
                    onClose: function() {
                        console.log('Customer menutup popup tanpa menyelesaikan pembayaran');
                    }
                });
            } else {
                alert('Gagal memuat modul pembayaran Midtrans. Mohon refresh halaman.');
            }
        }

        // Jalankan popup otomatis saat halaman selesai dimuat
        triggerPayment();

        // Tombol manual jika popup ditutup atau diblokir browser
        payButton.addEventListener('click', function() {
            triggerPayment();
        });
    });
    </script>
</body>
</html>