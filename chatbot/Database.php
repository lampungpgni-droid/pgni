<?php
// chatbot/Database.php

class ChatbotDatabase {
    public $conn; // Mengubah visibilitas dari private ke public
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Cek apakah NIK terdaftar
     */
    public function isNikRegistered($nik) {
        $nik = mysqli_real_escape_string($this->conn, $nik);
        $query = "SELECT id, nama, status_verifikasi FROM guru_ngaji WHERE nik = '$nik'";
        $result = mysqli_query($this->conn, $query);
        return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : false;
    }
    
    /**
     * Cek apakah no telp terdaftar (Mendukung pencarian variasi 62x dan 08x)
     */
    public function isPhoneRegistered($phone) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $altPhone = $cleanPhone;
        
        if (substr($cleanPhone, 0, 2) === '62') {
            $altPhone = '0' . substr($cleanPhone, 2);
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $altPhone = '62' . substr($cleanPhone, 1);
        }
        
        $cleanPhone = mysqli_real_escape_string($this->conn, $cleanPhone);
        $altPhone = mysqli_real_escape_string($this->conn, $altPhone);
        
        $query = "SELECT id, nama, nik, status_verifikasi, tempat_mengajar, no_telp 
                  FROM guru_ngaji 
                  WHERE no_telp = '$cleanPhone' OR no_telp = '$altPhone'";
        $result = mysqli_query($this->conn, $query);
        return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : false;
    }
    
    /**
     * Get data guru by NIK
     */
    public function getGuruByNik($nik) {
        $nik = mysqli_real_escape_string($this->conn, $nik);
        $query = "SELECT * FROM guru_ngaji WHERE nik = '$nik'";
        $result = mysqli_query($this->conn, $query);
        return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    }
    
    /**
     * Get status verifikasi
     */
    public function getStatusVerifikasi($nik) {
        $data = $this->getGuruByNik($nik);
        if (!$data) return null;
        
        $status_map = [
            'pending'   => '⏳ Menunggu Verifikasi',
            'disetujui' => '✅ Disetujui',
            'ditolak'   => '❌ Ditolak'
        ];
        
        return [
            'status'         => $data['status_verifikasi'],
            'status_text'    => $status_map[$data['status_verifikasi']] ?? $data['status_verifikasi'],
            'nama'           => $data['nama'],
            'nik'            => $data['nik'],
            'tempat_mengajar'=> $data['tempat_mengajar'],
            'alasan_ditolak' => $data['alasan_ditolak'] ?? null
        ];
    }
    
    /**
     * Get berita terbaru
     */
    public function getBeritaTerbaru($limit = 5) {
        $limit = (int)$limit;
        $query = "SELECT id, judul, isi, created_at FROM berita 
                  WHERE status = 1 
                  ORDER BY created_at DESC LIMIT $limit";
        $result = mysqli_query($this->conn, $query);
        $berita = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $berita[] = $row;
            }
        }
        return $berita;
    }
    
    /**
     * Get profil yayasan
     */
    public function getYayasan() {
        $query = "SELECT * FROM yayasan LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    }
    
    /**
     * Get pengurus
     */
    public function getPengurus($limit = 10) {
        $limit = (int)$limit;
        $query = "SELECT nama, jabatan FROM pengurus 
                  WHERE status = 'aktif' 
                  ORDER BY urutan ASC LIMIT $limit";
        $result = mysqli_query($this->conn, $query);
        $pengurus = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $pengurus[] = $row;
            }
        }
        return $pengurus;
    }
    
    /**
     * Get lokasi kantor
     */
    public function getKantorInfo() {
        return [
            'nama'   => 'PGNI Lampung',
            'alamat' => 'Gg.Pondok No.16 Kel. Durian Payung Kec. Tanjung Karang Pusat, Bandar Lampung - 35116',
            'telp'   => '081273437568',
            'email'  => 'info@pgni.net',
            'maps'   => 'https://maps.google.com/?q=Durian+Payung+Tanjung+Karang+Pusat+Bandar+Lampung'
        ];
    }
    
    /**
     * Update data guru
     */
    public function updateGuru($nik, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $value = mysqli_real_escape_string($this->conn, $value);
            $set[] = "$key = '$value'";
        }
        $set[] = "updated_at = NOW()";
        $set[] = "status_verifikasi = 'pending'";
        
        $nik = mysqli_real_escape_string($this->conn, $nik);
        $query = "UPDATE guru_ngaji SET " . implode(', ', $set) . " WHERE nik = '$nik'";
        return mysqli_query($this->conn, $query);
    }
    
    /**
     * Reset password member
     */
    public function resetPassword($nik, $newPassword) {
        $password = password_hash($newPassword, PASSWORD_DEFAULT);
        $nik = mysqli_real_escape_string($this->conn, $nik);
        $query = "UPDATE guru_ngaji SET password = '$password' WHERE nik = '$nik'";
        return mysqli_query($this->conn, $query);
    }
}