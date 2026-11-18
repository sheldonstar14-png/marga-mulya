// Foto laporan aspirasi: pratinjau + diperkecil di HP sebelum dikirim.
// Kanvas browser menulis ulang foto, sehingga data lokasi (GPS) di dalam foto ikut hilang
// dan ukuran kiriman tetap di bawah batas server.
(function () {
  const input = document.getElementById('a-foto');
  const kotak = document.getElementById('fotoPratinjau');
  if (!input || !kotak || !window.DataTransfer) return;
  const MAKS = 3, SISI = 1600;

  function perkecil(berkas) {
    return new Promise(selesai => {
      const url = URL.createObjectURL(berkas);
      const img = new Image();
      img.onload = () => {
        const skala = Math.min(1, SISI / Math.max(img.width, img.height));
        const c = document.createElement('canvas');
        c.width = Math.round(img.width * skala); c.height = Math.round(img.height * skala);
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(b => selesai(b ? new File([b], berkas.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : berkas), 'image/jpeg', 0.82);
      };
      img.onerror = () => { URL.revokeObjectURL(url); selesai(berkas); };
      img.src = url;
    });
  }

  input.addEventListener('change', async () => {
    let daftar = Array.from(input.files).filter(f => /^image\//.test(f.type));
    const kelebihan = daftar.length > MAKS;
    daftar = daftar.slice(0, MAKS);
    kotak.textContent = daftar.length ? 'Menyiapkan foto...' : '';
    const kecil = await Promise.all(daftar.map(perkecil));
    const dt = new DataTransfer();
    kecil.forEach(f => dt.items.add(f));
    input.files = dt.files;
    kotak.textContent = '';
    kecil.forEach((f, i) => {
      const fig = document.createElement('figure');
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f); img.alt = 'Pratinjau foto ' + (i + 1);
      const hapus = document.createElement('button');
      hapus.type = 'button'; hapus.textContent = 'Hapus'; hapus.setAttribute('aria-label', 'Hapus foto ' + (i + 1));
      hapus.addEventListener('click', () => {
        const sisa = new DataTransfer();
        Array.from(input.files).forEach((g, j) => { if (j !== i) sisa.items.add(g); });
        input.files = sisa.files; input.dispatchEvent(new Event('ulang'));
      });
      fig.append(img, hapus); kotak.append(fig);
    });
    if (kelebihan) { const p = document.createElement('p'); p.className = 'hint'; p.textContent = 'Hanya 3 foto pertama yang dipakai.'; kotak.append(p); }
  });
  // Setelah satu foto dihapus, gambar ulang pratinjau tanpa memperkecil ulang
  input.addEventListener('ulang', () => {
    const f = Array.from(input.files); kotak.textContent = '';
    f.forEach((g, i) => {
      const fig = document.createElement('figure'); const img = document.createElement('img');
      img.src = URL.createObjectURL(g); img.alt = 'Pratinjau foto ' + (i + 1);
      const hapus = document.createElement('button'); hapus.type = 'button'; hapus.textContent = 'Hapus';
      hapus.addEventListener('click', () => { const s = new DataTransfer(); Array.from(input.files).forEach((h, j) => { if (j !== i) s.items.add(h); }); input.files = s.files; input.dispatchEvent(new Event('ulang')); });
      fig.append(img, hapus); kotak.append(fig);
    });
  });
})();
