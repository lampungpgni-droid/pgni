<?php
// donasi.php - Halaman Donasi Publik

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

// Set title
$title = 'Donasi';
$meta_description = 'Donasi untuk mendukung program PGNI Lampung dalam membina guru ngaji dan pendidikan Al-Qur\'an';

// Include header
include __DIR__ . '/include/header.php';

// ============================================
// HANDLE PARAMETER DARI CHAT BOT
// ============================================
$chat_nominal = isset($_GET['nominal']) ? (int)$_GET['nominal'] : 0;
$chat_email = isset($_GET['email']) ? trim(urldecode($_GET['email'])) : '';
$chat_nama = isset($_GET['nama']) ? trim(urldecode($_GET['nama'])) : '';
$chat_phone = isset($_GET['phone']) ? trim(urldecode($_GET['phone'])) : '';
$chat_from = isset($_GET['from']) ? trim($_GET['from']) : '';

// BERSIHKAN EMAIL
$chat_email = preg_replace('/<br\s*\/?>/i', '', $chat_email);
$chat_email = str_replace(['📌', "\n", "\r", "\t"], '', $chat_email);
$chat_email = trim($chat_email);
$chat_email = filter_var($chat_email, FILTER_SANITIZE_EMAIL);

$isFromChat = ($chat_nominal > 0 && !empty($chat_email) && $chat_from === 'chat');

// Daftar nominal donasi
$nominal_donasi = [
    10000, 25000, 50000, 100000, 250000, 500000
];

// Baca donasi terbaru
$donasi_terbaru = [];
$query = "SELECT nama_donatur, jumlah, created_at FROM donasi 
          WHERE status IN ('settlement', 'capture') 
          ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $donasi_terbaru[] = $row;
    }
}
?>

<style>
/* HANYA STYLE TAMBAHAN UNTUK DONASI - TIDAK MENGUBAH HEADER */
.donasi-hero {
    background: linear-gradient(135deg, #1a6e3a, #2e7d32);
    padding: 40px 20px 30px;
    text-align: center;
    color: #fff;
}
.donasi-hero h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 10px;
}
.donasi-hero p {
    font-size: 1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 20px;
    line-height: 1.6;
}
.donasi-section {
    padding: 30px 15px 60px;
}
.donasi-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 25px 20px;
    max-width: 700px;
    margin: 0 auto;
}
.donasi-card h2 {
    text-align: center;
    color: #1a6e3a;
    margin-bottom: 8px;
    font-size: 1.3rem;
}
.donasi-card .subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 25px;
    font-size: 0.95rem;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
    font-size: 0.95rem;
}
.form-group label .required {
    color: red;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: 0.3s;
    font-family: inherit;
    background: #fff;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #2e7d32;
    outline: none;
    box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
}
.form-group textarea {
    resize: vertical;
    min-height: 80px;
}
.nominal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 8px;
    margin: 8px 0;
}
.nominal-btn {
    padding: 10px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: 0.2s;
    font-family: inherit;
    min-height: 44px;
}
.nominal-btn:hover {
    border-color: #2e7d32;
    background: rgba(46,125,50,0.05);
}
.nominal-btn.active {
    border-color: #2e7d32;
    background: #2e7d32;
    color: #fff;
}
.nominal-input-custom {
    margin-top: 10px;
    display: none;
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    font-family: inherit;
}
.nominal-input-custom.show {
    display: block;
}
.btn-donasi {
    width: 100%;
    padding: 14px;
    background: #d4a847;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    font-family: inherit;
}
.btn-donasi:hover {
    background: #c49a3a;
    box-shadow: 0 6px 25px rgba(212,168,71,0.3);
}
.btn-donasi:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.btn-donasi i {
    margin-right: 8px;
}
.donasi-terbaru {
    margin-top: 30px;
    background: #fff;
    border-radius: 16px;
    padding: 25px 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.donasi-terbaru h3 {
    color: #1a6e3a;
    margin-bottom: 15px;
    font-size: 1.1rem;
}
.donasi-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 5px;
}
.donasi-item:last-child {
    border-bottom: none;
}
.donasi-item .nama {
    font-weight: 500;
    font-size: 0.95rem;
}
.donasi-item .jumlah {
    font-weight: 700;
    color: #1a6e3a;
    font-size: 0.95rem;
}
.donasi-item .tanggal {
    font-size: 0.8rem;
    color: #999;
}
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 0.95rem;
}
.alert-danger {
    background: #fde8e8;
    color: #c0392b;
    border: 1px solid #f5c6cb;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.alert-chat {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 20px;
}
.alert-chat .chat-header {
    font-weight: 700;
    font-size: 1rem;
    color: #1b5e20;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-chat .chat-header .chat-icon {
    font-size: 22px;
}
.alert-chat .chat-body .row {
    display: flex;
    padding: 4px 0;
    border-bottom: 1px solid rgba(165, 214, 167, 0.3);
    flex-wrap: wrap;
}
.alert-chat .chat-body .row:last-child {
    border-bottom: none;
}
.alert-chat .chat-body .label {
    font-weight: 600;
    color: #2e7d32;
    min-width: 80px;
    flex-shrink: 0;
    font-size: 0.9rem;
}
.alert-chat .chat-body .value {
    font-weight: 500;
    color: #1b5e20;
    word-break: break-all;
    font-size: 0.9rem;
}
.alert-chat .chat-footer {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #a5d6a7;
    font-size: 0.85rem;
    color: #2e7d32;
}
.badge-chat {
    display: inline-block;
    background: #2e7d32;
    color: #fff;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
    margin-left: 6px;
    vertical-align: middle;
}
.donasi-kepercayaan {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 15px;
    flex-wrap: wrap;
}
.donasi-kepercayaan span {
    display: flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.9);
    font-size: 0.8rem;
}
.donasi-kepercayaan i {
    color: #d4a847;
    font-size: 1rem;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}
