<?php
// pages/progres/index.php
requireRole(['admin','staf_lapangan']);

$proyekId = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];
$proyek = $proyekId ? dbFetchOne("SELECT * FROM proyek WHERE id=?",[$proyekId]) : null;

// Filter minggu
$filterMinggu = (int)($_GET['minggu'] ?? 0);

$sql = "SELECT pf.*, w.kode_wbs, w.uraian, w.anggaran, w.bobot
        FROM progres_fisik pf
        JOIN wbs w ON w.id = pf.wbs_id
        WHERE w.proyek_id = ?";
$params = [$proyekId];
if ($filterMinggu) { $sql .= " AND pf.minggu_ke = ?"; $params[] = $filterMinggu; }
$sql .= " ORDER BY pf.minggu_ke DESC, w.urutan ASC";

$records = $proyekId ? dbFetchAll($sql, $params) : [];

// Minggu-minggu yang ada data
$availMinggu = $proyekId
  ? dbFetchAll("SELECT DISTINCT pf.minggu_ke, MIN(pf.periode) as tgl FROM progres_fisik pf JOIN wbs w ON w.id=pf.wbs_id WHERE w.proyek_id=? GROUP BY pf.minggu_ke ORDER BY pf.minggu_ke DESC",[$proyekId])
  : [];

$pageTitle = 'Input Progres Fisik';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Progres Fisik</h4>
    <div class="text-muted small">Monitoring realisasi kemajuan fisik per WBS per periode</div>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2" id="filterForm">
      <input type="hidden" name="p" value="progres">
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
    <a href="index.php?p=progres&a=input&proyek_id=<?= $proyekId ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>Input Progres
    </a>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-dark">
          <tr>
            <th>Minggu Ke</th><th>Periode</th><th>Kode WBS</th><th>Uraian</th>
            <th class="text-center">Progress Minggu Ini</th><th class="text-center">Kumulatif</th>
            <th class="text-center">Status</th><th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr>
            <td><span class="badge bg-secondary">Mgg <?= $r['minggu_ke'] ?></span></td>
            <td><?= date('d/m/Y', strtotime($r['periode'])) ?></td>
            <td><span class="badge bg-primary"><?= e($r['kode_wbs']) ?></span></td>
            <td class="fw-500" style="max-width:200px"><?= e(mb_strimwidth($r['uraian'],0,50,'…')) ?></td>
            <td class="text-center">
              <span class="fw-bold"><?= formatPersen($r['persentase_aktual']) ?></span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px;min-width:80px">
                  <div class="progress-bar <?= $r['persentase_kumulatif']>=90?'bg-success':($r['persentase_kumulatif']>=60?'bg-warning':'bg-danger') ?>"
                       style="width:<?= min(100,$r['persentase_kumulatif']) ?>%"></div>
                </div>
                <span style="width:44px" class="fw-bold"><?= round($r['persentase_kumulatif'],1) ?>%</span>
              </div>
            </td>
            <td class="text-center">
              <?php
              $pv = getBaselineKumulatif($r['wbs_id'], $r['minggu_ke']);
              $ev = (float)$r['persentase_kumulatif'];
              $diff = $ev - $pv;
              if ($ev >= 99) echo '<span class="badge bg-success">Selesai</span>';
              elseif ($diff >= -5) echo '<span class="badge bg-success">Normal</span>';
              elseif ($diff >= -15) echo '<span class="badge bg-warning text-dark">Terlambat</span>';
              else echo '<span class="badge bg-danger">Kritis</span>';
              ?>
            </td>
            <td class="text-muted" style="max-width:150px"><?= e(mb_strimwidth($r['catatan']??'',0,40,'…')) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($records)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data progres.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
