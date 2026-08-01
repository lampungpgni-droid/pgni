<?php
// chatbot/index.php
session_start();
require_once __DIR__ . '/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Pengunjung';
$userId = $_SESSION['user_id'] ?? 'guest_' . uniqid();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PGNI Lampung - Live Chat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e5ddd5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .chat-container {
            width: 100%;
            max-width: 500px;
            height: 100vh;
            max-height: 650px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            background: linear-gradient(135deg, #075e54, #0a7a6e);
            padding: 16px 20px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h2 { font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .chat-header .status { font-size: 11px; color: #9de1c7; }
        .chat-header .user { font-size: 13px; opacity: 0.8; }
        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #e5ddd5;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDQwMCA0MDAiPjxwYXRoIGQ9Ik0wIDBoMjAwdjIwMEgweiIgZmlsbD0iI2U1ZGRkNSIgLz48L3N2Zz4=');
            background-size: 40px 40px;
        }
        .message {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            max-width: 80%;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message-user { align-self: flex-end; }
        .message-bot { align-self: flex-start; }
        .message-content {
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
        }
        .message-user .message-content {
            background: #dcf8c6;
            border-bottom-right-radius: 4px;
        }
        .message-bot .message-content {
            background: #fff;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        .message-content strong { color: #075e54; }
        .message-content a { color: #075e54; text-decoration: none; border-bottom: 1px dashed #075e54; }
        .message-time {
            font-size: 10px;
            color: #999;
            margin-top: 2px;
            padding: 0 4px;
        }
        .message-user .message-time { text-align: right; }
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 4px 0;
        }
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }
        .chat-input-area {
            padding: 10px 16px;
            background: #fff;
            border-top: 1px solid #e0e0e0;
        }
        .chat-input-container {
            display: flex;
            gap: 10px;
        }
        .chat-input-container input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 24px;
            outline: none;
            font-size: 14px;
        }
        .chat-input-container input:focus { border-color: #075e54; }
        .btn-send {
            padding: 10px 24px;
            background: #075e54;
            color: #fff;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-send:hover { background: #054740; }
        .btn-send:disabled { opacity: 0.5; cursor: not-allowed; }
        .quick-replies {
            display: flex;
            gap: 6px;
            padding: 8px 0 4px;
            flex-wrap: wrap;
        }
        .quick-reply {
            padding: 4px 14px;
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            border-radius: 16px;
            font-size: 12px;
            color: #2e7d32;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-reply:hover {
            background: #c8e6c9;
            transform: scale(1.05);
        }
        .chat-footer {
            padding: 6px 16px;
            background: #f5f5f5;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
        }
        .error-msg {
            color: #d32f2f;
            font-size: 12px;
            text-align: center;
            padding: 8px;
            background: #ffebee;
            border-radius: 8px;
            margin: 4px 0;
        }
        @media (max-width: 480px) {
            .chat-container {
                max-height: 100vh;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>
                <span>🤖</span>
                PGNI Bot
                <span class="status">● Online</span>
            </h2>
            <span class="user">👤 <?= htmlspecialchars($userName) ?></span>
        </div>
        
        <div class="chat-messages" id="chatMessages"></div>
        
        <div class="chat-input-area">
            <div class="quick-replies" id="quickReplies"></div>
            <div class="chat-input-container">
                <input type="text" id="chatInput" placeholder="Ketik pesan..." onkeydown="handleEnter(event)">
                <button class="btn-send" id="sendBtn" onclick="sendMessage()">Kirim</button>
            </div>
        </div>
        
        <div class="chat-footer">PGNI Lampung &copy; <?= date('Y') ?></div>
    </div>

    <script>
    (function() {
        'use strict';
        
        const CONFIG = {
            USER_ID: '<?= addslashes($userId) ?>',
            USER_NAME: '<?= addslashes($userName) ?>',
            API_URL: 'chat-handler.php',
            HISTORY_KEY: 'chat_history_' + '<?= addslashes($userId) ?>'
        };
        
        let state = {
            messages: [],
            session: 'menu',
            data: {},
            isLoading: false
        };
        
        const els = {
            messages: document.getElementById('chatMessages'),
            input: document.getElementById('chatInput'),
            quickReplies: document.getElementById('quickReplies'),
            sendBtn: document.getElementById('sendBtn')
        };
        
        function formatMessage(text) {
            if (!text) return '';
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
            text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
            return text;
        }
        
        function loadHistory() {
            try {
                const saved = localStorage.getItem(CONFIG.HISTORY_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (parsed && parsed.length > 0) {
                        state.messages = parsed;
                        renderMessages();
                        return;
                    }
                }
            } catch(e) {}
            showWelcome();
        }
        
        function showWelcome() {
            const welcome = "🌙 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\nSelamat datang di *PGNI Lampung Bot*! 🤖\n\n📋 *Menu Layanan:*\n1️⃣ Registrasi\n2️⃣ Cek Status\n3️⃣ Update Data\n4️⃣ Login\n5️⃣ Berita\n6️⃣ Donasi\n7️⃣ Lokasi\n8️⃣ Tentang\n\n📌 Ketik *menu* untuk menu utama";
            state.messages = [{ type: 'bot', text: welcome, time: new Date().toLocaleTimeString() }];
            saveHistory();
            renderMessages();
        }
        
        function saveHistory() {
            try {
                localStorage.setItem(CONFIG.HISTORY_KEY, JSON.stringify(state.messages));
            } catch(e) {}
        }
        
        function renderMessages() {
            const container = els.messages;
            container.innerHTML = '';
            if (!state.messages || state.messages.length === 0) {
                showWelcome();
                return;
            }
            state.messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message message-${msg.type}`;
                const content = document.createElement('div');
                content.className = 'message-content';
                content.innerHTML = formatMessage(msg.text);
                const time = document.createElement('div');
                time.className = 'message-time';
                time.textContent = msg.time || new Date().toLocaleTimeString();
                div.appendChild(content);
                div.appendChild(time);
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
        }
        
        function addMessage(type, text) {
            state.messages.push({ type, text, time: new Date().toLocaleTimeString() });
            saveHistory();
            renderMessages();
        }
        
        function showTyping() {
            const container = els.messages;
            const div = document.createElement('div');
            div.className = 'message message-bot';
            div.id = 'typingIndicator';
            div.innerHTML = `<div class="message-content"><div class="typing-indicator"><span></span><span></span><span></span></div></div>`;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        function hideTyping() {
            const el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }
        
        function sendMessage() {
            const input = els.input;
            const message = input.value.trim();
            if (!message || state.isLoading) return;
            
            input.value = '';
            input.disabled = true;
            els.sendBtn.disabled = true;
            state.isLoading = true;
            
            addMessage('user', message);
            showTyping();
            
            fetch(CONFIG.API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: CONFIG.USER_ID,
                    user_name: CONFIG.USER_NAME,
                    message: message,
                    session: state.session,
                    data: state.data
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                hideTyping();
                input.disabled = false;
                els.sendBtn.disabled = false;
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
            })
            .catch(error => {
                console.error('Error:', error);
                hideTyping();
                input.disabled = false;
                els.sendBtn.disabled = false;
                state.isLoading = false;
                addMessage('bot', '❌ Koneksi error. Silakan refresh halaman.');
                showQuickReplies(['menu']);
            });
        }
        
        function showQuickReplies(replies) {
            const container = els.quickReplies;
            container.innerHTML = '';
            if (!replies || replies.length === 0) return;
            const unique = [...new Set(replies)];
            unique.forEach(reply => {
                const btn = document.createElement('button');
                btn.className = 'quick-reply';
                btn.textContent = reply;
                btn.onclick = function() {
                    els.input.value = reply;
                    sendMessage();
                };
                container.appendChild(btn);
            });
        }
        
        function handleEnter(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        }
        
        // Init
        document.addEventListener('DOMContentLoaded', function() {
            loadHistory();
            showQuickReplies(['1', '2', '3', '4', '5', '6', '7', '8', 'menu']);
            els.input.focus();
        });
        
        window.sendMessage = sendMessage;
        window.handleEnter = handleEnter;
        
    })();
    </script>
</body>
</html>