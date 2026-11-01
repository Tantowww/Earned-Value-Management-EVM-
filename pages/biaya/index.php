<?php
// pages/biaya/index.php
requireRole(['admin','staf_keuangan']);

$proyekId = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];
$filterMinggu = (int)($_GET['minggu'] ?? 0);

$sql = "SELECT ba.*, w.kode_wbs, w.uraian, w.anggaran
        FROM biaya_aktual ba
        JOIN wbs w ON w.id = ba.wbs_id
        WHERE w.proyek_id = ?";
$params = [$proyekId];
if ($filterMinggu) { $sql .= " AND ba.minggu_ke=?"; $params[] = $filterMinggu; }
$sql .= " ORDER BY ba.minggu_ke DESC, w.urutan ASC";
$records = $proyekId ? dbFetchAll($sql, $params) : [];

$totalBiaya = array_sum(array_column($records, 'jumlah_biaya'));

$availMinggu = $proyekId
  ? dbFetchAll("SELECT DISTINCT ba.minggu_ke, MIN(ba.periode) as tgl FROM biaya_aktual ba JOIN wbs w ON w.id=ba.wbs_id WHERE w.proyek_id=? GROUP BY ba.minggu_ke ORDER BY ba.minggu_ke DESC",[$proyekId])
  : [];

$pageTitle = 'Biaya Aktual (AC)';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i>Realisasi Biaya Aktual (AC)</h4>
    <div class="text-muted small">Pencatatan actual cost per WBS per periode</div>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="p" value="biaya">
      <select name="proyek_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:220px">
        <?php foreach ($allProyek as $pr): ?>
        <option value="<?= $pr['id'] ?>" <?= $pr['id']==$proyekId?'selected':'' ?>><?= e($pr['kode_proyek'].' — '.mb_strimwidth($pr['nama_proyek'],0,35,'…')) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="minggu" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:140px">
        <option value="">Semua Minggu</option>
        <?php foreach ($availMinggu as $m): ?>
        <option value="<?= $m['minggu_ke'] ?>" <?= $filterMinggu==$m['minggu_ke']?'selected':'' ?>>
          Minggu <?= $m['minggu_ke'] ?> (<?= date('d/m/Y',strtotime($m['tgl'])) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="index.php?p=biaya&a=input&proyek_id=<?= $proyekId ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>Input Biaya
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Total AC (Ditampilkan)</div>
      <div class="fw-800 fs-5 text-warning"><?= formatMilliar($totalBiaya) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Jumlah Record</div>
      <div class="fw-800 fs-4"><?= count($records) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Filter</div>
      <div class="fw-600"><?= $filterMinggu ? "Minggu ke-$filterMinggu" : 'Semua periode' ?></div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-dark">
          <tr>
            <th>Minggu Ke</th><th>Periode</th><th>Kode WBS</th><th>Uraian</th>
            <th class="text-end">Biaya (Rp)</th><th>Deskripsi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr>
            <td><span class="badge bg-secondary">Mgg <?= $r['minggu_ke'] ?></span></td>
            <td><?= date('d/m/Y',strtotime($r['periode'])) ?></td>
            <td><span class="badge bg-warning text-dark"><?= e($r['kode_wbs']) ?></span></td>
            <td><?= e(mb_strimwidth($r['uraian'],0,50,'…')) ?></td>
            <td class="text-end fw-bold text-warning"><?= formatRupiah($r['jumlah_biaya']) ?></td>
            <td class="text-muted"><?= e(mb_strimwidth($r['deskripsi']??'',0,50,'…')) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data biaya aktual.</td></tr>
          <?php endif; ?>
        </tbody>
        <?php if ($records): ?>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="4">TOTAL AC</td>
            <td class="text-end"><?= formatRupiah($totalBiaya) ?></td>
            <td></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
