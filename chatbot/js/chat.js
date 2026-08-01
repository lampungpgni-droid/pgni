// chatbot/js/chat.js
// Fungsi tambahan untuk chat

// Check if user is on mobile
function isMobile() {
    return window.innerWidth <= 768;
}

// Handle sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', function() {
    // Create overlay for mobile sidebar
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    overlay.id = 'sidebarOverlay';
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', function() {
        toggleSidebar();
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Format number with thousands separator
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Validate NIK
function validateNIK(nik) {
    return /^[0-9]{16}$/.test(nik);
}

// Validate phone number
function validatePhone(phone) {
    const cleaned = phone.replace(/[^0-9]/g, '');
    return cleaned.length >= 10 && cleaned.length <= 15;
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('📋 Teks disalin ke clipboard');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = 0;
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showToast('📋 Teks disalin ke clipboard');
    } catch (err) {
        console.error('Failed to copy:', err);
    }
    document.body.removeChild(textarea);
}

// Show toast notification
function showToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: #fff;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 2000;
        animation: fadeInUp 0.3s ease;
        max-width: 90%;
        text-align: center;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Parse message for links and formatting
function parseMessage(text) {
    // Convert \n to <br>
    text = text.replace(/\n/g, '<br>');
    
    // Convert URLs to links
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
    
    // Convert bold text (*text*)
    text = text.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
    
    return text;
}

// Auto resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
}

// Get emoji from text
function getEmoji(text) {
    const emojis = {
        'menu': '📋',
        'registrasi': '📝',
        'status': '🔍',
        'update': '🔄',
        'login': '🔐',
        'berita': '📰',
        'donasi': '🤲',
        'lokasi': '📍',
        'tentang': '🏛️',
        'batal': '❌',
        'ya': '✅',
        'tidak': '❌'
    };
    
    const lower = text.toLowerCase();
    for (const [key, emoji] of Object.entries(emojis)) {
        if (lower.includes(key)) return emoji;
    }
    return '';
}