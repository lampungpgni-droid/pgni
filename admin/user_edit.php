<?php
// admin/user_edit.php
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

// Cek role - hanya super_admin dan admin yang bisa edit user
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header('Location: dashboard.php?error=akses_ditolak');
    exit;
}

$title = 'Edit User';
$error = '';
$success = '';

// Ambil ID user dari URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header('Location: user.php?error=id_tidak_valid');
    exit;
}

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = $user_id";
$result_user = mysqli_query($conn, $query_user);
if (mysqli_num_rows($result_user) == 0) {
    header('Location: user.php?error=user_tidak_ditemukan');
    exit;
}
$user = mysqli_fetch_assoc($result_user);

// Ambil permissions user
$user_permissions = [];
$query_perm = "SELECT permission_id FROM user_permissions WHERE user_id = $user_id AND granted = 1";
$result_perm = mysqli_query($conn, $query_perm);
while ($row = mysqli_fetch_assoc($result_perm)) {
    $user_permissions[] = $row['permission_id'];
}

// Ambil akses wilayah user
$user_wilayah = [
    'akses_semua' => 0,
    'kabupaten' => [],
    'kecamatan' => [],
    'desa' => []
];

$query_wilayah = "SELECT * FROM user_wilayah_akses WHERE user_id = $user_id";
$result_wilayah = mysqli_query($conn, $query_wilayah);
while ($row = mysqli_fetch_assoc($result_wilayah)) {
    if ($row['akses_semua'] == 1) {
        $user_wilayah['akses_semua'] = 1;
    }
    if ($row['kabupaten_id']) {
        $user_wilayah['kabupaten'][] = $row['kabupaten_id'];
    }
    if ($row['kecamatan_id']) {
        $user_wilayah['kecamatan'][] = $row['kecamatan_id'];
    }
    if ($row['desa_id']) {
        $user_wilayah['desa'][] = $row['desa_id'];
    }
}

// Ambil daftar kabupaten
$query_kabupaten = "SELECT * FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $query_kabupaten);

// Ambil daftar kecamatan untuk semua kabupaten
$query_kecamatan = "SELECT k.*, kb.nama as kabupaten_nama 
                    FROM kecamatan k 
                    JOIN kabupaten kb ON k.kabupaten_id = kb.id 
                    ORDER BY kb.nama, k.nama";
$kecamatan_list = mysqli_query($conn, $query_kecamatan);

// Ambil daftar desa untuk semua kecamatan
$query_desa = "SELECT d.*, k.nama as kecamatan_nama, kb.nama as kabupaten_nama 
               FROM desa d 
               JOIN kecamatan k ON d.kecamatan_id = k.id 
               JOIN kabupaten kb ON k.kabupaten_id = kb.id 
               ORDER BY kb.nama, k.nama, d.nama";
$desa_list = mysqli_query($conn, $query_desa);

// Definisikan roles yang tersedia
$available_roles = ['admin', 'petugas_kecamatan'];
if ($_SESSION['role'] === 'super_admin') {
    $available_roles = ['super_admin', 'admin', 'petugas_kecamatan'];
}

