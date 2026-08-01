document.addEventListener('DOMContentLoaded', function() {
    const nominalBtns = document.querySelectorAll('.nominal-btn');
    const jumlahInput = document.getElementById('jumlah');
    const donasiForm = document.getElementById('donasiForm');
    const btnDonasi = document.getElementById('btnDonasi');
    
    // Pilih nominal
    nominalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            nominalBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            if (this.dataset.nominal === 'custom') {
                jumlahInput.style.display = 'block';
                jumlahInput.value = '';
                jumlahInput.focus();
                jumlahInput.required = true;
            } else {
                jumlahInput.style.display = 'none';
                jumlahInput.value = this.dataset.nominal;
                jumlahInput.required = true;
            }
        });
    });
    
    // Gantikan event listener submit pada donasi.php
form.addEventListener('submit', function(e) {
    e.preventDefault(); // Mencegah form submit standar agar tidak menampilkan raw JSON
    
    const jumlah = parseInt(jumlahInput.value);
    if (isNaN(jumlah) || jumlah < 100) {
        alert('Minimal donasi adalah Rp 100');
        return false;
    }
    
    const btnDonasi = document.getElementById('btnDonasi');
    btnDonasi.disabled = true;
    btnDonasi.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

    // Kirim data menggunakan Fetch API
    const formData = new FormData(this);

    fetch('proses_donasi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.redirect_url) {
            // Redirect otomatis ke halaman pembayaran Snap
            window.location.href = data.redirect_url;
        } else {
            alert('Gagal memproses donasi: ' + (data.error || 'Terjadi kesalahan'));
            btnDonasi.disabled = false;
            btnDonasi.innerHTML = '<i class="fas fa-hand-holding-heart"></i> Donasi Sekarang';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan jaringan atau server.');
        btnDonasi.disabled = false;
        btnDonasi.innerHTML = '<i class="fas fa-hand-holding-heart"></i> Donasi Sekarang';
    });
});
});