// =========================================================
// ASISTEN DESA BERMENU
// Pengunjung cukup menekan nomor pilihan. Teks dari server dimasukkan sebagai teks biasa
// (bukan HTML), jadi aman dari sisipan kode.
// =========================================================
(function () {
  const tombol = document.getElementById('asistenBtn'), panel = document.getElementById('asistenPanel');
  if (!tombol || !panel) return;
  const isi = document.getElementById('asistenIsi'), jejakEl = document.getElementById('asistenJejak'),
        kembali = document.getElementById('asistenKembali'), awal = document.getElementById('asistenAwal'),
        tutup = document.getElementById('asistenTutup');
  let sekarang = null, dimuat = false;

  function buka() {
    panel.hidden = false; tombol.setAttribute('aria-expanded', 'true'); tombol.hidden = true;
    if (!dimuat) { dimuat = true; muat(0); } else { fokus(); }
  }
  function tutupPanel() { panel.hidden = true; tombol.hidden = false; tombol.setAttribute('aria-expanded', 'false'); tombol.focus(); }
  function fokus() { const f = isi.querySelector('button, a'); if (f) f.focus(); }
  tombol.addEventListener('click', buka);
  tutup.addEventListener('click', tutupPanel);
  document.addEventListener('keydown', e => {
    if (panel.hidden) return;
    if (e.key === 'Escape') tutupPanel();
    // Tekan angka 1-7 di papan ketik untuk memilih, seperti di telepon
    if (/^[1-7]$/.test(e.key) && !e.target.closest('input, textarea')) {
      const b = isi.querySelectorAll('.asisten-opsi [data-id]')[Number(e.key) - 1];
      if (b) b.click();
    }
  });
  kembali.addEventListener('click', () => { if (sekarang && sekarang.id) muat(sekarang.induk || 0); });
  awal.addEventListener('click', () => muat(0));

  function el(tag, kelas, teks) { const x = document.createElement(tag); if (kelas) x.className = kelas; if (teks) x.textContent = teks; return x; }
  function paragraf(teks) {
    const p = el('div', 'asisten-jawab');
    teks.split('\n').forEach((baris, i) => { if (i) p.appendChild(document.createElement('br')); p.appendChild(document.createTextNode(baris)); });
    return p;
  }

  function tampil(d) {
    sekarang = d;
    isi.innerHTML = '';
    jejakEl.textContent = ['Menu awal'].concat(d.jejak.map(j => j.judul)).join(' › ');
    kembali.disabled = !d.id;
    if (d.jawaban) isi.appendChild(paragraf(d.jawaban));
    if (d.tautan) {
      const a = el('a', 'btn btn-main asisten-aksi', d.label); a.href = d.tautan;
      if (/^https:/.test(d.tautan)) { a.target = '_blank'; a.rel = 'noopener'; }
      isi.appendChild(a);
    }
    if (d.pertanyaan && d.pilihan.length) isi.appendChild(el('p', 'asisten-tanya', d.pertanyaan));
    else if (d.pertanyaan && !d.id) isi.appendChild(el('p', 'asisten-tanya', d.pertanyaan));
    const daftar = el('div', 'asisten-opsi');
    d.pilihan.forEach((p, i) => {
      const b = el('button', ''); b.type = 'button'; b.dataset.id = p.id;
      b.appendChild(el('i', '', String(i + 1))); b.appendChild(el('span', '', p.judul));
      b.addEventListener('click', () => muat(p.id));
      daftar.appendChild(b);
    });
    const wa = el('a', 'asisten-wa'); wa.href = d.wa; wa.target = '_blank'; wa.rel = 'noopener';
    wa.appendChild(el('i', '', String(d.pilihan.length + 1))); wa.appendChild(el('span', '', 'Tidak ada di pilihan? Tanya petugas lewat WhatsApp'));
    daftar.appendChild(wa);
    isi.appendChild(daftar);
    isi.scrollTop = 0;
    fokus();
  }

  function muat(id) {
    isi.setAttribute('aria-busy', 'true');
    fetch('/asisten?id=' + id)
      .then(r => r.json())
      .then(tampil)
      .catch(() => { isi.innerHTML = ''; isi.appendChild(paragraf('Maaf, asisten belum bisa dimuat. Periksa koneksi internet, atau buka halaman Kontak.')); })
      .finally(() => isi.removeAttribute('aria-busy'));
  }
})();
