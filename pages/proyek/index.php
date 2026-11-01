<?php
// pages/proyek/index.php
requireAuth();
$pageTitle = 'Data Proyek';

// Handle delete
if ($_GET['action'] ?? '' === 'delete') {
    requireRole(['admin']);
    $id = (int)($_GET['id'] ?? 0);
    dbDelete('proyek', 'id=?', [$id]);
    setFlash('success', 'Proyek berhasil dihapus.');
    redirect('index.php?p=proyek');
}

$projects = getDaftarProyek();
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-buildings me-2 text-primary"></i>Data Proyek</h4>
    <div class="text-muted small">Kelola seluruh proyek PT. Pesona Tamanindo</div>
  </div>
  <?php if (hasRole(['admin','manager'])): ?>
  <a href="index.php?p=proyek&a=create" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Tambah Proyek
  </a>
  <?php endif; ?>
</div>

<div class="row g-3">
  <?php foreach ($projects as $pr):
    $spiOk = $pr['spi'] >= 1;
    $cpiOk = $pr['cpi'] >= 1;
    $pctTime = $pr['total_minggu'] > 0 ? round($pr['minggu_ke']/$pr['total_minggu']*100) : 0;
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100 project-card">
      <div class="card-header border-0 pb-1 d-flex justify-content-between align-items-start">
        <span class="badge bg-dark"><?= e($pr['kode_proyek']) ?></span>
        <?= badgeStatus($pr['status']) ?>
      </div>
      <div class="card-body">
        <h6 class="fw-700 mb-1"><?= e(mb_strimwidth($pr['nama_proyek'], 0, 60, '…')) ?></h6>
        <div class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i><?= e($pr['lokasi'] ?? '-') ?></div>

        <!-- EVM mini indicators -->
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="p-2 rounded-3 text-center" style="background:<?= $spiOk?'#ecfdf5':'#fef2f2' ?>">
              <div class="fw-800 <?= $spiOk?'text-success':'text-danger' ?>"><?= number_format($pr['spi'],3) ?></div>
              <div class="text-muted" style="font-size:10px">SPI</div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2 rounded-3 text-center" style="background:<?= $cpiOk?'#ecfdf5':'#fef2f2' ?>">
              <div class="fw-800 <?= $cpiOk?'text-success':'text-danger' ?>"><?= number_format($pr['cpi'],3) ?></div>
              <div class="text-muted" style="font-size:10px">CPI</div>
            </div>
          </div>
        </div>

        <!-- Progress bar EV vs PV -->
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted" style="font-size:11px">EV <?= formatPersen($pr['ev_pct']) ?></span>
            <span class="text-muted" style="font-size:11px">PV <?= formatPersen($pr['pv_pct']) ?></span>
          </div>
          <div class="progress" style="height:8px">
            <div class="progress-bar bg-primary" style="width:<?= min(100,$pr['pv_pct']) ?>%;opacity:0.4"></div>
            <div class="progress-bar bg-success" style="width:<?= min(100,$pr['ev_pct']) ?>%;margin-left:-<?= min(100,$pr['pv_pct']) ?>%"></div>
          </div>
          <div class="progress mt-1" style="height:4px;background:#e2e8f0">
            <div class="progress-bar <?= $pr['ev_pct']>=$pr['pv_pct']?'bg-success':'bg-success' ?>" style="width:<?= min(100,$pr['ev_pct']) ?>%"></div>
          </div>
        </div>

        <div class="text-muted" style="font-size:11px">
          <i class="bi bi-cash me-1"></i><?= formatMilliar($pr['nilai_kontrak']) ?>
          &nbsp;|&nbsp;
          <i class="bi bi-calendar me-1"></i>Mgg <?= $pr['minggu_ke'] ?>/<?= $pr['total_minggu'] ?>
          &nbsp;(<?= $pctTime ?>% waktu)
        </div>
      </div>
      <div class="card-footer bg-white border-0 pt-0 d-flex gap-2">
        <a href="index.php?p=dashboard&proyek_id=<?= $pr['id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
          <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
        <?php if (hasRole(['admin','manager'])): ?>
        <a href="index.php?p=proyek&a=edit&id=<?= $pr['id'] ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-pencil"></i>
        </a>
        <?php endif; ?>
        <?php if (hasRole(['admin'])): ?>
        <a href="index.php?p=proyek&action=delete&id=<?= $pr['id'] ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus proyek ini? Semua data WBS dan progres akan ikut terhapus.')">
          <i class="bi bi-trash"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($projects)): ?>
  <div class="col-12">
    <div class="card border-0 shadow-sm text-center py-5">
      <div class="text-muted"><i class="bi bi-buildings fs-1 mb-2 d-block"></i>Belum ada proyek.<br>
        <?php if (hasRole(['admin','manager'])): ?>
        <a href="index.php?p=proyek&a=create" class="btn btn-primary mt-2">Tambah Proyek Baru</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
