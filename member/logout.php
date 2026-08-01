<?php
// member/logout.php - Logout Member dengan Konfirmasi
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama = isset($_SESSION['member_nama']) ? $_SESSION['member_nama'] : 'Member';

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: login.php?msg=logout_success');
    exit;
}

$title = 'Logout';
include 'include/member_header.php';
?>

<style>
    .logout-page {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
        padding: 20px;
    }
    
    .logout-card {
        background: #fff;
        border-radius: 20px;
        padding: 40px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        border: 1px solid #f0f2f5;
    }
    
    .logout-card .icon {
        font-size: 4rem;
        margin-bottom: 15px;
        display: block;
    }
    
    .logout-card h2 {
        font-size: 1.3rem;
        color: #1a1a2e;
        margin: 0 0 10px 0;
    }
    
    .logout-card p {
        color: #7f8c8d;
        font-size: 0.95rem;
        margin: 0 0 25px 0;
    }
    
    .logout-card .user-name {
        font-weight: 600;
        color: #1a6e3a;
    }
    
    .logout-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .logout-actions .btn {
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
    }
    
    .logout-actions .btn-primary {
        background: linear-gradient(135deg, #1a6e3a, #2d8f52);
        color: #fff;
    }
    
    .logout-actions .btn-primary:hover {
        background: linear-gradient(135deg, #0e4a26, #1a6e3a);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(26, 110, 58, 0.2);
    }
    
    .logout-actions .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    
    .logout-actions .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.2);
    }
    
    .logout-actions .btn-secondary {
        background: #eef2f5;
        color: #495057;
    }
    
    .logout-actions .btn-secondary:hover {
        background: #e2e6ea;
        transform: translateY(-2px);
    }
    
    .logout-actions .btn i {
        font-size: 1rem;
    }
    
    @media (max-width: 480px) {
        .logout-card {
            padding: 25px 20px;
        }
        
        .logout-card .icon {
            font-size: 3rem;
        }
        
        .logout-actions {
            flex-direction: column;
        }
        
        .logout-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="logout-page">
    <div class="logout-card">
        <span class="icon">🚪</span>
        <h2>Konfirmasi Logout</h2>
        <p>
            Apakah Anda yakin ingin logout?<br>
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
        </p>
        <div class="logout-actions">
            <a href="logout.php?confirm=yes" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Ya, Logout
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </div>
</div>

<script>
    setTimeout(function() {
        window.location.href = 'dashboard.php';
    }, 30000);
</script>

<?php include 'include/member_footer.php'; ?>