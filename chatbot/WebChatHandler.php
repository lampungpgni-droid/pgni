<?php
// chatbot/SimpleChatHandler.php
// Handler sederhana untuk chat

require_once __DIR__ . '/Database.php';

class SimpleChatHandler {
    private $db;
    private $sessionFile;
    private $sessions = [];
    
    public function __construct($connection) {
        $this->db = new ChatbotDatabase($connection);
        $this->sessionFile = __DIR__ . '/web_sessions.json';
        $this->loadSessions();
    }
    
    public function processMessage($userId, $message, $userName, $currentState, $tempData) {
        $message = trim($message);
        $lowerMsg = strtolower($message);
        
        // Get session
        $session = $this->getSession($userId);
        $state = $session['state'] ?? 'menu';
        $data = $session['data'] ?? [];
        
        // Handle reset
        if (in_array($lowerMsg, ['menu', '0', 'batal', 'cancel', 'reset'])) {
            $this->clearSession($userId);
            if (in_array($lowerMsg, ['batal', 'cancel'])) {
                return $this->response($userId, "✅ Proses dibatalkan.\n\n" . $this->menuText(), 'menu', []);
            }
            return $this->response($userId, $this->menuText(), 'menu', []);
        }
        
        // Routing
        switch ($state) {
            case 'menu':
                return $this->handleMenu($userId, $message, $userName);
            case 'registrasi_1':
                return $this->handleRegistrasiNama($userId, $message);
            case 'registrasi_2':
                return $this->handleRegistrasiNik($userId, $message);
            case 'registrasi_3':
                return $this->handleRegistrasiTelp($userId, $message);
            case 'registrasi_4':
                return $this->handleRegistrasiTempat($userId, $message);
            case 'registrasi_profesi':
                return $this->handleRegistrasiProfesi($userId, $message);
            case 'registrasi_5':
                return $this->handleRegistrasiDetail($userId, $message);
            case 'registrasi_6':
                return $this->handleRegistrasiFinal($userId, $message);
            case 'cek_status':
                return $this->handleCekStatus($userId, $message);
            case 'login_nik':
                return $this->handleLoginNik($userId, $message);
            case 'login_password':
                return $this->handleLoginPassword($userId, $message);
            default:
                $this->clearSession($userId);
                return $this->response($userId, $this->menuText(), 'menu', []);
        }
    }
    
    private function handleMenu($userId, $message, $userName) {
        $lowerMsg = strtolower($message);
        $userData = $this->db->isPhoneRegistered($userId);
        
        switch ($lowerMsg) {
            case '1':
            case 'registrasi':
                return $this->startRegistrasi($userId);
            case '2':
            case 'cek status':
            case 'cek':
                return $this->startCekStatus($userId);
            case '3':
            case 'update':
            case 'perbaharui':
                return $this->startUpdate($userId);
            case '4':
            case 'login':
                return $this->startLogin($userId);
            case '5':
            case 'berita':
            case 'info':
                return $this->sendBerita($userId);
            case '6':
            case 'donasi':
                return $this->sendDonasi($userId);
            case '7':
            case 'lokasi':
            case 'kantor':
                return $this->sendLokasi($userId);
            case '8':
            case 'tentang':
                return $this->sendTentang($userId);
            default:
                if ($userData) {
                    $response = "👋 Halo *{$userData['nama']}*\n\n";
                    $response .= "Anda terdaftar sebagai member PGNI Lampung.\n";
                    $response .= "Status: " . $this->statusText($userData['status_verifikasi']) . "\n\n";
                    $response .= $this->menuText();
                    return $this->response($userId, $response, 'menu', []);
                }
                $response = "Halo! 👋\n\nSaya adalah asisten PGNI Lampung. Ada yang bisa saya bantu?\n\n" . $this->menuText();
                return $this->response($userId, $response, 'menu', []);
        }
    }
    
    private function startRegistrasi($userId) {
        $userData = $this->db->isPhoneRegistered($userId);
        if ($userData) {
            return $this->response($userId, 
                "⚠️ Anda sudah terdaftar dengan NIK: *{$userData['nik']}*\n" .
                "Status: " . $this->statusText($userData['status_verifikasi']) . "\n\n" .
                "Jika ingin memperbaharui data, ketik *3*",
                'menu', []
            );
        }
        
        $this->setSession($userId, ['state' => 'registrasi_1', 'data' => []]);
        return $this->response($userId, 
            "📝 *Registrasi Member PGNI Lampung*\n\n" .
            "1️⃣ *Nama Lengkap*\n" .
            "Silakan ketik nama lengkap Anda sesuai KTP.\n\n" .
            "📌 Ketik *batal* untuk membatalkan",
            'registrasi_1', []
        );
    }
    
