<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Tentang -->
            <div class="footer-col">
                <h4>Tentang PGNI</h4>
                <p>Persatuan Guru Ngaji Indonesia (PGNI) adalah organisasi profesi yang bergerak di bidang pendidikan Al-Qur'an, berkomitmen mencetak generasi Qur'ani yang berakhlak mulia di Provinsi Lampung.</p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/profile.php?id=61592155853021" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/lampungpgni/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.youtube.com/channel/UC6S6flu9de0gsdPhv8xJUlg" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                        <i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <!-- Tautan Cepat -->
            <div class="footer-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>"><i class="fas fa-chevron-left"></i> Beranda</a></li>
                    <li><a href="<?php echo BASE_URL; ?>berita.php"><i class="fas fa-chevron-left"></i> Berita</a></li>
                    <li><a href="<?php echo BASE_URL; ?>tentang.php"><i class="fas fa-chevron-left"></i> Tentang Kami</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pengurus.php"><i class="fas fa-chevron-left"></i> Pengurus</a></li>
                    <li><a href="<?php echo BASE_URL; ?>kontak.php"><i class="fas fa-chevron-left"></i> Kontak</a></li>
                </ul>
            </div>
            
            <!-- Kontak -->
            <div class="footer-col">
                <h4>Kontak</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> Gg. Pondok No.16, Kel. Durian Payung, Kec. Tanjung Karang Pusat, Bandar Lampung - 35116</li>
                    <li><i class="fas fa-phone"></i> +62 812-7343-7568</li>
                    <li><i class="fas fa-envelope"></i> info@pgni.net</li>
                    <li><i class="fas fa-clock"></i> Senin - Jumat: 08:00 - 16:00 WIB</li>
                </ul>
            </div>
            
            <!-- Newsletter -->
            <div class="footer-col">
                <h4>Newsletter</h4>
                <p>Dapatkan informasi terbaru dari PGNI Lampung</p>
                <form class="newsletter-form" action="#" method="POST">
                    <input type="email" placeholder="Email Anda" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> PGNI Lampung. All Rights Reserved.</p>
            <p>Developed with <i class="fas fa-heart" style="color: #e74c3c;"></i> by PGNI Lampung Team</p>
        </div>
    </div>
</footer>



<!-- Scroll to Top Button -->
<button id="scrollTop" class="scroll-top" aria-label="Scroll to top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- ============================================ -->
<!-- CHAT WIDGET - TIDAK MENGGANGGU MENU -->
<!-- ============================================ -->
<style>
#chatWidget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 999999;
}
#chatWidget .chat-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #075e54;
    color: #fff;
    border: none;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    transition: transform 0.3s;
}
#chatWidget .chat-btn:hover {
    transform: scale(1.1);
}
#chatWidget .chat-box {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 500px;
    max-height: calc(100vh - 130px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
}
#chatWidget .chat-box.open {
    display: flex;
}
#chatWidget .chat-header {
    background: #075e54;
    padding: 12px 16px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
#chatWidget .chat-header span {
    font-weight: bold;
    font-size: 16px;
}
#chatWidget .chat-header .status {
    font-size: 10px;
    color: #9de1c7;
}
#chatWidget .chat-header .close-btn {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 18px;
}
#chatWidget .chat-body {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #e5ddd5;
}
#chatWidget .chat-body .msg {
    margin-bottom: 10px;
    padding: 8px 14px;
    border-radius: 12px;
    max-width: 85%;
    font-size: 14px;
    line-height: 1.5;
    animation: slideIn 0.3s ease;
}
#chatWidget .chat-body .msg.bot {
    background: #fff;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
#chatWidget .chat-body .msg.user {
    background: #dcf8c6;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
#chatWidget .chat-footer {
    display: flex;
    flex-direction: column;
    padding: 10px 12px;
    background: #fff;
    border-top: 1px solid #e0e0e0;
    flex-shrink: 0;
}
#chatWidget .quick-replies {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
#chatWidget .quick-replies button {
    padding: 3px 10px;
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 14px;
    font-size: 11px;
    color: #2e7d32;
    cursor: pointer;
    font-family: inherit;
}
#chatWidget .quick-replies button:hover {
    background: #c8e6c9;
}
#chatWidget .input-row {
    display: flex;
    gap: 8px;
}
#chatWidget .input-row input {
    flex: 1;
    padding: 8px 14px;
    border: 1px solid #ddd;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
    font-family: inherit;
}
#chatWidget .input-row input:focus {
    border-color: #075e54;
}
#chatWidget .input-row button {
    padding: 8px 16px;
    background: #075e54;
    color: #fff;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
}
#chatWidget .input-row button:hover {
    background: #054740;
}
#chatWidget .input-row button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}
.typing-indicator {
    display: inline-flex;
    gap: 4px;
    padding: 4px 0;
}
.typing-indicator span {
    width: 7px;
    height: 7px;
    background: #999;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
@media (max-width: 480px) {
    #chatWidget .chat-box {
        position: fixed;
        bottom: 0;
        right: 0;
        width: 100%;
        max-width: 100%;
        height: 100%;
        max-height: 100%;
        border-radius: 0;
    }
}
</style>

