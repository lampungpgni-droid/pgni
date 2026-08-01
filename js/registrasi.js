// js/registrasi.js - VERSI LENGKAP DENGAN OCR.SPACE API
// =========================================================
// REGISTRASI PGNI - JavaScript dengan OCR via OCR.space
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Registrasi PGNI loaded!');

    // ============================================
    // TERMS CHECKBOX
    // ============================================
    const termsCheck = document.getElementById('terms_agree');
    const submitBtn = document.getElementById('submitBtn');
    const termsContainer = document.getElementById('termsCheck');

    if (termsCheck && submitBtn) {
        function updateSubmitButton() {
            if (termsCheck.checked) {
                submitBtn.disabled = false;
                submitBtn.classList.add('active');
                if (termsContainer) termsContainer.classList.add('checked');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('active');
                if (termsContainer) termsContainer.classList.remove('checked');
            }
        }
        termsCheck.addEventListener('change', updateSubmitButton);
        updateSubmitButton();
    }

    // ============================================
    // COMPRESS IMAGE
    // ============================================
    function compressImage(file, maxWidth, maxHeight, quality) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;
                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    let mimeType = 'image/jpeg';
                    let outputQuality = quality;
                    if (file.type === 'image/png') {
                        mimeType = 'image/png';
                        outputQuality = 0.9;
                    } else if (file.type === 'image/webp') {
                        mimeType = 'image/webp';
                        outputQuality = quality;
                    }
                    canvas.toBlob(function(blob) {
                        if (blob) resolve(blob);
                        else reject(new Error('Gagal mengompresi gambar'));
                    }, mimeType, outputQuality);
                };
                img.onerror = function() { reject(new Error('Gagal memuat gambar')); };
                img.src = e.target.result;
            };
            reader.onerror = function() { reject(new Error('Gagal membaca file')); };
            reader.readAsDataURL(file);
        });
    }

    // ============================================
    // SETUP UPLOAD
    // ============================================
    function setupUpload(targetId) {
        const input = document.getElementById(targetId + '_file');
        const preview = document.getElementById(targetId + 'Preview');
        const area = document.getElementById(targetId + 'UploadArea');
        const status = document.getElementById(targetId + 'Status');
        const hidden = document.getElementById(targetId + '_base64');
        const removeBtn = document.querySelector('.btn-remove[data-target="' + targetId + '"]');
        
        if (!input || !preview || !area) return;
        
        const cameraBtn = document.querySelector('.btn-camera[data-target="' + targetId + '"]');
        if (cameraBtn) {
            cameraBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                input.setAttribute('capture', 'environment');
                input.click();
            });
        }
        
        const galleryBtn = document.querySelector('.btn-gallery[data-target="' + targetId + '"]');
        if (galleryBtn) {
            galleryBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                input.removeAttribute('capture');
                input.click();
            });
        }
        
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                if (hidden) hidden.value = '';
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'upload-status';
                area.classList.remove('has-file');
                removeBtn.style.display = 'none';
                if (cameraBtn) cameraBtn.style.display = 'inline-flex';
                if (galleryBtn) galleryBtn.style.display = 'inline-flex';
            });
        }
        
        // ============================================
        // EVENT CHANGE - PROSES UPLOAD
        // ============================================
        input.addEventListener('change', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const file = this.files[0];
            if (!file) {
                area.classList.remove('has-file');
                preview.innerHTML = '';
                status.innerHTML = '';
                status.className = 'upload-status';
                if (hidden) hidden.value = '';
                if (removeBtn) removeBtn.style.display = 'none';
                if (cameraBtn) cameraBtn.style.display = 'inline-flex';
                if (galleryBtn) galleryBtn.style.display = 'inline-flex';
                return;
            }
            
            const maxSize = 10 * 1024 * 1024;
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            if (file.size > maxSize) {
                status.innerHTML = '❌ File terlalu besar (' + fileSizeMB + 'MB)';
                status.className = 'upload-status error';
                this.value = '';
                if (hidden) hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                status.innerHTML = '❌ Tipe file tidak didukung';
                status.className = 'upload-status error';
                this.value = '';
                if (hidden) hidden.value = '';
                area.classList.remove('has-file');
                preview.innerHTML = '';
                return;
            }
            
            let sourceText = '🖼️';
            if (input.hasAttribute('capture')) sourceText = '📷';
            
            status.innerHTML = '⏳ Mengompresi...';
            status.className = 'upload-status loading';
            area.classList.add('has-file');
            
            if (cameraBtn) cameraBtn.style.display = 'none';
            if (galleryBtn) galleryBtn.style.display = 'none';
            if (removeBtn) removeBtn.style.display = 'inline-flex';
            
            compressImage(file, 1280, 720, 0.75)
                .then(compressedBlob => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    };
                    reader.readAsDataURL(compressedBlob);
                    
                    const base64Reader = new FileReader();
                    base64Reader.onload = function(e) {
                        if (hidden) hidden.value = e.target.result;
                        const compressedSizeMB = (compressedBlob.size / (1024 * 1024)).toFixed(2);
                        status.innerHTML = '✅ ' + sourceText + ' ' + fileSizeMB + 'MB → ' + compressedSizeMB + 'MB';
                        status.className = 'upload-status success';
                        
                        // ============================================
                        // JIKA INI KTP, OTOMATIS SCAN
                        // ============================================
                        if (targetId === 'ktp' && hidden && hidden.value) {
                            setTimeout(function() {
                                const imageData = hidden.value;
                                if (imageData) {
                                    openScanModalWithImage(imageData);
                                }
                            }, 500);
                        }
                    };
                    base64Reader.readAsDataURL(compressedBlob);
                })
                .catch(function(err) {
                    console.error('Kompresi gagal:', err);
                    status.innerHTML = '⚠️ Menggunakan file asli...';
                    status.className = 'upload-status loading';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                        if (hidden) hidden.value = e.target.result;
                        status.innerHTML = '✅ File asli (' + fileSizeMB + 'MB)';
                        status.className = 'upload-status success';
                        
                        if (targetId === 'ktp' && hidden && hidden.value) {
                            setTimeout(function() {
                                const imageData = hidden.value;
                                if (imageData) {
                                    openScanModalWithImage(imageData);
                                }
                            }, 500);
                        }
                    };
                    reader.readAsDataURL(file);
                });
        });
        
        // Drag and drop
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.removeAttribute('capture');
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });

        // Klik area untuk upload
        area.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('input')) {
                return;
            }
            input.removeAttribute('capture');
            input.click();
        });
    }

    // Inisialisasi upload
    if (document.getElementById('ktp_file')) setupUpload('ktp');
    if (document.getElementById('kk_file')) setupUpload('kk');

    // ============================================
    // LOAD KECAMATAN & DESA
    // ============================================
    const kabupatenSelect = document.getElementById('kabupaten_id');
    const kecamatanSelect = document.getElementById('kecamatan_id');
    const desaSelect = document.getElementById('desa_id');

    if (kabupatenSelect) {
        kabupatenSelect.addEventListener('change', function() {
            const kabupatenId = this.value;
            if (kecamatanSelect) kecamatanSelect.innerHTML = '<option value="">Loading...</option>';
            if (desaSelect) desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
            
            if (kabupatenId) {
                fetch('ajax/get_kecamatan.php?kabupaten_id=' + kabupatenId)
                    .then(response => response.json())
                    .then(data => {
                        if (kecamatanSelect) {
                            kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
                            data.forEach(kec => {
                                kecamatanSelect.innerHTML += `<option value="${kec.id}">${kec.nama}</option>`;
                            });
                        }
                    })
                    .catch(() => {
                        if (kecamatanSelect) kecamatanSelect.innerHTML = '<option value="">Error</option>';
                    });
            } else {
                if (kecamatanSelect) kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
            }
        });
    }

    if (kecamatanSelect) {
        kecamatanSelect.addEventListener('change', function() {
            const kecamatanId = this.value;
            if (desaSelect) desaSelect.innerHTML = '<option value="">Loading...</option>';
            
            if (kecamatanId) {
                fetch('ajax/get_desa.php?kecamatan_id=' + kecamatanId)
                    .then(response => response.json())
                    .then(data => {
                        if (desaSelect) {
                            desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
                            data.forEach(desa => {
                                desaSelect.innerHTML += `<option value="${desa.id}">${desa.nama}</option>`;
                            });
                        }
                    })
                    .catch(() => {
                        if (desaSelect) desaSelect.innerHTML = '<option value="">Error</option>';
                    });
            } else {
                if (desaSelect) desaSelect.innerHTML = '<option value="">Pilih Desa</option>';
            }
        });
    }

    // ============================================
    // SCAN KTP - MODAL
    // ============================================
    let scanData = {};
    let isProcessing = false;

    // Fungsi untuk membuka modal dengan gambar dari upload
    window.openScanModalWithImage = function(imageData) {
        const modal = document.getElementById('modalScanKTP');
        if (!modal) return;
        
        modal.style.display = 'block';
        document.getElementById('scanStatus').innerHTML = '<span style="color: #f39c12;">⏳ Mengirim gambar ke server...</span>';
        document.getElementById('scanResult').style.display = 'none';
        document.getElementById('scanLoading').style.display = 'block';
        document.getElementById('scanImageInput').value = '';
        scanData = {};
        isProcessing = false;
        
        // Tampilkan preview
        document.getElementById('scanPreview').innerHTML = 
            '<img src="' + imageData + '" style="max-width:100%; max-height:180px; border-radius:8px; border:2px solid #e8e8e8; padding:3px;">';
        
        // Kirim ke server untuk OCR
        setTimeout(function() {
            processOCRWithServer(imageData);
        }, 300);
    };

    // Fungsi buka modal manual
    window.openScanModal = function() {
        const modal = document.getElementById('modalScanKTP');
        if (!modal) return;
        modal.style.display = 'block';
        document.getElementById('scanPreview').innerHTML = '';
        document.getElementById('scanStatus').innerHTML = '';
        document.getElementById('scanResult').style.display = 'none';
        document.getElementById('scanLoading').style.display = 'none';
        document.getElementById('scanImageInput').value = '';
        scanData = {};
        isProcessing = false;
    };

    // Tutup modal
    window.closeScanModal = function() {
        const modal = document.getElementById('modalScanKTP');
        if (modal) modal.style.display = 'none';
        isProcessing = false;
    };

    // Tombol Scan KTP
    const btnScan = document.getElementById('btnScanKTP');
    if (btnScan) {
        btnScan.addEventListener('click', function(e) {
            e.preventDefault();
            window.openScanModal();
        });
    }

    // Tutup modal dengan klik di luar
    const modalScan = document.getElementById('modalScanKTP');
    if (modalScan) {
        modalScan.addEventListener('click', function(e) {
            if (e.target === this) window.closeScanModal();
        });
    }

    // ============================================
    // PROSES OCR VIA SERVER (OCR.SPACE API)
    // ============================================
    function processOCRWithServer(imageData) {
        if (isProcessing) return;
        isProcessing = true;
        
        const statusEl = document.getElementById('scanStatus');
        const loadingEl = document.getElementById('scanLoading');
        const resultEl = document.getElementById('scanResult');
        
        loadingEl.style.display = 'block';
        statusEl.innerHTML = '<span style="color: #f39c12;">⏳ Memproses OCR di server...</span>';
        statusEl.style.color = '#f39c12';
        resultEl.style.display = 'none';

        // Konversi base64 ke Blob
        try {
            const byteString = atob(imageData.split(',')[1]);
            const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
            const ab = new ArrayBuffer(byteString.length);
            const ia = new Uint8Array(ab);
            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            const blob = new Blob([ab], {type: mimeString});
            
            // Kirim ke server
            const formData = new FormData();
            formData.append('image', blob, 'ktp_scan.jpg');
            
            fetch('ajax/ocr_scan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                loadingEl.style.display = 'none';
                
                console.log('📄 Hasil OCR dari server:', result);
                
                if (result.success) {
                    scanData = result.data;
                    displayScanResult(scanData);
                    statusEl.innerHTML = '<span style="color: #28a745;">✅ OCR selesai!</span>';
                    statusEl.style.color = '#28a745';
                    isProcessing = false;
                    
                    // Auto-apply jika ada NIK valid
                    if (scanData && scanData.nik && scanData.nik.length === 16) {
                        setTimeout(function() {
                            autoApplyScanResult();
                        }, 1500);
                    } else {
                        setTimeout(function() {
                            alert('⚠️ NIK tidak terdeteksi atau tidak valid (harus 16 digit).\nSilakan coba upload ulang dengan gambar yang lebih jelas.');
                        }, 500);
                    }
                } else {
                    statusEl.innerHTML = '<span style="color: #dc3545;">❌ ' + result.message + '</span>';
                    statusEl.style.color = '#dc3545';
                    isProcessing = false;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                loadingEl.style.display = 'none';
                statusEl.innerHTML = '<span style="color: #dc3545;">❌ Error koneksi: ' + error.message + '</span>';
                statusEl.style.color = '#dc3545';
                isProcessing = false;
            });
        } catch (error) {
            console.error('Base64 Error:', error);
            loadingEl.style.display = 'none';
            statusEl.innerHTML = '<span style="color: #dc3545;">❌ Error memproses gambar</span>';
            statusEl.style.color = '#dc3545';
            isProcessing = false;
        }
    }

    // ============================================
    // TAMPILKAN HASIL SCAN DI MODAL
    // ============================================
    function displayScanResult(data) {
        const container = document.getElementById('scanResultData');
        const resultEl = document.getElementById('scanResult');
        
        if (!container) return;
        
        let html = '';
        const fields = [
            {key: 'nik', label: 'NIK', required: true},
            {key: 'nama', label: 'Nama Lengkap', required: true},
            {key: 'tempat_lahir', label: 'Tempat Lahir', required: false},
            {key: 'tanggal_lahir', label: 'Tgl Lahir', required: false},
            {key: 'jenis_kelamin', label: 'Jenis Kelamin', required: false},
            {key: 'alamat', label: 'Alamat', required: false},
            {key: 'rt_rw', label: 'RT/RW', required: false},
            {key: 'kel_desa', label: 'Desa/Kelurahan', required: false},
            {key: 'kecamatan', label: 'Kecamatan', required: false},
            {key: 'kabupaten', label: 'Kabupaten', required: false},
            {key: 'provinsi', label: 'Provinsi', required: false},
            {key: 'agama', label: 'Agama', required: false},
            {key: 'status_perkawinan', label: 'Status Perkawinan', required: false},
            {key: 'pekerjaan', label: 'Pekerjaan', required: false},
            {key: 'kewarganegaraan', label: 'Kewarganegaraan', required: false}
        ];
        
        let hasData = false;
        let hasNIK = false;
        let fieldCount = 0;
        let displayData = [];
        
        for (const field of fields) {
            const value = data[field.key] ? data[field.key].trim() : '';
            if (value !== '' && value.length > 0) {
                hasData = true;
                fieldCount++;
                if (field.key === 'nik') {
                    const cleanNik = value.replace(/[^0-9]/g, '');
                    if (cleanNik.length === 16) {
                        hasNIK = true;
                    }
                }
                displayData.push({
                    label: field.label,
                    value: value,
                    required: field.required
                });
            }
        }
        
        if (!hasData) {
            html = '<p style="color:#999; font-size:0.85rem; text-align:center;">Tidak ada data terdeteksi.<br>Pastikan gambar KTP jelas dan terbaca.</p>';
        } else {
            html = '<p style="color:#28a745; font-size:0.8rem; margin-bottom:8px;">✓ ' + fieldCount + ' field terdeteksi</p>';
            
            for (const item of displayData) {
                const isRequired = item.required ? '⚠️' : '';
                html += `<div style="display:flex; padding:4px 0; border-bottom:1px solid #f0f0f0; font-size:0.82rem;">
                    <span style="font-weight:600; color:#555; width:120px; flex-shrink:0;">${item.label}:</span>
                    <span style="color:#1a1a2e; word-break:break-word;">${isRequired} ${item.value}</span>
                </div>`;
            }
            
            if (!hasNIK) {
                html = '<p style="color:#e74c3c; font-size:0.85rem; text-align:center; margin-bottom:10px;">⚠️ NIK tidak valid (harus 16 digit)!</p>' + html;
            }
        }
        
        container.innerHTML = html;
        if (resultEl) resultEl.style.display = 'block';
    }

    // ============================================
    // AUTO APPLY SCAN RESULT KE FORM
    // ============================================
    function autoApplyScanResult() {
        const data = scanData;
        if (!data || !data.nik || data.nik.length !== 16) {
            console.warn('NIK tidak valid untuk diaplikasikan');
            return;
        }
        
        console.log('Mengapply data:', data);
        
        // Isi field NIK
        const nikField = document.getElementById('nik');
        if (nikField && data.nik) {
            nikField.value = data.nik;
            nikField.dispatchEvent(new Event('input'));
            nikField.dispatchEvent(new Event('change'));
        }
        
        // Isi field Nama
        const namaField = document.getElementById('nama');
        if (namaField && data.nama) {
            namaField.value = data.nama;
            namaField.dispatchEvent(new Event('input'));
            namaField.dispatchEvent(new Event('change'));
        }
        
        // Set Kabupaten
        if (data.kabupaten) {
            const kabSelect = document.getElementById('kabupaten_id');
            if (kabSelect) {
                const options = kabSelect.options;
                const searchText = data.kabupaten.toLowerCase().trim();
                let found = false;
                for (let i = 0; i < options.length; i++) {
                    const optionText = options[i].text.toLowerCase().trim();
                    if (optionText === searchText || 
                        optionText.includes(searchText) || 
                        searchText.includes(optionText)) {
                        kabSelect.value = options[i].value;
                        kabSelect.dispatchEvent(new Event('change'));
                        found = true;
                        console.log('Kabupaten ditemukan:', options[i].text);
                        break;
                    }
                }
                if (!found) {
                    console.log('Kabupaten tidak ditemukan:', data.kabupaten);
                }
            }
        }
        
        // Set Kecamatan - delay untuk menunggu dropdown terisi
        setTimeout(function() {
            if (data.kecamatan) {
                const kecSelect = document.getElementById('kecamatan_id');
                if (kecSelect) {
                    const options = kecSelect.options;
                    const searchText = data.kecamatan.toLowerCase().trim();
                    let found = false;
                    for (let i = 0; i < options.length; i++) {
                        const optionText = options[i].text.toLowerCase().trim();
                        if (optionText === searchText || 
                            optionText.includes(searchText) || 
                            searchText.includes(optionText)) {
                            kecSelect.value = options[i].value;
                            kecSelect.dispatchEvent(new Event('change'));
                            found = true;
                            console.log('Kecamatan ditemukan:', options[i].text);
                            break;
                        }
                    }
                    if (!found) {
                        console.log('Kecamatan tidak ditemukan:', data.kecamatan);
                    }
                }
            }
        }, 800);
        
        // Set Desa - delay lebih lama
        setTimeout(function() {
            if (data.kel_desa) {
                const desaSelect = document.getElementById('desa_id');
                if (desaSelect) {
                    const options = desaSelect.options;
                    const searchText = data.kel_desa.toLowerCase().trim();
                    let found = false;
                    for (let i = 0; i < options.length; i++) {
                        const optionText = options[i].text.toLowerCase().trim();
                        if (optionText === searchText || 
                            optionText.includes(searchText) || 
                            searchText.includes(optionText)) {
                            desaSelect.value = options[i].value;
                            found = true;
                            console.log('Desa ditemukan:', options[i].text);
                            break;
                        }
                    }
                    if (!found) {
                        console.log('Desa tidak ditemukan:', data.kel_desa);
                    }
                }
            }
        }, 1500);
        
        // Tutup modal dan tampilkan notifikasi
        setTimeout(function() {
            window.closeScanModal();
            
            let message = '✅ Data berhasil diisi otomatis!\n\n';
            message += 'NIK: ' + data.nik + '\n';
            message += 'Nama: ' + data.nama + '\n';
            if (data.kabupaten) message += 'Kabupaten: ' + data.kabupaten + '\n';
            if (data.kecamatan) message += 'Kecamatan: ' + data.kecamatan + '\n';
            if (data.kel_desa) message += 'Desa: ' + data.kel_desa;
            
            alert(message);
        }, 2000);
    }

    // ============================================
    // APPLY SCAN RESULT (Tombol Manual)
    // ============================================
    window.applyScanResult = function() {
        const data = scanData;
        if (!data || !data.nik) {
            alert('Tidak ada data yang bisa diaplikasikan. Silakan scan ulang KTP.');
            return;
        }
        autoApplyScanResult();
    };

    // ============================================
    // EVENT SCAN IMAGE INPUT (Manual Upload di Modal)
    // ============================================
    const scanInput = document.getElementById('scanImageInput');
    if (scanInput) {
        scanInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
            if (!validTypes.includes(file.type)) {
                document.getElementById('scanStatus').innerHTML = 
                    '<span style="color: #dc3545;">❌ Tipe file tidak didukung</span>';
                document.getElementById('scanStatus').style.color = '#dc3545';
                this.value = '';
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                document.getElementById('scanStatus').innerHTML = 
                    '<span style="color: #dc3545;">❌ File terlalu besar (max 10MB)</span>';
                document.getElementById('scanStatus').style.color = '#dc3545';
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('scanPreview').innerHTML = 
                    '<img src="' + e.target.result + '" style="max-width:100%; max-height:180px; border-radius:8px; border:2px solid #e8e8e8; padding:3px;">';
                processOCRWithServer(e.target.result);
            };
            reader.readAsDataURL(file);
        });
    }

    // ============================================
    // FORM SUBMIT
    // ============================================
    const form = document.getElementById('formRegistrasi');
    if (form) {
        form.addEventListener('submit', function(e) {
            const termsCheck = document.getElementById('terms_agree');
            if (termsCheck && !termsCheck.checked) {
                e.preventDefault();
                alert('Silakan setujui syarat & ketentuan terlebih dahulu.');
                return;
            }
            
            const nikField = document.getElementById('nik');
            const namaField = document.getElementById('nama');
            const tempatField = document.getElementById('tempat_mengajar');
            
            const nik = nikField ? nikField.value.trim() : '';
            const nama = namaField ? namaField.value.trim() : '';
            const tempat_mengajar = tempatField ? tempatField.value : '';
            
            if (!nik || !nama || !tempat_mengajar) {
                e.preventDefault();
                alert('NIK, Nama Lengkap, dan Tempat Mengajar wajib diisi!');
                return;
            }
            
            if (nik.length !== 16 || !/^\d+$/.test(nik)) {
                e.preventDefault();
                alert('NIK harus 16 digit angka!');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftarkan...';
            }
        });
    }
    
    console.log('✅ Registrasi JavaScript siap!');
});