    private function handleRegistrasiNama($userId, $message) {
        if (strlen($message) < 3) {
            return $this->response($userId, 
                "❌ Nama terlalu pendek. Minimal 3 huruf.\n\n📌 Ketik *batal*",
                'registrasi_1', []
            );
        }
        
        $this->updateSession($userId, [
            'state' => 'registrasi_2',
            'data' => ['nama' => $message]
        ]);
        
        return $this->response($userId, 
            "✅ Nama: *{$message}*\n\n" .
            "2️⃣ *NIK (16 digit)*\n" .
            "Silakan ketik NIK Anda.\n\n📌 Ketik *batal*",
            'registrasi_2', []
        );
    }
    
    private function handleRegistrasiNik($userId, $message) {
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            return $this->response($userId, 
                "❌ NIK harus 16 digit angka.\n\n📌 Ketik *batal*",
                'registrasi_2', []
            );
        }
        
        if ($this->db->isNikRegistered($message)) {
            return $this->response($userId, 
                "⚠️ NIK *{$message}* sudah terdaftar.\n\n📌 Ketik *batal*",
                'registrasi_2', []
            );
        }
        
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        $data['nik'] = $message;
        
        $this->updateSession($userId, [
            'state' => 'registrasi_3',
            'data' => $data
        ]);
        
