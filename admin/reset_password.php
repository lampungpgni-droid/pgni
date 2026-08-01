<?php
// reset_password.php - Halaman Lupa & Reset Password
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// PERBAIKAN PATH
// ============================================================
$root_path = dirname(__DIR__); // Naik satu level dari /admin ke root
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Lupa Password - PGNI Lampung';
$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$error = '';
$email = '';

// ============================================================
// STEP 1: REQUEST RESET (Minta Link Reset via Email)
// ============================================================
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Silakan masukkan alamat email Anda.';
    } else {
        // Cek apakah email terdaftar
        $email_escaped = mysqli_real_escape_string($conn, $email);
        $query = "SELECT id, username, nama_lengkap, email FROM users WHERE email = '$email_escaped'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            // Generate token unik
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Simpan token ke database
            $user_id = $user['id'];
            $query_insert = "INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                             VALUES ($user_id, '$token', '$expires', NOW())";
            
            if (mysqli_query($conn, $query_insert)) {
                // Kirim email
                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?step=reset&token=" . $token;
                $subject = "Reset Password - PGNI Lampung";
                $body = "Assalamualaikum Wr. Wb.\n\n";
                $body .= "Yth. " . $user['nama_lengkap'] . ",\n\n";
                $body .= "Kami menerima permintaan untuk mereset password akun Anda.\n\n";
                $body .= "Klik link berikut untuk mereset password Anda:\n";
                $body .= $reset_link . "\n\n";
                $body .= "Link ini berlaku selama 1 jam.\n\n";
                $body .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
                $body .= "Wassalamualaikum Wr. Wb.\n";
                $body .= "PGNI Lampung";
                
                $headers = "From: no-reply@pgni.net\r\n";
                $headers .= "Reply-To: admin@pgni.net\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                if (mail($email, $subject, $body, $headers)) {
                    $message = "Link reset password telah dikirim ke email Anda. Cek inbox atau folder spam.";
                    $email = '';
                } else {
                    $error = "Gagal mengirim email. Silakan coba lagi atau hubungi admin.";
                }
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        } else {
            // Jangan beri tahu user spesifik, tapi log untuk keamanan
            $message = "Jika email terdaftar, link reset akan dikirim ke inbox Anda.";
        }
    }
}

// ============================================================
// STEP 2: RESET PASSWORD (Ganti Password dengan Token)
// ============================================================
if ($step === 'reset' && !empty($token)) {
    // Validasi token
    $token_escaped = mysqli_real_escape_string($conn, $token);
    $query = "SELECT pr.*, u.id as user_id, u.username, u.nama_lengkap, u.email 
              FROM password_resets pr
              JOIN users u ON pr.user_id = u.id
              WHERE pr.token = '$token_escaped' AND pr.used = 0 AND pr.expires_at > NOW()";
    $result = mysqli_query($conn, $query);
    $reset_data = mysqli_fetch_assoc($result);
    
    if (!$reset_data) {
        $error = "Token tidak valid atau sudah kadaluarsa. Silakan minta reset ulang.";
        $step = 'request';
    }
    
    // Proses update password
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Password dan konfirmasi password tidak cocok.";
        } else {
            // Hash password baru
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $user_id = $reset_data['user_id'];
            
            // Update password
            $query_update = "UPDATE users SET password = '$hashed' WHERE id = $user_id";
            if (mysqli_query($conn, $query_update)) {
                // Tandai token sudah digunakan
                $query_used = "UPDATE password_resets SET used = 1 WHERE token = '$token_escaped'";
                mysqli_query($conn, $query_used);
                
                $message = "Password berhasil direset! Silakan login dengan password baru Anda.";
                $step = 'success';
            } else {
                $error = "Gagal mengupdate password. Silakan coba lagi.";
            }
        }
    }
}

// ============================================================
// Cek apakah user sudah login - redirect ke dashboard
// ============================================================
if (isset($_SESSION['user_id']) && $step === 'request') {
    header('Location: dashboard.php');
    exit;
}

include $root_path . '/include/header.php';
?>

<style>
/* ============================================
   STYLE RESET PASSWORD
============================================ */
.reset-page {
    padding: 40px 0;
    min-height: calc(100vh - 300px);
    background: #f8f9fa;
}

.reset-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 0 20px;
}

.reset-header {
    text-align: center;
    margin-bottom: 35px;
}

.reset-header h1 {
    font-size: 1.8rem;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.reset-header h1 i {
    color: #d4a847;
}

.reset-header p {
    color: #666;
    font-size: 0.95rem;
}

.reset-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.08);
    overflow: hidden;
    padding: 35px 40px;
}

.reset-card .icon-box {
    text-align: center;
    margin-bottom: 20px;
}

.reset-card .icon-box i {
    font-size: 3rem;
    color: #1a6e3a;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.form-group label .required {
    color: #e74c3c;
}

.form-group .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: #fafafa;
}

.form-group .form-control:focus {
    border-color: #1a6e3a;
    outline: none;
    box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    background: #fff;
}

.form-group .form-text {
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
    display: block;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: 'Poppins', sans-serif;
}

.btn-submit:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
}

.btn-submit i {
    font-size: 1rem;
}

.btn-link {
    display: inline-block;
    color: #1a6e3a;
    text-decoration: none;
    font-weight: 500;
}

.btn-link:hover {
    text-decoration: underline;
}