// Ambil daftar permissions
$permissions_list = [];
$query_permissions = "SELECT id, name, description FROM permissions ORDER BY name";
$result_permissions = mysqli_query($conn, $query_permissions);
while ($perm = mysqli_fetch_assoc($result_permissions)) {
    $permissions_list[] = $perm;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $password = $_POST['password'];
    $user_permissions_post = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Data wilayah
    $akses_semua = isset($_POST['akses_semua']) ? 1 : 0;
    $kabupaten_terpilih = isset($_POST['kabupaten']) ? $_POST['kabupaten'] : [];
    $kecamatan_terpilih = isset($_POST['kecamatan']) ? $_POST['kecamatan'] : [];
    $desa_terpilih = isset($_POST['desa']) ? $_POST['desa'] : [];
    
    // Validasi
    if (empty($username) || empty($nama_lengkap) || empty($role)) {
        $error = 'Username, Nama Lengkap, dan Role wajib diisi!';
    } else {
        // Cek username duplicate (kecuali diri sendiri)
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            // Cek email duplicate
            if (!empty($email)) {
                $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id AND email != ''");
                if (mysqli_num_rows($check_email) > 0) {
                    $error = 'Email sudah terdaftar!';
                }
            }
            
            if (empty($error)) {
                // Build update query
                $email_value = !empty($email) ? "'$email'" : "NULL";
                $update_query = "UPDATE users SET 
                    username = '$username',
                    nama_lengkap = '$nama_lengkap',
                    email = $email_value,
                    role = '$role',
                    no_telepon = '$no_telepon',
                    alamat = '$alamat'";
                
                // Jika password diisi, update password
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = 'Password minimal 6 karakter!';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_query .= ", password = '$hashed_password'";
                    }
                }
                
                $update_query .= " WHERE id = $user_id";
                
                if (empty($error) && mysqli_query($conn, $update_query)) {
                    // Hapus permissions lama
                    mysqli_query($conn, "DELETE FROM user_permissions WHERE user_id = $user_id");
                    
                    // Simpan permission baru
                    if (!empty($user_permissions_post) && $role !== 'super_admin') {
                        foreach ($user_permissions_post as $perm_id) {
                            $perm_id = (int)$perm_id;
                            $insert_perm = "INSERT INTO user_permissions (user_id, permission_id, granted) 
                                            VALUES ($user_id, $perm_id, 1)";
                            mysqli_query($conn, $insert_perm);
                        }
                    }
                    
                    // Hapus akses wilayah lama
                    mysqli_query($conn, "DELETE FROM user_wilayah_akses WHERE user_id = $user_id");
                    
                    // Simpan akses wilayah baru
                    if ($akses_semua == 1) {
                        mysqli_query($conn, "INSERT INTO user_wilayah_akses (user_id, akses_semua) VALUES ($user_id, 1)");
                    } else {
                        // Simpan kabupaten
                        if (!empty($kabupaten_terpilih)) {
                            foreach ($kabupaten_terpilih as $kab_id) {
                                $kab_id = (int)$kab_id;
                                mysqli_query($conn, "INSERT INTO user_wilayah_akses (user_id, kabupaten_id) VALUES ($user_id, $kab_id)");
                            }
                        }
                        
                        // Simpan kecamatan
                        if (!empty($kecamatan_terpilih)) {
                            foreach ($kecamatan_terpilih as $kec_id) {
                                $kec_id = (int)$kec_id;
                                mysqli_query($conn, "INSERT INTO user_wilayah_akses (user_id, kecamatan_id) VALUES ($user_id, $kec_id)");
                            }
                        }
                        
                        // Simpan desa
                        if (!empty($desa_terpilih)) {
                            foreach ($desa_terpilih as $desa_id) {
                                $desa_id = (int)$desa_id;
                                mysqli_query($conn, "INSERT INTO user_wilayah_akses (user_id, desa_id) VALUES ($user_id, $desa_id)");
                            }
                        }
                    }
                    
                    header('Location: user.php?msg=edit');
                    exit;
                } else {
                    $error = 'Gagal mengupdate user: ' . mysqli_error($conn);
                }
            }
        }
    }
}

include 'include/admin_header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>