        return $this->response($userId, 
            "✅ NIK: *{$message}*\n\n" .
            "3️⃣ *Nomor Telepon*\n" .
            "Silakan ketik nomor telepon aktif.\n\n📌 Ketik *batal*",
            'registrasi_3', []
        );
    }
    
    private function handleRegistrasiTelp($userId, $message) {
        $phone = preg_replace('/[^0-9]/', '', $message);
        
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return $this->response($userId, 
                "❌ Nomor telepon tidak valid (10-15 digit).\n\n📌 Ketik *batal*",
                'registrasi_3', []
            );
        }
        
        if ($this->db->isPhoneRegistered($phone)) {
            return $this->response($userId, 
                "⚠️ Nomor *{$phone}* sudah terdaftar.\n\n📌 Ketik *batal*",
                'registrasi_3', []
            );
        }
        
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        $data['no_telp'] = $phone;
        
        $this->updateSession($userId, [
            'state' => 'registrasi_4',
            'data' => $data
        ]);
        
        return $this->response($userId, 
            "✅ Telepon: *{$phone}*\n\n" .
            "4️⃣ *Tempat Mengajar*\n" .
            "Pilih:\n1️⃣ Rumah Pribadi\n2️⃣ TPA\n3️⃣ MDTA\n4️⃣ Ponpes\n5️⃣ Masjid\n6️⃣ Yayasan\n7️⃣ Lainnya\n\n📌 Ketik *batal*",
            'registrasi_4', []
        );
    }
    
    private function handleRegistrasiTempat($userId, $message) {
        $tempatMap = [
            '1' => 'Rumah Pribadi',
            '2' => 'TPA (Taman Pendidikan Al-Qur\'an)',
            '3' => 'MDTA (Madrasah Diniyah Takmiliyah)',
            '4' => 'Pondok Pesantren',
            '5' => 'Masjid/Musholla',
            '6' => 'Yayasan',
            '7' => 'Lainnya'
        ];
        
        if (!isset($tempatMap[$message])) {
            return $this->response($userId, 
                "❌ Pilihan 1-7.\n\n📌 Ketik *batal*",
                'registrasi_4', []
            );
        }
        
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        $data['tempat_mengajar'] = $tempatMap[$message];
        
        $this->updateSession($userId, [
            'state' => 'registrasi_profesi',
            'data' => $data
        ]);
        
        return $this->response($userId, 
            "✅ Tempat: *{$tempatMap[$message]}*\n\n" .
            "5️⃣ *Jenis Profesi*\n" .
            "Pilih:\n1️⃣ Guru Ngaji\n2️⃣ Marbot\n3️⃣ Penjaga Makam\n\n📌 Ketik *batal*",
            'registrasi_profesi', []
        );
    }
    
    private function handleRegistrasiProfesi($userId, $message) {
        $profesiMap = [
            '1' => 'Guru Ngaji',
            '2' => 'Marbot',
            '3' => 'Penjaga Makam'
        ];
        
        if (!isset($profesiMap[$message])) {
            return $this->response($userId, 
                "❌ Pilihan 1-3.\n\n📌 Ketik *batal*",
                'registrasi_profesi', []
            );
        }
        
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        $data['jenis_profesi'] = $profesiMap[$message];
        
        $this->updateSession($userId, [
            'state' => 'registrasi_5',
            'data' => $data
        ]);
        
        return $this->response($userId, 
            "✅ Profesi: *{$profesiMap[$message]}*\n\n" .
            "6️⃣ *Detail Tempat* (opsional)\n" .
            "Ketik nama spesifik tempat, atau *skip* untuk lewati.",
            'registrasi_5', []
        );
    }
    
    private function handleRegistrasiDetail($userId, $message) {
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        $data['tempat_detail'] = (strtolower(trim($message)) !== 'skip') ? $message : '';
        
        $this->updateSession($userId, [
            'state' => 'registrasi_6',
            'data' => $data
        ]);
        
        $response = "📋 *Konfirmasi Data*\n\n";
        $response .= "Nama: *{$data['nama']}*\n";
        $response .= "NIK: *{$data['nik']}*\n";
        $response .= "Telepon: *{$data['no_telp']}*\n";
        $response .= "Tempat: *{$data['tempat_mengajar']}*\n";
        $response .= "Profesi: *{$data['jenis_profesi']}*\n";
        if ($data['tempat_detail']) {
            $response .= "Detail: *{$data['tempat_detail']}*\n";
        }
        $response .= "\n⚠️ *Pastikan data benar!*\n\n";
        $response .= "Ketik *YA* untuk simpan, *TIDAK* untuk batal.";
        
        return $this->response($userId, $response, 'registrasi_6', []);
    }
    
    private function handleRegistrasiFinal($userId, $message) {
        $lowerMsg = strtolower(trim($message));
        
        if ($lowerMsg !== 'ya' && $lowerMsg !== 'yes') {
            $this->clearSession($userId);
            return $this->response($userId, "✅ Registrasi dibatalkan.\n\n" . $this->menuText(), 'menu', []);
        }
        
        $session = $this->getSession($userId);
        $data = $session['data'] ?? [];
        
        if (empty($data['nik']) || empty($data['nama']) || empty($data['no_telp'])) {
            $this->clearSession($userId);
            return $this->response($userId, "❌ Data tidak lengkap. Mulai ulang dengan *1*", 'menu', []);
        }
        
        $nik = mysqli_real_escape_string($this->db->conn, $data['nik']);
        $nama = mysqli_real_escape_string($this->db->conn, $data['nama']);
        $no_telp = mysqli_real_escape_string($this->db->conn, $data['no_telp']);
        $tempat_mengajar = mysqli_real_escape_string($this->db->conn, $data['tempat_mengajar']);
        $tempat_detail = mysqli_real_escape_string($this->db->conn, $data['tempat_detail'] ?? '');
        $jenis_profesi = mysqli_real_escape_string($this->db->conn, $data['jenis_profesi'] ?? 'Guru Ngaji');
        $default_pass = password_hash('pgnilampung', PASSWORD_DEFAULT);
        
        $query = "INSERT INTO guru_ngaji (
            nik, nama, no_telp, tempat_mengajar, 
            tempat_mengajar_detail, jenis_profesi,
            password, status_verifikasi, status, created_at
        ) VALUES (
            '$nik', '$nama', '$no_telp', '$tempat_mengajar',
            '$tempat_detail', '$jenis_profesi',
            '$default_pass', 'pending', 'aktif', NOW()
        )";
        
        if (mysqli_query($this->db->conn, $query)) {
            $this->clearSession($userId);
            
            $response = "🎉 *Registrasi Berhasil!*\n\n";
            $response .= "Nama: *{$data['nama']}*\n";
            $response .= "NIK: *{$data['nik']}*\n\n";
            $response .= "Tim admin akan memverifikasi dalam 3x24 jam.\n";
            $response .= "🔍 Cek status: ketik *2*\n\n";
            $response .= "📌 Ketik *menu* untuk kembali.";
            
            return $this->response($userId, $response, 'menu', []);
        } else {
            $error = mysqli_error($this->db->conn);
            return $this->response($userId, "❌ Gagal menyimpan: $error\n\n📌 Ketik *menu*", 'menu', []);
        }
    }
    
    private function startCekStatus($userId) {
        $this->setSession($userId, ['state' => 'cek_status']);
        return $this->response($userId, 
            "🔍 *Cek Status*\n\nMasukkan *NIK* (16 digit):\n\n📌 Ketik *batal*",
            'cek_status', []
        );
    }
    
    private function handleCekStatus($userId, $message) {
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            return $this->response($userId, 
                "❌ NIK 16 digit angka.\n\n📌 Ketik *batal*",
                'cek_status', []
            );
        }
        
        $status = $this->db->getStatusVerifikasi($message);
        $this->clearSession($userId);
        
        if (!$status) {
            return $this->response($userId, 
                "❌ NIK *{$message}* tidak ditemukan.\n\n📌 Ketik *menu*",
                'menu', []
            );
        }
        
        $response = "🔍 *Hasil Cek Status*\n\n";
        $response .= "Nama: *{$status['nama']}*\n";
        $response .= "NIK: *{$status['nik']}*\n";
        $response .= "Status: {$status['status_text']}\n";
        if ($status['tempat_mengajar']) {
            $response .= "Tempat: *{$status['tempat_mengajar']}*\n";
        }
        
        if ($status['status'] === 'disetujui') {
            $response .= "\n✅ *Terverifikasi!*\n";
            $response .= "🔑 NIK: {$status['nik']}\n";
            $response .= "🔑 Password: pgnilampung\n";
        } elseif ($status['status'] === 'pending') {
            $response .= "\n⏳ *Menunggu Verifikasi*";
        } else {
            $response .= "\n❌ *Ditolak:* " . ($status['alasan_ditolak'] ?? 'Data kurang lengkap');
        }
        
        $response .= "\n\n📌 Ketik *menu* untuk kembali.";
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function startUpdate($userId) {
        $userData = $this->db->isPhoneRegistered($userId);
        if (!$userData) {
            return $this->response($userId, 
                "⚠️ Anda belum terdaftar. Ketik *1* untuk registrasi.",
                'menu', []
            );
        }
        
        $baseUrl = "https://pgni.net/pgnil/cek_status.php";
        $nik = urlencode($userData['nik']);
        $noTelp = urlencode($userData['no_telp'] ?? '');
        $link = $baseUrl . "?nik={$nik}&no_telp={$noTelp}";
        
        $response = "📝 *Perbaharui Data*\n\n";
        $response .= "Silakan perbarui data melalui link:\n";
        $response .= "🔗 {$link}\n\n";
        $response .= "📌 Ketik *menu* untuk kembali.";
        
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function startLogin($userId) {
        $this->setSession($userId, ['state' => 'login_nik']);
        return $this->response($userId, 
            "🔐 *Login*\n\nMasukkan NIK (16 digit):\n\n📌 Ketik *batal*",
            'login_nik', []
        );
    }
    
    private function handleLoginNik($userId, $message) {
        if (!preg_match('/^[0-9]{16}$/', $message)) {
            return $this->response($userId, 
                "❌ NIK 16 digit.\n\n📌 Ketik *batal*",
                'login_nik', []
            );
        }
        
        $userData = $this->db->getGuruByNik($message);
        if (!$userData) {
            return $this->response($userId, 
                "❌ NIK tidak ditemukan.\n\n📌 Ketik *batal*",
                'login_nik', []
            );
        }
        
        $this->updateSession($userId, [
            'state' => 'login_password',
            'data' => ['user' => $userData]
        ]);
        
        return $this->response($userId, 
            "🔑 Masukkan password:\n\n📌 Ketik *batal*",
            'login_password', []
        );
    }
    
    private function handleLoginPassword($userId, $message) {
        $session = $this->getSession($userId);
        $userData = $session['data']['user'] ?? null;
        
        if (!$userData) {
            $this->clearSession($userId);
            return $this->response($userId, "❌ Sesi berakhir. Ulangi login.", 'menu', []);
        }
        
        if (password_verify($message, $userData['password'])) {
            $this->clearSession($userId);
            $response = "🔐 *Login Berhasil!*\n\n";
            $response .= "Selamat datang, *{$userData['nama']}* 🎉\n\n";
            $response .= "📊 Dashboard: https://www.pgni.net/pgnil/member/dashboard.php\n\n";
            $response .= "📌 Ketik *menu* untuk kembali.";
            
            return $this->response($userId, $response, 'menu', []);
        }
        
        return $this->response($userId, 
            "❌ Password salah.\n\n📌 Ketik *batal*",
            'login_password', []
        );
    }
    
    private function sendBerita($userId) {
        $beritaList = $this->db->getBeritaTerbaru(5);
        if (empty($beritaList)) {
            return $this->response($userId, "📰 Belum ada berita.", 'menu', []);
        }
        
        $response = "📰 *Berita Terbaru*\n\n";
        foreach ($beritaList as $idx => $b) {
            $num = $idx + 1;
            $response .= "{$num}. *{$b['judul']}*\n";
            $response .= "   📅 " . date('d/m/Y', strtotime($b['created_at'])) . "\n\n";
        }
        $response .= "🔗 https://www.pgni.net/pgnil/berita.php\n\n";
        $response .= "📌 Ketik *menu*";
        
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function sendDonasi($userId) {
        $response = "🤲 *Donasi PGNI Lampung*\n\n";
        $response .= "Salurkan donasi Anda untuk mendukung program PGNI.\n\n";
        $response .= "💳 *Rekening BRI:* 8905-3656-96\n";
        $response .= "A/N: PGNI Lampung\n\n";
        $response .= "🔗 Donasi Online: https://pgni.net/pgnil/donasi.php\n\n";
        $response .= "📌 Ketik *menu*";
        
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function sendLokasi($userId) {
        $response = "📍 *Lokasi Kantor*\n\n";
        $response .= "Gg.Pondok No.16 Kel. Durian Payung\n";
        $response .= "Kec. Tanjung Karang Pusat, Bandar Lampung - 35116\n\n";
        $response .= "🗺️ https://maps.google.com/?q=Durian+Payung+Bandar+Lampung\n\n";
        $response .= "📌 Ketik *menu*";
        
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function sendTentang($userId) {
        $response = "🏛️ *PGNI Lampung*\n\n";
        $response .= "Persatuan Guru Ngaji Indonesia Provinsi Lampung\n";
        $response .= "Organisasi profesi guru ngaji dan pengajar Al-Qur'an.\n\n";
        $response .= "🌐 https://www.pgni.net/pgnil/tentang.php\n\n";
        $response .= "📌 Ketik *menu*";
        
        return $this->response($userId, $response, 'menu', []);
    }
    
    private function menuText() {
        return "📋 *Menu Utama*\n\n" .
               "1️⃣ Registrasi\n" .
               "2️⃣ Cek Status\n" .
               "3️⃣ Update Data\n" .
               "4️⃣ Login\n" .
               "5️⃣ Berita\n" .
               "6️⃣ Donasi\n" .
               "7️⃣ Lokasi\n" .
               "8️⃣ Tentang\n\n" .
               "📌 Ketik nomor pilihan.";
    }
    
    private function statusText($status) {
        $map = [
            'pending' => '⏳ Menunggu Verifikasi',
            'disetujui' => '✅ Disetujui',
            'ditolak' => '❌ Ditolak'
        ];
        return $map[$status] ?? $status;
    }
    
    private function response($userId, $text, $state, $data) {
        $this->setSession($userId, ['state' => $state, 'data' => $data]);
        
        $quickReplies = ['menu', 'batal'];
        if ($state === 'menu') {
            $quickReplies = ['1', '2', '3', '4', '5', '6', '7', '8', 'menu'];
        } elseif ($state === 'registrasi_4') {
            $quickReplies = ['1', '2', '3', '4', '5', '6', '7'];
        } elseif ($state === 'registrasi_profesi') {
            $quickReplies = ['1', '2', '3'];
        } elseif ($state === 'registrasi_5') {
            $quickReplies = ['skip'];
        } elseif ($state === 'registrasi_6') {
            $quickReplies = ['YA', 'TIDAK'];
        }
        
        return [
            'status' => true,
            'response' => $text,
            'session' => ['state' => $state, 'data' => $data],
            'quick_replies' => $quickReplies
        ];
    }
    
    // ===== Session Management =====
    private function getSession($userId) {
        $this->loadSessions();
        return $this->sessions[$userId] ?? ['state' => 'menu', 'data' => []];
    }
    
    private function setSession($userId, $data) {
        $this->loadSessions();
        $this->sessions[$userId] = $data;
        $this->saveSessions();
    }
    
    private function updateSession($userId, $data) {
        $session = $this->getSession($userId);
        foreach ($data as $k => $v) {
            $session[$k] = $v;
        }
        $this->setSession($userId, $session);
    }
    
    private function clearSession($userId) {
        $this->loadSessions();
        unset($this->sessions[$userId]);
        $this->saveSessions();
    }
    
    private function loadSessions() {
        if (file_exists($this->sessionFile)) {
            $content = @file_get_contents($this->sessionFile);
            $this->sessions = @json_decode($content, true) ?: [];
        } else {
            $this->sessions = [];
        }
    }
    
    private function saveSessions() {
        @file_put_contents($this->sessionFile, json_encode($this->sessions, JSON_PRETTY_PRINT));
    }
}