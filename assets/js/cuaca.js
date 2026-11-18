// =========================================================
// CUACA: prakiraan dari Open-Meteo (gratis, tanpa API key).
// Dipakai di Beranda, Detail Wisata, dan Reservasi.
// =========================================================
(function () {
  const cache = {};

  // Ambil prakiraan 16 hari untuk satu titik koordinat
  window.ambilCuaca = function (lat, lng) {
    const kunci = lat + ',' + lng;
    if (!cache[kunci]) {
      const url = 'https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lng +
        '&current=temperature_2m,weather_code' +
        '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum' +
        '&timezone=Asia%2FJakarta&forecast_days=16';
      cache[kunci] = fetch(url)
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(j => ({
          sekarang: { suhu: Math.round(j.current.temperature_2m), kode: j.current.weather_code },
          hari: j.daily.time.map((t, i) => ({
            tgl: t,
            kode: j.daily.weather_code[i],
            maks: Math.round(j.daily.temperature_2m_max[i]),
            min: Math.round(j.daily.temperature_2m_min[i]),
            hujan: j.daily.precipitation_probability_max[i] ?? 0,
            mm: (j.daily.precipitation_sum || [])[i] ?? 0
          }))
        }))
        .catch(err => { delete cache[kunci]; throw err; });
    }
    return cache[kunci];
  };

  // Kode cuaca WMO -> nama dan jenis ikon
  window.infoCuaca = function (kode) {
    if (kode === 0) return ['Cerah', 'cerah'];
    if (kode <= 3) return ['Berawan', 'awan'];
    if (kode <= 48) return ['Berkabut', 'kabut'];
    if (kode <= 57) return ['Gerimis', 'hujan'];
    if (kode <= 67) return ['Hujan', 'hujan'];
    if (kode <= 82) return ['Hujan lokal', 'hujan'];
    return ['Badai petir', 'badai'];
  };

  window.ikonCuaca = function (jenis) {
    const g = {
      cerah: '<circle cx="16" cy="16" r="7" fill="#E8641E"/><g stroke="#E8641E" stroke-width="2.4" stroke-linecap="round"><path d="M16 2v4M16 26v4M2 16h4M26 16h4M6 6l3 3M23 23l3 3M26 6l-3 3M9 23l-3 3"/></g>',
      awan: '<circle cx="11" cy="12" r="5" fill="#E8641E"/><path d="M9 25h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#9AA9A0"/>',
      kabut: '<g stroke="#9AA9A0" stroke-width="2.6" stroke-linecap="round"><path d="M5 11h22M8 16h18M5 21h22M10 26h14"/></g>',
      hujan: '<path d="M8 19h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#7F938A"/><g stroke="#1F6F8B" stroke-width="2.4" stroke-linecap="round"><path d="M11 23l-2 5M17 23l-2 5M23 23l-2 5"/></g>',
      badai: '<path d="M8 17h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#5E6E66"/><path d="M17 18l-4 7h4l-2 6 7-9h-4l2-4z" fill="#E8641E"/>'
    };
    return '<svg class="ic" viewBox="0 0 32 32" aria-hidden="true">' + g[jenis] + '</svg>';
  };

  // ---------- Saran sesuai cuaca ----------
  // window.WISATA_HUJAN diisi footer dari CMS: destinasi yang dicentang "Tetap nyaman saat hujan".
  const tempatHujan = () => window.WISATA_HUJAN || [];
  const tautanHujan = kecuali => {
    const daftar = tempatHujan().filter(w => w.nama !== kecuali)
      .map(w => '<a href="' + w.url + '">' + w.nama.replace(/</g, '') + '</a>');
    daftar.push('<a href="/umkm">belanja oleh-oleh di UMKM</a>');
    return daftar.join(', ');
  };
  // Tingkat hujan sehari: 0 aman, 1 gerimis/ringan, 2 hujan, 3 lebat atau petir
  window.tingkatHujan = h => {
    if (h.kode >= 95 || h.mm >= 20) return 3;
    if (h.hujan >= 60 && h.mm >= 5) return 2;
    if (h.hujan >= 30) return 1;
    return 0;
  };
  // h = satu hari dari ambilCuaca(); jenis dan nama = destinasi (kosong untuk panel desa)
  window.saranCuaca = function (h, jenis, nama) {
    const t = tingkatHujan(h);
    const air = /pantai|mangrove|laut|pesisir/i.test(nama || '');
    const dalamRuang = tempatHujan().some(w => w.nama === nama) || jenis === 'Budaya';
    if (dalamRuang) return t >= 2 ? 'Hujan diperkirakan turun, tapi kegiatan di sini berlangsung di dalam ruangan. Tetap bisa berkunjung.' : 'Cuaca mendukung. Kegiatan berlangsung di dalam ruangan.';
    if (t === 0) return air ? 'Cuaca bersahabat untuk ke pesisir. Tetap cek jadwal air pasang.' : 'Cocok untuk jalan kaki dan berfoto di luar ruangan.';
    if (t === 1) return 'Mungkin gerimis, biasanya siang atau sore. Datang pagi dan bawa payung' + (air ? ', dan hindari bermain air kalau angin mulai kencang.' : '.');
    const pilihan = 'Pilihan yang tetap nyaman saat hujan: ' + tautanHujan(nama) + '.';
    if (t === 3) return (air ? 'Hujan lebat diperkirakan turun. Ombak dan air pasang bisa tinggi, kunjungan ke pantai dan mangrove sebaiknya ditunda. ' : 'Hujan lebat atau petir diperkirakan turun. Hindari sawah terbuka dan pesisir. ') + pilihan;
    return 'Hujan diperkirakan turun, biasanya mulai siang. ' + (air ? 'Pesisir bisa ditutup saat angin kencang, jadi cek kabar di WhatsApp desa sebelum berangkat. ' : 'Kalau tetap ke luar ruangan, datang pagi. ') + pilihan;
  };

  const namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
  const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const hariPanjang = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const keTanggal = s => new Date(s + 'T00:00:00');
  window.tglPanjang = s => { const d = keTanggal(s); return hariPanjang[d.getDay()] + ', ' + d.getDate() + ' ' + namaBulan[d.getMonth()]; };

  // Hari dengan peluang hujan terkecil dalam 7 hari (mulai besok)
  // (tingkat hujan paling rendah; kalau sama, curah hujan paling sedikit)
  window.hariTerbaik = hari => hari.slice(1, 7).reduce((a, b) => {
    const ta = tingkatHujan(a), tb = tingkatHujan(b);
    return tb < ta || (tb === ta && (b.mm < a.mm || (b.mm === a.mm && b.hujan < a.hujan))) ? b : a;
  });

  // Panel 7 hari: <div data-cuaca data-lat data-lng data-jenis data-nama data-tombol="idTombol">
  function isiPanel(el) {
    const lat = el.dataset.lat, lng = el.dataset.lng;
    ambilCuaca(lat, lng).then(data => {
      const terbaik = hariTerbaik(data.hari);
      const kotak = data.hari.slice(0, 7).map((d, i) => {
        const [nama, jenis] = infoCuaca(d.kode);
        const label = i === 0 ? 'Hari ini' : namaHari[keTanggal(d.tgl).getDay()];
        return '<div class="wx-day' + (d.tgl === terbaik.tgl ? ' best' : '') + '" title="' + nama + ', ' + d.min + '-' + d.maks + '°C, peluang hujan ' + d.hujan + '%, ' + Math.round(d.mm) + ' mm">' +
          '<b>' + label + '</b>' + ikonCuaca(jenis) + d.maks + '°<span class="rain"><span class="sr-only">peluang hujan </span>' + d.hujan + '%</span></div>';
      }).join('');
      const [namaKini, jenisKini] = infoCuaca(data.sekarang.kode);
      el.innerHTML =
        '<div class="wx-top"><div><h3 style="margin:0">' + (el.dataset.judul || 'Cuaca 7 hari ke depan') + '</h3>' +
        '<span class="small muted">' + (el.dataset.sub || '') + '</span></div>' +
        '<div class="wx-now">' + ikonCuaca(jenisKini) + '<div><b>' + data.sekarang.suhu + '°</b><div class="small">' + namaKini + '</div></div></div></div>' +
        '<div class="wx-days">' + kotak + '</div>' +
        '<p class="wx-note">' + (tingkatHujan(terbaik) >= 2 ? 'Minggu ini hujan hampir setiap hari. Hari paling ringan: <b>' : 'Hari terbaik berkunjung: <b>') + tglPanjang(terbaik.tgl) + '</b> (peluang hujan ' + terbaik.hujan + '%, sekitar ' + Math.round(terbaik.mm) + ' mm). ' +
        saranCuaca(terbaik, el.dataset.jenis, el.dataset.nama) + '</p>' +
        '<div class="wx-src">Angka biru = peluang hujan. Kotak hijau = hari paling cocok. Sumber: Open-Meteo.</div>';
      // Tombol "Pesan" ikut diarahkan ke tanggal terbaik
      const tombol = el.dataset.tombol && document.getElementById(el.dataset.tombol);
      if (tombol) {
        tombol.href = tombol.href.split('&t=')[0] + '&t=' + terbaik.tgl;
        tombol.textContent = 'Pesan untuk ' + tglPanjang(terbaik.tgl);
      }
    }).catch(() => {
      el.querySelector('.wx-note').textContent = 'Prakiraan cuaca belum bisa dimuat. Periksa koneksi internet lalu muat ulang halaman.';
    });
  }

  document.querySelectorAll('[data-cuaca]').forEach(isiPanel);

  // ---------- Siaga cuaca (Fase 8) ----------
  // Peringatan untuk hari ini sampai 2 hari ke depan. Ambang mengikuti kategori BMKG:
  // hujan lebat 50 sampai 100 mm per hari, sangat lebat di atas 100 mm per hari.
  window.siagaCuaca = function (hari) {
    return hari.slice(0, 3).map(d => {
      let tingkat = null, teks = '';
      if (d.mm >= 100) { tingkat = 'awas'; teks = 'hujan sangat lebat (sekitar ' + Math.round(d.mm) + ' mm)'; }
      else if (d.mm >= 50) { tingkat = 'siaga'; teks = 'hujan lebat (sekitar ' + Math.round(d.mm) + ' mm)'; }
      else if (d.kode >= 95) { tingkat = 'siaga'; teks = 'badai petir'; }
      return tingkat ? { tgl: d.tgl, tingkat, teks } : null;
    }).filter(Boolean);
  };

  document.querySelectorAll('[data-siaga]').forEach(el => {
    ambilCuaca(el.dataset.lat, el.dataset.lng).then(data => {
      const daftar = siagaCuaca(data.hari);
      document.dispatchEvent(new CustomEvent('siaga-cuaca', { detail: daftar })); // peta desa ikut menyorot zona rawan
      el.hidden = false;
      if (!daftar.length) {
        el.className = 'siaga aman';
        el.innerHTML = '<b>Tidak ada peringatan cuaca</b> untuk 3 hari ke depan. Nomor darurat desa ada di <a href="/kontak#darurat">halaman Kontak</a>.';
        return;
      }
      const awas = daftar.some(d => d.tingkat === 'awas');
      el.className = 'siaga ' + (awas ? 'awas' : 'waspada');
      el.setAttribute('role', 'alert');
      el.innerHTML = '<b>Siaga cuaca:</b> ' + daftar.map(d => tglPanjang(d.tgl) + ' berpotensi ' + d.teks).join('; ') +
        '. Hindari pantai, tambak, dan saluran sawah saat hujan deras atau air pasang. <a href="/profil#peta-desa">Lihat zona rawan</a> atau <a href="/kontak#darurat">nomor darurat</a>.';
    }).catch(() => { el.hidden = true; });
  });
})();