/* Alert */
.alert {
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.alert i {
    font-size: 1.2rem;
}

.footer-links {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.footer-links a {
    color: #666;
    text-decoration: none;
    font-size: 0.9rem;
}

.footer-links a:hover {
    color: #1a6e3a;
}

/* Password Strength */
.password-strength {
    margin-top: 8px;
    height: 4px;
    border-radius: 4px;
    background: #e8e8e8;
    overflow: hidden;
}

.password-strength .bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.password-strength .bar.weak { background: #e74c3c; width: 33%; }
.password-strength .bar.medium { background: #f39c12; width: 66%; }
.password-strength .bar.strong { background: #28a745; width: 100%; }

.password-strength-text {
    font-size: 0.75rem;
    margin-top: 4px;
    color: #999;
}

/* Responsive */
@media (max-width: 576px) {
    .reset-card {
        padding: 25px 20px;
    }
    .reset-header h1 {
        font-size: 1.4rem;
    }
}
</style>

<section class="reset-page">
    <div class="reset-container">
        <!-- Header -->
        <div class="reset-header">
            <h1><i class="fas fa-key"></i> Lupa Password</h1>
            <p>Masukkan email Anda untuk mendapatkan link reset password</p>
        </div>

        <!-- Card -->
        <div class="reset-card">
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- ============================================================
            STEP 1: REQUEST RESET
            ============================================================ -->
            <?php if ($step === 'request'): ?>
                <div class="icon-box">
                    <i class="fas fa-envelope"></i>
                </div>
                <p style="text-align: center; color: #666; margin-bottom: 25px;">
                    Masukkan alamat email yang terdaftar untuk mendapatkan link reset password.
                </p>
                
                <form method="POST" action="?step=request">
                    <div class="form-group">
                        <label>Alamat Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" 
                               placeholder="Masukkan email terdaftar" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               required>
                        <span class="form-text">
                            <i class="fas fa-info-circle"></i> Email yang digunakan saat mendaftar
                        </span>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Kirim Link Reset
                    </button>
                </form>

                <div class="footer-links">
                    <a href="login.php">← Kembali ke Login</a>
                </div>

            <!-- ============================================================
            STEP 2: RESET PASSWORD
            ============================================================ -->
            <?php elseif ($step === 'reset' && !empty($token) && isset($reset_data)): ?>
                <div class="icon-box">
                    <i class="fas fa-lock-open"></i>
                </div>
                <p style="text-align: center; color: #666; margin-bottom: 25px;">
                    Masukkan password baru untuk akun <strong><?php echo htmlspecialchars($reset_data['nama_lengkap']); ?></strong>
                </p>
                
                <form method="POST" action="?step=reset&token=<?php echo $token; ?>">
                    <div class="form-group">
                        <label>Password Baru <span class="required">*</span></label>
                        <input type="password" class="form-control" name="new_password" 
                               id="new_password"
                               placeholder="Minimal 6 karakter" 
                               required minlength="6">
                        <div class="password-strength">
                            <div class="bar" id="strengthBar"></div>
                        </div>
                        <span class="password-strength-text" id="strengthText">Kekuatan password</span>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" 
                               placeholder="Masukkan ulang password baru" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>

                <div class="footer-links">
                    <a href="login.php">← Kembali ke Login</a>
                </div>

            <!-- ============================================================
            STEP 3: SUCCESS
            ============================================================ -->
            <?php elseif ($step === 'success'): ?>
                <div class="icon-box">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                </div>
                <p style="text-align: center; color: #333; font-size: 1.05rem; margin-bottom: 10px;">
                    Password berhasil direset!
                </p>
                <p style="text-align: center; color: #666; margin-bottom: 25px;">
                    Silakan login dengan password baru Anda.
                </p>
                
                <a href="login.php" class="btn-submit" style="text-decoration: none; display: flex;">
                    <i class="fas fa-sign-in-alt"></i> Login Sekarang
                </a>

            <!-- ============================================================
            TOKEN INVALID
            ============================================================ -->
            <?php elseif ($step === 'reset' && !empty($token) && !isset($reset_data)): ?>
                <div class="icon-box">
                    <i class="fas fa-times-circle" style="color: #e74c3c;"></i>
                </div>
                <p style="text-align: center; color: #e74c3c; font-size: 1.05rem;">
                    <?php echo $error ?? 'Token tidak valid atau sudah kadaluarsa.'; ?>
                </p>
                <p style="text-align: center; color: #666; margin-bottom: 25px;">
                    Silakan minta reset ulang dengan mengklik tombol di bawah.
                </p>
                
                <a href="?step=request" class="btn-submit" style="text-decoration: none; display: flex; background: #f39c12;">
                    <i class="fas fa-redo"></i> Minta Reset Ulang
                </a>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            let level = 'weak';
            let label = 'Lemah';
            if (strength >= 4) { level = 'strong'; label = 'Kuat'; }
            else if (strength >= 3) { level = 'medium'; label = 'Sedang'; }
            
            strengthBar.className = 'bar ' + level;
            strengthText.textContent = 'Kekuatan password: ' + label;
            strengthText.style.color = 
                level === 'strong' ? '#28a745' : 
                level === 'medium' ? '#f39c12' : '#e74c3c';
        });
    }
});
</script>

<?php include $root_path . '/include/footer.php'; ?>