<?php
// pengurus.php - Halaman Bagan Struktur Organisasi PGNI Lampung
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root_path = __DIR__;
require_once $root_path . '/config/database.php';
require_once $root_path . '/include/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = 'Struktur Organisasi PGNI Lampung';

// CEK TABEL PENGURUS
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pengurus'");
if (mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS `pengurus` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `parent_id` INT(11) NULL,
        `nama` VARCHAR(255) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `is_ketua_bidang` TINYINT(1) DEFAULT 0,
        `jenis_kelamin` ENUM('L', 'P') NULL,
        `tempat_lahir` VARCHAR(100) NULL,
        `tanggal_lahir` DATE NULL,
        `foto` VARCHAR(255) NULL,
        `bio` TEXT NULL,
        `email` VARCHAR(100) NULL,
        `no_telp` VARCHAR(20) NULL,
        `alamat` TEXT NULL,
        `kabupaten_id` INT(11) NULL,
        `kecamatan_id` INT(11) NULL,
        `desa_id` INT(11) NULL,
        `urutan` INT(11) DEFAULT 0,
        `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_jabatan` (`jabatan`),
        INDEX `idx_parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_table);
}

// AMBIL DATA
$query = "SELECT * FROM pengurus WHERE status = 'aktif' ORDER BY urutan ASC";
$pengurus_list = mysqli_query($conn, $query);

$pengurus_array = [];
while ($row = mysqli_fetch_assoc($pengurus_list)) {
    $foto_path = '';
    if (!empty($row['foto'])) {
        $paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/assets/images/pengurus/' . $row['foto'],
            $_SERVER['DOCUMENT_ROOT'] . '/pgnil/assets/images/pengurus/' . $row['foto'],
            __DIR__ . '/assets/images/pengurus/' . $row['foto']
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $foto_path = (strpos($path, '/pgnil/') !== false) ? '/pgnil/assets/images/pengurus/' . $row['foto'] : '/assets/images/pengurus/' . $row['foto'];
                break;
            }
        }
    }
    
    $pengurus_array[] = array(
        'id'            => (int)$row['id'],
        'parent_id'     => isset($row['parent_id']) ? (int)$row['parent_id'] : 0,
        'nama'          => $row['nama'],
        'jabatan'       => $row['jabatan'],
        'is_ketua_bidang' => isset($row['is_ketua_bidang']) ? (int)$row['is_ketua_bidang'] : 0,
        'jenis_kelamin' => $row['jenis_kelamin'],
        'tempat_lahir'  => $row['tempat_lahir'],
        'tanggal_lahir' => $row['tanggal_lahir'],
        'alamat'        => $row['alamat'],
        'no_telp'       => $row['no_telp'],
        'email'         => $row['email'],
        'foto'          => $foto_path
    );
}

include $root_path . '/include/header.php';
?>

<style>
/* ============================================================
   ROOT VARIABLES & BASE - Dari flow-chart.css
   ============================================================ */
:root {
  --tosca-primary: #14b8a6;
  --tosca-light: #f0fdfa;
  --bg: #f8fafc;
  --line: #99f6e4;
  --text-black: #585858;
  --text-dark: #0f172a;
  --text-muted: #64748b;
  --border-light: #e2e8f0;
  --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-hover: 0 20px 25px -5px rgba(20, 184, 166, 0.25);
  --transition-smooth: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    padding: 20px 10px;
}

/* ============================================================
   BOX WRAPPER - Dari flow-chart.css
   ============================================================ */
.box {
    background-color: #fff;
    padding-top: 30px;
    padding-bottom: 30px;
    margin-top: 10px;
    margin-right: 20px;
    margin-bottom: 20px;
    margin-left: 20px;
    border: 1px solid #dee2e6;
    border-top: 4px solid #118a95;
    border-radius: 4px;
    position: relative;
    overflow-x: auto;
}

.flow-container {
    max-width: 1100px;
    margin: auto;
    position: relative;
    background-color: #fff;
}

/* ============================================================
   PAGE TITLE
   ============================================================ */
.page-title {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 4px;
}

.page-subtitle {
    text-align: center;
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 30px;
}

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 20px 30px;
}

/* ============================================================
   CARD STYLING - Terinspirasi dari .cerd di flow-chart.css
   ============================================================ */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 12px 14px;
    text-align: center;
    border: 1.5px solid var(--line);
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: var(--transition-smooth);
    cursor: pointer;
    position: relative;
    width: 100%;
    max-width: 220px;
    box-sizing: border-box;
}

.card:hover {
    transform: translateY(-12px) scale(1.06);
    box-shadow: var(--shadow-hover);
    border-color: var(--tosca-primary);
    background-color: #ffffff;
}

/* Card Primary - Dari .cerd.primary */
.card.primary {
    border: 1.5px solid var(--tosca-primary);
    background: var(--tosca-light);
}

.card.primary:hover {
    background: #ffffff;
}

/* ============================================================
   AVATAR - Dengan ikon style dari flow-chart
   ============================================================ */
.avatar-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    margin-bottom: 8px;
    overflow: hidden;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    background-color: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.4s ease;
}

.card:hover .avatar-wrapper {
    transform: rotate(5deg) scale(1.1);
}

.avatar-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
}

.avatar-fallback.blue { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
.avatar-fallback.green { background: linear-gradient(135deg, #065f46, #0d9488); }
.avatar-fallback.purple { background: linear-gradient(135deg, #5b21b6, #7c3aed); }
.avatar-fallback.orange { background: linear-gradient(135deg, #b45309, #d97706); }
.avatar-fallback.pink { background: linear-gradient(135deg, #be185d, #ec4899); }
.avatar-fallback.tosca { background: linear-gradient(135deg, #0d9488, #14b8a6); }
.avatar-fallback.slate { background: linear-gradient(135deg, #475569, #94a3b8); }

/* ============================================================
   CARD TEXT - Menggunakan warna dari flow-chart.css
   ============================================================ */
.card .jabatan {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    line-height: 1.2;
    width: 100%;
    color: var(--text-black);
}

.card .nama {
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.3;
    color: var(--text-dark);
    width: 100%;
    word-wrap: break-word;
}

/* ============================================================
   CARD VARIANTS - Border warna sesuai jabatan
   ============================================================ */
.card.pembina { border-top: 4px solid #d97706; }
.card.pembina .jabatan { color: #d97706; }

.card.penasehat { border-top: 4px solid #7c3aed; }
.card.penasehat .jabatan { color: #7c3aed; }

.card.pengawas { border-top: 4px solid #ec4899; }
.card.pengawas .jabatan { color: #ec4899; }

.card.ketua {
    border-top: 4px solid #1e3a8a;
    max-width: 240px;
    padding: 16px;
}
.card.ketua .jabatan { color: #1e3a8a; }
.card.ketua .avatar-wrapper { width: 72px; height: 72px; }
.card.ketua .nama { font-size: 0.95rem; }

.card.pimpinan { border-top: 4px solid #0d9488; }
.card.pimpinan .jabatan { color: #0d9488; }

.card.departemen {
    border-top: 3px solid #7c3aed;
    max-width: 180px;
    padding: 10px;
}
.card.departemen .jabatan { color: #7c3aed; }
.card.departemen .avatar-wrapper { width: 44px; height: 44px; }
.card.departemen .nama { font-size: 0.75rem; }

/* Card Anggota - Lebih kecil */
.card.anggota {
    border-top: 2px solid #94a3b8;
    max-width: 160px;
    padding: 8px 10px;
}
.card.anggota .jabatan { color: #94a3b8; font-size: 0.55rem; }
.card.anggota .avatar-wrapper { width: 36px; height: 36px; }
.card.anggota .nama { font-size: 0.7rem; }

/* ============================================================
   CONNECTOR - Terinspirasi dari flow-chart.css
   ============================================================ */
.connector-v-large {
    width: 2px;
    height: 32px;
    background: var(--line);
    margin: 0 auto;
    position: relative;
}

.connector-v-large::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: -4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--tosca-primary);
}

/* Horizontal connector untuk level */
.level-connector {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0;
    padding: 0 20px;
    position: relative;
}

.level-connector::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 2px;
    background: var(--line);
    z-index: 0;
}

.level-connector .level-item {
    position: relative;
    z-index: 1;
    padding: 0 10px;
}

/* ============================================================
   GROUP BOX - Dengan gaya dari flow-chart
   ============================================================ */
.top-section {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
    width: 100%;
}

.group-box {
    border: 2px dashed var(--line);
    border-radius: 14px;
    padding: 16px;
    background: rgba(255,255,255,0.6);
    flex: 1 1 280px;
    max-width: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.group-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 14px;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.group-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    align-items: center;
}

.group-list.scrollable {
    max-height: 380px;
    overflow-y: auto;
    padding-right: 4px;
}

/* ============================================================
   LEVELS - Terinspirasi dari .row .col di flow-chart
   ============================================================ */
.level {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px;
    width: 100%;
    padding: 6px 0;
}

.level-center {
    display: flex;
    justify-content: center;
    width: 100%;
}

/* ============================================================
   SECTION BADGE
   ============================================================ */
.section-badge {
    background: var(--tosca-primary);
    color: #ffffff;
    padding: 6px 24px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    box-shadow: 0 2px 8px rgba(20, 184, 166, 0.25);
    margin: 8px 0;
}

/* ============================================================
   BIDANG WRAPPER - Grid dengan gaya modern
   ============================================================ */
.bidang-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    width: 100%;
    padding-top: 14px;
    position: relative;
}

.bidang-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 5%;
    right: 5%;
    height: 2px;
    background: var(--line);
}

.bidang-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #ffffff;
    border-radius: 12px;
    padding: 12px;
    border: 1px solid var(--border-light);
    box-shadow: var(--shadow-card);
    transition: var(--transition-smooth);
}

.bidang-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--tosca-primary);
}

.bidang-item .ketua-bidang {
    margin-bottom: 8px;
    width: 100%;
}

.bidang-item .ketua-bidang .card {
    max-width: 100%;
}

.bidang-item .anggota-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    width: 100%;
}

.bidang-item .anggota-list .card.anggota {
    max-width: 140px;
}

/* ============================================================
   MODAL - Dengan gaya dari flow-chart
   ============================================================ */
#profile-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

#profile-modal.show {
    display: flex;
    opacity: 1;
}

#profile-modal .modal-backdrop {
    position: absolute;
    inset: 0;
    cursor: pointer;
}

#profile-modal .modal-card {
    background: #ffffff;
    width: 100%;
    max-width: 420px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    transform: scale(0.9);
    transition: transform 0.3s ease;
    position: relative;
    z-index: 10;
    border: 1px solid var(--line);
}

#profile-modal.show .modal-card {
    transform: scale(1);
}

#profile-modal .modal-header {
    padding: 24px 24px 20px;
    text-align: center;
    border-bottom: 1px solid var(--line);
    background: var(--tosca-light);
    position: relative;
}

#profile-modal .modal-close {
    position: absolute;
    top: 12px;
    right: 16px;
    background: #fff;
    border: 1px solid var(--line);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-smooth);
}

#profile-modal .modal-close:hover {
    background: var(--tosca-primary);
    color: #fff;
    transform: rotate(90deg);
}

#profile-modal .modal-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 12px;
    border: 4px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

#profile-modal .modal-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#profile-modal .modal-avatar .avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
}

#profile-modal .modal-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-dark);
}

#profile-modal .modal-badge {
    display: inline-block;
    margin-top: 6px;
    padding: 4px 16px;
    background: var(--tosca-light);
    color: var(--tosca-primary);
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 20px;
    border: 1px solid var(--line);
}

#profile-modal .modal-body {
    padding: 20px 24px 24px;
}

#profile-modal .modal-body .info-item {
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

#profile-modal .modal-body .info-item:last-child {
    border-bottom: none;
}

#profile-modal .modal-body .info-label {
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted);
    display: block;
    margin-bottom: 2px;
}

#profile-modal .modal-body .info-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-dark);
}

#profile-modal .modal-body .no-data {
    text-align: center;
    color: var(--text-muted);
    font-style: italic;
    font-size: 0.8rem;
    padding: 16px 0;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .bidang-wrapper { grid-template-columns: 1fr 1fr; }
    .bidang-wrapper::before { display: none; }
    .group-box { max-width: 100%; }
    .top-section { flex-direction: column; align-items: center; }
    .level { flex-direction: column; align-items: center; }
    .card { max-width: 280px; }
    .card.ketua { max-width: 280px; }
    .card.departemen { max-width: 100%; }
    .card.anggota { max-width: 100%; }
}

@media (max-width: 480px) {
    .bidang-wrapper { grid-template-columns: 1fr; }
    #profile-modal .modal-card {
        max-width: 100%;
        margin: 0 10px;
        border-radius: 16px;
    }
    #profile-modal .modal-header {
        padding: 20px 16px 16px;
    }
    #profile-modal .modal-body {
        padding: 16px;
    }
    #profile-modal .modal-avatar {
        width: 64px;
        height: 64px;
    }
    #profile-modal .modal-name {
        font-size: 1rem;
    }
}
</style>

<!-- MODAL POPUP -->
<div id="profile-modal">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-card">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div class="modal-avatar" id="modalAvatar">
                <div class="avatar-fallback blue">?</div>
            </div>
            <div class="modal-name" id="modalName">Nama</div>
            <div class="modal-badge" id="modalBadge">Jabatan</div>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="info-item">
                <span class="info-label">Informasi</span>
                <span class="info-value">Belum ada data</span>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    <h1 class="page-title">STRUKTUR ORGANISASI PENGURUS</h1>
    <p class="page-subtitle">PGNI Wilayah Provinsi Lampung | Periode 2026-2031</p>

    <div class="org-chart">
        <div class="top-section">
            <div class="group-box">
                <div class="group-title">PEMBINA</div>
                <div class="group-list" id="deckPembina"></div>
            </div>
            <div class="group-box">
                <div class="group-title">PENASEHAT</div>
                <div class="group-list scrollable" id="deckPenasehat"></div>
            </div>
            <div class="group-box">
                <div class="group-title">PENGAWAS</div>
                <div class="group-list" id="deckPengawas"></div>
            </div>
        </div>

        <div class="connector-v-large"></div>

        <div class="level-center">
            <div id="deckKetuaUmum"></div>
        </div>

        <div class="connector-v-large"></div>

        <div class="level" id="levelPimpinan">
            <div id="deckWakilKetua"></div>
            <div id="deckSekretaris"></div>
            <div id="deckBendahara"></div>
        </div>

        <div class="connector-v-large"></div>

        <div class="level-center">
            <div class="section-badge">KETUA BIDANG</div>
        </div>

        <div class="bidang-wrapper" id="deckBidang"></div>
    </div>
</div>

<script>
var dataPengurus = <?php echo json_encode($pengurus_array); ?>;

function getInitials(nama) {
    if (!nama) return '?';
    var words = nama.trim().split(' ');
    var initial = '';
    for (var i = 0; i < words.length; i++) {
        if (words[i].length > 0) {
            initial += words[i].charAt(0).toUpperCase();
        }
        if (initial.length >= 2) break;
    }
    return initial || '?';
}

function getColorClass(jabatan) {
    if (jabatan === 'Pembina') return 'orange';
    if (jabatan === 'Penasehat') return 'purple';
    if (jabatan === 'Pengawas') return 'pink';
    if (jabatan === 'Ketua Umum') return 'blue';
    if (jabatan.indexOf('Bidang') === 0) return 'tosca';
    return 'green';
}

function getCardClass(jabatan, isKetuaBidang) {
    if (jabatan === 'Pembina') return 'pembina';
    if (jabatan === 'Penasehat') return 'penasehat';
    if (jabatan === 'Pengawas') return 'pengawas';
    if (jabatan === 'Ketua Umum') return 'ketua primary';
    if (jabatan === 'Wakil Ketua') return 'pimpinan primary';
    if (jabatan === 'Sekretaris Umum') return 'pimpinan primary';
    if (jabatan === 'Wakil Sekretaris') return 'pimpinan';
    if (jabatan === 'Bendahara Umum') return 'pimpinan primary';
    if (jabatan === 'Wakil Bendahara') return 'pimpinan';
    if (jabatan.indexOf('Bidang') === 0 && isKetuaBidang) return 'departemen primary';
    if (jabatan === 'Anggota') return 'anggota';
    return 'anggota';
}

function getFallbackHtml(nama, jabatan) {
    var initial = getInitials(nama);
    var color = getColorClass(jabatan);
    return '<div class="avatar-fallback ' + color + '">' + initial + '</div>';
}

function buildCard(p, isAnggota) {
    var cardClass = getCardClass(p.jabatan, p.is_ketua_bidang);
    var fallback = getFallbackHtml(p.nama, p.jabatan);

    var cardDiv = document.createElement('div');
    cardDiv.className = 'card ' + cardClass;
    cardDiv.onclick = function() { openModal(p.id); };

    var avatarWrap = document.createElement('div');
    avatarWrap.className = 'avatar-wrapper';

    if (p.foto && p.foto.trim() !== '') {
        var img = document.createElement('img');
        img.src = p.foto;
        img.alt = p.nama;
        img.onerror = function() {
            avatarWrap.innerHTML = fallback;
        };
        avatarWrap.appendChild(img);
    } else {
        avatarWrap.innerHTML = fallback;
    }

    var jabatanDiv = document.createElement('div');
    jabatanDiv.className = 'jabatan';
    jabatanDiv.textContent = p.jabatan;

    var namaDiv = document.createElement('div');
    namaDiv.className = 'nama';
    namaDiv.textContent = p.nama;

    cardDiv.appendChild(avatarWrap);
    cardDiv.appendChild(jabatanDiv);
    cardDiv.appendChild(namaDiv);

    return cardDiv;
}

function renderChart() {
    var bidang = {};
    var anggotaByParent = {};

    // Kategorikan data
    for (var i = 0; i < dataPengurus.length; i++) {
        var p = dataPengurus[i];

        if (p.jabatan === 'Pembina') {
            document.getElementById('deckPembina').appendChild(buildCard(p));
        } else if (p.jabatan === 'Penasehat') {
            document.getElementById('deckPenasehat').appendChild(buildCard(p));
        } else if (p.jabatan === 'Pengawas') {
            document.getElementById('deckPengawas').appendChild(buildCard(p));
        } else if (p.jabatan === 'Ketua Umum') {
            var kDeck = document.getElementById('deckKetuaUmum');
            kDeck.innerHTML = '';
            kDeck.appendChild(buildCard(p));
        } else if (p.jabatan === 'Wakil Ketua') {
            document.getElementById('deckWakilKetua').appendChild(buildCard(p));
        } else if (p.jabatan === 'Sekretaris Umum' || p.jabatan === 'Wakil Sekretaris') {
            document.getElementById('deckSekretaris').appendChild(buildCard(p));
        } else if (p.jabatan === 'Bendahara Umum' || p.jabatan === 'Wakil Bendahara') {
            document.getElementById('deckBendahara').appendChild(buildCard(p));
        } else if (p.jabatan.indexOf('Bidang') === 0) {
            if (!bidang[p.jabatan]) bidang[p.jabatan] = [];
            bidang[p.jabatan].push(p);
        } else if (p.parent_id > 0) {
            if (!anggotaByParent[p.parent_id]) anggotaByParent[p.parent_id] = [];
            anggotaByParent[p.parent_id].push(p);
        }
    }

    // Render Bidang dengan Anggotanya
    var root = document.getElementById('deckBidang');
    root.innerHTML = '';
    var bidangKeys = Object.keys(bidang);

    if (bidangKeys.length > 0) {
        for (var j = 0; j < bidangKeys.length; j++) {
            var namaBidang = bidangKeys[j];
            var items = bidang[namaBidang];

            for (var k = 0; k < items.length; k++) {
                var p = items[k];
                var wrapper = document.createElement('div');
                wrapper.className = 'bidang-item';

                // Ketua Bidang
                var ketuaDiv = document.createElement('div');
                ketuaDiv.className = 'ketua-bidang';
                ketuaDiv.appendChild(buildCard(p));
                wrapper.appendChild(ketuaDiv);

                // Anggota di bawah bidang ini
                if (anggotaByParent[p.id] && anggotaByParent[p.id].length > 0) {
                    var anggotaList = document.createElement('div');
                    anggotaList.className = 'anggota-list';

                    for (var a = 0; a < anggotaByParent[p.id].length; a++) {
                        var anggotaCard = buildCard(anggotaByParent[p.id][a], true);
                        anggotaList.appendChild(anggotaCard);
                    }
                    wrapper.appendChild(anggotaList);
                }

                root.appendChild(wrapper);
            }
        }
    } else {
        root.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);">Belum ada data bidang</div>';
    }
}

// MODAL
function openModal(id) {
    var p = null;
    for (var i = 0; i < dataPengurus.length; i++) {
        if (dataPengurus[i].id === id) {
            p = dataPengurus[i];
            break;
        }
    }
    if (!p) return;

    var modal = document.getElementById('profile-modal');
    var avatarBox = document.getElementById('modalAvatar');
    var nameBox = document.getElementById('modalName');
    var badgeBox = document.getElementById('modalBadge');
    var bodyBox = document.getElementById('modalBody');

    avatarBox.innerHTML = '';
    if (p.foto && p.foto.trim() !== '') {
        var img = document.createElement('img');
        img.src = p.foto;
        img.alt = p.nama;
        img.onerror = function() {
            avatarBox.innerHTML = getFallbackHtml(p.nama, p.jabatan);
        };
        avatarBox.appendChild(img);
    } else {
        avatarBox.innerHTML = getFallbackHtml(p.nama, p.jabatan);
    }

    nameBox.textContent = p.nama;
    badgeBox.textContent = p.jabatan;

    var info = [
        { title: 'Jenis Kelamin', value: (p.jenis_kelamin === 'L') ? 'Laki-laki' : (p.jenis_kelamin === 'P') ? 'Perempuan' : null },
        { title: 'Tempat, Tgl Lahir', value: (p.tempat_lahir && p.tanggal_lahir) ? p.tempat_lahir + ', ' + p.tanggal_lahir : null },
        { title: 'No. Telepon', value: p.no_telp },
        { title: 'Email Resmi', value: p.email },
        { title: 'Alamat Tinggal', value: p.alamat }
    ];

    var html = '';
    for (var j = 0; j < info.length; j++) {
        if (info[j].value) {
            html += '<div class="info-item">' +
                    '<span class="info-label">' + info[j].title + '</span>' +
                    '<span class="info-value">' + info[j].value + '</span>' +
                    '</div>';
        }
    }

    if (!html) {
        html = '<div class="no-data">Informasi biodata belum diisi lengkap.</div>';
    }

    bodyBox.innerHTML = html;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    var modal = document.getElementById('profile-modal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderChart);
} else {
    renderChart();
}
</script>

<?php 
include $root_path . '/include/footer.php'; 
?>