// =========================================================
// PETA GOOGLE (Fase 12s3): satu peta Google Maps + daftar lokasi.
// Menekan nama lokasi memindahkan peta ke titik itu. Tombol lapisan menyaring daftar.
// Saat ada siaga cuaca, zona rawan bencana disorot di daftar.
// =========================================================
(function () {
  document.querySelectorAll('.gpeta').forEach(el => {
    const frame = el.querySelector('iframe'), info = el.querySelector('.gpeta-pilihan');
    el.querySelectorAll('.gpeta-titik').forEach(b => b.addEventListener('click', () => {
      el.querySelectorAll('.gpeta-titik.on').forEach(x => x.classList.remove('on'));
      b.classList.add('on');
      frame.src = b.dataset.src;
      if (info) info.textContent = 'Menampilkan: ' + b.dataset.nama;
      if (window.matchMedia('(max-width: 860px)').matches) frame.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    const lapis = el.querySelectorAll('.gpeta-lapis [data-lapis]');
    lapis.forEach(b => b.addEventListener('click', () => {
      lapis.forEach(x => { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      el.querySelectorAll('[data-lapis-item]').forEach(s => { s.hidden = b.dataset.lapis !== '' && s.dataset.lapisItem !== b.dataset.lapis; });
    }));
    document.addEventListener('siaga-cuaca', ev => {
      if (!ev.detail || !ev.detail.length) return;
      el.querySelectorAll('.gpeta-zona').forEach(z => z.classList.add('siaga-on'));
    });
  });
})();
