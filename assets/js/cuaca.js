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
        '&current=temperature_2m,weather_code,wind_speed_10m,relative_humidity_2m' +
        '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum,wind_speed_10m_max,wind_gusts_10m_max' +
        '&timezone=Asia%2FJakarta&forecast_days=16';
      cache[kunci] = fetch(url)
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(j => ({
          sekarang: { suhu: Math.round(j.current.temperature_2m), kode: j.current.weather_code, angin: Math.round(j.current.wind_speed_10m || 0), lembap: Math.round(j.current.relative_humidity_2m || 0) },
          hari: j.daily.time.map((t, i) => ({
            tgl: t,
            kode: j.daily.weather_code[i],
            maks: Math.round(j.daily.temperature_2m_max[i]),
            min: Math.round(j.daily.temperature_2m_min[i]),
            hujan: j.daily.precipitation_probability_max[i] ?? 0,
            mm: (j.daily.precipitation_sum || [])[i] ?? 0,
            angin: Math.round((j.daily.wind_speed_10m_max || [])[i] ?? 0),
            hembus: Math.round((j.daily.wind_gusts_10m_max || [])[i] ?? 0)
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
      cerah: '<circle cx="16" cy="16" r="7" fill="#F08A5D"/><g stroke="#F08A5D" stroke-width="2.4" stroke-linecap="round"><path d="M16 2v4M16 26v4M2 16h4M26 16h4M6 6l3 3M23 23l3 3M26 6l-3 3M9 23l-3 3"/></g>',
      awan: '<circle cx="11" cy="12" r="5" fill="#F08A5D"/><path d="M9 25h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#9AA9A0"/>',
      kabut: '<g stroke="#9AA9A0" stroke-width="2.6" stroke-linecap="round"><path d="M5 11h22M8 16h18M5 21h22M10 26h14"/></g>',
      hujan: '<path d="M8 19h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#7F938A"/><g stroke="#1F6F8B" stroke-width="2.4" stroke-linecap="round"><path d="M11 23l-2 5M17 23l-2 5M23 23l-2 5"/></g>',
      badai: '<path d="M8 17h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#5E6E66"/><path d="M17 18l-4 7h4l-2 6 7-9h-4l2-4z" fill="#F08A5D"/>'
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

  // ---------- Fase 12s: cuaca laut (gelombang) untuk nelayan, saran untuk petani ----------
  const cacheLaut = {};
  window.ambilLaut = function (lat, lng) {
    const kunci = lat + ',' + lng;
    if (!cacheLaut[kunci]) {
      const url = 'https://marine-api.open-meteo.com/v1/marine?latitude=' + lat + '&longitude=' + lng +
        '&current=wave_height&daily=wave_height_max,wave_period_max&timezone=Asia%2FJakarta&forecast_days=4';
      cacheLaut[kunci] = fetch(url)
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(j => {
          if (!j.daily || j.daily.wave_height_max.every(v => v === null)) throw new Error('tidak ada data laut');
          return {
            sekarang: j.current && j.current.wave_height !== null ? j.current.wave_height : j.daily.wave_height_max[0],
            hari: j.daily.time.map((t, i) => ({ tgl: t, maks: j.daily.wave_height_max[i] ?? 0, periode: Math.round(j.daily.wave_period_max[i] ?? 0) }))
          };
        })
        .catch(err => { delete cacheLaut[kunci]; throw err; });
    }
    return cacheLaut[kunci];
  };
  const koma = n => String(Math.round(n * 10) / 10).replace('.', ',');
  // Status perahu kecil dari tinggi gelombang (meter) dan angin (km/jam)
  window.statusLaut = function (m, angin) {
    if (m >= 2.5 || angin >= 40) return ['bahaya', 'Perahu kecil sebaiknya tidak melaut', 'Gelombang tinggi. Tambatkan perahu lebih jauh dari bibir pantai dan jauhi tepi pantai.'];
    if (m >= 1.25 || angin >= 28) return ['waspada', 'Berbahaya untuk perahu motor tempel', 'Kalau tetap melaut, jangan jauh dari pantai, pakai pelampung, dan kabari keluarga jam pulang.'];
    return ['aman', 'Aman untuk perahu kecil', 'Tetap bawa pelampung untuk semua awak dan HP di kantong kedap air.'];
  };
  // Saran petani untuk satu hari dari ambilCuaca()
  window.saranTani = function (h) {
    const t = tingkatHujan(h), saran = [];
    let status = 'aman';
    if (t === 0) saran.push('Cocok menjemur gabah dan kopra sepanjang hari');
    else if (t === 1) saran.push('Jemur gabah pagi, angkat sebelum siang');
    else { saran.push('Tunda menjemur dan memupuk, pupuk bisa larut terbawa hujan'); status = 'waspada'; }
    if (h.angin >= 20 || t >= 2) saran.push('jangan menyemprot pestisida');
    else saran.push('menyemprot paling baik pukul 07.00 sampai 10.00');
    if (h.mm >= 50 || h.kode >= 95) { saran.push('periksa saluran sawah dan tanggul tambak'); status = 'bahaya'; }
    const kal = saran.join(', ') + '.';
    return [status, kal.charAt(0).toUpperCase() + kal.slice(1)];
  };
  const hariLabel = (s, i) => i === 0 ? 'Hari ini' : i === 1 ? 'Besok' : tglPanjang(s).split(',')[0];

  // Kartu cuaca di beranda: darat dan laut
  document.querySelectorAll('[data-info-cuaca]').forEach(el => {
    const lat = el.dataset.lat, lng = el.dataset.lng, darat = el.querySelector('[data-darat]'), laut = el.querySelector('[data-laut]');
    const isi = (blok, judul, ket, kelas) => {
      const j = blok.querySelector('[data-isi="judul"]'), k = blok.querySelector('[data-isi="ket"]');
      j.textContent = judul; k.textContent = ket; if (kelas) j.className = 'status-' + kelas;
    };
    ambilCuaca(lat, lng).then(d => {
      const [nama] = infoCuaca(d.sekarang.kode), h = d.hari[0];
      isi(darat, nama + ', ' + d.sekarang.suhu + '°C', 'Hujan ' + h.hujan + '%, angin ' + h.angin + ' km/jam. ' + saranTani(h)[1].split(',')[0] + '.');
    }).catch(() => isi(darat, 'Belum bisa dimuat', 'Periksa sinyal internet, lalu muat ulang.'));
    Promise.all([ambilLaut(Number(lat) + 0.05, lng), ambilCuaca(Number(lat) + 0.05, lng).catch(() => null)]).then(([l, c]) => {
      const angin = c ? c.hari[0].angin : 0, [kelas, status] = statusLaut(l.hari[0].maks, angin);
      isi(laut, 'Gelombang ' + koma(l.sekarang) + ' m, maks ' + koma(l.hari[0].maks) + ' m', status + (angin ? ', angin ' + angin + ' km/jam' : '') + '.', kelas);
    }).catch(() => isi(laut, 'Belum bisa dimuat', 'Cek juga kabar BMKG Maritim.'));
  });

  // Halaman /cuaca: 4 hari darat dan laut
  const ikonStatus = { aman: '✓', waspada: '!', bahaya: '✕' };
  document.querySelectorAll('[data-cuaca-darat]').forEach(el => {
    ambilCuaca(el.dataset.lat, el.dataset.lng).then(d => {
      el.innerHTML = d.hari.slice(0, 4).map((h, i) => {
        const [nama, jenis] = infoCuaca(h.kode), [st, saran] = saranTani(h);
        return '<article class="hari-kartu st-' + st + '"><h3>' + hariLabel(h.tgl, i) + '<small>' + tglPanjang(h.tgl).split(', ')[1] + '</small></h3>' +
          '<div class="hari-atas">' + ikonCuaca(jenis) + '<div><b>' + h.min + '–' + h.maks + '°C</b><span>' + nama + '</span></div></div>' +
          '<dl><div><dt>Peluang hujan</dt><dd>' + h.hujan + '%</dd></div><div><dt>Curah hujan</dt><dd>' + koma(h.mm) + ' mm</dd></div><div><dt>Angin</dt><dd>' + h.angin + ' km/jam</dd></div></dl>' +
          '<p class="hari-saran"><i aria-hidden="true">' + ikonStatus[st] + '</i><span>' + saran + '</span></p></article>';
      }).join('');
    }).catch(() => { el.innerHTML = '<p class="muted">Prakiraan cuaca darat belum bisa dimuat. Periksa sinyal internet, lalu muat ulang halaman.</p>'; });
  });
  document.querySelectorAll('[data-cuaca-laut]').forEach(el => {
    Promise.all([ambilLaut(el.dataset.lat, el.dataset.lng), ambilCuaca(el.dataset.lat, el.dataset.lng).catch(() => null)]).then(([l, c]) => {
      el.innerHTML = l.hari.slice(0, 4).map((h, i) => {
        const angin = c && c.hari[i] ? c.hari[i].angin : 0, hembus = c && c.hari[i] ? c.hari[i].hembus : 0;
        const [st, judul, saran] = statusLaut(h.maks, angin);
        return '<article class="hari-kartu st-' + st + '"><h3>' + hariLabel(h.tgl, i) + '<small>' + tglPanjang(h.tgl).split(', ')[1] + '</small></h3>' +
          '<div class="hari-atas"><svg class="ic" viewBox="0 0 32 32" aria-hidden="true"><path d="M2 13c4-3 8-3 12 0s8 3 12 0 4-2 4-2M2 21c4-3 8-3 12 0s8 3 12 0 4-2 4-2" stroke="#1E8C93" stroke-width="2.6" fill="none" stroke-linecap="round"/></svg><div><b>' + koma(h.maks) + ' m</b><span>gelombang tertinggi</span></div></div>' +
          '<dl>' + (angin ? '<div><dt>Angin laut</dt><dd>' + angin + ' km/jam</dd></div><div><dt>Hembusan</dt><dd>' + hembus + ' km/jam</dd></div>' : '') + '<div><dt>Periode ombak</dt><dd>' + h.periode + ' detik</dd></div></dl>' +
          '<p class="hari-saran"><i aria-hidden="true">' + ikonStatus[st] + '</i><span><b>' + judul + '.</b> ' + saran + '</span></p></article>';
      }).join('');
    }).catch(() => { el.innerHTML = '<p class="muted">Prakiraan gelombang belum bisa dimuat. Periksa sinyal internet, atau lihat <a href="https://maritim.bmkg.go.id" target="_blank" rel="noopener">BMKG Maritim</a>.</p>'; });
  });
})();
