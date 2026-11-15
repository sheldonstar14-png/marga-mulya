// =========================================================
// JAVASCRIPT CMS: konfirmasi hapus, upload foto ke Cloudinary, pilih lokasi di peta
// =========================================================
(function () {
  // ---------- Konfirmasi sebelum menghapus / menolak ----------
  document.querySelectorAll('.form-hapus').forEach(f => f.addEventListener('submit', e => {
    if (!confirm('Hapus "' + f.dataset.nama + '"? Data yang dihapus tidak bisa dikembalikan.')) e.preventDefault();
  }));
  document.querySelectorAll('.form-konfirmasi').forEach(f => f.addEventListener('submit', e => {
    if (!confirm(f.dataset.pesan)) e.preventDefault();
  }));

  // ---------- Upload foto ----------
  const csrf = document.querySelector('input[name="csrf"]');
  document.querySelectorAll('.unggah-foto').forEach(input => input.addEventListener('change', async () => {
    const file = input.files[0];
    const target = document.getElementById(input.dataset.target);
    const status = document.getElementById('status-' + input.dataset.target);
    const preview = document.getElementById(input.dataset.preview);
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { status.textContent = 'File harus berupa foto JPG, PNG, atau WebP.'; return; }
    if (file.size > 5 * 1024 * 1024) { status.textContent = 'Ukuran foto maksimal 5 MB. Perkecil dulu fotonya.'; return; }

    const tombolSimpan = input.form.querySelector('button[type="submit"]');
    tombolSimpan.disabled = true;
    status.textContent = 'Mengunggah foto...';
    try {
      // 1. Minta tanda tangan upload ke server kita
      const izin = await fetch('/admin/upload-sign', { method: 'POST', body: new URLSearchParams({ csrf: csrf.value }) });
      const data = await izin.json();
      if (!izin.ok) throw new Error(data.error || 'Gagal meminta izin upload.');

      // 2. Kirim foto langsung ke Cloudinary
      const kirim = new FormData();
      kirim.append('file', file);
      kirim.append('api_key', data.api_key);
      kirim.append('timestamp', data.timestamp);
      kirim.append('folder', data.folder);
      kirim.append('signature', data.signature);
      const hasil = await fetch(data.url, { method: 'POST', body: kirim });
      const foto = await hasil.json();
      if (!hasil.ok) throw new Error(foto.error ? foto.error.message : 'Cloudinary menolak foto.');

      // 3. Simpan alamat foto ke kolom form
      target.value = foto.secure_url;
      preview.innerHTML = '<img src="' + foto.secure_url + '" alt="Pratinjau foto">';
      status.textContent = 'Foto terunggah. Klik Simpan untuk menyimpan.';
    } catch (err) {
      status.textContent = err.message;
    } finally {
      tombolSimpan.disabled = false;
    }
  }));

  // Pratinjau saat alamat gambar ditempel manual
  document.querySelectorAll('.unggah-foto').forEach(input => {
    const target = document.getElementById(input.dataset.target);
    const preview = document.getElementById(input.dataset.preview);
    target.addEventListener('change', () => {
      preview.innerHTML = target.value ? '<img src="' + target.value.replace(/"/g, '') + '" alt="Pratinjau foto">' : '';
    });
  });

  // ---------- Pilih lokasi di peta ----------
  const el = document.querySelector('.peta-pilih');
  if (el && typeof L !== 'undefined') {
    const lat = document.getElementById('lat'), lng = document.getElementById('lng');
    const ada = lat.value !== '' && lng.value !== '';
    const awal = ada ? [Number(lat.value), Number(lng.value)] : [Number(el.dataset.pusatLat), Number(el.dataset.pusatLng)];
    const peta = L.map(el).setView(awal, ada ? 16 : 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);
    const titik = L.marker(awal, { draggable: true });
    if (ada) titik.addTo(peta);
    const pasang = p => {
      titik.setLatLng(p).addTo(peta);
      lat.value = p.lat.toFixed(6);
      lng.value = p.lng.toFixed(6);
    };
    peta.on('click', e => pasang(e.latlng));
    titik.on('dragend', () => pasang(titik.getLatLng()));
    [lat, lng].forEach(i => i.addEventListener('change', () => {
      if (lat.value && lng.value) { const p = L.latLng(Number(lat.value), Number(lng.value)); titik.setLatLng(p).addTo(peta); peta.panTo(p); }
    }));
  }
})();

// Di layar HP, geser menu samping supaya menu yang aktif terlihat
(function () {
  const aktif = document.querySelector('.side-link.on');
  if (aktif && window.innerWidth <= 820) aktif.parentElement.scrollLeft = aktif.offsetLeft - 40;
})();
