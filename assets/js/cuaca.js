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
        '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max' +
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
            hujan: j.daily.precipitation_probability_max[i] ?? 0
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
      cerah: '<circle cx="16" cy="16" r="7" fill="#E2A529"/><g stroke="#E2A529" stroke-width="2.4" stroke-linecap="round"><path d="M16 2v4M16 26v4M2 16h4M26 16h4M6 6l3 3M23 23l3 3M26 6l-3 3M9 23l-3 3"/></g>',
      awan: '<circle cx="11" cy="12" r="5" fill="#E2A529"/><path d="M9 25h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#9AA9A0"/>',
      kabut: '<g stroke="#9AA9A0" stroke-width="2.6" stroke-linecap="round"><path d="M5 11h22M8 16h18M5 21h22M10 26h14"/></g>',
      hujan: '<path d="M8 19h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#7F938A"/><g stroke="#2F6E95" stroke-width="2.4" stroke-linecap="round"><path d="M11 23l-2 5M17 23l-2 5M23 23l-2 5"/></g>',
      badai: '<path d="M8 17h15a5 5 0 0 0 0-10 7 7 0 0 0-13 1 4.5 4.5 0 0 0-2 9z" fill="#5E6E66"/><path d="M17 18l-4 7h4l-2 6 7-9h-4l2-4z" fill="#E2A529"/>'
    };
    return '<svg class="ic" viewBox="0 0 32 32" aria-hidden="true">' + g[jenis] + '</svg>';
  };

  // Saran sesuai peluang hujan dan jenis destinasi
  window.saranCuaca = function (hujan, jenis, nama) {
    const air = /curug|air terjun/i.test(nama || '');
    if (hujan < 30) return air ? 'Cuaca bersahabat, jalur curug aman dilalui.' : 'Cocok untuk jalan kaki dan berfoto di luar ruangan.';
    if (hujan < 60) return 'Ada kemungkinan hujan siang atau sore. Datang pagi dan bawa jas hujan.';
    if (jenis === 'Budaya') return 'Peluang hujan tinggi, tapi kegiatan berlangsung di dalam ruangan.';
    return air ? 'Peluang hujan tinggi. Jalur curug bisa ditutup, sebaiknya pilih tanggal lain.' : 'Peluang hujan tinggi. Bawa jas hujan atau pilih tanggal lain.';
  };

  const namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
  const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const hariPanjang = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const keTanggal = s => new Date(s + 'T00:00:00');
  window.tglPanjang = s => { const d = keTanggal(s); return hariPanjang[d.getDay()] + ', ' + d.getDate() + ' ' + namaBulan[d.getMonth()]; };

  // Hari dengan peluang hujan terkecil dalam 7 hari (mulai besok)
  window.hariTerbaik = hari => hari.slice(1, 8).reduce((a, b) => (b.hujan < a.hujan ? b : a));

  // Panel 7 hari: <div data-cuaca data-lat data-lng data-jenis data-nama data-tombol="idTombol">
  function isiPanel(el) {
    const lat = el.dataset.lat, lng = el.dataset.lng;
    ambilCuaca(lat, lng).then(data => {
      const terbaik = hariTerbaik(data.hari);
      const kotak = data.hari.slice(0, 7).map((d, i) => {
        const [nama, jenis] = infoCuaca(d.kode);
        const label = i === 0 ? 'Hari ini' : namaHari[keTanggal(d.tgl).getDay()];
        return '<div class="wx-day' + (d.tgl === terbaik.tgl ? ' best' : '') + '" title="' + nama + ', ' + d.min + '-' + d.maks + '°C, peluang hujan ' + d.hujan + '%">' +
          '<b>' + label + '</b>' + ikonCuaca(jenis) + d.maks + '°<span class="rain">' + d.hujan + '%</span></div>';
      }).join('');
      const [namaKini, jenisKini] = infoCuaca(data.sekarang.kode);
      el.innerHTML =
        '<div class="wx-top"><div><h3 style="margin:0">' + (el.dataset.judul || 'Cuaca 7 hari ke depan') + '</h3>' +
        '<span class="small muted">' + (el.dataset.sub || '') + '</span></div>' +
        '<div class="wx-now">' + ikonCuaca(jenisKini) + '<div><b>' + data.sekarang.suhu + '°</b><div class="small">' + namaKini + '</div></div></div></div>' +
        '<div class="wx-days">' + kotak + '</div>' +
        '<p class="wx-note">Hari terbaik berkunjung: <b>' + tglPanjang(terbaik.tgl) + '</b>, peluang hujan ' + terbaik.hujan + '%. ' +
        saranCuaca(terbaik.hujan, el.dataset.jenis, el.dataset.nama) + '</p>' +
        '<div class="wx-src">Sumber: Open-Meteo. Kotak hijau menandai hari terbaik.</div>';
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
})();
