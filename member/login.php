<?php
// member/login.php - Halaman Login Member Guru
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['member_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$nik = '';

// Cek dari URL parameter (dari link WhatsApp)
if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = trim($_GET['nik']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = isset($_POST['nik']) ? mysqli_real_escape_string($conn, trim($_POST['nik'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validasi NIK
    if (empty($nik) || strlen($nik) !== 16 || !is_numeric($nik)) {
        $error = 'NIK harus 16 digit angka.';
    } 
    // Validasi password
    elseif (empty($password)) {
        $error = 'Password tidak boleh kosong.';
    }
    else {
        // Cari guru berdasarkan NIK
        $query = "SELECT * FROM guru_ngaji WHERE nik = '$nik'";
        $result = mysqli_query($conn, $query);
        $guru = mysqli_fetch_assoc($result);
        
        if ($guru) {
            // Cek status verifikasi
            if ($guru['status_verifikasi'] !== 'disetujui') {
                $error = 'Status Anda belum disetujui. Silakan cek status pendaftaran.';
            } else {
                // Cek password - handle jika password null
                $default_password = 'pgnilampung';
                $password_valid = false;
                
                // Cek jika password di database kosong atau null, gunakan default
                if (empty($guru['password'])) {
                    // Jika password kosong, cek apakah input sama dengan default
                    if ($password === $default_password) {
                        $password_valid = true;
                        // Update password dengan hash untuk keamanan
                        $hashed = password_hash($default_password, PASSWORD_DEFAULT);
                        mysqli_query($conn, "UPDATE guru_ngaji SET password = '$hashed' WHERE id = " . $guru['id']);
                    }
                } else {
                    // Verifikasi password hash
                    if (password_verify($password, $guru['password']) || $password === $default_password) {
                        $password_valid = true;
                    }
                }
                
                if ($password_valid) {
                    // Set session
                    $_SESSION['member_id'] = $guru['id'];
                    $_SESSION['member_nik'] = $guru['nik'];
                    $_SESSION['member_nama'] = $guru['nama'];
                    $_SESSION['member_role'] = 'guru';
                    
                    // Update last_login - cek apakah kolom ada terlebih dahulu
                    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM guru_ngaji LIKE 'last_login'");
                    if (mysqli_num_rows($check_column) > 0) {
                        mysqli_query($conn, "UPDATE guru_ngaji SET last_login = NOW() WHERE id = " . $guru['id']);
                    }
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Password salah. Default password: pgnilampung';
                }
            }
        } else {
            $error = 'NIK tidak ditemukan. Pastikan Anda sudah mendaftar.';
        }
    }
}

$title = 'Login Member Guru';
include '../include/header.php';
?>

<style>
    /* ============================================
       STYLE LOGIN MEMBER
    ============================================ */
    .login-page {
        min-height: calc(100vh - 300px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8edf2 100%);
    }

    .login-container {
        max-width: 420px;
        width: 100%;
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        padding: 40px;
        border: 1px solid rgba(26, 110, 58, 0.08);
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-header .logo-icon {
        font-size: 3rem;
        margin-bottom: 10px;
        display: block;
    }

    .login-header h2 {
        color: #1a1a2e;
        font-size: 1.5rem;
        margin: 0 0 5px 0;
        font-weight: 700;
    }

    .login-header p {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
    }

    .login-header .badge-status {
        display: inline-block;
        padding: 4px 16px;
        background: #d4edda;
        color: #155724;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 8px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.85rem;
        margin-bottom: 6px;
    }

    .form-group label .required {
        color: #e74c3c;
    }

    .form-group .input-group {
        display: flex;
        align-items: center;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .form-group .input-group:focus-within {
        border-color: #1a6e3a;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
        background: #fff;
    }

    .form-group .input-group .input-icon {
        padding: 0 0 0 14px;
        color: #95a5a6;
        font-size: 1rem;
    }

    .form-group .input-group input {
        flex: 1;
        padding: 12px 14px;
        border: none;
        background: transparent;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        outline: none;
        color: #1a1a2e;
    }

    .form-group .input-group input::placeholder {
        color: #aab;
    }

    .form-group .input-group .toggle-password {
        padding: 0 14px 0 0;
        background: none;
        border: none;
        color: #95a5a6;
        cursor: pointer;
        font-size: 1rem;
    }

    .form-group .input-group .toggle-password:hover {
        color: #1a6e3a;
    }

    .form-group .form-text {
        font-size: 0.75rem;
        color: #95a5a6;
        margin-top: 4px;
        display: block;
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.25);
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .error-message {
        background: #fef3f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .error-message i {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .login-footer {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f0f2f5;
    }

    .login-footer .links {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .login-footer .links a {
        color: #1a6e3a;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: color 0.3s ease;
    }

    .login-footer .links a:hover {
        color: #0e4a26;
        text-decoration: underline;
    }

    .login-footer .links a i {
        margin-right: 5px;
    }

    .default-pass-info {
        background: #f8f9fa;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 0.8rem;
        color: #7f8c8d;
        text-align: center;
        margin-top: 15px;
        border: 1px dashed #dce1e6;
    }

    .default-pass-info strong {
        color: #1a6e3a;
        font-family: 'Courier New', monospace;
        background: #e8f5e9;
        padding: 2px 12px;
        border-radius: 4px;
        letter-spacing: 1px;
    }

    .default-pass-info i {
        color: #f39c12;
        margin-right: 5px;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .login-container {
            padding: 25px 20px;
            border-radius: 16px;
        }

        .login-header h2 {
            font-size: 1.2rem;
        }

        .form-group .input-group input {
            padding: 10px 12px;
            font-size: 0.9rem;
        }

        .btn-login {
            padding: 12px;
            font-size: 0.9rem;
        }

        .login-footer .links {
            gap: 12px;
            flex-direction: column;
        }
    }
</style>

<div class="login-page">
    <div class="login-container">
        <div class="login-header">
            <span class="logo-icon">📚</span>
            <h2>Member Area</h2>
            <p>Masuk untuk mengelola data guru ngaji</p>
            <span class="badge-status">✅ Guru Terverifikasi</span>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="nik">NIK <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" 
                           id="nik" 
                           name="nik" 
                           placeholder="Masukkan 16 digit NIK" 
                           value="<?php echo htmlspecialchars($nik); ?>"
                           required 
                           maxlength="16"
                           pattern="[0-9]{16}"
                           title="NIK harus 16 digit angka">
                </div>
                <span class="form-text"><i class="fas fa-info-circle"></i> Masukkan NIK sesuai KTP (16 digit angka)</span>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Masukkan password" 
                           required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <span class="form-text"><i class="fas fa-key"></i> Default password: <strong>pgnilampung</strong></span>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="default-pass-info">
            <i class="fas fa-info-circle"></i> 
            Password default: <strong>pgnilampung</strong>
            <span style="display:block; font-size:0.75rem; margin-top:4px; color:#95a5a6;">
                Ganti password setelah login untuk keamanan
            </span>
        </div>

        <div class="login-footer">
            <div class="links">
                <a href="../cek_status.php"><i class="fas fa-search"></i> Cek Status</a>
                <a href="../registrasi.php"><i class="fas fa-user-plus"></i> Daftar Guru</a>
                <a href="../"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-format NIK input
document.getElementById('nik').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length > 16) {
        this.value = this.value.slice(0, 16);
    }
});

// Toggle password visibility
function togglePassword() {
    const password = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (password.type === 'password') {
        password.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        password.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php include '../include/footer.php'; ?>