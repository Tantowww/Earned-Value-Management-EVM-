<?php
// includes/layout/sidebar.php
$p     = $_GET['p'] ?? 'dashboard';
$user  = currentUser();
$role  = $user['role'] ?? '';

$navItems = [
    ['p' => 'dashboard', 'icon' => 'speedometer2',   'label' => 'Dashboard EVM',    'roles' => []],
    ['p' => 'proyek',    'icon' => 'buildings',       'label' => 'Data Proyek',      'roles' => []],
    ['p' => 'wbs',       'icon' => 'diagram-3',       'label' => 'WBS & Anggaran',   'roles' => ['admin','manager']],
    ['p' => 'progres',   'icon' => 'graph-up-arrow',  'label' => 'Input Progres',    'roles' => ['admin','manager','staf_lapangan']],
    ['p' => 'biaya',     'icon' => 'cash-stack',      'label' => 'Input Biaya Aktual','roles' => ['admin','manager','staf_keuangan']],
    ['p' => 'evm',       'icon' => 'bar-chart-line',  'label' => 'Analisis EVM',     'roles' => ['admin','manager']],
    ['p' => 'laporan',   'icon' => 'file-earmark-pdf','label' => 'Laporan',          'roles' => ['admin','manager']],
    ['p' => 'users',     'icon' => 'people',          'label' => 'Manajemen Pengguna','roles' => ['admin']],
];
?>
<nav id="sidebar" class="sidebar d-flex flex-column">
  <!-- Project info box -->
  <div class="sidebar-project-box mx-2 my-3 p-3 rounded-3">
    <div class="d-flex align-items-center gap-2 mb-1">
      <i class="bi bi-tree text-success fs-5"></i>
      <span class="fw-bold text-white small">Proyek Aktif</span>
    </div>
    <?php
    $activeProj = dbFetchOne("SELECT nama_proyek, minggu_ke, total_minggu FROM proyek WHERE status='aktif' LIMIT 1");
    if ($activeProj):
      $pct = $activeProj['total_minggu'] > 0
           ? round($activeProj['minggu_ke'] / $activeProj['total_minggu'] * 100, 0)
           : 0;
    ?>
    <div class="text-white-50 small mb-1" style="font-size:11px"><?= e(mb_strimwidth($activeProj['nama_proyek'], 0, 45, '…')) ?></div>
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span style="font-size:10px;color:rgba(255,255,255,0.5)">Minggu <?= $activeProj['minggu_ke'] ?>/<?= $activeProj['total_minggu'] ?></span>
      <span style="font-size:10px;color:#6ee7b7"><?= $pct ?>%</span>
    </div>
    <div class="progress" style="height:4px;background:rgba(255,255,255,0.15)">
      <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
    </div>
    <?php else: ?>
    <div class="text-white-50 small">Tidak ada proyek aktif</div>
    <?php endif; ?>
  </div>

  <div class="px-2" style="font-size:10px;color:rgba(255,255,255,0.35);letter-spacing:1px;text-transform:uppercase;padding-left:16px !important">MENU UTAMA</div>

  <ul class="nav flex-column px-2 mt-1 flex-grow-1">
    <?php foreach ($navItems as $item):
      if (!empty($item['roles']) && !in_array($role, $item['roles'])) continue;
      $active = ($p === $item['p']);
    ?>
    <li class="nav-item mb-1">
      <a href="index.php?p=<?= $item['p'] ?>"
         class="nav-link sidebar-link <?= $active ? 'active' : '' ?> d-flex align-items-center gap-2">
        <i class="bi bi-<?= $item['icon'] ?> fs-6"></i>
        <span class="sidebar-label"><?= e($item['label']) ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>

  <!-- Bottom info -->
  <div class="px-3 pb-3 mt-auto">
    <hr style="border-color:rgba(255,255,255,0.1)">
    <div class="d-flex align-items-center gap-2">
      <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold">
        <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
      </div>
      <div class="sidebar-label">
        <div style="font-size:11px;color:#fff;font-weight:600"><?= e(mb_strimwidth($user['nama_lengkap'] ?? '', 0, 18, '…')) ?></div>
        <div style="font-size:10px;color:rgba(255,255,255,0.45)"><?= e(roleLabel($user['role'] ?? '')) ?></div>
      </div>
    </div>
  </div>
</nav>
