<?php
// pages/wbs/create.php (used for both create and edit)
requireRole(['admin','staf_keuangan']);

$id        = (int)($_GET['id'] ?? 0);
$proyekId  = (int)($_GET['proyek_id'] ?? 0);
$isEdit    = $id > 0;
$wbs       = $isEdit ? dbFetchOne("SELECT * FROM wbs WHERE id=?", [$id]) : [];
if ($isEdit && !$wbs) { setFlash('danger','WBS tidak ditemukan.'); redirect('index.php?p=wbs'); }
if ($isEdit) $proyekId = $wbs['proyek_id'];

$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
$pageTitle = $isEdit ? 'Edit WBS' : 'Tambah WBS';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'proyek_id'      => (int)($_POST['proyek_id'] ?? 0),
        'kode_wbs'       => strtoupper(sanitize($_POST['kode_wbs'] ?? '')),
        'uraian'         => sanitize($_POST['uraian'] ?? ''),
        'bobot'          => (float)str_replace(',','.',($_POST['bobot'] ?? 0)),
        'anggaran'       => (float)str_replace(['.',','],['','.'],($_POST['anggaran'] ?? 0)),
        'durasi_rencana' => (int)($_POST['durasi_rencana'] ?? 0) ?: null,
        'urutan'         => (int)($_POST['urutan'] ?? 1),
    ];
    if (!$data['kode_wbs']) $errors[] = 'Kode WBS wajib diisi.';
    if (!$data['uraian'])   $errors[] = 'Uraian pekerjaan wajib diisi.';
    if (!$data['proyek_id']) $errors[] = 'Pilih proyek terlebih dahulu.';

    if (!$errors) {
        if ($isEdit) {
            dbUpdate('wbs', $data, 'id=?', [$id]);
            setFlash('success', 'WBS berhasil diperbarui.');
        } else {
            dbInsert('wbs', $data);
            setFlash('success', 'WBS berhasil ditambahkan.');
        }
        redirect('index.php?p=wbs&proyek_id=' . $data['proyek_id']);
    }
    $wbs = $data;
}

include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?p=wbs&proyek_id=<?= $proyekId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i><?= $pageTitle ?></h4>
    <div class="text-muted small">Work Breakdown Structure — Paket Pekerjaan</div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $er) echo "<div>• $er</div>"; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-600 required">Proyek</label>
          <select name="proyek_id" class="form-select" required>
            <option value="">— Pilih Proyek —</option>
            <?php foreach ($allProyek as $pr): ?>
            <option value="<?= $pr['id'] ?>" <?= ($wbs['proyek_id']??$proyekId)==$pr['id']?'selected':'' ?>>
              <?= e($pr['kode_proyek'].' — '.$pr['nama_proyek']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 required">Kode WBS</label>
          <input type="text" name="kode_wbs" class="form-control text-uppercase" placeholder="mis. C.III"
                 value="<?= e($wbs['kode_wbs'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600">Urutan</label>
          <input type="number" name="urutan" class="form-control" min="1" value="<?= e($wbs['urutan'] ?? 1) ?>">
        </div>
        <div class="col-12">
          <label class="form-label fw-600 required">Uraian Pekerjaan</label>
          <input type="text" name="uraian" class="form-control" placeholder="Deskripsi lengkap paket pekerjaan"
                 value="<?= e($wbs['uraian'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Anggaran / BAC per WBS (Rp)</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" name="anggaran" class="form-control rupiah-input"
                   value="<?= e(number_format($wbs['anggaran']??0,0,',','.')) ?>" placeholder="0">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Bobot (% terhadap Total Proyek)</label>
          <div class="input-group">
            <input type="number" name="bobot" class="form-control" step="0.0001" min="0" max="100"
                   value="<?= e($wbs['bobot'] ?? 0) ?>" placeholder="0.0000">
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Durasi Rencana (Minggu)</label>
          <div class="input-group">
            <input type="number" name="durasi_rencana" class="form-control" min="0"
                   value="<?= e($wbs['durasi_rencana'] ?? '') ?>" placeholder="0">
            <span class="input-group-text">minggu</span>
          </div>
        </div>
      </div>
      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4 fw-bold">
          <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Tambah WBS' ?>
        </button>
        <a href="index.php?p=wbs&proyek_id=<?= $proyekId ?>" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.rupiah-input').forEach(inp => {
  inp.addEventListener('input', function(){
    let v = this.value.replace(/\D/g,'');
    this.value = v ? parseInt(v).toLocaleString('id-ID') : '';
  });
});
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
