// =========================================================
// KOTAK OBROLAN ASISTEN DESA
// Mengirim pertanyaan ke /asisten dan menampilkan jawaban. Semua teks dari server
// dimasukkan sebagai teks biasa (bukan HTML), jadi aman dari sisipan kode.
// =========================================================
(function () {
  const tombol = document.getElementById('asistenBtn'), panel = document.getElementById('asistenPanel');
  if (!tombol || !panel) return;
  const log = document.getElementById('asistenLog'), form = document.getElementById('asistenForm'),
        input = document.getElementById('asistenInput'), saranEl = document.getElementById('asistenSaran'),
        tutup = document.getElementById('asistenTutup');
  let sudahSapa = false, sibuk = false;

  function buka() {
    panel.hidden = false; tombol.setAttribute('aria-expanded', 'true'); tombol.hidden = true;
    if (!sudahSapa) { sudahSapa = true; tanya(''); }
    setTimeout(() => input.focus(), 50);
  }
  function tutupPanel() { panel.hidden = true; tombol.hidden = false; tombol.setAttribute('aria-expanded', 'false'); tombol.focus(); }
  tombol.addEventListener('click', buka);
  tutup.addEventListener('click', tutupPanel);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden) tutupPanel(); });

  function gelembung(siapa, teks) {
    const b = document.createElement('div');
    b.className = 'chat-b chat-' + siapa;
    teks.split('\n').forEach((baris, i) => { if (i) b.appendChild(document.createElement('br')); b.appendChild(document.createTextNode(baris)); });
    log.appendChild(b); log.scrollTop = log.scrollHeight;
    return b;
  }
  const aman = url => /^(\/|https:\/\/|tel:)/.test(url);

  function tampilJawaban(d) {
    const b = gelembung('bot', d.teks || '');
    (d.tautan || []).filter(t => aman(t.url)).forEach(t => {
      const a = document.createElement('a');
      a.href = t.url; a.textContent = t.label; a.className = 'chat-link';
      if (/^https:/.test(t.url)) { a.target = '_blank'; a.rel = 'noopener'; }
      b.appendChild(a);
    });
    saranEl.innerHTML = '';
    (d.saran || []).forEach(s => {
      const c = document.createElement('button');
      c.type = 'button'; c.className = 'chip'; c.textContent = s;
      c.addEventListener('click', () => kirim(s));
      saranEl.appendChild(c);
    });
    log.scrollTop = log.scrollHeight;
  }

  function tanya(teks) {
    if (sibuk) return;
    sibuk = true;
    const tunggu = gelembung('bot', 'Sebentar...'); tunggu.classList.add('chat-tunggu');
    fetch('/asisten', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'pesan=' + encodeURIComponent(teks) })
      .then(r => r.json())
      .then(d => { tunggu.remove(); tampilJawaban(d); })
      .catch(() => { tunggu.remove(); tampilJawaban({ teks: 'Maaf, asisten tidak bisa dihubungi. Periksa koneksi internet, atau lihat nomor penting di halaman Kontak.', tautan: [{ label: 'Halaman Kontak', url: '/kontak' }] }); })
      .finally(() => { sibuk = false; });
  }
  function kirim(teks) {
    teks = teks.trim();
    if (!teks || sibuk) return;
    gelembung('saya', teks); input.value = ''; tanya(teks);
  }
  form.addEventListener('submit', e => { e.preventDefault(); kirim(input.value); });
})();
