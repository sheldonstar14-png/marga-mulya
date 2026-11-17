// =========================================================
// PWA: tombol pasang aplikasi (di footer), simpan halaman untuk dibuka tanpa sinyal,
// dan penanda saat offline.
// Catatan Fase 12: mode mudah (huruf besar, kontras, Dengarkan) dihapus. Aksesibilitas kini
// bawaan di semua halaman: huruf 18px, kontras AA, area sentuh 48px, dan ramah pembaca layar
// HP (TalkBack/VoiceOver) yang sudah bisa membacakan halaman tanpa tombol tambahan.
// =========================================================
(function () {
  // Pasang sebagai aplikasi (muncul kalau browser mendukung)
  let tawaran = null;
  const tombolPasang = document.getElementById('pasangBtn');
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault(); tawaran = e;
    if (tombolPasang) tombolPasang.hidden = false;
  });
  if (tombolPasang) tombolPasang.addEventListener('click', async () => {
    if (!tawaran) return;
    tawaran.prompt(); await tawaran.userChoice; tawaran = null; tombolPasang.hidden = true;
  });

  // Simpan halaman untuk dibuka tanpa sinyal
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }

  // Penanda offline
  const penanda = document.getElementById('penandaOffline');
  const aturOffline = () => { if (penanda) penanda.hidden = navigator.onLine; };
  window.addEventListener('online', aturOffline); window.addEventListener('offline', aturOffline); aturOffline();
})();
