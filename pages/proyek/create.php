<?php
// pages/proyek/create.php — juga dipakai untuk edit
requireRole(['admin','manager']);

$id    = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$proyek = $isEdit ? dbFetchOne("SELECT * FROM proyek WHERE id=?", [$id]) : [];
if ($isEdit && !$proyek) { setFlash('danger','Proyek tidak ditemukan.'); redirect('index.php?p=proyek'); }

$pageTitle = $isEdit ? 'Edit Proyek' : 'Tambah Proyek';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'kode_proyek'     => sanitize($_POST['kode_proyek'] ?? ''),
        'nama_proyek'     => sanitize($_POST['nama_proyek'] ?? ''),
        'lokasi'          => sanitize($_POST['lokasi'] ?? ''),
        'pemberi_kerja'   => sanitize($_POST['pemberi_kerja'] ?? ''),
        'tanggal_mulai'   => $_POST['tanggal_mulai'] ?? '',
        'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '',
        'nilai_kontrak'   => (float)str_replace(['.',','], ['','.'], $_POST['nilai_kontrak'] ?? 0),
        'ppn_persen'      => (float)($_POST['ppn_persen'] ?? 10),
        'status'          => $_POST['status'] ?? 'aktif',
        'minggu_ke'       => (int)($_POST['minggu_ke'] ?? 1),
        'total_minggu'    => (int)($_POST['total_minggu'] ?? 1),
        'deskripsi'       => sanitize($_POST['deskripsi'] ?? ''),
    ];

    if (!$data['kode_proyek']) $errors[] = 'Kode proyek wajib diisi.';
    if (!$data['nama_proyek']) $errors[] = 'Nama proyek wajib diisi.';
    if (!$data['tanggal_mulai'] || !$data['tanggal_selesai']) $errors[] = 'Tanggal mulai & selesai wajib diisi.';
    if ($data['nilai_kontrak'] <= 0) $errors[] = 'Nilai kontrak harus lebih dari 0.';

    if (!$errors) {
        if ($isEdit) {
            dbUpdate('proyek', $data, 'id=?', [$id]);
            setFlash('success', 'Proyek berhasil diperbarui.');
        } else {
            $data['created_by'] = $_SESSION['user_id'];
            dbInsert('proyek', $data);
            setFlash('success', 'Proyek berhasil ditambahkan.');
        }
        redirect('index.php?p=proyek');
    }
    $proyek = $data;
}

include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?p=proyek" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-<?= $isEdit?'pencil-square':'plus-circle' ?> me-2 text-primary"></i><?= $pageTitle ?></h4>
    <div class="text-muted small">Form data proyek konstruksi</div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-circle me-2"></i><strong>Terdapat kesalahan:</strong>
  <ul class="mb-0 mt-1"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-600 required">Kode Proyek</label>
          <input type="text" name="kode_proyek" class="form-control" placeholder="PTM-BXC2-2022"
                 value="<?= e($proyek['kode_proyek'] ?? '') ?>" required>
        </div>
        <div class="col-md-8">
          <label class="form-label fw-600 required">Nama Proyek</label>
          <input type="text" name="nama_proyek" class="form-control" placeholder="Nama lengkap proyek"
                 value="<?= e($proyek['nama_proyek'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">Lokasi</label>
          <input type="text" name="lokasi" class="form-control" placeholder="Kota, Provinsi"
                 value="<?= e($proyek['lokasi'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">Pemberi Kerja / Owner</label>
          <input type="text" name="pemberi_kerja" class="form-control" placeholder="Nama perusahaan pemberi kerja"
                 value="<?= e($proyek['pemberi_kerja'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 required">Tanggal Mulai</label>
          <input type="date" name="tanggal_mulai" class="form-control"
                 value="<?= e($proyek['tanggal_mulai'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 required">Tanggal Selesai Rencana</label>
          <input type="date" name="tanggal_selesai" class="form-control"
                 value="<?= e($proyek['tanggal_selesai'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600">Minggu Ke (Saat Ini)</label>
          <input type="number" name="minggu_ke" class="form-control" min="1"
                 value="<?= e($proyek['minggu_ke'] ?? 1) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600">Total Durasi (Minggu)</label>
          <input type="number" name="total_minggu" class="form-control" min="1"
                 value="<?= e($proyek['total_minggu'] ?? 1) ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label fw-600 required">Nilai Kontrak / BAC (Rp)</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" name="nilai_kontrak" class="form-control rupiah-input" placeholder="24.800.000.000"
                   value="<?= e(number_format($proyek['nilai_kontrak'] ?? 0, 0, ',', '.')) ?>" required>
          </div>
          <div class="form-text">Budget at Completion (BAC) — sebelum PPN</div>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600">PPN (%)</label>
          <div class="input-group">
            <input type="number" name="ppn_persen" class="form-control" step="0.01" min="0" max="100"
                   value="<?= e($proyek['ppn_persen'] ?? 10) ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Status Proyek</label>
          <select name="status" class="form-select">
            <option value="aktif"        <?= ($proyek['status']??'')=='aktif'?'selected':'' ?>>Aktif</option>
            <option value="selesai"      <?= ($proyek['status']??'')=='selesai'?'selected':'' ?>>Selesai</option>
            <option value="ditangguhkan" <?= ($proyek['status']??'')=='ditangguhkan'?'selected':'' ?>>Ditangguhkan</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-600">Deskripsi Proyek</label>
          <textarea name="deskripsi" class="form-control" rows="3"
                    placeholder="Deskripsi singkat proyek..."><?= e($proyek['deskripsi'] ?? '') ?></textarea>
        </div>
      </div>
      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4 fw-bold">
          <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Proyek' ?>
        </button>
        <a href="index.php?p=proyek" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
// Format rupiah input
document.querySelectorAll('.rupiah-input').forEach(inp => {
  inp.addEventListener('input', function(){
    let v = this.value.replace(/\D/g,'');
    this.value = v ? parseInt(v).toLocaleString('id-ID') : '';
  });
});
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
