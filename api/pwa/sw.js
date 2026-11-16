// =========================================================
// SERVICE WORKER: website tetap bisa dibuka saat sinyal hilang.
// - Halaman yang pernah dibuka disimpan di HP (termasuk tiket reservasi dan nomor darurat).
// - Saat online, selalu ambil versi terbaru dulu (network first).
// - Halaman CMS (/admin) dan form tidak pernah disimpan.
// Naikkan angka VERSI kalau CSS atau JS berubah besar.
// =========================================================
const VERSI = 'mm-v9-1';
const WAJIB = ['/', '/kontak', '/assets/css/style.css', '/assets/js/akses.js', '/assets/img/favicon.svg', '/assets/img/ikon-192.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(VERSI).then(c => Promise.allSettled(WAJIB.map(u => c.add(u)))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(k => Promise.all(k.filter(n => n !== VERSI).map(n => caches.delete(n)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', e => {
  const req = e.request;
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== location.origin) return;
  if (url.pathname.startsWith('/admin') || url.pathname === '/kuota' || url.searchParams.has('ics')) return;

  // Halaman: coba internet dulu, kalau gagal pakai salinan tersimpan
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).then(res => {
        if (res.ok) { const salin = res.clone(); caches.open(VERSI).then(c => c.put(req, salin)); }
        return res;
      }).catch(() => caches.match(req).then(r => r || caches.match('/kontak')).then(r => r || new Response(
        '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Sedang offline</title>' +
        '<body style="font-family:sans-serif;padding:24px;line-height:1.5"><h1>Kamu sedang offline</h1>' +
        '<p>Halaman ini belum pernah dibuka saat online. Dalam keadaan darurat, telepon <a href="tel:112"><b>112</b></a>.</p></body>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } })))
    );
    return;
  }

  // CSS, JS, gambar: pakai yang tersimpan, sambil memperbarui di belakang layar
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith(caches.match(req).then(simpan => {
      const baru = fetch(req).then(res => { if (res.ok) { const salin = res.clone(); caches.open(VERSI).then(c => c.put(req, salin)); } return res; }).catch(() => simpan);
      return simpan || baru;
    }));
  }
});
