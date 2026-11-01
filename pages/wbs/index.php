<?php
// pages/wbs/index.php
requireRole(['admin','staf_keuangan']);

$proyekId = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];

$proyek = $proyekId ? dbFetchOne("SELECT * FROM proyek WHERE id=?", [$proyekId]) : null;

// Delete
if (($_GET['action'] ?? '') === 'delete') {
    $wid = (int)($_GET['id'] ?? 0);
    dbDelete('wbs','id=?',[$wid]);
    setFlash('success','Item WBS berhasil dihapus.');
    redirect("index.php?p=wbs&proyek_id=$proyekId");
}

$wbsList = $proyekId ? dbFetchAll("SELECT * FROM wbs WHERE proyek_id=? ORDER BY urutan ASC",[$proyekId]) : [];
$totalBobot = array_sum(array_column($wbsList,'bobot'));
$totalAng   = array_sum(array_column($wbsList,'anggaran'));

$pageTitle = 'WBS & Anggaran';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Work Breakdown Structure</h4>
    <div class="text-muted small">Kelola paket pekerjaan dan anggaran per WBS</div>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="p" value="wbs">
      <select name="proyek_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:240px">
        <?php foreach ($allProyek as $pr): ?>
        <option value="<?= $pr['id'] ?>" <?= $pr['id']==$proyekId?'selected':'' ?>><?= e($pr['kode_proyek'].' — '.mb_strimwidth($pr['nama_proyek'],0,40,'…')) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="index.php?p=wbs&a=create&proyek_id=<?= $proyekId ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>Tambah WBS
    </a>
  </div>
</div>

<?php if ($proyek): ?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><div class="text-muted small">Total WBS Items</div><div class="fw-800 fs-4"><?= count($wbsList) ?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><div class="text-muted small">Total Anggaran (BAC)</div><div class="fw-800 fs-5"><?= formatMilliar($proyek['nilai_kontrak']) ?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><div class="text-muted small">Total Bobot WBS</div><div class="fw-800 fs-4 <?= abs($totalBobot-100)<0.5?'text-success':'text-danger' ?>"><?= formatPersen($totalBobot) ?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><div class="text-muted small">Jumlah Anggaran WBS</div><div class="fw-800 fs-5"><?= formatMilliar($totalAng) ?></div></div></div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-dark">
          <tr>
            <th>No</th><th>Kode WBS</th><th>Uraian Pekerjaan</th>
            <th class="text-end">Anggaran (Rp)</th><th class="text-center">Bobot%</th>
            <th class="text-center">Durasi (Mgg)</th><th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($wbsList as $i => $w): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><span class="badge bg-primary"><?= e($w['kode_wbs']) ?></span></td>
            <td><?= e($w['uraian']) ?></td>
            <td class="text-end fw-600"><?= formatRupiah($w['anggaran']) ?></td>
            <td class="text-center"><?= formatPersen($w['bobot']) ?></td>
            <td class="text-center"><?= $w['durasi_rencana'] ?? '-' ?></td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <a href="index.php?p=wbs&a=create&id=<?= $w['id'] ?>&proyek_id=<?= $proyekId ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <a href="index.php?p=wbs&action=delete&id=<?= $w['id'] ?>&proyek_id=<?= $proyekId ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Hapus WBS ini beserta semua progres & biaya terkait?')">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($wbsList)): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data WBS untuk proyek ini.</td></tr>
          <?php endif; ?>
        </tbody>
        <?php if ($wbsList): ?>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="3">TOTAL</td>
            <td class="text-end"><?= formatRupiah($totalAng) ?></td>
            <td class="text-center <?= abs($totalBobot-100)<0.5?'text-success':'text-warning' ?>"><?= formatPersen($totalBobot) ?></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