.text-muted {
    color: #999;
    font-size: 0.8rem;
    display: block;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .donasi-hero h1 {
        font-size: 1.6rem;
    }
    .donasi-card {
        padding: 20px 15px;
    }
    .nominal-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }
    .nominal-btn {
        font-size: 0.75rem;
        padding: 8px 4px;
        min-height: 40px;
    }
    .btn-donasi {
        font-size: 1rem;
        padding: 14px;
    }
    .alert-chat .chat-body .row {
        flex-direction: column;
        padding: 2px 0;
    }
    .alert-chat .chat-body .label {
        min-width: auto;
    }
}

@media (max-width: 400px) {
    .nominal-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- ============================================ -->
<!-- DONASI PAGE CONTENT -->
<!-- ============================================ -->
<section class="donasi-hero">
    <div class="container">
        <h1>🤲 Donasi untuk Guru Ngaji</h1>
        <p>Salurkan donasi Anda untuk mendukung program pembinaan, pemberdayaan, dan kesejahteraan guru ngaji di Provinsi Lampung.</p>
        <div class="donasi-kepercayaan">
            <span><i class="fas fa-shield-alt"></i> Aman</span>
            <span><i class="fas fa-lock"></i> Terenkripsi</span>
            <span><i class="fas fa-credit-card"></i> Payment Gateway</span>
        </div>
    </div>
</section>

<section class="donasi-section">
    <div class="container">
        <div class="donasi-card">
            <h2>
                Isi Form Donasi
                <?php if ($isFromChat): ?>
                <span class="badge-chat">🤖 Dari Chat</span>
                <?php endif; ?>
            </h2>
            <p class="subtitle">Donasi Anda sangat berarti bagi masa depan pendidikan Al-Qur'an di Lampung</p>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Terima kasih!</strong> Donasi Anda berhasil diproses.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'cancel'): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Donasi dibatalkan.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Silakan coba lagi.'; ?>
            </div>
            <?php endif; ?>

            <!-- ALERT DARI CHAT BOT -->
            <?php if ($isFromChat): ?>
            <div class="alert-chat">
                <div class="chat-header">
                    <span class="chat-icon">🤖</span>
                    <span>Dari PGNI Bot</span>
                </div>
                <div class="chat-body">
                    <div class="row">
                        <span class="label">💰 Nominal</span>
                        <span class="value">Rp <?php echo number_format($chat_nominal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="row">
                        <span class="label">📧 Email</span>
                        <span class="value"><?php echo htmlspecialchars($chat_email); ?></span>
                    </div>
                    <?php if (!empty($chat_nama)): ?>
                    <div class="row">
                        <span class="label">👤 Nama</span>
                        <span class="value"><?php echo htmlspecialchars($chat_nama); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($chat_phone)): ?>
                    <div class="row">
                        <span class="label">📱 Telepon</span>
                        <span class="value"><?php echo htmlspecialchars($chat_phone); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="chat-footer">
                    💡 Konfirmasi data, lalu klik "Donasi Sekarang"
                </div>
            </div>
            <?php endif; ?>
            
            <form id="donasiForm" method="POST" action="proses_donasi.php">
                <?php if ($isFromChat): ?>
                <input type="hidden" name="from_chat" value="true">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nama_donatur">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="nama_donatur" name="nama_donatur" placeholder="Masukkan nama lengkap" 
                           value="<?php echo $chat_nama ? htmlspecialchars($chat_nama) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email aktif" 
                           value="<?php echo $chat_email ? htmlspecialchars($chat_email) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="no_telepon">Nomor Telepon <span class="required">*</span></label>
                    <input type="tel" id="no_telepon" name="no_telepon" placeholder="Contoh: 08123456789" 
                           value="<?php echo $chat_phone ? htmlspecialchars($chat_phone) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Donasi <span class="required">*</span></label>
                    <div class="nominal-grid">
                        <?php 
                        $unique_nominal = array_unique($nominal_donasi);
                        foreach ($unique_nominal as $nominal): 
                        ?>
                        <button type="button" class="nominal-btn <?php echo ($chat_nominal == $nominal) ? 'active' : ''; ?>" data-nominal="<?php echo $nominal; ?>">
                            Rp <?php echo number_format($nominal, 0, ',', '.'); ?>
                        </button>
                        <?php endforeach; ?>
                        <button type="button" class="nominal-btn <?php echo ($chat_nominal > 0 && !in_array($chat_nominal, $nominal_donasi)) ? 'active' : ''; ?>" data-nominal="custom">Custom</button>
                    </div>
                    <input type="number" id="jumlah" name="jumlah" class="nominal-input-custom <?php echo ($chat_nominal > 0) ? 'show' : ''; ?>" 
                           placeholder="Masukkan nominal lainnya" 
                           min="10000" step="1000" 
                           value="<?php echo $chat_nominal > 0 ? $chat_nominal : ''; ?>">
                    <span class="text-muted">Minimal donasi Rp 10.000</span>
                </div>
                
                <div class="form-group">
                    <label for="pesan">Pesan / Doa (Opsional)</label>
                    <textarea id="pesan" name="pesan" placeholder="Tuliskan pesan atau doa untuk para guru ngaji..."><?php echo isset($_GET['pesan']) ? htmlspecialchars($_GET['pesan']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn-donasi" id="btnDonasi">
                    <i class="fas fa-hand-holding-heart"></i> Donasi Sekarang
                </button>
            </form>
        </div>
        
        <?php if (!empty($donasi_terbaru)): ?>
        <div class="donasi-terbaru">
            <h3><i class="fas fa-heart" style="color: #e74c3c;"></i> Donasi Terbaru</h3>
            <?php foreach ($donasi_terbaru as $donasi): ?>
            <div class="donasi-item">
                <span class="nama"><?php echo htmlspecialchars($donasi['nama_donatur']); ?></span>
                <span class="jumlah">Rp <?php echo number_format($donasi['jumlah'], 0, ',', '.'); ?></span>
                <span class="tanggal"><?php echo date('d M Y', strtotime($donasi['created_at'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// ============================================
// JAVASCRIPT SEDERHANA - TIDAK MENGGANGGU HEADER
// ============================================
(function() {
    'use strict';
    
    // Inisialisasi setelah DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        var nominalBtns = document.querySelectorAll('.nominal-btn');
        var jumlahInput = document.getElementById('jumlah');
        var form = document.getElementById('donasiForm');
        var btnDonasi = document.getElementById('btnDonasi');
        
        // Event untuk tombol nominal
        nominalBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Hapus active dari semua
                nominalBtns.forEach(function(b) {
                    b.classList.remove('active');
                });
                
                // Active tombol yang diklik
                this.classList.add('active');
                
                // Tampilkan input custom
                if (this.dataset.nominal === 'custom') {
                    jumlahInput.classList.add('show');
                    jumlahInput.value = '';
                    jumlahInput.focus();
                } else {
                    jumlahInput.classList.remove('show');
                    jumlahInput.value = this.dataset.nominal;
                }
            });
        });
        
        // Submit form
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var nama = document.getElementById('nama_donatur').value.trim();
                var email = document.getElementById('email').value.trim();
                var phone = document.getElementById('no_telepon').value.trim();
                var jumlah = parseInt(jumlahInput.value);
                
                // Validasi
                if (!nama || nama.length < 3) {
                    alert('Masukkan nama lengkap (minimal 3 karakter)');
                    document.getElementById('nama_donatur').focus();
                    return false;
                }
                
                if (!email || email.indexOf('@') === -1) {
                    alert('Masukkan email yang valid');
                    document.getElementById('email').focus();
                    return false;
                }
                
                if (!phone || phone.length < 10) {
                    alert('Masukkan nomor telepon yang valid (minimal 10 digit)');
                    document.getElementById('no_telepon').focus();
                    return false;
                }
                
                if (isNaN(jumlah) || jumlah < 10000) {
                    alert('Minimal donasi adalah Rp 10.000');
                    jumlahInput.focus();
                    return false;
                }
                
                // Loading
                btnDonasi.disabled = true;
                btnDonasi.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                
                var formData = new FormData(form);
                
                fetch('proses_donasi.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert('Gagal memproses donasi: ' + (data.error || 'Terjadi kesalahan.'));
                        btnDonasi.disabled = false;
                        btnDonasi.innerHTML = '<i class="fas fa-hand-holding-heart"></i> Donasi Sekarang';
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan koneksi atau server.');
                    btnDonasi.disabled = false;
                    btnDonasi.innerHTML = '<i class="fas fa-hand-holding-heart"></i> Donasi Sekarang';
                });
            });
        }
        
        // Jika dari chat, scroll ke form
        <?php if ($isFromChat): ?>
        setTimeout(function() {
            var card = document.querySelector('.donasi-card');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 500);
        <?php endif; ?>
        
        // Trigger klik untuk nominal dari chat
        <?php if ($chat_nominal > 0): ?>
        <?php if (in_array($chat_nominal, $nominal_donasi)): ?>
        var targetBtn = document.querySelector('.nominal-btn[data-nominal="<?php echo $chat_nominal; ?>"]');
        if (targetBtn) targetBtn.click();
        <?php else: ?>
        var customBtn = document.querySelector('.nominal-btn[data-nominal="custom"]');
        if (customBtn) customBtn.click();
        if (jumlahInput) jumlahInput.value = <?php echo $chat_nominal; ?>;
        <?php endif; ?>
        <?php endif; ?>
    }
    
})();
</script>

<?php include __DIR__ . '/include/footer.php'; ?>
</body>
</html>