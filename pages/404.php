<?php
requireAuth();
$pageTitle = 'Halaman Tidak Ditemukan';
include __DIR__ . '/../includes/layout/header.php';
?>
<div class="text-center py-5">
  <div style="font-size:80px">🔍</div>
  <h3 class="fw-800 text-dark mt-3">404 — Halaman Tidak Ditemukan</h3>
  <p class="text-muted">Halaman yang Anda cari tidak tersedia.</p>
  <a href="index.php?p=dashboard" class="btn btn-primary mt-2">
    <i class="bi bi-house me-1"></i>Kembali ke Dashboard
  </a>
</div>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
