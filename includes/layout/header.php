<?php
// includes/layout/header.php
$flash     = getFlash();
$user      = currentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<!-- App CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<!-- TOPBAR -->
<nav class="navbar navbar-dark topbar px-3 py-2">
  <div class="d-flex align-items-center gap-2">
    <span class="topbar-brand">
      <i class="bi bi-tree-fill me-1 text-success-emphasis"></i>
      <?= APP_NAME ?>
    </span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span class="text-white-50 d-none d-md-inline" style="font-size:12px">
      <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i') ?> WIB
    </span>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <div class="avatar-sm"><i class="bi bi-person-fill"></i></div>
        <span class="d-none d-md-inline"><?= e($user['nama_lengkap'] ?? 'User') ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header"><?= e($user['nama_lengkap'] ?? '') ?></h6></li>
        <li><span class="dropdown-item-text text-muted small"><?= e(roleLabel($user['role'] ?? '')) ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="index.php?p=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- WRAPPER -->
<div class="d-flex" id="wrapper">

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTENT -->
  <div id="page-content" class="flex-grow-1 p-4" style="min-height:calc(100vh - 56px);overflow-y:auto">

    <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'x-circle' : 'info-circle') ?> me-2"></i>
      <?= e($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