<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>
        <p class="text-muted">Edit data user dan hak aksesnya</p>
    </div>
    <div class="page-header-right">
        <a href="user.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="form-wrapper">
    <form action="" method="POST" id="formEditUser">
        <!-- INFORMASI AKUN -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-user-circle"></i>
                <h3>Informasi Akun</h3>
                <span class="section-badge">Wajib diisi</span>
            </div>
            <div class="form-section-body">
                <div class="form-group-row">
                    <label for="username" class="form-label">Username <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Masukkan username" required
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        <small class="form-text text-muted">Username unik untuk login</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="password" class="form-label">Password Baru</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Kosongkan jika tidak ingin mengubah password" minlength="6">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Minimal 6 karakter. Kosongkan jika tidak ingin mengubah password.</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                   placeholder="Masukkan nama lengkap" required
                                   value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="email" class="form-label">Email</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Masukkan email (opsional)"
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <small class="form-text text-muted">Email bersifat opsional, namun harus unik jika diisi</small>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="role" class="form-label">Role <span class="required">*</span></label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                            <select class="form-control" id="role" name="role" required onchange="toggleRoleOptions()">
                                <option value="">Pilih Role</option>
                                <?php foreach ($available_roles as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo ($user['role'] == $r) ? 'selected' : ''; ?>>
                                        <?php 
                                            switch($r) {
                                                case 'super_admin': echo '👑 Super Admin'; break;
                                                case 'admin': echo '⚙️ Admin'; break;
                                                case 'petugas_kecamatan': echo '📋 Petugas Kecamatan'; break;
                                                default: echo $r;
                                            }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <small class="form-text text-muted">Role menentukan hak akses dasar user</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- INFORMASI TAMBAHAN -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-address-card"></i>
                <h3>Informasi Tambahan</h3>
                <span class="section-badge">Opsional</span>
            </div>
            <div class="form-section-body">
                <div class="form-group-row">
                    <label for="no_telepon" class="form-label">No. Telepon</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="no_telepon" name="no_telepon" 
                                   placeholder="Contoh: 0812-3456-7890"
                                   value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group-row">
                    <label for="alamat" class="form-label">Alamat</label>
                    <div class="form-control-wrap">
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-home"></i></span>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                      placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- HAK AKSES KHUSUS -->
        <div class="form-section" id="permissionSection">
            <div class="form-section-header">
                <i class="fas fa-key"></i>
                <h3>Hak Akses Khusus</h3>
                <span class="section-badge">Opsional</span>
            </div>
            <div class="form-section-body">
                <div class="permission-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Informasi:</strong> 
                        <span id="permissionInfoText">Berikan hak akses tambahan untuk user ini. Kosongkan jika menggunakan default role.</span>
                    </div>
                </div>
                
                <div class="permission-grid" id="permissionGrid">
                    <?php foreach ($permissions_list as $perm): ?>
                        <div class="permission-item">
                            <label class="permission-checkbox">
                                <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>"
                                    <?php echo in_array($perm['id'], $user_permissions) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <div class="permission-label">
                                    <span class="perm-name"><?php echo htmlspecialchars($perm['name']); ?></span>
                                    <span class="perm-desc"><?php echo htmlspecialchars($perm['description']); ?></span>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="permission-actions-mini">
                    <button type="button" class="btn btn-sm btn-primary" onclick="checkAllPermissions()">
                        <i class="fas fa-check-double"></i> Centang Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="uncheckAllPermissions()">
                        <i class="fas fa-times"></i> Hapus Semua
                    </button>
                </div>
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle"></i> 
                    <span id="permissionNote">Hak akses khusus akan override permission default dari role.</span>
                </small>
            </div>
        </div>
        
        <!-- AKSES WILAYAH -->
        <div class="form-section" id="wilayahSection">
            <div class="form-section-header">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Akses Wilayah</h3>
                <span class="section-badge">Sesuai Role</span>
            </div>
            <div class="form-section-body">
                <div class="wilayah-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Informasi:</strong> 
                        <span id="wilayahInfoText">Pilih wilayah yang dapat diakses oleh user ini.</span>
                    </div>
                </div>
                
                <!-- Akses Semua Wilayah -->
                <div class="form-group-row">
                    <label class="form-label">Akses Semua</label>
                    <div class="form-control-wrap">
                        <label class="checkbox-toggle">
                            <input type="checkbox" id="akses_semua" name="akses_semua" value="1" 
                                   onchange="toggleAllWilayah()"
                                   <?php echo $user_wilayah['akses_semua'] == 1 ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Akses ke seluruh wilayah</span>
                        </label>
                        <small class="form-text text-muted">Jika dicentang, user dapat mengakses semua data tanpa batasan wilayah.</small>
                    </div>
                </div>
                
                <!-- Pilihan Wilayah -->
                <div id="wilayah_container" style="<?php echo $user_wilayah['akses_semua'] == 1 ? 'display:none;' : ''; ?>">
                    
                    <!-- Pilihan Kabupaten -->
                    <div class="wilayah-section-title">
                        <i class="fas fa-city"></i> Pilih Kabupaten
                        <span class="badge-count" id="kabupaten_count">0 terpilih</span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAllKabupaten()">
                            <i class="fas fa-check-double"></i> Pilih Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllKabupaten()">
                            <i class="fas fa-times"></i> Hapus Semua
                        </button>
                    </div>
                    
                    <div class="wilayah-grid" id="kabupaten_grid">
                        <?php 
                        mysqli_data_seek($kabupaten_list, 0);
                        while ($kab = mysqli_fetch_assoc($kabupaten_list)): 
                            $checked = in_array($kab['id'], $user_wilayah['kabupaten']);
                        ?>
                            <div class="wilayah-card kabupaten-card <?php echo $checked ? 'selected' : ''; ?>" data-id="<?php echo $kab['id']; ?>">
                                <label class="wilayah-checkbox">
                                    <input type="checkbox" name="kabupaten[]" value="<?php echo $kab['id']; ?>"
                                           onchange="toggleKabupaten(this)"
                                           <?php echo $checked ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <div class="wilayah-label">
                                        <span class="wilayah-name"><?php echo htmlspecialchars($kab['nama']); ?></span>
                                        <span class="wilayah-count" id="kab_count_<?php echo $kab['id']; ?>">
                                            <?php 
                                            $count_kec = mysqli_query($conn, "SELECT COUNT(*) as total FROM kecamatan WHERE kabupaten_id = " . $kab['id']);
                                            $total_kec = mysqli_fetch_assoc($count_kec);
                                            echo $total_kec['total'] . ' kecamatan';
                                            ?>
                                        </span>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pilihan Kecamatan -->
                    <div id="kecamatan_container" style="margin-top:20px;display:none;">
                        <div class="wilayah-section-title">
                            <i class="fas fa-map"></i> Pilih Kecamatan
                            <span class="badge-count" id="kecamatan_count">0 terpilih</span>
                            <button type="button" class="btn btn-sm btn-primary" onclick="selectAllKecamatan()">
                                <i class="fas fa-check-double"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllKecamatan()">
                                <i class="fas fa-times"></i> Hapus Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="toggleAllKecamatanVisibility()">
                                <i class="fas fa-expand"></i> Tampilkan Semua
                            </button>
                        </div>
                        <div id="kecamatan_grid" class="wilayah-grid"></div>
                    </div>
                    
                    <!-- Pilihan Desa -->
                    <div id="desa_container" style="margin-top:20px;display:none;">
                        <div class="wilayah-section-title">
                            <i class="fas fa-village"></i> Pilih Desa
                            <span class="badge-count" id="desa_count">0 terpilih</span>
                            <button type="button" class="btn btn-sm btn-primary" onclick="selectAllDesa()">
                                <i class="fas fa-check-double"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllDesa()">
                                <i class="fas fa-times"></i> Hapus Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="toggleAllDesaVisibility()">
                                <i class="fas fa-expand"></i> Tampilkan Semua
                            </button>
                        </div>
                        <div id="desa_grid" class="wilayah-grid"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TOMBOL AKSI -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Update User
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
                <i class="fas fa-undo"></i> Reset
            </button>
            <a href="user.php" class="btn btn-danger btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>

<style>
    /* ===== STYLING - Sama seperti user_tambah ===== */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .page-header-left h2 {
        font-size: 1.4rem;
        color: #1a1a2e;
        margin-bottom: 3px;
    }
    .page-header-left h2 i {
        color: #d4a847;
        margin-right: 10px;
    }
    .page-header-left .text-muted {
        color: #999;
        font-size: 0.9rem;
        margin: 0;
    }
    .page-header-right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .form-wrapper {
        background: #fff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.06);
    }
    
    .form-section {
        background: #fafafa;
        border-radius: 12px;
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .form-section-header {
        background: #fff;
        padding: 14px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid #f0f0f0;
    }
    .form-section-header i {
        color: #d4a847;
        font-size: 1.1rem;
        width: 22px;
        text-align: center;
    }
    .form-section-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
        flex: 1;
    }
    .form-section-header .section-badge {
        font-size: 0.7rem;
        padding: 2px 12px;
        border-radius: 20px;
        background: #e8e8e8;
        color: #666;
        font-weight: 500;
    }
    .form-section-body {
        padding: 20px 25px;
    }
    
    .form-group-row {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #f5f5f5;
    }
    .form-group-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .form-label {
        min-width: 170px;
        max-width: 170px;
        padding-top: 10px;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
        text-align: left;
        flex-shrink: 0;
    }
    .form-label .required {
        color: #e74c3c;
        font-weight: 700;
        margin-left: 3px;
    }
    .form-control-wrap {
        flex: 1;
        min-width: 0;
    }
    
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-group .input-icon {
        position: absolute;
        left: 14px;
        color: #999;
        font-size: 1rem;
        z-index: 1;
    }
    .form-control-wrap .form-control {
        width: 100%;
        padding: 10px 14px 10px 45px;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #fff;
        color: #333;
    }
    .form-control-wrap .form-control:focus {
        border-color: #1a6e3a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 110, 58, 0.08);
    }
    .form-control-wrap .form-control::placeholder {
        color: #aaa;
    }
    .form-control-wrap textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
    .form-control-wrap .form-text {
        font-size: 0.8rem;
        color: #999;
        margin-top: 4px;
        display: block;
    }
    
    .btn-toggle-password {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 5px;
        z-index: 1;
    }
    .btn-toggle-password:hover {
        color: #333;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
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
        color: #fff;
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
        transform: translateY(-2px);
    }
    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }
    .btn-danger:hover {
        background: #c0392b;
        color: #fff;
        transform: translateY(-2px);
    }
    .btn-info {
        background: #3498db;
        color: #fff;
    }
    .btn-info:hover {
        background: #2980b9;
        color: #fff;
        transform: translateY(-2px);
    }
    .btn-sm {
        padding: 6px 14px;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    .btn-lg {
        padding: 12px 32px;
        font-size: 1rem;
        border-radius: 10px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        padding-top: 25px;
        border-top: 2px solid #f0f0f0;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .form-actions .btn {
        min-width: 140px;
        justify-content: center;
    }
    
    .permission-info-box,
    .wilayah-info-box {
        background: #e8f4fd;
        border-radius: 8px;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    .permission-info-box i,
    .wilayah-info-box i {
        color: #3498db;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .permission-info-box div,
    .wilayah-info-box div {
        font-size: 0.9rem;
        color: #2c3e50;
    }
    .permission-info-box strong,
    .wilayah-info-box strong {
        color: #1a1a2e;
    }
    
    .permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .permission-item {
        padding: 6px 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    .permission-item:hover {
        background: #f8f9fa;
        border-color: #ddd;
    }
    .permission-item:has(input:checked) {
        background: #f0f7f3;
        border-color: #1a6e3a;
    }
    
    .permission-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        width: 100%;
        position: relative;
        padding: 4px 0;
    }
    .permission-checkbox input[type="checkbox"] {
        display: none;
    }
    
    .checkmark {
        width: 20px;
        height: 20px;
        border: 2px solid #ccc;
        border-radius: 4px;
        flex-shrink: 0;
        margin-top: 2px;
        position: relative;
        transition: all 0.3s ease;
    }
    .permission-checkbox input[type="checkbox"]:checked + .checkmark {
        background: #1a6e3a;
        border-color: #1a6e3a;
    }
    .permission-checkbox input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
    }
    
    .permission-label {
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .perm-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: #333;
    }
    .perm-desc {
        font-size: 0.7rem;
        color: #999;
        margin-top: 1px;
    }
    
    .permission-actions-mini {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .wilayah-section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        font-weight: 600;
        color: #1a1a2e;
        flex-wrap: wrap;
    }
    .wilayah-section-title i {
        color: #d4a847;
    }
    .wilayah-section-title .badge-count {
        font-size: 0.8rem;
        font-weight: 400;
        color: #666;
        background: #e8e8e8;
        padding: 2px 12px;
        border-radius: 12px;
    }
    
    .wilayah-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .wilayah-card {
        padding: 8px 12px;
        border-radius: 8px;
        border: 2px solid #eee;
        transition: all 0.3s ease;
        background: #fff;
    }
    .wilayah-card:hover {
        border-color: #d4a847;
        background: #fafafa;
    }
    .wilayah-card.selected {
        border-color: #1a6e3a;
        background: #f0f7f3;
    }
    
    .wilayah-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        width: 100%;
        padding: 4px 0;
    }
    .wilayah-checkbox input[type="checkbox"] {
        display: none;
    }
    .wilayah-checkbox .checkmark {
        width: 20px;
        height: 20px;
        border: 2px solid #ccc;
        border-radius: 4px;
        flex-shrink: 0;
        margin-top: 2px;
        position: relative;
        transition: all 0.3s ease;
    }
    .wilayah-checkbox input[type="checkbox"]:checked + .checkmark {
        background: #1a6e3a;
        border-color: #1a6e3a;
    }
    .wilayah-checkbox input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
    }
    
    .wilayah-label {
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .wilayah-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: #333;
    }
    .wilayah-count {
        font-size: 0.7rem;
        color: #999;
        margin-top: 1px;
    }
    
    .checkbox-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }
    .checkbox-toggle input[type="checkbox"] {
        display: none;
    }
    .toggle-slider {
        width: 48px;
        height: 26px;
        background: #ccc;
        border-radius: 13px;
        position: relative;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }
    .toggle-slider::after {
        content: '';
        width: 22px;
        height: 22px;
        background: #fff;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .checkbox-toggle input[type="checkbox"]:checked + .toggle-slider {
        background: #1a6e3a;
    }
    .checkbox-toggle input[type="checkbox"]:checked + .toggle-slider::after {
        left: 24px;
    }
    .toggle-label {
        font-size: 0.9rem;
        color: #333;
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
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .kecamatan-group, .desa-group {
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }
    .kecamatan-group-header, .desa-group-header {
        background: #f8f9fa;
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        border-bottom: 1px solid #e8e8e8;
    }
    .kecamatan-group-header .group-name,
    .desa-group-header .group-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #1a1a2e;
    }
    .kecamatan-grid-inner,
    .desa-grid-inner {
        padding: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 8px;
    }
    
    @media (max-width: 768px) {
        .form-wrapper { padding: 20px 15px; }
        .form-group-row {
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        .form-label {
            min-width: auto;
            max-width: none;
            width: 100%;
            padding-top: 0;
            text-align: left;
        }
        .form-control-wrap { width: 100%; }
        .form-section-body { padding: 15px 18px; }
        .form-section-header { padding: 12px 18px; }
        .form-actions { flex-direction: column; }
        .form-actions .btn { width: 100%; justify-content: center; }
        .page-header { flex-direction: column; align-items: stretch; }
        .page-header-right .btn { flex: 1; justify-content: center; }
        .permission-grid { grid-template-columns: 1fr; }
        .wilayah-grid { grid-template-columns: 1fr; }
        .kecamatan-grid-inner,
        .desa-grid-inner { grid-template-columns: 1fr; }
        .wilayah-section-title { flex-wrap: wrap; }
        .permission-info-box,
        .wilayah-info-box { flex-direction: column; text-align: center; }
    }
</style>

<script>
// ============================================
// PASSWORD TOGGLE
// ============================================
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

// ============================================
// ROLE OPTIONS
// ============================================
function toggleRoleOptions() {
    const role = document.getElementById('role').value;
    const wilayahSection = document.getElementById('wilayahSection');
    const permissionSection = document.getElementById('permissionSection');
    const infoText = document.getElementById('wilayahInfoText');
    const permInfoText = document.getElementById('permissionInfoText');
    const permNote = document.getElementById('permissionNote');
    
    if (role === 'super_admin') {
        wilayahSection.style.display = 'none';
        permissionSection.style.display = 'none';
    } else {
        wilayahSection.style.display = 'block';
        permissionSection.style.display = 'block';
        
        if (role === 'admin') {
            infoText.textContent = 'Admin dapat mengakses seluruh data di kabupaten yang dipilih. Pilih kabupaten, otomatis semua kecamatan dan desa terpilih.';
            permInfoText.textContent = 'Admin memiliki akses default ke semua modul manajemen. Centang untuk memberikan akses tambahan atau batasi akses.';
            permNote.textContent = 'Hak akses khusus akan override permission default dari role.';
        } else if (role === 'petugas_kecamatan') {
            infoText.textContent = 'Petugas Kecamatan hanya dapat mengakses data di kecamatan yang dipilih. Pilih kecamatan, otomatis semua desa terpilih.';
            permInfoText.textContent = 'Petugas Kecamatan memiliki akses terbatas. Centang untuk memberikan akses tambahan.';
            permNote.textContent = 'Hak akses khusus akan override permission default dari role.';
        } else {
            infoText.textContent = 'Pilih wilayah yang dapat diakses oleh user ini.';
            permInfoText.textContent = 'Berikan hak akses tambahan untuk user ini. Kosongkan jika menggunakan default role.';
            permNote.textContent = 'Hak akses khusus akan override permission default dari role.';
        }
    }
}

// ============================================
// PERMISSIONS
// ============================================
function checkAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
}

function uncheckAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
}

// ============================================
// WILAYAH DATA CACHE
// ============================================
var wilayahCache = {
    kecamatan: {},
    desa: {}
};

// ============================================
// LOAD DATA AJAX
// ============================================
function loadKecamatan(kabupatenId, callback) {
    if (wilayahCache.kecamatan[kabupatenId]) {
        if (callback) callback(wilayahCache.kecamatan[kabupatenId]);
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../ajax/get_kecamatan.php?kabupaten_id=' + kabupatenId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                wilayahCache.kecamatan[kabupatenId] = data;
                if (callback) callback(data);
            } catch(e) {
                console.error('Error parsing JSON:', e);
            }
        }
    };
    xhr.send();
}

function loadDesa(kecamatanId, callback) {
    if (wilayahCache.desa[kecamatanId]) {
        if (callback) callback(wilayahCache.desa[kecamatanId]);
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../ajax/get_desa.php?kecamatan_id=' + kecamatanId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                wilayahCache.desa[kecamatanId] = data;
                if (callback) callback(data);
            } catch(e) {
                console.error('Error parsing JSON:', e);
            }
        }
    };
    xhr.send();
}

// ============================================
// TOGGLE FUNCTIONS
// ============================================
function toggleAllWilayah() {
    var checked = document.getElementById('akses_semua').checked;
    var container = document.getElementById('wilayah_container');
    container.style.display = checked ? 'none' : 'block';
    
    if (!checked) {
        document.querySelectorAll('#kabupaten_grid input[type="checkbox"]').forEach(function(cb) {
            cb.checked = false;
            cb.closest('.kabupaten-card').classList.remove('selected');
        });
        document.getElementById('kecamatan_container').style.display = 'none';
        document.getElementById('desa_container').style.display = 'none';
        updateCounts();
        clearKecamatan();
        clearDesa();
    }
}

