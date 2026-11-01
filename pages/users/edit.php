<?php
// pages/users/create.php (also handles edit)
requireRole(['admin']);

$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$user   = $isEdit ? dbFetchOne("SELECT * FROM users WHERE id=?",[$id]) : [];
if ($isEdit && !$user) { setFlash('danger','Pengguna tidak ditemukan.'); redirect('index.php?p=users'); }

$pageTitle = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username'     => sanitize($_POST['username'] ?? ''),
        'nama_lengkap' => sanitize($_POST['nama_lengkap'] ?? ''),
        'email'        => sanitize($_POST['email'] ?? ''),
        'role'         => $_POST['role'] ?? 'staf_lapangan',
        'aktif'        => isset($_POST['aktif']) ? 1 : 0,
    ];
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$data['username'])     $errors[] = 'Username wajib diisi.';
    if (!$data['nama_lengkap']) $errors[] = 'Nama lengkap wajib diisi.';
    if (!$isEdit && !$password) $errors[] = 'Password wajib diisi untuk pengguna baru.';
    if ($password && $password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';
    if (strlen($password) > 0 && strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';

    // Check username unique
    $existing = dbFetchOne("SELECT id FROM users WHERE username=? AND id!=?",[$data['username'],$id]);
    if ($existing) $errors[] = 'Username sudah digunakan.';

    if (!$errors) {
        if ($password) $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);

        if ($isEdit) {
            dbUpdate('users', $data, 'id=?', [$id]);
            setFlash('success','Pengguna berhasil diperbarui.');
        } else {
            dbInsert('users', $data);
            setFlash('success','Pengguna berhasil ditambahkan.');
        }
        redirect('index.php?p=users');
    }
    $user = array_merge($user, $data);
}

include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?p=users" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-person-plus me-2 text-primary"></i><?= $pageTitle ?></h4>
    <div class="text-muted small">Kelola akun dan hak akses pengguna</div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $er) echo "<div>• $er</div>"; ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-600 required">Nama Lengkap</label>
              <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap pengguna"
                     value="<?= e($user['nama_lengkap'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600 required">Username</label>
              <input type="text" name="username" class="form-control" placeholder="username (tanpa spasi)"
                     value="<?= e($user['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_]+" autocomplete="off">
              <div class="form-text">Hanya huruf, angka, dan underscore.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Email</label>
              <input type="email" name="email" class="form-control" placeholder="email@perusahaan.co.id"
                     value="<?= e($user['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600 required">Role / Jabatan</label>
              <select name="role" class="form-select">
                <?php foreach (['admin'=>'Administrator','manager'=>'Manajer Proyek','staf_lapangan'=>'Staf Lapangan','staf_keuangan'=>'Staf Keuangan'] as $val=>$lbl): ?>
                <option value="<?= $val ?>" <?= ($user['role']??'')====$val?'selected':'' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600 <?= $isEdit?'':'required' ?>">Password<?= $isEdit?' (kosongkan jika tidak berubah)':'' ?></label>
              <div class="input-group">
                <input type="password" name="password" id="pwd" class="form-control" autocomplete="new-password"
                       placeholder="<?= $isEdit?'Biarkan kosong jika tidak ingin mengubah':'Minimal 8 karakter' ?>"
                       <?= $isEdit?'':'required' ?>>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd','eyePwd')">
                  <i class="bi bi-eye" id="eyePwd"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Konfirmasi Password</label>
              <div class="input-group">
                <input type="password" name="confirm_password" id="pwd2" class="form-control" autocomplete="new-password"
                       placeholder="Ulangi password">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd2','eyePwd2')">
                  <i class="bi bi-eye" id="eyePwd2"></i>
                </button>
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="aktif" id="aktifSwitch"
                       <?= ($user['aktif']??1) ? 'checked' : '' ?>>
                <label class="form-check-label fw-600" for="aktifSwitch">Akun Aktif</label>
              </div>
            </div>
          </div>
          <hr class="my-4">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 fw-bold">
              <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
            </button>
            <a href="index.php?p=users" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Role info -->
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-body">
        <h6 class="fw-700 mb-3">Informasi Hak Akses</h6>
        <div class="row g-2 small">
          <?php foreach ([
            ['dark','Administrator','Akses penuh: CRUD proyek, WBS, pengguna, laporan, semua modul.'],
            ['primary','Manajer Proyek','Kelola WBS, lihat semua data, setujui progres, cetak laporan EVM.'],
            ['success','Staf Lapangan','Input progres fisik harian/mingguan per paket WBS.'],
            ['info','Staf Keuangan','Input dan verifikasi realisasi biaya aktual per periode.'],
          ] as [$c,$r,$d]): ?>
          <div class="col-12">
            <div class="d-flex gap-2 align-items-start">
              <span class="badge bg-<?= $c ?> mt-1"><?= $r ?></span>
              <span class="text-muted"><?= $d ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePwd(id, iconId) {
  const el = document.getElementById(id);
  const ic = document.getElementById(iconId);
  el.type = el.type === 'password' ? 'text' : 'password';
  ic.className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
