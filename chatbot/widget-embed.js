// chatbot/widget-embed.js - FIXED & FULL WORKING VERSION
(function() {
    'use strict';

    if (document.getElementById('pgnichat-widget-full')) return;

    const BASE_URL = window.PGNI_BASE_URL || 'https://pgni.net/pgnil';
    const API_URL = BASE_URL + '/chatbot/chat-handler.php';
    const USER_ID = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
    const USER_NAME = 'Pengunjung';

    let state = {
        session: 'menu',
        data: {},
        messages: [],
        isLoading: false,
        isOpen: false
    };

    // Create widget container
    const widget = document.createElement('div');
    widget.id = 'pgnichat-widget-full';
    widget.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;font-family:Segoe UI,sans-serif;pointer-events:none;';

    // Create button
    const btn = document.createElement('button');
    btn.id = 'pgnichat-btn';
    btn.innerHTML = '💬';
    btn.style.cssText = 'width:60px;height:60px;border-radius:50%;background:#075e54;color:#fff;border:none;font-size:28px;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);transition:transform 0.3s;pointer-events:auto;';
    btn.onmouseenter = function() { this.style.transform = 'scale(1.1)'; };
    btn.onmouseleave = function() { this.style.transform = 'scale(1)'; };

    // Create chat box
    const box = document.createElement('div');
    box.id = 'pgnichat-box';
    box.style.cssText = 'display:none;position:absolute;bottom:70px;right:0;width:380px;height:520px;max-height:80vh;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,0.2);overflow:hidden;flex-direction:column;pointer-events:auto;';

    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background:#075e54;padding:12px 16px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;';
    header.innerHTML = '<span style="font-weight:bold;font-size:16px;">🤖 PGNI Bot <span style="font-size:10px;color:#9de1c7;">● Online</span></span><button onclick="closeChat()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:18px;">✕</button>';

    // Body
    const body = document.createElement('div');
    body.id = 'pgnichat-body';
    body.style.cssText = 'flex:1;padding:16px;overflow-y:auto;background:#e5ddd5;';

    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display:flex;flex-direction:column;padding:10px 12px;background:#fff;border-top:1px solid #e0e0e0;flex-shrink:0;';

    // Quick replies
    const quickReplies = document.createElement('div');
    quickReplies.id = 'pgnichat-quick';
    quickReplies.style.cssText = 'display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px;';

    // Input row
    const inputRow = document.createElement('div');
    inputRow.style.cssText = 'display:flex;gap:8px;';

    const input = document.createElement('input');
    input.id = 'pgnichat-input';
    input.type = 'text';
    input.placeholder = 'Ketik pesan...';
    input.style.cssText = 'flex:1;padding:8px 14px;border:1px solid #ddd;border-radius:20px;outline:none;font-size:14px;';
    input.onkeydown = function(e) { if (e.key === 'Enter') { e.preventDefault(); sendMessage(); } };

    const sendBtn = document.createElement('button');
    sendBtn.innerHTML = 'Kirim';
    sendBtn.style.cssText = 'padding:8px 16px;background:#075e54;color:#fff;border:none;border-radius:20px;cursor:pointer;font-size:14px;font-weight:600;';
    sendBtn.onclick = function(e) { e.preventDefault(); sendMessage(); };

    inputRow.appendChild(input);
    inputRow.appendChild(sendBtn);
    footer.appendChild(quickReplies);
    footer.appendChild(inputRow);
    box.appendChild(header);
    box.appendChild(body);
    box.appendChild(footer);
    widget.appendChild(btn);
    widget.appendChild(box);
    document.body.appendChild(widget);

    // ============================================
    // CORE FUNCTIONS
    // ============================================

    function addMessage(type, text) {
        var msgDiv = document.createElement('div');
        msgDiv.style.cssText = 'margin-bottom:10px;max-width:85%;display:flex;flex-direction:column;animation:fadeIn 0.3s ease;';
        msgDiv.className = type === 'user' ? 'msg-user' : 'msg-bot';
        msgDiv.style.alignSelf = type === 'user' ? 'flex-end' : 'flex-start';

        var content = document.createElement('div');
        content.style.cssText = 'padding:8px 14px;border-radius:12px;font-size:14px;line-height:1.6;word-wrap:break-word;max-width:100%;';
        
        if (type === 'bot') {
            content.innerHTML = formatMessage(text);
        } else {
            content.textContent = text;
        }
        
        content.style.background = type === 'user' ? '#dcf8c6' : '#ffffff';
        content.style.borderBottomRightRadius = type === 'user' ? '4px' : '12px';
        content.style.borderBottomLeftRadius = type === 'user' ? '12px' : '4px';
        content.style.boxShadow = type === 'user' ? 'none' : '0 1px 2px rgba(0,0,0,0.08)';

        var time = document.createElement('div');
        time.style.cssText = 'font-size:9px;color:#999;margin-top:2px;padding:0 4px;';
        time.textContent = new Date().toLocaleTimeString();
        if (type === 'user') time.style.textAlign = 'right';

        msgDiv.appendChild(content);
        msgDiv.appendChild(time);
        body.appendChild(msgDiv);
        body.scrollTop = body.scrollHeight;
    }

    // FORMAT MESSAGE WITH ADVANCED URL DETECTION & PAYMENT BUTTON
    function formatMessage(text) {
        if (!text) return '';
        
        // 1. Format Teks Tebal (Bold)
        text = text.replace(/\*(.*?)\*/g, '<strong style="color:#075e54;">$1</strong>');
        
        // 2. Regex URL presisi yang mendukung ?, =, &, %, @, dll
        var urlRegex = /(https?:\/\/[^\s<]+)/gi;
        
        text = text.replace(urlRegex, function(url) {
            var cleanUrl = url.trim().replace(/<br\s*\/?>/gi, '');
            
            // Cek apakah URL merupakan link donasi/pembayaran
            var lowerUrl = cleanUrl.toLowerCase();
            var isDonation = lowerUrl.indexOf('donasi') !== -1 || 
                             lowerUrl.indexOf('payment') !== -1 || 
                             lowerUrl.indexOf('midtrans') !== -1;
            
            if (isDonation) {
                // Tampilan Tombol Bayar
                return '<div style="margin:12px 0;padding:12px;background:#e8f5e9;border-radius:10px;border:2px solid #2e7d32;text-align:center;">' +
                       '<a href="' + cleanUrl + '" target="_blank" rel="noopener noreferrer" style="display:block;padding:12px;background:#2e7d32;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;cursor:pointer;pointer-events:auto;">💳 BAYAR SEKARANG</a>' +
                       '<div style="font-size:10px;color:#666;margin-top:6px;word-break:break-all;">' + cleanUrl + '</div>' +
                       '</div>';
            } else {
                // Tampilan Link Biasa
                return '<a href="' + cleanUrl + '" target="_blank" rel="noopener noreferrer" style="color:#075e54;text-decoration:underline;font-weight:bold;cursor:pointer;pointer-events:auto;">' + cleanUrl + '</a>';
            }
        });
        
        // 3. Konversi baris baru setelah URL diproses
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }

    function showTyping() {
        var typing = document.createElement('div');
        typing.id = 'pgnichat-typing';
        typing.style.cssText = 'padding:8px 14px;background:#fff;border-radius:12px;max-width:60px;border-bottom-left-radius:4px;margin-bottom:10px;box-shadow:0 1px 2px rgba(0,0,0,0.08);';
        typing.innerHTML = '<span style="display:inline-block;width:6px;height:6px;background:#999;border-radius:50%;margin:0 2px;animation:typing 1.4s infinite ease-in-out;"></span><span style="display:inline-block;width:6px;height:6px;background:#999;border-radius:50%;margin:0 2px;animation:typing 1.4s infinite ease-in-out 0.2s;"></span><span style="display:inline-block;width:6px;height:6px;background:#999;border-radius:50%;margin:0 2px;animation:typing 1.4s infinite ease-in-out 0.4s;"></span>';
        body.appendChild(typing);
        body.scrollTop = body.scrollHeight;
    }

    function hideTyping() {
        var el = document.getElementById('pgnichat-typing');
        if (el) el.remove();
    }

    function showQuickReplies(replies) {
        quickReplies.innerHTML = '';
        if (!replies || replies.length === 0) return;
        
        var unique = [];
        for (var i = 0; i < replies.length; i++) {
            if (unique.indexOf(replies[i]) === -1) {
                unique.push(replies[i]);
            }
        }
        
        for (var j = 0; j < unique.length; j++) {
            var reply = unique[j];
            var btn = document.createElement('button');
            btn.textContent = reply;
            btn.style.cssText = 'padding:3px 10px;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:14px;font-size:11px;color:#2e7d32;cursor:pointer;transition:all 0.2s;font-family:inherit;';
            btn.onmouseenter = function() { this.style.background = '#c8e6c9'; this.style.transform = 'scale(1.05)'; };
            btn.onmouseleave = function() { this.style.background = '#e8f5e9'; this.style.transform = 'scale(1)'; };
            btn.onclick = function() {
                input.value = this.textContent;
                sendMessage();
            };
            quickReplies.appendChild(btn);
        }
    }

    function toggleChat() {
        state.isOpen = !state.isOpen;
        box.style.display = state.isOpen ? 'flex' : 'none';
        if (state.isOpen) {
            setTimeout(function() { input.focus(); }, 200);
            if (body.children.length === 0) {
                addMessage('bot', '📋 *Menu Utama PGNI Lampung Bot*\n\n1️⃣ Registrasi Member\n2️⃣ Cek Status Pendaftaran\n3️⃣ Perbaharui Data Member\n4️⃣ Login Member Area\n5️⃣ Berita & Informasi\n6️⃣ Donasi\n7️⃣ Lokasi Kantor\n8️⃣ Tentang PGNI\n\n📌 Ketik nomor pilihan Anda.');
                showQuickReplies(['1', '2', '3', '4', '5', '6', '7', '8', 'menu']);
                state.session = 'menu';
            }
        }
    }

    function closeChat() {
        state.isOpen = false;
        box.style.display = 'none';
    }

    function sendMessage() {
        var message = input.value.trim();
        if (!message || state.isLoading) return;

        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;
        state.isLoading = true;

        addMessage('user', message);
        showTyping();

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: USER_ID,
                user_name: USER_NAME,
                message: message,
                session: state.session,
                data: state.data
            })
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            hideTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            state.isLoading = false;

            if (data.response) {
                addMessage('bot', data.response);
            } else {
                addMessage('bot', '❌ Maaf, terjadi kesalahan.');
            }

            if (data.session) {
                state.session = data.session.state || 'menu';
                state.data = data.session.data || {};
            }

            if (data.quick_replies && data.quick_replies.length > 0) {
                showQuickReplies(data.quick_replies);
            }
            
            body.scrollTop = body.scrollHeight;
            setTimeout(function() { input.focus(); }, 100);
        })
        .catch(function(error) {
            console.error('Error:', error);
            hideTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            state.isLoading = false;
            addMessage('bot', '❌ Koneksi error. Silakan refresh halaman.');
            showQuickReplies(['menu']);
            setTimeout(function() { input.focus(); }, 100);
        });
    }

    // ============================================
    // STYLES
    // ============================================
    
    var style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }
        .msg-user { align-self: flex-end; }
        .msg-bot { align-self: flex-start; }
        
        #pgnichat-body a {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        @media (max-width: 480px) {
            #pgnichat-box {
                position: fixed;
                bottom: 0;
                right: 0;
                width: 100%;
                height: 100%;
                max-height: 100%;
                border-radius: 0;
            }
            #pgnichat-widget-full {
                bottom: 10px;
                right: 10px;
            }
            #pgnichat-btn {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // INIT
    // ============================================
    
    btn.onclick = function(e) {
        e.stopPropagation();
        toggleChat();
    };

    console.log('PGNI Chat Widget loaded successfully!');

    // Auto open
    if (!localStorage.getItem('pgnichat_visited')) {
        setTimeout(function() {
            toggleChat();
            localStorage.setItem('pgnichat_visited', 'true');
        }, 2000);
    }

    window.toggleChat = toggleChat;
    window.closeChat = closeChat;
    window.sendMessage = sendMessage;

})();