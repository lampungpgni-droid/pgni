<?php
// admin/login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path absolut dari root project
$root_path = dirname(__DIR__);
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    // Redirect berdasarkan role
    if ($_SESSION['role'] === 'super_admin') {
        header('Location: dashboard.php');
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: dashboard_admin.php');
    } elseif ($_SESSION['role'] === 'petugas_kecamatan') {
        header('Location: dashboard_petugas.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $query = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['kecamatan_id'] = $user['kecamatan_id'] ?? 0;
                
                // Redirect berdasarkan role
                if ($user['role'] === 'super_admin') {
                    header('Location: dashboard.php');
                } elseif ($user['role'] === 'admin') {
                    header('Location: dashboard_admin.php');
                } elseif ($user['role'] === 'petugas_kecamatan') {
                    header('Location: dashboard_petugas.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}

$title = 'Login Admin';
?>
<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PGNI Lampung</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0e4a26 0%, #1a6e3a 50%, #2d8f52 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            direction: ltr;
        }
        .login-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px 40px;
            max-width: 440px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #d4a847, #f0dba8, #d4a847);
        }
        .login-header { text-align: center; margin-bottom: 35px; }
        .login-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a6e3a, #2d8f52);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2.5rem;
            color: #fff;
        }
        .login-logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .login-header h1 { font-size: 1.5rem; color: #1a1a2e; font-weight: 700; }
        .login-header p { color: #666; font-size: 0.9rem; margin-top: 5px; }
        .login-header .subtitle {
            display: block;
            font-size: 0.75rem;
            color: #d4a847;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        .login-header .arabic {
            font-family: 'Amiri', serif;
            font-size: 1.1rem;
            color: #1a6e3a;
            display: block;
            margin-bottom: 5px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .form-group label i { margin-right: 8px; color: #d4a847; }
        .input-group { position: relative; }
        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px 12px 45px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #1a6e3a;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.1);
            background: #fff;
        }
        .form-control::placeholder { color: #aaa; }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
        }
        .toggle-password:hover { color: #333; }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.85rem;
        }
        .form-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            cursor: pointer;
        }
        .form-options label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a6e3a;
            cursor: pointer;
        }
        .form-options a { color: #1a6e3a; text-decoration: none; font-weight: 500; }
        .form-options a:hover { color: #0e4a26; text-decoration: underline; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a6e3a, #2d8f52);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 110, 58, 0.3);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-danger i { color: #dc2626; }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-success i { color: #059669; }
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: #999;
        }
        .login-footer .arabic {
            font-family: 'Amiri', serif;
            font-size: 0.9rem;
            color: #d4a847;
            display: block;
            margin-bottom: 5px;
        }
        .login-footer a { color: #1a6e3a; text-decoration: none; font-weight: 500; }
        .login-footer a:hover { color: #0e4a26; text-decoration: underline; }
        .islamic-pattern {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: repeating-linear-gradient(90deg, #d4a847, #f0dba8 10px, #d4a847 20px);
            opacity: 0.3;
        }
        @media (max-width: 480px) {
            .login-container { padding: 30px 20px; }
            .login-header h1 { font-size: 1.3rem; }
            .form-options { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="/pgnil/assets/images/logo/logo-pgni.png" 
                     alt="PGNI Lampung" 
                     onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-quran\'></i>'">
            </div>
            <span class="arabic">بسم الله الرحمن الرحيم</span>
            <h1>PGNI Lampung</h1>
            <span class="subtitle">Admin Panel</span>
            <p>Masuk ke dashboard manajemen</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Anda telah berhasil logout.
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Masukkan password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-options">
                <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <a href="#"><i class="fas fa-key"></i> Lupa password?</a>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>
        
        <div class="login-footer">
            <span class="arabic">اللهم صل على محمد</span>
            <i class="fas fa-shield-alt" style="color: #d4a847;"></i>
            &copy; <?php echo date('Y'); ?> PGNI Lampung - All Rights Reserved
        </div>
        <div class="islamic-pattern"></div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>