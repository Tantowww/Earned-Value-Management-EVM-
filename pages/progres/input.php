<?php
// pages/progres/input.php
requireRole(['admin','staf_lapangan']);

$proyekId = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];
$proyek  = $proyekId ? dbFetchOne("SELECT * FROM proyek WHERE id=?",[$proyekId]) : null;
$wbsList = $proyekId ? getWBSByProyek($proyekId) : [];

$pageTitle = 'Input Progres Fisik';
$errors = $success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pId     = (int)$_POST['proyek_id'];
    $mingguKe= (int)$_POST['minggu_ke'];
    $periode = $_POST['periode'];

    if (!$mingguKe || !$periode) {
        $errors[] = 'Minggu ke dan periode wajib diisi.';
    } else {
        $wbsItems = dbFetchAll("SELECT * FROM wbs WHERE proyek_id=? ORDER BY urutan",[   $pId]);
        foreach ($wbsItems as $w) {
            $wid = $w['id'];
            $pctMingguIni = (float)($_POST["prog_$wid"] ?? 0);

            // Hitung kumulatif dari periode sebelumnya
            $prevRow = dbFetchOne(
                "SELECT persentase_kumulatif FROM progres_fisik WHERE wbs_id=? AND minggu_ke<? ORDER BY minggu_ke DESC LIMIT 1",
                [$wid, $mingguKe]
            );
            $prevKum = $prevRow ? (float)$prevRow['persentase_kumulatif'] : 0;
            $kum     = min(100, $prevKum + $pctMingguIni);

            // Upsert
            $existing = dbFetchOne("SELECT id FROM progres_fisik WHERE wbs_id=? AND minggu_ke=?",[$wid,$mingguKe]);
            $data = [
                'wbs_id'                => $wid,
                'periode'               => $periode,
                'minggu_ke'             => $mingguKe,
                'persentase_aktual'     => $pctMingguIni,
                'persentase_kumulatif'  => $kum,
                'catatan'               => sanitize($_POST["cat_$wid"] ?? ''),
                'created_by'            => $_SESSION['user_id'],
            ];
            if ($existing) {
                dbUpdate('progres_fisik', $data, 'id=?', [$existing['id']]);
            } else {
                dbInsert('progres_fisik', $data);
            }
        }
        setFlash('success', "Progres fisik Minggu ke-$mingguKe berhasil disimpan.");
        redirect("index.php?p=progres&proyek_id=$pId");
    }
}

// Untuk tampilkan nilai existing jika edit minggu tertentu
$editMinggu = (int)($_GET['minggu'] ?? ($proyek['minggu_ke'] ?? 1));
$existingData = [];
if ($proyekId && $editMinggu) {
    $rows = dbFetchAll(
        "SELECT pf.* FROM progres_fisik pf JOIN wbs w ON w.id=pf.wbs_id WHERE w.proyek_id=? AND pf.minggu_ke=?",
        [$proyekId, $editMinggu]
    );
    foreach ($rows as $r) $existingData[$r['wbs_id']] = $r;
}
$defaultDate = date('Y-m-d');

include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?p=progres&proyek_id=<?= $proyekId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Input Progres Fisik</h4>
    <div class="text-muted small">Catat realisasi kemajuan fisik per paket WBS per minggu</div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $er) echo "<div>• $er</div>"; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <form method="POST" id="progresForm">
      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-4">
          <label class="form-label fw-600 required">Proyek</label>
          <select name="proyek_id" class="form-select" onchange="location='index.php?p=progres&a=input&proyek_id='+this.value">
            <?php foreach ($allProyek as $pr): ?>
            <option value="<?= $pr['id'] ?>" <?= $pr['id']==$proyekId?'selected':'' ?>><?= e($pr['kode_proyek'].' — '.mb_strimwidth($pr['nama_proyek'],0,40,'…')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-600 required">Minggu Ke</label>
          <input type="number" name="minggu_ke" class="form-control" min="1"
                 max="<?= $proyek['total_minggu'] ?? 999 ?>" value="<?= $editMinggu ?>" id="mingguInput"
                 onchange="location='index.php?p=progres&a=input&proyek_id=<?= $proyekId ?>&minggu='+this.value">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 required">Tanggal Periode</label>
          <input type="date" name="periode" class="form-control" value="<?= e($existingData ? array_values($existingData)[0]['periode'] ?? $defaultDate : $defaultDate) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="alert alert-info mb-0 py-2 w-100 small">
            <i class="bi bi-info-circle me-1"></i>
            Isi <b>% minggu ini saja</b>. Kumulatif dihitung otomatis.
          </div>
        </div>
      </div>

      <!-- WBS Input Table -->
      <div class="table-responsive">
        <table class="table table-bordered align-middle small mb-3">
          <thead class="table-primary">
            <tr>
              <th style="width:80px">Kode</th>
              <th>Uraian Pekerjaan</th>
              <th class="text-end" style="width:140px">Anggaran</th>
              <th class="text-center" style="width:100px">Kum. Lalu</th>
              <th class="text-center" style="width:150px">Progres Minggu Ini (%)</th>
              <th class="text-center" style="width:110px">Est. Kumulatif</th>
              <th style="width:200px">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($wbsList as $w):
              $wid    = $w['id'];
              $prevRow = dbFetchOne(
                "SELECT persentase_kumulatif FROM progres_fisik WHERE wbs_id=? AND minggu_ke<? ORDER BY minggu_ke DESC LIMIT 1",
                [$wid, $editMinggu]
              );
              $prevKum = $prevRow ? (float)$prevRow['persentase_kumulatif'] : 0;
              $curVal  = $existingData[$wid]['persentase_aktual'] ?? 0;
              $curCat  = $existingData[$wid]['catatan'] ?? '';
            ?>
            <tr>
              <td><span class="badge bg-dark"><?= e($w['kode_wbs']) ?></span></td>
              <td><?= e($w['uraian']) ?></td>
              <td class="text-end"><?= formatMilliar($w['anggaran']) ?></td>
              <td class="text-center text-muted"><?= round($prevKum,2) ?>%</td>
              <td>
                <div class="input-group input-group-sm">
                  <input type="number" name="prog_<?= $wid ?>" class="form-control text-center prog-input fw-bold"
                         step="0.01" min="0" max="<?= 100 - $prevKum ?>"
                         value="<?= $curVal ?>" data-prev="<?= $prevKum ?>"
                         onchange="updateKum(this)">
                  <span class="input-group-text">%</span>
                </div>
              </td>
              <td class="text-center">
                <span class="badge bg-success kum-display" id="kum_<?= $wid ?>">
                  <?= round(min(100, $prevKum + $curVal), 2) ?>%
                </span>
              </td>
              <td>
                <input type="text" name="cat_<?= $wid ?>" class="form-control form-control-sm"
                       placeholder="Keterangan..." value="<?= e($curCat) ?>">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4 fw-bold">
          <i class="bi bi-save me-1"></i>Simpan Progres Minggu Ke-<?= $editMinggu ?>
        </button>
        <a href="index.php?p=progres&proyek_id=<?= $proyekId ?>" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
function updateKum(input) {
  const prev = parseFloat(input.dataset.prev) || 0;
  const cur  = parseFloat(input.value) || 0;
  const wid  = input.name.replace('prog_','');
  const kum  = Math.min(100, prev + cur);
  const el   = document.getElementById('kum_' + wid);
  if (el) {
    el.textContent = kum.toFixed(2) + '%';
    el.className = 'badge kum-display ' + (kum >= 90 ? 'bg-success' : kum >= 60 ? 'bg-warning text-dark' : 'bg-danger');
  }
}
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