function toggleKabupaten(checkbox) {
    var kabId = checkbox.value;
    var isChecked = checkbox.checked;
    var card = checkbox.closest('.kabupaten-card');
    
    if (isChecked) {
        card.classList.add('selected');
        loadKecamatan(kabId, function(kecamatan) {
            // Auto-check semua kecamatan di kabupaten ini
            kecamatan.forEach(function(kec) {
                checkKecamatan(kec.id, true);
            });
            showKecamatan(kabId, kecamatan);
            // Auto-check semua desa
            kecamatan.forEach(function(kec) {
                loadDesa(kec.id, function(desa) {
                    desa.forEach(function(d) {
                        checkDesa(d.id, true);
                    });
                    showDesa(kec.id, desa);
                });
            });
        });
    } else {
        card.classList.remove('selected');
        removeKecamatan(kabId);
        // Hapus desa yang terkait
        document.querySelectorAll('.desa-group').forEach(function(group) {
            var kecId = group.getAttribute('data-kec-id');
            var kecCard = document.querySelector('.kecamatan-card input[value="' + kecId + '"]');
            if (kecCard && !kecCard.checked) {
                group.remove();
            }
        });
        checkKecamatanForKabupaten(kabId, false);
    }
    
    updateCounts();
}

function toggleKecamatan(checkbox) {
    var kecId = checkbox.value;
    var isChecked = checkbox.checked;
    var card = checkbox.closest('.kecamatan-card');
    
    if (isChecked) {
        card.classList.add('selected');
        loadDesa(kecId, function(desa) {
            // Auto-check semua desa
            desa.forEach(function(d) {
                checkDesa(d.id, true);
            });
            showDesa(kecId, desa);
        });
    } else {
        card.classList.remove('selected');
        removeDesa(kecId);
        // Hapus centang desa
        document.querySelectorAll('.desa-card input[value]').forEach(function(cb) {
            var desaKecId = cb.closest('.desa-group').getAttribute('data-kec-id');
            if (desaKecId == kecId) {
                cb.checked = false;
                cb.closest('.desa-card').classList.remove('selected');
            }
        });
    }
    
    updateCounts();
}