<div id="chatWidget">
    <button class="chat-btn" id="chatToggleBtn">💬</button>
    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            <span>🤖 PGNI Bot <span class="status">● Online</span></span>
            <button class="close-btn" id="chatCloseBtn">✕</button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="msg bot">Assalamu'alaikum! 👋<br>Saya asisten PGNI Lampung.</div>
        </div>
        <div class="chat-footer">
            <div class="quick-replies" id="quickReplies"></div>
            <div class="input-row">
                <input type="text" id="chatInput" placeholder="Ketik pesan..." autocomplete="off">
                <button id="chatSendBtn">Kirim</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ============================================
    // KONFIGURASI
    // ============================================
    var BASE_URL = '<?php echo BASE_URL; ?>';
    var API_URL = BASE_URL + '/chatbot/chat-handler.php';
    var USER_ID = 'guest_' + Date.now();
    var USER_NAME = 'Pengunjung';

    // ============================================
    // ELEMEN
    // ============================================
    var btn = document.getElementById('chatToggleBtn');
    var box = document.getElementById('chatBox');
    var closeBtn = document.getElementById('chatCloseBtn');
    var body = document.getElementById('chatBody');
    var input = document.getElementById('chatInput');
    var sendBtn = document.getElementById('chatSendBtn');
    var quickReplies = document.getElementById('quickReplies');

    var isOpen = false;
    var isLoading = false;
    var state = 'menu';
    var data = {};

    // ============================================
    // FUNGSI
    // ============================================
    function addMessage(type, text) {
        var msg = document.createElement('div');
        msg.className = 'msg ' + type;
        msg.innerHTML = text.replace(/\n/g, '<br>').replace(/\*(.*?)\*/g, '<strong style="color:#075e54;">$1</strong>');
        body.appendChild(msg);
        body.scrollTop = body.scrollHeight;
    }

    function showTyping() {
        var typing = document.createElement('div');
        typing.id = 'typingIndicator';
        typing.className = 'msg bot';
        typing.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        body.appendChild(typing);
        body.scrollTop = body.scrollHeight;
    }

    function hideTyping() {
        var el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    function showQuickReplies(replies) {
        quickReplies.innerHTML = '';
        if (!replies || replies.length === 0) return;
        var unique = [];
        for (var i = 0; i < replies.length; i++) {
            if (unique.indexOf(replies[i]) === -1) unique.push(replies[i]);
        }
        for (var j = 0; j < unique.length; j++) {
            (function(reply) {
                var btn = document.createElement('button');
                btn.textContent = reply;
                btn.onclick = function() {
                    input.value = reply;
                    sendMessage();
                };
                quickReplies.appendChild(btn);
            })(unique[j]);
        }
    }

    function sendMessage() {
        var message = input.value.trim();
        if (!message || isLoading) return;

        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;
        isLoading = true;

        addMessage('user', message);
        showTyping();

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: USER_ID,
                user_name: USER_NAME,
                message: message,
                session: state,
                data: data
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            hideTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            isLoading = false;

            if (res.response) {
                addMessage('bot', res.response);
            }
            if (res.session) {
                state = res.session.state || 'menu';
                data = res.session.data || {};
            }
            if (res.quick_replies) {
                showQuickReplies(res.quick_replies);
            }
            input.focus();
        })
        .catch(function(err) {
            hideTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            isLoading = false;
            addMessage('bot', '❌ Koneksi error. Silakan refresh.');
            showQuickReplies(['menu']);
            input.focus();
        });
    }

    function toggleChat() {
        isOpen = !isOpen;
        box.classList.toggle('open', isOpen);
        if (isOpen) {
            input.focus();
        }
    }

    function closeChat() {
        isOpen = false;
        box.classList.remove('open');
    }

    // ============================================
    // EVENT
    // ============================================
    btn.onclick = toggleChat;
    closeBtn.onclick = closeChat;
    sendBtn.onclick = sendMessage;
    input.onkeydown = function(e) {
        if (e.key === 'Enter') sendMessage();
    };

    // ============================================
    // WELCOME
    // ============================================
    setTimeout(function() {
        addMessage('bot', '📋 *Menu Utama PGNI Lampung Bot*\n\n1️⃣ Registrasi Member\n2️⃣ Cek Status Pendaftaran\n3️⃣ Perbaharui Data Member\n4️⃣ Login Member Area\n5️⃣ Berita & Informasi\n6️⃣ Donasi\n7️⃣ Lokasi Kantor\n8️⃣ Tentang PGNI\n\n📌 Ketik nomor pilihan Anda.');
        showQuickReplies(['1', '2', '3', '4', '5', '6', '7', '8', 'menu']);
    }, 300);

    // Auto open first visit
    if (!localStorage.getItem('pgnichat_visited')) {
        setTimeout(function() {
            toggleChat();
            localStorage.setItem('pgnichat_visited', 'true');
        }, 2000);
    }

    console.log('PGNI Chat Widget loaded!');
})();
</script>



<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>js/script.js"></script>
<script src="https://pgni.net/pgnil/chatbot/widget-embed.js?v=1.0.2"></script>
</body>
</html>