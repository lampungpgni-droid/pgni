<?php
// admin/pengurus_edit.php - Edit Pengurus
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// PATH
// ============================================
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

$title = 'Edit Pengurus';
$error = '';
$success = '';

// ============================================
// AMBIL ID DARI URL
// ============================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: pengurus.php?error=notfound');
    exit;
}

// ============================================
// AMBIL DATA PENGURUS
// ============================================
$query = "SELECT * FROM pengurus WHERE id = $id";
$result = mysqli_query($conn, $query);
$pengurus = mysqli_fetch_assoc($result);

if (!$pengurus) {
    header('Location: pengurus.php?error=notfound');
    exit;
}

// ============================================
// CEK KOLOM YANG ADA
// ============================================
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM pengurus");
$existing_columns = [];
while ($col = mysqli_fetch_assoc($check_columns)) {
    $existing_columns[] = $col['Field'];
}

$has_bio = in_array('bio', $existing_columns);
$has_desa = in_array('desa_id', $existing_columns);
$has_kecamatan = in_array('kecamatan_id', $existing_columns);
$has_kabupaten = in_array('kabupaten_id', $existing_columns);
$has_urutan = in_array('urutan', $existing_columns);
$has_updated_at = in_array('updated_at', $existing_columns);
$has_parent_id = in_array('parent_id', $existing_columns);
$has_is_ketua_bidang = in_array('is_ketua_bidang', $existing_columns);

// ============================================
// AMBIL DAFTAR KABUPATEN
// ============================================
$kabupaten_query = "SELECT id, nama FROM kabupaten ORDER BY nama";
$kabupaten_list = mysqli_query($conn, $kabupaten_query);

// ============================================
// AMBIL DAFTAR KETUA BIDANG (untuk dropdown parent)
// ============================================
$ketua_bidang_query = "SELECT id, nama, jabatan FROM pengurus WHERE is_ketua_bidang = 1 AND status = 'aktif' AND id != $id ORDER BY nama";
$ketua_bidang_list = mysqli_query($conn, $ketua_bidang_query);

// ============================================
// DAFTAR JABATAN LENGKAP
// ============================================
$jabatan_list = [
    'Pembina',
    'Penasehat',
    'Pengawas',
    'Ketua Umum',
    'Wakil Ketua',
    'Sekretaris Umum',
    'Wakil Sekretaris',
    'Bendahara Umum',
    'Wakil Bendahara',
    'Bidang Organisasi dan Kaderisasi',
    'Bidang Ketenagakerjaan dan Kesejahteraan',
    'Bidang Pendidikan dan Pelatihan',
    'Bidang Informasi dan Komunikasi',
    'Bidang Pengembangan Karir dan Profesi',
    'Bidang Penelitian dan Pengembangan',
    'Bidang Humas',
    'Bidang Da\'wah',
    'Bidang Pengabdian Masyarakat',
    'Bidang Advokasi dan Perlindungan Hukum',
    'Anggota',
    'Lainnya'
];

// ============================================
// FUNGSI HELPER
// ============================================
function safe_html($value) {
    return htmlspecialchars($value ?? '');
}