// ============================================
// CHECK/UNCHECK FUNCTIONS
// ============================================
function checkKecamatan(kecId, checked) {
    var checkbox = document.querySelector('.kecamatan-card input[value="' + kecId + '"]');
    if (checkbox) {
        checkbox.checked = checked;
        if (checked) {
            checkbox.closest('.kecamatan-card').classList.add('selected');
        } else {
            checkbox.closest('.kecamatan-card').classList.remove('selected');
        }
    }
}

function checkKecamatanForKabupaten(kabId, checked) {
    document.querySelectorAll('.kecamatan-group[data-kab-id="' + kabId + '"] .kecamatan-card input').forEach(function(cb) {
        cb.checked = checked;
        if (checked) {
            cb.closest('.kecamatan-card').classList.add('selected');
        } else {
            cb.closest('.kecamatan-card').classList.remove('selected');
        }
    });
}

function checkDesa(desaId, checked) {
    var checkbox = document.querySelector('.desa-card input[value="' + desaId + '"]');
    if (checkbox) {
        checkbox.checked = checked;
        if (checked) {
            checkbox.closest('.desa-card').classList.add('selected');
        } else {
            checkbox.closest('.desa-card').classList.remove('selected');
        }
    }
}

// ============================================
// SHOW/HIDE FUNCTIONS
// ============================================
function showKecamatan(kabupatenId, kecamatanList) {
    var container = document.getElementById('kecamatan_grid');
    var mainContainer = document.getElementById('kecamatan_container');
    
    var oldGroup = document.querySelector('.kecamatan-group[data-kab-id="' + kabupatenId + '"]');
    if (oldGroup) oldGroup.remove();
    
    if (!kecamatanList || kecamatanList.length === 0) return;
    
    var groupDiv = document.createElement('div');
    groupDiv.className = 'kecamatan-group';
    groupDiv.setAttribute('data-kab-id', kabupatenId);
    
    var headerDiv = document.createElement('div');
    headerDiv.className = 'kecamatan-group-header';
    var kabName = document.querySelector('.kabupaten-card[data-id="' + kabupatenId + '"] .wilayah-name');
    headerDiv.innerHTML = `
        <span class="group-name">${kabName ? kabName.textContent : 'Kabupaten'}</span>
        <div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleGroup(this)">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    `;
    groupDiv.appendChild(headerDiv);
    
    var gridDiv = document.createElement('div');
    gridDiv.className = 'kecamatan-grid-inner';
    
    kecamatanList.forEach(function(kec) {
        var checked = document.querySelector('.kecamatan-card input[value="' + kec.id + '"]');
        var isChecked = checked ? checked.checked : false;
        var itemDiv = document.createElement('div');
        itemDiv.className = 'wilayah-card kecamatan-card' + (isChecked ? ' selected' : '');
        itemDiv.innerHTML = `
            <label class="wilayah-checkbox">
                <input type="checkbox" name="kecamatan[]" value="${kec.id}"
                       onchange="toggleKecamatan(this)"
                       ${isChecked ? 'checked' : ''}>
                <span class="checkmark"></span>
                <div class="wilayah-label">
                    <span class="wilayah-name">${kec.nama}</span>
                    <span class="wilayah-count">${kec.desa_count || 0} desa</span>
                </div>
            </label>
        `;
        gridDiv.appendChild(itemDiv);
    });
    
    groupDiv.appendChild(gridDiv);
    container.appendChild(groupDiv);
    mainContainer.style.display = 'block';
}

