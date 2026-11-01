<?php
// pages/login.php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = dbFetchOne("SELECT * FROM users WHERE username = ? AND aktif = 1", [$username]);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = $user;
            setFlash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
            redirect('index.php?p=dashboard');
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Username dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<style>
body {
  background: 
    linear-gradient(135deg, rgba(6, 78, 59, 0.75), rgba(4, 120, 87, 0.75)),
    url('../evm_proyek/pages/taman1.png') no-repeat center center;
  background-size: cover;
  min-height: 100vh;
  color: #fff; /* teks di luar kartu menjadi putih */
}

.login-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 25px 80px rgba(0, 0, 0, 0.45);
}

.login-brand {
  background: linear-gradient(135deg, #047857, #10b981);
  border-radius: 20px 20px 0 0;
  color: #fff;
  padding: 24px;
}

.demo-box {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  font-size: 13px;
  padding: 12px;
}
</style>
</head>
<body class="d-flex align-items-center justify-content-center p-4">
<div class="container" style="max-width:420px">
  <div class="login-card overflow-hidden">
    <!-- Brand Header -->
    <div class="login-brand p-4 text-center text-white">
      <div class="fs-1 mb-2"><i class="bi bi-tree-fill"></i></div>
      <h4 class="fw-800 mb-0"><?= APP_NAME ?></h4>
      <div class="opacity-75 small">Sistem Informasi Manajemen Proyek EVM</div>
      <div class="small opacity-50 mt-1">PT. Pesona Tamanindo</div>
    </div>

    <!-- Login Form -->
    <div class="p-4">
      <h5 class="fw-bold text-dark mb-4"><i class="bi bi-box-arrow-in-right me-2 text-primary"></i>Masuk ke Sistem</h5>

      <?php if ($error): ?>
      <div class="alert alert-danger alert-sm py-2 mb-3">
        <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-600 small">Username</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
            <input type="text" name="username" class="form-control border-start-0 ps-0"
                   placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-600 small">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" name="password" id="pwdInput" class="form-control border-start-0 border-end-0 ps-0"
                   placeholder="Masukkan password" required>
            <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePwd()">
              <i class="bi bi-eye" id="pwdIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>
      </form>

      <!-- Demo accounts -->
      <div class="demo-box mt-4 p-3">
        <div class="fw-bold mb-2 text-primary small"><i class="bi bi-info-circle me-1"></i>Akun Demo (password: tanya admin)</div>
        <div class="row g-1">
          <?php foreach ([
            ['dani','Admin','dark'],
            ['fauzi','Manager','primary'],
            ['zamzam','Staf Keuangan','success'],
            ['ayue','Staf Lapangan','info'],
          ] as [$u,$l,$c]): ?>
          <div class="col-6">
            <button type="button" class="btn btn-outline-<?= $c ?> btn-sm w-100 py-1 demo-fill"
                    data-user="<?= $u ?>">
              <?= $l ?>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <p class="text-center text-white-50 small mt-3 mb-0">
    WEBSITE PROYEK EVM - PT. PESONA TAMANINDO 
    <br>Created by- Nafisha Azzahra 11222104    
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd() {
  const i = document.getElementById('pwdInput');
  const icon = document.getElementById('pwdIcon');
  i.type = i.type === 'password' ? 'text' : 'password';
  icon.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
document.querySelectorAll('.demo-fill').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelector('[name=username]').value = btn.dataset.user;
    document.querySelector('[name=password]').value = 'indomaret123';
  });
});
</script>
</body>
</html>
