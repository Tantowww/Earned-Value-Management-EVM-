<?php
// pages/users/index.php
requireRole(['admin']);

if (($_GET['action'] ?? '') === 'delete') {
    $uid = (int)($_GET['id'] ?? 0);
    if ($uid === $_SESSION['user_id']) { setFlash('danger','Tidak dapat menghapus akun sendiri.'); }
    else { dbDelete('users','id=?',[$uid]); setFlash('success','Pengguna berhasil dihapus.'); }
    redirect('index.php?p=users');
}
if (($_GET['action'] ?? '') === 'toggle') {
    $uid = (int)($_GET['id'] ?? 0);
    $cur = dbFetchOne("SELECT aktif FROM users WHERE id=?",[$uid]);
    if ($cur) { dbUpdate('users',['aktif'=>$cur['aktif']?0:1],'id=?',[$uid]); setFlash('success','Status pengguna diubah.'); }
    redirect('index.php?p=users');
}

$users = dbFetchAll("SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
$pageTitle = 'Manajemen Pengguna';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-people me-2 text-primary"></i>Manajemen Pengguna</h4>
    <div class="text-muted small">Kelola akun dan hak akses pengguna sistem</div>
  </div>
  <a href="index.php?p=users&a=create" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Tambah Pengguna
  </a>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
  <?php
  $roles = ['admin'=>'Administrator','manager'=>'Manajer Proyek','staf_lapangan'=>'Staf Lapangan','staf_keuangan'=>'Staf Keuangan'];
  $colors = ['admin'=>'dark','manager'=>'primary','staf_lapangan'=>'success','staf_keuangan'=>'info'];
  foreach ($roles as $r => $lbl):
    $cnt = count(array_filter($users, fn($u) => $u['role'] === $r));
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-<?= $colors[$r] ?>-subtle">
          <i class="bi bi-person-badge fs-4 text-<?= $colors[$r] ?>"></i>
        </div>
        <div><div class="fw-800 fs-4"><?= $cnt ?></div><div class="text-muted small"><?= $lbl ?></div></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>No</th><th>Nama Lengkap</th><th>Username</th>
            <th>Email</th><th class="text-center">Role</th>
            <th class="text-center">Status</th><th class="text-center">Bergabung</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $i => $u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                     style="width:36px;height:36px;font-size:14px;flex-shrink:0">
                  <?= strtoupper(substr($u['nama_lengkap'],0,1)) ?>
                </div>
                <div>
                  <div class="fw-600"><?= e($u['nama_lengkap']) ?></div>
                  <?php if ($u['id'] === $_SESSION['user_id']): ?>
                  <span class="badge bg-primary" style="font-size:9px">Akun Anda</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><code><?= e($u['username']) ?></code></td>
            <td class="text-muted small"><?= e($u['email'] ?? '-') ?></td>
            <td class="text-center"><?= roleBadge($u['role']) ?></td>
            <td class="text-center">
              <a href="index.php?p=users&action=toggle&id=<?= $u['id'] ?>"
                 class="badge <?= $u['aktif']?'bg-success':'bg-secondary' ?> text-decoration-none">
                <?= $u['aktif'] ? 'Aktif' : 'Non-aktif' ?>
              </a>
            </td>
            <td class="text-center text-muted small"><?= date('d/m/Y',strtotime($u['created_at'])) ?></td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <a href="index.php?p=users&a=create&id=<?= $u['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <a href="index.php?p=users&action=delete&id=<?= $u['id'] ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Hapus pengguna <?= e($u['nama_lengkap']) ?>?')">
                  <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
