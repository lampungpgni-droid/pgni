<?php
// member/absensi_scan.php - Scan QR Code untuk Absensi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['level'] == 'admin') {
    header('Location: login.php');
    exit;
}

$title = 'Scan QR Code Absensi';
$kegiatan_id = isset($_GET['kegiatan_id']) ? (int)$_GET['kegiatan_id'] : 0;
$guru_id = $_SESSION['user_id'];

// Ambil data kegiatan
$kegiatan = null;
if ($kegiatan_id > 0) {
    $query = "SELECT * FROM kegiatan WHERE id = $kegiatan_id AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    $kegiatan = mysqli_fetch_assoc($result);
}

// Cek sudah absen
$sudah_absen = false;
if ($kegiatan_id > 0 && $guru_id > 0) {
    $check_query = "SELECT * FROM absensi WHERE kegiatan_id = $kegiatan_id AND guru_id = $guru_id";
    $check_result = mysqli_query($conn, $check_query);
    $sudah_absen = mysqli_num_rows($check_result) > 0;
}

include $root_path . '/member/include/member_header.php';
?>

<style>
    .scan-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }
    .scan-card {
        background: #fff;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 30px rgba(0,0,0,0.08);
        text-align: center;
    }
    .scan-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 10px;
    }
    .scan-subtitle {
        color: #7f8c8d;
        font-size: 0.95rem;
        margin-bottom: 25px;
    }
    .qr-scanner {
        width: 100%;
        max-width: 400px;
        height: 400px;
        margin: 0 auto 20px;
        border: 2px dashed #d4a847;
        border-radius: 12px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    .qr-scanner video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .qr-scanner .placeholder {
        text-align: center;
        color: #999;
    }
    .qr-scanner .placeholder i {
        font-size: 4rem;
        color: #d4a847;
        display: block;
        margin-bottom: 10px;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
    }
    .btn-secondary {
        background: #95a5a6;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #7f8c8d;
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
        justify-content: center;
    }

    .info-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px 20px;
        margin: 15px 0;
        text-align: left;
    }
    .info-box .label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-box .value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1a1a2e;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
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
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-badge.success {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.warning {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.danger {
        background: #f8d7da;
        color: #721c24;
    }

    @media (max-width: 480px) {
        .scan-card {
            padding: 20px 15px;
        }
        .qr-scanner {
            height: 300px;
        }
    }
</style>

<div class="scan-container">
    <div class="scan-card">
        <h1 class="scan-title"><i class="fas fa-qrcode" style="color: #d4a847;"></i> Scan QR Code</h1>
        <p class="scan-subtitle">Arahkan kamera ke QR Code kegiatan untuk melakukan absensi</p>

        <?php if ($kegiatan): ?>
            <!-- Informasi Kegiatan -->
            <div class="info-box">
                <div class="label">Kegiatan</div>
                <div class="value"><?php echo htmlspecialchars($kegiatan['judul']); ?></div>
                <div style="margin-top: 8px;">
                    <span class="label">Tanggal & Waktu</span>
                    <div class="value" style="font-weight:500;font-size:0.85rem;">
                        <?php echo date('d/m/Y H:i', strtotime($kegiatan['tanggal_mulai'])); ?> - 
                        <?php echo date('H:i', strtotime($kegiatan['tanggal_selesai'])); ?>
                    </div>
                </div>
                <div style="margin-top: 8px;">
                    <span class="label">Lokasi</span>
                    <div class="value" style="font-weight:500;font-size:0.85rem;">
                        <?php echo htmlspecialchars($kegiatan['lokasi']); ?>
                    </div>
                </div>
            </div>

            <?php if ($sudah_absen): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Anda sudah melakukan absensi untuk kegiatan ini.
                </div>
                <a href="dashboard.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            <?php else: ?>
                <!-- QR Scanner -->
                <div class="qr-scanner" id="scanner">
                    <div class="placeholder" id="placeholderScanner">
                        <i class="fas fa-camera"></i>
                        <p>Klik tombol Start untuk memindai</p>
                    </div>
                    <video id="video" style="display:none;"></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                <!-- Tombol Aksi -->
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:15px;">
                    <button class="btn btn-primary" id="startScan" style="flex:1;">
                        <i class="fas fa-play"></i> Start Scan
                    </button>
                    <button class="btn btn-secondary" id="stopScan" style="flex:1;">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>

                <!-- Hasil Scan -->
                <div id="scanResult" style="margin-top:15px;"></div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Kegiatan tidak ditemukan atau sudah tidak aktif.
            </div>
            <a href="dashboard.php" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const scanner = document.getElementById('scanner');
    const placeholder = document.getElementById('placeholderScanner');
    const startBtn = document.getElementById('startScan');
    const stopBtn = document.getElementById('stopScan');
    const resultDiv = document.getElementById('scanResult');

    let stream = null;
    let scanning = false;
    let animationId = null;

    const kegiatanId = <?php echo $kegiatan_id; ?>;
    const guruId = <?php echo $guru_id; ?>;

    function startScanner() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            }).then(function(s) {
                stream = s;
                video.srcObject = stream;
                video.style.display = 'block';
                placeholder.style.display = 'none';
                video.play();
                scanning = true;
                startBtn.disabled = true;
                stopBtn.disabled = false;
                scanLoop();
            }).catch(function(err) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Tidak dapat mengakses kamera: ${err.message}
                    </div>
                `;
            });
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Browser Anda tidak mendukung akses kamera.
                </div>
            `;
        }
    }

    function stopScanner() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        scanning = false;
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        video.style.display = 'none';
        placeholder.style.display = 'block';
        startBtn.disabled = false;
        stopBtn.disabled = true;
    }

    function scanLoop() {
        if (!scanning) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });

            if (code && code.data) {
                try {
                    // Parse QR Code data
                    const data = JSON.parse(atob(code.data));
                    if (data.kegiatan_id && data.kegiatan_id == kegiatanId) {
                        // Valid QR Code untuk kegiatan ini
                        prosesAbsensi(data);
                        return;
                    }
                } catch(e) {
                    // QR Code tidak valid
                }
            }
        }

        animationId = requestAnimationFrame(scanLoop);
    }

    function prosesAbsensi(data) {
        stopScanner();
        
        // Dapatkan lokasi GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                kirimAbsensi(position.coords.latitude, position.coords.longitude);
            }, function() {
                // GPS tidak tersedia, lanjutkan tanpa lokasi
                kirimAbsensi(0, 0);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            kirimAbsensi(0, 0);
        }
    }

    function kirimAbsensi(lat, lon) {
        resultDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-spinner fa-spin"></i>
                Memproses absensi...
            </div>
        `;

        const formData = new FormData();
        formData.append('action', 'scan');
        formData.append('kegiatan_id', kegiatanId);
        formData.append('guru_id', guruId);
        formData.append('latitude', lat);
        formData.append('longitude', lon);
        formData.append('kode_absensi', 'QR_' + Date.now());

        fetch('../api/absensi_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        ${data.message}
                        ${data.data && data.data.jarak ? '<br><small>Jarak dari lokasi: ' + data.data.jarak + '</small>' : ''}
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = 'dashboard.php?notif=absen';
                }, 3000);
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.message}
                        ${data.jarak ? '<br><small>Jarak Anda: ' + data.jarak + ' meter</small>' : ''}
                    </div>
                `;
                setTimeout(() => {
                    resultDiv.innerHTML = '';
                    startBtn.disabled = false;
                }, 5000);
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Gagal memproses absensi: ${error.message}
                </div>
            `;
            setTimeout(() => {
                resultDiv.innerHTML = '';
                startBtn.disabled = false;
            }, 5000);
        });
    }

    startBtn.addEventListener('click', startScanner);
    stopBtn.addEventListener('click', stopScanner);

    // Cek status absensi saat load
    fetch('../api/absensi_api.php?action=cek_status&kegiatan_id=' + kegiatanId + '&guru_id=' + guruId)
        .then(response => response.json())
        .then(data => {
            if (data.sudah_absen) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Anda sudah melakukan absensi untuk kegiatan ini.
                    </div>
                `;
                startBtn.disabled = true;
            }
        })
        .catch(() => {});
});
</script>

<?php include $root_path . '/member/include/member_footer.php'; ?>