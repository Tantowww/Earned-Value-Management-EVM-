<?php
// pages/biaya/input.php
requireRole(['admin','staf_keuangan']);

$proyekId  = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];
$proyek    = $proyekId ? dbFetchOne("SELECT * FROM proyek WHERE id=?",[$proyekId]) : null;
$wbsList   = $proyekId ? getWBSByProyek($proyekId) : [];
$editMinggu= (int)($_GET['minggu'] ?? ($proyek['minggu_ke'] ?? 1));
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pId      = (int)$_POST['proyek_id'];
    $mingguKe = (int)$_POST['minggu_ke'];
    $periode  = $_POST['periode'];

    if (!$mingguKe || !$periode) {
        $errors[] = 'Minggu ke dan periode wajib diisi.';
    } else {
        $wbsItems = dbFetchAll("SELECT * FROM wbs WHERE proyek_id=? ORDER BY urutan",[$pId]);
        foreach ($wbsItems as $w) {
            $wid     = $w['id'];
            $biaya   = (float)str_replace(['.',','],['',' .'],($_POST["biaya_$wid"] ?? 0));
            $biaya   = (float)str_replace([' ','.'],['',''],($_POST["biaya_$wid"] ?? 0));
            // parse rupiah format
            $raw = preg_replace('/[^0-9]/', '', $_POST["biaya_$wid"] ?? '0');
            $biaya = (float)$raw;

            if ($biaya <= 0) continue; // skip kosong

            $existing = dbFetchOne("SELECT id FROM biaya_aktual WHERE wbs_id=? AND minggu_ke=?",[$wid,$mingguKe]);
            $data = [
                'wbs_id'       => $wid,
                'periode'      => $periode,
                'minggu_ke'    => $mingguKe,
                'jumlah_biaya' => $biaya,
                'deskripsi'    => sanitize($_POST["desk_$wid"] ?? ''),
                'created_by'   => $_SESSION['user_id'],
            ];
            if ($existing) {
                dbUpdate('biaya_aktual', $data, 'id=?', [$existing['id']]);
            } else {
                dbInsert('biaya_aktual', $data);
            }
        }
        setFlash('success', "Biaya aktual Minggu ke-$mingguKe berhasil disimpan.");
        redirect("index.php?p=biaya&proyek_id=$pId");
    }
}

// Load existing data
$existingData = [];
if ($proyekId && $editMinggu) {
    $rows = dbFetchAll(
        "SELECT ba.* FROM biaya_aktual ba JOIN wbs w ON w.id=ba.wbs_id WHERE w.proyek_id=? AND ba.minggu_ke=?",
        [$proyekId, $editMinggu]
    );
    foreach ($rows as $r) $existingData[$r['wbs_id']] = $r;
}

$pageTitle = 'Input Biaya Aktual';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?p=biaya&proyek_id=<?= $proyekId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i>Input Biaya Aktual (AC)</h4>
    <div class="text-muted small">Catat realisasi biaya per paket WBS per periode</div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $er) echo "<div>• $er</div>"; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <form method="POST" id="biayaForm">
      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-4">
          <label class="form-label fw-600 required">Proyek</label>
          <select name="proyek_id" class="form-select" onchange="location='index.php?p=biaya&a=input&proyek_id='+this.value">
            <?php foreach ($allProyek as $pr): ?>
            <option value="<?= $pr['id'] ?>" <?= $pr['id']==$proyekId?'selected':'' ?>><?= e($pr['kode_proyek'].' — '.mb_strimwidth($pr['nama_proyek'],0,40,'…')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-600 required">Minggu Ke</label>
          <input type="number" name="minggu_ke" id="mingguKe" class="form-control" min="1"
                 value="<?= $editMinggu ?>"
                 onchange="location='index.php?p=biaya&a=input&proyek_id=<?= $proyekId ?>&minggu='+this.value">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 required">Tanggal Periode</label>
          <input type="date" name="periode" class="form-control"
                 value="<?= e($existingData ? array_values($existingData)[0]['periode'] ?? date('Y-m-d') : date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
          <div class="alert alert-warning mb-0 py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Isi biaya <strong>periode ini saja</strong> (bukan kumulatif).
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle small mb-3">
          <thead class="table-warning">
            <tr>
              <th style="width:80px">Kode</th>
              <th>Uraian Pekerjaan</th>
              <th class="text-end" style="width:150px">Anggaran (BAC)</th>
              <th class="text-end" style="width:120px">AC s.d. Lalu</th>
              <th style="width:220px">Biaya Minggu Ini (Rp)</th>
              <th class="text-end" style="width:120px">AC Kumulatif</th>
              <th style="width:200px">Deskripsi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $grandTotal = 0;
            foreach ($wbsList as $w):
              $wid = $w['id'];
              $prevAC = getBiayaKumulatif($wid, $editMinggu - 1);
              $curBiaya = $existingData[$wid]['jumlah_biaya'] ?? 0;
              $curDesk  = $existingData[$wid]['deskripsi'] ?? '';
              $grandTotal += $curBiaya;
            ?>
            <tr>
              <td><span class="badge bg-warning text-dark"><?= e($w['kode_wbs']) ?></span></td>
              <td><?= e($w['uraian']) ?></td>
              <td class="text-end"><?= formatMilliar($w['anggaran']) ?></td>
              <td class="text-end text-muted"><?= formatMilliar($prevAC) ?></td>
              <td>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">Rp</span>
                  <input type="text" name="biaya_<?= $wid ?>"
                         class="form-control text-end rupiah-input biaya-input fw-bold"
                         value="<?= $curBiaya > 0 ? number_format($curBiaya,0,',','.') : '' ?>"
                         data-prev="<?= $prevAC ?>" id="biayaInput_<?= $wid ?>"
                         onchange="updateAC(this, <?= $wid ?>)"
                         placeholder="0">
                </div>
              </td>
              <td class="text-end">
                <span class="badge bg-warning text-dark" id="ac_<?= $wid ?>">
                  <?= formatMilliar($prevAC + $curBiaya) ?>
                </span>
              </td>
              <td>
                <input type="text" name="desk_<?= $wid ?>" class="form-control form-control-sm"
                       value="<?= e($curDesk) ?>" placeholder="Deskripsi biaya...">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-warning fw-bold">
            <tr>
              <td colspan="4" class="text-end">Total Input Periode Ini:</td>
              <td class="text-end" id="grandTotalCell"><?= formatRupiah($grandTotal) ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning fw-bold px-4">
          <i class="bi bi-save me-1"></i>Simpan Biaya Aktual Minggu Ke-<?= $editMinggu ?>
        </button>
        <a href="index.php?p=biaya&proyek_id=<?= $proyekId ?>" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
function rupiah(n) {
  return 'Rp ' + parseInt(n||0).toLocaleString('id-ID');
}
function updateAC(input, wid) {
  const prev = parseFloat(input.dataset.prev) || 0;
  const cur  = parseFloat(input.value.replace(/\D/g,'')) || 0;
  const el   = document.getElementById('ac_' + wid);
  if (el) el.textContent = rupiah(prev + cur);
  // update grand total
  let total = 0;
  document.querySelectorAll('.biaya-input').forEach(i => {
    total += parseFloat(i.value.replace(/\D/g,'')) || 0;
  });
  const gt = document.getElementById('grandTotalCell');
  if (gt) gt.textContent = rupiah(total);
}
document.querySelectorAll('.rupiah-input').forEach(inp => {
  inp.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    this.value = v ? parseInt(v).toLocaleString('id-ID') : '';
  });
});
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
