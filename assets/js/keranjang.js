// =========================================================
// KERANJANG UMKM: isi keranjang disimpan di browser (localStorage), dikelompokkan per penjual.
// Saat dikirim, server mencatat pesanan lalu menampilkan tombol "Kirim ke WhatsApp penjual".
// =========================================================
(function () {
  const KUNCI = 'mm_keranjang';
  const produk = {};
  document.querySelectorAll('[data-produk]').forEach(el => {
    try { const p = JSON.parse(el.dataset.produk); produk[p.id] = p; } catch (e) {}
  });
  const bar = document.getElementById('keranjangBar');
  const dialog = document.getElementById('keranjangDialog');
  const daftar = document.getElementById('daftarKeranjang');
  const input = document.getElementById('inputKeranjang');
  const ringkas = document.getElementById('keranjangRingkas');
  if (!bar || !dialog) return;

  const baca = () => { try { return JSON.parse(localStorage.getItem(KUNCI) || '{}') || {}; } catch (e) { return {}; } };
  let isi = baca();
  // Buang produk yang sudah tidak ada di katalog
  Object.keys(isi).forEach(id => { if (!produk[id]) delete isi[id]; });
  const simpan = () => { try { localStorage.setItem(KUNCI, JSON.stringify(isi)); } catch (e) {} };
  const rupiah = n => 'Rp' + Number(n).toLocaleString('id-ID');
  const teks = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  function gambar() {
    const ids = Object.keys(isi);
    const jumlah = ids.reduce((a, id) => a + isi[id], 0);
    const total = ids.reduce((a, id) => a + isi[id] * produk[id].harga, 0);
    bar.hidden = jumlah === 0;
    ringkas.textContent = jumlah + ' barang, ' + rupiah(total);
    input.value = JSON.stringify(isi);
    // Kelompokkan per penjual
    const grup = {};
    ids.forEach(id => { (grup[produk[id].usaha] = grup[produk[id].usaha] || []).push(id); });
    let html = '';
    Object.keys(grup).forEach(usaha => {
      html += '<div class="keranjang-grup"><b>' + teks(usaha) + '</b>';
      grup[usaha].forEach(id => {
        const p = produk[id];
        html += '<div class="keranjang-baris"><span>' + teks(p.produk) + '<br><small>' + rupiah(p.harga) + ' / ' + teks(p.satuan) + '</small></span>' +
          '<span class="qty"><button type="button" data-kurang="' + id + '" aria-label="Kurangi ' + teks(p.produk) + '">−</button>' +
          '<output aria-live="polite">' + isi[id] + '</output>' +
          '<button type="button" data-tambah-qty="' + id + '" aria-label="Tambah ' + teks(p.produk) + '">+</button></span>' +
          '<b>' + rupiah(p.harga * isi[id]) + '</b></div>';
      });
      html += '</div>';
    });
    daftar.innerHTML = html ? html + '<p class="keranjang-total">Total <b>' + rupiah(total) + '</b></p>' : '<p class="muted">Keranjang masih kosong.</p>';
    document.querySelectorAll('[data-tambah]').forEach(b => {
      const n = isi[b.dataset.tambah] || 0;
      b.textContent = n ? 'Di keranjang (' + n + ')' : 'Tambah ke keranjang';
    });
  }

  document.addEventListener('click', e => {
    const t = e.target.closest('[data-tambah],[data-tambah-qty],[data-kurang]');
    if (!t) return;
    const id = t.dataset.tambah || t.dataset.tambahQty || t.dataset.kurang;
    if (!produk[id]) return;
    if (t.dataset.kurang) { isi[id] = (isi[id] || 0) - 1; if (isi[id] <= 0) delete isi[id]; }
    else isi[id] = Math.min(99, (isi[id] || 0) + 1);
    simpan(); gambar();
  });
  bar.addEventListener('click', () => { gambar(); dialog.showModal ? dialog.showModal() : dialog.setAttribute('open', ''); });
  dialog.querySelector('[data-tutup]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); });
  document.getElementById('formPesan').addEventListener('submit', e => {
    if (!Object.keys(isi).length) { e.preventDefault(); daftar.insertAdjacentHTML('afterbegin', '<p class="alert alert-err">Pilih produk dulu.</p>'); }
  });
  gambar();
})();