function showDesa(kecamatanId, desaList) {
    var container = document.getElementById('desa_grid');
    var mainContainer = document.getElementById('desa_container');
    
    var oldGroup = document.querySelector('.desa-group[data-kec-id="' + kecamatanId + '"]');
    if (oldGroup) oldGroup.remove();
    
    if (!desaList || desaList.length === 0) return;
    
    var groupDiv = document.createElement('div');
    groupDiv.className = 'desa-group';
    groupDiv.setAttribute('data-kec-id', kecamatanId);
    
    var headerDiv = document.createElement('div');
    headerDiv.className = 'desa-group-header';
    var kecName = document.querySelector('.kecamatan-card input[value="' + kecamatanId + '"]');
    if (kecName) {
        var nameEl = kecName.closest('.kecamatan-card').querySelector('.wilayah-name');
        headerDiv.innerHTML = `
            <span class="group-name">${nameEl ? nameEl.textContent : 'Kecamatan'}</span>
            <div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleGroup(this)">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        `;
    }
    groupDiv.appendChild(headerDiv);
    
    var gridDiv = document.createElement('div');
    gridDiv.className = 'desa-grid-inner';
    
    desaList.forEach(function(desa) {
        var checked = document.querySelector('.desa-card input[value="' + desa.id + '"]');
        var isChecked = checked ? checked.checked : false;
        var itemDiv = document.createElement('div');
        itemDiv.className = 'wilayah-card desa-card' + (isChecked ? ' selected' : '');
        itemDiv.innerHTML = `
            <label class="wilayah-checkbox">
                <input type="checkbox" name="desa[]" value="${desa.id}"
                       ${isChecked ? 'checked' : ''}>
                <span class="checkmark"></span>
                <div class="wilayah-label">
                    <span class="wilayah-name">${desa.nama}</span>
                </div>
            </label>
        `;
        gridDiv.appendChild(itemDiv);
    });
    
    groupDiv.appendChild(gridDiv);
    container.appendChild(groupDiv);
    mainContainer.style.display = 'block';
}

function removeKecamatan(kabupatenId) {
    var group = document.querySelector('.kecamatan-group[data-kab-id="' + kabupatenId + '"]');
    if (group) group.remove();
    
    var remaining = document.querySelectorAll('.kecamatan-group');
    if (remaining.length === 0) {
        document.getElementById('kecamatan_container').style.display = 'none';
    }
}

