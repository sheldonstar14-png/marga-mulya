// =========================================================
// JAVASCRIPT CMS: konfirmasi hapus, upload foto ke Cloudinary, pilih lokasi di peta
// =========================================================
(function () {
  // ---------- Menu CMS di HP: layar penuh + cari menu ----------
  const menu = document.getElementById('menuCms'), bukaMenu = document.getElementById('menuCmsBtn'), tutupMenu = document.getElementById('menuCmsTutup');
  if (menu && bukaMenu) {
    const atur = buka => { document.body.classList.toggle('menu-cms-buka', buka); bukaMenu.setAttribute('aria-expanded', String(buka)); if (buka) { const c = document.getElementById('cariMenuCms'); if (c) c.focus(); } };
    bukaMenu.addEventListener('click', () => atur(!document.body.classList.contains('menu-cms-buka')));
    if (tutupMenu) tutupMenu.addEventListener('click', () => { atur(false); bukaMenu.focus(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && document.body.classList.contains('menu-cms-buka')) { atur(false); bukaMenu.focus(); } });
  }
  const cariMenu = document.getElementById('cariMenuCms');
  if (cariMenu) cariMenu.addEventListener('input', () => {
    const k = cariMenu.value.trim().toLowerCase();
    menu.querySelectorAll('.side-link').forEach(a => { a.hidden = k !== '' && !a.textContent.toLowerCase().includes(k); });
    menu.querySelectorAll('h4').forEach(h => {
      let n = h.nextElementSibling, ada = false;
      while (n && n.tagName !== 'H4') { if (n.classList.contains('side-link') && !n.hidden) ada = true; n = n.nextElementSibling; }
      h.hidden = !ada;
    });
  });

  // ---------- Di HP, seluruh kartu data bisa ditekan untuk membuka halaman edit ----------
  document.querySelectorAll('table.daftar-kartu tr[data-cari-item]').forEach(tr => tr.addEventListener('click', e => {
    if (window.innerWidth > 820 || e.target.closest('a, button, form, input')) return;
    const a = tr.querySelector('.tombol-edit');
    if (a) location.href = a.href;
  }));

  // ---------- Kotak cari di daftar panjang ----------
  document.querySelectorAll('[data-cari-daftar]').forEach(input => {
    const wadah = document.querySelector(input.dataset.cariDaftar), info = input.parentElement.querySelector('.cari-info');
    input.addEventListener('input', () => {
      const k = input.value.trim().toLowerCase(); let n = 0;
      wadah.querySelectorAll('[data-cari-item]').forEach(el => { const cocok = !k || el.textContent.toLowerCase().includes(k); el.hidden = !cocok; if (cocok) n++; });
      if (info) info.textContent = k ? n + ' data cocok' : '';
    });
  });

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

  // ---------- Pilih lokasi lewat Google Maps (tempel koordinat atau tautan) ----------
  const kotakLokasi = document.querySelector('.pilih-lokasi');
  if (kotakLokasi) {
    const lat = document.getElementById('lat'), lng = document.getElementById('lng');
    const tempel = kotakLokasi.querySelector('.tempel-lokasi'), pratinjau = kotakLokasi.querySelector('.pratinjau-lokasi');
    const perbarui = () => {
      if (lat.value && lng.value && !isNaN(lat.value) && !isNaN(lng.value)) pratinjau.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(lat.value + ',' + lng.value) + '&z=17&output=embed';
    };
    tempel.addEventListener('input', () => {
      const t = decodeURIComponent(tempel.value);
      // Mengenali "-6.04, 106.53", tautan ".../@-6.04,106.53,17z", "q=-6.04,106.53", atau "!3d-6.04!4d106.53"
      const m = t.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/) || t.match(/@(-?\d+\.\d+),\s*(-?\d+\.\d+)/) || t.match(/(-?\d{1,2}\.\d{3,})\s*,\s*(-?\d{1,3}\.\d{3,})/);
      if (!m) return;
      lat.value = Number(m[1]).toFixed(6); lng.value = Number(m[2]).toFixed(6);
      perbarui();
    });
    [lat, lng].forEach(i => i.addEventListener('change', perbarui));
  }
})();

// ---------- Dropdown: tambah pilihan baru ----------
document.querySelectorAll('select.bisa-tambah').forEach(sel => {
  const isian = document.getElementById(sel.id + '-baru');
  const atur = () => {
    const baru = sel.value === '__baru__';
    isian.hidden = !baru;
    isian.required = baru;
    if (baru) isian.focus();
  };
  sel.addEventListener('change', atur);
  isian.hidden = sel.value !== '__baru__';
});

// ---------- Akun: paket akses dan sembunyikan centang untuk Super admin ----------
document.querySelectorAll('.preset-akses').forEach(b => b.addEventListener('click', () => {
  const pilih = b.dataset.akses ? b.dataset.akses.split(',') : [];
  document.querySelectorAll('input[name="akses[]"]').forEach(c => { c.checked = pilih.includes(c.value); });
}));
const peran = document.getElementById('f-peran');
const kotakAkses = document.querySelector('.akses-box');
if (peran && kotakAkses) {
  const aturAkses = () => { kotakAkses.hidden = peran.value === 'Super admin'; };
  peran.addEventListener('change', aturAkses);
  aturAkses();
}

// Di layar HP, geser menu samping supaya menu yang aktif terlihat
(function () {
  const aktif = document.querySelector('.side-link.on');
  if (aktif && window.innerWidth <= 820) aktif.parentElement.scrollLeft = aktif.offsetLeft - 40;
})();
