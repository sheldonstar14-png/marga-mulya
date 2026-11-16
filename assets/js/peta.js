// =========================================================
// PETA: Leaflet + OpenStreetMap (gratis).
// Pakai: <div class="peta" data-titik='[{"lat":..,"lng":..,"nama":"..","jenis":"wisata","url":"/wisata/.."}]'></div>
// =========================================================
(function () {
  if (typeof L === 'undefined') return; // Leaflet gagal dimuat: lewati tanpa merusak halaman lain

  const warna = { wisata: '#E2A529', umkm: '#3F7D3A', fasilitas: '#1E2B22', pilih: '#B3402E' };

  document.querySelectorAll('.peta').forEach(el => {
    let titik = [];
    try { titik = JSON.parse(el.dataset.titik || '[]'); } catch (e) { return; }
    if (!titik.length) return;

    const peta = L.map(el, { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);

    const batas = [];
    const semua = [];
    titik.forEach(t => {
      const penanda = L.circleMarker([t.lat, t.lng], {
        radius: t.jenis === 'pilih' ? 11 : 8,
        color: '#fff', weight: 2.5, fillOpacity: 1,
        fillColor: t.warna || warna[t.jenis] || warna.fasilitas
      }).addTo(peta);
      const isi = document.createElement('div');
      const judul = document.createElement(t.url ? 'a' : 'b');
      judul.textContent = t.nama;
      if (t.url) judul.href = t.url;
      isi.appendChild(judul);
      if (t.ket) { const ket = document.createElement('div'); ket.className = 'small'; ket.textContent = t.ket; isi.appendChild(ket); }
      penanda.bindPopup(isi);
      batas.push([t.lat, t.lng]);
      semua.push({ penanda, kat: t.kat || '' });
    });

    // Tombol saring kategori: <div class="saring-peta" data-peta="idPeta"><button data-kat="">Semua</button>...</div>
    if (el.id) document.querySelectorAll('.saring-peta[data-peta="' + el.id + '"] [data-kat]').forEach(tombol => {
      tombol.addEventListener('click', () => {
        const kat = tombol.dataset.kat;
        tombol.parentElement.querySelectorAll('[data-kat]').forEach(b => { b.classList.toggle('on', b === tombol); b.setAttribute('aria-pressed', b === tombol ? 'true' : 'false'); });
        semua.forEach(m => (kat === '' || m.kat === kat) ? m.penanda.addTo(peta) : m.penanda.remove());
        document.querySelectorAll('[data-kat-item]').forEach(li => { li.hidden = !(kat === '' || li.dataset.katItem === kat); });
      });
    });

    if (batas.length === 1) peta.setView(batas[0], 15);
    else peta.fitBounds(batas, { padding: [30, 30] });
  });
})();
