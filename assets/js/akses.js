// =========================================================
// MODE MUDAH: huruf besar, kontras tinggi, dan "Dengarkan" (dibacakan suara)
// untuk lansia, penyandang disabilitas, dan warga yang kurang lancar membaca.
// Suara memakai fitur bawaan browser (Web Speech API), gratis dan tanpa kirim data.
// Juga: tombol pasang aplikasi (PWA) dan penanda saat offline.
// =========================================================
(function () {
  const html = document.documentElement;
  const simpan = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
  const ambil = k => { try { return localStorage.getItem(k); } catch (e) { return null; } };

  // Pulihkan pilihan sebelumnya
  ['besar', 'kontras'].forEach(m => { if (ambil('mm-' + m) === '1') html.classList.add('mode-' + m); });

  const tombolBuka = document.getElementById('aksesBtn'), panel = document.getElementById('aksesPanel');
  if (tombolBuka && panel) {
    tombolBuka.addEventListener('click', () => {
      panel.hidden = !panel.hidden;
      tombolBuka.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden) { panel.hidden = true; tombolBuka.setAttribute('aria-expanded', 'false'); tombolBuka.focus(); } });
  }

  // Tombol huruf besar dan kontras
  document.querySelectorAll('[data-mode]').forEach(b => {
    const m = b.dataset.mode;
    const sinkron = () => b.setAttribute('aria-pressed', String(html.classList.contains('mode-' + m)));
    sinkron();
    b.addEventListener('click', () => { html.classList.toggle('mode-' + m); simpan('mm-' + m, html.classList.contains('mode-' + m) ? '1' : '0'); sinkron(); });
  });

  // Dengarkan halaman
  const tombolSuara = document.getElementById('dengarBtn');
  if (tombolSuara) {
    if (!('speechSynthesis' in window)) {
      tombolSuara.disabled = true;
      tombolSuara.textContent = 'Suara tidak didukung browser ini';
    } else {
      let bicara = false;
      const suaraIndonesia = () => speechSynthesis.getVoices().find(v => /^id|indonesia/i.test(v.lang + ' ' + v.name));
      const berhenti = () => { speechSynthesis.cancel(); bicara = false; tombolSuara.textContent = 'Dengarkan halaman ini'; tombolSuara.setAttribute('aria-pressed', 'false'); };
      tombolSuara.addEventListener('click', () => {
        if (bicara) return berhenti();
        const isi = document.getElementById('isi');
        // Ambil teks yang terlihat saja, lalu pecah per kalimat supaya tidak terpotong di sebagian browser
        const teks = (isi ? isi.innerText : document.body.innerText).replace(/\s+/g, ' ').trim();
        const kalimat = teks.match(/[^.!?]+[.!?]*/g) || [teks];
        const suara = suaraIndonesia();
        kalimat.forEach((k, i) => {
          const u = new SpeechSynthesisUtterance(k.trim());
          u.lang = 'id-ID'; u.rate = 0.95;
          if (suara) u.voice = suara;
          if (i === kalimat.length - 1) u.onend = berhenti;
          speechSynthesis.speak(u);
        });
        bicara = true;
        tombolSuara.textContent = 'Hentikan suara';
        tombolSuara.setAttribute('aria-pressed', 'true');
      });
      window.addEventListener('pagehide', () => speechSynthesis.cancel());
    }
  }

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
