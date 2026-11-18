// =========================================================
// PETA: Leaflet + OpenStreetMap (gratis).
// Pakai: <div class="peta" data-titik='[{"lat":..,"lng":..,"nama":"..","jenis":"wisata","url":"/wisata/..","radius":150}]'></div>
// Titik yang punya "radius" digambar sebagai lingkaran area (zona rawan bencana).
// =========================================================
(function () {
  if (typeof L === 'undefined') return; // Leaflet gagal dimuat: lewati tanpa merusak halaman lain

  const warna = { wisata: '#F08A5D', umkm: '#15607F', fasilitas: '#1F6F8B', pilih: '#B3261E' };

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
      const isiWarna = t.warna || warna[t.jenis] || warna.fasilitas;
      const penanda = t.radius
        ? L.circle([t.lat, t.lng], { radius: t.radius, color: isiWarna, weight: 2, dashArray: '6 6', fillColor: isiWarna, fillOpacity: 0.18 }).addTo(peta)
        : L.circleMarker([t.lat, t.lng], { radius: t.jenis === 'pilih' ? 11 : 8, color: '#fff', weight: 2.5, fillOpacity: 1, fillColor: isiWarna }).addTo(peta);
      const isi = document.createElement('div');
      const judul = document.createElement(t.url ? 'a' : 'b');
      judul.textContent = t.nama;
      if (t.url) judul.href = t.url;
      isi.appendChild(judul);
      if (t.ket) { const ket = document.createElement('div'); ket.className = 'small'; ket.textContent = t.ket; isi.appendChild(ket); }
      penanda.bindPopup(isi);
      batas.push([t.lat, t.lng]);
      semua.push({ penanda, kat: t.kat || '', zona: !!t.radius });
    });

    // Saring satu kategori: <div class="saring-peta" data-peta="idPeta"><button data-kat="">Semua</button>...</div>
    if (el.id) document.querySelectorAll('.saring-peta[data-peta="' + el.id + '"] [data-kat]').forEach(tombol => {
      tombol.addEventListener('click', () => {
        const kat = tombol.dataset.kat;
        tombol.parentElement.querySelectorAll('[data-kat]').forEach(b => { b.classList.toggle('on', b === tombol); b.setAttribute('aria-pressed', b === tombol ? 'true' : 'false'); });
        semua.forEach(m => (kat === '' || m.kat === kat) ? m.penanda.addTo(peta) : m.penanda.remove());
        document.querySelectorAll('[data-kat-item]').forEach(li => { li.hidden = !(kat === '' || li.dataset.katItem === kat); });
      });
    });

    // Lapisan (bisa beberapa sekaligus): <div class="lapisan-peta" data-peta="idPeta"><button data-lapis="wisata">..</button></div>
    const tombolLapis = el.id ? document.querySelectorAll('.lapisan-peta[data-peta="' + el.id + '"] [data-lapis]') : [];
    const aturLapis = (lapis, nyala) => {
      tombolLapis.forEach(b => { if (b.dataset.lapis === lapis) { b.classList.toggle('on', nyala); b.setAttribute('aria-pressed', nyala ? 'true' : 'false'); } });
      semua.forEach(m => { if (m.kat === lapis) nyala ? m.penanda.addTo(peta) : m.penanda.remove(); });
      document.querySelectorAll('[data-lapis-item="' + lapis + '"]').forEach(s => { s.hidden = !nyala; });
    };
    tombolLapis.forEach(b => b.addEventListener('click', () => aturLapis(b.dataset.lapis, b.getAttribute('aria-pressed') !== 'true')));

    // Terhubung dengan siaga cuaca: saat ada peringatan, zona rawan dinyalakan dan disorot
    document.addEventListener('siaga-cuaca', ev => {
      if (!ev.detail || !ev.detail.length) return;
      aturLapis('rawan', true);
      semua.forEach(m => { if (m.zona) m.penanda.setStyle({ weight: 3.5, fillOpacity: 0.35, dashArray: null }); });
    });

    if (batas.length === 1) peta.setView(batas[0], 15);
    else peta.fitBounds(batas, { padding: [30, 30] });
  });
})();