// ============================================
// PROSES UPDATE DATA
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil semua field dari POST
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, trim($_POST['nama'])) : '';
    $jabatan = isset($_POST['jabatan']) ? mysqli_real_escape_string($conn, trim($_POST['jabatan'])) : '';
    $is_ketua_bidang = isset($_POST['is_ketua_bidang']) ? (int)$_POST['is_ketua_bidang'] : 0;
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    $tempat_lahir = isset($_POST['tempat_lahir']) ? mysqli_real_escape_string($conn, trim($_POST['tempat_lahir'])) : '';
    $tanggal_lahir = isset($_POST['tanggal_lahir']) && !empty($_POST['tanggal_lahir']) ? mysqli_real_escape_string($conn, $_POST['tanggal_lahir']) : '';
    $bio = isset($_POST['bio']) ? mysqli_real_escape_string($conn, trim($_POST['bio'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $no_telp = isset($_POST['no_telp']) ? mysqli_real_escape_string($conn, trim($_POST['no_telp'])) : '';
    $alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, trim($_POST['alamat'])) : '';
    $kabupaten_id = isset($_POST['kabupaten_id']) ? (int)$_POST['kabupaten_id'] : 0;
    $kecamatan_id = isset($_POST['kecamatan_id']) ? (int)$_POST['kecamatan_id'] : 0;
    $desa_id = isset($_POST['desa_id']) ? (int)$_POST['desa_id'] : 0;
    $urutan = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 0;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'aktif';
    
    // Validasi
    if (empty($nama) || empty($jabatan)) {
        $error = 'Nama dan Jabatan wajib diisi!';
    } else {
        // Upload foto baru jika ada
        $foto = $pengurus['foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $upload = upload_file($_FILES['foto'], 'pengurus', ['jpg','jpeg','png','gif','webp'], 5242880);
            if ($upload['status']) {
                // Hapus foto lama
                if ($foto && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $foto)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $foto);
                }
                $foto = $upload['nama_file'];
            } else {
                $error = 'Upload foto gagal: ' . $upload['message'];
            }
        }
        
        if (empty($error)) {
            // ==========================================
            // BUILD QUERY UPDATE - SEMUA FIELD
            // ==========================================
            $set_parts = [
                "nama = '$nama'",
                "jabatan = '$jabatan'",
                "tempat_lahir = " . (!empty($tempat_lahir) ? "'$tempat_lahir'" : "NULL"),
                "tanggal_lahir = " . (!empty($tanggal_lahir) ? "'$tanggal_lahir'" : "NULL"),
                "foto = '$foto'",
                "email = " . (!empty($email) ? "'$email'" : "NULL"),
                "no_telp = " . (!empty($no_telp) ? "'$no_telp'" : "NULL"),
                "alamat = " . (!empty($alamat) ? "'$alamat'" : "NULL"),
                "status = '$status'"
            ];
            
            // JENIS KELAMIN - PERBAIKAN UTAMA
            // Hanya masukkan jika nilai 'L' atau 'P', selain itu NULL
            if ($jenis_kelamin === 'L' || $jenis_kelamin === 'P') {
                $set_parts[] = "jenis_kelamin = '$jenis_kelamin'";
            } else {
                $set_parts[] = "jenis_kelamin = NULL";
            }
            
            if ($has_is_ketua_bidang) {
                $set_parts[] = "is_ketua_bidang = $is_ketua_bidang";
            }
            
            if ($has_parent_id) {
                $set_parts[] = "parent_id = " . ($parent_id > 0 ? $parent_id : "NULL");
            }
            
            if ($has_bio) {
                $set_parts[] = "bio = " . (!empty($bio) ? "'$bio'" : "NULL");
            }
            
            if ($has_kabupaten) {
                $set_parts[] = "kabupaten_id = " . ($kabupaten_id > 0 ? $kabupaten_id : "NULL");
            }
            
            if ($has_kecamatan) {
                $set_parts[] = "kecamatan_id = " . ($kecamatan_id > 0 ? $kecamatan_id : "NULL");
            }
            
            if ($has_desa) {
                $set_parts[] = "desa_id = " . ($desa_id > 0 ? $desa_id : "NULL");
            }
            
            if ($has_urutan) {
                $set_parts[] = "urutan = $urutan";
            }
            
            if ($has_updated_at) {
                $set_parts[] = "updated_at = NOW()";
            }
            
            $query = "UPDATE pengurus SET " . implode(', ', $set_parts) . " WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                header('Location: pengurus.php?msg=edit');
                exit;
            } else {
                $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
            }
        }
    }
}

include $root_path . '/admin/include/admin_header.php';
?>

<style>
.pengurus-edit-wrap * { box-sizing: border-box; }
.pengurus-edit-wrap { width: 100%; max-width: 100%; }