function removeDesa(kecamatanId) {
    var group = document.querySelector('.desa-group[data-kec-id="' + kecamatanId + '"]');
    if (group) group.remove();
    
    var remaining = document.querySelectorAll('.desa-group');
    if (remaining.length === 0) {
        document.getElementById('desa_container').style.display = 'none';
    }
}

function clearKecamatan() {
    document.getElementById('kecamatan_grid').innerHTML = '';
    document.getElementById('kecamatan_container').style.display = 'none';
}

function clearDesa() {
    document.getElementById('desa_grid').innerHTML = '';
    document.getElementById('desa_container').style.display = 'none';
}

// ============================================
// TOGGLE GROUP
// ============================================
function toggleGroup(btn) {
    var group = btn.closest('.kecamatan-group, .desa-group');
    var grid = group.querySelector('.kecamatan-grid-inner, .desa-grid-inner');
    if (grid.style.display === 'none') {
        grid.style.display = 'grid';
        btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    } else {
        grid.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-chevron-down"></i>';
    }
}

// ============================================
// SELECT ALL FUNCTIONS
// ============================================
function selectAllKabupaten() {
    document.querySelectorAll('#kabupaten_grid input[type="checkbox"]').forEach(function(cb) {
        if (!cb.checked) {
            cb.checked = true;
            toggleKabupaten(cb);
        }
    });
}

function deselectAllKabupaten() {
    document.querySelectorAll('#kabupaten_grid input[type="checkbox"]').forEach(function(cb) {
        if (cb.checked) {
            cb.checked = false;
            toggleKabupaten(cb);
        }
    });
}

function selectAllKecamatan() {
    document.querySelectorAll('#kecamatan_grid input[type="checkbox"]').forEach(function(cb) {
        if (!cb.checked) {
            cb.checked = true;
            toggleKecamatan(cb);
        }
    });
}

function deselectAllKecamatan() {
    document.querySelectorAll('#kecamatan_grid input[type="checkbox"]').forEach(function(cb) {
        if (cb.checked) {
            cb.checked = false;
            toggleKecamatan(cb);
        }
    });
}

function selectAllDesa() {
    document.querySelectorAll('#desa_grid input[type="checkbox"]').forEach(function(cb) {
        if (!cb.checked) {
            cb.checked = true;
            cb.closest('.desa-card').classList.add('selected');
        }
    });
    updateCounts();
}

function deselectAllDesa() {
    document.querySelectorAll('#desa_grid input[type="checkbox"]').forEach(function(cb) {
        if (cb.checked) {
            cb.checked = false;
            cb.closest('.desa-card').classList.remove('selected');
        }
    });
    updateCounts();
}

function toggleAllKecamatanVisibility() {
    document.querySelectorAll('.kecamatan-group .kecamatan-grid-inner').forEach(function(grid) {
        if (grid.style.display === 'none') {
            grid.style.display = 'grid';
            var btn = grid.closest('.kecamatan-group').querySelector('.kecamatan-group-header .btn');
            if (btn) btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
        } else {
            grid.style.display = 'none';
            var btn = grid.closest('.kecamatan-group').querySelector('.kecamatan-group-header .btn');
            if (btn) btn.innerHTML = '<i class="fas fa-chevron-down"></i>';
        }
    });
}

function toggleAllDesaVisibility() {
    document.querySelectorAll('.desa-group .desa-grid-inner').forEach(function(grid) {
        if (grid.style.display === 'none') {
            grid.style.display = 'grid';
            var btn = grid.closest('.desa-group').querySelector('.desa-group-header .btn');
            if (btn) btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
        } else {
            grid.style.display = 'none';
            var btn = grid.closest('.desa-group').querySelector('.desa-group-header .btn');
            if (btn) btn.innerHTML = '<i class="fas fa-chevron-down"></i>';
        }
    });
}

// ============================================
// UPDATE COUNTS
// ============================================
function updateCounts() {
    var kabChecked = document.querySelectorAll('#kabupaten_grid input[type="checkbox"]:checked').length;
    document.getElementById('kabupaten_count').textContent = kabChecked + ' terpilih';
    
    var kecChecked = document.querySelectorAll('#kecamatan_grid input[type="checkbox"]:checked').length;
    document.getElementById('kecamatan_count').textContent = kecChecked + ' terpilih';
    
    var desaChecked = document.querySelectorAll('#desa_grid input[type="checkbox"]:checked').length;
    document.getElementById('desa_count').textContent = desaChecked + ' terpilih';
}

// ============================================
// INITIALIZE
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    toggleRoleOptions();
    
    // Load selected kecamatan
    var selectedKabupaten = document.querySelectorAll('#kabupaten_grid input[type="checkbox"]:checked');
    selectedKabupaten.forEach(function(cb) {
        toggleKabupaten(cb);
    });
    
    // Load selected desa
    var selectedKecamatan = document.querySelectorAll('#kecamatan_grid input[type="checkbox"]:checked');
    selectedKecamatan.forEach(function(cb) {
        toggleKecamatan(cb);
    });
    
    updateCounts();
});
</script>

<?php include 'include/admin_footer.php'; ?>
</body>
</html>