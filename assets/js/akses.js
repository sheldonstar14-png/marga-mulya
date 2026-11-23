// =========================================================
// PWA (Fase 12s): pasang di HP Android dan iPhone, simpan halaman untuk dibuka tanpa sinyal,
// penanda offline, dan form yang menunggu sinyal.
// Aksesibilitas bawaan di semua halaman: huruf 18px, kontras AA, area sentuh 48px,
// dan ramah pembaca layar HP (TalkBack/VoiceOver).
// =========================================================
(function () {
  const tombolPasang = document.getElementById('pasangBtn');
  const dialog = document.getElementById('pasangDialog');
  const terpasang = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  let tawaran = null;

  // Sudah dibuka sebagai aplikasi: tombol pasang tidak perlu
  if (terpasang && tombolPasang) tombolPasang.hidden = true;

  // Android/Chrome: browser menawarkan pemasangan langsung
  window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); tawaran = e; });
  window.addEventListener('appinstalled', () => { if (tombolPasang) tombolPasang.hidden = true; });

  if (tombolPasang) tombolPasang.addEventListener('click', async () => {
    if (tawaran) {
      tawaran.prompt(); const pilihan = await tawaran.userChoice; tawaran = null;
      if (pilihan.outcome === 'accepted') tombolPasang.hidden = true;
      return;
    }
    // iPhone, iPad, atau browser lain: tampilkan langkah pemasangan manual
    if (!dialog || !dialog.showModal) return;
    dialog.querySelector('.pasang-ios').hidden = !iOS;
    dialog.querySelector('.pasang-lain').hidden = iOS;
    dialog.showModal();
  });

  // Simpan halaman untuk dibuka tanpa sinyal
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }

  // Penanda offline + form tidak dikirim saat offline (isian tetap tersimpan di layar)
  const penanda = document.getElementById('penandaOffline');
  const aturOffline = () => { if (penanda) penanda.hidden = navigator.onLine; };
  window.addEventListener('online', aturOffline); window.addEventListener('offline', aturOffline); aturOffline();
  document.addEventListener('submit', e => {
    const f = e.target;
    if (navigator.onLine || (f.method || '').toLowerCase() === 'get' && f.closest('#cariForm')) return;
    e.preventDefault();
    let pesan = f.querySelector('.pesan-offline');
    if (!pesan) {
      pesan = document.createElement('div'); pesan.className = 'alert alert-err pesan-offline'; pesan.setAttribute('role', 'alert');
      f.prepend(pesan);
    }
    pesan.textContent = 'Sinyal sedang tidak ada, jadi isian belum terkirim. Isianmu tetap di layar. Tekan tombol kirim lagi setelah sinyal kembali.';
    pesan.scrollIntoView({ block: 'center' });
  }, true);
})();
