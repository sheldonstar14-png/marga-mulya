  </main>
</div>
<?php if (!empty($pakaiQr)): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
document.querySelectorAll('[data-qr]').forEach(el => {
  if (typeof qrcode === 'undefined') { el.innerHTML = '<p class="small">QR gagal dimuat. Alamat: ' + el.dataset.qr.replace(/</g, '') + '</p>'; return; }
  const qr = qrcode(0, 'M');
  qr.addData(el.dataset.qr);
  qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
});
</script>
<?php endif; ?>
<script src="/assets/js/admin.js?v=12s3"></script>
<?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?>
<form method="post" action="/keluar-situs" class="gerbang-keluar"><button type="submit">Mode privat aktif. Keluar</button></form>
<?php endif; ?>
</body>
</html>
