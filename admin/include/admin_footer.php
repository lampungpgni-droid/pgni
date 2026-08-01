<?php
// admin/include/admin_footer.php

// Tampilkan popup WhatsApp jika ada session
function showWhatsAppPopup() {
    if (isset($_SESSION['whatsapp_guru_url']) && isset($_SESSION['whatsapp_admin_url'])) {
        $guru_url = $_SESSION['whatsapp_guru_url'];
        $admin_url = $_SESSION['whatsapp_admin_url'];
        $status = $_SESSION['whatsapp_status'] ?? '';
        $name = $_SESSION['whatsapp_guru_name'] ?? 'Guru';
        $status_text = $status === 'disetujui' ? 'Disetujui' : 'Ditolak';
        $status_icon = $status === 'disetujui' ? '✅' : '❌';
        
        // Hapus session setelah ditampilkan
        unset($_SESSION['whatsapp_guru_url']);
        unset($_SESSION['whatsapp_admin_url']);
        unset($_SESSION['whatsapp_status']);
        unset($_SESSION['whatsapp_guru_name']);
        
        echo '
        <!-- WhatsApp Popup -->
        <div class="whatsapp-popup" id="whatsappPopup">
            <div class="whatsapp-popup-content">
                <div class="popup-header">
                    <i class="fab fa-whatsapp"></i>
                    <h4>Kirim Notifikasi WhatsApp</h4>
                    <button class="popup-close" onclick="closeWhatsAppPopup()">&times;</button>
                </div>
                <div class="popup-body">
                    <div class="status-badge ' . $status . '">
                        ' . $status_icon . ' Verifikasi ' . $status_text . '
                    </div>
                    <p class="guru-name">Untuk: <strong>' . htmlspecialchars($name) . '</strong></p>
                    <p class="popup-desc">Pilih tombol di bawah untuk mengirim notifikasi melalui WhatsApp</p>
                </div>
                <div class="popup-actions">
                    <a href="' . $guru_url . '" target="_blank" class="btn-whatsapp btn-guru">
                        <i class="fab fa-whatsapp"></i> Kirim ke Guru
                    </a>
                    <a href="' . $admin_url . '" target="_blank" class="btn-whatsapp btn-admin">
                        <i class="fab fa-whatsapp"></i> Kirim ke Admin
                    </a>
                </div>
                <div class="popup-footer">
                    <span class="popup-note">
                        <i class="fas fa-info-circle"></i> Klik tombol untuk membuka WhatsApp
                    </span>
                    <button class="btn-skip" onclick="closeWhatsAppPopup()">Lewati</button>
                </div>
            </div>
        </div>
        
        <style>
            /* WhatsApp Popup Styles */
            .whatsapp-popup {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
                animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                max-width: 420px;
                width: 100%;
            }
            
            .whatsapp-popup-content {
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(37, 211, 102, 0.15);
                border-top: 5px solid #25D366;
                overflow: hidden;
            }
            
            .popup-header {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                background: #f8faf8;
                border-bottom: 1px solid #e8f5e9;
            }
            
            .popup-header i.fa-whatsapp {
                color: #25D366;
                font-size: 1.5rem;
            }
            
            .popup-header h4 {
                flex: 1;
                margin: 0;
                font-size: 1rem;
                font-weight: 600;
                color: #2c3e50;
            }
            
            .popup-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #95a5a6;
                cursor: pointer;
                padding: 0 5px;
                transition: color 0.3s ease;
            }
            
            .popup-close:hover {
                color: #e74c3c;
            }
            
            .popup-body {
                padding: 20px;
                text-align: center;
            }
            
            .status-badge {
                display: inline-block;
                padding: 6px 18px;
                border-radius: 30px;
                font-size: 0.85rem;
                font-weight: 600;
                margin-bottom: 12px;
            }
            
            .status-badge.disetujui {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .status-badge.ditolak {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .guru-name {
                margin: 0 0 8px 0;
                color: #495057;
                font-size: 0.95rem;
            }
            
            .guru-name strong {
                color: #2c3e50;
            }
            
            .popup-desc {
                margin: 0;
                color: #7f8c8d;
                font-size: 0.85rem;
            }
            
            .popup-actions {
                display: flex;
                gap: 12px;
                padding: 0 20px 15px 20px;
            }
            
            .btn-whatsapp {
                flex: 1;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 16px;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.85rem;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            
            .btn-whatsapp:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            }
            
            .btn-guru {
                background: #25D366;
                color: #ffffff;
            }
            
            .btn-guru:hover {
                background: #1da851;
            }
            
            .btn-admin {
                background: #075e54;
                color: #ffffff;
            }
            
            .btn-admin:hover {
                background: #054740;
            }
            
            .popup-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 20px;
                background: #f8faf8;
                border-top: 1px solid #e8f5e9;
            }
            
            .popup-note {
                color: #95a5a6;
                font-size: 0.75rem;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .popup-note i {
                color: #3498db;
            }
            
            .btn-skip {
                background: none;
                border: none;
                color: #95a5a6;
                font-size: 0.8rem;
                cursor: pointer;
                padding: 5px 10px;
                border-radius: 6px;
                transition: all 0.3s ease;
            }
            
            .btn-skip:hover {
                background: #eef2f5;
                color: #495057;
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(30px) scale(0.95);
                    opacity: 0;
                }
                to {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }
            }
            
            @keyframes slideDown {
                from {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }
                to {
                    transform: translateY(30px) scale(0.95);
                    opacity: 0;
                }
            }
            
            .whatsapp-popup.closing {
                animation: slideDown 0.4s ease forwards;
            }
            
            /* Responsive */
            @media (max-width: 480px) {
                .whatsapp-popup {
                    bottom: 15px;
                    right: 15px;
                    left: 15px;
                    max-width: 100%;
                }
                
                .popup-actions {
                    flex-direction: column;
                    gap: 8px;
                }
                
                .btn-whatsapp {
                    padding: 14px 16px;
                }
                
                .popup-footer {
                    flex-direction: column;
                    gap: 8px;
                    text-align: center;
                }
                
                .popup-header h4 {
                    font-size: 0.9rem;
                }
            }
        </style>
        
        <script>
            function closeWhatsAppPopup() {
                const popup = document.getElementById(\'whatsappPopup\');
                if (popup) {
                    popup.classList.add(\'closing\');
                    setTimeout(function() {
                        popup.style.display = \'none\';
                    }, 400);
                }
            }
            
            // Auto close after 20 seconds
            setTimeout(function() {
                closeWhatsAppPopup();
            }, 20000);
            
            // Close on click outside
            document.addEventListener(\'click\', function(e) {
                const popup = document.getElementById(\'whatsappPopup\');
                const content = popup?.querySelector(\'.whatsapp-popup-content\');
                if (popup && content && !popup.classList.contains(\'closing\')) {
                    if (!content.contains(e.target) && e.target !== popup) {
                        closeWhatsAppPopup();
                    }
                }
            });
        </script>
        ';
    }
}

// Panggil fungsi untuk menampilkan popup
showWhatsAppPopup();
?>
    </main>
</div>

<script>
    // Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = adminSidebar.classList.contains('open') ? 'hidden' : '';
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            adminSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Auto close sidebar on resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            adminSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Close WhatsApp popup with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const popup = document.getElementById('whatsappPopup');
            if (popup && !popup.classList.contains('closing')) {
                closeWhatsAppPopup();
            }
        }
    });
</script>
</body>
</html>