.pengurus-edit-wrap .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #eef2f7;
}
.pengurus-edit-wrap .page-header h2 {
    font-size: 1.3rem;
    color: #1a1a2e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pengurus-edit-wrap .page-header h2 i { color: #d4a847; }
.pengurus-edit-wrap .page-header p {
    color: #7f8c8d;
    font-size: 0.85rem;
    margin: 2px 0 0 0;
}
.pengurus-edit-wrap .page-header .btn-back {
    padding: 8px 18px;
    background: #95a5a6;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.pengurus-edit-wrap .page-header .btn-back:hover { background: #7f8c8d; }

.pengurus-edit-wrap .alert {
    padding: 10px 16px;
    border-radius: 6px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}
.pengurus-edit-wrap .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.pengurus-edit-wrap .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

.pengurus-edit-wrap .form-wrap {
    background: #fff;
    padding: 25px 30px;
    border-radius: 8px;
    border: 1px solid #eef2f7;
}
.pengurus-edit-wrap .form-row {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f5f5f5;
}
.pengurus-edit-wrap .form-row:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.pengurus-edit-wrap .form-row label {
    min-width: 150px;
    padding-top: 8px;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}
.pengurus-edit-wrap .form-row label .required { color: #e74c3c; }
.pengurus-edit-wrap .form-row .field {
    flex: 1;
}
.pengurus-edit-wrap .form-row .field input,
.pengurus-edit-wrap .form-row .field select,
.pengurus-edit-wrap .form-row .field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #eef2f7;
    border-radius: 6px;
    font-size: 0.9rem;
    font-family: inherit;
    transition: all 0.3s;
}
.pengurus-edit-wrap .form-row .field input:focus,
.pengurus-edit-wrap .form-row .field select:focus,
.pengurus-edit-wrap .form-row .field textarea:focus {
    border-color: #1a6e3a;
    outline: none;
    box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.08);
}
.pengurus-edit-wrap .form-row .field .help {
    font-size: 0.75rem;
    color: #999;
    margin-top: 4px;
    display: block;
}
.pengurus-edit-wrap .form-row .field .current-photo {
    margin-bottom: 10px;
}
.pengurus-edit-wrap .form-row .field .current-photo img {
    max-width: 120px;
    max-height: 120px;
    border-radius: 8px;
    border: 2px solid #eef2f7;
    padding: 3px;
    object-fit: cover;
}

.pengurus-edit-wrap .form-actions {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 2px solid #eef2f7;
    margin-top: 10px;
    flex-wrap: wrap;
}
.pengurus-edit-wrap .form-actions .btn {
    padding: 10px 28px;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.pengurus-edit-wrap .form-actions .btn-primary {
    background: linear-gradient(135deg, #1a6e3a, #2d8f52);
    color: #fff;
}
.pengurus-edit-wrap .form-actions .btn-primary:hover {
    background: linear-gradient(135deg, #0e4a26, #1a6e3a);
}
.pengurus-edit-wrap .form-actions .btn-secondary {
    background: #95a5a6;
    color: #fff;
}
.pengurus-edit-wrap .form-actions .btn-secondary:hover { background: #7f8c8d; }
.pengurus-edit-wrap .form-actions .btn-danger {
    background: #e74c3c;
    color: #fff;
}
.pengurus-edit-wrap .form-actions .btn-danger:hover { background: #c0392b; }

/* Grid 2 kolom */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 30px;
}

/* Sembunyikan parent_id jika is_ketua_bidang = 1 */
#parentGroup.hidden {
    display: none;
}

@media (max-width: 768px) {
    .pengurus-edit-wrap .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    .pengurus-edit-wrap .page-header .btn-back {
        width: 100%;
        justify-content: center;
    }
    .pengurus-edit-wrap .form-wrap {
        padding: 18px 15px;
    }
    .pengurus-edit-wrap .form-row {
        flex-direction: column;
        gap: 6px;
        margin-bottom: 12px;
        padding-bottom: 12px;
    }
    .pengurus-edit-wrap .form-row label {
        min-width: auto;
        width: 100%;
        padding-top: 0;
    }
    .pengurus-edit-wrap .form-actions {
        flex-direction: column;
    }
    .pengurus-edit-wrap .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<div class="pengurus-edit-wrap">

    <!-- HEADER -->
    <div class="page-header">
        <div>
            <h2><i class="fas fa-user-edit"></i> Edit Pengurus</h2>
            <p>Perbarui data pengurus PGNI Lampung</p>
        </div>
        <a href="pengurus.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-wrap">
        <form action="" method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <!-- ===== KOLOM KIRI ===== -->
                <div>

                    <!-- Nama -->
                    <div class="form-row">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <div class="field">
                            <input type="text" name="nama" value="<?php echo safe_html($pengurus['nama']); ?>" required>
                        </div>
                    </div>

                    <!-- Jabatan -->
                    <div class="form-row">
                        <label>Jabatan <span class="required">*</span></label>
                        <div class="field">
                            <select name="jabatan" required>
                                <option value="">Pilih Jabatan</option>
                                <?php foreach ($jabatan_list as $j): ?>
                                    <option value="<?php echo $j; ?>" <?php echo ($pengurus['jabatan'] == $j) ? 'selected' : ''; ?>>
                                        <?php echo $j; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="form-row">
                        <label>Jenis Kelamin</label>
                        <div class="field">
                            <select name="jenis_kelamin">
                                <option value="">Pilih</option>
                                <option value="L" <?php echo ($pengurus['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($pengurus['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tempat Lahir -->
                    <div class="form-row">
                        <label>Tempat Lahir</label>
                        <div class="field">
                            <input type="text" name="tempat_lahir" value="<?php echo safe_html($pengurus['tempat_lahir']); ?>" placeholder="Contoh: Bandar Lampung">
                        </div>
                    </div>

                    <!-- Tanggal Lahir -->
                    <div class="form-row">
                        <label>Tanggal Lahir</label>
                        <div class="field">
                            <input type="date" name="tanggal_lahir" value="<?php echo safe_html($pengurus['tanggal_lahir']); ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-row">
                        <label>Email</label>
                        <div class="field">
                            <input type="email" name="email" value="<?php echo safe_html($pengurus['email']); ?>" placeholder="email@domain.com">
                        </div>
                    </div>

                </div>

                <!-- ===== KOLOM KANAN ===== -->
                <div>

                    <!-- No Telepon -->
                    <div class="form-row">
                        <label>No. Telepon</label>
                        <div class="field">
                            <input type="text" name="no_telp" value="<?php echo safe_html($pengurus['no_telp']); ?>" placeholder="0812-3456-7890">
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="form-row">
                        <label>Alamat</label>
                        <div class="field">
                            <textarea name="alamat" rows="2" placeholder="Masukkan alamat lengkap"><?php echo safe_html($pengurus['alamat']); ?></textarea>
                        </div>
                    </div>

                    <?php if ($has_bio): ?>
                    <!-- Bio -->
                    <div class="form-row">
                        <label>Bio / Deskripsi</label>
                        <div class="field">
                            <textarea name="bio" rows="3" placeholder="Tulis bio atau deskripsi singkat"><?php echo safe_html($pengurus['bio']); ?></textarea>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_is_ketua_bidang): ?>
                    <!-- Status Ketua Bidang -->
                    <div class="form-row">
                        <label>Status</label>
                        <div class="field">
                            <select name="is_ketua_bidang" id="is_ketua_bidang">
                                <option value="0" <?php echo ($pengurus['is_ketua_bidang'] == 0) ? 'selected' : ''; ?>>Anggota Biasa</option>
                                <option value="1" <?php echo ($pengurus['is_ketua_bidang'] == 1) ? 'selected' : ''; ?>>Ketua Bidang</option>
                            </select>
                            <span class="help">Pilih "Ketua Bidang" jika ini adalah ketua dari suatu bidang</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_parent_id): ?>
                    <!-- Di Bawah Ketua Bidang -->
                    <div class="form-row" id="parentGroup">
                        <label>Di Bawah Ketua Bidang</label>
                        <div class="field">
                            <select name="parent_id" id="parent_id">
                                <option value="">-- Tidak ada (Independen) --</option>
                                <?php 
                                if ($ketua_bidang_list && mysqli_num_rows($ketua_bidang_list) > 0):
                                    while ($kb = mysqli_fetch_assoc($ketua_bidang_list)): 
                                ?>
                                    <option value="<?php echo $kb['id']; ?>" <?php echo ($pengurus['parent_id'] == $kb['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kb['nama']); ?> (<?php echo htmlspecialchars($kb['jabatan']); ?>)
                                    </option>
                                <?php endwhile; 
                                endif; 
                                ?>
                            </select>
                            <span class="help">Pilih Ketua Bidang jika anggota ini berada di bawah bidang tertentu</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_kabupaten): ?>
                    <!-- Kabupaten -->
                    <div class="form-row">
                        <label>Kabupaten</label>
                        <div class="field">
                            <select name="kabupaten_id" id="kabupaten_id_edit">
                                <option value="">Pilih Kabupaten</option>
                                <?php 
                                if ($kabupaten_list && mysqli_num_rows($kabupaten_list) > 0):
                                    mysqli_data_seek($kabupaten_list, 0);
                                    while ($kab = mysqli_fetch_assoc($kabupaten_list)): 
                                ?>
                                    <option value="<?php echo $kab['id']; ?>" <?php echo ($pengurus['kabupaten_id'] == $kab['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kab['nama']); ?>
                                    </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_kecamatan): ?>
                    <!-- Kecamatan -->
                    <div class="form-row">
                        <label>Kecamatan</label>
                        <div class="field">
                            <select name="kecamatan_id" id="kecamatan_id_edit">
                                <option value="">Pilih Kecamatan</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_desa): ?>
                    <!-- Desa -->
                    <div class="form-row">
                        <label>Desa/Kelurahan</label>
                        <div class="field">
                            <select name="desa_id" id="desa_id_edit">
                                <option value="">Pilih Desa</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Urutan -->
                    <div class="form-row">
                        <label>Urutan Tampil</label>
                        <div class="field">
                            <input type="number" name="urutan" value="<?php echo isset($pengurus['urutan']) ? $pengurus['urutan'] : 0; ?>" min="0">
                            <span class="help">Semakin kecil angka, semakin atas tampilnya</span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-row">
                        <label>Status</label>
                        <div class="field">
                            <select name="status">
                                <option value="aktif" <?php echo $pengurus['status'] == 'aktif' ? 'selected' : ''; ?>>✅ Aktif</option>
                                <option value="nonaktif" <?php echo $pengurus['status'] == 'nonaktif' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <!-- Foto -->
                    <div class="form-row">
                        <label>Foto</label>
                        <div class="field">
                            <?php if (!empty($pengurus['foto']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $pengurus['foto'])): ?>
                                <div class="current-photo">
                                    <img src="../assets/images/pengurus/<?php echo safe_html($pengurus['foto']); ?>" alt="Foto Pengurus">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto" accept="image/*">
                            <span class="help">Format: JPG, PNG, GIF, WebP (Max 5MB)</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Data</button>
                <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                <a href="pengurus.php" class="btn btn-danger"><i class="fas fa-times"></i> Batal</a>
            </div>

        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // TOGGLE PARENT ID BERDASARKAN IS_KETUA_BIDANG
    // ============================================
    const isKetuaBidang = document.getElementById('is_ketua_bidang');
    const parentGroup = document.getElementById('parentGroup');
    const parentSelect = document.getElementById('parent_id');

    function toggleParentGroup() {
        if (isKetuaBidang && isKetuaBidang.value === '1') {
            parentGroup.classList.add('hidden');
            if (parentSelect) parentSelect.value = '';
        } else {
            parentGroup.classList.remove('hidden');
        }
    }

    if (isKetuaBidang) {
        isKetuaBidang.addEventListener('change', toggleParentGroup);
        // Jalankan saat load
        toggleParentGroup();
    }

    // ============================================
    // LOAD KECAMATAN & DESA
    // ============================================
    const kabupatenSelect = document.getElementById('kabupaten_id_edit');
    const kecamatanSelect = document.getElementById('kecamatan_id_edit');
    const desaSelect = document.getElementById('desa_id_edit');

    const currentKabupaten = <?php echo isset($pengurus['kabupaten_id']) ? (int)$pengurus['kabupaten_id'] : 0; ?>;
    const currentKecamatan = <?php echo isset($pengurus['kecamatan_id']) ? (int)$pengurus['kecamatan_id'] : 0; ?>;
    const currentDesa = <?php echo isset($pengurus['desa_id']) ? (int)$pengurus['desa_id'] : 0; ?>;

    function loadKecamatan(kabupatenId, selectedId = 0) {
        if (!kecamatanSelect) return;
        kecamatanSelect.innerHTML = '<option value="">Loading...</option>';
        if (kabupatenId) {
            fetch('../ajax/get_kecamatan.php?kabupaten_id=' + kabupatenId)
                .then(response => response.json())
                .then(data => {
                    kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    data.forEach(kec => {
                        const selected = (kec.id == selectedId) ? 'selected' : '';
                        kecamatanSelect.innerHTML += `<option value="${kec.id}" ${selected}>${kec.nama}</option>`;
                    });
                    if (selectedId > 0) loadDesa(selectedId, currentDesa);
                })
                .catch(() => {
                    kecamatanSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        } else {
            kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
            if (desaSelect) desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
        }
    }

    function loadDesa(kecamatanId, selectedId = 0) {
        if (!desaSelect) return;
        desaSelect.innerHTML = '<option value="">Loading...</option>';
        if (kecamatanId) {
            fetch('../ajax/get_desa.php?kecamatan_id=' + kecamatanId)
                .then(response => response.json())
                .then(data => {
                    desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
                    data.forEach(desa => {
                        const selected = (desa.id == selectedId) ? 'selected' : '';
                        desaSelect.innerHTML += `<option value="${desa.id}" ${selected}>${desa.nama}</option>`;
                    });
                })
                .catch(() => {
                    desaSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        } else {
            desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
        }
    }

    if (kabupatenSelect) {
        kabupatenSelect.addEventListener('change', function() {
            loadKecamatan(this.value, 0);
        });
        if (currentKabupaten > 0) {
            loadKecamatan(currentKabupaten, currentKecamatan);
        }
    }

    if (kecamatanSelect) {
        kecamatanSelect.addEventListener('change', function() {
            loadDesa(this.value, 0);
        });
    }

    // ============================================
    // PREVIEW FOTO
    // ============================================
    const fotoInput = document.querySelector('input[name="foto"]');
    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.current-photo');
                    if (preview) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    } else {
                        const container = this.closest('.field');
                        const div = document.createElement('div');
                        div.className = 'current-photo';
                        div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        container.insertBefore(div, container.querySelector('input[type="file"]'));
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

});
</script>

<?php include $root_path . '/admin/include/admin_footer.php'; ?>