// =========================================================
// SERVICE WORKER: website tetap bisa dibuka saat sinyal hilang (Fase 12s).
// - Saat dipasang, halaman penting langsung disimpan: beranda, layanan surat, kontak dan
//   nomor darurat, wisata, UMKM, profil, cuaca, dan kabar desa.
// - Halaman lain yang pernah dibuka ikut disimpan. Saat online, selalu ambil versi terbaru dulu.
// - Jawaban Asisten Desa dan prakiraan cuaca terakhir juga disimpan, jadi tetap tampil tanpa sinyal.
// - Halaman CMS (/admin) dan kiriman form tidak pernah disimpan.
// Naikkan angka VERSI kalau CSS atau JS berubah besar.
// =========================================================
const VERSI = 'mm-v12s-1';
const HALAMAN = ['/', '/kontak', '/data-desa?grup=Layanan+surat', '/data-desa?grup=Pantau+bansos', '/wisata', '/umkm', '/profil', '/cuaca', '/agenda'];
const ASET = ['/assets/css/style.css?v=12s', '/assets/js/akses.js?v=12s', '/assets/js/asisten.js?v=12s', '/assets/js/cuaca.js?v=12s',
  '/assets/img/favicon.png', '/assets/img/ikon-192.png', '/assets/img/logo-mark.png?v=2', '/assets/img/panorama.svg', '/assets/img/kades.svg', '/asisten'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(VERSI).then(c => Promise.allSettled([...HALAMAN, ...ASET].map(u => c.add(u)))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(k => Promise.all(k.filter(n => n !== VERSI).map(n => caches.delete(n)))).then(() => self.clients.claim()));
});

const simpan = (req, res) => {
  // Saat mode privat aktif (header noindex), halaman tidak disimpan di HP
  if (res.ok && !(res.headers.get('X-Robots-Tag') || '').includes('noindex')) { const salin = res.clone(); caches.open(VERSI).then(c => c.put(req, salin)); }
  return res;
};

// Halaman cadangan: daftar halaman yang tersimpan di HP ini
async function halamanOffline() {
  const c = await caches.open(VERSI);
  const nama = { '/': 'Beranda', '/kontak': 'Kontak dan nomor darurat', '/wisata': 'Wisata dan budaya', '/umkm': 'Produk UMKM', '/profil': 'Profil desa', '/cuaca': 'Cuaca darat dan laut', '/agenda': 'Kabar desa dan agenda' };
  const daftar = (await c.keys()).map(r => new URL(r.url)).filter(u => u.origin === location.origin && !u.pathname.startsWith('/assets') && u.pathname !== '/asisten')
    .map(u => '<li><a href="' + u.pathname + u.search + '">' + (nama[u.pathname + u.search] || (u.searchParams.get('grup') ? 'Informasi desa: ' + u.searchParams.get('grup') : u.pathname)) + '</a></li>').join('');
  return new Response('<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sedang offline</title>' +
    '<body style="font-family:system-ui,sans-serif;padding:24px;line-height:1.6;color:#132430;max-width:640px;margin:auto"><h1 style="color:#0B3C5D">Kamu sedang offline</h1>' +
    '<p>Halaman ini belum tersimpan di HP. Dalam keadaan darurat, telepon <a href="tel:112"><b>112</b></a> (bebas pulsa).</p>' +
    '<h2 style="font-size:1.1rem">Halaman yang bisa dibuka tanpa sinyal</h2><ul>' + daftar + '</ul></body></html>',
    { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

self.addEventListener('fetch', e => {
  const req = e.request;
  const url = new URL(req.url);
  if (req.method !== 'GET') return;

  // Prakiraan cuaca dan gelombang: ambil terbaru, kalau gagal pakai salinan terakhir
  if (/(^|\.)open-meteo\.com$/.test(url.hostname)) {
    e.respondWith(fetch(req).then(res => simpan(req, res)).catch(() => caches.match(req)));
    return;
  }
  if (url.origin !== location.origin) return;
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/foto') || url.searchParams.has('ics')) return;

  // Halaman: coba internet dulu, kalau gagal pakai salinan tersimpan
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).then(res => simpan(req, res))
        .catch(() => caches.match(req).then(r => r || caches.match(req, { ignoreSearch: true })).then(r => r || halamanOffline()))
    );
    return;
  }

  // Jawaban Asisten Desa (JSON): terbaru dulu, salinan saat offline
  if (url.pathname === '/asisten') {
    e.respondWith(fetch(req).then(res => simpan(req, res)).catch(() => caches.match(req)));
    return;
  }

  // CSS, JS, gambar: pakai yang tersimpan, sambil memperbarui di belakang layar
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith(caches.match(req).then(tersimpan => {
      const baru = fetch(req).then(res => simpan(req, res)).catch(() => tersimpan);
      return tersimpan || baru;
    }));
  }
});
