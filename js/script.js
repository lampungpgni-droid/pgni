// js/script.js

document.addEventListener('DOMContentLoaded', function() {
    
    // === Mobile Menu Toggle ===
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('open');
        });
        
        // Close menu on link click
        mainNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                menuToggle.classList.remove('active');
                mainNav.classList.remove('open');
            });
        });
    }
    
    // === Scroll to Top Button ===
    const scrollBtn = document.getElementById('scrollTop');
    
    if (scrollBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // === Hero Stats Animation ===
    const stats = document.querySelectorAll('.stat-number');
    
    if (stats.length > 0) {
        let animated = false;
        
        const animateStats = () => {
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-count'));
                let current = 0;
                const increment = Math.ceil(target / 40);
                const duration = 2000;
                const interval = duration / 40;
                
                const counter = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        clearInterval(counter);
                        current = target;
                    }
                    stat.textContent = current;
                }, interval);
            });
        };
        
        // Check if stats are visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !animated) {
                    animated = true;
                    animateStats();
                }
            });
        }, { threshold: 0.5 });
        
        stats.forEach(stat => {
            observer.observe(stat.parentElement);
        });
    }
    
    // === Image Preview for Upload ===
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const preview = this.parentElement.querySelector('.image-preview');
                if (preview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; border-radius: 8px; margin-top: 10px;">`;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    });
    
    // === Video Preview for Upload ===
    document.querySelectorAll('input[type="file"][accept*="video"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const preview = this.parentElement.querySelector('.video-preview');
                if (preview) {
                    const url = URL.createObjectURL(file);
                    preview.innerHTML = `
                        <video controls style="max-width: 300px; border-radius: 8px; margin-top: 10px;">
                            <source src="${url}" type="${file.type}">
                            Browser Anda tidak mendukung tag video.
                        </video>
                    `;
                }
            }
        });
    });
    
    // === Confirm Delete ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                window.location.href = this.href;
            }
        });
    });
    
    // === Alert Auto Close ===
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // === Smooth Scroll for anchor links ===
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // === Active Nav Link ===
    const currentPath = window.location.pathname;
    document.querySelectorAll('.main-nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath.split('/').pop()) {
            link.closest('li').classList.add('active');
        }
    });
});

// === File Size Validator ===
function validateFileSize(input, maxSizeMB = 64) {
    const maxSize = maxSizeMB * 1024 * 1024;
    if (input.files[0] && input.files[0].size > maxSize) {
        alert(`Ukuran file terlalu besar. Maksimal ${maxSizeMB}MB.`);
        input.value = '';
        return false;
    }
    return true;
}

// === Video Compression (Client-side) ===
function compressVideo(file, maxWidth = 720, maxHeight = 1280) {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        
        video.src = URL.createObjectURL(file);
        video.onloadedmetadata = function() {
            let width = video.videoWidth;
            let height = video.videoHeight;
            
            if (width > maxWidth) {
                height = height * maxWidth / width;
                width = maxWidth;
            }
            if (height > maxHeight) {
                width = width * maxHeight / height;
                height = maxHeight;
            }
            
            canvas.width = width;
            canvas.height = height;
            
            const stream = canvas.captureStream(30);
            const mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'video/webm;codecs=vp9',
                videoBitsPerSecond: 2500000
            });
            
            const chunks = [];
            mediaRecorder.ondataavailable = e => chunks.push(e.data);
            mediaRecorder.onstop = () => {
                const blob = new Blob(chunks, { type: 'video/webm' });
                resolve(blob);
            };
            
            video.play();
            mediaRecorder.start();
            
            const drawFrame = () => {
                context.drawImage(video, 0, 0, width, height);
                if (!video.paused && !video.ended) {
                    requestAnimationFrame(drawFrame);
                }
            };
            drawFrame();
            
            video.onended = () => {
                mediaRecorder.stop();
            };
        };
        
        video.onerror = reject;
    });
}